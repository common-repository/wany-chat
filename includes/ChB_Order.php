<?php

namespace ChatBot;

class ChB_Order {
	public const ORDER_CREATED_VIA_BOT = 'rrbot';
	public const ORDER_STATUS_COMPLETED = 'completed';
	public const ORDER_STATUS_CANCELLED = 'cancelled';

	private const INFO_ATTR_SHIPPING_OPTION_ID = 'shp_id';
	private const INFO_ATTR_PAYMENT_OPTION_ID = 'pmt_id';

	public static function orderStatusExists( $status ) {
		return wc_is_order_status( 'wc-' . $status );
	}

	public static function getEarlyCancelledStatus() {
		return ( empty( ChB_Settings()->getParam( 'order_statuses' )['early-cancelled'] ) ? '' : ChB_Settings()->getParam( 'order_statuses' )['early-cancelled'] );
	}

	public static function getShippedStatus() {
		return ( empty( ChB_Settings()->getParam( 'order_statuses' )['shipped'] ) ? '' : ChB_Settings()->getParam( 'order_statuses' )['shipped'] );
	}

	public static function getInitStatus() {
		return ( empty( ChB_Settings()->getParam( 'order_statuses' )['init_status'] ) ? 'pending' : ChB_Settings()->getParam( 'order_statuses' )['init_status'] );
	}

	public static function getToShipStatus() {
		return ( empty( ChB_Settings()->getParam( 'order_statuses' )['to-ship'] ) ? 'processing' : ChB_Settings()->getParam( 'order_statuses' )['to-ship'] );
	}

	public static function getStatuses4StatsCompleted() {
		if ( ! empty( ChB_Settings()->getParam( 'order_statuses' )['stats']['completed'] ) ) {
			$completed_statuses = ChB_Settings()->getParam( 'order_statuses' )['stats']['completed'];
		}

		if ( empty( $completed_statuses ) || ! in_array( ChB_Order::ORDER_STATUS_COMPLETED, $completed_statuses ) ) {
			$completed_statuses[] = ChB_Order::ORDER_STATUS_COMPLETED;
		}

		return $completed_statuses;
	}

	public static function createNewOrderFromCart( &$cart_details, ChB_PaymentOption $payment_option, ChB_User $user ) {

		$new_order = null;
		foreach ( $cart_details['products_details'] as $product_details ) {
			$quantity   = $product_details['quantity'];
			$wc_product = $product_details['wc_product'];
			$args       = [];
			if ( ! empty( $product_details['pa_filter'] ) ) {
				$args['variation'] = ChB_Catalogue::convertPAFilterToWCVariationFormat( $product_details['pa_filter'] );
			}

			$args['total']    = $product_details['total'];
			$args['subtotal'] = $product_details['subtotal'];

			if ( $new_order === null ) {
				$new_order = wc_create_order( [
					'customer_id' => $user->wp_user->ID,
					'created_via' => self::ORDER_CREATED_VIA_BOT
				] );
			}

			$new_order->add_product( $wc_product, $quantity, $args );
		}

		if ( ! $new_order ) {
			return null;
		}

		$order_info = [];

		if ( ! empty( $cart_details['shipping_details'] ) && $cart_details['shipping_details'] instanceof ChB_ShippingDetails ) {
			$cart_details['shipping_details']->addShippingToOrder( $new_order );
			if ( $cart_details['shipping_details']->getShippingOptionId() ) {
				$order_info[ self::INFO_ATTR_SHIPPING_OPTION_ID ] = $cart_details['shipping_details']->getShippingOptionId();
			}
		}

		if ( $cart_details['num_of_lines'] > 1 ) {
			$order_info['grp_img'] = $cart_details['grp_img'];
		}
		if ( ! empty( $cart_details['promo_conditions'] ) ) {
			$order_info['promo_conditions'] = $cart_details['promo_conditions'];
		}
		if ( $payment_option->id ) {
			$order_info[ self::INFO_ATTR_PAYMENT_OPTION_ID ] = $payment_option->id;
		}

		$new_order->update_meta_data( ChB_Common::ORDER_ATTR_RRBOT_INFO, json_encode( $order_info ) );

		$new_order->set_total( $cart_details['total'] );
		$new_order->set_status( $payment_option->order_init_status );

		$new_order->set_payment_method( $payment_option->title );

		$new_order->save();
		$new_order_id = $new_order->get_id();

		if ( $new_order_id ) {
			$tags = ChB_Analytics::mergeEventTags( $cart_details['products_details'] );
			ChB_Analytics::registerEvent( ChB_Analytics::EVENT_CONFIRM_ORDER, [ 'tags' => $tags ], $user->fb_user_id );
			ChB_Common::my_log( $tags, true, 'Order #' . $new_order_id . ' tags' );
		}

		return $new_order_id;
	}

	/**
	 * @param $order
	 *
	 * @return \WC_Order|false
	 */
	public static function getWCOrder( $order ) {

		if ( ! ( $order instanceof \WC_Order ) ) {
			if ( ! ( ( $order = wc_get_order( $order ) ) instanceof \WC_Order ) ) {
				return false;
			}
		}

		return $order;
	}

	/**
	 * @param \WC_Order|string|int $order
	 * @param ChB_PaymentOption $payment_option
	 * @param ChB_PaymentOption $prev_payment_option
	 *
	 * @return bool
	 * @throws \WC_Data_Exception
	 */
	public static function changePaymentOption4Order( $order, ChB_PaymentOption $payment_option, ChB_PaymentOption $prev_payment_option ) {

		if ( ! ( $order = ChB_Order::getWCOrder( $order ) ) ||
		     $order->get_status() !== $prev_payment_option->order_init_status
		) {
			return false;
		}

		$rrbot_order_info = json_decode( $order->get_meta( ChB_Common::ORDER_ATTR_RRBOT_INFO, true ), true );
		if ( ! is_array( $rrbot_order_info ) ) {
			$rrbot_order_info = [];
		}

		$rrbot_order_info[ self::INFO_ATTR_PAYMENT_OPTION_ID ] = $payment_option->id;
		$order->update_meta_data( ChB_Common::ORDER_ATTR_RRBOT_INFO, json_encode( $rrbot_order_info ) );
		$order->set_status( $payment_option->order_init_status );
		$order->set_payment_method( $payment_option->title );
		$order->save();

		return true;
	}

	/**
	 * @param \WC_Order|string $order
	 * @param ChB_PromoItem|null $promo_item4conditions
	 * @param array|null $custom_fields
	 *
	 * @return array|false
	 */
	public static function getOrderDetails( $order, ?ChB_PromoItem $promo_item4conditions = null, $custom_fields = null ) {

		if ( ! ( $order = self::getWCOrder( $order ) ) ) {
			return false;
		}

		$order_details = [
			'order_id'         => $order->get_id(),
			'phone'            => $order->get_billing_phone(),
			'status'           => $order->get_status(),
			'payment_method'   => $order->get_payment_method(),
			'display_name'     => self::getNameFromOrder( $order ),
			'products_details' => [],
		];

		if ( ! ChB_User::emailIsDummy( $email = $order->get_billing_email() ) ) {
			$order_details['email'] = $email;
		}

		$order_subtotal = 0;
		foreach ( $order->get_items( [ 'line_item' ] ) as $item_id => $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$item_total                  = $item->get_total();
				$product_details             = [
					'item_id'  => $item_id,
					'quantity' => $item->get_quantity()
				];
				$item_subtotal               = $item->get_subtotal();
				$product_details['total']    = $item_total;
				$product_details['subtotal'] = $item_subtotal;
				$order_subtotal              += $item_subtotal;
				$wc_product                  = $item->get_product();

				$product_attrs = $wc_product->get_attributes();
				$pa_filter     = [];
				foreach ( $item->get_meta_data() as $metadata ) {
					if ( isset( $product_attrs[ $metadata->key ] ) ) {
						$pa_filter[ $metadata->key ] = $metadata->value;
					}
				}
				$product_details['pa_filter'] = $pa_filter;

				self::fillInProductDetails( $product_details, $wc_product );
				$order_details['products_details'][] = $product_details;
				$order_details['pa_filter']          = self::getPaFilterFromOrderItemProduct( $item );
			}
		}
		$order_details['shipping_details'] = new ChB_ShippingDetails( null, $order );

		$rrbot_order_info = json_decode( $order->get_meta( ChB_Common::ORDER_ATTR_RRBOT_INFO, true ), true );
		if ( ! empty( $rrbot_order_info[ self::INFO_ATTR_SHIPPING_OPTION_ID ] ) ) {
			$order_details['shipping_details']->setShippingOptionId( $rrbot_order_info[ self::INFO_ATTR_SHIPPING_OPTION_ID ] );
			$order_details['shipping_option_id'] = $order_details['shipping_details']->getShippingOptionId();
		} else {
			$order_details['shipping_option_id'] = null;
		}

		if ( ! empty( $rrbot_order_info[ self::INFO_ATTR_PAYMENT_OPTION_ID ] ) ) {
			$order_details['payment_option_id'] = $rrbot_order_info[ self::INFO_ATTR_PAYMENT_OPTION_ID ];
		} else {
			$order_details['payment_option_id'] = null;
		}

		$order_subtotal += $order_details['shipping_details']->getShippingTotal();
		$order_total    = $order->get_total();
		if ( $order_total != $order_subtotal && $promo_item4conditions ) {
			$order_details['promo_conditions'] = $promo_item4conditions->printPromo4UserConditions();
		}

		$order_details['total']    = $order_total;
		$order_details['subtotal'] = $order_subtotal;

		$rrbot_order_info = $order->get_meta( ChB_Common::ORDER_ATTR_RRBOT_INFO, true );
		if ( ! empty( $rrbot_order_info ) ) {
			$rrbot_order_info = json_decode( $rrbot_order_info, true );
			if ( ! empty( $rrbot_order_info['grp_img'] ) ) {
				$order_details['grp_img'] = $rrbot_order_info['grp_img'];
			}
			if ( ! empty( $rrbot_order_info['promo_conditions'] ) ) {
				if ( empty( $order_details['promo_conditions'] ) ) {
					$order_details['promo_conditions'] = $rrbot_order_info['promo_conditions'];
				} else {
					$order_details['promo_conditions'] .= chr( 10 ) . $rrbot_order_info['promo_conditions'];
				}
			}
		}

		if ( empty( $order_details['grp_img'] ) && sizeof( $order_details['products_details'] ) > 0 ) {
			$order_details['grp_img'] = [
				'aspect' => 'square',
				'url'    => $order_details['products_details'][0]['image_url']
			];
		}

		if ( $custom_fields ) {
			foreach ( $custom_fields as $custom_field => $custom_field_meta_key ) {
				$order_details[ $custom_field ] = $order->get_meta( $custom_field_meta_key, true );
			}
		}

		return $order_details;
	}

	private static function getPaFilterFromOrderItemProduct( \WC_Order_Item_Product $item ) {
		$res        = [];
		$wc_product = $item->get_product();
		$attrs      = $wc_product->get_attributes();
		foreach ( $item->get_meta_data() as $mdata ) {
			$data = $mdata->get_data();
			if ( isset( $attrs[ $data['key'] ] ) && ! empty( $data['value'] ) ) {
				$res[ $data['key'] ] = $data['value'];
			}
		}

		return $res;
	}

	public static function fillInProductDetails( &$product_details, \WC_Product $wc_product ) {

		$product_details['title']      = htmlspecialchars_decode( $wc_product->get_title() );
		$product_details['image_url']  = ChB_Common::getAttachmentMediumSizeUrl( $wc_product->get_image_id() );
		$product_details['SKU']        = $wc_product->get_sku();
		$product_details['var_id']     = $wc_product->get_id();
		$product_details['wc_product'] = $wc_product;

		if ( $wc_product instanceof \WC_Product_Variation ) {
			$product_details['attrs_str']  = ChB_Catalogue::printPaFilter( $wc_product, $product_details['pa_filter'] );
			$product_details['product_id'] = $wc_product->get_parent_id();
		} else {
			$product_details['product_id'] = $product_details['var_id'];
		}
	}

	public static function setOrderStatus( $order_id, $new_status, $manager_id ) {
		$order = wc_get_order( $order_id );
		if ( ! ( $order instanceof \WC_Order ) ) {
			return false;
		}
		$status = $order->get_status();
		if ( $status == $new_status ) {
			return false;
		}

		//early cancellation validation
		$early_cancelled_status = self::getEarlyCancelledStatus();
		if ( $early_cancelled_status && $new_status === $early_cancelled_status ) {
			if ( $status !== self::getInitStatus() && $status !== self::getToShipStatus() ) {
				return false;
			}
		}

		$order->set_status( $new_status, '', true );
		$rrbot_order_info                          = $order->get_meta( ChB_Common::ORDER_ATTR_RRBOT_INFO, true );
		$rrbot_order_info                          = ( empty( $rrbot_order_info ) ? [] : json_decode( $rrbot_order_info, true ) );
		$rrbot_order_info['status'][ $new_status ] = $manager_id;
		$order->update_meta_data( ChB_Common::ORDER_ATTR_RRBOT_INFO, json_encode( $rrbot_order_info ) );
		$order->save();

		return true;
	}

	public static function getOrderDetails4Notification( \WC_Order $wc_order, $new_status ) {
		$res = [
			'order_id'          => $wc_order->get_id(),
			'order_created_via' => $wc_order->get_created_via()
		];

		//ignoring notifications for managers who has set the new status
		$manager_id       = null;
		$rrbot_order_info = $wc_order->get_meta( ChB_Common::ORDER_ATTR_RRBOT_INFO, true );
		if ( $rrbot_order_info ) {
			$rrbot_order_info = json_decode( $rrbot_order_info, true );
			if ( ! empty( $rrbot_order_info['status'][ $new_status ] ) ) {
				$manager_id   = $rrbot_order_info['status'][ $new_status ];
				$manager_user = ChB_User::initUser( $manager_id );
				if ( $manager_user ) {
					$res['manager_display_name'] = $manager_user->getUserDisplayName();
				}
			}
		}

		$res['managers2notify'] = ChB_Manager_Settings::getManagers2NotifyOnOrders( $new_status, $manager_id );

		return $res;
	}

	/**
	 * @param $product \WC_Product|\WC_Product_Variable|\WC_Product_Variation|String
	 *
	 * @return int|mixed|null
	 */
	public static function getAvailableStock( $product ) {
		if ( empty( $product ) ) {
			return 0;
		}

		$wc_product = $product instanceof \WC_Product ? $product : wc_get_product( $product );
		if ( ! $wc_product ) {
			return 0;
		}

		if ( $wc_product->get_status() !== 'publish' ) {
			return 0;
		}

		if ( $wc_product->managing_stock() ) {
			return $wc_product->get_stock_quantity();
		}

		if ( $wc_product->get_stock_status() === 'instock' ) {
			return ChB_Settings()->getParam( 'max_input_quantity' );
		}

		return 0;
	}

	public static function getProductsOrderedByUser( ChB_User $user, $use_variations = false ) {
		$order_statuses_to_ignore = [ 'wc-pending' ];

		$customer_orders = wc_get_orders( [
			'meta_key'    => '_customer_user',
			'meta_value'  => $user->wp_user->ID,
			'numberposts' => - 1
		] );

		$res = [];
		foreach ( $customer_orders as $order ) {
			$status = $order->get_status();
			if ( in_array( $status, $order_statuses_to_ignore ) ) {
				continue;
			}

			foreach ( $order->get_items( [ 'line_item' ] ) as $item_id => $item ) {
				if ( $item instanceof \WC_Order_Item_Product ) {
					if ( $use_variations ) {
						$product_id = $item->get_variation_id();
						if ( $product_id == 0 ) {
							$product_id = $item->get_product_id();
						}
					} else {
						$product_id = $item->get_product_id();
					}

					if ( ! in_array( $product_id, $res ) ) {
						$res[] = $product_id;
					}
				}
			}
		}

		return $res;
	}

	public static function getEstimateDeliveryDates( $timestamp ) {
		if ( ChB_Settings()->getParam( 'shipping_cost_code' ) === ChB_Common::SHIPPING_WOO ) {
			return null;
		}

		$delivery_estimates = ChB_Settings()->getParam( 'delivery_estimates' );
		if ( empty( $delivery_estimates['days_range']['from'] ) ||
		     empty( $delivery_estimates['days_range']['to'] ) ||
		     empty( $delivery_estimates['midday'] ) ) {
			return null;
		}

		$days_range    = $delivery_estimates['days_range'];
		$midday        = $delivery_estimates['midday'];
		$sunday_plus_1 = ! empty( $delivery_estimates['sunday_plus_1'] );

		$from = new \DateTime( 'now', ChB_Settings()->timezone );
		$from->setTimestamp( $timestamp );
		$to = new \DateTime( 'now', ChB_Settings()->timezone );
		$to->setTimestamp( $timestamp );

		$hour             = $to->format( 'H' );
		$num_of_day_today = $to->format( 'N' ) % 7;

		if ( $hour >= $midday ) {
			$days_range['to'] ++;
		}

		$from->modify( '+' . $days_range['from'] . ' day' );
		if ( $sunday_plus_1 && $num_of_day_today >= ( $from->format( 'N' ) % 7 ) ) //crossing Sunday
		{
			$from->modify( '+1 day' );
		}

		$to->modify( '+' . $days_range['to'] . ' day' );
		if ( $sunday_plus_1 && $num_of_day_today >= ( $to->format( 'N' ) % 7 ) ) //crossing Sunday
		{
			$to->modify( '+1 day' );
		}

		return [ 'from' => $from->format( 'd.m.Y' ), 'to' => $to->format( 'd.m.Y' ) ];
	}

	public static function getNameFromOrder( \WC_Order $wc_order ) {
		return trim( ( $wc_order->get_billing_first_name() ? $wc_order->get_billing_first_name() : $wc_order->get_shipping_first_name() ) . ' ' .
		             ( $wc_order->get_billing_last_name() ? $wc_order->get_billing_last_name() : $wc_order->get_shipping_last_name() ) );
	}

	public static function getOrderSummary( \WC_Order $wc_order ) {
		return [
			'user_name'       => self::getNameFromOrder( $wc_order ),
			'date_created'    => $wc_order->get_date_created()->setTimezone( ChB_Settings()->timezone )->format( 'd.m.Y' ),
			'ts_date_created' => $wc_order->get_date_created()->getTimestamp(),
			'number'          => $wc_order->get_order_number(),
			'total'           => $wc_order->get_total()
		];
	}
}