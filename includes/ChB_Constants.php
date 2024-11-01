<?php


namespace ChatBot;


class ChB_Constants {
	public const CHANNEL_IG = 'ig';
	public const CHANNEL_FB = 'fb';

	public const FB_API_VERSION = 'v17.0';
	public const FB_INBOX_APP_ID = '263902037430900';
	public const FB_MESSAGE_TAG_POST_PURCHASE_UPDATE = 'POST_PURCHASE_UPDATE';

	public const APP_CALL_MC = 'mc';
	public const APP_CALL_WY = 'wy';

	public const BASE64_MARKER = 'WY64::';
	public const BASE64_MARKER_LEN = 6;

	public const BF_EmptyValue = 'RRB_EMPTY_VALUE';

	public const CF_Events = 'RRB_Events';

	public const PROD_ATTR_HAS_TRY_ON = 'prod_has_try_on';

	public const RRBOT_PATH = '/wp-json/wany-chat/v1/q?';
	public const RRBOT_API_PATH = '/wp-json/wany-chat/v1/api?';
	public const RRBOT_ADMIN_API_PATH = '/wp-json/wany-chat/v1/aapi?';
	public const WANY = 'bot.wany.chat';
	public const WANY_URL = 'https://' . self::WANY;
	public const WANY_SITE = 'wany.chat';
	public const WANY_SITE_URL = 'https://' . self::WANY_SITE;

	public const JQUERY_UI_URL = 'https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css';

	public const REQUEST_WY_TOKEN_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=request_wy_token';
	public const CHECK_WY_TOKEN2VERIFY_IS_ALIVE_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=check_wy_token2verify_is_alive';

	public const FACEBOOK_LOGIN_PAGE_TASK = 'facebook_login';
	public const FACEBOOK_LOGIN_PAGE_URL = self::WANY_URL . '/wp-content/plugins/wany-chat-util/?task=' . self::FACEBOOK_LOGIN_PAGE_TASK;

	public const FACEBOOK_SELECT_PAGE_TASK = 'facebook_select_page';
	public const FACEBOOK_SELECT_PAGE_URL = self::WANY_URL . '/wp-content/plugins/wany-chat-util/?task=' . self::FACEBOOK_SELECT_PAGE_TASK;

	public const EXCHANGE_FB_TOKEN_AND_SUBSCRIBE_WEBHOOK_TASK = 'fb_exchange_token_subscribe_webhook';
	public const EXCHANGE_FB_TOKEN_AND_SUBSCRIBE_WEBHOOK_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=' . self::EXCHANGE_FB_TOKEN_AND_SUBSCRIBE_WEBHOOK_TASK;

	public const UNSUBSCRIBE_WEBHOOK_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=unsubscribe_webhook';
	public const CONNECT_TEST_DOMAIN_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=connect_test_domain';
	public const DISCONNECT_TEST_DOMAIN_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=disconnect_test_domain';
	public const CONNECT_MCLESS_DOMAIN_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=connect_mcless_domain';
	public const DISCONNECT_MCLESS_DOMAIN_URL = self::WANY_URL . '/wp-json/wany-chat-util/v1/api?task=disconnect_mcless_domain';

	public const RRBOT_VERIFY_WY_TOKEN_URL = self::RRBOT_API_PATH . 'task=verify_wy_token';
	public const RRBOT_SET_TOKENS_URL = self::RRBOT_API_PATH . 'task=set_tokens';

	public const RRBOT_ADMIN_TASK_GET_TOKENS_FORM = 'get_tokens_form';
	public const RRBOT_ADMIN_TASK_REGEN_TOKENS = 'regen_tokens';
	public const RRBOT_ADMIN_TASK_CLEAR_FB_PAGE = 'clear_fb_page';
	public const RRBOT_ADMIN_TASK_CONNECT_TEST_DOMAIN = 'connect_test_domain';
	public const RRBOT_ADMIN_TASK_DISCONNECT_TEST_DOMAIN = 'disconnect_test_domain';
	public const RRBOT_ADMIN_TASK_CONNECT_MCLESS_DOMAIN = 'connect_mcless_domain';
	public const RRBOT_ADMIN_TASK_DISCONNECT_MCLESS_DOMAIN = 'disconnect_mcless_domain';
	public const RRBOT_ADMIN_TASK_SET_MERCADO_PAGO_TOKEN = 'set_mercado_pago_token';
	public const RRBOT_ADMIN_TASK_SET_ROBOKASSA_OPTIONS = 'set_robokassa_options';

	public const SAVE_FB_PAGE_URL = self::RRBOT_API_PATH . 'task=save_fb_page';
	public const WEBHOOK_URL_FB = self::RRBOT_API_PATH . 'task=messenger_webhook';
	public const WEBHOOK_URL_IG = self::RRBOT_API_PATH . 'task=instagram_webhook';
	public const GET_MC_USER_ID_URL = self::RRBOT_API_PATH . 'task=get_mc_user_id';
	public const SEND_DOMAIN_IS_URL = self::RRBOT_API_PATH . 'task=send_domain_is';

	public const SET_MC_USER_IN_WOO_SESSION_TASK = 'set_mc_user_woo_session';
	public const SET_MC_USER_IN_WOO_SESSION_URL = self::RRBOT_API_PATH . 'task=' . self::SET_MC_USER_IN_WOO_SESSION_TASK;

	public const KW_CONNECT_SHOP_MANAGER = 'connect_shop_manager_';
	public const KW_SHOP = 'shop';
	public const KW_NTF_MENU = 'ntf menu';
	public const KW_NTF = 'ntf';
	public const KW_MNG = 'mng';
	public const KW_ORDER = 'ordr';
	public const KW_ORDERS = 'orders';
	public const KW_LANG = 'lang';
	public const KW_CART = 'cart';
	public const KW_HELP = 'help';
	public const KW_TEST = 'tttest';
	public const KW_AC_TEST = 'actest';
	public const KW_RMKT_TEST = 'rmktest';
	public const KW_DBG = 'dbgpar';
	public const KW_DBG_LEN = 6;

	public const COLOR_WANY_TEXT = '#d534eb';
	public const COLOR_WANY_BG = '#D534EB1A';

	public const SECONDS_ONE_MINUTE = 60;
	public const SECONDS_ONE_DAY = 86400;
	public const SECONDS_TWO_WEEKS = 1209600;

	public const CURRENCY_SIGNS = [
		'EUR' => '€',
		'AFN' => '؋',
		'XCD' => '$',
		'AMD' => '֏',
		'AOA' => 'Kz',
		'ARS' => '$',
		'USD' => '$',
		'AUD' => '$',
		'AZN' => '₼',
		'BAM' => 'KM',
		'BBD' => '$',
		'BDT' => '৳',
		'BMD' => '$',
		'BND' => '$',
		'BOB' => 'Bs',
		'BRL' => 'R$',
		'BSD' => '$',
		'BWP' => 'P',
		'BYN' => 'р.',
		'BZD' => '$',
		'CAD' => '$',
		'NZD' => '$',
		'CLP' => '$',
		'CNY' => '¥',
		'COP' => '$',
		'CRC' => '₡',
		'CUC' => '$',
		'CZK' => 'Kč',
		'DKK' => 'kr',
		'DOP' => '$',
		'EGP' => 'E£',
		'FJD' => '$',
		'FKP' => '£',
		'GBP' => '£',
		'GEL' => '₾',
		'GHS' => 'GH₵',
		'GIP' => '£',
		'GNF' => 'FG',
		'GTQ' => 'Q',
		'GYD' => '$',
		'HKD' => '$',
		'HNL' => 'L',
		'HUF' => 'Ft',
		'IDR' => 'Rp',
		'ILS' => '₪',
		'INR' => '₹',
		'ISK' => 'kr',
		'JMD' => '$',
		'JPY' => '¥',
		'KHR' => '៛',
		'KMF' => 'CF',
		'KPW' => '₩',
		'KRW' => '₩',
		'KYD' => '$',
		'KZT' => '₸',
		'LAK' => '₭',
		'LBP' => 'L£',
		'LKR' => 'Rs',
		'LRD' => '$',
		'MGA' => 'Ar',
		'MMK' => 'K',
		'MNT' => '₮',
		'MUR' => 'Rs',
		'MXN' => '$',
		'MYR' => 'RM',
		'NAD' => '$',
		'NGN' => '₦',
		'NIO' => 'C$',
		'NOK' => 'kr',
		'NPR' => 'Rs',
		'PHP' => '₱',
		'PKR' => 'Rs',
		'PLN' => 'zł',
		'PYG' => '₲',
		'RON' => 'lei',
		'RUB' => '₽',
		'RWF' => 'RF',
		'SBD' => '$',
		'SEK' => 'kr',
		'SGD' => '$',
		'SHP' => '£',
		'SRD' => '$',
		'SSP' => '£',
		'STN' => 'Db',
		'SYP' => '£',
		'THB' => '฿',
		'TOP' => 'T$',
		'TRY' => '₺',
		'TTD' => '$',
		'TWD' => '$',
		'UAH' => '₴',
		'UYU' => '$',
		'VND' => '₫',
		'ZAR' => 'R',
		'ZMW' => 'ZK',
	];

	public static function genAdminAPIURL( $task, $nonce ) {
		return 'https://' . ChB_Settings()->getDomainPath() . self::RRBOT_ADMIN_API_PATH . 'task=' . $task . '&_wpnonce=' . $nonce;
	}
}