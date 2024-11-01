<?php


namespace ChatBot;


class ChB_Auth {
	private const OPTION_TOKENS = 'RRB_TOKENS';
	private const WY_TOKEN = 'wy_token';
	private const WY_TOKEN_TO_BE_VERIFIED = 'wy_token_to_be_verified';
	private const MC_APP_TOKEN = 'mc_app_token';

	private const MC_PAGE_ID = 'page_id';
	private const FB_ACCESS_TOKEN = 'fb_access_token';
	private const FB_PAGE_ID = 'fb_page_id';
	private const IG_BUSINESS_ACCOUNT_ID = 'ig_ba_id';

	private const IS_TEST_ENV = 'is_test_env';
	private const CONNECTION_TYPE = 'con_type';

	private const CONNECTION_TYPE_DIRECT = 'dct';
	private const CONNECTION_TYPE_MANYCHAT = 'mc';

	private const MERCADO_PAGO_ACCESS_TOKEN = 'wy_mp_at';

	public const ROBOKASSA_MERCHANT_LOGIN = 'wy_rk_ml';
	public const ROBOKASSA_PASSWORD_1 = 'wy_rk_pwd1';
	public const ROBOKASSA_PASSWORD_2 = 'wy_rk_pwd2';
	public const ROBOKASSA_TEST_PASSWORD_1 = 'wy_rk_test_pwd1';
	public const ROBOKASSA_TEST_PASSWORD_2 = 'wy_rk_test_pwd2';

	private array $tokens_options;

	public function __construct() {
		$t                    = get_option( self::OPTION_TOKENS );
		$this->tokens_options = ( is_array( $t ) ? $t : [] );
	}

	private function updateInDB() {
		update_option( self::OPTION_TOKENS, $this->tokens_options );
	}

	public function authorize( $wy_token2check ) {
		if ( ! ( $wy_token = $this->getWYToken() ) ) {
			return false;
		}

		if ( is_array( $wy_token2check ) ) {
			return ( isset( $wy_token2check['wy_token'] ) && $wy_token2check['wy_token'] === $wy_token );
		}

		return $wy_token && $wy_token2check === $wy_token;
	}

	public static function getAccessDeniedErrorJSON() {
		return [
			'status'  => 'error',
			'message' => 'access denied'
		];
	}

	public function getWYToken() {
		return ( isset( $this->tokens_options[ self::WY_TOKEN ] ) ? $this->tokens_options[ self::WY_TOKEN ] : null );
	}

	public function getWYTokenToBeVerified() {
		return ( isset( $this->tokens_options[ self::WY_TOKEN_TO_BE_VERIFIED ] ) ? $this->tokens_options[ self::WY_TOKEN_TO_BE_VERIFIED ] : null );
	}

	public function getMCAppToken() {
		if ( ! $this->connectionIsMC() ) {
			return null;
		}

		return ( isset( $this->tokens_options[ self::MC_APP_TOKEN ] ) ? $this->tokens_options[ self::MC_APP_TOKEN ] : null );
	}

	public function connectionTypeIsNotSet() {
		return empty( $this->tokens_options[ self::CONNECTION_TYPE ] );
	}

	public function connectionIsDirect() {
		return ! empty( $this->tokens_options[ self::CONNECTION_TYPE ] ) && $this->tokens_options[ self::CONNECTION_TYPE ] === self::CONNECTION_TYPE_DIRECT;
	}

	public function connectionIsMC() {
		return ! empty( $this->tokens_options[ self::CONNECTION_TYPE ] ) && $this->tokens_options[ self::CONNECTION_TYPE ] === self::CONNECTION_TYPE_MANYCHAT;
	}

	public function setConnectionIsDirect() {
		$this->tokens_options[ self::CONNECTION_TYPE ] = self::CONNECTION_TYPE_DIRECT;
		$this->updateInDB();
	}

	public function setConnectionIsMC() {
		$this->tokens_options[ self::CONNECTION_TYPE ] = self::CONNECTION_TYPE_MANYCHAT;
		$this->updateInDB();
	}

	public function getMCPageID() {
		return ( isset( $this->tokens_options[ self::MC_PAGE_ID ] ) ? $this->tokens_options[ self::MC_PAGE_ID ] : null );
	}

	public function getFBPageID() {
		return ( isset( $this->tokens_options[ self::FB_PAGE_ID ] ) ? $this->tokens_options[ self::FB_PAGE_ID ] : null );
	}

	public function getIGBusinessAccountID() {
		return ( isset( $this->tokens_options[ self::IG_BUSINESS_ACCOUNT_ID ] ) ? $this->tokens_options[ self::IG_BUSINESS_ACCOUNT_ID ] : null );
	}

	public function isTestEnv() {
		return ! empty( $this->tokens_options[ self::IS_TEST_ENV ] );
	}

	public function getFBAccessToken() {
		return ( isset( $this->tokens_options[ self::FB_ACCESS_TOKEN ] ) ? $this->tokens_options[ self::FB_ACCESS_TOKEN ] : null );
	}

	public function requestWYToken() {

		// First, clearing fb access token and unsubscribing webhooks - BEFORE requesting a new wy_token
		if ( $this->getFBAccessToken() ) {
			$this->setFBAccessToken( '', '', '' );
		}

		$url = ChB_Constants::REQUEST_WY_TOKEN_URL . '&domain=' . ChB_Settings()->getDomainPath();
		$res = ChB_Common::sendPost( $url, $this->getWYToken() ? [ 'wy_token' => $this->getWYToken() ] : [] );
		if ( $res ) {
			$data = json_decode( $res, true );
			if ( ! empty( $data['wy_token'] ) ) {
				$this->setTokens( '', $data['wy_token'], '', '' );

				return true;
			}
		}

		return false;
	}

	public function checkWYTokenToBeVerifiedIsAlive() {
		$res = ChB_Common::sendPost( ChB_Constants::CHECK_WY_TOKEN2VERIFY_IS_ALIVE_URL, [ 'wy_token' => $this->getWYTokenToBeVerified() ] );
		if ( $res ) {
			$data = json_decode( $res, true );
			if ( ! empty( $data['status'] ) && $data['status'] === 'success' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $wy_token
	 * @param $wy_token_to_be_verified
	 * @param $mc_app_token - if empty then connection is manychatless
	 * @param $page_id - if connection is manychatless, then it is fb_page_id. Otherwise it is manychat page_id
	 * @param null $is_test_env
	 */
	public function setTokens( $wy_token, $wy_token_to_be_verified, $mc_app_token, $page_id, $is_test_env = null ) {

		// Clearing fb_access_token and unsubscribing webhooks before setting new page_id
		// This way we guarantee that fb_access_token is empty or belongs to current page_id
		if ( $this->getFBAccessToken() && ( ! $page_id || $page_id != $this->getMCPageID() ) ) {
			$this->setFBAccessToken( '', '', '' );
		}

		/**
		 * This is the only place where WY_TOKEN_TO_BE_VERIFIED and WY_TOKEN are SET
		 * After user has chosen connection type, WY_TOKEN_TO_BE_VERIFIED and WY_TOKEN cannot be simultaneously not empty
		 * 1. WY_TOKEN_TO_BE_VERIFIED and WY_TOKEN are both empty - error (cannot get WY_TOKEN_TO_BE_VERIFIED from server)
		 * 2. WY_TOKEN_TO_BE_VERIFIED is not empty, WY_TOKEN is empty - waiting for verification
		 * 3. WY_TOKEN_TO_BE_VERIFIED is empty, WY_TOKEN is not empty - verified
		 */
		if ( $wy_token_to_be_verified ) {
			$wy_token = '';
		}

		if ( ! $wy_token ) {
			$mc_app_token = '';
			$page_id      = '';
		}

		$this->tokens_options[ self::WY_TOKEN ]                = $wy_token;
		$this->tokens_options[ self::WY_TOKEN_TO_BE_VERIFIED ] = $wy_token_to_be_verified;
		$this->tokens_options[ self::MC_APP_TOKEN ]            = $mc_app_token;
		$this->tokens_options[ self::CONNECTION_TYPE ]         = $wy_token ? ( $mc_app_token ? self::CONNECTION_TYPE_MANYCHAT : self::CONNECTION_TYPE_DIRECT ) : '';

		// for reloadMCData()
		$this->updateInDB();

		// if we are disconnecting from a page, this will also clear fb_page_name, fb_page_username and ig_account_name
		// even if recent connection was manychatless
		ChB_Settings()->reloadMCData();

		if ( $wy_token ) {
			if ( $mc_app_token ) {
				$this->tokens_options[ self::MC_PAGE_ID ] = $page_id;

				// if 'fb/page/getInfo' contains id - that means manychat account was created from fb page
				// in this case, this id is in fact fb_page_id
				// otherwise we don't have fb_page_id, but only manychat account name which hopefully matches ig_account_name (ridiculous!!)
				//
				//  ¯\_(ツ)_/¯
				//
				$this->tokens_options[ self::FB_PAGE_ID ] = ChB_Settings()->getParam( 'mc_page_getinfo_id' ) ? ChB_Settings()->getParam( 'mc_page_getinfo_id' ) : null;
			} else {
				$this->tokens_options[ self::MC_PAGE_ID ] = null;
				$this->tokens_options[ self::FB_PAGE_ID ] = $page_id;
			}
		} else {
			$this->tokens_options[ self::MC_PAGE_ID ] = null;
			$this->tokens_options[ self::FB_PAGE_ID ] = null;
		}

		if ( ! $wy_token ) {
			$this->tokens_options[ self::IS_TEST_ENV ] = false;
		} elseif ( $is_test_env ) {
			$this->tokens_options[ self::IS_TEST_ENV ] = true;
		}

		$this->updateInDB();
	}

	private function setFBAccessToken( $fb_access_token, $fb_access_token_page_id, $ig_ba_id ) {

		if ( ! $fb_access_token ) {
			// connecting a new fb page or disconnecting an old one

			if ( ! $this->isTestEnv() && $this->getFBPageId() && $this->getFBAccessToken() ) {
				$res = ChB_Common::sendPost( ChB_Constants::UNSUBSCRIBE_WEBHOOK_URL,
					[
						'access_token' => $this->getFBAccessToken(),
						'page_id'      => $this->connectionIsDirect() ? $this->getFBPageId() : $this->getMCPageId(),
						'wy_token'     => $this->getWYToken(),
						'is_test_env'  => $this->isTestEnv()
					] );

				if ( ChB_Common::checkStatusIsSuccess( $res ) ) {
					ChB_Common::my_log( 'Unsubscribed webhook for fb_page_id=' . $this->getFBPageId() );
				} else {
					ChB_Common::my_log( 'Failed to unsubscribe webhook for fb_page_id=' . $this->getFBPageId() );
				}
			}

			$this->tokens_options[ self::FB_ACCESS_TOKEN ]        = '';
			$this->tokens_options[ self::IG_BUSINESS_ACCOUNT_ID ] = '';

		} else {
			// remote call from wany after token excahnge

			if ( empty( $this->tokens_options[ self::FB_PAGE_ID ] ) ) {
				//for ig-first connection
				$this->tokens_options[ self::FB_PAGE_ID ] = $fb_access_token_page_id;

			} elseif ( $this->tokens_options[ self::FB_PAGE_ID ] != $fb_access_token_page_id ) {
				return false;
			}

			$this->tokens_options[ self::FB_ACCESS_TOKEN ]        = $fb_access_token;
			$this->tokens_options[ self::IG_BUSINESS_ACCOUNT_ID ] = $ig_ba_id;
		}

		$this->updateInDB();

		return true;
	}

	public static function verifyWYToken_API( array &$post_payload ) {
		$wy_token_to_be_verified = ChB_Settings()->auth->getWYTokenToBeVerified();
		if ( $wy_token_to_be_verified ) {
			if ( ! empty( $post_payload['wy_token'] ) && isset( $post_payload['mc_app_token'] ) && ! empty( $post_payload['page_id'] ) ) {
				$wy_token = $post_payload['wy_token'];
				if ( $wy_token_to_be_verified == $wy_token ) {
					ChB_Settings()->auth->setTokens( $post_payload['wy_token'], '', $post_payload['mc_app_token'], $post_payload['page_id'] );

					return [ 'status' => 'success' ];
				}
			}
		}
		ChB_Common::my_log( $post_payload, true, 'verifyWYToken_API ERROR my wy_token=' . $wy_token_to_be_verified . ' input=' );

		return [ 'status' => 'error' ];
	}

	public static function setTokens_API( array &$post_payload ) {
		if ( isset( $post_payload['set']['wy_token'] ) && isset( $post_payload['set']['mc_app_token'] ) && isset( $post_payload['set']['page_id'] ) ) {
			ChB_Settings()->auth->setTokens( $post_payload['set']['wy_token'], '', $post_payload['set']['mc_app_token'], $post_payload['set']['page_id'] );

			return [ 'status' => 'success' ];
		}
		ChB_Common::my_log( $post_payload, true, 'setTokens_API ERROR input=' );

		return [ 'status' => 'error' ];
	}

	public static function regenerateTokens_AdminAPI() {

		if ( ChB_Settings()->auth->requestWYToken() ) {
			return [ 'status' => 'success' ];
		} else {
			return [ 'status' => 'error' ];
		}
	}

	public static function clearFBPage_AdminAPI() {
		if ( ChB_Settings()->auth->setFBAccessToken( '', '', '' ) ) {
			return [ 'status' => 'success' ];
		} else {
			return [ 'status' => 'error' ];
		}
	}

	public static function connectTestDomain_AdminAPI() {
		$post_body = file_get_contents( 'php://input' );
		$data      = json_decode( $post_body, true );

		if ( empty( $data['wy_token'] ) ) {
			return [ 'status' => 'error' ];
		}
		$wy_token = $data['wy_token'];

		$res = ChB_Common::sendPost( ChB_Constants::CONNECT_TEST_DOMAIN_URL,
			[
				'wy_token' => $wy_token,
				'domain'   => ChB_Settings()->getDomainPath()
			] );

		$data = json_decode( $res, true );
		if ( ! ChB_Common::checkStatusIsSuccess( $data ) ) {
			ChB_Common::my_log( $res, true, 'connectTestDomain_AdminAPI ERROR. Response=' );

			return [ 'status' => 'error', 'error_code' => ( isset( $data['error_code'] ) ) ];
		}

		if ( ! empty( $data['wy_token'] ) && ! empty( $data['mc_app_token'] ) && ! empty( $data['page_id'] ) ) {
			//This is the only place to set is_test_env=true
			ChB_Settings()->auth->setTokens( $data['wy_token'], '', $data['mc_app_token'], $data['page_id'], true );

			return [ 'status' => 'success' ];
		}

		return [ 'status' => 'error' ];
	}

	public static function disconnectTestDomain_AdminAPI() {

		ChB_Common::sendPost( ChB_Constants::DISCONNECT_TEST_DOMAIN_URL,
			[
				'wy_token' => ChB_Settings()->auth->getWYToken(),
				'domain'   => ChB_Settings()->getDomainPath()
			] );
		ChB_Settings()->auth->setTokens( '', '', '', '' );

		return [ 'status' => 'success' ];
	}

	public static function connectMCLessDomain_AdminAPI() {

		$post_body = file_get_contents( 'php://input' );
		$data      = json_decode( $post_body, true );

		if ( empty( $data['page_id'] ) ) {
			return [ 'status' => 'error' ];
		}

		$res  = ChB_Common::sendPost( ChB_Constants::CONNECT_MCLESS_DOMAIN_URL,
			[
				'wy_token'     => ChB_Settings()->auth->getWYTokenToBeVerified(),
				'mc_app_token' => '',
				'page_id'      => $data['page_id']
			] );
		$data = json_decode( $res, true );
		if ( ! ChB_Common::checkStatusIsSuccess( $data ) ) {
			ChB_Settings()->auth->requestWYToken();
		}

		return $data;
	}

	public static function disconnectMCLessDomain_AdminAPI() {

		// First, clearing fb access token and unsubscribing webhooks - BEFORE disconnecting
		if ( ChB_Settings()->auth->getFBAccessToken() ) {
			ChB_Settings()->auth->setFBAccessToken( '', '', '' );
		}

		ChB_Common::sendPost( ChB_Constants::DISCONNECT_MCLESS_DOMAIN_URL,
			[ 'wy_token' => ChB_Settings()->auth->getWYToken() ] );
		ChB_Settings()->auth->setTokens( '', '', '', '' );

		return [ 'status' => 'success' ];
	}

	public static function saveFBPage_API( array &$post_payload ) {

		$log_prefix = ChB_Common::my_rand_string( 4 ) . ' saveFBPage: ';
		if ( empty( $post_payload ) || ! isset( $post_payload['access_token'] ) || ! isset( $post_payload['fb_page_id'] ) ) {
			$error_code = 310;
			ChB_Common::my_log( $post_payload, 1, $log_prefix . 'ERROR ' . $error_code . '! No enough data. Body=' );

			return [ 'status' => 'error', 'error_code' => $error_code ];
		}

		if ( ! ChB_Settings()->auth->setFBAccessToken( $post_payload['access_token'], $post_payload['fb_page_id'], $post_payload['ig_ba_id'] ) ) {
			$error_code = 320;
			ChB_Common::my_log( $post_payload, 1, $log_prefix . 'ERROR ' . $error_code . '! setFBAccessToken failed. Body=' );

			return [ 'status' => 'error', 'error_code' => $error_code ];
		}

		if ( ChB_Settings()->auth->connectionIsDirect() ||
		     ! ChB_Settings()->getParam( 'fb_page_name' ) ||
		     ! ChB_Settings()->getParam( 'fb_page_username' ) ||
		     ! ChB_Settings()->getParam( 'ig_account_username' ) ) {

			if ( ! empty( $post_payload['fb_page_name'] ) ) {
				$options2update['fb_page_name'] = $post_payload['fb_page_name'];
			}
			if ( ! empty( $post_payload['fb_page_username'] ) ) {
				$options2update['fb_page_username'] = $post_payload['fb_page_username'];
			}

			if ( ! empty( $post_payload['ig_ba_username'] ) ) {
				$options2update['ig_account_username'] = $post_payload['ig_ba_username'];
			}

			if ( ! empty( $options2update ) ) {
				ChB_Settings()->updateSomeOptions( $options2update );
			}
		}

		return [ 'status' => 'success' ];
	}

	public static function getMCUserId_API( $post_payload ) {
		if ( ! empty( $post_payload['fb_user_id'] ) && ! empty( $post_payload['channel'] ) ) {
			$user = ChB_User::initUser( $post_payload['fb_user_id'], $post_payload['channel'] );
			if ( $user ) {
				return [ 'status' => 'success', 'mc_user_id' => $user->mc_user_id ];
			}
		}

		return [ 'status' => 'error' ];
	}

	public static function sendDomainIs_API( $post_payload ) {
		$ChB = null;
		try {
			if ( ! empty( $post_payload['fb_user_id'] ) && ! empty( $post_payload['channel'] ) ) {
				$user = ChB_User::initUser( $post_payload['fb_user_id'], $post_payload['channel'] );
				if ( $user && ( $ChB = ChatBot::openTempChatBotSession( $user ) ) ) {
					$data = [
						'version' => 'v2',
						'content' => [
							'messages' => [
								[
									'type' => 'text',
									'text' => 'Domain is ' . ChB_Settings()->getDomainPath()
								]
							]
						]
					];
					if ( ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $data ], $ChB ) ) {
						return [ 'status' => 'success' ];
					}
				}
			}

			return [ 'status' => 'error' ];
		} finally {
			if ( $ChB ) {
				ChatBot::closeTempChatBotSession();
			}
		}
	}

	public static function showAuthRecords() {
		return "CLIENT\n" .
		       'wy_t=' . ChB_Settings()->auth->getWYToken() . "\n" .
		       'wy_t_v=' . ChB_Settings()->auth->getWYTokenToBeVerified() . "\n" .
		       'mc_t=' . ChB_Settings()->auth->getMCAppToken() . "\n" .
		       'mp=' . ChB_Settings()->auth->getMCPageID() . "\n" .
		       'fp=' . ChB_Settings()->auth->getFBPageID() . "\n" .
		       'fpun=' . ChB_Settings()->getParam( 'fb_page_username' ) . "\n" .
		       'fpn=' . ChB_Settings()->getParam( 'fb_page_name' ) . "\n" .
		       'mcls' . ChB_Settings()->auth->connectionIsDirect() . "\n";
	}


	public function mercadoPagoIsConnected() {
		return ! empty( $this->tokens_options[ self::MERCADO_PAGO_ACCESS_TOKEN ] );
	}

	public function mercadoPagoIsOn() {
		return ChB_Settings()->getParam( 'use_mercado_pago' ) && $this->mercadoPagoIsConnected();
	}

	public function getMercadoPagoAccessToken() {
		return empty( $this->tokens_options[ self::MERCADO_PAGO_ACCESS_TOKEN ] ) ? null : $this->tokens_options[ self::MERCADO_PAGO_ACCESS_TOKEN ];
	}

	public function setMercadoPagoAccessToken( $token ) {
		if ( $token ) {
			$this->tokens_options[ self::MERCADO_PAGO_ACCESS_TOKEN ] = $token;
		} else {
			unset( $this->tokens_options[ self::MERCADO_PAGO_ACCESS_TOKEN ] );
		}
		$this->updateInDB();
	}

	public static function setMercadoPagoAccessToken_AdminAPI() {

		$post_body = file_get_contents( 'php://input' );
		$data      = json_decode( $post_body, true );

		if ( empty( $data['token'] ) ) {
			ChB_Settings()->auth->setMercadoPagoAccessToken( null );
		} else {
			ChB_Settings()->auth->setMercadoPagoAccessToken( $data['token'] );
		}

		if ( isset( $data['use_mercado_pago'] ) ) {
			ChB_Settings()->updateSomeOptions( [ 'use_mercado_pago' => ( empty( $data['use_mercado_pago'] ) ? '' : '1' ) ] );
		}

		return [ 'status' => 'success' ];
	}


	public function robokassaIsConnected() {
		return ! ( empty( $this->tokens_options[ self::ROBOKASSA_MERCHANT_LOGIN ] ) ||
		           empty( $this->tokens_options[ self::ROBOKASSA_PASSWORD_1 ] ) ||
		           empty( $this->tokens_options[ self::ROBOKASSA_PASSWORD_2 ] ) ||
		           empty( $this->tokens_options[ self::ROBOKASSA_TEST_PASSWORD_1 ] ) ||
		           empty( $this->tokens_options[ self::ROBOKASSA_TEST_PASSWORD_2 ] )
		);
	}

	public function robokassaIsOn() {
		return ( ChB_Settings()->getParam( 'use_robokassa' ) === ChB_PaymentRobokassa::ROBOKASSA_TEST_ENABLED ||
		         ChB_Settings()->getParam( 'use_robokassa' ) === ChB_PaymentRobokassa::ROBOKASSA_ENABLED ) &&
		       $this->robokassaIsConnected();
	}

	private function setRobokassaOptions( $options ) {
		foreach ( $options as $key => $value ) {
			if ( $value ) {
				$this->tokens_options[ $key ] = $value;
			} else {
				unset( $this->tokens_options[ $key ] );
			}
		}
		$this->updateInDB();
	}

	public static function setRobokassaOptions_AdminAPI() {

		$post_body = file_get_contents( 'php://input' );
		$data      = json_decode( $post_body, true );
		$mode      = empty( $_GET['mode'] ) ? null : sanitize_text_field( $_GET['mode'] );

		if ( $mode === 'disconnect' ) {
			ChB_Settings()->auth->setRobokassaOptions( [
				self::ROBOKASSA_MERCHANT_LOGIN  => null,
				self::ROBOKASSA_PASSWORD_1      => null,
				self::ROBOKASSA_PASSWORD_2      => null,
				self::ROBOKASSA_TEST_PASSWORD_1 => null,
				self::ROBOKASSA_TEST_PASSWORD_2 => null
			] );
		} else {
			if ( empty( $data[ ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ] ) ||
			     empty( $data[ ChB_Auth::ROBOKASSA_PASSWORD_1 ] ) ||
			     empty( $data[ ChB_Auth::ROBOKASSA_PASSWORD_2 ] ) ||
			     empty( $data[ ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 ] ) ||
			     empty( $data[ ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 ] )
			) {
				return [
					'status'    => 'error',
					'error_msg' => 'Please fill in Merchant Login and all the passwords to Connect'
				];
			}

			ChB_Settings()->auth->setRobokassaOptions( [
				self::ROBOKASSA_MERCHANT_LOGIN  => $data[ ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ],
				self::ROBOKASSA_PASSWORD_1      => $data[ ChB_Auth::ROBOKASSA_PASSWORD_1 ],
				self::ROBOKASSA_PASSWORD_2      => $data[ ChB_Auth::ROBOKASSA_PASSWORD_2 ],
				self::ROBOKASSA_TEST_PASSWORD_1 => $data[ ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 ],
				self::ROBOKASSA_TEST_PASSWORD_2 => $data[ ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 ]
			] );

		}

		return [ 'status' => 'success' ];
	}

	/**
	 * @param $setting_name - string login|pass1|pass2|is_test
	 *
	 * @return string|bool
	 */
	public function getRobokassaSetting( $setting_name ) {

		if ( $setting_name === 'login' ) {
			return empty( $this->tokens_options[ ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ] ) ? null : $this->tokens_options[ ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ];
		} else {
			$is_test = ( ChB_Settings()->getParam( 'use_robokassa' ) === ChB_PaymentRobokassa::ROBOKASSA_TEST_ENABLED );
			if ( $setting_name === 'is_test' ) {
				return $is_test;
			}
			if ( $setting_name === 'pass1' ) {
				$field = $is_test ? ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 : ChB_Auth::ROBOKASSA_PASSWORD_1;
			} elseif ( $setting_name === 'pass2' ) {
				$field = $is_test ? ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 : ChB_Auth::ROBOKASSA_PASSWORD_2;
			} else {
				return null;
			}

			return empty( $this->tokens_options[ $field ] ) ? null : $this->tokens_options[ $field ];
		}
	}

}