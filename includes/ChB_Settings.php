<?php

namespace ChatBot;

function ChB_Settings() {
	return ChB_Settings::instance();
}

require_once dirname( __FILE__ ) . '/ChB_Constants.php';
require_once dirname( __FILE__ ) . '/ChB_Common.php';
require_once dirname( __FILE__ ) . '/ChB_Auth.php';
require_once dirname( __FILE__ ) . '/ChB_KeyValueStorage.php';
require_once dirname( __FILE__ ) . '/ChB_KeyValueStorageSQL.php';
require_once dirname( __FILE__ ) . '/ChB_Updater_WanyChat.php';


class ChB_Settings {
	const REFRESH_MC_BOT_FIELDS_PERIOD = 86400;//24h
	const OPTION_MC_BOT_FIELDS = 'rrbot_mc_bot_fields';

	const REFRESH_POPULAR_PRODUCTS_PERIOD = 86400;//24h

	const SETTING_WEB_REDIRECT_NO = "10";
	const SETTING_WEB_REDIRECT_BUY = "11";
	const SETTING_WEB_REDIRECT_PLACE_ORDER = "12";

	protected static $_instance = null;
	private ?array $stash;
	private array $data;

	private ?string $_domain_path;
	public $timezone;
	private $options;
	public $init;
	public $salt;
	private $mc_bot_fields;

	public ChB_Auth $auth;
	public ?ChB_KeyValueStorage $kvs;
	private $tictoc;

	private ?ChB_User $user;
	public $lang;

	private int $ceil_digits;

	public $uploads_path;
	public $uploads_url;

	public $rrbot_uploads_path;
	public $rrbot_uploads_url;

	public $assets_path;
	public $assets_url;

	//at the moment used only in try-on
	public $temp_uploads_path;
	public $temp_uploads_url;
	public $try_on_demo_path;
	public $try_on_demo_url;

	public $domain;
	public $log_path;
	public $ref_url_imglink;
	public $ref_url_product;
	public $ref_url_product_card;
	public $ref_url_site_product;

	public $redirect_url;
	public $wany_chat_logo_url;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::$_instance->initialize();
		}

		return self::$_instance;
	}

	public const OPTION_NAME = 'rrbot_settings';
	public const SETTINGS_SCREEN_ID = 'settings_page_rrbot-settings';

	private function __construct() {
		$this->options  = get_option( self::OPTION_NAME );
		$this->data     = [];
		$this->log_path = ( empty( $this->options['log_path'] ) ? '' : $this->options['log_path'] );
		$this->timezone = wp_timezone();
		$this->auth     = new ChB_Auth();
	}

	const PARAM_TYPES = [
		'max_input_quantity'   => [ 'empty' => 10 ],
		'q_step'               => [ 'empty' => 5 ],
		'use_default_quantity' => [ 'empty' => 0 ],

		'pa_order'              => [ 'json_decode' => true ],
		'product_view_settings' => [ 'json_decode' => true ],
		'view_settings'         => [ 'json_decode' => true ],
		'order_statuses'        => [ 'json_decode' => true ],
		'roles'                 => [ 'json_decode' => true ],
		'bot_discounts'         => [ 'json_decode' => true ],
		'promo_headers'         => [ 'json_decode' => true ],
		'size_recognition'      => [ 'json_decode' => true ],
		'delivery_estimates'    => [ 'json_decode' => true ],

		'products4followup' => [ 'explode' => '.', 'empty' => [] ],

		'managers2notify'                     => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],
		'managers2notify_on_tth'              => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],
		'managers2notify_on_orders'           => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],
		'managers2notify_on_completed_orders' => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],
		'managers2email_on_ntf'               => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],
		'users_ignore_reminders'              => [ 'json_decode' => true, 'empty' => [], 'filter' => 'arrayOfStrings' ],

		'product_import_settings' => [ 'json_decode' => true ],
		'ext_bots'                => [ 'json_decode' => true ],
		'custom_hooks'            => [ 'json_decode' => true ],
		'used_languages'          => [ 'explode_trim' => ',', 'empty' => [] ],

		'abandoned_cart_delete_after_days' => [ 'filter' => 'filterAbandonedCartDeleteAfterDays' ]
	];

	public function getParam( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		if ( empty( $this->options[ $key ] ) ) {
			$res = ( isset( self::PARAM_TYPES[ $key ]['empty'] ) ? self::PARAM_TYPES[ $key ]['empty'] : null );
		} else {
			$res = $this->options[ $key ];
			if ( ! empty( self::PARAM_TYPES[ $key ]['json_decode'] ) ) {
				$res = json_decode( $res, true );
			} elseif ( ! empty( self::PARAM_TYPES[ $key ]['explode'] ) ) {
				$res = explode( self::PARAM_TYPES[ $key ]['explode'], $res );
			} elseif ( ! empty( self::PARAM_TYPES[ $key ]['explode_trim'] ) ) {
				$res = explode( self::PARAM_TYPES[ $key ]['explode_trim'], $res );
				if ( $res && is_array( $res ) ) {
					foreach ( $res as &$vv ) {
						$vv = trim( $vv );
					}
					unset( $vv );
				}
			}
		}

		if ( isset( self::PARAM_TYPES[ $key ]['filter'] ) ) {
			$res = $this->{self::PARAM_TYPES[ $key ]['filter']}( $res );
		}

		$this->data[ $key ] = $res;

		return $this->data[ $key ];
	}

	public function setParam( $key, $val, $update_option_in_db = false ) {
		$this->data[ $key ] = $val;
		if ( $update_option_in_db ) {
			ChB_Common::my_log( 'updating settings option in db ' . $key . '=' . $val );
			$this->updateSomeOptions( [ $key => $val ] );
		}
	}

	private function initialize() {

		$plugin_root_path = get_wy_plugin_dir_path();
		$plugin_root_url  = get_wy_plugin_dir_url();

		//User-specific settings
		$this->user   = null;
		$this->lang   = 'en';
		$this->tictoc = [];

		$uploads = wp_get_upload_dir();

		$this->uploads_path = $uploads['path'] . '/';
		$this->uploads_url  = $uploads['url'] . '/';

		$this->rrbot_uploads_path = $uploads['basedir'] . '/rrbot/';
		$this->rrbot_uploads_url  = $uploads['baseurl'] . '/rrbot/';

		$this->setInitOption();
		$this->salt = ( empty( $this->options['salt'] ) ? '' : $this->options['salt'] );
		$this->kvs  = ChB_KeyValueStorageSQL::connect();

		$this->ref_url_imglink      = 'https://m.me/' . $this->getParam( 'fb_page_username' ) . '?ref=imglink--';
		$this->ref_url_product      = 'https://m.me/' . $this->getParam( 'fb_page_username' ) . '?ref=pi--';
		$this->ref_url_product_card = 'https://m.me/' . $this->getParam( 'fb_page_username' ) . '?ref=pc--';
		$this->ref_url_site_product = 'https://m.me/' . $this->getParam( 'fb_page_username' ) . '?ref=siteprd--';

		$this->assets_path        = $plugin_root_path . 'assets/';
		$this->assets_url         = $plugin_root_url . 'assets/';
		$this->wany_chat_logo_url = $this->assets_url . 'img/wanychat-logo.png';

		$this->temp_uploads_path = ( empty( $this->options['temp_uploads_path'] ) ? $this->rrbot_uploads_path : $this->options['temp_uploads_path'] );
		$this->temp_uploads_url  = ( empty( $this->options['temp_uploads_url'] ) ? $this->rrbot_uploads_url : $this->options['temp_uploads_url'] );
		$this->try_on_demo_path  = ( empty( $this->options['try_on_demo_path'] ) ? $this->temp_uploads_path : $this->options['try_on_demo_path'] );
		$this->try_on_demo_url   = ( empty( $this->options['try_on_demo_url'] ) ? $this->temp_uploads_url : $this->options['try_on_demo_url'] );

		$this->redirect_url = 'https://' . $this->getDomainPath() . ChB_Constants::RRBOT_PATH . 'task=redirect';

		$this->ceil_digits = ( isset( $this->options['ceil_digits'] ) && is_numeric( $this->options['ceil_digits'] ) ? intval( $this->options['ceil_digits'] ) : - 1 );
	}

	public function useNativeAPI( ?ChatBot $ChB ) {
		return ( $this->auth->connectionIsDirect() ||
		         $this->getParam( 'force_native_api' ) ||
		         $ChB && $ChB->user->channel === ChB_Constants::CHANNEL_IG );
	}

	public function filterAbandonedCartDeleteAfterDays( $val ) {
		return ( $val = intval( $val ) ) ? $val : 365;
	}

	public function arrayOfStrings( $val ) {
		return ChB_Common::arrayOfStrings( $val );
	}

	public function getImageDetection( $key ) {
		if ( ! isset( $this->data['image_detection'] ) ) {
			$image_detection_option        = json_decode( $this->options['image_detection'], true );
			$this->data['image_detection'] = is_array( $image_detection_option ) ? $image_detection_option : null;
			if ( $this->user && $this->user->channel === ChB_Constants::CHANNEL_IG ) {
				$this->data['image_detection']['is_on'] = false;
			}
		}

		return ( isset( $this->data['image_detection'][ $key ] ) ? $this->data['image_detection'][ $key ] : null );
	}

	public function getTypography( $key ) {
		if ( ! isset( $this->data['typography'] ) ) {
			$typography_option                           = ( empty( $this->options['typography'] ) ? null : json_decode( $this->options['typography'], true ) );
			$this->data['typography']                    = [];
			$this->data['typography']['cart_font_path']  = ( empty( $typography_option['cart_font_path'] ) ? $this->assets_path . 'fonts/05929_BoxedIn.ttf' : $typography_option['cart_font_path'] );
			$this->data['typography']['cart_font_color'] = ( empty( $typography_option['cart_font_color'] ) ? 0 : $typography_option['cart_font_color'] );
			$this->data['typography']['cart_font_color'] = ChB_Image::getDecARGBColorByHexARGColor( $this->data['typography']['cart_font_color'] );

			$this->data['typography']['promo_font_path1'] = ( empty( $typography_option['promo_font_path1'] ) ? $this->assets_path . 'fonts/05929_BoxedIn.ttf' : $typography_option['promo_font_path1'] );
			$this->data['typography']['promo_font_path2'] = ( empty( $typography_option['promo_font_path2'] ) ? $this->assets_path . 'fonts/05929_BoxedIn.ttf' : $typography_option['promo_font_path2'] );
			$this->data['typography']['font4img_gen']     = ( empty( $typography_option['font4img_gen'] ) ? $this->assets_path . 'fonts/Roboto-Medium.ttf' : $typography_option['font4img_gen'] );
		}

		return ( isset( $this->data['typography'][ $key ] ) ? $this->data['typography'][ $key ] : null );
	}

	public function getTryOn( $key ) {

		if ( ! isset( $this->data['try_on_settings'] ) ) {

			$try_on_settings_option                          = empty( $this->options['try_on_settings'] ) ? null : json_decode( $this->options['try_on_settings'], true );
			$this->data['try_on_settings']                   = ( is_array( $try_on_settings_option ) ? $try_on_settings_option : [ 'is_on' => false ] );
			$this->data['try_on_settings']['is_try_on_demo'] = false;

			if ( ! $this->user ) {
				$this->data['try_on_settings']['is_on'] = false;
			} elseif ( $this->user->channel === ChB_Constants::CHANNEL_IG ) {
				$this->data['try_on_settings']['is_on'] = false;
			} else {
				//demo is for making GIF, it is a dummy with a prepared image
				$this->data['try_on_settings']['is_try_on_demo'] = ! empty( $this->data['try_on_settings']['demo_fb_user_id'] ) && ( $this->data['try_on_settings']['demo_fb_user_id'] == $this->user->fb_user_id );
				if ( $this->data['try_on_settings']['is_try_on_demo'] ) {
					$this->data['try_on_settings']['is_on'] = true;
				}

				//test is for testing try-on on a specific user
				if ( ! empty( $this->data['try_on_settings']['test_fb_user_id'] ) && ( $this->data['try_on_settings']['test_fb_user_id'] == $this->user->fb_user_id ) ) {
					$this->data['try_on_settings']['is_on'] = true;
				}
			}

			//trying to load utils only if try-on is enabled
			if ( ! empty( $this->data['try_on_settings']['is_on'] ) && ! ChB_Common::utilIsDefined() ) {
				$this->data['try_on_settings']['is_on']          = false;
				$this->data['try_on_settings']['is_try_on_demo'] = false;
			}
		}

		return ( isset( $this->data['try_on_settings'][ $key ] ) ? $this->data['try_on_settings'][ $key ] : null );
	}

	public function getCeilDigits() {
		if ( $this->ceil_digits !== - 1 ) {
			return $this->ceil_digits;
		}

		$currency = ( wooIsDefined() ? get_woocommerce_currency() : '' );
		if ( $currency === 'USD' ) {
			$this->ceil_digits = 0;
		} elseif ( $currency === 'VND' ) {
			$this->ceil_digits = 3;
		} elseif ( $currency === 'RUB' ) {
			$this->ceil_digits = 1;
		}

		if ( $this->ceil_digits !== - 1 ) {
			return $this->ceil_digits;
		}

		return 0;
	}

	public function setInitOption() {

		if ( ! empty( $this->options['init'] ) ) {
			return true;
		}

		//initial options setup
		$this->options['shop_manager_passphrase'] = ChB_Constants::KW_CONNECT_SHOP_MANAGER . ChB_Common::my_rand_string( 10 );
		$this->options['init']                    = 1;

		//setting plugin version in db
		if ( ! ChB_Updater_WanyChat::instance()->getPluginVersionFromDB() ) {
			ChB_Updater_WanyChat::instance()->updatePluginVersionInDB( ChB_Updater_WanyChat::instance()->getPluginVersionFromCode(), false );
		}

		if ( empty( $this->options['shipping_cost_code'] ) ) {
			$this->options['shipping_cost_code'] = ChB_Common::SHIPPING_FREE;
		}
		if ( empty( $this->options['delivery_estimates'] ) ) {
			$this->options['delivery_estimates'] = json_encode( [
				'days_range'    => [ 'from' => 2, 'to' => 4 ],
				'midday'        => 12,
				'sunday_plus_1' => true
			] );
		}

		if ( empty( $this->options['use_livechat'] ) ) {
			$this->options['use_livechat'] = 1;
		}
		if ( empty( $this->options['max_input_quantity'] ) ) {
			$this->options['max_input_quantity'] = 10;
		}
		if ( empty( $this->options['q_step'] ) ) {
			$this->options['q_step'] = 5;
		}
		if ( empty( $this->options['salt'] ) ) {
			$this->options['salt'] = str_replace( '.', '', $this->getDomainPath() ) . ChB_Common::my_rand_string( 8 );
		}
		if ( empty( $this->options['cats_aspect_ratio'] ) ) {
			$this->options['cats_aspect_ratio'] = 'square';
		}
		if ( empty( $this->options['pa_order'] ) ) {
			require_once dirname( __FILE__ ) . '/ChB_Catalogue.php';
			$pa_order = ChB_Catalogue::getDefaultPAs();
			if ( $pa_order ) {
				$this->options['pa_order'] = json_encode( $pa_order );
			}
		}
		if ( empty( $this->options['product_view_settings'] ) ) {
			require_once dirname( __FILE__ ) . '/ChB_Catalogue.php';
			if ( ! isset( $pa_order ) ) {
				$pa_order = ChB_Catalogue::getDefaultPAs();
			}
			if ( ! empty( $pa_order[0] ) ) {
				$product_view_settings                  = [
					'list'    => [ 'show_availability' => [ $pa_order[0] ] ],
					'element' => [ 'show_availability' => [ $pa_order[0] ] ]
				];
				$this->options['product_view_settings'] = json_encode( $product_view_settings );
			}
		}

		if ( empty( $this->options['abandoned_cart_overlay_widget_name'] ) ) {
			$this->options['abandoned_cart_overlay_widget_name'] = 'WY Opt-In Widget';
		}

		if ( empty( $this->options['abandoned_cart_delete_after_days'] ) ) {
			$this->options['abandoned_cart_delete_after_days'] = 30;
		}

		$this->options['addr_use_name']  = 1;
		$this->options['addr_use_city']  = 1;
		$this->options['addr_use_phone'] = 1;

		//creating table for key-value storage
		$first_time_kvs = ChB_KeyValueStorageSQL::connectFirstTime();
		if ( $first_time_kvs ) {
			$first_time_kvs->close();

			wp_mkdir_p( $this->rrbot_uploads_path );

			update_option( ChB_Settings::OPTION_NAME, $this->options );

			return true;
		}


		return false;
	}

	public function getDomainPath() {
		if ( ! isset( $this->_domain_path ) ) {
			$url_parts          = parse_url( site_url() );
			$this->_domain_path = $url_parts['host'];
			if ( ! empty( $url_parts['path'] ) ) {
				$this->_domain_path .= $url_parts['path'];
			}
			$this->_domain_path = rtrim( $this->_domain_path, '/' );
		}

		return $this->_domain_path;
	}

	/**
	 * @param $order \WC_Order|string
	 */
	public function setUserByOrder( $order ) {
		$wc_order = is_string( $order ) ? wc_get_order( $order ) : $order;
		if ( $wc_order instanceof \WC_Order ) {
			if ( $wp_user_id = $wc_order->get_customer_id() ) {
				$user = ChB_User::initUserByWPUserID( $wp_user_id );
				if ( $user instanceof ChB_User ) {
					$this->setUserSettings( $user );
				}
			}
		}
	}

	public function setUserSettings( ChB_User $user ) {
		$this->user = $user;
		$this->lang = $user->getLang();
		$this->data = [];
	}

	public function stashUserSettings() {
		$this->stash = [
			'user' => $this->user,
			'lang' => $this->lang
		];
	}

	public function unstashUserSettings() {
		if ( isset( $this->stash['user'] ) ) {
			$this->user = $this->stash['user'];
		}
		if ( isset( $this->stash['lang'] ) ) {
			$this->lang = $this->stash['lang'];
		}
	}

	public static function getNotificationsLists( $subscriber_id ) {
		return
			[
				'managers2notify_on_tth' =>
					[
						'desc'             => 'Notifications on "Talk to human" button pushed',
						'subscriber_is_in' => in_array( $subscriber_id, ChB_Settings()->getParam( 'managers2notify_on_tth' ) )
					],

				'managers2notify' =>
					[
						'desc'             => 'Notifications on messages from customers',
						'subscriber_is_in' => in_array( $subscriber_id, ChB_Settings()->getParam( 'managers2notify' ) )
					],

				'managers2notify_on_orders' =>
					[
						'desc'             => 'Notifications on changes of orders statuses',
						'subscriber_is_in' => in_array( $subscriber_id, ChB_Settings()->getParam( 'managers2notify_on_orders' ) )
					],

				'managers2notify_on_completed_orders' =>
					[
						'desc'             => 'Notifications on completed orders',
						'subscriber_is_in' => in_array( $subscriber_id, ChB_Settings()->getParam( 'managers2notify_on_completed_orders' ) )
					]
			];
	}

	public function changeNotificationsList( $list_id, $subscriber_id, $add ) {
		if ( $list_id === 'managers2notify_on_tth' ) {
			$list = $this->getParam( 'managers2notify_on_tth' );
		} elseif ( $list_id === 'managers2notify' ) {
			$list = $this->getParam( 'managers2notify' );
		} elseif ( $list_id === 'managers2notify_on_orders' ) {
			$list = $this->getParam( 'managers2notify_on_orders' );
		} elseif ( $list_id === 'managers2notify_on_completed_orders' ) {
			$list = $this->getParam( 'managers2notify_on_completed_orders' );
		} elseif ( $list_id === 'users_ignore_reminders' ) {
			$list = $this->getParam( 'users_ignore_reminders' );
		}

		if ( ! isset( $list ) ) {
			return false;
		}

		$changed = false;
		$ind     = array_search( $subscriber_id, $list );
		if ( $add && $ind === false ) {
			$list[]  = $subscriber_id;
			$changed = true;
		} elseif ( ! $add && $ind !== false ) {
			unset( $list[ $ind ] );
			$changed = true;
		}

		if ( $changed ) {
			$this->updateSomeOptions( [ $list_id => $list ] );
		}

		return true;
	}

	public function updateRoles( $roles ) {
		$this->updateSomeOptions( [ 'roles' => $roles ] );
	}

	public function isWebRedirectOnBUY() {
		return $this->getParam( 'web_redirect' ) == ChB_Settings::SETTING_WEB_REDIRECT_BUY;
	}

	public function setWebRedirectOnBUY() {
		$this->setParam( 'web_redirect', ChB_Settings::SETTING_WEB_REDIRECT_BUY );
	}

	public function updateSomeOptions( $options2update ) {
		if ( $options2update ) {
			foreach ( $options2update as $key => $val ) {
				$val_str               = $val ? ( is_string( $val ) ? $val : json_encode( $val ) ) : '';
				$this->options[ $key ] = $val_str;
				if ( property_exists( $this, $key ) ) {
					$this->{$key} = $val;
				}
			}
		}
		update_option( ChB_Settings::OPTION_NAME, $this->options );
		$this->data = [];
	}

	public function getFields4SettingsForm() {

		$hide_ext          = ! defined( 'RRB_EXT' );
		$wy_token_is_empty = ! $this->auth->getWYToken();
		$fb_token_is_empty = ! $this->auth->getFBAccessToken();

		$sections = [
			'wy_token' => [
				'hidden'   => false,
				'title'    => '',
				'callback' => 'rrbot_settings_section_wy_token_callback',
				'fields'   => []
			],

			'facebook'       => [
				'hidden'   => false,
				'title'    => '-- 🔵 FACEBOOK --',
				'callback' => '',
				'fields'   => [ 'fb_page_name', 'fb_page_username', 'ig_account_username' ]
			],
			'manychat'       => [
				'hidden'   => true,
				'title'    => '-- MANYCHAT --',
				'callback' => '',
				'fields'   => [
					'flow_catalog',
					'flow_ig_catalog',
					'flow_try_on_demo',
					'flow_ig_try_on_demo',
					'flow_ig_connect',
					'flow_open_product_by_ref_FBSHOP',
					'flow_df_response'
				]
			],
			'wanychat'       => [
				'hidden'   => false,
				'title'    => '-- 🟣 WANY.CHAT --',
				'callback' => 'rrbot_settings_section_wanychat_fb_app_callback',
				'fields'   => []
			],
			'roles'          => [
				'hidden'   => false,
				'title'    => '-- 👩‍💼 ROLES AND NOTIFICATIONS --',
				'callback' => '',
				'fields'   => [
					'shop_manager_passphrase',
					'roles',
					'notify_via_mc_flow',
					'use_livechat',
					'managers2notify_on_tth',
					'managers2notify',
					'managers2notify_on_orders',
					'managers2notify_on_completed_orders',
					'managers2email_on_ntf',
				]
			],
			'web_checkout'   => [
				'hidden'   => false,
				'title'    => '-- 🌍 REDIRECT TO WEBSITE --',
				'callback' => '',
				'fields'   => [
					'web_redirect'
				]
			],
			'abandoned_cart' => [
				'hidden'   => false,
				'title'    => '-- 🎯 REMARKETING AND 🛒 ABANDONED CART --',
				'callback' => '',
				'fields'   => [
					'use_woo_views_remarketing',
					'use_abandoned_cart',
					'abandoned_cart_widget_id',
					'abandoned_cart_overlay_widget_name',
					'abandoned_cart_delete_after_days',
					'use_pc_links_in_fb_catalog',
					'do_not_send_related_products',
					'do_not_send_recommended_products',
					'users_ignore_reminders'
				]
			],

			'general' => [
				'hidden'   => false,
				'title'    => '-- 🎚 GENERAL --',
				'callback' => '',
				'fields'   => [
					'ceil_digits',
					'order_statuses',
					'log_path',
					ChB_Debug::SUPER_DEBUG_SETTINGS_PAR
				]
			],

			'product_view' => [
				'hidden'   => false,
				'title'    => '-- 🎨 APPEARANCE SETTINGS --',
				'callback' => '',
				'fields'   => [
					'parent_cat',
					'cats_aspect_ratio',
					'pa_order',
					'pa_on_buy_button',
					'use_default_quantity',
					'max_input_quantity',
					'q_step',
					'product_view_settings',
				]
			],

			'shipping_contact_info' => [
				'hidden'   => false,
				'title'    => '-- 🏘 SHIPPING CONTACT INFO --',
				'callback' => '',
				'fields'   => [
					'addr_use_name',
					'addr_use_phone',
					'addr_use_email',
					'addr_use_country',
					'addr_use_state',
					'addr_use_city',
					'addr_use_postcode',
					'addr_use_address_1',
					'additional_fields_settings'
				]
			],

			'shipping' => [
				'hidden'   => false,
				'title'    => '-- 🚚 SHIPPING --',
				'callback' => '',
				'fields'   => [
					'shipping_cost_code',
					'shipping_cost',
					'shipping_cost_text',
					'delivery_estimates',
				]
			],

			'payment' => [
				'hidden'   => false,
				'title'    => '-- 💵 PAYMENTS --',
				'callback' => '',
				'fields'   => [
					'use_cod',
					'use_mercado_pago',
					'use_robokassa',
				]
			],

			'lang' => [
				'hidden'   => false,
				'title'    => '-- 🇬🇧 LANGUAGES --',
				'callback' => '',
				'fields'   => [
					'used_languages',
					'default_force_lang'
				]
			],

			'list_view' => [
				'hidden'   => $hide_ext,
				'title'    => '-- 📢 MARKETING SETTINGS --',
				'callback' => '',
				'fields'   => [ 'set_has_opt_in_sms', 'opt_in_sms_consent_phrase' ]
			],

			'promo' => [
				'hidden'   => $hide_ext,
				'title'    => '-- ‼️ PROMO --',
				'callback' => '',
				'fields'   => [
					'bot_discounts',
					'promo_headers',
				]
			],

			'lab'    => [
				'hidden'   => $hide_ext,
				'title'    => '-- 🧪 LAB --',
				'callback' => '',
				'fields'   => [
					'filter_by_gender_in_related',
					'fb_marketing_api_token',
					'pixel_id',
					'ext_bots',
					'image_detection',
					'size_recognition',
					'try_on_settings',
					'custom_hooks',
					'manychat_receiving_app_id'
				]
			],
			'pro'    => [
				'hidden'   => $hide_ext,
				'title'    => '-- PRO --',
				'callback' => '',
				'fields'   => []
			],
			'hidden' => [
				'hidden'   => $hide_ext,
				'title'    => '',
				'callback' => '',
				'fields'   => [
					'force_native_api',
					'init',
					'salt',
					'view_settings',
					'typography',
					'temp_uploads_path',
					'temp_uploads_url',
					'try_on_demo_path',
					'try_on_demo_url',
					'videos_path',
					'videos_url',
					'videos_prefix',
					'video_log_file',
					'product_import_settings',
					'products4followup',
					'popular_products',
					'multi_brand',
					'brands_names_list',
				]
			]
		];

		$fields_render = [
			'fb_page_name'                        => 'render_field_readonly',
			'fb_page_username'                    => 'render_field_readonly',
			'ig_account_username'                 => 'render_field_readonly',
			'shop_manager_passphrase'             => 'render_field_readonly',
			'use_livechat'                        => 'render_field_checkbox',
			'pa_order'                            => 'render_field_json',
			'product_view_settings'               => 'render_field_json',
			'roles'                               => 'render_field_json',
			'managers2notify_on_tth'              => 'render_field_userlist',
			'managers2notify'                     => 'render_field_userlist',
			'managers2notify_on_orders'           => 'render_field_userlist',
			'managers2notify_on_completed_orders' => 'render_field_userlist',
			'managers2email_on_ntf'               => 'render_field_json',
			'users_ignore_reminders'              => 'render_field_json',
			'shipping_cost_code'                  => 'render_field_shipping_code',
			'shipping_cost_text'                  => 'render_field_textarea',
			'force_native_api'                    => 'render_field_checkbox',
			'init'                                => 'render_field_checkbox',
			'web_redirect'                        => 'render_field_web_redirect',
			'use_woo_views_remarketing'           => 'render_field_checkbox',
			'use_abandoned_cart'                  => 'render_field_checkbox',
			'addr_use_name'                       => 'render_field_checkbox',
			'addr_use_phone'                      => 'render_field_checkbox',
			'addr_use_email'                      => 'render_field_checkbox',
			'addr_use_country'                    => 'render_field_checkbox',
			'addr_use_state'                      => 'render_field_checkbox',
			'addr_use_city'                       => 'render_field_checkbox',
			'addr_use_postcode'                   => 'render_field_checkbox',
			'addr_use_address_1'                  => 'render_field_checkbox',
			'use_cod'                             => 'render_field_checkbox',
			'use_mercado_pago'                    => 'render_field_use_mercado_pago',
			'use_robokassa'                       => 'render_field_use_robokassa',
			'use_pc_links_in_fb_catalog'          => 'render_field_checkbox',
			'do_not_send_related_products'        => 'render_field_checkbox',
			'do_not_send_recommended_products'    => 'render_field_checkbox',
			'set_has_opt_in_sms'                  =>
				[
					'type'    => 'render_field_checkbox',
					'related' => [ 'opt_in_sms_consent_phrase' ]
				],
		];

		$hidden_fields = [
			'flow_catalog',
			'flow_ig_catalog',
			'flow_try_on_demo',
			'flow_ig_try_on_demo',
			'flow_ig_connect',
			'flow_open_product_by_ref_FBSHOP',
			'flow_df_response',
			'shipping_cost',
			'shipping_cost_text',
			'init',
			'products4followup',
			'popular_products',
			'abandoned_cart_widget_id',
			'addr_use_country',
			ChB_Debug::SUPER_DEBUG_SETTINGS_PAR
		];

		if ( ! $this->auth->connectionIsDirect() ) {
			$hidden_fields[] = 'parent_cat';
		}

		if ( ! $this->auth->connectionIsMC() ) {
			$hidden_fields[] = 'set_has_opt_in_sms';
			$hidden_fields[] = 'opt_in_sms_consent_phrase';
		}

		if ( $hide_ext ) {
			$hidden_fields[] = 'managers2email_on_ntf';
		}

		$submit_button_after = '';

		if ( $wy_token_is_empty || $fb_token_is_empty ) {
			foreach ( $sections as $section_name => &$section ) {
				$section['hidden'] = true;
			}

			$sections['wy_token']['hidden'] = false;
			if ( ! $wy_token_is_empty ) {
				$sections['facebook']['hidden'] = false;
				$sections['wanychat']['hidden'] = false;
			}
		} else {
			$submit_button_after = 'LAST';
		}

		//fields titles
		$fields_titles = [
			'fb_page_name'                        => 'FB page name',
			'fb_page_username'                    => 'FB page username',
			'ig_account_username'                 => 'IG account username',
			'managers2notify_on_tth'              => 'Managers to notify on "Talk to human" button',
			'managers2notify'                     => 'Managers to notify on text messages from customers',
			'managers2notify_on_orders'           => 'Managers to notify on orders',
			'managers2notify_on_completed_orders' => 'Managers to notify on completed orders',
			'users_ignore_reminders'              => 'Do not send reminders to these users',
			'use_livechat'                        => 'Show "Talk To Human" button',
			'notify_via_mc_flow'                  => 'Notify managers using this ManyChat flow',
			'ceil_digits'                         => 'Number of digits to round up',
			'log_path'                            => 'Path to bot log file',
			'parent_cat'                          => 'Parent category for catalog',
			'cats_aspect_ratio'                   => 'Product categories cards aspect ration',
			'pa_order'                            => 'Add-To-Cart attributes order',
			'pa_on_buy_button'                    => 'Product attribute on "BUY" button',
			'shipping_cost_code'                  => 'Shipping method',
			'shipping_cost_text'                  => 'Shipping terms',
			'default_force_lang'                  => 'Default language',
			'use_default_quantity'                => 'Default quantity',
			'q_step'                              => 'Max input quantity on one screen',
			'set_has_opt_in_sms'                  => 'Set "Opted-in for SMS" for shipping phone',
			'opt_in_sms_consent_phrase'           => '"Opted-in for SMS" consent phrase',
			'use_woo_views_remarketing'           => 'Use messenger remarketing',
			'use_abandoned_cart'                  => 'Use abandoned cart reminders',
			'abandoned_cart_overlay_widget_name'  => 'Opt-In Widget Name',
			'abandoned_cart_widget_id'            => 'Abandoned cart Widget ID',
			'abandoned_cart_delete_after_days'    => 'Delete remarketing data after days',
			'use_pc_links_in_fb_catalog'          => 'Use links to Messenger product cards in FB catalog',
			'addr_use_name'                       => 'First and Last Names',
			'addr_use_phone'                      => 'Phone',
			'addr_use_email'                      => 'Email',
			'addr_use_country'                    => 'Country',
			'addr_use_state'                      => 'State ',
			'addr_use_city'                       => 'City',
			'addr_use_postcode'                   => 'Postcode',
			'addr_use_address_1'                  => 'Address line 1',
			'additional_fields_settings'          => 'Additional checkout fields and settings',
			'use_cod'                             => 'Use cash on delivery - COD',
			'use_mercado_pago'                    => 'Use Mercado Pago (beta)',
			'use_robokassa'                       => 'Use Robokassa (beta)',
		];

		$bot = '<a target="_blank" href="' . esc_url( 'https://m.me/' . ChB_Settings()->getParam( 'fb_page_username' ) ) . '">' . esc_attr( ChB_Settings()->getParam( 'fb_page_username' ) ) . '</a>';

		//
		$fields_descriptions['shop_manager_passphrase'] = 'To connect yourself as a <code>SHOP MANAGER</code> send this passphrase to ' . $bot . ' and refresh this page';
		$fields_help_links['shop_manager_passphrase']   = self::getHelpLink( 'shop_manager_passphrase' );

		$text = 'To subscribe or unsubscribe yourself send key phrase <code>ntf menu</code> to ' . $bot . ' and refresh this page';

		//
		$fields_help_links['use_livechat'] = self::getHelpLink( 'use_livechat' );

		$fields_descriptions['notify_via_mc_flow'] =

			'Wany.Chat sends notifications via <code>Facebook</code> and <code>Instagram</code>, using it\'s own templates.<br>' .
			'If you want to use your custom template or send via <code>Telegram</code> - please insert <code>ID</code> of ManyChat flow here.' .
			'<br>This flow will be launched to notify users on events listed below (button pushed, text messages from customers, orders statuses changes, etc).<br>' .
			'Custom field <code>RRB_Par1</code> will be used to pass additional info into this flow';


		$fields_descriptions['managers2notify_on_tth'] = 'Receive notifications on <code>Talk to human</code> button pushed<br>' . $text;


		$fields_descriptions['managers2notify'] = 'Receive notifications when customers send free-text messages to bot<br>' . $text;
		$fields_help_links['managers2notify']   = self::getHelpLink( 'managers2notify' );

		//
		$fields_descriptions['managers2notify_on_orders'] = 'Receive notifications on changes of orders statuses <br>' . $text;
		$fields_help_links['managers2notify_on_orders']   = self::getHelpLink( 'managers2notify_on_orders' );

		//
		$fields_descriptions['managers2notify_on_completed_orders'] = $text;

		//

		$fields_descriptions['use_woo_views_remarketing']          = 'Send reminders via FB Messenger and IG Direct of <code>products viewed</code> on your website';
		$fields_descriptions['use_abandoned_cart']                 = 'Send reminders via FB Messenger and IG Direct of <code>abandoned cart</code> on your website';
		$fields_descriptions['abandoned_cart_overlay_widget_name'] = 'Change this if you\'ve changed default widget name or created a new one instead';
		$fields_descriptions['abandoned_cart_delete_after_days']   = 'How many days should be remarketing data kept alive (product views, abandoned cart, etc.)';
		$fields_descriptions['use_pc_links_in_fb_catalog']         = 'Use links to Messenger product cards in catalog generated by <code>Facebook for WooCommerce</code> plugin';

		$fields_descriptions['do_not_send_related_products']     = '<code>Related products</code> are sent <code>1 minute</code> after customer opens any product and makes no further action';
		$fields_descriptions['do_not_send_recommended_products'] = '<code>Recommended products</code> are sent <code>22 hours 45 minutes</code> after customer\'s last interaction with bot';

		//
		$fields_descriptions['users_ignore_reminders'] = 'Add user id to this list for him/her to stop receiving marketing activity from bot (e.g. related products, reminders, etc) <br>' .
		                                                 '* Example: <code>["1231231231231", "12312312312567"]</code>';

		$fields_help_links['users_ignore_reminders'] = self::getHelpLink( 'fb_user_id' );


		//
		$fields_descriptions['ceil_digits'] = 'Example: 3 digits round 12499 up to 13000. For USD default is 0';

		//
		$fields_descriptions['order_statuses'] = '<code>init_status</code> - status with which bot creates a new order<br>' .
		                                         '* <code>to-ship</code> - status after Shop Manager confirms an order in bot<br>' .
		                                         '* <code>early-cancel</code> - status after Shop Manager cancels an order in bot, but before marking it as shipped<br>' .
		                                         '* <code>shipped</code> - status after Shop Manager marks an order in bot as shipped<br>' .
		                                         'Default value is <code>{"init_status" : "pending", "early-cancelled" : "", "to-ship" : "processing", "shipped" : ""}</code><br>' .
		                                         'Custom order statuses can be added using third-party plugins';
		$fields_help_links['order_statuses']   = self::getHelpLink( 'order_statuses' );

		//
		$fields_descriptions['log_path'] = 'Specify to see bot\'s logs';

		//
		$fields_descriptions['cats_aspect_ratio'] = '<code>horizontal</code> or <code>square</code>';

		//
		$fields_descriptions['pa_order'] = 'Example: <code>["pa_size", "pa_color"]</code> means bot will first ask to choose <code>size</code> and then <code>color</code>';
		$fields_help_links['pa_order']   = self::getHelpLink( 'pa_order' );

		//
		$fields_descriptions['pa_on_buy_button'] = 'If current product variations have only one attribute - you can put it directly on "BUY" button <br>' .
		                                           'Example: <code>pa_size</code>';
		$fields_help_links['pa_on_buy_button']   = self::getHelpLink( 'pa_on_buy_button' );

		//
		$fields_descriptions['use_default_quantity'] = 'If set, bot would not prompt for quantity during add to cart process. User can change quantity later';

		$fields_descriptions['product_view_settings'] =
			'Example: <br><code>{"list"&nbsp;&nbsp;&nbsp;&nbsp;: {"show_availability" : ["pa_brand", "pa_size"], "open_buy" : 1},<br>&nbsp;&nbsp;"element" : {"show_availability" : ["pa_brand", "pa_size", "pa_color"]}}</code><br>' .
			'to show <code>brand</code> and <code>size</code> attributes and ' .
			'<code>Open</code> and <code>Buy</code> buttons in product list<br>' .
			'to show <code>brand</code>, <code>size</code> and <code>color</code> attributes in product card<br><br>';


		$fields_help_links['product_view_settings'] = self::getHelpLink( 'product_view_settings' );

		$fields_descriptions['additional_fields_settings'] = 'Configure existing or add new fields on checkout';
		$fields_help_links['additional_fields_settings']   = self::getHelpLink( 'additional_fields_settings' );

		$fields_descriptions['shipping_cost_code'] =
			'<span class="rrb-field-shipping_code_free rrb-hidden">Shipping is free for all orders created via bot</span>' .
			'<span class="rrb-field-shipping_code_flat rrb-hidden">Shipping cost is the same for all orders created via bot</span>' .
			'<span class="rrb-field-shipping_code_manual rrb-hidden">Shipping cost is set later manually</span>' .
			'<span class="rrb-field-shipping_code_woo rrb-hidden">On checkout, bot will display list of shipping methods from WooCommerce settings to choose from</span>';

		$fields_descriptions['shipping_cost'] =
			'<span class="rrb-field-shipping_code_flat rrb-hidden">Applied to all orders created via bot</span>' .
			'<span class="rrb-field-shipping_code_manual rrb-hidden">If set, applied to all orders created via bot. Starting price for shipping</span>';

		$fields_descriptions['shipping_cost_text'] = 'If set, added as a description in the Cart';

		$fields_descriptions['delivery_estimates'] =
			'Used in order follow up message to customer. Sent when manager has confirmed the order<br>' .
			'Example: <code>{"days_range" : {"from": 2, "to" : 4}, "midday" : 12, "sunday_plus_1" : true}</code><br>' .
			'Means delivery is from <code>2</code> to <code>4</code> days. Days starts at 12:01 pm. Sundays are ignored';

		$fields_descriptions['set_has_opt_in_sms'] =
			'Set this only if you confirm that you have obtained appropriate consent from user beforehand';

		$fields_descriptions['opt_in_sms_consent_phrase'] = 'Consent phrase. REQUIRED by ManyChat if "Opted-in for SMS" is checked';

		$fields_descriptions['used_languages']     = 'List of languages used in bot, separated by comma. ' .
		                                             'Default value is <code>en, es, ru</code>.' .
		                                             '<br>Available languages:<br><br>' . ChB_CustomLang::getLangListForSettingsPage() .
		                                             '<br><b>IMPORTANT:</b> click on the language from the list above to customize translation for your bot';
		$fields_descriptions['default_force_lang'] = 'Bot will use this language if user hasn\'t explicitly chosen another one';

		return [
			'sections'            => $sections,
			'fields_render'       => $fields_render,
			'hidden_fields'       => $hidden_fields,
			'fields_titles'       => $fields_titles,
			'fields_descriptions' => $fields_descriptions,
			'fields_help_links'   => $fields_help_links,
			'submit_button_after' => $submit_button_after
		];
	}

	public static function getHelpLink( $setting_name ) {
		return '<a target="_blank" href="' . esc_url( ChB_Constants::WANY_SITE_URL . '/wany-chat-customization#' . $setting_name ) . '">🔎 See details</a>';
	}

	public static function getHowToInstallLink() {
		return '<a target="_blank" href="' . esc_url( ChB_Constants::WANY_SITE_URL . '/install-wany-chat/' ) . '">🔎 See details</a>';
	}

	public function reloadMCData() {
		//setting bot fields in MC -- not used atm
//		if ( $this->auth->getMCAppToken() ) {
//			ChB_ManyChat::initManyChatBotFields();
//		}

		//getting values for options from MC
		$options2update = ChB_ManyChat::getManyChatData();
		ChB_Common::my_log( $options2update, true, 'updating mc options' );
		$this->updateSomeOptions( $options2update );
	}

	public function getPopularProducts( $allow_recalc ) {
		if ( empty( $this->getParam( 'popular_products' )['timestamp'] ) ||
		     ( $allow_recalc && ( time() - $this->getParam( 'popular_products' )['timestamp'] > ChB_Settings::REFRESH_POPULAR_PRODUCTS_PERIOD ) ) ) {
			$this->refreshPopularProducts();
		}

		return isset( $this->getParam( 'popular_products' )['prods'] ) ? $this->getParam( 'popular_products' )['prods'] : [];
	}

	private function refreshPopularProducts() {
		$options2update = [
			'popular_products' => [
				'prods'     => ChB_Catalogue::calcPopularProducts(),
				'timestamp' => time()
			]
		];
		$this->updateSomeOptions( $options2update );
	}

	public function getMCBotFields( $field_name, ChatBot $ChB4ScheduledRefresh = null ) {
		ChB_Common::my_log( 'getMCBotFields' );

		if ( empty( $this->mc_bot_fields ) ) {
			$option = get_option( self::OPTION_MC_BOT_FIELDS );
			if ( empty( $option ) ) {
				$option = self::refreshMCBotFields();
			} elseif ( time() - $option['timestamp'] > self::REFRESH_MC_BOT_FIELDS_PERIOD ) {
				if ( ! empty( $ChB4ScheduledRefresh ) ) {
					ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_REFRESH_MC_BOT_FIELDS, [], 0 );
				} else {
					$option = self::refreshMCBotFields();
				}
			}

			$this->mc_bot_fields = $option['data'];
		}

		if ( ! empty( $this->mc_bot_fields ) ) {
			foreach ( $this->mc_bot_fields as $field ) {
				if ( $field['name'] == $field_name ) {
					return $field['value'];
				}
			}
		}

		return null;
	}

	public static function refreshMCBotFields() {
		ChB_Common::my_log( 'refreshMCBotFields' );

		$res  = ChB_ManyChat::sendGet2ManyChat( 'https://api.manychat.com/fb/page/getBotFields' );
		$info = json_decode( $res, true );
		if ( $info['status'] !== 'success' ) {
			return false;
		}
		$info['timestamp'] = time();

		update_option( self::OPTION_MC_BOT_FIELDS, $info );

		return $info;
	}

	public function tic( $key ) {
		if ( empty( $this->tictoc[ $key ] ) ) {
			$this->tictoc[ $key ] = [ 0, 0, 0, 0 ];
		}

		if ( $this->tictoc[ $key ][1] !== 0 ) {
			$this->tictoc[ $key ][2] = 1;
		}

		$this->tictoc[ $key ][1] = hrtime( true );
		$this->tictoc[ $key ][3] ++;
	}

	public function toc( $key ) {
		if ( empty( $this->tictoc[ $key ] ) ) {
			$this->tictoc[ $key ] = [ 0, 0, 1, 0 ];

			return;
		}

		if ( ! empty( $this->tictoc[ $key ][1] ) ) {
			$this->tictoc[ $key ][0] += ( hrtime( true ) - $this->tictoc[ $key ][1] );
			$this->tictoc[ $key ][1] = 0;
		} else {
			$this->tictoc[ $key ][2] = 1;
		}
	}

	public function printTicToc( $prefix ) {
		foreach ( $this->tictoc as $key => $tt ) {
			$res = 'tictoc - ' . $prefix . ' - ' . $key . ': ' . ( $tt[0] / 1e+6 ) . ' | ' . ( $tt[1] / 1e+6 ) . ' | ' . $tt[2] . ' | ' . $tt[3];
			ChB_Common::my_log( $res );
		}
	}
}