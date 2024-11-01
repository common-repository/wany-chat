<?php


namespace ChatBot;


class ChB_WYSession {

	private const WC_SESSION_KEY_WY_SESSION = 'wy_sn';
	private const COOKIE_KEY_WY_SESSION = 'wy_sn';
	public const KVS_PREFIX_WY_SESSION = 'WY_SSN__';

	public const GET_PAR_CONNECT_TO_BOT_USER = 'wy_ct';
	public const USER_ATTR_WY_SESSION_ID = 'wy_si';

	public const TASK_CHECKOUT = 'co';
	public const TASK_CART_RECOVERY = 'ac';

	private $_data;
	private bool $_dirty;
	private bool $_new;
	public string $wy_session_id;

	private static array $_sessions;
	private static array $_bots_wy_session_ids;
	private static array $_bots_wy_session_ids_dirty;

	public static function init() {

		$initialize_session = false;
		if ( ChB_Settings()->getParam( 'web_redirect' ) == ChB_Settings::SETTING_WEB_REDIRECT_PLACE_ORDER ) {
			$initialize_session = true;
		}

		if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) ||
		     ChB_Settings()->getParam( 'use_woo_views_remarketing' ) ) {

			$initialize_session = true;
			require_once dirname( __FILE__ ) . '/ChB_WooRemarketing.php';
			ChB_WooRemarketing::init();
		}

		if ( $initialize_session ) {
			add_action( 'wp_loaded', function () {
				ChB_WYSession::initWYSession();
			}, 10 );

			add_action( 'template_redirect', [ 'ChatBot\ChB_WYSession', 'connectWYSessionToBotUserByGetPar' ], 9 );
			add_action( 'wp_logout', [ 'ChatBot\ChB_WYSession', 'forgetWYSession' ] );
		}
	}

	public static function session( $wy_session_id ) {
		if ( ! isset( self::$_sessions ) ) {
			self::$_sessions = [];
			add_action( 'shutdown', [ '\ChatBot\ChB_WYSession', 'save_data' ], 20 );
		}

		if ( isset( self::$_sessions[ $wy_session_id ] ) ) {
			return self::$_sessions[ $wy_session_id ];
		} else {
			return ( self::$_sessions[ $wy_session_id ] = new self( $wy_session_id ) );
		}
	}

	private function __construct( $wy_session_id ) {
		$this->wy_session_id = $wy_session_id;
		$key                 = self::KVS_PREFIX_WY_SESSION . $this->wy_session_id;
		$val                 = ChB_Settings()->kvs->get( $key );
		if ( $val ) {
			$this->_data = @unserialize( $val );
			$this->_new  = false;
		} else {
			$this->_new = true;
		}

		if ( ! isset( $this->_data ) || ! is_array( $this->_data ) ) {
			$this->_data = [];
		}

		$this->_dirty = false;
	}

	public static function save_data() {

		//saving modified sessions
		if ( ! empty( self::$_sessions ) ) {
			foreach ( self::$_sessions as $wy_session ) {
				if ( $wy_session instanceof ChB_WYSession ) {
					$wy_session->_save();
				}
			}
		}

		self::_updateBotsWYSessionIdsInDB();
	}

	private function _save() {
		if ( $this->_dirty ) {

			$key              = self::KVS_PREFIX_WY_SESSION . $this->wy_session_id;
			$this->_data['t'] = time();

			foreach ( [ 'buid', 'wsc', 'p', 'ip' ] as $field ) {
				if ( isset( $this->_data[ $field ] ) && ! $this->_data[ $field ] ) {
					unset( $this->_data[ $field ] );
				}
			}

			ChB_Settings()->kvs->set( $key, @serialize( $this->_data ) );
			$this->_new = false;
		}
	}

	private static function _updateBotsWYSessionIdsInDB() {

		// updating changed $wy_session_ids
		if ( ! empty( self::$_bots_wy_session_ids_dirty ) ) {
			foreach ( self::$_bots_wy_session_ids_dirty as $bot_wp_user_id => $val ) {
				if ( isset( self::$_bots_wy_session_ids[ $bot_wp_user_id ] ) ) {
					delete_user_meta( $bot_wp_user_id, self::USER_ATTR_WY_SESSION_ID );
					foreach ( self::$_bots_wy_session_ids[ $bot_wp_user_id ] as $wy_session_id ) {
						add_user_meta( $bot_wp_user_id, self::USER_ATTR_WY_SESSION_ID, $wy_session_id, false );
					}
				}
			}
		}
	}

	public function is_new() {
		return $this->_new;
	}

	public function getWCSessionIds() {
		return ( empty( $this->_data['wcs'] ) || ! is_array( $this->_data['wcs'] ) ) ? [] : $this->_data['wcs'];
	}

	public function connectWCSessionId( $wc_session_id ) {
		if ( empty( $this->_data['wcs'] ) || ! in_array( $wc_session_id, $this->_data['wcs'] ) ) {
			$this->_data['wcs'][] = $wc_session_id;
			$this->_dirty         = true;
		}
	}

	public function disconnectWCSessionIds( $wc_session_ids ) {
		if ( is_array( $wc_session_ids ) && ! empty( $this->_data['wcs'] ) && is_array( $this->_data['wcs'] ) ) {
			$this->_data['wcs'] = array_values( array_diff( $this->_data['wcs'], $wc_session_ids ) );
		}
	}

	public function getBotUserId() {
		return empty( $this->_data['buid'] ) ? null : $this->_data['buid'];
	}

	public function connectBotUserId( $bot_wp_user_id ) {
		if ( empty( $this->_data['buid'] ) || $this->_data['buid'] != $bot_wp_user_id ) {
			$this->_data['buid'] = $bot_wp_user_id;
			$this->_dirty        = true;


			self::_readBotWYSessionIds( $bot_wp_user_id );
			if ( empty( self::$_bots_wy_session_ids[ $bot_wp_user_id ] ) ||
			     ! in_array( $this->wy_session_id, self::$_bots_wy_session_ids[ $bot_wp_user_id ] ) ) {

				self::$_bots_wy_session_ids[ $bot_wp_user_id ][]     = $this->wy_session_id;
				self::$_bots_wy_session_ids_dirty[ $bot_wp_user_id ] = 1;
			}

			if ( $this->getIsParentSession() ) {
				$this->setPersistentConnectionToBotUser();
			}
		}
	}

	public function disconnectBotUserId( $bot_wp_user_id = null ) {
		if ( ! empty( $this->_data['buid'] ) && ( ! $bot_wp_user_id || $bot_wp_user_id == $this->_data['buid'] ) ) {
			$bot_wp_user_id = $this->_data['buid'];
			unset( $this->_data['buid'] );
			$this->_dirty = true;

			self::_readBotWYSessionIds( $bot_wp_user_id );
			if ( ! empty( self::$_bots_wy_session_ids[ $bot_wp_user_id ] ) ) {
				$ind = array_search( $this->wy_session_id, self::$_bots_wy_session_ids[ $bot_wp_user_id ] );
				if ( $ind !== false ) {
					unset( self::$_bots_wy_session_ids[ $bot_wp_user_id ][ $ind ] );
					self::$_bots_wy_session_ids_dirty[ $bot_wp_user_id ] = 1;
				}
			}
		}
	}

	private static function _readBotWYSessionIds( $bot_wp_user_id ) {
		if ( ! isset( self::$_bots_wy_session_ids[ $bot_wp_user_id ] ) ) {
			self::$_bots_wy_session_ids[ $bot_wp_user_id ] = get_user_meta( $bot_wp_user_id, self::USER_ATTR_WY_SESSION_ID, false );
		}
	}

	public static function getBotWYSessionIds( $bot_wp_user_id ) {
		self::_readBotWYSessionIds( $bot_wp_user_id );

		return self::$_bots_wy_session_ids[ $bot_wp_user_id ];
	}

	public function getParentSessionId() {
		return empty( $this->_data['p'] ) ? null : $this->_data['p'];
	}

	public function setParentSession( $parent_wy_session_id ) {
		if ( empty( $this->_data['p'] ) || $this->_data['p'] != $parent_wy_session_id ) {
			$this->_data['p'] = $parent_wy_session_id;
			$this->_dirty     = true;
		}
	}

	public function getIsParentSession() {
		return ! empty( $this->_data['ip'] );
	}

	public function setIsParentSession() {
		if ( empty( $this->_data['ip'] ) ) {
			$this->_data['ip'] = 1;
			$this->_dirty      = true;
		}

		//WC session id matches wy_session_id for logged-in users
		$this->connectWCSessionId( $this->wy_session_id );

		$this->setPersistentConnectionToBotUser();
	}

	//setting persistent connection between bot user and woo user
	public function setPersistentConnectionToBotUser() {
		if ( empty( $this->_data['buid'] ) || empty( $this->_data['ip'] ) ) {
			return;//not a big deal
		}

		if ( ! class_exists( '\ChatBot\ChB_User' ) ) {
			chb_load();
		}

		$bot_user = ChB_User::initUserByWPUserID( $this->_data['buid'] );
		if ( $bot_user ) {
			$bot_user->setWooWPUserID( $this->wy_session_id );
		}
	}

	public function setProductView( $product_id ) {
		if ( empty( $this->_data['rmkt']['v'] ) || ! in_array( $product_id, $this->_data['rmkt']['v'] ) ) {
			$this->_data['rmkt']['v'][] = $product_id;
			$this->_dirty               = true;
		}
	}

	public function getProductViews() {
		return ( empty( $this->_data['rmkt']['v'] ) ? null : $this->_data['rmkt']['v'] );
	}

	public static function forgetWYSession() {
		try {
			self::unsetWYSessionCookie();
		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}

	}

	public static function getWYSessionCookie() {
		return isset( $_COOKIE[ self::COOKIE_KEY_WY_SESSION ] ) ? $_COOKIE[ self::COOKIE_KEY_WY_SESSION ] : null;
	}

	public static function unsetWYSessionCookie() {
		ChB_WooCommon::unsetCookie( self::COOKIE_KEY_WY_SESSION );
	}

	public static function currentWySessionIsConnectedToBot() {
		if ( $wy_session_id = ChB_WYSession::getWYSessionCookie() ) {
			return ! empty( self::session( $wy_session_id )->getBotUserId() );
		}

		return false;
	}

	public static function getWYSession( $wy_session_id ) {
		if ( ! $wy_session_id ) {
			return false;
		}

		$key = self::KVS_PREFIX_WY_SESSION . $wy_session_id;
		$val = ChB_Settings()->kvs->get( $key );

		return $val ? @unserialize( $val ) : null;

	}

	public static function setNewWYSessionCookie( $val = null ) {

		if ( ! $val ) {
			if ( ! class_exists( '\ChatBot\ChB_Common' ) ) {
				require_once dirname( __FILE__ ) . '/ChB_Common.php';
			}

			$val = ChB_Common::my_rand_string( 32 );
		}

		ChB_WooCommon::setCookie( self::COOKIE_KEY_WY_SESSION, $val );

		return $val;
	}

	public static function initWYSession( $check_in_db = false ) {

		if ( ! ( $wy_session_id = self::getWYSessionCookie() ) ) {
			$wy_session_id = self::setNewWYSessionCookie();
		}

		if ( WC()->session && WC()->session->has_session() ) {
			$wy_session_key = $wy_session_id . '#' . WC()->session->get_customer_id();

			if ( WC()->session->get( self::WC_SESSION_KEY_WY_SESSION ) !== $wy_session_key ) {

				if ( $user_id = get_current_user_id() ) {
					//User logs in just now
					$prev_wy_session_id = $wy_session_id;
					$wy_session_id      = $user_id;

					// parent session is a session corresponding to logged-in user,
					// it is a parent to a temporary session(s) from which user logged in.
					// initWYSession() is the only place where parent is set
					self::session( $prev_wy_session_id )->setParentSession( $wy_session_id );
					self::session( $wy_session_id )->setIsParentSession();

					if ( $bot_wp_user_id = self::session( $prev_wy_session_id )->getBotUserId() ) {
						ChB_WYSession::connectWYSessionToBotUser( $bot_wp_user_id, $wy_session_id );

					} elseif ( $bot_wp_user_id = self::session( $wy_session_id )->getBotUserId() ) {
						ChB_WYSession::connectWYSessionToBotUser( $bot_wp_user_id, $wy_session_id );
					}

					//here $wy_session_id equals $user_id equals WC()->session->get_customer_id()
					$wy_session_key = $wy_session_id . '#' . $wy_session_id;
					self::setNewWYSessionCookie( $wy_session_id );
				}

				WC()->session->set( self::WC_SESSION_KEY_WY_SESSION, $wy_session_key );
				self::session( $wy_session_id )->connectWCSessionId( WC()->session->get_customer_id() );

			} elseif ( $check_in_db ) {

				/** Uncommon scenario: according to cookies we have wy_session connected to wc_session (and maybe to bot),
				 * but wy_session doesn't exist in db
				 * Additional check here to make sure wy_session does exist, if not - creating and connecting it
				 * This operation is done only during add_to_cart and similar events
				 */
				if ( self::session( $wy_session_id )->is_new() ) {

					ChB_Common::my_log( __FUNCTION__ . ': wy_session=' . $wy_session_id . ' is empty, but shouldn\'t be - recreating it' );
					if ( $user_id = get_current_user_id() ) {
						ChB_Common::my_log( __FUNCTION__ . ': wy_session=' . $wy_session_id . ' setting is parent' );
						self::session( $wy_session_id )->setIsParentSession();

						if ( ! class_exists( '\ChatBot\ChB_User' ) ) {
							chb_load();
						}
						if ( $bot_wp_user_id = ChB_User::getBotWPUserIdByWooWPUserID( $user_id ) ) {
							ChB_WYSession::connectWYSessionToBotUser( $bot_wp_user_id, $wy_session_id );
						}
					}
				}

				// reconnecting wc session anyway
				self::session( $wy_session_id )->connectWCSessionId( WC()->session->get_customer_id() );
				self::session( $wy_session_id )->_save();
			}
		}

		return $wy_session_id;
	}

	public static function sessionIdIsUserId( $session_id ) {
		return $session_id && is_numeric( $session_id ) && get_user_by( 'id', $session_id );
	}

	/**
	 * Returns WP user ID
	 *
	 * @return mixed|null
	 */
	public static function getBotUser4WYSessionId( $wy_session_id ) {

		return ChB_WYSession::session( $wy_session_id )->getBotUserId();

	}

	public static function connectWYSessionToBotUser( $bot_wp_user_id, $wy_session_id_add = null, $wy_session_id_del = null ) {

		ChB_Common::my_log( 'connectWYSessionToBotUser: bot_user=' . $bot_wp_user_id . ' wy_add=' . $wy_session_id_add . ' wy_del=' . $wy_session_id_del );

		if ( $wy_session_id_add ) {

			$wy_session_ids_add = [ $wy_session_id_add ];
			if ( $parent_wy_session_id_add = ChB_WYSession::session( $wy_session_id_add )->getParentSessionId() ) {
				$wy_session_ids_add[] = $parent_wy_session_id_add;
			}

			foreach ( $wy_session_ids_add as $cur_wy_session_id_add ) {

				//checking if $cur_wy_session_id_add was connected to another bot user (unlikely)
				$cur_wy_session_add  = ChB_WYSession::session( $cur_wy_session_id_add );
				$prev_bot_wp_user_id = $cur_wy_session_add->getBotUserId();
				if ( $prev_bot_wp_user_id && $prev_bot_wp_user_id != $bot_wp_user_id ) {
					self::connectWYSessionToBotUser( $prev_bot_wp_user_id, null, $cur_wy_session_id_add );
				}
				$cur_wy_session_add->connectBotUserId( $bot_wp_user_id );
			}

		} elseif ( $wy_session_id_del ) {
			ChB_WYSession::session( $wy_session_id_del )->disconnectBotUserId( $bot_wp_user_id );
		}

		ChB_WooRemarketing::refreshRemarketing( $bot_wp_user_id, true );
		ChB_WooAbandonedCart::refreshAbandonedCart( $bot_wp_user_id );

	}

	public static function encodeBotUserForGetPar( $bot_wp_user_id, $task = null, $par1 = null ) {
		$key = 'bugp' . $bot_wp_user_id . $task;
		if ( ! ( $val = ChB_RuntimeCache::get( $key ) ) ) {
			$val = ChB_Encryption::encrypt( $bot_wp_user_id . '.' . $task . '.' . $par1 );
			ChB_RuntimeCache::set( $key, $val );
		}

		return $val;
	}

	public static function decodeBotUserFromGetPar( $enc_bot_wp_user_id ) {
		$val  = ChB_Encryption::decrypt( $enc_bot_wp_user_id );
		$vals = explode( '.', $val );

		if ( ! isset( $vals[1] ) ) {
			$vals[1] = null;
		}
		if ( ! isset( $vals[2] ) ) {
			$vals[2] = null;
		}

		return $vals;
	}

	/**
	 * Opening link in browser from bot
	 * We know bot user, so we connect it to current wy_session
	 */
	public static function connectWYSessionToBotUserByGetPar() {

		try {
			if ( empty( $_GET[ self::GET_PAR_CONNECT_TO_BOT_USER ] ) ) {
				return;
			}

			chb_load();

			$bot_user_flag = urldecode( sanitize_text_field( $_GET[ self::GET_PAR_CONNECT_TO_BOT_USER ] ) );
			list( $bot_wp_user_id, $task, $par1 ) = self::decodeBotUserFromGetPar( $bot_user_flag );
			if ( ! $bot_wp_user_id ) {
				return;
			}

			if ( $wy_session_id = ChB_WYSession::getWYSessionCookie() ) {
				ChB_WYSession::connectWYSessionToBotUser( $bot_wp_user_id, $wy_session_id );
			}

			if ( $task === ChB_WYSession::TASK_CHECKOUT ) {
				if ( $bot_user = ChB_User::initUserByWPUserID( $bot_wp_user_id ) ) {

					$cart = new ChB_Cart( $bot_wp_user_id, $bot_user );
					// adding products from bot cart
					ChB_WooCart::mergeItemsIntoWCCart( $cart->convertItemsToWCCartItems() );

					// setting shipping chosen in bot
					$cart->activateSavedShippingOptionId();
					if ( $shipping_item = ChB_ShippingOption::calcShipping( $cart ) ) {
						if ( $shipping_item->wc_shipping_method_id && $shipping_item->wc_shipping_instance_id ) {
							WC()->session->set( 'chosen_shipping_methods', [ $shipping_item->wc_shipping_method_id . ':' . $shipping_item->wc_shipping_instance_id ] );
						}
					}

					// pre-filling shipping and billing data
					add_filter( 'woocommerce_checkout_get_value', function ( $value, $field ) use ( $bot_user ) {
						return ChB_WooCommon::woocommerceCheckoutFieldFromBotUser( $bot_user, $value, $field );
					}, 30, 2 );

					if ( ! is_checkout() ) {
						wp_safe_redirect( wc_get_checkout_url() );
					}
				}
			} elseif ( $task === ChB_WYSession::TASK_CART_RECOVERY ) {
				ChB_WooAbandonedCart::recoverAbandonedCart( $bot_wp_user_id, $par1 );
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
	 * Removes session directly, without using ChB_WYSession objects
	 * To be used in standalone clean-ups. When used alongside with objects, data can be inconsistent
	 *
	 * @param $wy_session_id
	 * @param bool $id_is_key
	 * @param bool $bot_wp_user_id
	 * @param bool $check_is_parent
	 * @param int $delete_before_ts
	 *
	 * @return bool|int
	 */
	public static function removeWYSession( $wy_session_id, $id_is_key, $bot_wp_user_id, $check_is_parent = true, $delete_before_ts = 0, $empty_session_delete_before_ts = 0 ) {
		if ( ! $wy_session_id ) {
			return false;
		}

		$wc_session_reader = new ChB_WC_Session_Reader();
		$session_key       = $id_is_key ? $wy_session_id : self::KVS_PREFIX_WY_SESSION . $wy_session_id;
		$delete            = true;

		$val = ChB_Settings()->kvs->get( $session_key );
		if ( ! $val ) {
			$delete = false;
		} else {
			$data = @unserialize( $val );
			if ( $bot_wp_user_id && ( empty( $data['buid'] ) || $bot_wp_user_id != $data['buid'] ) ) {
				$delete = false;
			} elseif ( $check_is_parent && ! empty( $data['ip'] ) ) {
				$delete = false;
			} elseif ( $delete_before_ts ) {
				$t = empty( $data['t'] ) ? 0 : $data['t'];
				if ( $t > $delete_before_ts ) {
					$delete = false;
				}

				// Despite this session not being old, we still may want to delete it because it is empty.
				// This is for sessions generated by crawlers
				if ( ! $delete
				     && $empty_session_delete_before_ts
				     && $t < $empty_session_delete_before_ts
				     && empty( $data['buid'] )
				     && empty( $data['ip'] ) ) {

					$delete = true;

					ChB_Common::my_debug_log( 'deleting empty wy_session=' . $wy_session_id );
					// won't delete if wy_session is connected to same alive WC session (unlikely)
					if ( ! empty( $data['wcs'] ) ) {
						foreach ( $data['wcs'] as $wc_session_id ) {
							if ( $wc_session_reader->get_session_by_id( $wc_session_id ) ) {
								ChB_Common::my_debug_log( 'cancelled deleting because of wc_session_id=' . $wc_session_id );
								$delete = false;
								break;
							}
						}
					}
				}
			}
		}

		if ( $delete ) {
			if ( ! empty( $data['buid'] ) ) {
				$id = $id_is_key ? substr( $wy_session_id, strlen( self::KVS_PREFIX_WY_SESSION ) ) : $wy_session_id;
				delete_user_meta( $data['buid'], ChB_WYSession::USER_ATTR_WY_SESSION_ID, $id );
			}

			return ChB_Settings()->kvs->del( $session_key );
		}

		return 0;
	}

	/**
	 * Cleaning up old wy sessions
	 */
	public static function cleanUpOldWYSessions() {
		ChB_Common::my_log( 'cleanUpOldWYSessions start now=' . ChB_Common::timestamp2DateTime( time() ) );

		$delete_before_ts                = time() - ChB_Settings()->getParam( 'abandoned_cart_delete_after_days' ) * ChB_Constants::SECONDS_ONE_DAY;
		$empty_sessions_delete_before_ts = time() - ChB_Constants::SECONDS_ONE_DAY;

		if ( $sessions = ChB_Settings()->kvs->scanAllKeysByPrefix( ChB_WYSession::KVS_PREFIX_WY_SESSION ) ) {
			foreach ( $sessions as $session_key ) {
				self::removeWYSession( $session_key, true, null, true, $delete_before_ts, $empty_sessions_delete_before_ts );
			}
		}
		ChB_Common::my_log( 'cleanUpOldWYSessions finish' );
	}

	public static function removeAllWySessions( $bot_wp_user_id = null ) {
		$wy_session_ids = ChB_Settings()->kvs->scanAllKeysByPrefix( ChB_WYSession::KVS_PREFIX_WY_SESSION );
		foreach ( $wy_session_ids as $wy_session_id ) {
			ChB_WYSession::removeWYSession( $wy_session_id, true, $bot_wp_user_id, false );
		}
	}

}