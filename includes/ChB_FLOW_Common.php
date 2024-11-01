<?php


namespace ChatBot;


class ChB_FLOW_Common {
	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_cmn_getCachedResponse' ) {

			return self::getCachedResponse( $ChB );

		} elseif ( $ChB->task === 'manychat_cmn_sendTextMessage' ) {

			return self::sendTextMessage( $ChB );

		} elseif ( $ChB->task === 'manychat_cmn_setUserCF' ) {

			return self::setUserCF( $ChB );

		} elseif ( $ChB->task === 'manychat_cmn_getHelpMenu' ) {

			return self::getHelpMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_cmn_getTestFlow' ) {

			return ChB_Test::getTestFlow( $ChB );

		} elseif ( $ChB->task === 'manychat_cmn_getDummy' ) {

			return [
				'version' => 'v2',
				'content' =>
					[
						'messages' => [
							[
								'type' => 'text',
								'text' => "Nice :)! 24H window has been extended\n🎰"
							]
						]
					]
			];

		} elseif ( $ChB->task === 'manychat_cmn_pauseUserReminders' ) {

			return self::pauseUserReminders( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getCustomerStats' ) {

			return self::getCustomerStats( $ChB );
		}

		return [];
	}

	public static function getCachedResponse( ChatBot $ChB ) {
		return ChB_Common::getResponseFromCache( $ChB->getParam( 'val' ) );
	}

	public static function getDynamicCallbackButton4FlowButton( ChatBot $ChB, $button ) {

		if ( $button['target'] === ChB_Settings()->getParam( 'flow_catalog' ) ||
		     $button['target'] === ChB_Settings()->getParam( 'flow_ig_catalog' ) ) {
			return ChatBot::makeDynamicBlockCallbackButton(
				$ChB, $button['caption'],
				[
					'task' => 'manychat_cat_getCatalog',
					'evt'  => ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_CATALOG ], null )
				] );
		}

		return null;
	}

	public static function getContent4Flow( ChatBot $ChB, $flow_ns ) {

		if ( $flow_ns === ChB_Settings()->getParam( 'flow_catalog' ) ||
		     $flow_ns === ChB_Settings()->getParam( 'flow_ig_catalog' ) ) {
			return ChB_FLOW_Catalogue::getCatalog( $ChB );
		}

		return null;
	}

	public static function sendTextMessage( ChatBot $ChB ) {
		if ( $ChB->viewHas( ChB_Common::VIEW_TRANSLATE_VAL ) ) {
			$text = ChB_Lang::translateByKey( $ChB->getParam( 'val' ) );
		} elseif ( $ChB->viewHas( ChB_Common::VIEW_TRANSLATE_HOOK ) ) {
			$text = ChB_Lang::translateUsingHook( $ChB->getParam( 'val' ) );
		} else {
			$text = $ChB->getParam( 'val' );
		}

		if ( $ChB->user->channel === ChB_Constants::CHANNEL_FB &&
		     ( $ChB->viewHas( ChB_Common::VIEW_PHONE_INPUT ) || $ChB->viewHas( ChB_Common::VIEW_EMAIL_INPUT ) ) ) {
			return [
				'version' => 'native',
				'content' =>
					[
						'messages' =>
							[
								[
									'recipient' => [ 'id' => $ChB->user->fb_user_id ],
									'message'   => [
										'text'          => $text,
										'quick_replies' => [
											[
												"content_type" =>
													( $ChB->viewHas( ChB_Common::VIEW_PHONE_INPUT ) ? "user_phone_number" : "user_email" )
											]
										]
									]
								]
							]
					]
			];
		} else {
			return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => $text ] ] ] ];
		}
	}

	public static function startFreeInputText( ChatBot $ChB, $text, $validation_callback, $success_callback, $error_callback, $timeout_callback, $timeout, $retry_times ) {

		$ChB->user->setBlock4UserReminders( ChB_Common::SECONDS_1H );

		$free_input_id = $ChB->user->startFreeInput( $validation_callback, $success_callback, $error_callback, $timeout_callback, $timeout, $retry_times );
		ChB_Events::scheduleSingleEvent( ChB_Events::CHB_EVENT_FREE_INPUT_TIMEOUT, [
			'fb_user_id' => $ChB->user->fb_user_id,
			'fi_id'      => $free_input_id
		], $timeout * 60 );

		$ChB->setParam( 'val', $text );

		$res = self::sendTextMessage( $ChB );

		return $res;
	}

	public static function processFreeInput( ChB_User $user, $text ) {
		if ( ! $user->freeInputIsOn() ) {
			return false;
		}

		$validation        = $user->getFreeInputAttr( 'validation_callback' );
		$validation_result = true;

		if ( ! empty( $validation['addr_part'] ) ) {
			$cart = new ChB_Cart( $user->wp_user_id, $user->getCartUser() );
			$cart->activateSavedShippingOptionId();
			$shipping_details  = new ChB_ShippingDetails( $cart );
			$validation_result = $shipping_details->validateAndSaveAddrPartValue( $validation['addr_part'], $text );
		}

		$callback_type = 'success';
		if ( ! $validation_result ) {
			if ( $user->freeInputCheckTriesLeft() ) {
				$callback_type = 'error';
			} else {
				$callback_type = 'timeout';
			}
		}

		self::freeInputCallback( $user, $text, $callback_type );

		return true;
	}

	public static function freeInputCallback( ChB_User $user, $text, $callback_type, $free_input_id = null ) {

		//skipping if free input was already overridden by another free input
		if ( $free_input_id && $free_input_id !== $user->getFreeInputAttr( 'id' ) ) {
			return;
		}

		if ( $callback_type === 'success' || $callback_type === 'timeout' ) {
			$user->unsetBlock4UserReminders();
			$user->finishFreeInput( $free_input_id );
		}

		$callback_pars = $user->getFreeInputAttr( $callback_type . '_callback' );

		if ( $text && ! empty( $callback_pars['text_callback_par'] ) ) {
			$callback_pars[ $callback_pars['text_callback_par'] ] = $text;
			unset( $callback_pars['text_callback_par'] );
		}

		if ( $callback_pars ) {
			$ChB      = ChatBot::openTempChatBotSession( $user, $callback_pars );
			$response = ChatBot::run( $ChB );
			ChB_Common::my_log( 'freeInputCallback task=' . $callback_pars['task'] );
			if ( $response ) {
				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [ 'data' => $response ], $ChB );
			}
			ChatBot::closeTempChatBotSession();
		}
	}

	public static function setUserCF( ChatBot $ChB ) {
		$ChB->user->setCF( $ChB->getParam( 'cf_name' ), $ChB->getParam( 'cf_value' ) );

		return [];
	}

	public static function getHelpMenu( ChatBot $ChB ) {

		if ( count( ChB_Lang::getUsedLanguages() ) > 1 ) {
			$messages = [
				[
					'type' => 'text',
					'text' => ChB_Lang::translate( ChB_Lang::LNG0009 )
				],

				ChB_FLOW_Lang::getMessage4LangKW( $ChB )
			];
		} else {
			$messages = [];
		}

		$messages[] =
			[
				'type'    => 'text',
				'text'    => strtoupper( ChB_Constants::KW_ORDERS ) . ' - ' . ChB_Lang::translate( ChB_Lang::LNG0016 ),
				'buttons' => [ ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0017 ), [ 'task' => 'manychat_myorders_getMyOrders' ] ) ]
			];

		$messages[] =
			[
				'type'    => 'text',
				'text'    => strtoupper( ChB_Constants::KW_SHOP ) . ' - ' . ChB_Lang::translate( ChB_Lang::LNG0015 ),
				'buttons' => [ ChB_FLOW_Catalogue::getCatalogButton( $ChB, ChB_Lang::LNG0018 ) ]
			];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function makeResponse4APPAction( $res1 = null, $res2 = null, $res3 = null, $res4 = null ) {
		return [
			'res1' => $res1,
			'res2' => $res2,
			'res3' => $res3,
			'res4' => $res4
		];
	}

	public static function getReplyForEmpty( ChatBot $ChB ) {
		return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => ';)' ] ] ] ];
	}

	public static function pauseUserReminders( ChatBot $ChB ) {
		$minutes = intval( $ChB->getParam( 'val' ) );
		if ( ! $minutes ) {
			return [ 'status' => 'error' ];
		}
		$ChB->user->setBlock4UserReminders( $minutes * 60 );

		return [ 'status' => 'success' ];
	}

	public static function getCustomerStats( ChatBot $ChB ) {
		$orders_stats = ChB_Orders::getCustomerOrdersStats( $ChB->user );

		return self::makeResponse4APPAction(
			$orders_stats['sum_completed'],
			$orders_stats['qty_completed'],
			$orders_stats['sum_cancelled'],
			$orders_stats['qty_cancelled'] );
	}

	public static function sendMessages( ChatBot $ChB, $messages ) {

		ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', [
			'data' => [
				'version' => 'v2',
				'content' => [ 'messages' => $messages ]
			]
		], $ChB );
	}

	public static function somethingWentWrong( ChatBot $ChB, $text = null, $open_cart = false ) {
		if ( $open_cart ) {
			$ChB->setParam( 'view', [] );

			return ChB_FLOW_NewOrder::openCart( $ChB, $text );
		}

		if ( $text ) {
			$ChB->setParam( 'val', $text );
		} else {
			$ChB->setParam( 'val', ChB_Lang::translate( ChB_Lang::LNG0181 ) );
		}

		return self::sendTextMessage( $ChB );
	}
}