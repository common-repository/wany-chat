<?php

namespace ChatBot;


class ChB_User {
	public const USER_ATTR_LANG = 'rrbot_usr_lang';
	public const USER_ATTR_CHANNEL = 'rrbot_channel';
	public const USER_ATTR_MC_USER_ID = 'rrbot_mc_user_id';
	public const USER_ATTR_LAST_INTERACTION = 'rrbot_last_interaction';
	public const USER_ATTR_NAME_DOUBLECHECKED = 'rrbot_usr_nm_chk';
	public const USER_ATTR_IS_BOT_USER = 'rrbot_is_bot_user';
	public const USER_ATTR_IS_BOT_GUEST_USER = 'rrbot_is_bot_guest_user';
	public const USER_ATTR_GENDER = 'rrbot_gender';
	public const USER_ATTR_CART = 'rrbot_cart';
	public const USER_ATTR_WOO_WP_USER_ID = 'rrbot_usr_woo_wpuid';
	public const USER_ATTR_TRY_ON_OPTIONS = 'rrbot_usr_try_on_optns';
	public const USER_ATTR_PROMO = 'rrbot_usr_promos';
	public const USER_ATTR_OPTIONS = 'rrbot_usr_optns';
	public const USER_ATTR_MANAGER_SETINGS = 'rrbot_manager_settings';
	public const USER_STATUS_INACTIVE = 'inactive';
	public const USER_ATTR_STATUS = 'rrbot_usr_status';

	public const USER_ATTR_SHIPPING_FIRST_NAME = 'shipping_first_name';
	public const USER_ATTR_SHIPPING_LAST_NAME = 'shipping_last_name';
	public const USER_ATTR_EMAIL = 'billing_email';
	public const USER_ATTR_PHONE = 'billing_phone';
	public const USER_ATTR_COUNTRY = 'shipping_country';//'VN'
	public const USER_ATTR_CITY = 'shipping_city';
	public const USER_ATTR_STATE = 'shipping_state';
	public const USER_ATTR_POSTCODE = 'shipping_postcode';
	public const USER_ATTR_ADDRESS_LINE = 'shipping_address_1';

	public const USER_ATTR_FREE_INPUT = 'rrbot_usr_free_input';
	public const USER_ATTR_OPENED_PRODUCTS = 'rrbot_usr_opn_prds';
	public const USER_ATTR_OPENED_PRODUCTS_REMINDED = 'rrbot_usr_opn_prds_rmnd';
	public const USER_ATTR_LAST_OPENED_PRODUCTS = 'rrbot_usr_last_opn_prds';
	public string $fb_user_id;
	public string $channel;
	public ?string $mc_user_id;
	public ?ChB_User $cart_user;
	public \WP_User $wp_user;
	public ?int $wp_user_id;
	private ?array $mc_user_info;
	private string $lang;
	private ?ChB_Manager_Settings $manager_setings;
	private ?int $woo_wp_user_id;
	private ?string $gender = null;
	private ?array $free_input_attrs = null;
	private ?int $last_interaction_ts = null;
	private ?string $last_interaction_format = null;
	private ?\DateTime $last_interaction_dt = null;
	private ?array $_data;

	private function __construct( \WP_User $wp_user, string $fb_user_id, string $channel, ?string $mc_user_id, ?array $mc_user_info ) {
		$this->wp_user         = $wp_user;
		$this->fb_user_id      = $fb_user_id;
		$this->channel         = $channel;
		$this->mc_user_id      = $mc_user_id;
		$this->mc_user_info    = $mc_user_info;
		$this->wp_user_id      = $wp_user->ID;
		$this->manager_setings = null;
		$this->cart_user       = null;
	}

	public static function initUserByWPUserID( $wp_user_id ) {
		$wp_user = get_user_by( 'ID', $wp_user_id );
		if ( ! $wp_user ) {
			return false;
		}
		$channel    = get_user_meta( $wp_user->ID, self::USER_ATTR_CHANNEL, true );
		$mc_user_id = get_user_meta( $wp_user->ID, self::USER_ATTR_MC_USER_ID, true );

		return new ChB_User( $wp_user, $wp_user->user_login, $channel, $mc_user_id, null );
	}

	public static function findWPUserIDs( $q ) {
		if ( is_numeric( $q ) ) {
			$user = self::getWPUserByFBUserId( $q );
			if ( empty( $user ) ) {
				return [];
			} else {
				return [ $user ];
			}
		}

		$users = get_users(
			[
				'fields'         => [ 'ID' ],
				'role'           => 'customer',
				'search'         => '*' . $q . '*',
				'search_columns' => [ 'display_name' ]
			] );

		if ( empty( $users ) ) {
			return [];
		} else {
			return $users;
		}
	}

	public static function getAllCustomersProfilePics( $offset = 0, $limit = - 1 ) {

		$users = self::getAllActiveCustomers( $offset, $limit );
		$res   = [];
		foreach ( $users as $user ) {
			$path = $user->getUserProfilePicFromMC();
			if ( ! empty( $path ) ) {
				$res[] = $path;
			}
		}

		return $res;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array ChB_User
	 */
	public static function getAllActiveCustomers( $offset = 0, $limit = - 1 ) {
		$wp_users = self::getAllWPUsersIDAndLogin( 'customer', $offset, $limit );

		$res = [];
		foreach ( $wp_users as $wp_user ) {
			$fb_user_id = $wp_user->user_login;
			if ( $fb_user_id == ChB_Common::EXT_USER ) {
				continue;
			}

			if ( get_user_meta( $wp_user->ID, self::USER_ATTR_STATUS, true ) ) {
				continue;
			}

			$user = ChB_User::initUser( $fb_user_id );
			if ( $user ) {
				$res[] = $user;
			}
		}

		return $res;
	}

	public static function getAllWPUsersIDAndLogin( $role = null, $offset = 0, $limit = - 1 ) {
		$args = [
			'offset' => $offset,
			'number' => $limit,
			'fields' => [ 'ID', 'user_login' ]
		];
		if ( $role ) {
			$args['role'] = $role;
		}

		return get_users( $args );
	}

	public static function emailIsDummy( $email ) {
		if ( ! $email ) {
			return false;
		}

		if ( ChB_Settings()->getParam( 'new_user_dummy_email' ) === $email ) {
			return true;
		}

		$parts = explode( '@', $email );
		if ( ! $parts || count( $parts ) !== 2 ) {
			return false;
		}

		//first, just check domain
		if ( '@' . $parts[1] !== self::genDummyEmail4User( '' ) ) {
			return false;
		}
		if ( ! $parts[0] ) {
			return false;
		}

		$wp_user = get_user_by( 'login', $parts[0] );
		if ( ! $wp_user ) {
			return false;
		}

		if ( ! get_user_meta( $wp_user->ID, self::USER_ATTR_IS_BOT_USER, true ) ) {
			return false;
		}

		return ( $wp_user->user_email == self::genDummyEmail4User( $wp_user->user_login ) );
	}

	public static function printUserlist( $fb_users_ids ) {
		$res = [];
		foreach ( $fb_users_ids as $fb_user_id ) {
			$display_name = self::getSubscriberDisplayName( $fb_user_id, false );
			if ( $display_name ) {
				$res[] = $display_name . ' (' . $fb_user_id . ')';
			} else {
				$res[] = $fb_user_id;
			}
		}

		return implode( ', ', $res );
	}

	public static function getSubscriberDisplayName( $fb_user_id, $use_mc = true ) {
		$wp_user = self::getWPUserByFBUserId( $fb_user_id );
		if ( ! empty( $wp_user->display_name ) ) {
			return $wp_user->display_name;
		}

		//Для тех случаев, когда пользователь еще не создан в WP
		if ( $use_mc ) {
			return self::getNameFromManyChat( $fb_user_id );
		} else {
			null;
		}
	}

	public static function getNameFromManyChat( $fb_user_id ) {

		$info = json_decode( ChB_ManyChat::sendGet2ManyChat( 'https://api.manychat.com/fb/subscriber/getInfo?subscriber_id=' . $fb_user_id ), true );
		if ( $info['status'] !== 'success' ) {
			return false;
		}

		return $info['data']['name'];
	}

	public static function setMCUser4WooSession_API( $user_ref ) {

//		ChB_Common::my_log($_POST, 1, 'PST');
//		ChB_Common::my_log(WC()->session->get_customer_id(), 1, 'SSSN');
		return [ 'status' => 'success' ];
	}

	public function lastInteractionIsInside24H() {
		list( $last_interaction_ts ) = $this->getLastInteraction();
		if ( ! $last_interaction_ts ) {
			return false;
		}

		return ( time() - $last_interaction_ts < ChB_Events::WINDOW24H );
	}

	public function getLastInteraction() {
		if ( $this->last_interaction_ts ) {
			return [ $this->last_interaction_ts, $this->last_interaction_format, $this->last_interaction_dt ];
		}

		if ( $this->last_interaction_ts === 0 ) {
			return [ 0, null, null ];
		}

		if ( ChB_Settings()->auth->connectionIsDirect() ) {
			$last_interaction = get_user_meta( $this->wp_user->ID, self::USER_ATTR_LAST_INTERACTION, true );
			if ( $last_interaction ) {
				$this->last_interaction_ts = intval( $last_interaction );
				$this->last_interaction_dt = new \DateTime();
				$this->last_interaction_dt->setTimezone( ChB_Settings()->timezone );
				$this->last_interaction_dt->setTimestamp( $this->last_interaction_ts );
				$this->last_interaction_format = $this->last_interaction_dt->format( 'Y-m-d H:i:s' );
			}
		} elseif ( $this->mc_user_id ) {
			$mc_user_info = $this->getMCUserInfo();
			$li           = ( $this->channel === ChB_Constants::CHANNEL_IG ? 'ig_last_interaction' : 'last_interaction' );
			if ( ! empty( $mc_user_info[ $li ] ) ) {
				$this->last_interaction_dt = new \DateTime( $mc_user_info[ $li ] );//last_interaction string contains timezone
				$this->last_interaction_dt->setTimezone( ChB_Settings()->timezone );
				$this->last_interaction_ts     = $this->last_interaction_dt->getTimestamp();
				$this->last_interaction_format = $this->last_interaction_dt->format( 'Y-m-d H:i:s' );
			}
		}

		if ( ! $this->last_interaction_ts ) {
			$this->last_interaction_ts = 0;

			return [ 0, null, null ];
		}

		return [ $this->last_interaction_ts, $this->last_interaction_format, $this->last_interaction_dt ];
	}

	public function getMCUserInfo() {
		if ( $this->mc_user_info ) {
			return $this->mc_user_info;
		}

		if ( $this->mc_user_info === [] ) {
			return false;
		}

		if ( ! $this->mc_user_id ) {
			return false;
		}

		$info = self::getUserInfoFromManyChat( $this->mc_user_id, $this->wp_user_id );
		if ( $info ) {
			$this->mc_user_info = $info;
		} else {
			$this->mc_user_info = [];
		}//we've tried, but something went wrong

		return $this->mc_user_info;
	}

	private static function getUserInfoFromManyChat( $mc_user_id, $wp_user_id = null ) {
		$info = ChB_ManyChat::getUserInfoFromMC( $mc_user_id );

		if ( isset( $info['data']['status'] ) && $info['data']['status'] == ChB_User::USER_STATUS_INACTIVE ||
		     isset( $info['status'] ) && $info['status'] == 'error' && isset( $info['details']['messages'][0]['message'] ) &&
		     $info['details']['messages'][0]['message'] = 'Subscriber does not exist' ) {
			if ( $wp_user_id ) {
				update_user_meta( $wp_user_id, self::USER_ATTR_STATUS, ChB_User::USER_STATUS_INACTIVE );
			}

			return false;
		}

		if ( ! isset( $info['status'] ) || $info['status'] !== 'success' ) {
			return false;
		}

		return $info['data'];
	}

	public function setLastInteraction() {
		if ( ! ChB_Settings()->auth->connectionIsDirect() ) {
			return;
		}
		$now = time();
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_LAST_INTERACTION, $now );
		ChB_Common::my_log( 'setLastInteraction ' . $this->fb_user_id . ' li=' . $now );
	}

	public function getLang( $set_lang = null, $force = false ) {
		if ( ! $set_lang && ! $force && ! empty( $this->lang ) ) {
			return $this->lang;
		}

		if ( $set_lang ) {
			if ( $force || ! ChB_Settings()->getParam( 'default_force_lang' ) ) {
				$cur_lang = $set_lang;
			} else {
				$cur_lang = ChB_Settings()->getParam( 'default_force_lang' );
			}
		} else {
			$cur_lang = get_user_meta( $this->wp_user->ID, self::USER_ATTR_LANG, true );
			if ( $cur_lang ) {
				$this->lang = $cur_lang;

				return $cur_lang;
			}
			$mc_user_info = $this->getMCUserInfo();
			if ( $mc_user_info ) {
				$field_vals = ChB_ManyChat::getCFValuesFromManyChat( [ ChB_ManyChat::CF_Lang ], $mc_user_info );
				if ( ! empty( $field_vals[ ChB_ManyChat::CF_Lang ] ) ) {
					$cur_lang = $field_vals[ ChB_ManyChat::CF_Lang ];
				} elseif ( ChB_Settings()->getParam( 'default_force_lang' ) ) {
					$cur_lang = ChB_Settings()->getParam( 'default_force_lang' );
				} elseif ( ! empty( $mc_user_info['language'] ) ) {
					$cur_lang = $mc_user_info['language'];
				}
			}
		}

		$cur_lang = ChB_Lang::detectLang( $cur_lang );
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_LANG, $cur_lang );
		$this->lang = $cur_lang;

		return $cur_lang;
	}

	public function cartUserIsCurrentUser() {
		return $this->wp_user_id === $this->getCartUser()->wp_user_id;
	}

	public function getCartUser() {
		$cart_fb_user_id = $this->getManagerSettings()->getMarkedCustomer();
		if ( ! $cart_fb_user_id ) {
			$this->cart_user = null;

			return $this;
		}

		if ( $this->cart_user && $this->cart_user->fb_user_id == $cart_fb_user_id ) {
			return $this->cart_user;
		}

		$cart_user = ChB_User::initUser( $cart_fb_user_id );
		if ( $cart_user ) {
			$this->cart_user = $cart_user;

			return $cart_user;
		} else {
			$this->cart_user = null;
			$this->manager_setings->unmarkCustomer();
			ChB_Common::my_log( 'Fail to initialize cart user for manager' );

			return $this;
		}
	}

	public function getManagerSettings() {
		if ( $this->manager_setings === null ) {
			$this->manager_setings = new ChB_Manager_Settings( $this );
		}

		return $this->manager_setings;
	}

	public static function initUser( $fb_user_id, $channel = null, $mc_user_id = null ) {

		ChB_Common::my_log( 'INPUT----- initUser $fb_user_id=' . $fb_user_id . ' $channel=' . $channel . ' $mc_user_id=' . $mc_user_id );
		$mc_user_info = null;
		$user_info    = null;

		if ( $fb_user_id ) {
			if ( ! $mc_user_id && $channel === ChB_Constants::CHANNEL_FB ) {
				if ( ChB_Settings()->auth->connectionIsMC() ) {
					$mc_user_id = $fb_user_id;
				}
			}

			$wp_user = self::getWPUserByFBUserId( $fb_user_id );
			if ( ! $wp_user ) {
				if ( $mc_user_id ) {
					if ( ! ( $mc_user_info = self::getUserInfoFromManyChat( $mc_user_id ) ) ) {
						ChB_Common::my_log( 'initUser ERROR: MC user info request fail' );

						return false;
					}

					if ( ! $channel ) {
						$channel = ChB_ManyChat::getUserChannelFromMC( $mc_user_id, $mc_user_info );
					}
					$user_info = $mc_user_info;
				} else {
					if ( ! $channel ) {
						ChB_Common::my_log( 'initUser ERROR: both channel and mc_user_id are empty' );

						return false;
					}
					if ( $fb_user_id !== ChB_Common::EXT_USER ) {
						if ( ! ( $user_info = ChB_Common::getUserInfoFromFBAPI( $fb_user_id ) ) ) {
							ChB_Common::my_log( 'initUser ERROR: FB API user info request fail' );

							return false;
						}
					} else {
						$user_info = [];
					}
				}

				//creating user from user_info
				$wp_user = self::createUser( $fb_user_id, $mc_user_id, $channel, $user_info );

			} elseif ( $mc_user_id && ! get_user_meta( $wp_user->ID, self::USER_ATTR_MC_USER_ID, true ) ) {
				if ( ! ( $mc_user_info = self::getUserInfoFromManyChat( $mc_user_id, $wp_user->ID ) ) ) {
					ChB_Common::my_log( 'initUser ERROR: MC user info request fail' );

					return false;
				}
				//we didn't have mc_user_id before, attaching it
				self::updateUser( $wp_user, $mc_user_id, $channel, $mc_user_info );
			}
		} else {
			if ( ! $mc_user_id ) {
				ChB_Common::my_log( 'initUser ERROR: both fb_user_id and mc_user_id are empty' );

				return false;
			}

			/**
			 * Main scenario for call from MC App from existing user
			 */
			$wp_user = self::getWPUserByMCUserID( $mc_user_id );
			if ( ! $wp_user ) {
				if ( ! ( $mc_user_info = self::getUserInfoFromManyChat( $mc_user_id ) ) ) {
					ChB_Common::my_log( 'initUser ERROR: MC user info request fail' );

					return false;
				}

				$channel = ChB_ManyChat::getUserChannelFromMC( $mc_user_id, $mc_user_info );
				if ( $channel === ChB_Constants::CHANNEL_FB ) {
					$fb_user_id = $mc_user_id;
				} elseif ( $channel === ChB_Constants::CHANNEL_IG ) {
					//check IG user match
					$fb_user_id = ChB_ManyChat::getUserIGSIDFromMC( $mc_user_info );
					if ( ! $fb_user_id ) {
						ChB_Common::my_log( 'initUser ERROR: get IGSID from MC failed' );

						return false;
					}
				} else {
					ChB_Common::my_log( 'initUser ERROR: incorrect channel=' . $channel );

					return false;
				}

				$wp_user = self::getWPUserByFBUserId( $fb_user_id );
				if ( $wp_user ) {
					self::updateUser( $wp_user, $mc_user_id, $channel, $mc_user_info );
				} else {
					$wp_user = self::createUser( $fb_user_id, $mc_user_id, $channel, $mc_user_info );
				}
			}
		}

		if ( empty( $wp_user ) ) {
			ChB_Common::my_log( 'initUser ERROR: something went wrong' );

			return false;
		}

		if ( ! $fb_user_id ) {
			$fb_user_id = $wp_user->user_login;
		}
		if ( ! $channel ) {
			$channel = get_user_meta( $wp_user->ID, self::USER_ATTR_CHANNEL, true );
		}
		if ( ! $mc_user_id ) {
			$mc_user_id = get_user_meta( $wp_user->ID, self::USER_ATTR_MC_USER_ID, true );
		}

		// additional check for first_name and last name - sometimes they're empty for unknown reason
		if ( empty( $wp_user->first_name ) && empty( $wp_user->last_name ) && $channel === ChB_Constants::CHANNEL_FB &&
		     ! get_user_meta( $wp_user->ID, self::USER_ATTR_NAME_DOUBLECHECKED, true ) ) {
			if ( ! empty( $mc_user_info ) && empty( $mc_user_info['first_name'] ) && empty( $mc_user_info['last_name'] ) ) {
				ChB_Common::my_log( $mc_user_info, true, 'WARNING!!! first_name and last_name are empty in MC user info' );
			} elseif ( ! empty( $user_info ) && empty( $user_info['first_name'] ) && empty( $user_info['last_name'] ) ) {
				ChB_Common::my_log( $user_info, true, 'WARNING!!! first_name and last_name are empty in FB user info' );
			}

			$user_info = ChB_Common::getUserInfoFromFBAPI( $fb_user_id );
			if ( $user_info ) {
				ChB_Common::my_log( 'setting first_name and last_name from fb api for fb_user_id=' . $fb_user_id );
				ChB_User::updateUser( $wp_user, $mc_user_id, $channel, $user_info );
			}
			update_user_meta( $wp_user->ID, self::USER_ATTR_NAME_DOUBLECHECKED, time() );
		}

		return new ChB_User( $wp_user, $fb_user_id, $channel, $mc_user_id, $mc_user_info );
	}

	public static function getWPUserByFBUserId( $fb_user_id ) {
		return get_user_by( 'login', $fb_user_id );
	}

	private static function createUser( $fb_user_id, $mc_user_id, $channel, &$user_info ) {

		//ignore current user from adding to mailing list on creation
		ChB_WPHooks::ignoreMailingLists();

		$wp_username  = $fb_user_id;
		$first_name   = ( empty( $user_info['first_name'] ) ? '' : $user_info['first_name'] );
		$last_name    = ( empty( $user_info['last_name'] ) ? '' : $user_info['last_name'] );
		$gender       = ( empty( $user_info['gender'] ) ? '' : $user_info['gender'] );
		$display_name = self::getDisplayNameFromUserInfo( $user_info );

		$wp_email = ChB_User::genDummyEmail4User( $wp_username );
		$password = ChB_Common::my_rand_string( 15 );
		ChB_Settings()->setParam( 'new_user_dummy_email', $wp_email );

		do_action( 'wany_hook_before_add_bot_user' );

		$wp_user_id = wc_create_new_customer( $wp_email,
			$wp_username,
			$password,
			[
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => $display_name
			]
		);
		if ( $mc_user_id ) {
			update_user_meta( $wp_user_id, self::USER_ATTR_MC_USER_ID, $mc_user_id );
		}
		if ( $channel ) {
			update_user_meta( $wp_user_id, self::USER_ATTR_CHANNEL, $channel );
		}
		if ( $gender ) {
			update_user_meta( $wp_user_id, self::USER_ATTR_GENDER, $gender );
		}
		update_user_meta( $wp_user_id, self::USER_ATTR_IS_BOT_USER, 1 );
		if ( ! empty( $user_info['status'] ) && $user_info['status'] === 'visitor' ) {
			update_user_meta( $wp_user_id, self::USER_ATTR_IS_BOT_GUEST_USER, 1 );
		}

		$wp_user = get_user_by( 'id', $wp_user_id );
		if ( ! $wp_user ) {
			ChB_Common::my_log( 'createUser ERROR: fail to create new user' );

			return false;
		}

		return $wp_user;
	}

	public static function getDisplayNameFromUserInfo( $user_info ) {
		if ( ! empty( $user_info['name'] ) ) {
			return $user_info['name'];
		} elseif ( ! empty( $user_info['first_name'] ) ) {
			return $user_info['first_name'] . ( empty( $user_info['last_name'] ) ? '' : ( ' ' . $user_info['last_name'] ) );
		}

		return '';
	}

	public static function genDummyEmail4User( $username ) {
		return $username . '@' . str_replace( '/', '.', ChB_Settings()->getDomainPath() );
	}

	private static function updateUser( \WP_User $wp_user, $mc_user_id, $channel, &$user_info ) {

		if ( ! empty( $user_info['first_name'] ) ) {
			$wp_user->first_name = $user_info['first_name'];
		}
		if ( ! empty( $user_info['last_name'] ) ) {
			$wp_user->last_name = $user_info['last_name'];
		}
		if ( $display_name = self::getDisplayNameFromUserInfo( $user_info ) ) {
			$wp_user->display_name = $display_name;
		}

		if ( $mc_user_id ) {
			update_user_meta( $wp_user->ID, self::USER_ATTR_MC_USER_ID, $mc_user_id );
		}

		if ( $channel ) {
			update_user_meta( $wp_user->ID, self::USER_ATTR_CHANNEL, $channel );
		}

		if ( ! empty( $user_info['gender'] ) ) {
			update_user_meta( $wp_user->ID, self::USER_ATTR_GENDER, $user_info['gender'] );
		}

		wp_update_user( $wp_user );
	}

	public static function getWPUserByMCUserID( $mc_user_id ) {
		$users = get_users( [
			'meta_key'   => self::USER_ATTR_MC_USER_ID,
			'meta_value' => $mc_user_id,
			'number'     => 1
		] );
		if ( ! empty( $users[0] ) && $users[0] instanceof \WP_User ) {
			return $users[0];
		}

		return false;
	}

	public function startFreeInput( $validation_callback, $success_callback, $error_callback, $timeout_callback, $timeout, $retry_times ) {
		$free_input_id          = ChB_Common::my_rand_string( 4 );
		$this->free_input_attrs = [
			'id'  => $free_input_id,
			'vcb' => $validation_callback,
			'scb' => $success_callback,
			'ecb' => $error_callback,
			'tcb' => $timeout_callback,
			'exp' => time() + $timeout * 60,
			'rtt' => $retry_times,
			'rtd' => 0
		];
		update_user_meta( $this->wp_user_id, self::USER_ATTR_FREE_INPUT, json_encode( $this->free_input_attrs, JSON_UNESCAPED_UNICODE ) );

		return $free_input_id;
	}

	public function finishFreeInput( $free_input_id = null ) {
		if ( $free_input_id && $free_input_id !== $this->getFreeInputAttr( 'id' ) ) {
			return;
		}
		delete_user_meta( $this->wp_user_id, self::USER_ATTR_FREE_INPUT );
	}

	public function getFreeInputAttr( $attr_name ) {

		if ( $this->free_input_attrs === null ) {
			$val = get_user_meta( $this->wp_user_id, self::USER_ATTR_FREE_INPUT, true );
			if ( $val ) {
				$this->free_input_attrs = json_decode( $val, true );
			}
			if ( ! $this->free_input_attrs ) {
				$this->free_input_attrs = [];
			}
		}

		if ( ! $this->free_input_attrs ) {
			return null;
		}

		if ( $attr_name === 'id' ) {
			return ( empty( $this->free_input_attrs['id'] ) ? null : $this->free_input_attrs['id'] );
		} elseif ( $attr_name === 'validation_callback' ) {
			return ( empty( $this->free_input_attrs['vcb'] ) ? null : $this->free_input_attrs['vcb'] );
		} elseif ( $attr_name === 'success_callback' ) {
			return ( empty( $this->free_input_attrs['scb'] ) ? null : $this->free_input_attrs['scb'] );
		} elseif ( $attr_name === 'error_callback' ) {
			return ( empty( $this->free_input_attrs['ecb'] ) ? null : $this->free_input_attrs['ecb'] );
		} elseif ( $attr_name === 'timeout_callback' ) {
			return ( empty( $this->free_input_attrs['tcb'] ) ? null : $this->free_input_attrs['tcb'] );
		} elseif ( $attr_name === 'expire' ) {
			return ( empty( $this->free_input_attrs['exp'] ) ? null : $this->free_input_attrs['exp'] );
		} elseif ( $attr_name === 'retry_times' ) {
			return ( empty( $this->free_input_attrs['rtt'] ) ? 0 : $this->free_input_attrs['rtt'] );
		} elseif ( $attr_name === 'retry_times_done' ) {
			return ( empty( $this->free_input_attrs['rtd'] ) ? 0 : $this->free_input_attrs['rtd'] );
		}

		return null;
	}

	public function freeInputIsOn() {
		$ts    = $this->getFreeInputAttr( 'expire' );
		$is_on = ( $ts && $ts > time() );
		ChB_Common::my_log( 'freeInputIsOn=' . $is_on );

		return $is_on;
	}

	public function freeInputCheckTriesLeft() {
		$retry_times      = $this->getFreeInputAttr( 'retry_times' );
		$retry_times_done = $this->getFreeInputAttr( 'retry_times_done' );
		if ( $retry_times_done + 1 > $retry_times ) {
			return false;
		}

		$this->free_input_attrs['rtd'] += 1;
		update_user_meta( $this->wp_user_id, self::USER_ATTR_FREE_INPUT, json_encode( $this->free_input_attrs ) );

		return true;
	}

	public function getFirstName() {
		return $this->wp_user->first_name;
	}

	public function getLastName() {
		return $this->wp_user->last_name;
	}

	public function getUserDisplayName() {
		return $this->wp_user->display_name;
	}

	public function getUserShippingFirstName() {

		if ( ! isset( $this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ] ) ) {
			$this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_SHIPPING_FIRST_NAME, true );
			if ( ! $this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ] ) {
				$this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ] = $this->getFirstName();
			}
		}

		return $this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ];
	}

	public function saveUserShippingFirstName( $val ) {

		$this->_data[ self::USER_ATTR_SHIPPING_FIRST_NAME ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_SHIPPING_FIRST_NAME, $val );

		return true;
	}

	public function getUserShippingLastName() {

		if ( ! isset( $this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ] ) ) {
			$this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_SHIPPING_LAST_NAME, true );
			if ( ! $this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ] ) {
				$this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ] = $this->getLastName();
			}
		}

		return $this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ];
	}

	public function saveUserShippingLastName( $val ) {

		$this->_data[ self::USER_ATTR_SHIPPING_LAST_NAME ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_SHIPPING_LAST_NAME, $val );

		return true;
	}

	public function getGender() {
		if ( $this->gender === null ) {
			$gender       = get_user_meta( $this->wp_user->ID, self::USER_ATTR_GENDER, true );
			$this->gender = ( $gender ? $gender : '' );
		}

		return $this->gender;
	}

	public function getUserAccountEmail() {
		$userdata = get_userdata( $this->wp_user->ID );
		if ( isset( $userdata->user_email ) ) {
			return $userdata->user_email;
		} else {
			return false;
		}
	}

	public function getUserPhone() {

		if ( ! isset( $this->_data[ self::USER_ATTR_PHONE ] ) ) {
			$this->_data[ self::USER_ATTR_PHONE ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_PHONE, true );
		}

		return $this->_data[ self::USER_ATTR_PHONE ];
	}

	public function savePhone( $phone, $validate = true ) {

		if ( $validate ) {
			$phone = self::validatePhone( $phone );
			if ( ! $phone ) {
				return false;
			}
		}

		ChB_Common::my_log( 'MCPh=' . $this->getUserPhoneFromMC() . ' ph=' . $phone );
		if ( ! ChB_Settings()->auth->connectionIsDirect() && $this->mc_user_id && $this->getUserPhoneFromMC() !== $phone ) {
			if ( $ChB = ChatBot::openTempChatBotSession( $this ) ) {
				$fields = [ 'subscriber_id' => $this->mc_user_id, 'phone' => $phone ];

				if ( ChB_Settings()->getParam( 'set_has_opt_in_sms' ) && ChB_Settings()->getParam( 'opt_in_sms_consent_phrase' ) ) {
					$fields['has_opt_in_sms'] = true;
					$fields['consent_phrase'] = ChB_Settings()->getParam( 'opt_in_sms_consent_phrase' );
				} else {
					$fields['has_opt_in_sms'] = false;
				}

				$res = ChB_ManyChat::sendPost2ManyChat( '/fb/subscriber/updateSubscriber', $fields, $ChB );

				ChatBot::closeTempChatBotSession();
				if ( ! ChB_Common::checkStatusIsSuccess( $res ) ) {
					ChB_Common::my_log( $res, true, 'WARNING! validateAndSavePhone MC ERROR' );
				}
			}
		}

		$this->_data[ self::USER_ATTR_PHONE ] = $phone;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_PHONE, $phone );

		return true;
	}

	public static function validatePhone( $phone ) {
		if ( ! $phone || $phone[0] !== '+' ) {
			return false;
		}

		$phone = str_replace( '+', '', str_replace( '(', '', str_replace( ')', '', str_replace( '-', '', str_replace( ' ', '', $phone ) ) ) ) );
		if ( ! is_numeric( $phone ) ) {
			return false;
		}
		if ( strlen( $phone ) < 8 || strlen( $phone ) > 14 ) {
			return false;
		}

		return '+' . $phone;
	}

	private function getUserPhoneFromMC() {
		$mc_user_info = $this->getMCUserInfo();

		return ( empty( $mc_user_info['phone'] ) ? false : $mc_user_info['phone'] );
	}

	public function getUserBillingEmail() {

		if ( ! isset( $this->_data[ self::USER_ATTR_EMAIL ] ) ) {
			$email                                = get_user_meta( $this->wp_user->ID, self::USER_ATTR_EMAIL, true );
			$this->_data[ self::USER_ATTR_EMAIL ] = self::emailIsDummy( $email ) ? null : $email;
		}

		return $this->_data[ self::USER_ATTR_EMAIL ];
	}

	public function saveUserBillingEmail( $val, $validate = true ) {
		if ( $validate && ! filter_var( $val, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		}

		$this->_data[ self::USER_ATTR_EMAIL ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_EMAIL, $val );

		return true;
	}

	public function getUserShippingCountry() {

		if ( ! isset( $this->_data[ self::USER_ATTR_COUNTRY ] ) ) {
			$this->_data[ self::USER_ATTR_COUNTRY ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_COUNTRY, true );
		}

		return $this->_data[ self::USER_ATTR_COUNTRY ];
	}

	public function saveUserShippingCountry( $val, $validate = true ) {

		$this->_data[ self::USER_ATTR_COUNTRY ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_COUNTRY, $val );

		return true;
	}

	public function getUserShippingCity() {

		if ( ! isset( $this->_data[ self::USER_ATTR_CITY ] ) ) {
			$this->_data[ self::USER_ATTR_CITY ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_CITY, true );
		}

		return $this->_data[ self::USER_ATTR_CITY ];
	}

	public function saveUserShippingCity( $val, $validate = true ) {

		$this->_data[ self::USER_ATTR_CITY ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_CITY, $val );

		return true;
	}

	public function getUserShippingState() {

		if ( ! isset( $this->_data[ self::USER_ATTR_STATE ] ) ) {
			$this->_data[ self::USER_ATTR_STATE ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_STATE, true );
		}

		return $this->_data[ self::USER_ATTR_STATE ];
	}

	public function saveUserShippingState( $val, $validate = true ) {

		$this->_data[ self::USER_ATTR_STATE ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_STATE, $val );

		return true;
	}

	public function getUserShippingPostcode() {
		if ( ! isset( $this->_data[ self::USER_ATTR_POSTCODE ] ) ) {
			$this->_data[ self::USER_ATTR_POSTCODE ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_POSTCODE, true );
		}

		return $this->_data[ self::USER_ATTR_POSTCODE ];
	}

	public function saveUserShippingPostcode( $val, $validate = true ) {

		$this->_data[ self::USER_ATTR_POSTCODE ] = $val;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_POSTCODE, $val );

		return true;
	}

	public function getUserShippingAddressLine() {
		if ( ! isset( $this->_data[ self::USER_ATTR_ADDRESS_LINE ] ) ) {
			$this->_data[ self::USER_ATTR_ADDRESS_LINE ] = get_user_meta( $this->wp_user->ID, self::USER_ATTR_ADDRESS_LINE, true );
		}

		return $this->_data[ self::USER_ATTR_ADDRESS_LINE ];
	}

	public function saveUserShippingAddressLine( $shipping_address, $validate = true ) {
		if ( $validate && mb_strlen( $shipping_address ) < ChB_Common::ADDR_MIN_LEN ) {
			return false;
		}
		$shipping_address                            = str_replace( chr( 10 ), ' ', $shipping_address );
		$this->_data[ self::USER_ATTR_ADDRESS_LINE ] = $shipping_address;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_ADDRESS_LINE, $shipping_address );

		return $shipping_address;
	}

	public function getUserAttr( $user_attr ) {
		if ( ! isset( $this->_data[ $user_attr ] ) ) {
			$this->_data[ $user_attr ] = get_user_meta( $this->wp_user->ID, $user_attr, true );
		}


		return $this->_data[ $user_attr ];
	}

	public function setUserAttr( $user_attr, $val ) {

		$this->_data[ $user_attr ] = $val;
		update_user_meta( $this->wp_user->ID, $user_attr, $val );

		return true;
	}

	public function getWooWPUserID() {
		if ( ! isset( $this->woo_wp_user_id ) ) {
			$this->woo_wp_user_id = (int) get_user_meta( $this->wp_user->ID, self::USER_ATTR_WOO_WP_USER_ID, true );
		}

		return $this->woo_wp_user_id;
	}

	public function setWooWPUserID( $woo_wp_user_id ) {
		$this->woo_wp_user_id = (int) $woo_wp_user_id;
		update_user_meta( $this->wp_user->ID, self::USER_ATTR_WOO_WP_USER_ID, $this->woo_wp_user_id );
	}

	public function unsetWooWPUserID() {
		unset( $this->woo_wp_user_id );
		delete_user_meta( $this->wp_user->ID, self::USER_ATTR_WOO_WP_USER_ID );
	}

	public static function getAllBotUsersConnectedToWooUsers() {
		$args = [
			'fields'       => 'ID',
			'meta_key'     => ChB_User::USER_ATTR_WOO_WP_USER_ID,
			'meta_compare' => 'EXISTS'
		];

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();

		return $users ? $users : [];
	}

	public static function getBotWPUserIdByWooWPUserID( $woo_wp_user_id ) {

		$args = [
			'fields'     => 'ID',
			'number'     => 1,
			'meta_key'   => self::USER_ATTR_WOO_WP_USER_ID,
			'meta_value' => $woo_wp_user_id
		];

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();

		return empty( $users[0] ) ? null : $users[0];
	}

	public function markProductOpenedByUser( $product_id ) {
		$product_ids = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS );
		if ( empty( $product_ids[0] ) ) {
			update_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS, $product_id );
		} elseif ( strpos( $product_ids[0], strval( $product_id ) ) === false ) {
			update_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS, $product_id . '#' . $product_ids[0] );
		}

		return true;
	}

	public function getProductsOpenedByUser() {
		$product_ids_str = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS, true );
		if ( empty( $product_ids_str ) || is_wp_error( $product_ids_str ) ) {
			return [];
		} else {
			return explode( '#', $product_ids_str );
		}
	}

	public function getRemindedProductsOpenedByUser() {
		$marked_product_ids_str = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS_REMINDED, true );
		if ( empty( $marked_product_ids_str ) || is_wp_error( $marked_product_ids_str ) ) {
			return [];
		} else {
			return explode( '#', $marked_product_ids_str );
		}
	}

	public function markRemindedProductsOpenedByUser( $marked_product_ids ) {
		if ( ! empty( $marked_product_ids ) ) {
			update_user_meta( $this->wp_user->ID, self::USER_ATTR_OPENED_PRODUCTS_REMINDED, implode( '#', $marked_product_ids ) );
		}
	}

	public function clearLastProductsOpenedByUser() {
		delete_user_meta( $this->wp_user->ID, self::USER_ATTR_LAST_OPENED_PRODUCTS );
	}

	public function addProduct2LastProductsOpenedByUser( $product_id ) {
		$last_products = $this->getLastProductsOpenedByUser();

		if ( empty( $last_products ) ) {
			$last_products = [ $product_id ];
		} else {
			$ind = array_search( $product_id, $last_products );
			if ( $ind !== false ) {
				unset( $last_products[ $ind ] );
			}
			$last_products[] = $product_id;
		}

		return update_user_meta( $this->wp_user->ID, self::USER_ATTR_LAST_OPENED_PRODUCTS, json_encode( $last_products ) );
	}

	public function getLastProductsOpenedByUser() {
		return json_decode( get_user_meta( $this->wp_user->ID, self::USER_ATTR_LAST_OPENED_PRODUCTS, true ), true );
	}

	public function getBlock4UserReminders() {
		return $this->getUserActionState( 'rmndr_blck' );
	}

	public function getUserActionState( $action_name ) {
		$options_attr = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPTIONS, true );
		if ( empty( $options_attr ) ) {
			return false;
		}

		$options = json_decode( $options_attr, true );
		if ( empty( $options[ $action_name ] ) ) {
			return false;
		}

		return ( time() < $options[ $action_name ] );
	}

	public function setBlock4UserReminders( $ttl = ChB_Common::SECONDS_YEAR ) {
		$this->setUserActionState( 'rmndr_blck', $ttl );
	}

	public function setUserActionState( $action_name, $ttl ) {
		$options_attr = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPTIONS, true );
		$options      = empty( $options_attr ) ? [] : json_decode( $options_attr, true );
		$new_time     = time() + intval( $ttl );
		if ( empty( $options[ $action_name ] ) || $options[ $action_name ] < $new_time ) {
			$options[ $action_name ] = $new_time;
			update_user_meta( $this->wp_user->ID, self::USER_ATTR_OPTIONS, json_encode( $options ) );
			ChB_Common::my_log( $this->wp_user->user_login . ' ' . $action_name . ' for user has been set' );
		}
	}

	public function unsetBlock4UserReminders() {
		$this->unsetUserActionState( 'rmndr_blck' );
	}

	public function unsetUserActionState( $action_name ) {
		$options_attr = get_user_meta( $this->wp_user->ID, self::USER_ATTR_OPTIONS, true );
		$options      = empty( $options_attr ) ? [] : json_decode( $options_attr, true );
		if ( ! empty( $options[ $action_name ] ) ) {
			unset( $options[ $action_name ] );
			update_user_meta( $this->wp_user->ID, self::USER_ATTR_OPTIONS, json_encode( $options ) );
			ChB_Common::my_log( $this->wp_user->user_login . ' ' . $action_name . ' for user has been unset' );
		}
	}

	public function getBlock4ManagersNotifications() {
		return $this->getUserActionState( 'blck_mng_ntf' );
	}

	public function setBlock4ManagersNotifications( $ttl ) {
		$this->setUserActionState( 'blck_mng_ntf', $ttl );
	}

	public function setCF( $cf_name, $cf_value ) {
		update_user_meta( $this->wp_user->ID, $cf_name, $cf_value );
	}

	public function getHi() {
		if ( $this->getFirstName() ) {
			return ChB_Lang::translateWithPars( ChB_Lang::LNG0012, $this->getFirstName() );
		} else {
			ChB_Lang::translate( ChB_Lang::LNG0011 );
		}
	}

	public function getUserProfilePicFromMC( $full_size = false ) {

		if ( ! $this->mc_user_id ) {
			return null;
		}
		$medium_size             = 720;
		$profile_pic_path        = ChB_Settings()->getTryOn( 'profile_pics_dir' ) . $this->mc_user_id . '.jpg';
		$profile_pic_medium_path = ChB_Settings()->getTryOn( 'profile_pics_dir' ) . $$this->mc_user_id . '-' . $medium_size . '.jpg';

		if ( ! file_exists( $profile_pic_path ) ) {
			$mc_user_info = $this->getMCUserInfo();
			if ( empty( $mc_user_info['profile_pic'] ) || $mc_user_info['profile_pic'] == 'NULL' ) {
				ChB_Common::my_log( 'CANNOT GET MANYCHAT INFO ' . $this->mc_user_id );

				return null;
			}
			$image_url = $mc_user_info['profile_pic'];
			if ( empty( ChB_Common::downloadURL( $image_url, $profile_pic_path ) ) ) {
				return null;
			}

			//creating medium size
			$img = imagecreatefromjpeg( $profile_pic_path );
			list( $img, $shrinked ) = ChB_Common::shrinkImage( $img, $medium_size );
			if ( $shrinked ) {
				imagejpeg( $img, $profile_pic_medium_path, 100 );
			}
			imagedestroy( $img );
		}

		return ( ! $full_size && file_exists( $profile_pic_medium_path ) ? $profile_pic_medium_path : $profile_pic_path );
	}

	public function getUserProfilePicFromFB( $full_size = false ) {

		$medium_size             = 720;
		$profile_pic_path        = ChB_Settings()->getTryOn( 'profile_pics_dir' ) . 'fbapi' . $this->fb_user_id . '.jpg';
		$profile_pic_medium_path = ChB_Settings()->getTryOn( 'profile_pics_dir' ) . 'fbapi' . $this->fb_user_id . '-' . $medium_size . '.jpg';

		if ( ! file_exists( $profile_pic_path ) ) {
			$user_info = ChB_Common::getUserInfoFromFBAPI( $this->fb_user_id );
			if ( ! empty( $user_info['profile_pic'] ) ) {
				$image_url = $user_info['profile_pic'];
				if ( empty( ChB_Common::downloadURL( $image_url, $profile_pic_path ) ) ) {
					return null;
				}
				//creating medium size
				$img = imagecreatefromjpeg( $profile_pic_path );
				list( $img, $shrinked ) = ChB_Common::shrinkImage( $img, $medium_size );
				if ( $shrinked ) {
					imagejpeg( $img, $profile_pic_medium_path, 100 );
				}
				imagedestroy( $img );
			} else {
				ChB_Common::my_log( $this->fb_user_id . ' cannot get profile_pic from fb api' );
			}
		}

		return ( ! $full_size && file_exists( $profile_pic_medium_path ) ? $profile_pic_medium_path : $profile_pic_path );
	}

}