<?php


namespace ChatBot;

class ChB_ShippingDetails {

	/**
	 * Shipping details are fields that are
	 * 1. Prompted from user during checkout
	 * 2. Saved to user attrs permanently - for during checkout and later usage
	 * 3. Saved to order attrs once order is created
	 *
	 * Cart has user field. All values are stored this user field.
	 */

	private ?ChB_User $_cart_user;
	private ?ChB_Cart $_cart;
	private ?string $_shipping_option_id;
	private ?\WC_Order $_wc_order;

	private array $_addr_parts_info;
	private array $_addr_parts_groups;
	private ?array $_shipping_items;
	private $_shipping_total;

	public function __construct( ?ChB_Cart $cart, ?\WC_Order $wc_order = null ) {

		if ( $cart ) {
			$this->_cart      = $cart;
			$this->_cart_user = $cart->cart_user;
		} else {
			$this->_cart      = null;
			$this->_cart_user = null;
		}

		$this->_wc_order           = $wc_order;
		$this->_shipping_option_id = $cart ? $cart->getActiveShippingOptionId() : null;
	}

	public function getShippingOptionId() {
		return $this->_shipping_option_id;
	}

	public function setShippingOptionId( $shipping_option_id ) {
		$this->_shipping_option_id = $shipping_option_id;
	}

	public function getShippingTotal() {

		if ( ! isset( $this->_shipping_total ) ) {

			$this->_shipping_total = 0;
			foreach ( $this->getShippingItems() as $shipping_item ) {
				$this->_shipping_total += floatval( $shipping_item->price );
			}
		}

		return $this->_shipping_total;
	}

	public function initAddressParts( $force = false ) {
		if ( ! isset( $this->_addr_parts_info ) || $force ) {

			if ( $addr_parts_info = ChB_Settings()->getParam( 'additional_fields_settings' ) ) {
				$addr_parts_info = json_decode( $addr_parts_info, true );
			}

			// adding custom fields or changing order using hook
			$addr_parts_info = apply_filters(
				'wany_hook_get_address_parts_to_use',
				is_array( $addr_parts_info ) ? $addr_parts_info : [],
				$this->_shipping_option_id );

			// unpacking settings
			if ( is_array( $addr_parts_info ) ) {
				$temp = [];
				foreach ( $addr_parts_info as $key => $val ) {
					if ( isset( $val['id'] ) && empty( $val['disabled'] ) ) {
						$temp[ $val['id'] ] = $val;
					} elseif ( is_string( $val ) ) {
						$temp[ $val ] = $val;
					}
				}
				$addr_parts_info = $temp;
			} else {
				$addr_parts_info = [];
			}

			// unpacking/adding standard fields

			if ( ChB_Settings()->getParam( 'addr_use_name' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_FIRST_NAME ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_FIRST_NAME ] = [
					'id'    => ChB_Common::ADDR_PART_FIRST_NAME,
					'group' => ChB_Common::ADDR_GROUP_NAME
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_name' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_LAST_NAME ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_LAST_NAME ] = [
					'id'    => ChB_Common::ADDR_PART_LAST_NAME,
					'group' => ChB_Common::ADDR_GROUP_NAME
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_phone' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_PHONE ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_PHONE ] = [ 'id' => ChB_Common::ADDR_PART_PHONE ];
			}

			if ( ChB_Settings()->getParam( 'addr_use_email' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_EMAIL ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_EMAIL ] = [ 'id' => ChB_Common::ADDR_PART_EMAIL ];
			}

			if ( ChB_Settings()->getParam( 'addr_use_country' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_COUNTRY ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_COUNTRY ] = [
					'id'    => ChB_Common::ADDR_PART_COUNTRY,
					'group' => ChB_Common::ADDR_GROUP_ADDRESS
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_state' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_STATE ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_STATE ] = [
					'id'    => ChB_Common::ADDR_PART_STATE,
					'group' => ChB_Common::ADDR_GROUP_ADDRESS
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_city' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_CITY ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_CITY ] = [
					'id'    => ChB_Common::ADDR_PART_CITY,
					'group' => ChB_Common::ADDR_GROUP_ADDRESS
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_postcode' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_POSTCODE ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_POSTCODE ] = [
					'id'    => ChB_Common::ADDR_PART_POSTCODE,
					'group' => ChB_Common::ADDR_GROUP_ADDRESS
				];
			}

			if ( ChB_Settings()->getParam( 'addr_use_address_1' ) || isset ( $addr_parts_info[ ChB_Common::ADDR_PART_ADDRESS_LINE ] ) ) {
				$addr_parts_info[ ChB_Common::ADDR_PART_ADDRESS_LINE ] = [
					'id'    => ChB_Common::ADDR_PART_ADDRESS_LINE,
					'group' => ChB_Common::ADDR_GROUP_ADDRESS
				];
			}

			// sorting keys to make all keys in each group stay together
			$this->_addr_parts_groups = [];
			foreach ( $addr_parts_info as $key => $val ) {
				if ( isset( $val['group'] ) ) {
					$this->_addr_parts_groups[ $val['group'] ][] = $key;
				} else {
					$this->_addr_parts_groups[ $key ] = $key;
				}
			}

			// adding parts to result array in proper order
			$this->_addr_parts_info = [];
			foreach ( $this->_addr_parts_groups as $key => $val ) {
				if ( is_array( $val ) ) {
					foreach ( $val as $v ) {
						$this->_addr_parts_info[ $v ] = $addr_parts_info[ $v ];
					}
				} else {
					$this->_addr_parts_info[ $val ] = $addr_parts_info[ $val ];
				}
			}

			// filtering out parts with filters by other parts
			$unset = [];
			foreach ( $this->_addr_parts_info as $addr_part => $addr_part_info ) {
				foreach ( $addr_part_info as $key => $val ) {
					// if current field starts with wy_filter_, then it's a filter
					if ( is_string( $key ) && substr( $key, 0, 10 ) === 'wy_filter_' &&
					     ( $key = substr( $key, 10 ) ) && isset( $this->_addr_parts_info[ $key ] )
					) {
						if ( ! isset( $cache_val[ $key ] ) ) {
							$cache_val[ $key ] = $this->getAddrPartValue( $key );
						}
						if ( $val != $cache_val[ $key ] ) {
							$unset[] = $addr_part;
						}
					}
				}
			}
			foreach ( $unset as $addr_part ) {
				//removing from group
				if ( ! empty( $this->_addr_parts_info[ $addr_part ]['group'] ) ) {
					$group = $this->_addr_parts_info[ $addr_part ]['group'];
					$ind   = array_search( $addr_part, $this->_addr_parts_groups[ $group ] );
					if ( $ind !== false ) {
						unset( $this->_addr_parts_groups[ $group ][ $ind ] );
					}
				}
				//removing from parts
				unset( $this->_addr_parts_info[ $addr_part ] );
			}
		}
	}

	public function getAddressPartsToUse() {
		if ( ! isset( $this->_addr_parts_info ) ) {
			$this->initAddressParts();
		}

		return $this->_addr_parts_info;
	}

	public function addressPartIsUsed( $part ) {
		if ( ! isset( $this->_addr_parts_info ) ) {
			$this->initAddressParts();
		}

		return isset( $this->_addr_parts_info[ $part ] );
	}

	public function getNextAddressPart( $part ) {

		if ( ! isset( $this->_addr_parts_info ) ) {
			$this->initAddressParts();
		}

		$return = ! $part;
		foreach ( $this->_addr_parts_info as $cur_part => $val ) {
			if ( $return ) {
				return $cur_part;
			}

			if ( $cur_part === $part ) {
				$return = true;
			}
		}

		return null;
	}

	public function getAddressGroupFinalPart( $group ) {

		if ( ! isset( $this->_addr_parts_groups ) ) {
			$this->initAddressParts();
		}

		if ( isset( $this->_addr_parts_groups[ $group ] ) && is_array( $this->_addr_parts_groups[ $group ] ) ) {
			return $this->_addr_parts_groups[ $group ][ count( $this->_addr_parts_groups[ $group ] ) - 1 ];
		}

		return null;
	}

	public function addressPartIsInGroup( $addr_part, $group ) {

		if ( ! isset( $this->_addr_parts_groups ) ) {
			$this->initAddressParts();
		}

		if ( isset( $this->_addr_parts_groups[ $group ] ) && is_array( $this->_addr_parts_groups[ $group ] ) ) {
			return in_array( $addr_part, $this->_addr_parts_groups[ $group ] );
		}

		return false;
	}

	public function getAddressPartInfo( $part ) {
		if ( ! isset( $this->_addr_parts_info ) ) {
			$this->initAddressParts();
		}

		return ( isset( $this->_addr_parts_info[ $part ] ) ? $this->_addr_parts_info[ $part ] : null );
	}

	public function getAddrPartValue( $addr_part ) {

		if ( ! $this->_wc_order && ! $this->addressPartIsUsed( $addr_part ) ) {
			return null;
		}

		if ( $addr_part === ChB_Common::ADDR_PART_FIRST_NAME ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_first_name();
			} else {
				return $this->_cart_user->getUserShippingFirstName();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_LAST_NAME ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_last_name();
			} else {
				return $this->_cart_user->getUserShippingLastName();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_PHONE ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_phone();
			} else {
				return $this->_cart_user->getUserPhone();
			}

		} elseif ( $addr_part === ChB_Common::ADDR_PART_EMAIL ) {
			if ( $this->_wc_order ) {
				$email = $this->_wc_order->get_billing_email();

				return ChB_User::emailIsDummy( $email ) ? null : $email;
			} else {
				return $this->_cart_user->getUserBillingEmail();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_COUNTRY ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_country();
			} else {
				return $this->_cart_user->getUserShippingCountry();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_STATE ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_state();
			} else {
				return $this->_cart_user->getUserShippingState();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_CITY ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_city();
			} else {
				return $this->_cart_user->getUserShippingCity();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_POSTCODE ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_postcode();
			} else {
				return $this->_cart_user->getUserShippingPostcode();
			}
		} elseif ( $addr_part === ChB_Common::ADDR_PART_ADDRESS_LINE ) {
			if ( $this->_wc_order ) {
				return $this->_wc_order->get_shipping_address_1();
			} else {
				return $this->_cart_user->getUserShippingAddressLine();
			}
		} else {
			$addr_part_info = $this->getAddressPartInfo( $addr_part );

			if ( $this->_wc_order ) {
				if ( ! empty( $addr_part_info['order_attr'] ) ) {
					if ( $val = $this->_wc_order->get_meta( $addr_part_info['order_attr'] ) ) {
						return $val;
					}
				}
				if ( ! empty( $addr_part_info['user_attr'] ) && $wp_user_id = $this->_wc_order->get_customer_id() ) {
					if ( $user = ChB_User::initUserByWPUserID( $wp_user_id ) ) {
						return $user->getUserAttr( $addr_part_info['user_attr'] );
					}
				}
			}

			if ( ! empty( $addr_part_info['user_attr'] ) ) {
				return $this->_cart_user->getUserAttr( $addr_part_info['user_attr'] );
			}
		}

		return null;
	}

	public function validateAndSaveAddrPartValue( $addr_part, $value ) {

		$res = true;

		if ( $addr_part === ChB_Common::ADDR_PART_FIRST_NAME ) {
			$res = $this->_cart_user->saveUserShippingFirstName( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_LAST_NAME ) {
			$res = $this->_cart_user->saveUserShippingLastName( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_PHONE ) {
			$res = $this->_cart_user->savePhone( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_EMAIL ) {
			$res = $this->_cart_user->saveUserBillingEmail( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_COUNTRY ) {
			$res = $this->_cart_user->saveUserShippingCountry( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_STATE ) {
			$res = $this->_cart_user->saveUserShippingState( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_CITY ) {
			$res = $this->_cart_user->saveUserShippingCity( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_POSTCODE ) {
			$res = $this->_cart_user->saveUserShippingPostcode( $value );
		} elseif ( $addr_part === ChB_Common::ADDR_PART_ADDRESS_LINE ) {
			$res = $this->_cart_user->saveUserShippingAddressLine( $value );
		} else {
			$addr_part_info = $this->getAddressPartInfo( $addr_part );
			if ( ! empty( $addr_part_info['user_attr'] ) ) {
				if ( ! empty( $addr_part_info['validation_hook'] ) ) {
					$value = apply_filters( $addr_part_info['validation_hook'], $value );
				}
				$res = $value && $this->_cart_user->setUserAttr( $addr_part_info['user_attr'], $value );
			}
		}

		$this->initAddressParts( true );

		return $res;
	}

	public function printContactInfo() {

		if ( ! isset( $this->_addr_parts_groups ) ) {
			$this->initAddressParts();
		}

		$res = [];
		foreach ( $this->_addr_parts_groups as $group_key => $group_val ) {

			if ( $group_key === ChB_Common::ADDR_GROUP_NAME ) {
				if ( $val = $this->getDisplayName() ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0080 ) . ' ' . $val;
				}
			} elseif ( $group_key === ChB_Common::ADDR_GROUP_ADDRESS ) {
				if ( $val = $this->getConcatAddressLine() ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0010 ) . ' ' . $val;
				}
			} elseif ( $group_key === ChB_Common::ADDR_PART_PHONE ) {
				if ( $val = $this->getPhone() ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0004 ) . ' ' . ChB_Lang::maybeForceLTR( $val );
				}
			} elseif ( $group_key === ChB_Common::ADDR_PART_EMAIL ) {
				if ( $val = $this->getEmail() ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0165 ) . ' ' . $val;
				}
			} else {
				$addr_part_info = $this->getAddressPartInfo( $group_key );
				if ( ! empty( $addr_part_info['caption'] ) && $val = $this->printAddrPartValue( $addr_part_info ) ) {
					$res[] = ChB_Lang::translate( ChB_Lang::convertAssoc( $addr_part_info['caption'] ) . $val );
				}
			}
		}

		return ChB_Lang::maybeForceRTL( implode( "\n", $res ) );
	}

	public function printAddrPartValue( $addr_part_info ) {
		$val = $this->getAddrPartValue( $addr_part_info['id'] );
		if ( ! empty( $addr_part_info['options'] ) ) {
			foreach ( $addr_part_info['options'] as $option ) {
				if ( $option['value'] == $val && $option['caption'] ) {
					$val = ChB_Lang::translate( ChB_Lang::convertAssoc( $option['caption'] ) );
				}
			}
		}

		return $val;
	}

	public function getFirstName() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_FIRST_NAME );
	}

	public function getLastName() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_LAST_NAME );
	}

	public function getPhone() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_PHONE );
	}

	public function getEmail() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_EMAIL );
	}

	public function getCountry() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_COUNTRY );
	}

	public function getState() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_STATE );
	}

	public function getCity() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_CITY );
	}

	public function getPostcode() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_POSTCODE );
	}

	public function getAddressLine() {
		return $this->getAddrPartValue( ChB_Common::ADDR_PART_ADDRESS_LINE );
	}

	public function getDisplayName() {
		$res = [];
		if ( ! empty( $this->_addr_parts_groups[ ChB_Common::ADDR_GROUP_NAME ] ) ) {
			foreach ( $this->_addr_parts_groups[ ChB_Common::ADDR_GROUP_NAME ] as $addr_part ) {
				if ( $val = $this->getAddrPartValue( $addr_part ) ) {
					$res[] = $val;
				}
			}
		}

		return implode( ' ', $res );
	}

	public function getConcatAddressLine() {

		$res = [];
		if ( ! empty( $this->_addr_parts_groups[ ChB_Common::ADDR_GROUP_ADDRESS ] ) ) {
			foreach ( $this->_addr_parts_groups[ ChB_Common::ADDR_GROUP_ADDRESS ] as $addr_part ) {
				if ( $val = $this->getAddrPartValue( $addr_part ) ) {
					$res[] = $val;
				}
			}
		}

		return implode( ', ', $res );
	}

	public function checkUserHasAllAddressParts() {

		foreach ( $this->getAddressPartsToUse() as $addr_part => $addr_part_pars ) {
			if ( ! empty( $addr_part_pars['force_input'] ) || ! ( $val = $this->getAddrPartValue( $addr_part ) ) ) {
				return false;
			}
		}

		return true;
	}

	public function addressPartIsAdditional( $part ) {
		return $part !== ChB_Common::ADDR_PART_FIRST_NAME &&
		       $part !== ChB_Common::ADDR_PART_LAST_NAME &&
		       $part !== ChB_Common::ADDR_PART_PHONE &&
		       $part !== ChB_Common::ADDR_PART_EMAIL &&
		       $part !== ChB_Common::ADDR_PART_COUNTRY &&
		       $part !== ChB_Common::ADDR_PART_STATE &&
		       $part !== ChB_Common::ADDR_PART_CITY &&
		       $part !== ChB_Common::ADDR_PART_POSTCODE &&
		       $part !== ChB_Common::ADDR_PART_ADDRESS_LINE;
	}

	/**
	 * @return ChB_ShippingItem[]
	 */
	public function getShippingItems() {

		if ( isset( $this->_shipping_items ) ) {
			return $this->_shipping_items;
		}

		$this->_shipping_items = [];

		if ( $this->_wc_order ) {
			foreach ( $this->_wc_order->get_items( [ 'shipping' ] ) as $item_id => $item ) {
				if ( $item instanceof \WC_Order_Item_Shipping ) {
					$this->_shipping_items[] = new ChB_ShippingItem( $item->get_name(), $item->get_total(), true );
				}
			}
		} else {
			$this->_shipping_items[] = ChB_ShippingOption::calcShipping( $this->_cart );
		}

		return $this->_shipping_items;
	}

	public function printShippingText() {

		$res = [];
		foreach ( $this->getShippingItems() as $shipping_item ) {
			if ( $text = $shipping_item->printShipping() ) {
				$res[] = $text;
			}
		}

		return implode( "\n", $res );
	}

	public function addShippingToOrder( \WC_Order $new_order ) {

		$address = [
			'first_name' => $this->getFirstName(),
			'last_name'  => $this->getLastName(),
			'phone'      => $this->getPhone(),
			'country'    => $this->getCountry(),
			'state'      => $this->getState(),
			'city'       => $this->getCity(),
			'postcode'   => $this->getPostcode(),
			'address_1'  => $this->getAddressLine()
		];

		$new_order->set_address( $address, 'shipping' );

		$address['email'] = $this->getEmail();
		$new_order->set_address( $address, 'billing' );

		foreach ( $this->getShippingItems() as $shipping_item ) {
			$wc_shipping_item = new \WC_Order_Item_Shipping();
			if ( $shipping_item->wc_shipping_instance_id ) {
				$wc_shipping_item->set_instance_id( $shipping_item->wc_shipping_instance_id );
			}
			if ( $shipping_item->wc_shipping_method_id ) {
				$wc_shipping_item->set_method_id( $shipping_item->wc_shipping_method_id );
			}
			$wc_shipping_item->set_method_title( $shipping_item->title );
			$wc_shipping_item->set_total( $shipping_item->price );
			$new_order->add_item( $wc_shipping_item );
		}

		foreach ( $this->getAddressPartsToUse() as $addr_part => $addr_part_info ) {
			if ( ! empty( $addr_part_info['order_attr'] ) && $val = $this->getAddrPartValue( $addr_part ) ) {
				$new_order->update_meta_data( $addr_part_info['order_attr'], sanitize_text_field( $val ) );
			}
		}

		$new_order->set_shipping_total( $this->getShippingTotal() );
	}
}