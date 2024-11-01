<?php

namespace ChatBot;

class ChB_WebHookProcess {
	public static function processWebHook( $channel, array &$message_ob ) {
		try {
			if ( isset( $message_ob['entry'] ) ) {
				foreach ( $message_ob['entry'] as &$entry_node ) {
					foreach ( [ 'messaging', 'standby' ] as $messaging_key ) {
						if ( isset( $entry_node[ $messaging_key ] ) ) {
							foreach ( $entry_node[ $messaging_key ] as &$messaging_node ) {
								if ( ! empty( $messaging_node['message']['is_echo'] ) ) {
									self::processEchoMessage( $channel, $messaging_node );
								} else {
									if ( ! isset( $messaging_node['sender']['id'] ) ) {
										return false;
									}
									$subscriber_id = $messaging_node['sender']['id'];

									if ( isset( $messaging_node['message']['attachments'][0]['payload']['product']['elements'][0]['retailer_id'] ) ) {
										//Сообщение из shop-а
										self::processMessageFromFBShop( $subscriber_id, $channel, $messaging_node );
									} else {
										$is_image = ( isset( $messaging_node['message']['attachments'][0]['type'] ) &&
										              ( $messaging_node['message']['attachments'][0]['type'] == 'image' ) );

										if ( ! $is_image ) {
											$text = isset( $messaging_node['message']['text'] ) ? $messaging_node['message']['text'] : '';
										} elseif ( $channel === ChB_Constants::CHANNEL_IG ) {
											$is_image = false;
											$text     = 'IMAGE';
										}

										if ( ! $is_image ) {
											self::processTextMessage( $subscriber_id, $channel, $text, $messaging_node );
										} elseif ( ! empty( $messaging_node['message']['attachments'][0]['payload']['url'] ) ) {
											$image_url = $messaging_node['message']['attachments'][0]['payload']['url'];
											self::processImageMessage( $subscriber_id, $channel, $image_url );
										}
									}
								}
							}
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'processWebHook: channel=' . $channel . ' ' . $e->getMessage() );
		}

		return true;
	}

	public static function processEchoMessage( $channel, &$messaging_node ) {
		if ( $channel === ChB_Constants::CHANNEL_FB && ! empty( $messaging_node['recipient']['id'] ) ) {
			$user = ChB_User::initUser( $messaging_node['recipient']['id'], $channel );
			if ( $user ) {
				ChB_Notifications::registerAnswer( $user );
			}
		}
	}

	public static function processTextMessage( $subscriber_id, $channel, $text, $messaging_node ) {
		$user = ChB_User::initUser( $subscriber_id, $channel );
		if ( ! $user ) {
			return;
		}
		$user->setLastInteraction();

		if ( ! self::processKeywords( $user, $text, $messaging_node ) ) {
			if ( ! ChB_FLOW_Common::processFreeInput( $user, $text ) ) {
				if ( empty ( $messaging_node['message']['quick_reply'] ) ) {
					self::processSizeQuestion( $user, $text );
					self::processMessage4Notification( $user, $text );
				}
			}
		}
	}

	public static function processKeywords( ChB_User $user, $text, $messaging_node ) {

		$ltext  = trim( strtolower( $text ) );
		$PARAMS = null;
		if ( substr( $text, 0, strlen( ChB_Constants::KW_CONNECT_SHOP_MANAGER ) ) === ChB_Constants::KW_CONNECT_SHOP_MANAGER ) {
			$PARAMS = [ 'task' => 'manychat_manager_connectShopManager', 'val' => $text ];
		} elseif ( $ltext === ChB_Constants::KW_NTF_MENU ) {
			$PARAMS = [ 'task' => 'manychat_manager_sendManageNotificationsMenu' ];
		} elseif ( $ltext === ChB_Constants::KW_MNG ) {
			$PARAMS = [ 'task' => 'manychat_manager_sendManagerMenu' ];
		} elseif ( $ltext === ChB_Constants::KW_NTF ) {
			$PARAMS = [ 'task' => 'manychat_manager_getAllUnansweredNotifications' ];
		} elseif ( $ltext === ChB_Constants::KW_HELP ) {
			$PARAMS = [ 'task' => 'manychat_cmn_getHelpMenu' ];
		} elseif ( $ltext === ChB_Constants::KW_LANG ) {
			$PARAMS = [ 'task' => 'manychat_lng_getLangMenu' ];
		} elseif ( $ltext === ChB_Constants::KW_CART ) {
			$PARAMS = [ 'task' => 'manychat_order_openCart' ];
		} elseif ( $ltext === ChB_Constants::KW_ORDERS ) {
			$PARAMS = [ 'task' => 'manychat_myorders_getMyOrders' ];
		} elseif ( $ltext === ChB_Constants::KW_TEST ) {
			$PARAMS = [ 'task' => 'manychat_cmn_getTestFlow' ];
		} elseif ( $ltext === ChB_Constants::KW_ORDER ) {
			$PARAMS = [ 'task' => 'manychat_myorders_sendFindOrderInput' ];
		} elseif ( substr( $ltext, 0, ChB_Constants::KW_DBG_LEN ) === ChB_Constants::KW_DBG ) {
			$PARAMS = [
				'task' => 'manychat_cmn_sendTextMessage',
				'val'  => ChB_Debug::processDebugKeyword( $user, substr( $ltext, ChB_Constants::KW_DBG_LEN ), $messaging_node )
			];
		} elseif ( $ltext === ChB_Constants::KW_AC_TEST ) {
			if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $user ) || ChB_Debug::userIsSuperDebug( $user ) ) {
				ChB_WooAbandonedCart::fireAbandonedCartTrigger( 1, $user );
			}

			return true;
		} elseif ( $ltext === ChB_Constants::KW_RMKT_TEST ) {
			if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $user ) || ChB_Debug::userIsSuperDebug( $user ) ) {
				ChB_WooRemarketing::fireRemarketingTrigger( 1, $user );
			}

			return true;
		}

		if ( $PARAMS ) {
			ChB_Common::my_log( 'processKeywords task=' . $PARAMS['task'] );
			$ChB      = ChatBot::openTempChatBotSession( $user, $PARAMS );
			$response = ChatBot::run( $ChB );
			if ( $response ) {
				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $response ], $ChB );
			}
			ChatBot::closeTempChatBotSession();

			return true;
		}

		return false;
	}

	public static function processMessage4Notification( ChB_User $user, $text ) {

		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $user->fb_user_id ) ) {
			return;
		}

		ChB_Notifications::registerQuestion( $user, false, false );

		//blocking promo reminders for user
		$user->setBlock4UserReminders( ChB_Common::SECONDS_12H );

		//quitting if notifications from user to managers are on pause
		if ( $user->getBlock4ManagersNotifications() ) {
			return;
		}
		//otherwise setting a pause for notifications (and sending current notification)
		$user->setBlock4ManagersNotifications( ChB_Common::MANAGER_NOTIFICATION_PAUSE_MINUTES * 60 );

		$display_name = $user->getUserDisplayName();
		$text         = '🔔 NOTIFICATION [' . strtoupper( $user->channel ) . '] 🔔' . chr( 10 ) . 'message from "' . $display_name . '" (' . $user->fb_user_id . ')' . "\n👉 \"" . $text . '"';

		foreach ( ChB_Settings()->getParam( 'managers2notify' ) as $manager_id ) {
			if ( ChB_Settings()->getParam( 'notify_via_mc_flow' ) ) {
				ChB_FLOW_Manager::
				sendNotificationViaManyChatFlow( $manager_id, $text, $user->mc_user_id );
			} else {
				$manager_user = ChB_User::initUser( $manager_id );
				if ( ! $manager_user || ! $manager_user->lastInteractionIsInside24H() ) {
					continue;
				}
				$ChB_Manager = ChatBot::openTempChatBotSession( $manager_user );

				$ChB_Manager->setParam( 'cus_fb_user_id', $user->fb_user_id );
				$response = ChB_FLOW_Manager::sendNotificationMenu( $ChB_Manager, [
					[
						'type' => 'text',
						'text' => $text
					]
				] );
				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $response ], $ChB_Manager );
				ChatBot::closeTempChatBotSession();
			}
		}
	}

	public static function processSizeQuestion( ChB_User $user, $text ) {
		if ( ! $text ) {
			return;
		}
		$size_recognition = ChB_Settings()->getParam( 'size_recognition' );
		if ( empty( $size_recognition['is_on'] ) || empty( $size_recognition['url'] )
		     || ! ChB_Analytics::getLastUnansweredProduct( $user->fb_user_id ) ) {
			return;
		}

		$fields = [
			'field_name'  => ChB_ManyChat::CF_DF_SizeEntity,
			'field_value' => ChB_Common::EMPTY_TEXT
		];
		$ChB    = ChatBot::openTempChatBotSession( $user );
		if ( ! $ChB ) {
			return;
		}
		ChB_ManyChat::sendPost2ManyChat( '/fb/subscriber/setCustomFieldByName', $fields, $ChB );

		ChB_Common::sendPost( $size_recognition['url'] . '?bot_id=' . ChB_Settings()->getDomainPath(),
			[
				'id'              => $user->mc_user_id,
				'last_input_text' => $text,
				'mc_api_token'    => ChB_Settings()->auth->getMCAppToken(),
				'callback_flow'   => ChB_Settings()->getParam( 'flow_df_response' )
			] );

		ChatBot::closeTempChatBotSession();
	}

	public static function processImageMessage( $subscriber_id, $channel, $image_url ) {
		$user = ChB_User::initUser( $subscriber_id, $channel );
		if ( ! $user ) {
			return null;
		}
		$user->setLastInteraction();

		ChB_Settings()->setUserSettings( $user );
		if ( ! ChB_Settings()->getImageDetection( 'is_on' ) && ! ChB_Settings()->getTryOn( 'is_on' ) ) {
			return null;
		}

		fastcgi_finish_request();
		$user->setBlock4UserReminders( $subscriber_id );

		ChB_Common::my_log( $subscriber_id . ' ' . $image_url );
		$image_path = ChB_Common::downloadURL( $image_url );

		if ( ! empty( ChB_Settings()->getTryOn( 'is_on' ) ) && ChB_TryOn::getUserTryOnState( $user ) ) {
			//try on
			ChB_Common::sendTextMessageWithFBAPI( $subscriber_id, ChB_Lang::translate( ChB_Lang::LNG0139 ), true );
			$event_tags = ChB_Analytics::getLastTryOnTags( $subscriber_id );
			ChB_Analytics::registerEvent( ChB_Analytics::EVENT_TRY_ON_IMAGE_SENT, [ 'tags' => $event_tags ], $subscriber_id );
			ChB_FLOW_TryOn::saveUserPhoto( $user, $image_path );
		} elseif ( ChB_Common::utilIsDefined() && ChB_Settings()->getImageDetection( 'is_on' ) ) {
			//yolo
			$ChB_Customer = ChatBot::openTempChatBotSession( $user );
			ChB_Common::sendTextMessageWithFBAPI( $subscriber_id, ChB_Lang::translate( ChB_Lang::LNG0129 ), true );

			list( $classes, $data ) = ChB_Lab::detectImageWithYolo( $image_path );

			if ( $classes ) {
				foreach ( $classes as $class ) {
					$ChB_Customer->setParam( 'product_ids', [ $class[0] ] );
				}
			}

			$event_pars = [ 'tags' => [ ChB_Analytics::TAG_SEND_IMG ] ];
			if ( ! empty( $ChB_Customer->getParam( 'product_ids' )[0] ) ) {
				$event_pars['pr_id'] = $ChB_Customer->getParam( 'product_ids' )[0];
			}
			ChB_Analytics::registerEvent( ChB_Analytics::EVENT_IMAGE_SENT, $event_pars, $subscriber_id );

			if ( ! empty( $ChB_Customer->getParam( 'product_ids' )[0] ) ) {
				$ChB_Customer->setParam( 'product_id', $ChB_Customer->getParam( 'product_ids' )[0] );
				$messages = [ [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0130 ) ] ];

				ChB_Analytics::addEvent( $ChB_Customer->events, ChB_Analytics::EVENT_TTH, [ ChB_Analytics::TAG_SEND_IMG ], [ 'pr_id' => $ChB_Customer->getParam( 'product_id' ) ] );
				ChB_Analytics::registerEvent( ChB_Analytics::EVENT_TTH, [
					'pr_id' => $ChB_Customer->getParam( 'product_id' ),
					'tags'  => [ ChB_Analytics::TAG_SEND_IMG ]
				], $subscriber_id );
				$trailing_messages = ChB_FLOW_Catalogue::getProductExplainMessages( $ChB_Customer );

				ChB_Analytics::addEvent( $ChB_Customer->events, ChB_Analytics::EVENT_LIST_PRODUCTS, [ ChB_Analytics::TAG_SEND_IMG ] );
				ChB_Analytics::registerEvent( ChB_Analytics::EVENT_LIST_PRODUCTS, [ 'tags' => [ ChB_Analytics::TAG_SEND_IMG ] ], $subscriber_id );
				ChB_FLOW_Catalogue::sendSelectedProducts( $ChB_Customer, false, null, null, null, $ChB_Customer->getParam( 'product_ids' ), $messages, $trailing_messages );
			} else {
				ChB_Common::sendTextMessageWithFBAPI( $subscriber_id, ChB_Lang::translate( ChB_Lang::LNG0153 ), true );
			}

			ChB_FLOW_Manager::notifyManagersToTalkToHuman( $ChB_Customer, $data );
			$user->unsetBlock4UserReminders();
			ChatBot::closeTempChatBotSession();
		}
	}

	public static function processMessageFromFBShop( $subscriber_id, $channel, &$messaging_node ) {

		sleep( 1 );//giving manychat some time to create user
		$ChB = null;
		try {
			$product_is_ok = false;
			$product_id    = null;

			if ( ! empty( $messaging_node['message']['attachments'][0]['payload']['product']['elements'][0]['retailer_id'] ) ) {
				$product_id    = $messaging_node['message']['attachments'][0]['payload']['product']['elements'][0]['retailer_id'];
				$product_is_ok = true;
			}

			//Установка скидки
			if ( $product_is_ok && isset( $messaging_node['message']['text'] ) ) {
				$text = trim( $messaging_node['message']['text'] );
				if ( strrpos( strtolower( $text ), 'sale' ) === 0 ) {
					if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_PRODUCT_DISCOUNTS, $subscriber_id ) ) {
						ChB_Promo::setPromo4ProductByText( $text, $product_id );
					}
				}
			}

			//Message with product_id
			if ( ChB_Order::getAvailableStock( $product_id ) ) {

				$user = ChB_User::initUser( $subscriber_id, $channel );
				if ( ! $user ) {
					return false;
				}
				$user->setLastInteraction();
				$ChB = ChatBot::openTempChatBotSession( $user );
				if ( ! $ChB ) {
					return false;
				}

				if ( ChB_Settings()->auth->connectionIsDirect() ) {
					$ChB->setParam( 'product_id', $product_id );
					$response = ChB_FLOW_Catalogue::openProduct( $ChB );
					if ( $response && ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $response ], $ChB ) === false ) {
						return false;
					}
				} else {
					$fields = [
						'field_name'  => ChB_ManyChat::CF_PAR1,
						'field_value' => $product_id
					];

					$res = ChB_ManyChat::sendPost2ManyChat( '/fb/subscriber/setCustomFieldByName', $fields, $ChB );
					if ( $res === false ) {
						return false;
					}

					$fields = [
						'flow_ns' => ChB_ManyChat::getMCFlowNS( $ChB->user->channel, 'flow_open_product_by_ref_FBSHOP' )
					];

					$res = ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendFlow', $fields, $ChB );
					if ( $res === false ) {
						return false;
					}
				}
			}

			return true;
		} finally {
			if ( $ChB ) {
				ChatBot::closeTempChatBotSession();
			}
		}
	}
}