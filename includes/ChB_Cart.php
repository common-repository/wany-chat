<?php


namespace ChatBot;

class ChB_Cart {

	public ChB_User $cart_user;
	public ?ChatBot $ChB;
	private int $storage_wp_user_id;
	private array $_data;
	private bool $_data_changed = false;
	private array $wc_products;
	private \WC_Product_Factory $pf;
	private array $out_messages;
	private int $cart_type;

	private array $_totals;
	private ?string $_active_shipping_optionId;

	public function __construct( $wp_user_id, ChB_User $cart_user ) {

		if ( $cart_pack = get_user_meta( $wp_user_id, ChB_User::USER_ATTR_CART, true ) ) {
			$data = json_decode( $cart_pack, true );
		}

		if ( empty( $data ) || empty( $data['t'] ) ) {
			$data = [
				'items' => [],
				't'     => 1
			];
		}

		$this->storage_wp_user_id = $wp_user_id;
		$this->_data              = $data;
		$this->cart_type          = $data['t'];
		$this->out_messages       = [];
		$this->cart_user          = $cart_user;

		add_action( 'shutdown', function () {
			$this->updateInDB();
		}, 20 );
	}

	public static function copyCartFromCustomer( $wp_user_id, $customer_fb_user_id, $only_check ) {
		if ( ! $customer_fb_user_id ) {
			return false;
		}
		$customer_user = ChB_User::initUser( $customer_fb_user_id );
		if ( ! $customer_user ) {
			return false;
		}
		$customer_cart = new ChB_Cart( $customer_user->wp_user_id, $customer_user );

		if ( $customer_cart->isEmpty() ) {
			return false;
		}
		if ( $only_check ) {
			return true;
		}

		$customer_cart->storage_wp_user_id = $wp_user_id;
		$customer_cart->_data_changed      = true;
		$customer_cart->refreshCartVersion();
		$customer_cart->updateInDB();

		return $customer_cart;
	}

	public function getActiveShippingOptionId() {
		return isset( $this->_active_shipping_optionId ) ? $this->_active_shipping_optionId : false;
	}

	public function activateSavedShippingOptionId() {
		$this->_active_shipping_optionId = isset( $this->_data['so'] ) ? $this->_data['so'] : null;
	}

	public function setShippingOptionId( $val ) {
		if ( is_string( $val ) ) {
			$this->_data['so']   = $val;
			$this->_data_changed = true;
			$this->refreshCartVersion();
		}
	}

	public function getPaymentOptionId() {
		return isset( $this->_data['po'] ) ? $this->_data['po'] : null;
	}

	public function setPaymentOptionId( $val ) {
		if ( is_string( $val ) ) {
			$this->_data['po']   = $val;
			$this->_data_changed = true;
			//NOT updating cart version here
		}
	}

	public function getProductTotals( $is_sum = true, $after_discount = true ) {
		$key = 'products' . ( $after_discount ? '_total' : '_subtotal' ) . ( $is_sum ? '_sum' : '_qty' );

		return ( isset( $this->_totals[ $key ] ) ? $this->_totals[ $key ] : null );
	}

	/**
	 * @param $variation_id
	 * @param $pa_filter false|array
	 *
	 * @return int|string|null
	 */
	public function getCartItemId( $variation_id, $pa_filter ) {
		foreach ( $this->_data['items'] as $item_id => $item ) {
			if ( $item['var_id'] == $variation_id &&
			     ( $pa_filter === false || ChB_Common::arraysHaveTheSameKeysAndValues( $item['pa_filter'], $pa_filter ) )
			) {
				return $item_id;
			}
		}

		return null;
	}

	public function updateCart( $variation_id, $pa_filter, $item_id, $q, $event_tags ) {

		$refresh_grp_img = false;
		$data_changed    = false;
		if ( $variation_id ) {
			//looks like an old item in the cart
			if ( $item_id && ! isset( $this->_data['items'][ $item_id ] ) ) {
				return;
			}

			$wc_product = $this->get_wc_product( $variation_id );
			//cannot buy product without a price
			if ( $wc_product->get_price() === '' ) {
				return;
			}

			if ( ! $pa_filter && $wc_product instanceof \WC_Product_Variation ) {
				$pa_filter = ChB_Catalogue::getPAFilterByVariation( $wc_product );
			}

			$text            = null;
			$refresh_grp_img = false;
			if ( ! $item_id ) {
				$item_id = $this->getCartItemId( $variation_id, $pa_filter );
			}

			if ( $q == 0 ) {
				if ( $item_id ) {
					$name                 = htmlspecialchars_decode( $wc_product->get_name() );
					$this->out_messages[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0097, $name );
					unset( $this->_data['items'][ $item_id ] );
					$refresh_grp_img = true;
					$data_changed    = true;
				}
			} else {
				$name           = htmlspecialchars_decode( $wc_product->get_name() );
				$stock_quantity = ChB_Order::getAvailableStock( $wc_product );

				if ( $stock_quantity == 0 ) {
					$this->out_messages[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0100, $name );
				} else {
					if ( $q > $stock_quantity ) {
						$this->out_messages[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0099, $name, ChB_Common::printNumberNoSpaces( $stock_quantity ) );
						$q                    = $stock_quantity;
					}

					if ( $item_id ) {
						$old_q          = $this->_data['items'][ $item_id ]['q'];
						$new_event_tags = ! empty( $this->_data['items'][ $item_id ]['et'] ) ? $this->_data['items'][ $item_id ]['et'] : $event_tags;
					} else {
						$item_id        = ChB_Common::my_rand_string( 4 );
						$old_q          = 0;
						$new_event_tags = ( empty( $event_tags ) ? null : $event_tags );
					}

					if ( $old_q != $q ) {
						if ( $old_q ) {
							$this->out_messages[] = ChB_Lang::translateWithPars(
								ChB_Lang::LNG0096,
								$name,
								ChB_Common::printNumberNoSpaces( $q ) );
						} else {
							$refresh_grp_img = true;
						}

						$this->_data['items'][ $item_id ] = [
							'var_id'    => $variation_id,
							'q'         => $q,
							't'         => time(),
							'pa_filter' => $pa_filter
						];
						if ( ! empty( $new_event_tags ) ) {
							$this->_data['items'][ $item_id ]['et'] = $new_event_tags;
						}

						$data_changed = true;
					}
				}
			}
		}

		if ( $data_changed ) {
			$this->_data_changed = true;
			$this->refreshCartVersion();
		}

		$this->actualizeCartStock( $refresh_grp_img );
	}

	public function actualizeCartStock( $refresh_grp_img = false ) {

		$data_changed = false;
		if ( ! empty( $this->_data['items'] ) ) {
			$vars_stock = [];
			foreach ( $this->_data['items'] as $item_id => &$item ) {
				$variation_id = $item['var_id'];
				$wc_product   = $this->get_wc_product( $variation_id );
				if ( ! $wc_product ) {
					//looks like variation has been deleted
					unset( $this->_data['items'][ $item_id ] );
					$data_changed    = true;
					$refresh_grp_img = true;
					continue;
				}

				$quantity = $item['q'];

				if ( ! isset( $vars_stock[ $variation_id ] ) ) {
					$vars_stock[ $variation_id ] = ChB_Order::getAvailableStock( $wc_product );
				}

				//Проверяем и обновляем сток в корзине
				if ( $vars_stock[ $variation_id ] <= 0 ) {
					$name                 = htmlspecialchars_decode( $wc_product->get_title() );
					$this->out_messages[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0098, $name );
					unset( $this->_data['items'][ $item_id ] );
					$data_changed    = true;
					$refresh_grp_img = true;
					continue;
				}

				if ( $vars_stock[ $variation_id ] < $quantity ) {
					$name                 = htmlspecialchars_decode( $wc_product->get_title() );
					$this->out_messages[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0099,
						$name,
						ChB_Common::printNumberNoSpaces( $vars_stock[ $variation_id ] ) );
					$quantity             = $vars_stock[ $variation_id ];
					$item['q']            = $quantity;
					$data_changed         = true;
				}

				$vars_stock[ $variation_id ] -= $quantity;
			}
			unset( $item );
		}

		//Refreshing group image
		if ( $refresh_grp_img ) {
			if ( ! empty( $this->_data['items'] ) && sizeof( $this->_data['items'] ) > 1 ) {
				$wc_prs = [];
				foreach ( $this->_data['items'] as &$item ) {
					$wc_prs[] = $this->get_wc_product( $item['var_id'] );
				}
				$this->_data['grp_img'] = ChB_Common::getGroupImg( $wc_prs, true );
			} else {
				$this->_data['grp_img'] = null;
			}
		}

		if ( $data_changed ) {
			$this->_data_changed = true;
			$this->refreshCartVersion();
		}

		return ! $data_changed;
	}

	public function clearCart( $test_mode = false ) {
		if ( $test_mode ) {
			$this->_data_changed = true;
			$this->refreshCartVersion();
		} else {
			$this->_data         = [ 'items' => [] ];
			$this->_data_changed = true;
		}
	}

	public function getCartProductsIds() {
		$res = [];
		if ( empty( $this->_data['items'] ) ) {
			return $res;
		}

		foreach ( $this->_data['items'] as &$item ) {
			$res[] = $item['var_id'];
		}

		return $res;
	}

	public function getCartDetails( ChB_Promo $promo ) {

		$this->actualizeCartStock( false );

		$products_details = [];
		$cart_total       = 0;
		$cart_subtotal    = 0;
		$cart_quantity    = 0;
		if ( ! empty( $this->_data['items'] ) ) {
			foreach ( $this->_data['items'] as $item_id => &$item ) {
				$wc_product      = $this->get_wc_product( $item['var_id'] );
				$quantity        = $item['q'];
				$product_details = [
					'item_id'   => $item_id,
					'quantity'  => $quantity,
					'pa_filter' => $item['pa_filter']
				];

				$price_details               = $promo->getPriceDetails( $wc_product, $quantity );
				$product_details['subtotal'] = $price_details['regular_sum'];
				$product_details['total']    = $price_details['sum'];
				$cart_subtotal               += $product_details['subtotal'];
				$cart_total                  += $product_details['total'];
				$cart_quantity               += $quantity;

				if ( ! empty( $item['et'] ) ) {
					$product_details['event_tags'] = $item['et'];
				}
				ChB_Order::fillInProductDetails( $product_details, $wc_product );
				$products_details[] = $product_details;
			}
		}
		unset( $item );

		$num_of_lines = sizeof( $this->_data['items'] );

		$cart_details = [
			'num_of_lines'     => $num_of_lines,
			'products_details' => $products_details
		];

//		if ( $cart_total != $cart_subtotal ) {//todo1 multiple items
//			$cart_details['promo_conditions'] = $promo->printPromo4UserConditions();
//		}

		$cart_details['total']    = $cart_total;
		$cart_details['subtotal'] = $cart_subtotal;
		$cart_details['quantity'] = $cart_quantity;

		ChB_Promo::applyPromo2Cart( $cart_details );

		//adding shipping
		$cart_details['shipping_details'] = new ChB_ShippingDetails( $this );

		$this->_totals['products_subtotal_sum'] = $cart_details['subtotal'];
		$this->_totals['products_total_sum']    = $cart_details['total'];

		$cart_details['subtotal'] += $cart_details['shipping_details']->getShippingTotal();
		$cart_details['total']    += $cart_details['shipping_details']->getShippingTotal();

		if ( $num_of_lines === 1 && ! empty( $wc_product ) ) {
			$att_id                  = $wc_product->get_image_id();
			$cart_details['grp_img'] = [
				'aspect' => 'square',
				'url'    => ChB_Common::getAttachmentMediumSizeUrl( $att_id )
			];
		} elseif ( $num_of_lines > 1 ) {
			$cart_details['grp_img'] = $this->_data['grp_img'];
		} else {
			$cart_details['grp_img'] = [ 'aspect' => 'horizontal', 'url' => '' ];
		}

		if ( ! empty( $this->out_messages ) ) {
			$cart_details['out_messages'] = $this->out_messages;
		}

		$cart_details['version'] = $this->getCartVersion();

		return $cart_details;
	}

	public function getCartVersion() {
		return isset( $this->_data['v'] ) ? $this->_data['v'] : null;
	}

	public function refreshCartVersion() {
		$this->_data['v'] = ChB_Common::my_rand_string( 6 );
	}

	public function isEmpty() {
		return empty( $this->_data['items'] );
	}

	public function updateInDB() {
		if ( $this->_data_changed ) {
			update_user_meta( $this->storage_wp_user_id, ChB_User::USER_ATTR_CART, json_encode( $this->_data ) );
			$this->_data_changed = false;
		}
	}

	public function get_wc_product( $product_id ) {
		if ( empty( $this->wc_products[ $product_id ] ) ) {
			if ( empty( $this->pf ) ) {
				$this->pf = new \WC_Product_Factory();
			}
			$this->wc_products[ $product_id ] = $this->pf->get_product( $product_id );
		}

		return $this->wc_products[ $product_id ];
	}

	public function convertItemsToWCCartItems() {
		$res = [];
		if ( ! empty( $this->_data['items'] ) ) {
			foreach ( $this->_data['items'] as $item_id => &$item ) {
				$res_item = [
					'quantity' => $item['q'],
				];

				$wc_product = $this->get_wc_product( $item['var_id'] );
				if ( $wc_product instanceof \WC_Product_Variation ) {
					$res_item['product_id']   = $wc_product->get_parent_id();
					$res_item['variation_id'] = $item['var_id'];
				} else {
					$res_item['product_id']   = $item['var_id'];
					$res_item['variation_id'] = 0;
				}

				if ( empty( $item['pa_filter'] ) ) {
					$res_item['variation'] = [];
				} else {
					$res_item['variation'] = ChB_Catalogue::convertPAFilterToWCVariationFormat( $item['pa_filter'] );
				}

				$res[] = $res_item;
			}
		}

		return $res;
	}

	public function removeItemsFromCartByOrder( $order_id ) {
		$wc_order = wc_get_order( $order_id );
		if ( ! ( $wc_order instanceof \WC_Order ) || empty( $this->_data['items'] ) ) {
			return;
		}

		ChB_Common::my_log( __FUNCTION__ . ': order=' . $wc_order->get_id() . ' bot_user_id=' . $this->storage_wp_user_id );

		$data_changed = false;
		foreach ( $wc_order->get_items( 'line_item' ) as $wc_order_item ) {
			if ( $wc_order_item instanceof \WC_Order_Item_Product ) {
				if ( $wc_product = $wc_order_item->get_product() ) {
					if ( $wc_product_id = $wc_product->get_id() ) {
						// wc_product_id is product or variation id here
						if ( $item_id = $this->getCartItemId( $wc_product_id, false ) ) {
							unset( $this->_data['items'][ $item_id ] );
							ChB_Common::my_log( 'deleting item_id=' . $item_id . ' wc_product_id=' . $wc_product_id );
							$data_changed = true;
						}
					}
				}
			}
		}

		if ( $data_changed ) {
			$this->_data_changed = true;
			$this->refreshCartVersion();
			$this->actualizeCartStock( true );
		}
	}
}