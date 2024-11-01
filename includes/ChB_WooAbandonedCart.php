<?php


namespace ChatBot;


class ChB_WooAbandonedCart {

	public const REMINDER_TIME_STEP_1H = 3600; //1h
	public const REMINDER_TIME_MAX_24H = 24 * 3600; //24h

	public const USER_ATTR_AC_INFO = 'wy_ac_info';
	public const USER_ATTR_AC_LAST_CREATION_TIME = 'wy_ac_cts';
	public const USER_ATTR_AC_NEXT_REMINDER_TIME = 'wy_ac_nrts';

	public const GET_PAR_LOAD_CART_FLAG = 'wy_ac_is_rcv';

	public const TRIGGER_NAME = 'ac_reminder';

	public static int $pause_ac_refresh;

	public static function init() {

		add_action( 'wp_loaded', [ 'ChatBot\ChB_WooAbandonedCart', 'initAbandonedCartSessionHooks' ], 10 );

		add_action( 'wp_body_open', [ 'ChatBot\ChB_WooAbandonedCart', 'printDiv4Widget' ] );
	}

	public static function printDiv4Widget() {
		try {
			if ( WC()->cart && WC()->cart->get_cart_contents_count() &&
			     ! ChB_WYSession::currentWySessionIsConnectedToBot() ) {
				echo '<div style="display:none;" name="wy_ac_div"></div>';
			}
		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}
	}

	/**
	 * Initializes MC script with WY session id
	 * Function is called from web page ('wp_head' hook), headers were already sent
	 */
	public static function printAbandonedCartScript() {

		if ( $widget_name = ChB_Settings()->getParam( 'abandoned_cart_overlay_widget_name' ) ) {
			if ( ! ( $payload = ChB_WYSession::getWYSessionCookie() ) ) {
				$payload = ChB_Common::EMPTY_TEXT;
			}
			?>
            <script>
                window.mcAsyncInit = function () {
                    let mcw = getMCSiteChatWidget('<?php echo $widget_name?>');
                    if (mcw) {
                        let payload = '<?php echo $payload;?>';
                        console.log('mcw payload=' + payload + ' for "<?php echo $widget_name?>"');
                        mcw.setPayload(payload);
                    } else {
                        console.log('cannot find widget "<?php echo $widget_name?>"');
                    }
                }
            </script>
			<?php
		}
	}

	/**
	 * Setting up hooks to track changes made to cart
	 * Function is called from web page ('wp_loaded' hook)
	 */
	public static function initAbandonedCartSessionHooks() {

		try {
			if ( ! WC()->session ) {
				return;
			}

			add_action( 'wany_hook_update_wy_session_and_ac', function () {
				self::updateWYSessionAndAbandonedCart( 'wany_hook_update_wy_session_and_ac' );;
			} );

			add_action( 'woocommerce_add_to_cart', function () {
				self::updateWYSessionAndAbandonedCart( 'woocommerce_add_to_cart' );;
			} );

			add_action( 'woocommerce_cart_item_removed', function () {
				self::updateWYSessionAndAbandonedCart( 'woocommerce_cart_item_removed' );;
			} );

			add_action( 'woocommerce_cart_item_restored', function () {
				self::updateWYSessionAndAbandonedCart( 'woocommerce_cart_item_restored' );;
			} );

			add_action( 'woocommerce_cart_item_set_quantity', function () {
				self::updateWYSessionAndAbandonedCart( 'woocommerce_cart_item_set_quantity' );;
			} );

			add_action( 'woocommerce_calculate_totals', function () {
				self::updateWYSessionAndAbandonedCart( 'woocommerce_calculate_totals' );;
			} );

			add_action( 'woocommerce_checkout_update_order_meta', function () {
				self::removeAbandonedCartFromBotUser( 'woocommerce_checkout_update_order_meta' );;
			} );

			add_action( 'woocommerce_thankyou', function () {
				self::removeAbandonedCartFromBotUser( 'woocommerce_thankyou' );
			} );

		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}

	}

	public static function fireAbandonedCartTrigger( $hours, ChB_User $bot_user ) {
		$context = [ 'hours_ago' => $hours, 'woo_cart_url' => self::getCartRecoveryUrl( $bot_user->wp_user_id ) ];
		ChB_Common::my_log( $context, 1, 'processAbandonedCarts bot_user_id=' . $bot_user->wp_user_id . ' ' . $bot_user->fb_user_id );
		if ( $hours ) {
			ChB_ManyChat::fireManyChatTrigger( $bot_user->fb_user_id, self::TRIGGER_NAME, $context );
		}
	}

	/**
	 * @param $bot_wp_user_id
	 * @param $product_id
	 * Opening pre-filled cart, which user opens from link in bot
	 * Function is called from web page
	 */
	public static function recoverAbandonedCart( $bot_wp_user_id, $product_id ) {

		try {
			$ac_info = get_user_meta( $bot_wp_user_id, self::USER_ATTR_AC_INFO, true );
			if ( empty( $ac_info['cart'] ) ) {
				if ( $product_id && $url = get_permalink( $product_id ) ) {
					wp_safe_redirect( $url );
					exit;
				}

				return;
			}

			ChB_WooCart::mergeItemsIntoWCCart( $ac_info['cart'] );
			wp_safe_redirect( add_query_arg( self::GET_PAR_LOAD_CART_FLAG, 1, wc_get_cart_url() ) );
			exit;

		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}

	}

	public static function getCartRecoveryUrl( $bot_wp_user_id, $product_id = null ) {
		return add_query_arg( [
			ChB_WYSession::GET_PAR_CONNECT_TO_BOT_USER => ChB_WYSession::encodeBotUserForGetPar( $bot_wp_user_id, ChB_WYSession::TASK_CART_RECOVERY, $product_id ),
			self::GET_PAR_LOAD_CART_FLAG               => 1
		],
			site_url() );
	}

	public static function updateWYSessionAndAbandonedCart( $hook ) {

		try {

			if ( ! empty( self::$pause_ac_refresh ) ) {
				return;
			}

			// supposed to be always false
			if ( ! WC() || ! WC()->session->has_session() ) {
				return;
			}

			ChB_Common::my_log( 'updateWYSessionAndAbandonedCart hook=' . $hook . ' u=' . get_current_user_id() );

			// We have to call here initWYSession() again because:
			// if it is the first add_to_cart -
			// then here it is the first time we have WC()->has_session() true,
			// which affects initWYSession() behaviour
			$wy_session_id  = ChB_WYSession::initWYSession( true );
			$bot_wp_user_id = ChB_WYSession::getBotUser4WYSessionId( $wy_session_id );
			if ( $bot_wp_user_id ) {
				ChB_WooAbandonedCart::refreshAbandonedCart( $bot_wp_user_id );
			}
		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}
	}

	public static function refreshAbandonedCart( $bot_wp_user_id ) {

		$wy_session_ids = ChB_WYSession::getBotWYSessionIds( $bot_wp_user_id );

		if ( ! ( $ac_info = get_user_meta( $bot_wp_user_id, self::USER_ATTR_AC_INFO, true ) ) ) {
			$ac_info = [];
		}
		ChB_Common::my_debug_log( $wy_session_ids, 1, 'refreshAbandonedCart wy_sessions for ac:' );
		$cart = [];
		if ( $wy_session_ids ) {
			foreach ( $wy_session_ids as $cur_wy_session_id ) {
				$cur_wy_session = ChB_WYSession::session( $cur_wy_session_id );
				if ( ChB_Debug::isDebug() ) {
					ChB_Common::my_log( $cur_wy_session->getWCSessionIds(), 1, 'refreshAbandonedCart wc_sessions for wy=' . $cur_wy_session_id );
				}
				if ( $wc_session_ids = $cur_wy_session->getWCSessionIds() ) {
					$wc_session_ids_del = [];
					foreach ( $wc_session_ids as $wc_session_id ) {
						$cur_cart = self::getCartByWCSessionId( $wc_session_id );
						if ( ChB_Debug::isDebug() ) {
							ChB_Common::my_log( 'refreshAbandonedCart wc_session_id=' . $wc_session_id . ' cart count=' . ChB_Common::printArrayCount( $cur_cart ) );
						}
						if ( $cur_cart === false ) {
							$wc_session_ids_del[] = $wc_session_id;
						} elseif ( $cur_cart ) {
							$cart = ChB_WooCart::mergeWCCarts( $cart, $cur_cart );
						}
					}
					if ( $wc_session_ids_del ) {
						$cur_wy_session->disconnectWCSessionIds( $wc_session_ids_del );
					}
				}
			}
		}

		if ( $cart ) {
			$ac_info['cart']      = $cart;
			$ac_info['cart_hash'] = self::getCartHash( $ac_info['cart'] );
		} elseif ( isset( $ac_info['cart'] ) ) {
			unset( $ac_info['cart'] );
			unset( $ac_info['cart_hash'] );
		}

		if ( $cart ) {
			update_user_meta( $bot_wp_user_id, self::USER_ATTR_AC_INFO, $ac_info );

			// if it's not recovery - updating cart creation time and next reminder
			if ( empty( $_GET[ self::GET_PAR_LOAD_CART_FLAG ] ) ) {
				update_user_meta( $bot_wp_user_id, self::USER_ATTR_AC_LAST_CREATION_TIME, time() );
				update_user_meta( $bot_wp_user_id, self::USER_ATTR_AC_NEXT_REMINDER_TIME, time() + self::REMINDER_TIME_STEP_1H );
			}
		} else {
			ChB_WooAbandonedCart::removeAbandonedCart4BotUserId( $bot_wp_user_id );
		}
	}

	public static function getCartHash( $cart ) {
		if ( ! $cart || ! is_array( $cart ) ) {
			return '';
		}

		$items2hash = [];
		foreach ( $cart as $item ) {
			$items2hash[] = isset( $item['data_hash'] ) ? $item['data_hash'] : '';
		}

		return md5( wp_json_encode( $items2hash ) );
	}

	public static function getAbandonedCartDetails( ChB_User $bot_user ) {
		$ac_info = get_user_meta( $bot_user->wp_user_id, self::USER_ATTR_AC_INFO, true );
		if ( empty( $ac_info['cart'] ) || ! is_array( $ac_info['cart'] ) ) {
			return null;
		}

		$res = [];

		$count = 0;
		foreach ( $ac_info['cart'] as $item ) {
			if ( $count ++ >= 10 ) {
				break;
			}

			$wc_product = wc_get_product( empty( $item['variation_id'] ) ? $item['product_id'] : $item['variation_id'] );
			if ( ! ChB_Catalogue::productIsVisible( $wc_product ) ) {
				continue;
			}

			$res['cards'][] = [
				'title'      => ChB_Catalogue::getProductName( $wc_product ),
				'subtitle'   => $item['quantity'] . ' x ' . ChB_Common::printPrice( $wc_product->get_price() ),
				'image_url'  => ChB_Catalogue::getProductImage( $wc_product ),
				'action_url' => self::getCartRecoveryUrl( $bot_user->wp_user_id, $item['product_id'] )
			];
		}

		return $res;
	}

	/**
	 * @param $session_id
	 *
	 * @return bool|mixed|string|null false if session doesn't exist
	 */
	public static function getCartByWCSessionId( $session_id ) {

		if ( did_action( 'wp_loaded' ) && WC()->cart && WC()->session &&
		     WC()->session->get_customer_id() == $session_id ) {

			return WC()->cart->get_cart_for_session();
		}

		if ( ChB_WYSession::sessionIdIsUserId( $session_id ) ) {
			$cart = get_user_meta( $session_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
			if ( $cart ) {
				$cart = maybe_unserialize( $cart );
				if ( ! empty( $cart['cart'] ) ) {
					return $cart['cart'];
				}
			}
		}

		if ( ! class_exists( '\ChatBot\ChB_WC_Session_Reader' ) ) {
			require __DIR__ . '/ChB_WC_Session_Reader.php';
			if ( ! class_exists( '\ChatBot\ChB_WC_Session_Reader' ) ) {
				return null;
			}
		}
		$wc_session_reader = new ChB_WC_Session_Reader();

		$session = $wc_session_reader->get_session_by_id( $session_id );
		if ( ! $session ) {
			return false;//session doesn't exist
		}

		if ( ! empty( $session['cart'] ) ) {
			return maybe_unserialize( $session['cart'] );
		}

		return null;
	}

	public static function removeAbandonedCartFromBotUser( $hook ) {

		try {
			ChB_Common::my_log( 'removeAbandonedCartFromBotUser hook= ' . $hook . ' u=' . get_current_user_id() );

			if ( $wy_session_id = ChB_WYSession::getWYSessionCookie() ) {
				if ( $bot_wp_user_id = ChB_WYSession::getBotUser4WYSessionId( $wy_session_id ) ) {
					self::removeAbandonedCart4BotUserId( $bot_wp_user_id );
				}
			}
		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}

	}

	public static function removeAbandonedCart4BotUserId( $wp_user_id ) {
		ChB_Common::my_log( 'removeAbandonedCart4UserId ' . $wp_user_id );

		delete_user_meta( $wp_user_id, self::USER_ATTR_AC_INFO );
		delete_user_meta( $wp_user_id, self::USER_ATTR_AC_LAST_CREATION_TIME );
		delete_user_meta( $wp_user_id, self::USER_ATTR_AC_NEXT_REMINDER_TIME );
	}

	public static function cleanUpOldAbandonedCarts() {

		ChB_Common::my_log( 'cleanUpOldAbandonedCarts start' );

		$delete_before_ts = time() - ChB_Settings()->getParam( 'abandoned_cart_delete_after_days' ) * ChB_Constants::SECONDS_ONE_DAY;

		$ids = ChB_WooRemarketing::getBotUserIds4Reminders( $delete_before_ts, ChB_WooAbandonedCart::USER_ATTR_AC_LAST_CREATION_TIME );
		if ( $ids ) {
			foreach ( $ids as $id ) {
				self::removeAbandonedCart4BotUserId( $id );
			}
		}
		ChB_Common::my_log( 'cleanUpOldAbandonedCarts finish' );

	}

}