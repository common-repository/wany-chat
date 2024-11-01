<?php


namespace ChatBot;


class ChB_Analytics {
	const EVENT_EXT_CLICK_OUT = 'EXTCLICKOUT';
	const EVENT_EXT_IN = 'EXTIN';
	const EVENT_EXT_OUT = 'EXTOUT';

	const EVENT_TRY_ON_DEMO_HATS = 'TRYONDH';
	const EVENT_TRY_ON_DEMO_PROFILE_HATS = 'TRYONDPH';
	const EVENT_TRY_ON_HATS = 'TRYONH';

	const EVENT_TRY_ON = 'TRYON_';
	const EVENT_TRY_ON_AGAIN = 'TRYONA';

	const TAG_TRY_ON_DEMO_HATS = self::EVENT_TRY_ON_DEMO_HATS;
	const TAG_TRY_ON_DEMO_PROFILE_HATS = self::EVENT_TRY_ON_DEMO_PROFILE_HATS;
	const TAG_TRY_ON = 'TRYON';

	const EVENT_TRY_ON_IMAGE_SENT = 'TRYONIMGSENT_';

	const EVENT_CATALOG = 'CTLG_';
	const EVENT_LIST_PRODUCTS = 'LSTPRD_';
	const EVENT_OPEN_PRODUCT = 'OPNPRD_';
	const EVENT_BUY_FROM_LIST_PRODUCT = 'BUYLPRD_';
	const EVENT_BUY_PRODUCTS = 'BUYPRD_';

	const EVENT_CHOOSE_SIZE = 'CHSIZE';//legacy
	const EVENT_CHOOSE_VAR = 'CHVAR';
	const EVENT_CHOOSE_QUANTITY = 'CHQNTY';
	const EVENT_CHECKOUT = 'CHKOUT';
	const EVENT_CHOOSE_SHIPPING = 'CHSHP';
	const EVENT_CONFIRM_ORDER = 'CNFRMORDR';

	const EVENT_LIST_NEXT_PAGE = 'NEXTPAGEPRD_';

	const EVENT_VIEW_VIDEO = 'VDVIEW';

	const EVENT_IMAGE_SENT = 'SNDIMG';
	const EVENT_TTH = 'EXPLTTH_';
	const EVENT_BUY_FROM_PRODUCT_EXPLANATION = 'BUYEXPLPRD_';
	const EVENT_OPEN_DF_SIZE = 'OPENDFSZ_';
	const EVENT_BUY_FROM_DF_SIZE = 'BUYDFSZ_';

	const TAG_REF_FBSHOP = 'REFFBSHP';
	const TAG_SITE_PRODUCT_PAGE_BUTTON = 'SITEPRDBNT';
	const TAG_REMINDER = 'RMDNR';
	const TAG_EXT_BOT = 'EXT_BOT';
	const TAG_RELATED = 'RLTD';
	const TAG_SEND_IMG = self::EVENT_IMAGE_SENT;
	const TAG_DF_SIZE_SIMILAR = 'SIMLRDFSZ_';
	const TAG_WEB_CART = 'WEB_CART_';

	const KVS_PREFIX_LOGGED_EVENT = 'LOG_EVT';
	const KVS_PREFIX_LAST_EVENT = 'LAST_EVT';

	const TAGS = [
		self::TAG_REF_FBSHOP               => [ 'desc' => 'ref=pifbsh: message from fbshop, button or image click in ads' ],
		self::TAG_SITE_PRODUCT_PAGE_BUTTON => [ 'desc' => 'site product page button' ],
		self::TAG_REMINDER                 => [ 'desc' => 'reminder after 23h' ],
		self::TAG_RELATED                  => [ 'desc' => 'reminder with related products after 1 min' ],
		self::TAG_SEND_IMG                 => [ 'desc' => 'customer sent image' ],
		self::TAG_DF_SIZE_SIMILAR          => [ 'desc' => 'we sent products with similar size' ],
		self::TAG_TRY_ON_DEMO_HATS         => [ 'desc' => 'we sent try on demo gif with cta' ],
		self::TAG_TRY_ON_DEMO_PROFILE_HATS => [ 'desc' => 'we sent try on using user\'s profile pic' ],
		self::TAG_TRY_ON                   => [ 'desc' => 'user pushed try on button' ],
		self::TAG_WEB_CART                 => [ 'desc' => 'added to cart from web' ],
	];

	const EVENTS = [
		self::EVENT_CATALOG               => [ 'name' => 'catalog', 'order' => 0 ],
		self::EVENT_LIST_PRODUCTS         => [ 'name' => 'list products', 'order' => 1 ],
		self::EVENT_LIST_NEXT_PAGE        => [ 'name' => 'next page prods', 'order' => 2 ],
		self::EVENT_OPEN_PRODUCT          => [ 'name' => 'open product', 'order' => 3 ],
		self::EVENT_VIEW_VIDEO            => [ 'name' => 'product video view', 'order' => 4 ],
		self::EVENT_BUY_FROM_LIST_PRODUCT => [ 'name' => 'buy from list', 'order' => 5 ],
		self::EVENT_BUY_PRODUCTS          => [ 'name' => 'buy product', 'order' => 6 ],

		self::EVENT_IMAGE_SENT                   => [ 'name' => 'customer sent img', 'order' => 7 ],
		self::EVENT_TTH                          => [ 'name' => 'talk to human', 'order' => 8 ],
		self::EVENT_BUY_FROM_PRODUCT_EXPLANATION => [ 'name' => 'buy from tth', 'order' => 9 ],

		self::EVENT_OPEN_DF_SIZE     => [ 'name' => 'recognized size sent', 'order' => 10 ],
		self::EVENT_BUY_FROM_DF_SIZE => [ 'name' => 'buy from recognized size', 'order' => 11 ],

		self::EVENT_CHOOSE_SIZE     => [ 'name' => 'choose size', 'order' => 12 ],
		self::EVENT_CHOOSE_VAR      => [ 'name' => 'choose variation', 'order' => 13 ],
		self::EVENT_CHOOSE_QUANTITY => [ 'name' => 'choose quantity', 'order' => 14 ],
		self::EVENT_CHECKOUT        => [ 'name' => 'check out', 'order' => 15 ],
        self::EVENT_CHOOSE_SHIPPING => [ 'name' => 'choose shipping ', 'order' => 16 ],
		self::EVENT_CONFIRM_ORDER   => [ 'name' => 'confirm order', 'order' => 17 ],

		self::EVENT_TRY_ON_DEMO_HATS         => [ 'name' => '(sent) try on gif DEMO CTA', 'order' => 101 ],
		self::EVENT_TRY_ON_DEMO_PROFILE_HATS => [ 'name' => '(sent) try on gif DEMO CTA profile', 'order' => 102 ],
		self::EVENT_TRY_ON_HATS              => [ 'name' => '(clicked) try on CTA DEMO', 'order' => 103 ],
		self::EVENT_TRY_ON_IMAGE_SENT        => [ 'name' => '(user sent) image', 'order' => 104 ],
		self::EVENT_TRY_ON                   => [ 'name' => '(clicked on product) try on', 'order' => 105 ],
		self::EVENT_TRY_ON_AGAIN             => [ 'name' => '(clicked on product) try on again', 'order' => 106 ],

		self::EVENT_EXT_OUT       => [ 'name' => 'ext bot out', 'order' => 201 ],
		self::EVENT_EXT_CLICK_OUT => [ 'name' => 'ext bot click out', 'order' => 202 ],
		self::EVENT_EXT_IN        => [ 'name' => 'ext bot in', 'order' => 203 ],
	];

	const EVENTS_PARENTS = [
		self::EVENT_LIST_PRODUCTS         => [],
		self::EVENT_LIST_NEXT_PAGE        => [
			self::EVENT_CATALOG,
			self::EVENT_LIST_PRODUCTS,
			self::EVENT_LIST_NEXT_PAGE
		],
		self::EVENT_OPEN_PRODUCT          => [
			self::EVENT_CATALOG,
			self::EVENT_LIST_PRODUCTS,
			self::EVENT_LIST_NEXT_PAGE
		],
		self::EVENT_VIEW_VIDEO            => [],
		self::EVENT_BUY_FROM_LIST_PRODUCT => [ self::EVENT_LIST_PRODUCTS, self::EVENT_LIST_NEXT_PAGE ],

		self::EVENT_BUY_PRODUCTS => [ ChB_Analytics::EVENT_OPEN_PRODUCT ],

		self::EVENT_TTH                          => [ ChB_Analytics::EVENT_OPEN_PRODUCT ],
		self::EVENT_BUY_FROM_PRODUCT_EXPLANATION => [ ChB_Analytics::EVENT_TTH ],

		self::EVENT_BUY_FROM_DF_SIZE => [],

		self::EVENT_CHOOSE_VAR => [
			ChB_Analytics::EVENT_BUY_PRODUCTS,
			ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT,
			ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			ChB_Analytics::EVENT_BUY_FROM_DF_SIZE,
			ChB_Analytics::EVENT_CHOOSE_VAR
		],

		self::EVENT_CHOOSE_SIZE     => [
			ChB_Analytics::EVENT_BUY_PRODUCTS,
			ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT,
			ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			ChB_Analytics::EVENT_BUY_FROM_DF_SIZE,
		],
		self::EVENT_CHOOSE_QUANTITY => [
			ChB_Analytics::EVENT_BUY_PRODUCTS,
			ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT,
			ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			ChB_Analytics::EVENT_BUY_FROM_DF_SIZE,
			ChB_Analytics::EVENT_CHOOSE_VAR,
			ChB_Analytics::EVENT_CHOOSE_SIZE
		],

		self::EVENT_CHECKOUT => [
			ChB_Analytics::EVENT_BUY_PRODUCTS,
			ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT,
			ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			ChB_Analytics::EVENT_BUY_FROM_DF_SIZE,
			ChB_Analytics::EVENT_CHOOSE_VAR,
			ChB_Analytics::EVENT_CHOOSE_SIZE,
			ChB_Analytics::EVENT_CHOOSE_QUANTITY
		],

        self::EVENT_CHOOSE_SHIPPING => [
            self::EVENT_CHECKOUT
        ],

        self::EVENT_CONFIRM_ORDER => [
			ChB_Analytics::EVENT_BUY_PRODUCTS,
			ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT,
			ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			ChB_Analytics::EVENT_BUY_FROM_DF_SIZE,
			ChB_Analytics::EVENT_CHOOSE_VAR,
			ChB_Analytics::EVENT_CHOOSE_SIZE,
			ChB_Analytics::EVENT_CHOOSE_QUANTITY
		],

		self::EVENT_TRY_ON_DEMO_HATS         => [],
		self::EVENT_TRY_ON_DEMO_PROFILE_HATS => [],
		self::EVENT_TRY_ON_HATS              => [],
		self::EVENT_TRY_ON                   => [ self::EVENT_LIST_PRODUCTS, self::EVENT_LIST_NEXT_PAGE ],
		self::EVENT_TRY_ON_AGAIN             => [],
	];

	const REG_LAST_EVENTS = [
		self::EVENT_LIST_PRODUCTS,
		self::EVENT_OPEN_PRODUCT,
		self::EVENT_OPEN_DF_SIZE,
		self::EVENT_TTH,
		self::EVENT_TRY_ON,
		self::EVENT_TRY_ON_AGAIN,
		self::EVENT_TRY_ON_HATS
	];

	//kvs

	public static function registerEvent( $event_code, $event_pars, $fb_user_id, $ts = null ) {
		$key = ChB_Settings()->salt . self::KVS_PREFIX_LOGGED_EVENT . '#' . $event_code . '#' . $fb_user_id . '#' . ( $ts === null ? time() : $ts );
		ChB_Settings()->kvs->set( $key, json_encode( $event_pars ) );
		if ( in_array( $event_code, self::REG_LAST_EVENTS ) ) {
			ChB_Settings()->kvs->set( ChB_Settings()->salt . self::KVS_PREFIX_LAST_EVENT . '#' . $event_code . '#' . $fb_user_id, $key );
		}
	}

	public static function getLastEvent( $event_code, $fb_user_id ) {
		$key = ChB_Settings()->kvs->get( ChB_Settings()->salt . self::KVS_PREFIX_LAST_EVENT . '#' . $event_code . '#' . $fb_user_id );
		if ( ! empty( $key ) ) {
			return self::unpackEventFromKVS( $key, ChB_Settings()->kvs->get( $key ) );
		}

		return null;
	}

	public static function registerEvents( $events, $fb_user_id, $ts = null ) {
		foreach ( $events as $event ) {
			self::registerEvent( $event['code'], $event['pars'], $fb_user_id, $ts );
		}
	}

	//property

	public static function addEvent( &$events, $event_code, $event_tags = null, $event_pars = null ) {
		$event = [ 'code' => $event_code ];
		if ( ! empty( $event_pars ) ) {
			$event['pars'] = $event_pars;
		}
		if ( ! empty( $event_tags ) ) {
			$event['pars']['tags'] = $event_tags;
		}
		$events[] = $event;
	}

	//url

	public static function packEvent4Url( $event_code, $event_pars = null ) {
		if ( $event_pars ) {
			return base64_encode( json_encode( [ 'code' => $event_code, 'pars' => $event_pars ] ) );
		}

		return base64_encode( json_encode( [ 'code' => $event_code ] ) );
	}

	public static function packEvents4Url( $events_codes, $events_tags, $events_pars = null ) {
		$res = [];

		//tags are stored inside pars
		if ( $events_tags ) {
			if ( $events_pars === null ) {
				$events_pars = [];
			}
			$events_pars['tags'] = $events_tags;
		}

		foreach ( $events_codes as $event_code ) {
			if ( $events_pars ) {
				$res[] = self::packEvent4Url( $event_code, $events_pars );
			} else {
				$res[] = self::packEvent4Url( $event_code );
			}
		}

		return $res;
	}

	public static function unpackEventFromKVS( $key, $val ) {
		$key_pars          = explode( '#', $key );
		$evt               = [];
		$evt['code']       = $key_pars[1];
		$evt['fb_user_id'] = $key_pars[2];
		$evt['ts']         = $key_pars[3];
		$evt['pars']       = ( empty( $val ) ? null : json_decode( $val, true ) );

		return $evt;
	}

	public static function unpackEventsFromUrl( ?ChatBot $ChB, $pack ) {
		if ( is_string( $pack ) ) {
			$pack = [ $pack ];
		}

		$events = [];
		foreach ( $pack as $str ) {
			$str   = sanitize_text_field( $str );
			$event = json_decode( base64_decode( $str ), true );

			//rrbot legacy 22.04.2021
			if ( isset( $event['pars']['tag'] ) ) {
				$event['pars']['tags'] = [ $event['pars']['tag'] ];
				unset( $event['pars']['tag'] );
			}

			$events[] = $event;
		}

		//e.g. for opening from brands
		$add_events = [
			'manychat_cat_getProducts' => [
				'check' => [ self::EVENT_LIST_PRODUCTS, self::EVENT_LIST_NEXT_PAGE ],
				'add'   => self::EVENT_LIST_PRODUCTS
			],
			'manychat_cat_openProduct' => [ 'check' => [ self::EVENT_OPEN_PRODUCT ], 'add' => self::EVENT_OPEN_PRODUCT ]
		];

		if ( ! empty( $ChB->task ) && ! empty( $add_events[ $ChB->task ] ) ) {
			$check_event_codes = $add_events[ $ChB->task ]['check'];
			$add               = true;
			foreach ( $events as $event ) {
				if ( in_array( $event['code'], $check_event_codes ) ) {
					$add = false;
					break;
				}
			}
			if ( $add ) {
				$events[] = [ 'code' => $add_events[ $ChB->task ]['add'] ];
			}
		}

		//e.g. for opening from ref=pi
		foreach ( $events as &$event ) {
			if ( ! isset( $event['pars'] ) ) {
				$event['pars'] = null;
			}

			if ( $ChB->task && $ChB->getParam( 'product_id' ) && empty( $event['pars']['pr_id'] ) &&
			     ( $ChB->task == 'manychat_cat_openProduct' ||
			       ( $ChB->task == 'manychat_cat_talkToHuman' ) ) ) {
				$event['pars']['pr_id'] = $ChB->getParam( 'product_id' );
			}
		}

		return $events;
	}

	public static function getParentEventsTags( &$events, $event_code ) {
		if ( empty( self::EVENTS_PARENTS[ $event_code ] ) ) {
			return [];
		}

		return self::getTags( $events, self::EVENTS_PARENTS[ $event_code ] );
	}

	public static function getTags( &$events, $event_code_filters ) {
		$tags = [];
		foreach ( $events as $event ) {
			foreach ( $event_code_filters as $event_code_filter ) {
				if ( $event_code_filter == $event['code'] ) {
					// empty($event['pars']['tags']) is in fact [null]
					$tags = array_unique( array_merge( $tags, ( empty( $event['pars']['tags'] ) ? [ null ] : $event['pars']['tags'] ) ) );
				}
			}
		}

		return $tags;
	}

	public static function addTagToTags( $tag2add, $tags ) {
		if ( empty( $tags ) ) {
			return [ $tag2add ];
		}
		if ( ! in_array( $tag2add, $tags ) ) {
			$tags[] = $tag2add;
		}

		return $tags;
	}

	public static function mergeEventTags( $lines ) {
		$tags = [];
		foreach ( $lines as $line ) {
			$tags = array_merge( $tags, ( empty( $line['event_tags'] ) ? [ null ] : $line['event_tags'] ) );
		}

		return $tags;
	}

	//analytics
	public static function getLastOpenedProduct( $fb_user_id ) {
		$field_name = 'pr_id';
		$event1     = self::getLastEvent( self::EVENT_OPEN_PRODUCT, $fb_user_id );

		$event2 = self::getLastEvent( self::EVENT_TTH, $fb_user_id );
		if ( ! empty( $event2['pars'][ $field_name ] ) && ( empty( $event1['pars'][ $field_name ] ) || $event2['ts'] > $event1['ts'] ) ) {
			$event1 = $event2;
		}

		return [
			'product_id' => ( empty( $event1['pars']['pr_id'] ) ? null : $event1['pars']['pr_id'] ),
			'event_tags' => ( empty( $event1['pars']['tags'] ) ? null : $event1['pars']['tags'] )
		];
	}

	public static function getLastRecognizedSizeProduct( $fb_user_id ) {
		$field_name = 'pr_id';
		$event1     = self::getLastEvent( self::EVENT_OPEN_DF_SIZE, $fb_user_id );

		return ( isset( $event1['pars'][ $field_name ] ) ? $event1['pars'][ $field_name ] : null );
	}

	public static function getLastUnansweredProduct( $fb_user_id ) {
		$last_opened_product = ChB_Analytics::getLastOpenedProduct( $fb_user_id );
		if ( empty( $last_opened_product['product_id'] ) ) {
			return null;
		}

		//not sending if just sent the same
		$last_recognized_size_product = ChB_Analytics::getLastRecognizedSizeProduct( $fb_user_id );
		if ( ! empty( $last_recognized_size_product ) && $last_opened_product['product_id'] == $last_recognized_size_product ) {
			return null;
		}

		return $last_opened_product;
	}

	public static function getLastTryOnTags( $fb_user_id ) {
		$field_name = 'tags';
		$event1     = self::getLastEvent( self::EVENT_TRY_ON, $fb_user_id );

		$event2 = self::getLastEvent( self::EVENT_TRY_ON_AGAIN, $fb_user_id );
		if ( ! empty( $event2['pars'][ $field_name ] ) && ( empty( $event1['pars'][ $field_name ] ) || $event2['ts'] > $event1['ts'] ) ) {
			$event1 = $event2;
		}

		$event2 = self::getLastEvent( self::EVENT_TRY_ON_HATS, $fb_user_id );
		if ( ! empty( $event2['pars'][ $field_name ] ) && ( empty( $event1['pars'][ $field_name ] ) || $event2['ts'] > $event1['ts'] ) ) {
			$event1 = $event2;
		}

		if ( $event2['ts'] > $event1['ts'] ) {
			$event1 = $event2;
		}

		return ( isset( $event1['pars'][ $field_name ] ) ? $event1['pars'][ $field_name ] : null );
	}


	public static function getLoggedEvents( $event_code, $expand_tags, $window_start_ts = null, $window = null, $all_users = false, $sort = false ) {
		$kvs = ChB_Settings()->kvs;
		if ( ! $window_start_ts ) {
			$window_start_ts = time();
		}
		$window_end_ts = $window ? ( $window_start_ts - $window ) : 0;
		$event_keys    = $kvs->scanAllKeysByPrefix( ChB_Settings()->salt . self::KVS_PREFIX_LOGGED_EVENT . '#' . $event_code );
		$res           = [];
		foreach ( $event_keys as $event_key ) {
			$event_key_pars = explode( '#', $event_key );
			$fb_user_id     = $event_key_pars[2];
			$event_ts       = $event_key_pars[3];

			if ( ! $all_users &&
			     ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $fb_user_id ) || $fb_user_id == ChB_Common::EXT_USER ) ) {
				continue;
			}

			if ( $event_ts <= $window_start_ts && $event_ts > $window_end_ts ) {
				$evt = self::unpackEventFromKVS( $event_key, $kvs->get( $event_key ) );
				if ( $expand_tags ) {
					$tags   = ( empty( $evt['pars']['tags'] ) ? [ null ] : $evt['pars']['tags'] );
					$weight = 1.0 / count( $tags );
					foreach ( $tags as $tag ) {
						$evt0           = $evt;
						$evt0['tag']    = ( empty( $tag ) ? '--' : $tag );
						$evt0['weight'] = $weight;
						$res[]          = $evt0;
					}
				} else {
					$evt['weight'] = 1;
					$res[]         = $evt;
				}
			}
		}

		if ( $sort ) {
			usort( $res, function ( $evt1, $evt2 ) {
				return ( $evt2['ts'] - $evt1['ts'] );
			} );
		}

		return $res;
	}

	public static function getExtBotLoggedEvents( $ext_bot_id, $event_code, $window_start_ts = null, $window = null, $all_users = false ) {

		$url = 'https://' . ChB_Settings()->getParam( 'ext_bots' )['pars'][ $ext_bot_id ]['url'] . '?task=get_my_logged_events&event_code=' . $event_code . '&window_start_ts=' . $window_start_ts . '&window=' . $window . '&all_users=' . $all_users;

		$result = ChB_Common::sendGet( $url );
		if ( $result === false ) {
			return [];
		} else {
			return json_decode( $result, true );
		}
	}

	public static function getExtBotAnalytics() {

		$days       = empty( $_GET['days'] ) ? 30 : intval( $_GET['days'] );
		$all_users  = ! empty( $_GET['all_users'] );
		$ext_bot_id = ( empty( $_GET['ext_bot_id'] ) ? null : sanitize_text_field( $_GET['ext_bot_id'] ) );
		if ( empty( $ext_bot_id ) ) {
			if ( empty( ChB_Settings()->getParam( 'ext_bots' )['on'] ) ) {
				echo 'NO EXT BOT SET';

				return;
			}
			$ext_bot_id = ChB_Settings()->getParam( 'ext_bots' )['on'];
		}

		if ( empty( ChB_Settings()->getParam( 'ext_bots' )['this_bot'] ) ) {
			echo 'NO THIS BOT SET';

			return;
		}
		$this_bot_id = ChB_Settings()->getParam( 'ext_bots' )['this_bot'];

		echo '<form method="get">';
		echo '<input hidden type="text" name="task" value="ext_bot">';
		echo '<input type="checkbox" ' . ( $all_users ? 'checked' : '' ) . ' id="all_users" name="all_users"><label for="all_users">All users</label><br>';
		echo 'DAYS: <input type="text" name="days" value="' . esc_attr( $days ) . '">';
		echo '<input type="submit" name="action" value="GET \'EM ALL">';
		echo '</form><br>';

		$window                  = $days * 24 * 3600;
		$logged_events_out       = self::getLoggedEvents( self::EVENT_EXT_OUT, true, null, $window, $all_users );
		$logged_events_click_out = self::getLoggedEvents( self::EVENT_EXT_CLICK_OUT, true, null, $window, $all_users );
		$logged_events_in        = self::getExtBotLoggedEvents( $ext_bot_id, self::EVENT_EXT_IN, null, $window, $all_users );

		$ttl_out  = 0;
		$ttl_cout = 0;
		$ttl_in   = 0;
		$an       = [];
		foreach ( $logged_events_out as $event ) {
			if ( $event['pars']['in_bot'] !== $ext_bot_id ) {
				continue;
			}
			$key = $event['pars']['out_cat'] . '#' . $event['pars']['in_cat'];
			if ( ! isset( $an[ $key ] ) ) {
				$an[ $key ] = [ 'out' => 0, 'cout' => 0, 'in' => 0 ];
			}
			$an[ $key ]['out'] ++;
			$ttl_out ++;
		}

		foreach ( $logged_events_click_out as $event ) {
			if ( $event['pars']['in_bot'] !== $ext_bot_id ) {
				continue;
			}
			$key = $event['pars']['out_cat'] . '#' . $event['pars']['in_cat'];
			if ( ! isset( $an[ $key ] ) ) {
				$an[ $key ] = [ 'out' => 0, 'cout' => 0, 'in' => 0 ];
			}
			$an[ $key ]['cout'] ++;
			$ttl_cout ++;
		}

		foreach ( $logged_events_in as $event ) {
			if ( $event['pars']['out_bot'] !== $this_bot_id ) {
				continue;
			}
			$key = $event['pars']['out_cat'] . '#' . $event['pars']['in_cat'];
			if ( ! isset( $an[ $key ] ) ) {
				$an[ $key ] = [ 'out' => 0, 'cout' => 0, 'in' => 0 ];
			}
			$an[ $key ]['in'] ++;
			$ttl_in ++;
		}

		$an['TOTAL#TOTAL'] = [ 'out' => $ttl_out, 'cout' => $ttl_cout, 'in' => $ttl_in ];

		echo '<table border="1px">';
		echo '<tr><td>CAT OUT</td><td>CAT IN</td><td>OUT</td><td>CLICK OUT</td><td>%</td><td>IN</td><td>%</td></tr>';

		foreach ( $an as $key => $val ) {
			$key_pars = explode( '#', $key );
			echo '<tr><td>' . esc_attr( $key_pars[0] ) . '</td><td>' . esc_attr( $key_pars[1] ) .
			     '</td><td>' . esc_attr( $val['out'] ) . '</td><td>' . esc_attr( $val['cout'] ) .
			     '</td><td>' . ( $val['out'] > 0 ? intval( 100 * $val['cout'] / $val['out'] ) : 0 ) . '</td>' .
			     '</td><td>' . $val['in'] .
			     '</td><td>' . ( $val['out'] > 0 ? intval( 100 * $val['in'] / $val['out'] ) : 0 ) . '</td></tr>';
		}
		echo '</table>';
	}

	public static function printStyleForReports() {
		?>
        <style>
            body {
                font-family: "Source Sans Pro", "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
                color: #6d6d6d;
            }

            table.has-border, table.has-border th, table.has-border td {
                border: 1px solid black;
            }

            table td {
                padding: 5px;
            }

            input[type=text], input[type=number], input[type=email], input[type=tel], input[type=url], input[type=password], input[type=search], textarea, .input-text {
                padding: 0.6180469716em;
                background-color: #f2f2f2;
                color: #43454b;
                border: 0;
                -webkit-appearance: none;
                box-sizing: border-box;
                font-weight: 400;
                box-shadow: inset 0 1px 1px rgb(0 0 0 / 13%);
            }

            button, input[type=button], input[type=reset], input[type=submit], .button, .wc-block-grid__products .wc-block-grid__product .wp-block-button__link, .added_to_cart {
                border: 0;
                border-radius: 0;
                background-color: #eeeeee;
                border-color: #eeeeee;
                color: #333333;
                cursor: pointer;
                padding: 0.6180469716em 1.41575em;
                text-decoration: none;
                font-weight: 600;
                text-shadow: none;
                display: inline-block;
                -webkit-appearance: none;
            }

            button:hover, input[type="button"]:hover, input[type="reset"]:hover, input[type="submit"]:hover, .button:hover, .widget a.button:hover {
                background-color: #d5d5d5;
                border-color: #d5d5d5;
                color: #333333;
            }
        </style>
		<?php
	}

	public static function getEventsReport( $event_codes, $group_by_tags, $days = 30 ) {

		if ( ! empty( wp_scripts()->registered['jquery-core']->src ) ) {
			echo '<script src="' . esc_url_raw( wp_scripts()->registered['jquery-core']->src ) . '"></script>';
		}
		if ( ! empty( wp_scripts()->registered['jquery-ui-core']->src ) ) {
			echo '<script src="' . esc_url_raw( wp_scripts()->registered['jquery-ui-core']->src ) . '"></script>';
		}
		if ( ! empty( wp_scripts()->registered['jquery-ui-datepicker']->src ) ) {
			echo '<script src="' . esc_url_raw( wp_scripts()->registered['jquery-ui-datepicker']->src ) . '"></script>';
		}
		echo '<link rel="stylesheet" href="' . esc_url_raw( ChB_Constants::JQUERY_UI_URL ) . '" />';
		?>
        <script>
            jQuery(document).ready(
                function () {
                    jQuery('#datepicker').datepicker({
                        dateFormat: 'yy-mm-dd'
                    });
                });
        </script>
		<?php
		self::printStyleForReports();

		if ( ! empty( $_GET['days'] ) ) {
			$days = intval( $_GET['days'] );
		}

		if ( ! empty( $_GET['date_from'] ) ) {
			$date_from = $_GET['date_from'];
			$dt        = \DateTime::createFromFormat( 'Y-m-d', $date_from, ChB_Settings()->timezone );
			if ( $dt ) {
				$dt->setTime( 23, 59, 59 );
				$window_start_ts = $dt->getTimestamp();
			}
		}

		if ( empty( $window_start_ts ) ) {
			$date_from       = '';
			$window_start_ts = null;
		}

		$show_events = ! empty( $_GET['show_events'] );
		$all_users   = ! empty( $_GET['all_users'] );

		echo '<form method="get"><table class="no-border">';
		echo '<input hidden type="text" name="task" value="' . esc_attr( sanitize_text_field( $_GET['task'] ) ) . '">';
		echo '<tr><td><input type="checkbox" ' . ( $all_users ? 'checked' : '' ) . ' id="all_users" name="all_users"><label for="all_users">All users</label></td></tr>';
		echo '<tr><td><input type="checkbox" ' . ( $show_events ? 'checked' : '' ) . ' id="show_events" name="show_events"><label for="show_events">Show events</label></td></tr>';
		echo '<tr><td><label for="datepicker">Date From:</label></td></tr><tr><td><input type="text" name="date_from" id="datepicker" autocomplete="off" value="' . esc_attr( $date_from ) . '"></td></tr>';
		echo '<tr><td><label for="days">Days:</label></td></tr><tr><td><input type="text" id="days" name="days" autocomplete="off" value="' . esc_attr( $days ) . '"></td></tr>';
		echo '<tr><td><input type="submit" name="action" value="GET \'EM ALL"></td></tr>';
		echo '</table></form>';

		$window = $days * 24 * 3600;

		$logged_events = [];
		foreach ( $event_codes as $event_code ) {
			$logged_events = array_merge( $logged_events, self::getLoggedEvents( $event_code, true, $window_start_ts, $window, $all_users, false ) );
		}


		if ( $group_by_tags ) {
			usort( $logged_events, function ( $evt1, $evt2 ) use ( $group_by_tags ) {
				if ( $group_by_tags && $evt1['tag'] != $evt2['tag'] ) {
					return ( $evt1['tag'] > $evt2['tag'] ? 1 : - 1 );
				} else if ( $evt1['code'] != $evt2['code'] ) {
					return ( self::EVENTS[ $evt1['code'] ]['order'] - self::EVENTS[ $evt2['code'] ]['order'] );
				}

				return ( $evt2['ts'] - $evt1['ts'] );
			} );
		}

		$fields   = [ 'tag', 'code' ];
		$counters = self::buildEventsCounters( $logged_events, $fields );

		echo '<div>TOTAL EVENTS: ' . self::getTotalEventsCounters( $counters )['weight'] . '</div>';

		echo '<table class="has-border">';
		if ( $group_by_tags ) {
			echo '<tr><td>TAG</td><td>EVENT</td><td colspan="3"></td></tr>';
		} else {
			echo '<tr><td>CODE</td><td colspan="4"></td></tr>';
		}

		foreach ( $logged_events as $logged_event ) {
			$username = ChB_User::getSubscriberDisplayName( $logged_event['fb_user_id'] );

			$new_tag  = ( $group_by_tags && ( ! isset( $prev_tag ) || $prev_tag != $logged_event['tag'] ) );
			$new_code = ( ! isset( $prev_code ) || $prev_code != $logged_event['code'] );

			if ( $show_events || $new_tag || $new_code ) {
				echo '<tr>';
			}

			if ( $group_by_tags ) {
				if ( $new_tag ) {

					$tag_desc = empty( self::TAGS[ $logged_event['tag'] ]['desc'] ) ? $logged_event['tag'] : self::TAGS[ $logged_event['tag'] ]['desc'];
					$counter  = self::getEventCounters( $counters, $logged_event, [ 'tag' ] );
					echo '<td rowspan="' . esc_attr( $show_events ? $counter['count'] : 1 ) . '">' . esc_attr( $tag_desc . ' (' . ChB_Common::printFloat2Round( $counter['weight'] ) . ')' ) . '</td>';
				}

			}

			if ( $new_tag || $new_code ) {
				$counter = self::getEventCounters( $counters, $logged_event, [ 'tag', 'code' ] );
				if ( $group_by_tags && ! $show_events && ! $new_tag ) {
					echo '<td></td>';
				}
				echo '<td rowspan="' . esc_attr( $show_events ? $counter['count'] : 1 ) . '">' . esc_attr( self::EVENTS[ $logged_event['code'] ]['name'] . '(' . ChB_Common::printFloat2Round( $counter['weight'] ) . ')' ) . '</td>';
			}

			if ( $show_events ) {
				echo '<td>' . esc_attr( ChB_Common::timestamp2DateTime( $logged_event['ts'] ) ) . '</td><td>' . ChB_ManyChat::getMCLiveChatLinkHTML( $logged_event['fb_user_id'] ) . '</td><td>' . esc_attr( $username ) . '</td>';
			}

			if ( $show_events || $new_tag || $new_code ) {
				echo '</tr>';
			}

			$prev_tag  = $logged_event['tag'];
			$prev_code = $logged_event['code'];
		}

		echo '</table>';

	}

	public static function getTryOnAnalytics() {
		$event_codes = [
			ChB_Analytics::EVENT_TRY_ON_DEMO_PROFILE_HATS,
			ChB_Analytics::EVENT_TRY_ON_DEMO_HATS,
			ChB_Analytics::EVENT_TRY_ON_HATS,
			ChB_Analytics::EVENT_TRY_ON,
			ChB_Analytics::EVENT_TRY_ON_AGAIN
		];
		self::getEventsReport( $event_codes, false );
	}


	public static function getClicksAnalytics() {
		$event_codes = [
			self::EVENT_CATALOG,
			self::EVENT_LIST_PRODUCTS,
			self::EVENT_LIST_NEXT_PAGE,
			self::EVENT_IMAGE_SENT,
			self::EVENT_TRY_ON_DEMO_HATS,
			self::EVENT_TRY_ON_DEMO_PROFILE_HATS,
			self::EVENT_TRY_ON_HATS,
			self::EVENT_TRY_ON_IMAGE_SENT,
			self::EVENT_TRY_ON,
			self::EVENT_TRY_ON_AGAIN,
			self::EVENT_OPEN_PRODUCT,
			self::EVENT_BUY_PRODUCTS,
			self::EVENT_BUY_FROM_LIST_PRODUCT,
			self::EVENT_TTH,
			self::EVENT_BUY_FROM_PRODUCT_EXPLANATION,
			self::EVENT_OPEN_DF_SIZE,
			self::EVENT_BUY_FROM_DF_SIZE,
			self::EVENT_VIEW_VIDEO,
			self::EVENT_CHOOSE_VAR,
			self::EVENT_CHOOSE_SIZE,
			self::EVENT_CHOOSE_QUANTITY,
			self::EVENT_CHECKOUT,
			self::EVENT_CHOOSE_SHIPPING,
			self::EVENT_CONFIRM_ORDER,
			self::EVENT_EXT_OUT,
			self::EVENT_EXT_CLICK_OUT,
			self::EVENT_EXT_IN
		];
		self::getEventsReport( $event_codes, true );
	}

	public static function buildEventsCounters( &$events, $fields ) {
		$counters = [];
		foreach ( $events as $event ) {
			$key = '$$$';
			foreach ( $fields as $field ) {
				$key .= $event[ $field ] . '###';

				if ( empty( $counters[ $key ] ) ) {
					$counters[ $key ] = [ 'count' => 1, 'weight' => $event['weight'] ];
				} else {
					$counters[ $key ]['count'] ++;
					$counters[ $key ]['weight'] += $event['weight'];
				}
			}

			if ( empty( $counters['TOTAL'] ) ) {
				$counters['TOTAL'] = [ 'count' => 1, 'weight' => $event['weight'] ];
			} else {
				$counters['TOTAL']['count'] ++;
				$counters['TOTAL']['weight'] += $event['weight'];
			}
		}

		return $counters;
	}

	public static function getEventCounters( &$counters, &$event, $fields ) {
		$key = '$$$';
		foreach ( $fields as $field ) {
			$key .= $event[ $field ] . '###';
		}

		return ( isset( $counters[ $key ] ) ? $counters[ $key ] : [ 'count' => 0, 'weight' => 0 ] );
	}

	public static function getTotalEventsCounters( &$counters ) {
		return ( isset( $counters['TOTAL'] ) ? $counters['TOTAL'] : [ 'count' => 0, 'weight' => 0 ] );
	}

	public static function getVideoAnalytics() {
		if ( ! ChB_Settings()->getParam( 'videos_path' ) ) {
			return;
		}

		$lines = [];
		if ( file_exists( ChB_Settings()->getParam( 'video_log_file' ) . '.1' ) ) {
			$lines = file( ChB_Settings()->getParam( 'video_log_file' ) . '.1' );
		}
		if ( file_exists( ChB_Settings()->getParam( 'video_log_file' ) ) ) {
			$lines = array_merge( $lines, file( ChB_Settings()->getParam( 'video_log_file' ) ) );
		}

		$skus = [];
		if ( $files = scandir( ChB_Settings()->getParam( 'videos_path' ) ) ) {
			foreach ( $files as $file ) {
				if ( strpos( $file, '.mp4' ) !== false ) {
					$skus[ str_replace( '.mp4', '', $file ) ] = $file;
				}
			}
		}

		$count = 0;
		foreach ( $lines as $line ) {
			if ( strpos( $line, '?product_video=' . ChB_Settings()->salt ) === false ) {
				continue;
			}

			if ( strpos( $line, "HTTP/1.1\" 200" ) === false ) {
				continue;
			}


			foreach ( $skus as $sku => $filename ) {
				if ( strpos( $line, $filename ) !== false ) {
					$count ++;
					echo esc_html( $line ) . '<br>';
					$ind1     = strpos( $line, '[' );
					$ind2     = strpos( $line, ']', $ind1 );
					$date_str = substr( $line, $ind1 + 1, $ind2 - $ind1 - 1 );
					$ts       = ( new \DateTime( $date_str ) )->getTimestamp();//timezone is contained in log date

					$ind1 = strpos( $line, 'GET ' ) + 4;
					$ind2 = strpos( $line, 'HTTP ', $ind1 );
					$line = substr( $line, $ind1, $ind2 - $ind1 );

					$PARAMS = [];
					parse_str( $line, $PARAMS );
					$events = ChB_Analytics::unpackEventsFromUrl( null, empty( $PARAMS['evt'] ) ? [] : $PARAMS['evt'] );

					ChB_Common::my_log( $events, true, 'getVideoAnalytics EVENTS' );

					if ( ! empty( $events ) ) {
						ChB_Analytics::registerEvents( $events, $PARAMS['fb_user_id'], $ts );
					}

					break;
				}
			}
		}
	}
}