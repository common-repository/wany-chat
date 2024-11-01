<?php


namespace ChatBot;


class ChB_FLOW_TryOn {


	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_tryon_showTryOn' ) {

			return self::showTryOn( $ChB );

		} elseif ( $ChB->task === 'manychat_tryon_showTryOnDemoCallToAction' ) {

			return self::showTryOnDemoCallToAction( $ChB );

		} elseif ( $ChB->task === 'manychat_tryon_sendTryOnDemo' ) {

			self::sendTryOnDemo( $ChB );

		}

		return [];

	}

	public static function showTryOn( ChatBot $ChB ) {
		$view_try_on_again = $ChB->viewHas( ChB_Common::VIEW_TRY_ON_AGAIN );
		if ( $view_try_on_again || ! ChB_TryOn::getUserPhoto4TryOn( $ChB->user, true, false ) ) {
			ChB_TryOn::setCurrentTryOnProduct( $ChB->user, $ChB->getParam( 'product_id' ), $ChB->getParam( 'cat_slug' ) );
			$text = ChB_Lang::translate( ChB_Lang::LNG0146 );
			ChB_TryOn::setUserTryOnState( $ChB->user );

			return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => $text ] ] ] ];
		}
		$event_tags = ChB_Analytics::getTags( $ChB->events, [
			ChB_Analytics::EVENT_TRY_ON,
			ChB_Analytics::EVENT_TRY_ON_AGAIN,
			ChB_Analytics::EVENT_TRY_ON_HATS
		] );

		return self::sendTryOnByProductId( $ChB, true, $event_tags, $ChB->getParam( 'product_id' ), $ChB->getParam( 'cat_slug' ) );
	}

	public static function saveUserPhoto( ChB_User $user, $tmp_path ) {

		if ( ChB_Settings()->getTryOn( 'is_try_on_demo' ) ) {
			sleep( 10 );
			$res = true;
		} else {
			list( $res, $err_code ) = ChB_TryOn::saveUserPhoto4TryOn( $user, $tmp_path );
		}

		ChB_TryOn::unsetUserTryOnState( $user );
		$user->unsetBlock4UserReminders();

		$ChB = ChatBot::openTempChatBotSession( $user );
		if ( ! $ChB ) {
			return false;
		}

		$event_tags = ChB_Analytics::getLastTryOnTags( $user->fb_user_id );
		list( $product_id, $cat_slug ) = ChB_TryOn::getCurrentTryOnProduct( $user );
		if ( $res ) {
			self::sendTryOnByProductId( $ChB, false, $event_tags, $product_id, $cat_slug );
		} else {
			$text = ChB_DressUp::getErrorText( $err_code );

			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0144 ),
				[
					'task'       => 'manychat_tryon_showTryOn',
					'view'       => ChB_Common::VIEW_TRY_ON_AGAIN,
					'product_id' => $product_id,
					'cat_slug'   => $cat_slug,
					'evt'        => [ ChB_Analytics::packEvent4Url( ChB_Analytics::EVENT_TRY_ON_AGAIN, [ 'tags' => $event_tags ] ) ]
				] );

			$fields = [
				'data' => [
					'version' => 'v2',
					'content' => [
						'messages' => [
							[
								'type'    => 'text',
								'text'    => $text,
								'buttons' => $buttons
							]
						]
					]
				]
			];
			ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );
		}
		ChatBot::closeTempChatBotSession();
	}

	public static function sendTryOnByProductId( ChatBot $ChB, $is_get, $event_tags, $product_id = null, $cat_slug = null, $text = null ) {
		if ( ! $cat_slug ) {
			$cat_slug = ChB_TryOn::getDefaultCatSlug4TryOn( $ChB->user->getGender() );
		}

		$related_product_ids = ChB_Catalogue::getSimilarProducts4TryOn( $product_id, $cat_slug );
		$ChB->setParam( 'view', [ ChB_Common::VIEW_TRY_ON ] );
		ChB_Analytics::addEvent( $ChB->events, ChB_Analytics::EVENT_LIST_PRODUCTS, $event_tags );
		ChB_Analytics::registerEvent( ChB_Analytics::EVENT_LIST_PRODUCTS, [ 'tags' => $event_tags ], $ChB->user->fb_user_id );

		return ChB_FLOW_Catalogue::sendSelectedProducts( $ChB, $is_get, $cat_slug, null, null, $related_product_ids, $text );
	}

	public static function sendTryOnDemo( ChatBot $ChB ) {
		ChB_Common::my_log( 'sendTryOnDemo  ' . $ChB->user->fb_user_id );
		if ( in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'users_ignore_reminders' ) ) || $ChB->user->getBlock4UserReminders() ) {
			ChB_Common::my_log( 'ignoring.. ' . $ChB->user->fb_user_id );

			return;
		}

		//quitting if user has already successfully uploaded selfie
		if ( ! empty( ChB_TryOn::getUserPhoto4TryOn( $ChB->user, true, false ) ) ) {
			return;
		}

		//detecting keypoints for profile pic
		ChB_TryOn::getUserPhoto4TryOn( $ChB->user, false, true, false );

		$fields = [ 'flow_ns' => ChB_ManyChat::getMCFlowNS( $ChB->user->channel, 'flow_try_on_demo' ) ];
		ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendFlow', $fields, $ChB );
	}

	public static function showTryOnDemoCallToAction( ChatBot $ChB ) {

		$user_has_photo4try_on = ! empty( ChB_TryOn::getUserPhoto4TryOn( $ChB->user, false, true ) );
		$event_tags            = [ $user_has_photo4try_on ? ChB_Analytics::TAG_TRY_ON_DEMO_PROFILE_HATS : ChB_Analytics::TAG_TRY_ON_DEMO_HATS ];

		$text = $ChB->user->getHi() . ChB_Lang::translate( ChB_Lang::LNG0147 );

		$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0148 ),
			[
				'task' => 'manychat_tryon_showTryOn',
				'evt'  => [ ChB_Analytics::packEvent4Url( ChB_Analytics::EVENT_TRY_ON_HATS, [ 'tags' => $event_tags ] ) ]
			] );
		$messages  = [
			[
				'text'    => $text,
				'type'    => 'text',
				'buttons' => $buttons
			]
		];

		if ( $user_has_photo4try_on ) {
			ChB_Analytics::registerEvent( ChB_Analytics::EVENT_TRY_ON_DEMO_PROFILE_HATS, [ 'tags' => $event_tags ], $ChB->user->fb_user_id );
			$event_tags = [ ChB_Analytics::TAG_TRY_ON_DEMO_PROFILE_HATS ];

			return self::sendTryOnByProductId( $ChB, true, $event_tags, null, null, $messages );
		} else {
			ChB_Analytics::registerEvent( ChB_Analytics::EVENT_TRY_ON_DEMO_HATS, [ 'tags' => $event_tags ], $ChB->user->fb_user_id );

			return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
		}
	}
}