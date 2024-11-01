<?php

namespace ChatBot;

class ChB_Events {
	const CHB_EVENT_ACTION_HOOK = 'rrbot_event_action_hook';

	const CHB_EVENT_SEND_RELATED_PRODUCTS = 'rrbot_send_rel_prods';
	const CHB_EVENT_SEND_RELATED_PRODUCTS1 = 'rrbot_send_rel_prods1';
	const CHB_EVENT_SEND_PRODUCTS_REMINDER = 'rrbot_send_prod_reminder';
	const CHB_EVENT_SEND_TRY_ON_DEMO = 'rrbot_send_try_on_demo';
	const CHB_EVENT_SEND_EXT_BOT_PRODUCTS = 'rrbot_send_ext_bot_prod';
	const CHB_EVENT_SEND_PROMO_REMINDER = 'rrbot_send_promo_reminder';
	const CHB_EVENT_SEND_PROMO_REMINDER_TEST = 'rrbot_send_promo_reminder_test';
	const CHB_EVENT_SEND_MANAGER_24H_REMINDER = 'rrbot_send_mng_24h_reminder';
	const CHB_EVENT_SEND_MANAGER_TALK_TO_HUMAN = 'rrbot_send_mng_tth';
	const CHB_EVENT_FREE_INPUT_TIMEOUT = 'rrbot_fi_timeout';

	const CHB_EVENT_VIDEO_ANALYTICS = 'rrbot_video_analytics';//todo1 fix video

	const CHB_EVENT_MC_API_POST = 'rrbot_mc_api_post';
	const CHB_EVENT_SEND_CONFIRMATION_TO_USER = 'rrbot_send_ordr_cnfrm';
	const CHB_EVENT_SEND_NOTIFICATIONS_ON_ORDER = 'rrbot_send_ordr_ntf';
	const CHB_EVENT_CLEAN_UP_CART = 'rrbot_clean_up_cart';

	const CHB_EVENT_PIXEL = 'rrbot_pixel_event';
	const CHB_EVENT_REFRESH_MC_BOT_FIELDS = 'rrbot_refresh_mc_bot_fields';

	const CHB_EVENT_CHECK_NOTIFICATIONS = 'rrbot_check_ntf';

	const CHB_EVENT_UPDATE_PLUGIN = 'rrbot_update_plugin';

	const WINDOW24H = 24 * 60 * 60;
	const WINDOW24H_MARGIN = 15 * 60; //15min before 24h
	const DYNAMIC_PARS_EXPIRE = 86400 * 7; //1 week

	const DEFAULT_DELAY = 60; //1m
	const DEFAULT_LAST_INTERACTION_OFFSET = 60; //1m
	const DEFAULT_LAST_LAUNCH_OFFSET = 15 * 24 * 60 * 60; //15 days

	const KVS_PREFIX_LAST_LAUNCH = 'LST_EVT';

	public static array $RECURRING_EVENTS = [
		self::CHB_EVENT_SEND_PROMO_REMINDER      => [
			'delay'                   => ( 24 * 60 - 15 ) * 60, //23h 45m
			'min_delay'               => 6 * 60 * 60, //6h
			'last_interaction_offset' => 60, //1m
			'loop'                    => true,
			'deadline_reminder'       => 5 * 60 * 60, //5h
			'deadline_reminder_alt'   => 3 * 60 * 60, //3h
		],
		self::CHB_EVENT_SEND_PROMO_REMINDER_TEST => [
			'delay'                   => 60, //1m
			'min_delay'               => 0,
			'last_interaction_offset' => 0,
			'loop'                    => false,
			'deadline_reminder'       => 5 * 60 * 60, //5h
			'deadline_reminder_alt'   => 3 * 60 * 60, //3h
		],

		self::CHB_EVENT_SEND_PRODUCTS_REMINDER => [
			'delay'                   => ( 23 * 60 - 15 ) * 60, //22h 45m
			'min_delay'               => 6 * 60 * 60, //6h
			'last_interaction_offset' => 60, //1m
			'loop'                    => true
		],

		self::CHB_EVENT_SEND_RELATED_PRODUCTS => [
			'delay'                   => self::DEFAULT_DELAY,
			'last_interaction_offset' => self::DEFAULT_LAST_INTERACTION_OFFSET,
			'loop'                    => false,
			'last_launch_offset'      => self::DEFAULT_LAST_LAUNCH_OFFSET
		],

		self::CHB_EVENT_SEND_TRY_ON_DEMO => [
			'delay'                    => self::DEFAULT_DELAY,
			'last_interaction_offset'  => self::DEFAULT_LAST_INTERACTION_OFFSET,
			'loop'                     => false,
			'last_launch_offset'       => self::DEFAULT_LAST_LAUNCH_OFFSET,
			'last_launch_offset_times' => 1,
			'pop_events'               => [ self::CHB_EVENT_SEND_RELATED_PRODUCTS ]
		],

		self::CHB_EVENT_SEND_EXT_BOT_PRODUCTS  => [
			'delay'                   => self::DEFAULT_DELAY,
			'last_interaction_offset' => self::DEFAULT_LAST_INTERACTION_OFFSET,
			'loop'                    => false,
			'last_launch_offset'      => self::DEFAULT_LAST_LAUNCH_OFFSET,
			'pop_events'              => [
				self::CHB_EVENT_SEND_RELATED_PRODUCTS,
				self::CHB_EVENT_SEND_TRY_ON_DEMO
			],
			'has_dynamic_pars'        => true
		],
		self::CHB_EVENT_SEND_RELATED_PRODUCTS1 => [
			'delay'                   => self::DEFAULT_DELAY,
			'last_interaction_offset' => self::DEFAULT_LAST_INTERACTION_OFFSET,
			'loop'                    => false,
			'pop_events'              => [
				self::CHB_EVENT_SEND_RELATED_PRODUCTS,
				self::CHB_EVENT_SEND_TRY_ON_DEMO,
				self::CHB_EVENT_SEND_EXT_BOT_PRODUCTS
			]
		],

		self::CHB_EVENT_VIDEO_ANALYTICS => [
			'delay'                   => 60 * 10, //10m
			'last_interaction_offset' => 0,
			'loop'                    => false
		],

		self::CHB_EVENT_SEND_MANAGER_24H_REMINDER => [
			'delay'                   => ( 24 * 60 - 15 ) * 60, //23h 45m
			'min_delay'               => 0,
			'last_interaction_offset' => ( 24 * 60 - 25 ) * 60, //23h 35m
			'loop'                    => true
		],

		self::CHB_EVENT_CHECK_NOTIFICATIONS => [
			'every_hour' => 10 * 60, // at 10 minutes every hour
			'loop'       => true
		]
	];

	public static function init() {
		add_action( ChB_Events::CHB_EVENT_ACTION_HOOK, [ 'ChatBot\ChB_Events', 'eventHandler' ], 10, 2 );
	}

	public static function scheduleManualEvents( $event_names ) {
		$now = time();
		foreach ( $event_names as $event_name ) {
			self::scheduleSingleRecurringEvent( false, $event_name, null, [], $now, null );
		}
	}

	public static function scheduleRecurringEvents( ChatBot $ChB ) {

		if ( $ChB->user->fb_user_id === ChB_Common::EXT_USER ) {
			return;
		}

		//scheduling order is important for pop_events
		//popping and pop_events should have same list of params

		$discount4user_items = $ChB->promo->getDiscount4UserReminderData();
		foreach ( $discount4user_items as $discount4user_item ) {
			$discount4user_item['fb_user_id'] = $ChB->user->fb_user_id;

			$events_args[] = [
				'event_name' => self::CHB_EVENT_SEND_PROMO_REMINDER,
				'args'       => $discount4user_item
			];

			if ( $discount4user_item['is_newly_created'] && ChB_Roles::userHasRole( ChB_Roles::ROLE_PROMO_MANAGER, $ChB->user->fb_user_id ) ) {
				$events_args[] = [
					'event_name' => self::CHB_EVENT_SEND_PROMO_REMINDER_TEST,
					'args'       => $discount4user_item
				];
			}
		}

		if ( $ChB->task === 'manychat_cat_openProduct' ) {

			if ( ! ChB_Settings()->getParam( 'do_not_send_related_products' ) ) {
				$events_args[] = [
					'event_name' => self::CHB_EVENT_SEND_RELATED_PRODUCTS,
					'args'       => [ 'fb_user_id' => $ChB->user->fb_user_id ]
				];
			}

			$events_args[] = [
				'event_name' => self::CHB_EVENT_VIDEO_ANALYTICS,
				'args'       => []
			];
		}

		if ( ChB_Settings()->getTryOn( 'is_on' ) ) {
			if ( $ChB->task === 'manychat_cat_getProductBrands' ||
			     $ChB->task === 'manychat_cat_getProductCats' ||
			     $ChB->task === 'manychat_cat_getProducts' ||
			     $ChB->task === 'manychat_cat_openProduct' ||
			     $ChB->task === 'manychat_cat_getProductsShopMore4Cart' ) {
				//scheduling only if user hasn't uploaded selfie yet
				if ( empty( ChB_TryOn::getUserPhoto4TryOn( $ChB->user, true, false ) ) ) {
					$events_args[] = [
						'event_name' => self::CHB_EVENT_SEND_TRY_ON_DEMO,
						'args'       => [ 'fb_user_id' => $ChB->user->fb_user_id ]
					];
				}
			}
		}

		if ( ! empty( ChB_Settings()->getParam( 'ext_bots' )['on'] ) ) {
			if ( $ChB->task === 'manychat_cat_openProduct' ) {
				$events_args[] = [
					'event_name'   => self::CHB_EVENT_SEND_EXT_BOT_PRODUCTS,
					'args'         => [
						'fb_user_id'       => $ChB->user->fb_user_id,
						'has_dynamic_pars' => true
					],
					'dynamic_pars' => [ 'product_id' => $ChB->getParam( 'product_id' ) ]
				];
			}
		}

		if ( $ChB->task === 'manychat_cat_openProduct' && ! ChB_Settings()->getParam( 'do_not_send_related_products' ) ) {
			$events_args[] = [
				'event_name' => self::CHB_EVENT_SEND_RELATED_PRODUCTS1,
				'args'       => [ 'fb_user_id' => $ChB->user->fb_user_id ]
			];
		}

		if ( ! ChB_Settings()->getParam( 'do_not_send_recommended_products' ) &&
		     ( $ChB->task === 'manychat_cat_getProductBrands' ||
		       $ChB->task === 'manychat_cat_getProductCats' ||
		       $ChB->task === 'manychat_cat_getProducts' ||
		       $ChB->task === 'manychat_cat_openProduct' ||
		       $ChB->task === 'manychat_cat_getProductsShopMore4Cart' ) ) {
			$events_args[] = [
				'event_name' => self::CHB_EVENT_SEND_PRODUCTS_REMINDER,
				'args'       => [ 'fb_user_id' => $ChB->user->fb_user_id ]
			];
		}

		if ( in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'managers2notify_on_tth' ) ) ||
		     in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'managers2notify' ) ) ||
		     in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'managers2notify_on_orders' ) ) ||
		     in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'managers2notify_on_completed_orders' ) ) ) {
			$events_args[] = [
				'event_name' => self::CHB_EVENT_SEND_MANAGER_24H_REMINDER,
				'args'       => [ 'fb_user_id' => $ChB->user->fb_user_id ]
			];
		}

		$now_ts              = time();
		$last_interaction_ts = $now_ts; //IMPORTANT: here we are supposing that the last interaction is now
		if ( ! empty( $events_args ) ) {
			foreach ( $events_args as $event_args ) {
				if ( ! empty( $event_args['args']['has_dynamic_pars'] ) && ! empty( ChB_Settings()->kvs ) ) {
					//dynamic pars will be overwritten every time,
					// even if current instance of event won't be scheduled
					$key = ChB_Settings()->salt . $event_args['event_name'] . $ChB->user->fb_user_id;
					$val = json_encode( $event_args['dynamic_pars'] );
					ChB_Settings()->kvs->setex( $key, self::DYNAMIC_PARS_EXPIRE, $val );
				}
				self::scheduleSingleRecurringEvent( false, $event_args['event_name'], $ChB->user->fb_user_id, $event_args['args'], $now_ts, $last_interaction_ts );
			}
		}
	}

	private static function scheduleSingleRecurringEvent( $is_reschedule, $event_name, $fb_user_id, $args, $now_ts, $last_interaction_ts, $delay = null ) {

		if ( empty( self::$RECURRING_EVENTS[ $event_name ] ) ) {
			return;
		}
		$recurring_event_pars = self::$RECURRING_EVENTS[ $event_name ];

		$log_prefix = 'scheduleSingleRecurringEvent: ' . ( $is_reschedule ? 'RE ' : '' ) . $event_name . ' ' . $fb_user_id;

		$wp_event_args = [ $event_name, json_encode( $args ) ];
		$event         = wp_get_scheduled_event( ChB_Events::CHB_EVENT_ACTION_HOOK, $wp_event_args );
		if ( $event ) {
			ChB_Common::my_log( $log_prefix . ' already scheduled to "' . ChB_Common::timestamp2DateTime( $event->timestamp ) . '"' );

			return;
		}
		if ( ! empty( $recurring_event_pars['pop_events'] ) ) {
			$args4search = $args;
			foreach ( $recurring_event_pars['pop_events'] as $pop_event_name ) {
				//this is just for searching - syncing has_dynamic_pars for popping and popped events
				if ( isset( self::$RECURRING_EVENTS[ $pop_event_name ]['has_dynamic_pars'] ) ) {
					$args4search['has_dynamic_pars'] = self::$RECURRING_EVENTS[ $pop_event_name ]['has_dynamic_pars'];
				} else {
					unset( $args4search['has_dynamic_pars'] );
				}

				$wp_event_args = [ $pop_event_name, json_encode( $args4search ) ];
				$event         = wp_get_scheduled_event( ChB_Events::CHB_EVENT_ACTION_HOOK, $wp_event_args );
				if ( $event ) {
					ChB_Common::my_log( $log_prefix . ' cannot schedule because of pop_event ' . $pop_event_name . ' already scheduled to "' . ChB_Common::timestamp2DateTime( $event->timestamp ) . '"' );

					return;
				}
			}
		}
		$last_launch_offset = ! empty( $recurring_event_pars['last_launch_offset'] ) ? $recurring_event_pars['last_launch_offset'] : 0;
		if ( ! empty( $last_launch_offset ) ) {
			$last_launch_ts = self::getEventLastLaunch( $event_name, $fb_user_id );
			if ( $now_ts - $last_launch_ts < $last_launch_offset ) {
				ChB_Common::my_log( $log_prefix . ' cannot schedule because of last_launch_offset (' . ( $now_ts - $last_launch_ts ) . ' < ' . $last_launch_offset . ')' );

				return;
			}
		}

		if ( ! empty( $recurring_event_pars['every_hour'] ) ) {
			$reschedule_ts = ChB_Common::getCurrentHourBeginningTS() + $recurring_event_pars['every_hour'];
			if ( $reschedule_ts < $now_ts ) {
				$reschedule_ts += 3600;
			}
		} else {
			$min_delay = ! empty( $recurring_event_pars['min_delay'] ) ? $recurring_event_pars['min_delay'] : 0;
			if ( empty( $delay ) ) {
				$delay = $recurring_event_pars['delay'];
			}

			$reschedule_ts = $now_ts + $delay;
			$window_24h_ts = $last_interaction_ts + self::WINDOW24H;
			if ( $reschedule_ts > $window_24h_ts ) {
				//shrinking delay to fit into 24h window
				$reschedule_ts = $window_24h_ts - self::WINDOW24H_MARGIN;
				if ( $reschedule_ts - $now_ts < $min_delay ) {
					ChB_Common::my_log( $log_prefix . ' cannot schedule because of min_delay (' . ( $reschedule_ts - $now_ts ) . ' < ' . $min_delay . ')' );

					return;
				}
			}

			if ( ! empty( $args['deadline'] ) && ! empty( $recurring_event_pars['deadline_reminder'] ) ) {

				if ( $reschedule_ts > $args['deadline'] - $recurring_event_pars['deadline_reminder'] ) {
					$reschedule_ts = $args['deadline'] - $recurring_event_pars['deadline_reminder'];

					if ( $reschedule_ts - $now_ts < $min_delay ) {
						if ( ! empty( $recurring_event_pars['deadline_reminder_alt'] ) ) {
							$reschedule_ts = $args['deadline'] - $recurring_event_pars['deadline_reminder_alt'];
							if ( $reschedule_ts - $now_ts < $min_delay ) {
								ChB_Common::my_log( $log_prefix . ' deadline2. Cannot schedule because of min_delay' );

								return;
							}
						} else {
							ChB_Common::my_log( $log_prefix . ' deadline1. Cannot schedule because of min_delay' );

							return;
						}
					}

				}
			}
		}

		//scheduling
		$wp_event_args = [ $event_name, json_encode( $args ) ];
		if ( ! wp_get_scheduled_event( ChB_Events::CHB_EVENT_ACTION_HOOK, $wp_event_args ) ) {
			wp_schedule_single_event( $reschedule_ts, ChB_Events::CHB_EVENT_ACTION_HOOK, $wp_event_args );
			ChB_Common::my_log( $wp_event_args, true, $log_prefix . ' scheduling to "' . ChB_Common::timestamp2DateTime( $reschedule_ts ) . '"' );
		} else {
			ChB_Common::my_log( $wp_event_args, true, 'QQQ scheduleRecurringEvents. NO SCHEDULING. EXISTS' );
		}
	}

	public static function eventHandler( $event_name, $args ) {
		chb_load();

		if ( ! is_array( $args ) ) {
			$args = json_decode( $args, true );
		}

		$fb_user_id = ! empty( $args['fb_user_id'] ) ? $args['fb_user_id'] : null;
		if ( $fb_user_id == ChB_Common::EXT_USER ) {
			return;
		}

		$user       = null;
		$fb_user_id = null;
		if ( ! empty( $args['user'] ) ) {
			$user       = $args['user'];
			$fb_user_id = $user->fb_user_id;
		} elseif ( ! empty( $args['fb_user_id'] ) ) {
			$user       = ChB_User::initUser( $args['fb_user_id'] );
			$fb_user_id = $user->fb_user_id;
		}

		//1. CHECKING LAST INTERACTION
		$now                     = new \DateTime( 'now', ChB_Settings()->timezone );
		$now_ts                  = $now->getTimestamp();
		$last_interaction_format = null;
		$last_interaction_ts     = null;
		$recurring_event_pars    = isset( self::$RECURRING_EVENTS[ $event_name ] ) ? self::$RECURRING_EVENTS[ $event_name ] : null;
		if ( $recurring_event_pars !== null && $fb_user_id !== null ) {

			list( $last_interaction_ts, $last_interaction_format ) = $user->getLastInteraction();
			if ( ! $last_interaction_ts ) {
				ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' cannot get last interaction' );

				return;
			}

			$diff = $now_ts - $last_interaction_ts;
			ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' now="' . $now->format( 'Y-m-d H:i:s' ) . '" li="' . $last_interaction_format . '" diff=' . $diff );

			//Checking 24h window
			if ( $diff >= self::WINDOW24H ) {
				ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' out of 24h' );

				return;
			}

			//Checking if last interaction was not too recent
			if ( $diff < $recurring_event_pars['last_interaction_offset'] ) {
				//rescheduling
				$delay = $recurring_event_pars['last_interaction_offset'] - $diff;
				ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' rescheduling because of li_offset' );
				self::scheduleSingleRecurringEvent( true, $event_name, $fb_user_id, $args, $now_ts, $last_interaction_ts, $delay );

				return;
			}

			$last_launch_offset = ! empty( $recurring_event_pars['last_launch_offset'] ) ? $recurring_event_pars['last_launch_offset'] : 0;
		}

		if ( $user ) {
			$ChB = ChatBot::openTempChatBotSession( $user );
			if ( ! $ChB ) {
				ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' user is not empty, but cannot init ChatBot' );

				return;
			}
		} else {
			$ChB = null;
		}

		//2. EVENT PROCESSING
		$dynamic_pars = null;
		if ( ! empty( $args['has_dynamic_pars'] ) && ! empty( ChB_Settings()->kvs ) ) {
			$key          = ChB_Settings()->salt . $event_name . $fb_user_id;
			$dynamic_pars = ChB_Settings()->kvs->get( $key );
			if ( ! empty( $dynamic_pars ) ) {
				$dynamic_pars = json_decode( $dynamic_pars, true );
			}
			if ( empty( $dynamic_pars ) ) {
				$dynamic_pars = false;
			}
		}

		if ( $dynamic_pars === false ) {
			ChB_Common::my_log( 'eventHandler: ' . $event_name . ' ' . $fb_user_id . ' not found dynamic pars' );
		} elseif ( $event_name === self::CHB_EVENT_MC_API_POST ) {
			foreach ( $args['actions'] as $action ) {
				$fields   = $action['fields'];
				$endpoint = $action['endpoint'];
				ChB_ManyChat::sendPost2ManyChat( $endpoint, $fields, $ChB );
			}
		} elseif ( $event_name === self::CHB_EVENT_SEND_NOTIFICATIONS_ON_ORDER ) {
			ChB_FLOW_Manager::sendNotificationsOnOrder( $args['order_id'], $args['new_status'] );
		} elseif ( $event_name === self::CHB_EVENT_CLEAN_UP_CART ) {
			if ( $bot_user = ChB_User::initUserByWPUserID( $args['bot_wp_user_id'] ) ) {
				$cart = new ChB_Cart( $bot_user->wp_user_id, $bot_user );
				$cart->removeItemsFromCartByOrder( $args['order_id'] );
			}
		} elseif ( $event_name === self::CHB_EVENT_PIXEL ) {
			ChB_Pixel::sendEvent2Pixel( $args );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_TRY_ON_DEMO ) {
			ChB_FLOW_TryOn::sendTryOnDemo( $ChB );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_RELATED_PRODUCTS || $event_name === ChB_Events::CHB_EVENT_SEND_RELATED_PRODUCTS1 ) {
			ChB_FLOW_Catalogue::sendRelatedProducts( $ChB );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_EXT_BOT_PRODUCTS ) {
			ChB_FLOW_Catalogue::sendExtBotProducts( $ChB, $dynamic_pars['product_id'] );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_PROMO_REMINDER || $event_name === ChB_Events::CHB_EVENT_SEND_PROMO_REMINDER_TEST ) {
			ChB_FLOW_Promo::sendPromoReminder( $ChB, $args );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_PRODUCTS_REMINDER ) {
			ChB_FLOW_Catalogue::sendProductsReminder( $ChB );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_REFRESH_MC_BOT_FIELDS ) {
			ChB_Settings::refreshMCBotFields();
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_CONFIRMATION_TO_USER ) {
			ChB_FLOW_MyOrders::sendNotificationToCustomerOnOrderConfirmation( $args['order_id'] );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_MANAGER_TALK_TO_HUMAN ) {
			ChB_FLOW_Manager::notifyManagersToTalkToHuman( $args['ChB_Customer'] );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_SEND_MANAGER_24H_REMINDER ) {
			ChB_FLOW_Manager::sendManager24HReminder( $ChB );
		} elseif ( $event_name === ChB_Events::CHB_EVENT_VIDEO_ANALYTICS ) {
			ChB_Analytics::getVideoAnalytics();
		} elseif ( $event_name === ChB_Events::CHB_EVENT_CHECK_NOTIFICATIONS ) {
			ChB_Notifications::checkNotifications();
		} elseif ( $event_name === ChB_Events::CHB_EVENT_UPDATE_PLUGIN ) {
			if ( isset( $args['plugin_id'] ) ) {
				do_action( 'wany_hook_plugin_update_' . $args['plugin_id'] );
			}
		} elseif ( $event_name === ChB_Events::CHB_EVENT_FREE_INPUT_TIMEOUT ) {
			ChB_FLOW_Common::freeInputCallback( $user, null, 'timeout', $args['fi_id'] );
		}

		if ( ! empty( $last_launch_offset ) ) {
			self::setEventLastLaunch( $event_name, $fb_user_id );
		}

		//3. RESCHEDULING
		if ( ! empty( $recurring_event_pars['loop'] ) ) {
			self::scheduleSingleRecurringEvent( true, $event_name, $fb_user_id, $args, $now_ts, $last_interaction_ts );
		}

		if ( $ChB ) {
			ChatBot::closeTempChatBotSession();
		}
	}

	public static function scheduleSingleEvent( $event_name, array $args, $delay_seconds, $suppress_log = false ) {

		wp_schedule_single_event( time() + $delay_seconds, ChB_Events::CHB_EVENT_ACTION_HOOK, [
			$event_name,
			json_encode( $args )
		] );
		if ( ! $suppress_log ) {
			ChB_Common::my_log( $args, true, 'scheduleSingleEvent ' . $event_name . ' delay=' . $delay_seconds );
		}
	}

	public static function scheduleSingleEventOnShutdown( ChatBot $ChB, $event_name, array $args ) {
		$ChB->scheduled_events[] = [ 'event_name' => $event_name, 'event_args' => $args ];
	}

	public static function setEventLastLaunch( $event_name, $fb_user_id ) {
		$MAX_LEN = 10;
		$key     = ChB_Settings()->salt . self::KVS_PREFIX_LAST_LAUNCH . '#' . $event_name . '#' . $fb_user_id;
		$val     = ChB_Settings()->kvs->get( $key );

		if ( empty( $val ) ) {
			$res = [ 'ts' => [ time() ] ];
		} else {
			$res = json_decode( $val, true );
			if ( is_array( $res['ts'] ) ) {
				$res['ts'][] = time();
			} else {
				$res['ts'] = [ intval( $res['ts'] ), time() ];
			}

			if ( count( $res['ts'] ) > $MAX_LEN ) {
				$res['ts'] = array_slice( $res['ts'], count( $res['ts'] ) - $MAX_LEN, $MAX_LEN );
			}
		}
		ChB_Settings()->kvs->set( $key, json_encode( $res ) );
	}

	public static function getEventLastLaunch( $event_name, $fb_user_id ) {
		$key = ChB_Settings()->salt . self::KVS_PREFIX_LAST_LAUNCH . '#' . $event_name . '#' . $fb_user_id;
		$val = ChB_Settings()->kvs->get( $key );
		if ( empty( $val ) ) {
			return 0;
		}

		$offset = ( empty( self::$RECURRING_EVENTS[ $event_name ]['last_launch_offset_times'] ) ? 1 : self::$RECURRING_EVENTS[ $event_name ]['last_launch_offset_times'] );
		$ts     = json_decode( $val, true )['ts'];

		if ( is_array( $ts ) ) {
			return ( count( $ts ) >= $offset ? $ts[ count( $ts ) - $offset ] : 0 );
		} else //legacy
		{
			return ( $offset === 1 ? $ts : 0 );
		}
	}

	public static function eventIsScheduled( $event_name, array $args ) {
		$wp_event_args = [ $event_name, json_encode( $args ) ];

		return ! empty( wp_get_scheduled_event( ChB_Events::CHB_EVENT_ACTION_HOOK, $wp_event_args ) );
	}
}
