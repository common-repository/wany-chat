<?php


namespace ChatBot;


class ChB_WooCart {

	/**
	 * @param $cart1 \WC_Cart|array
	 * @param $cart2 array
	 */
	public static function mergeWCCarts( $cart1, $cart2 ) {
		if ( ! $cart2 || ! is_array( $cart2 ) ) {
			return $cart1;
		}

		if ( $cart1 instanceof \WC_Cart ) {
			$cart1_items = $cart1->get_cart_for_session();
		} else {
			$cart1_items = &$cart1;
		}

		if ( ChB_Debug::isDebug() ) {
			ChB_Common::my_log( 'CART1 count=' . ChB_Common::printArrayCount( $cart1_items ) );
			ChB_Common::my_log( 'CART2 count=' . ChB_Common::printArrayCount( $cart2 ) );
		}

		foreach ( $cart2 as $item_key2 => $item2 ) {
			$add = true;
			if ( $cart1_items ) {
				foreach ( $cart1_items as $item_key1 => $item1 ) {
					if ( $item1['product_id'] != $item2['product_id'] ) {
						continue;
					}
					if ( isset( $item1['variation_id'] ) != isset( $item2['variation_id'] ) ) {
						continue;
					}
					if ( isset( $item1['variation_id'] ) && $item1['variation_id'] != $item2['variation_id'] ) {
						continue;
					}
					if ( isset( $item1['variation'] ) != isset( $item2['variation'] ) ) {
						continue;
					}
					if ( isset( $item1['variation'] ) && ! ChB_Common::arraysHaveTheSameKeysAndValues( $item1['variation'], $item2['variation'] ) ) {
						continue;
					}

					if ( $item1['quantity'] < $item2['quantity'] ) {
						if ( $cart1 instanceof \WC_Cart ) {
							$cart1->add_to_cart(
								$item1['product_id'],
								$item2['quantity'] - $item1['quantity'],
								isset( $item1['variation_id'] ) ? $item1['variation_id'] : 0,
								isset( $item1['variation'] ) ? $item1['variation'] : []
							);
						} else {
							$cart1[ $item_key1 ]['quantity'] = $item2['quantity'];
						}
					}
					$add = false;
					break;
				}

			}

			if ( $add ) {
				if ( $cart1 instanceof \WC_Cart ) {
					$cart1->add_to_cart(
						$item2['product_id'],
						$item2['quantity'],
						isset( $item2['variation_id'] ) ? $item2['variation_id'] : 0,
						isset( $item2['variation'] ) ? $item2['variation'] : []
					);
				} else {
					$cart1[ $item_key2 ] = $item2;
				}
			}
		}

		if ( ChB_Debug::isDebug() ) {
			if ( $cart1 instanceof \WC_Cart ) {
				ChB_Common::my_log( 'WC CART1 AFTER count=' . ChB_Common::printArrayCount( $cart1->get_cart_for_session() ) );
			} else {
				ChB_Common::my_log( 'ARR CART1 AFTER count=' . ChB_Common::printArrayCount( $cart1 ) );
			}
		}

		return $cart1;
	}

	/**
	 * @param $merge_cart array - array of items in WC Cart format
	 */
	public static function mergeItemsIntoWCCart( $merge_cart ) {

		if ( ! WC()->cart ) {
			return;
		}
		ChB_Common::my_log( 'mergeItemsIntoWCCart count=' . ChB_Common::printArrayCount( $merge_cart ) );

		// Pausing abandoned cart recalculation during merge process
		ChB_WooAbandonedCart::$pause_ac_refresh = 1;

		self::mergeWCCarts( WC()->cart, $merge_cart );

		WC()->session->set_customer_session_cookie( true );

		// Launching abandoned cart recalculation
		ChB_WooAbandonedCart::$pause_ac_refresh = 0;
//		do_action( 'wany_hook_update_wy_session_and_ac' );
	}

	/**
	 * snippet: adding product to WC session's cart
	 *
	 * wc_load_cart();
	 * wc_set_customer_auth_cookie( $ChB->user->wp_user_id );
	 * WC()->cart->add_to_cart( $product_id, 1 );
	 */
}