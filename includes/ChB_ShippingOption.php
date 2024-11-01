<?php


namespace ChatBot;


class ChB_ShippingOption {

	public string $id;
	public ?string $title;
	private ?array $_wc_shipping_methods;

	/** @var callable */
	private $tail_messages_callback;

	/** @var ChB_ShippingOption[] */
	private static array $_registered_shipping_options;
	private const _WY_SHIPPING_OPTION_ID = '_wany_';

	private static function _registerShippingOptions() {

		self::$_registered_shipping_options[ self::_WY_SHIPPING_OPTION_ID ] = new ChB_ShippingOption( self::_WY_SHIPPING_OPTION_ID, null, null, null );

		if ( ChB_Settings()->getParam( 'shipping_cost_code' ) === ChB_Common::SHIPPING_WOO ) {
			$zones    = \WC_Shipping_Zones::get_zones();
			$zone0    = new \WC_Shipping_Zone( 0 );
			$zones[0] = [
				'formatted_zone_location' => $zone0->get_formatted_location(),
				'shipping_methods'        => $zone0->get_shipping_methods()
			];

			foreach ( $zones as $zone ) {
				if ( ! empty( $zone['shipping_methods'] ) && is_array( $zone['shipping_methods'] ) && isset( $zone['formatted_zone_location'] ) ) {
					foreach ( $zone['shipping_methods'] as $shipping_method ) {
						if ( $shipping_method instanceof \WC_Shipping_Method && $shipping_method->enabled === 'yes' ) {
							$id                                        = $shipping_method->id . ':' . $shipping_method->instance_id;
							self::$_registered_shipping_options[ $id ] =
								new ChB_ShippingOption(
									$id,
									$zone['formatted_zone_location'] . ' - ' . $shipping_method->title,
									[ [ 'id' => $shipping_method->id, 'iid' => $shipping_method->instance_id ] ],
									null
								);
						}
					}
				}
			};
		}

		self::$_registered_shipping_options = apply_filters( 'wany_hook_register_shipping_options', self::$_registered_shipping_options );
	}

	/**
	 * @param string|null $id
	 *
	 * @return ChB_ShippingOption|false
	 */
	public static function getShippingOptionById( ?string $id ) {

		if ( ! isset( self::$_registered_shipping_options ) ) {
			self::_registerShippingOptions();
		}

		return ( $id && isset( self::$_registered_shipping_options[ $id ] ) && self::$_registered_shipping_options[ $id ] instanceof ChB_ShippingOption ?
			self::$_registered_shipping_options[ $id ] : false
		);
	}

	/**
	 * @param ChB_Cart $cart
	 *
	 * @return ChB_ShippingItem[]
	 */
	public static function getAvailableShipping4Cart( ChB_Cart $cart ) {

		if ( ! isset( self::$_registered_shipping_options ) ) {
			self::_registerShippingOptions();
		}

		$res = [];
		foreach ( self::$_registered_shipping_options as $shipping_option_id => $shipping_option ) {

			// hiding wy shipping option from user
			if ( $shipping_option_id === self::_WY_SHIPPING_OPTION_ID ) {
				continue;
			}

			// checking if current shipping option is available for the cart
			if ( $shipping_item = $shipping_option->getShippingItemByWCShippingMethods( $cart ) ) {
				$res[ $shipping_option_id ] = $shipping_item;
			}

		}

		return $res;
	}

	public function __construct( $id, $title, $wc_shipping_methods, $tail_messages_callback ) {
		$this->id                     = $id;
		$this->title                  = $title;
		$this->_wc_shipping_methods   = $wc_shipping_methods;
		$this->tail_messages_callback = $tail_messages_callback;
	}


	/**
	 * @param ChB_Cart $cart
	 *
	 * @return ChB_ShippingItem
	 */
	public static function calcShipping( ChB_Cart $cart ) {
		$shipping_option_id = $cart->getActiveShippingOptionId();

		if ( $shipping_option_id === false ) {
			//shipping option not selected, let's see if wany shipping option is registered
			$shipping_option_id = self::_WY_SHIPPING_OPTION_ID;
		}

		$shipping_option = self::getShippingOptionById( $shipping_option_id );
		if ( ! $shipping_option ) {
			return ChB_ShippingItem::getUndefinedShippingItem();
		}

		return $shipping_option->_calcShipping( $cart );
	}

	/**
	 * @param ChB_Cart $cart
	 *
	 * @return ChB_ShippingItem
	 */
	private function _calcShipping( ChB_Cart $cart ) {

		if ( $this->id === self::_WY_SHIPPING_OPTION_ID ) {
			return ChB_ShippingItem::getWYShippingItem();

		} elseif ( $this->_wc_shipping_methods ) {

			if ( $shipping_item = $this->getShippingItemByWCShippingMethods( $cart ) ) {
				return $shipping_item;
			}
		}

		return ChB_ShippingItem::getUndefinedShippingItem();
	}

	/**
	 * @param ChB_Cart $cart
	 *
	 * @return ChB_ShippingItem|null
	 */
	public function getShippingItemByWCShippingMethods( ChB_Cart $cart ) {

		foreach ( $this->_wc_shipping_methods as $wc_shipping_method_data ) {

			$wc_shipping_method_id   = $wc_shipping_method_data['id'];
			$wc_shipping_instance_id = $wc_shipping_method_data['iid'];

			if ( $wc_shipping_method_id === 'flat_rate' ) {
				$class_name = 'WC_Shipping_Flat_Rate';
			} elseif ( $wc_shipping_method_id === 'free_shipping' ) {
				$class_name = 'WC_Shipping_Free_Shipping';
			} elseif ( $wc_shipping_method_id === 'local_pickup' ) {
				$class_name = 'WC_Shipping_Local_Pickup';
			} else {
				$class_name = null;
			}

			if ( ! $class_name ) {
				return null;
			}

			try {
				$wc_shipping_method = new $class_name( $wc_shipping_instance_id );
			} catch ( \Exception $e ) {
				$wc_shipping_method = null;
			}

			$price = null;
			if ( $wc_shipping_method instanceof \WC_Shipping_Flat_Rate ) {

				$price = $wc_shipping_method->get_option( 'cost' );

			} elseif ( $wc_shipping_method instanceof \WC_Shipping_Free_Shipping ) {
				//requires:	'min_amount', 'coupon', 'either', 'both'
				if ( ( $wc_shipping_method->requires === 'min_amount' || $wc_shipping_method->requires === 'either' )
				     && $cart->getProductTotals() < $wc_shipping_method->min_amount
				) {
					$wc_shipping_method = null;
				}

			} elseif ( $wc_shipping_method instanceof \WC_Shipping_Local_Pickup ) {
				$price = $wc_shipping_method->get_option( 'cost' );
			} else {
				$wc_shipping_method = null;
			}

			if ( $wc_shipping_method ) {
				return new ChB_ShippingItem(
					$this->title ? $this->title : $wc_shipping_method->title,
					$price,
					false,
					null,
					$wc_shipping_method_id,
					$wc_shipping_instance_id,
					$this->tail_messages_callback
				);
			}
		}
	}
}