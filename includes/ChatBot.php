<?php

namespace ChatBot;

class ChatBot {
	public ChB_User $user;
	public ChB_Promo $promo;
	public ChB_Cart $cart;

	public ?string $task;
	public array $events;
	public array $scheduled_events;

	private ?string $init_error = null;
	private array $data;
	private array $PARAMS;
	private ?array $PAYLOAD_PARAMS;
	private ?array $PARAMS2MERGE;
	private ?array $PARAMS2MERGE64;

	private function loadParams( $PARAMS, $PAYLOAD_PARAMS ) {

		$this->data   = [];
		$this->PARAMS = is_array( $PARAMS ) ? $PARAMS : $_GET;

		if ( is_array( $PAYLOAD_PARAMS ) ) {
			$this->PAYLOAD_PARAMS = $PAYLOAD_PARAMS;
		} else {
			$postBody             = file_get_contents( 'php://input' );
			$this->PAYLOAD_PARAMS = json_decode( $postBody, true );
		}

		$params2merge_str = '';
		if ( ! empty( $this->PAYLOAD_PARAMS['val1'] ) ) {
			$params2merge_str .= $this->PAYLOAD_PARAMS['val1'];
			unset( $this->PAYLOAD_PARAMS['val1'] );
		}
		if ( ! empty( $this->PAYLOAD_PARAMS['val2'] ) ) {
			$params2merge_str .= urlencode( $this->PAYLOAD_PARAMS['val2'] );
			unset( $this->PAYLOAD_PARAMS['val2'] );
		}

		if ( $params2merge_str ) {
			$this->PARAMS2MERGE = [];
			parse_str( $params2merge_str, $this->PARAMS2MERGE );
		} else {
			$this->PARAMS2MERGE = null;
		}

		if ( ! empty( $this->PARAMS2MERGE['payload_base64'] ) ) {
			$params2merge64_str = $this->PARAMS2MERGE['payload_base64'];
			unset( $this->PARAMS2MERGE['payload_base64'] );
		} elseif ( ! empty( $this->PAYLOAD_PARAMS['payload_base64'] ) ) {
			$params2merge64_str = $this->PAYLOAD_PARAMS['payload_base64'];
			unset( $this->PAYLOAD_PARAMS['payload_base64'] );
		} elseif ( ! empty( $this->PARAMS['payload_base64'] ) ) {
			$params2merge64_str = $this->PARAMS['payload_base64'];
			unset( $this->PARAMS['payload_base64'] );
		}

		if ( ! empty( $params2merge64_str ) ) {
			$params2merge64_str   = base64_decode( $params2merge64_str );
			$this->PARAMS2MERGE64 = [];
			parse_str( $params2merge64_str, $this->PARAMS2MERGE64 );
		} else {
			$this->PARAMS2MERGE64 = null;
		}
	}

	private function __construct( ?ChB_User $user = null, $PARAMS = null, $PAYLOAD_PARAMS = null ) {
		$this->loadParams( $PARAMS, $PAYLOAD_PARAMS );

		if ( ! ChB_Settings()->auth->authorize( $this->getParam( 'wy_token' ) ) ) {
			ChB_Common::my_log( 'ACCESS DENIED __construct' );
			$this->init_error = 'access denied';

			return;
		} else {
			ChB_Common::my_log( 'ACCESS GRANTED __construct' );
		}

		if ( $this->checkDuplicateMessages( ChB_Settings()->kvs ) ) {
			$this->init_error = 'duplicate';

			return;
		}

		$this->task = $this->getParam( 'task' );

		if ( ! $user ) {
			$user = ChB_User::initUser( $this->getParam( 'fb_user_id' ), $this->getParam( 'channel' ), $this->getParam( 'user_id' ) );
		}

		if ( $user && $user instanceof ChB_User ) {
			$this->user = $user;
		} else {
			$this->init_error = 'init-user-error';

			return;
		}
		$set_lang   = ( $this->task === 'manychat_lng_initLang' || $this->task === 'manychat_lng_changeLang' ? $this->getParam( 'val' ) : false );
		$force_lang = ( $this->task === 'manychat_lng_changeLang' );
		$this->user->getLang( $set_lang, $force_lang );
		ChB_Settings()->setUserSettings( $this->user );

		$this->cart             = new ChB_Cart( $this->user->wp_user_id, $this->user->getCartUser() );
		$this->promo            = new ChB_Promo( $this->user, $this->getParam( 'promo' ) );
		$this->events           = ChB_Analytics::unpackEventsFromUrl( $this, $this->getParam( 'evt' ) );
		$this->scheduled_events = [];
	}

	/**
	 * @param ChB_User|string $user - user or wp_user_id or fb_user_id
	 * @param array|null $PARAMS
	 * @param bool $is_wp_user_id
	 *
	 * @return bool|ChatBot
	 */
	public static function openTempChatBotSession( $user, $PARAMS = null, $is_wp_user_id = false ) {

		if ( ! ( $user instanceof ChB_User ) ) {
			if ( $is_wp_user_id ) {
				$user = ChB_User::initUserByWPUserID( $user );
			} else {
				$user = ChB_User::initUser( $user );
			}

			if ( ! ( $user instanceof ChB_User ) ) {
				ChB_Common::my_log( $user, 1, __CLASS__ . '::' . __FUNCTION__ . ' ERROR: cannot create bot user' );

				return false;
			}
		}

		ChB_Settings()->stashUserSettings();
		if ( ! $PARAMS ) {
			$PARAMS = [];
		}

		$PARAMS['user_id']  = $user->fb_user_id;
		$PARAMS['wy_token'] = ChB_Settings()->auth->getWYToken();
		$PARAMS['u']        = ChB_Common::my_rand_string( 3 ); //for passing check for duplicates

		$ChB = new ChatBot( $user, $PARAMS, [] );
		if ( $ChB->init_error ) {
			return false;
		}

		return $ChB;
	}

	public static function closeTempChatBotSession() {
		ChB_Settings()->unstashUserSettings();
	}

	public static function run( ?ChatBot $ChB = null ) {

		ChB_Redirect::checkRedirect();

		$start_time = time();
		$res        = null;
		try {
			ChB_Settings()->tic( 'ttl' );
			ChB_Common::my_log( 'plugin_version_db=' . ChB_Updater_WanyChat::instance()->getPluginVersionFromDB() .
			                    ' kvs=' . ChB_Settings()->kvs->kvs_version() . ' dbg=' . ChB_Debug::getDebugLevel() . ' mem start=' . ( memory_get_peak_usage( true ) / 1048576 ) . ' mem_limit_ini=' . ini_get( 'memory_limit' ) );
			set_time_limit( 30 );

			ChB_Settings()->tic( 'chb_init' );
			if ( ! $ChB ) {
				$ChB = new ChatBot();
			}
			ChB_Settings()->toc( 'chb_init' );

			if ( $ChB->init_error ) {
				if ( $ChB->init_error == 'duplicate' ) {
					return [
						'version' => 'v2',
						'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => ':)' ] ] ]
					];
				}

				ChB_Common::my_log( 'OOPS Cannot launch bot, init_error=' . $ChB->init_error );

				return [];
			}

			if ( $ChB->getParam( 'li' ) ) {
				$ChB->user->setLastInteraction();
			}

			if ( ! empty( $ChB->events ) ) {
				ChB_Analytics::registerEvents( $ChB->events, $ChB->user->fb_user_id );
			}

			$init_promo_messages = ChB_FLOW_Promo::getInitPromoMessages( $ChB );

			if ( strpos( $ChB->task, 'manychat_cat_' ) === 0 ) {
				$res = ChB_FLOW_Catalogue::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_tryon_' ) === 0 ) {
				$res = ChB_FLOW_TryOn::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_order_' ) === 0 ) {
				$res = ChB_FLOW_NewOrder::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_myorders_' ) === 0 ) {
				$res = ChB_FLOW_MyOrders::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_promo_' ) === 0 ) {
				$res = ChB_FLOW_Promo::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_manager' ) === 0 ) {
				$res = ChB_FLOW_Manager::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_cmn' ) === 0 ) {
				$res = ChB_FLOW_Common::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_rmkt_' ) === 0 ) {
				$res = ChB_FLOW_WooRemarketing::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_wac_' ) === 0 ) {
				$res = ChB_FLOW_WooRemarketing::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_lng' ) === 0 ) {
				$res = ChB_FLOW_Lang::run( $ChB );
			} elseif ( strpos( $ChB->task, 'manychat_api_' ) === 0 ) {
				return ChB_API::run( null );
			} elseif ( $ChB->task === 'manychat_evt_reg' ) {
				// used in 'RRB Events' rule in ManyChat

				// dummy task, events were already registered above
				return [];
			}

			if ( ! empty( $init_promo_messages ) && isset( $res['content']['messages'] ) ) {
				$res['content']['messages'] = array_merge( $init_promo_messages, $res['content']['messages'] );
			}

			if ( ! empty( $res['version'] ) && $ChB->getParam( 'add_tag' ) ) {
				$res['content']['actions'] = [ [ 'action' => 'add_tag', 'tag_name' => $ChB->getParam( 'add_tag' ) ] ];
			}
			//Scheduling events. Mostly, repeating reminders
			ChB_Events::scheduleRecurringEvents( $ChB );

			if ( ! empty( $ChB->scheduled_events ) ) {
				if ( ChB_Debug::isDebug() ) {
					ChB_Common::my_log( array_column( $ChB->scheduled_events, 'event_name' ), 1, 'Scheduled events dbg' );
				}

				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' Scheduled events' );

				register_shutdown_function( [
					'\ChatBot\ChatBot',
					'shutDownFunction'
				], $ChB, time(), 0, 'processScheduledEvents', null );
			}

			if ( ! isset( $res['content']['messages'] ) || ! isset( $res['version'] ) ) {
				return $res;
			}

			ChB_Common::my_debug_log( $res, 1, 'RES dbg' );

			// Sending via native FB API
			if ( ChB_Settings()->useNativeAPI( $ChB ) || $res['version'] === 'native' ) {
				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $res ], $ChB );
				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' Sending response with native fb API' );

				return [];
			}

			//Sending via MC API
			//Splitting messages on groups of 10
			if ( count( $res['content']['messages'] ) <= 10 ) {
				$split_response = [ $res ];
			} else {
				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' More than 10 messages. Splitting..' );
				$mess           = $res['content']['messages'];
				$split_response = [];
				for ( $i = 0; $i < 9; $i ++ ) {
					$len              = min( count( $mess ), 10 );
					$split_response[] = [
						'version' => 'v2',
						'content' => [ 'messages' => array_slice( $mess, 0, $len ) ]
					];
					if ( count( $mess ) <= $len ) {
						break;
					}
					$mess = array_slice( $mess, $len );
				}

				if ( ! empty( $res['actions'] ) ) {
					$split_response[ count( $split_response ) - 1 ]['content']['actions'] = $res['actions'];
				}
				if ( ! empty( $res['quick_replies'] ) ) {
					$split_response[ count( $split_response ) - 1 ]['content']['quick_replies'] = $res['quick_replies'];
				}
			}

			if ( ( time() - $start_time ) > ChB_Common::LONG_REQUEST ) {
				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' Response is too long. Scheduling manual response' );
				$res                = [
					'version' => 'v2',
					'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => '⏳' ] ] ]
				];
				$splitted_ind_start = 0;

			} elseif ( count( $split_response ) > 1 && ! self::fastcgi_finish_request_exists() ) {
				//In case of Apache we cannot return first portion of json, finish request and send the rest
				//So we'll just send everything manually, just like in a long request case above

				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' No "fastcgi_finish_request". Scheduling manual response' );
				$res                = [
					'version' => 'v2',
					'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => '⏳' ] ] ]
				];
				$splitted_ind_start = 0;

			} else {
				$res                = $split_response[0];
				$splitted_ind_start = 1;
			}

			for ( $i = $splitted_ind_start; $i < count( $split_response ); $i ++ ) {
				$fields = [ 'data' => $split_response[ $i ] ];

				$sleep_seconds = ( $i === 0 ? 0 : ( $i === 1 ? 10 : 1 ) );
				register_shutdown_function( [
					'\ChatBot\ChatBot',
					'shutDownFunction'
				], $ChB, time(), $sleep_seconds, 'sendContent2ManyChat', $fields );
			}

			if ( $ChB->getParam( 'app_call' ) === ChB_Constants::APP_CALL_MC ) {
				// app_call=mc - call from Wany.Chat action in MC flow
				// splitting all messages by 10 and sending via MC API (above)

				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $res ], $ChB );
				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' Manual response' );

				return [];
			} else {
				// app_call=wy - call from dynamic MC button
				// splitting all messages by 10, returning first group to json, and sending other groups via MC API (above)
				return $res;
			}
		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		} finally {
			ChB_Settings()->toc( 'ttl' );
			ChB_Settings()->printTicToc( ( isset( $ChB->task ) ? $ChB->task : null ) . ' ' . ( ! empty( $ChB->user ) ? $ChB->user->fb_user_id : '' ) );
			ChB_Common::my_log( 'mem finish=' . ( memory_get_peak_usage( true ) / 1048576 ) );
			self::finalActions( $ChB );
		}

		return $res;
	}

	public static function finalActions( ?ChatBot $ChB ) {
		if ( empty( $ChB ) ) {
			return;
		}

		try {
			if ( ChB_Settings()->kvs ) {
				ChB_Settings()->kvs->close();
			}
		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}
	}


	public static function shutDownFunction( ChatBot $ChB, $time, $sleep_seconds, $task, $par1 ) {

		try {
			ChB_Settings()->tic( 'shd' );

			//@see https://core.trac.wordpress.org/ticket/41358#no0
			ChB_Common::my_debug_log( 'before fastcgi_finish_request hdst=' . headers_sent() );

			ignore_user_abort( true );
			if ( self::fastcgi_finish_request_exists() ) {

				ChB_Common::my_debug_log( 'fastcgi_finish_request' );

				// This function is available on nginx and is the cleanest solution.
				fastcgi_finish_request();

			} elseif ( ! headers_sent() ) {
				ChB_Common::my_debug_log( 'ob_end_flush' );

				// For apache, we can send some headers to end the connection early.
				header( 'Content-Length: ' . ob_get_length() );
				header( 'Connection: close' );
				ob_end_flush();
				flush();
			}

			ChB_Common::my_debug_log( 'after fastcgi_finish_request' );

			if ( $sleep_seconds != 0 ) {
				sleep( $sleep_seconds );
			}

			if ( $task === 'sendContent2ManyChat' ) {
				ChB_Common::my_log( $ChB->user->fb_user_id . ' ' . $ChB->task . ' Manual response' );
				if ( time() - $time < 86400 ) {
					ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $par1, $ChB );
				}
			} elseif ( $task === 'processScheduledEvents' ) {
				foreach ( $ChB->scheduled_events as $scheduled_event ) {
					ChB_Events::eventHandler( $scheduled_event['event_name'], $scheduled_event['event_args'] );
				}
			}


		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		} finally {
			ChB_Settings()->toc( 'shd' );
			ChB_Settings()->printTicToc( $ChB->task . ' ' . $ChB->user->fb_user_id );
			ChB_Common::my_log( 'mem shd=' . ( memory_get_peak_usage( true ) / 1048576 ) );
		}
	}

	public static function fastcgi_finish_request_exists() {
		return is_callable( 'fastcgi_finish_request' );
	}

	public function checkDuplicateMessages( ChB_KeyValueStorage $kvs ) {
		if ( ! $kvs ) {
			return false;
		}
		$key = ChB_Common::DUPLICATE_MESSAGES_PREFIX . ChB_Settings()->salt;
		foreach ( [ 'task', 'user_id', 'page_id', 'u' ] as $param_key ) {
			if ( $param_val = $this->getParam( $param_key ) ) {
				$key .= '&&' . $param_key . '=' . ( is_array( $param_val ) ? implode( '&&', $param_val ) : $param_val );
			}
		}

		if ( $kvs->get( $key ) ) {
			ChB_Common::my_log( 'duplicate message: ' . $key );

			return true;
		}
		$kvs->setex( $key, ChB_Common::DUPLICATE_MESSAGES_INTERVAL, 1 );

		return false;
	}

	public function makeExtBotQuery( $ext_domain, $pars ) {

//		$my_domain = ChB_Settings()->domain;
//		$my_fb_user_id = $this->fb_user_id;
//
//		$this->fb_user_id = ChB_Common::EXT_USER;
//		ChB_Settings()->domain = $ext_domain;
//		list($url, $payload) = ChB_Common::makeDynamicBlockCallbackPars($this, $pars);
//		ChB_Settings()->domain = $my_domain;
//		$this->fb_user_id = $my_fb_user_id;

//		return ChB_Common::sendPost($url, $payload);
		return '';
	}

	private const PARAM_TYPE_INT = 10;
	private const PARAM_TYPE_TEXT = 20;
	private const PARAM_TYPE_KEYS = 30;

	private const PARAM_TYPES = [
		'view'       => [ 'explode' => '.' ],
		'cat_slug'   => [ 'type' => self::PARAM_TYPE_KEYS ],
		'size_slug'  => [ 'type' => self::PARAM_TYPE_KEYS ],
		'parent'     => [ 'type' => self::PARAM_TYPE_KEYS ],
		'product_id' => [ 'type' => self::PARAM_TYPE_KEYS ],
		'var_id'     => [ 'type' => self::PARAM_TYPE_KEYS ],
		'order_id'   => [ 'type' => self::PARAM_TYPE_KEYS ],

		'product_ids' => [ 'type' => self::PARAM_TYPE_KEYS, 'explode' => '.', 'empty' => [] ],
		'cat_slugs'   => [ 'type' => self::PARAM_TYPE_KEYS, 'explode' => '.', 'empty' => [] ],
		'wc_tags'     => [ 'type' => self::PARAM_TYPE_KEYS, 'explode' => '.', 'empty' => [] ],
		'fields'      => [ 'type' => self::PARAM_TYPE_KEYS, 'explode' => '.', 'empty' => [] ],
		'promo'       => [ 'type' => self::PARAM_TYPE_KEYS, 'explode' => '.', 'empty' => [] ],
		'evt'         => [ 'empty' => [] ],

		'pmin'   => [ 'type' => self::PARAM_TYPE_INT ],
		'pmax'   => [ 'type' => self::PARAM_TYPE_INT ],
		'offset' => [ 'type' => self::PARAM_TYPE_INT ],
		'qty'    => [ 'type' => self::PARAM_TYPE_INT ],

		'text_over_image' => [ 'explode' => '_' ],

		'pa_filter' => [ 'hook' => 'hookPAFilter' ],
		'ca_filter' => [ 'hook' => 'hookCAFilter' ],
	];

	public function getParam( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		if ( isset( self::PARAM_TYPES[ $key ]['hook'] ) ) {
			$res = $this->{self::PARAM_TYPES[ $key ]['hook']}();
		} else {
			if ( isset( $this->PARAMS2MERGE64[ $key ] ) ) {
				$val = $this->PARAMS2MERGE64[ $key ];
			} elseif ( isset( $this->PARAMS2MERGE[ $key ] ) ) {
				$val = $this->PARAMS2MERGE[ $key ];
			} elseif ( isset( $this->PAYLOAD_PARAMS[ $key ] ) ) {
				$val = $this->PAYLOAD_PARAMS[ $key ];
			} elseif ( isset( $this->PARAMS[ $key ] ) ) {
				$val = $this->PARAMS[ $key ];
			}

			$type    = isset( self::PARAM_TYPES[ $key ]['type'] ) ? self::PARAM_TYPES[ $key ]['type'] : self::PARAM_TYPE_TEXT;
			$explode = isset( self::PARAM_TYPES[ $key ]['explode'] ) ? self::PARAM_TYPES[ $key ]['explode'] : null;

			if ( $type === self::PARAM_TYPE_INT ) {
				$res = ( isset( $val ) ? ChB_Common::sanitizeInt( $val ) : 0 );
			} elseif ( $type === self::PARAM_TYPE_TEXT ) {
				$res = ( isset( $val ) ? ChB_Common::sanitizeText( $val ) : null );
				if ( $res && $explode ) {
					$res = explode( $explode, $res );
				}
			} elseif ( $type === self::PARAM_TYPE_KEYS ) {
				$res = ( isset( $val ) ? $val : null );
				if ( $res && $explode ) {
					$res = explode( $explode, $res );
				}
				if ( $res ) {
					$res = ChB_Common::sanitizeKeys( $res );
				}
			} else {
				$res = ( isset( $val ) ? ChB_Common::sanitizeText( $val ) : null );
				if ( $res && $explode ) {
					$res = explode( $explode, $res );
				}
			}

			if ( ! $res && isset( self::PARAM_TYPES[ $key ]['empty'] ) ) {
				$res = self::PARAM_TYPES[ $key ]['empty'];
			}

			if ( isset( self::PARAM_TYPES[ $key ]['filter'] ) ) {
				$res = $this->{self::PARAM_TYPES[ $key ]['filter']}( $res );
			}
		}

		$this->data[ $key ] = $res;

		return $this->data[ $key ];
	}

	public function setParam( $key, $val ) {
		$this->data[ $key ] = $val;
	}

	public function viewHas( $view ) {
		$views = $this->getParam( 'view' );

		return ( is_array( $views ) && in_array( $view, $views ) );
	}

	public static function argsViewHas( $args, $view ) {
		return ( ! empty( $args['view'] ) && in_array( $view, $args['view'] ) );
	}

	public function addView( $view ) {
		$views = $this->getParam( 'view' );

		if ( ! $views || ! is_array( $views ) ) {
			$views = [ $view ];
		} elseif ( ! in_array( $view, $views ) ) {
			$views[] = $view;
		} else {
			return;
		}

		$this->setParam( 'view', $views );
	}

	//pa_filter - product attributes filter
	private function hookPAFilter() {
		$pa_filter = [];
		/**
		 * FOR THE REVIEWERS: No, this cannot be done without iteration.
		 * 1. We do not know potential filtering parameter names in advance.
		 * 2. We cannot ask users to specify these names. That would be too complicated. Nobody would use it
		 * 3. This is called only on certain calls, and only in via REST API calls to plugin
		 */
		foreach ( [ &$this->PAYLOAD_PARAMS, &$this->PARAMS2MERGE, &$this->PARAMS2MERGE64 ] as $cur_pars ) {
			if ( $cur_pars ) {
				foreach ( $cur_pars as $key => $value ) {
					$is_pa = false;
					//for attributes which are taxonomies
					if ( substr( $key, 0, 3 ) === 'pa_' ) {
						$is_pa = true;
					}
					//for attributes which are NOT taxonomies
					if ( substr( $key, 0, 4 ) === '_pa_' ) {
						$is_pa = true;
						$key   = substr( $key, 4 );
					}
					if ( $is_pa ) {
						if ( is_array( $value ) ) {
							foreach ( $value as $val ) {
								if ( $val || is_numeric( $val ) ) {
									$pa_filter[ $key ][] = sanitize_text_field( $val );
								}
							}
						} elseif ( $value || is_numeric( $value ) ) {
							$pa_filter[ $key ] = sanitize_text_field( $value );
						}
					}
				}
			}
		}

		return $pa_filter;
	}


	private function hookCAFilter() {
		$ca_filter = [];
		/**
		 * FOR THE REVIEWERS: No, this cannot be done without iteration.
		 * 1. We do not know potential filtering parameter names in advance.
		 * 2. We cannot ask users to specify these names. That would be to complicated. Nobody would use it
		 * 3. This is called only on certain calls, and only in via REST API calls to plugin
		 */
		foreach ( [ &$this->PAYLOAD_PARAMS, &$this->PARAMS2MERGE, &$this->PARAMS2MERGE64 ] as $cur_pars ) {
			if ( $cur_pars ) {
				foreach ( $cur_pars as $key => $value ) {
					if ( strpos( $key, 'rrbot_cat' ) !== false ) {
						$ca_filter[ $key ] = sanitize_text_field( $value );
					}
				}
			}
		}

		return $ca_filter;
	}

	public static function makeDynamicBlockCallbackButton( ChatBot $ChB, $caption, $pars ) {
		list( $url, $payload ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, $pars );

		return [
			'type'    => 'dynamic_block_callback',
			'method'  => 'post',
			'url'     => $url,
			'payload' => $payload,
			'caption' => $caption,
		];
	}

	public static function makeDynamicBlockCallbackPars( ChatBot $ChB, $pars, $no_protocol = false, $output_action_url = false ) {
		$action_url      = '';
		$action_url_pars = ( $output_action_url && isset( $pars['task'] ) ? 'task=' . urlencode( $pars['task'] ) : '' );

		$url_pars[] = 'app_call=' . ChB_Constants::APP_CALL_WY;
		$url_pars[] = 'fb_user_id=' . urlencode( $ChB->user->fb_user_id );
		$url_pars[] = 'channel=' . urlencode( $ChB->user->channel );
		$url_pars[] = 'user_id=' . urlencode( $ChB->user->mc_user_id );
		$url_pars[] = 'page_id=' . urlencode( ChB_Settings()->auth->getMCPageID() );
		$url_pars[] = 'u=' . urlencode( ChB_Common::my_rand_string( 3 ) );//for passing check for duplicates

		$pars2url = [ 'task', 'user_id', 'page_id' ];
		foreach ( $pars2url as $key ) {
			if ( isset( $pars[ $key ] ) ) {
				$url_pars[] = urlencode( $key ) . '=' . urlencode( $pars[ $key ] );
				unset( $pars[ $key ] );
			}
		}

		$payload = [];
		if ( $pars ) {
			foreach ( $pars as $key => $value ) {
				if ( $value ) {
					if ( $key === 'pa_filter' ) {
						foreach ( $value as $pa_key => $pa_value ) {
							//if product attr doesn't start with 'pa_' ,
							// then adding '_pa_', which we will cut during parameter reading
							if ( substr( $pa_key, 0, 3 ) !== 'pa_' ) {
								$pa_key = '_pa_' . $pa_key;
							}
							if ( is_array( $pa_value ) ) {
								foreach ( $pa_value as $pa_val ) {
									$payload[ $pa_key ][] = $pa_val;
								}
							} else {
								$payload[ $pa_key ] = $pa_value;
							}
						}
					} elseif ( $key === 'ca_filter' ) {
						foreach ( $value as $ca_key => $ca_value ) {
							$payload[ $ca_key ] = $ca_value;
						}
					} elseif ( is_array( $value ) ) {
						if ( isset( self::PARAM_TYPES[ $key ]['explode'] ) ) {
							$payload[ $key ] = implode( self::PARAM_TYPES[ $key ]['explode'], $value );
						} else {
							foreach ( $value as $val ) {
								$payload[ $key ][] = $val;
							}
						}
					} else {
						$payload[ $key ] = $value;
					}
				}
			}
		}

		$payload['channel'] = $ChB->user->channel;

		// add_par is a param string that was passed to the current bot call
		// with the intention to concatenate this params to the output links
		if ( $ChB->getParam( 'add_par' ) ) {
			$url_pars[] = urldecode( $ChB->getParam( 'add_par' ) );
		}

		if ( $output_action_url ) {
			foreach ( $payload as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $val ) {
						$action_url_pars .= '&' . urlencode( $key ) . '[]=' . urlencode( $val );
					}
				} else {
					$action_url_pars .= '&' . urlencode( $key ) . '=' . urlencode( $value );
				}
			}

			if ( $action_url_pars && $action_url_pars[0] === '&' ) {
				$action_url_pars = substr( $action_url_pars, 1 );
			}
			$action_url = ChB_Settings()->ref_url_imglink . base64_encode( $action_url_pars );
		}

		$payload['wy_token'] = ChB_Settings()->auth->getWYToken();

		if ( ChB_Settings()->useNativeAPI( $ChB ) ) {
			$url = ( $no_protocol ? '' : 'https://' ) . ChB_Settings()->getDomainPath() . ChB_Constants::RRBOT_PATH . implode( '&', $url_pars );
		} else {
			$url = ( $no_protocol ? '' : 'https://' ) . ChB_Constants::WANY . '/wp-content/plugins/wany-chat-util/?' . implode( '&', $url_pars );
		}

		return $output_action_url ? [ $url, $payload, $action_url ] : [ $url, $payload ];
	}

}