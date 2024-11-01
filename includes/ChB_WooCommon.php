<?php


namespace ChatBot;


class ChB_WooCommon {

	public static function init() {
		//Notifications on orders
		add_filter( 'woocommerce_new_order', [ '\ChatBot\ChB_WooCommon', 'newOrder' ], 10, 1 );
		add_filter( 'woocommerce_order_status_changed', [ '\ChatBot\ChB_WooCommon', 'orderStatusChanged' ], 10, 4 );
		add_filter( 'wc_price', [ '\ChatBot\ChB_Common', 'reformatWCPriceHook' ], 101, 5 );

		add_action( 'wp_head', [ '\ChatBot\ChB_WooCommon', 'addManychatJS' ], 5 );
		if ( ! is_admin() ) {
			require_once dirname( __FILE__ ) . '/ChB_WYSession.php';
			ChB_WYSession::init();
		}

		//Product query modifications
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [
			'\ChatBot\ChB_WooCommon',
			'handleCustomQueryVar'
		], 10, 2 );

		if ( ChB_Settings()->getParam( 'use_pc_links_in_fb_catalog' ) ) {
			add_filter( 'facebook_for_woocommerce_integration_prepare_product', [
				'ChatBot\ChB_WooCommon',
				'filterLinksForFBCatalog'
			], 10, 2 );
		}
	}

	/**
	 * Set a cookie - wrapper for setcookie using WP constants.
	 *
	 * @param string $name Name of the cookie being set.
	 * @param string $value Value of the cookie.
	 * @param integer $expire Expiry of the cookie.
	 * @param bool $secure Whether the cookie should be served only over https.
	 * @param bool $httponly Whether the cookie is only accessible over HTTP, not scripting languages like JavaScript. @since 3.6.0.
	 */
	public static function setCookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
		if ( ! headers_sent() ) {
			$_COOKIE[ $name ] = $value;
			setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, $httponly );
		}
	}

	public static function unsetCookie( $name ) {
		if ( ! headers_sent() ) {
			if ( isset( $_COOKIE[ $name ] ) ) {
				unset( $_COOKIE[ $name ] );
				setcookie( $name, '', 1, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
			}
		}
	}

	public static function newOrder( $order_id ) {
		chb_load();
		$order = wc_get_order( $order_id );
		if ( $order && $order->get_status() === ChB_Order::getInitStatus() ) {
			ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_SEND_NOTIFICATIONS_ON_ORDER, [
				'order_id'   => $order_id,
				'new_status' => $order->get_status()
			], 0, true );
		}

		// removing ordered items from BOT cart
		// for web sessions connected to bot users
		$wy_session_id = ChB_WYSession::initWYSession( true );
		if ( $bot_wp_user_id = ChB_WYSession::session( $wy_session_id )->getBotUserId() ) {
			ChB_Common::my_log( 'newOrder web order=' . $order_id . ' wy_session=' . $wy_session_id . ' connected to bot_wp_user_id=' . $bot_wp_user_id );
			ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_CLEAN_UP_CART, [
				'order_id'       => $order_id,
				'bot_wp_user_id' => $bot_wp_user_id
			], 0, true );
		}
	}

	public static function woocommerceCheckoutFieldFromBotUser( ?ChB_User $bot_user, $value, $field ) {

		if ( $field === 'billing_first_name' || $field === 'shipping_first_name' ) {
			if ( $val = $bot_user->getUserShippingFirstName() ) {
				return $val;
			}
		} elseif ( $field === 'billing_last_name' || $field === 'shipping_last_name' ) {
			if ( $val = $bot_user->getUserShippingLastName() ) {
				return $val;
			}
		} elseif ( $field === 'billing_phone' ) {
			if ( $val = $bot_user->getUserPhone() ) {
				return $val;
			}
		} elseif ( $field === 'billing_email' ) {
			if ( $val = $bot_user->getUserBillingEmail() ) {
				return $val;
			}
		} elseif ( $field === 'billing_country' || $field === 'shipping_country' ) {
			if ( $val = $bot_user->getUserShippingCountry() ) {
				return $val;
			}
		} elseif ( $field === 'billing_state' || $field === 'shipping_state' ) {
			if ( $val = $bot_user->getUserShippingState() ) {
				return $val;
			}
		} elseif ( $field === 'billing_city' || $field === 'shipping_city' ) {
			if ( $val = $bot_user->getUserShippingCity() ) {
				return $val;
			}
		} elseif ( $field === 'billing_postcode' || $field === 'shipping_postcode' ) {
			if ( $val = $bot_user->getUserShippingPostcode() ) {
				return $val;
			}
		} elseif ( $field === 'billing_address_1' || $field === 'shipping_address_1' ) {
			if ( $val = $bot_user->getUserShippingAddressLine() ) {
				return $val;
			}
		}

		return $value;
	}

	public static function addManychatJS() {
		if ( ChB_Settings()->auth->getMCPageID() ) {
			?>
            <!-- ManyChat -->
            <script src="//widget.manychat.com/<?php echo ChB_Settings()->auth->getMCPageID(); ?>.js"
                    defer="defer"></script>
            <script src="https://mccdn.me/assets/js/widget.js" defer="defer"></script>

            <script>
                function getMCSiteChatWidget(widgetName) {
                    mcw = MC.getWidgetList();
                    for (var i = 0; i < mcw.length; i++)
                        if (mcw[i].name === widgetName)
                            return MC.getWidget(mcw[i].widgetId);
                    return null;
                }
            </script>
			<?php
			if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) ) {
				ChB_WooAbandonedCart::printAbandonedCartScript();
			}
		}
	}

	//not used ATM
	public static function setMessengerOverlayPayload() {
		if ( ! ( $widget_name = ChB_Settings()->getParam( 'abandoned_cart_overlay_widget_name' ) ) ) {
			return;
		}
		?>
        <script>
            window.mcAsyncInit = function () {
                let mcw = MC.getWidget(<?php echo $widget_id?>);
                let single_add_to_cart_button = document.getElementsByClassName('single_add_to_cart_button');
                let set_mc_user_in_woo_session_url = '<?php echo '/?wy-ajax=set_fb_user&_wy_nonce=' . $wy_nonce?>';
                if (mcw && single_add_to_cart_button && single_add_to_cart_button.length) {
                    single_add_to_cart_button[0].onclick = function () {
                        MC.getWidget(<?php echo $widget_id?>).submit();
                    }
                    mcw.on('submitted', function () {
                        if (mcw.checked) {
                            console.log('SBMDTD! CHECKED ref=' + mcw.ref + ' userRef=' + mcw.userRef.toString());

                            jQuery.ajax({
                                type: 'POST',
                                url: set_mc_user_in_woo_session_url,
                                data: {
                                    user_ref: mcw.userRef
                                },
                                success: function (response) {
                                    console.log(response)
                                    // if ( ! response || ! response.fragments ) {
                                    //     window.location = $thisbutton.attr( 'href' );
                                    //     return;
                                    // }
                                    //
                                    // $( document.body ).trigger( 'removed_from_cart', [ response.fragments, response.cart_hash, $thisbutton ] );
                                },
                                error: function () {
                                    // window.location = $thisbutton.attr( 'href' );
                                    // return;
                                },
                                dataType: 'json'
                            });


                        }
                    });
                }
            };
        </script>
        <div align="left" style="max-width: 100px">
            <div class="mcwidget-checkbox" data-widget-id="<?php echo $widget_id; ?>"
        </div></div>
		<?php

	}

	//not used ATM
	public static function addMessengerCheckbox() {
		if ( ! ( $widget_id = ChB_Settings()->getParam( 'abandoned_cart_widget_id' ) ) ) {
			return;
		}
		$wy_ajax_action = 'set_fb_user';
		$wy_nonce       = wp_create_nonce( $wy_ajax_action );
		?>
        <script>
            window.mcAsyncInit = function () {
                let mcw = MC.getWidget(<?php echo $widget_id?>);
                let single_add_to_cart_button = document.getElementsByClassName('single_add_to_cart_button');
                let set_mc_user_in_woo_session_url = '<?php echo '/?wy-ajax=set_fb_user&_wy_nonce=' . $wy_nonce?>';
                if (mcw && single_add_to_cart_button && single_add_to_cart_button.length) {
                    single_add_to_cart_button[0].onclick = function () {
                        MC.getWidget(<?php echo $widget_id?>).submit();
                    }
                    mcw.on('submitted', function () {
                        if (mcw.checked) {
                            console.log('SBMDTD! CHECKED ref=' + mcw.ref + ' userRef=' + mcw.userRef.toString());

                            jQuery.ajax({
                                type: 'POST',
                                url: set_mc_user_in_woo_session_url,
                                data: {
                                    user_ref: mcw.userRef
                                },
                                success: function (response) {
                                    console.log(response)
                                    // if ( ! response || ! response.fragments ) {
                                    //     window.location = $thisbutton.attr( 'href' );
                                    //     return;
                                    // }
                                    //
                                    // $( document.body ).trigger( 'removed_from_cart', [ response.fragments, response.cart_hash, $thisbutton ] );
                                },
                                error: function () {
                                    // window.location = $thisbutton.attr( 'href' );
                                    // return;
                                },
                                dataType: 'json'
                            });


                        }
                    });
                }
            };
        </script>
        <div align="left" style="max-width: 100px">
            <div class="mcwidget-checkbox" data-widget-id="<?php echo $widget_id; ?>"
        </div></div>
		<?php
	}


	public static function orderStatusChanged( $order_id, $old_status, $new_status, $order ) {
		chb_load();
		if ( $order instanceof \WC_Order ) {
			ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_SEND_NOTIFICATIONS_ON_ORDER, [
				'order_id'   => $order->get_id(),
				'new_status' => $new_status
			], 0, true );
		}
	}

	/**
	 * Handle a custom 'customvar' query var to get products with the 'customvar' meta.
	 *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Product_Query.
	 *
	 * @return array modified $query
	 */
	public static function handleCustomQueryVar( $query, $query_vars ) {

		if ( ! empty( $query_vars['price_is_set'] ) ) {
			$query['meta_query'][] = [
				'key'     => '_price',
				'value'   => '',
				'compare' => '!=',
			];
		}
		if ( ! empty( $query_vars['pmin'] ) ) {
			$query['meta_query'][] = [
				'key'     => '_price',
				'value'   => $query_vars['pmin'],
				'compare' => '>=',
				'type'    => 'NUMERIC'
			];
		}
		if ( ! empty( $query_vars['pmax'] ) ) {
			$query['meta_query'][] = [
				'key'     => '_price',
				'value'   => $query_vars['pmax'],
				'compare' => '<=',
				'type'    => 'NUMERIC'
			];
		}

		if ( ! empty( $query_vars['onsale'] ) ) {
			$query['meta_query'][] = [
				'key'     => '_sale_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC'
			];
		}

		if ( ! empty( $query_vars[ ChB_Constants::PROD_ATTR_HAS_TRY_ON ] ) ) {
			$query['meta_query'][] = [
				'key'     => ChB_Constants::PROD_ATTR_HAS_TRY_ON,
				'value'   => 1,
				'compare' => '=',
				'type'    => 'NUMERIC'
			];
		}

		if ( ! empty( $query_vars[ ChB_Constants::PROD_ATTR_HAS_TRY_ON . '_order' ] ) ) {
			$query['orderby']  = 'meta_value_num';
			$query['meta_key'] = ChB_Constants::PROD_ATTR_HAS_TRY_ON;
			$query['order']    = 'DESC';
		}

		if ( ! empty( $query_vars['search'] ) ) {
			$query['s'] = $query_vars['search'];
		}

		return $query;
	}

	public static function filterLinksForFBCatalog( $product_data, $id ) {
		chb_load();

		// simple product
		if ( ! empty( $product_data['url'] ) ) {
			$product_data['url'] = ChB_Settings()->ref_url_product_card . $id;
		}

		// variable product
		if ( ! empty( $product_data['link'] ) ) {
			$product_data['link'] = ChB_Settings()->ref_url_product_card . $id;
		}

		return $product_data;
	}


}