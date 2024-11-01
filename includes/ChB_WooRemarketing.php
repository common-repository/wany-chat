<?php


namespace ChatBot;


class ChB_WooRemarketing {

	public const REMINDER_TIME_STEP_1H = 3600; //1h
	public const REMINDER_TIME_MAX_24H = 24 * 3600; //24h

	public const USER_ATTR_RMKT_INFO = 'wy_rmkt_info';
	public const USER_ATTR_RMKT_LAST_ACTIVITY_TIME = 'wy_rmkt_ats';
	public const USER_ATTR_RMKT_NEXT_REMINDER_TIME = 'wy_rmkt_nrts';

	public const TRIGGER_NAME = 'remarketing';

	public static function init() {

		if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) ) {
			require_once dirname( __FILE__ ) . '/ChB_WooAbandonedCart.php';
			require_once dirname( __FILE__ ) . '/ChB_WooCart.php';
			ChB_WooAbandonedCart::init();
		}

		if ( ChB_Settings()->getParam( 'use_woo_views_remarketing' ) ) {
			add_action( 'template_redirect', [ 'ChatBot\ChB_WooRemarketing', 'markProductView' ], 10 );
		}
	}

	public static function markProductView() {

		try {

			if ( ! is_product() || ! ( $wc_product = wc_get_product() ) ) {
				return;
			}

			$wy_session_id = ChB_WYSession::initWYSession( true );
			ChB_WYSession::session( $wy_session_id )->setProductView( $wc_product->get_id() );
			if ( $bot_wp_user_id = ChB_WYSession::getBotUser4WYSessionId( $wy_session_id ) ) {
				self::refreshRemarketing( $bot_wp_user_id, false );
			}

		} catch ( \Throwable $e ) {
			$error = 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString();
			if ( class_exists( '\ChatBot\ChB_Common' ) ) {
				ChB_Common::my_log( $error );
			} else {
				error_log( $error );
			}
		}
	}

	public static function refreshRemarketing( $bot_wp_user_id, $only_if_exists ) {

		if ( $only_if_exists && ! get_user_meta( $bot_wp_user_id, self::USER_ATTR_RMKT_LAST_ACTIVITY_TIME, true ) ) {
			return;
		}

		update_user_meta( $bot_wp_user_id, self::USER_ATTR_RMKT_LAST_ACTIVITY_TIME, time() );
		update_user_meta( $bot_wp_user_id, ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME, time() + self::REMINDER_TIME_STEP_1H );

	}

	public static function getBotUserIds4Reminders( $older_than_ts, $attr_name ) {

		$args = [
			'fields' => 'ID',
		];

		if ( $older_than_ts ) {
			$args['meta_query'] = [
				[
					'key'     => $attr_name,
					'value'   => $older_than_ts,
					'compare' => '<=',
					'type'    => 'numeric'
				]
			];
		} else {
			$args['meta_query'] = [ [ 'key' => $attr_name, 'compare' => 'EXISTS' ] ];
		}

		$user_query = new \WP_User_Query( $args );

		return $user_query->get_results();
	}

	/**
	 * Selecting abandoned carts that need to be reminded about, firing MC triggers.
	 * Same for remarketing
	 * Function is called from WP cron
	 *
	 */
	public static function processRemarketing() {

		$actions = [];

		if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) ) {
			$actions[] = [
				'last_creation_attr' => ChB_WooAbandonedCart::USER_ATTR_AC_LAST_CREATION_TIME,
				'next_reminder_attr' => ChB_WooAbandonedCart::USER_ATTR_AC_NEXT_REMINDER_TIME,
				'name'               => ChB_WooAbandonedCart::TRIGGER_NAME
			];
		}

		if ( ChB_Settings()->getParam( 'use_woo_views_remarketing' ) ) {
			$actions[] = [
				'last_creation_attr'    => ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME,
				'next_reminder_attr'    => ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME,
				'name'                  => ChB_WooRemarketing::TRIGGER_NAME,
				'check_not_exists_attr' => ChB_WooAbandonedCart::USER_ATTR_AC_NEXT_REMINDER_TIME
			];
		}

		$processed_user_ids = [];
		foreach ( $actions as $action ) {

			$now_ts           = time();
			$next_reminder_ts = $now_ts + self::REMINDER_TIME_STEP_1H;

			$user_ids = ChB_WooRemarketing::getBotUserIds4Reminders( $now_ts, $action['next_reminder_attr'] );
			if ( ! $user_ids ) {
				continue;
			}
			$check_not_exists_user_ids = isset( $action['check_not_exists_attr'] ) ? ChB_WooRemarketing::getBotUserIds4Reminders( 0, $action['check_not_exists_attr'] ) : null;

			ChB_Common::my_log( $user_ids, 1, 'CRON processRemarketing ' . $action['name'] );

			foreach ( $user_ids as $wp_user_id ) {

				if ( in_array( $wp_user_id, $processed_user_ids ) ) {
					continue;
				}

				if ( $check_not_exists_user_ids && in_array( $wp_user_id, $check_not_exists_user_ids ) ) {
					// not sending remarketing for users that have ac reminders scheduled
					// moving remarketing reminder to 1 hour later - not check it every minute

					update_user_meta( $wp_user_id, $action['next_reminder_attr'], $next_reminder_ts );
					ChB_Common::my_log( 'processRemarketing skipping bot_user_id=' . $wp_user_id . ' action=' . $action['name'] . ' because of check_not_exists_attr, moving rmndr to ' . ChB_Common::timestamp2DateTime( $next_reminder_ts ) );
					continue;
				}

				$creation_ts = get_user_meta( $wp_user_id, $action['last_creation_attr'], true );
				$bot_user    = ChB_User::initUserByWPUserID( $wp_user_id );
				if ( ! $creation_ts || ! $bot_user ) {
					continue;
				}

				$hours = intval( floor( ( $now_ts - $creation_ts ) / self::REMINDER_TIME_STEP_1H ) );
				if ( $action['name'] === ChB_WooRemarketing::TRIGGER_NAME ) {
					self::fireRemarketingTrigger( $hours, $bot_user );
				} elseif ( $action['name'] === ChB_WooAbandonedCart::TRIGGER_NAME ) {
					ChB_WooAbandonedCart::fireAbandonedCartTrigger( $hours, $bot_user );
				}
				$processed_user_ids[] = $wp_user_id;

				if ( $next_reminder_ts - $creation_ts <= self::REMINDER_TIME_MAX_24H ) {
					update_user_meta( $bot_user->wp_user_id, $action['next_reminder_attr'], $next_reminder_ts );
				} else {
					ChB_Common::my_log( $action['name'] . ' stopped reminders for bot user=' . $bot_user->wp_user_id );
					delete_user_meta( $bot_user->wp_user_id, $action['next_reminder_attr'] );
				}
			}

		}

	}

	public static function fireRemarketingTrigger( $hours, ChB_User $bot_user ) {
		$context = [ 'hours_ago' => $hours ];
		ChB_Common::my_log( $context, 1, 'fireRemarketingTrigger user=' . $bot_user->wp_user_id . ' ' . $bot_user->fb_user_id );
		if ( $hours ) {
			ChB_ManyChat::fireManyChatTrigger( $bot_user->fb_user_id, self::TRIGGER_NAME, $context );
		}
	}

	public static function getProductsToRemarketDetails( ChatBot $ChB ) {

		$wy_session_ids = ChB_WYSession::getBotWYSessionIds( $ChB->user->wp_user_id );
		if ( ! $wy_session_ids ) {
			return null;
		}

		// merging products from sessions connected to bot user
		$selected_products_ids = [];
		$selected_products     = [];
		foreach ( $wy_session_ids as $wy_session_id ) {
			if ( ! ( $product_views = ChB_WYSession::session( $wy_session_id )->getProductViews() ) ) {
				continue;
			}

			for ( $ind = count( $product_views ) - 1; $ind >= 0; $ind -- ) {

				$product_id = $product_views[ $ind ];
				if ( in_array( $product_id, $selected_products_ids ) ) {
					continue;
				}

				$wc_product = wc_get_product( $product_id );
				if ( ! ChB_Catalogue::productIsVisible( $wc_product ) ) {
					continue;
				}

				$selected_products_ids[] = $product_id;
				$selected_products[]     = $wc_product;
				if ( count( $selected_products ) >= 10 ) {
					break;
				}
			}
			if ( count( $selected_products ) >= 10 ) {
				break;
			}
		}

		// adding related products
		if ( count( $selected_products ) < 10 ) {
			$related_products_ids = ChB_Catalogue::getRelatedProducts4List( $ChB->user, $selected_products_ids, ChB_Catalogue::userGender2ProductGender( $ChB->user->getGender() ), 10 - count( $selected_products ) );
			if ( $related_products_ids ) {
				foreach ( $related_products_ids as $product_id ) {
					$selected_products[] = wc_get_product( $product_id );
				}
			}
		}

		$res = [];
		ChB_Settings()->setWebRedirectOnBUY();
		foreach ( $selected_products as $wc_product ) {
			$res['cards'][] = [
				'title'     => ChB_Catalogue::getProductName( $wc_product ),
				'subtitle'  => ChB_Common::printPrice( $wc_product->get_price() ),
				'image_url' => ChB_Catalogue::getProductImage( $wc_product ),
				'buttons'   => [ ChB_FLOW_Catalogue::makeBuyButton( $ChB, null, ChB_Lang::translate( ChB_Lang::LNG0175 ), $wc_product->get_id(), $wc_product->get_id() ) ]
			];
		}

		return $res;
	}

	public static function cleanUp() {
		if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) ) {
			ChB_WooAbandonedCart::cleanUpOldAbandonedCarts();
		}

		if ( ChB_Settings()->getParam( 'use_woo_views_remarketing' ) ) {
			ChB_WooRemarketing::cleanUpOldRemarketing();
		}

		if ( ChB_Settings()->getParam( 'use_abandoned_cart' ) || ChB_Settings()->getParam( 'use_woo_views_remarketing' ) ) {
			ChB_WYSession::cleanUpOldWYSessions();
		}
	}

	public static function cleanUpOldRemarketing() {

		ChB_Common::my_log( 'cleanUpOldRemarketing start' );

		$delete_before_ts = time() - ChB_Settings()->getParam( 'abandoned_cart_delete_after_days' ) * ChB_Constants::SECONDS_ONE_DAY;

		$ids = ChB_WooRemarketing::getBotUserIds4Reminders( $delete_before_ts, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME );
		ChB_Common::my_log( $ids, 'cleanUpOldRemarketing ids' );

		if ( $ids ) {
			foreach ( $ids as $id ) {
				delete_user_meta( $id, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME );
				delete_user_meta( $id, ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME );
			}
		}
		ChB_Common::my_log( 'cleanUpOldRemarketing finish' );
	}


	/**
	 * for debugging
	 */
	public static function cleanUpAllRemarketingAttrs( $show_only = true, $bot_wp_user_id = null ) {

		echo "show_only={$show_only}\n";
		echo "bot_wp_user_id={$bot_wp_user_id}\n";
		echo "CLEARING ALL BOT-WOO connections\n";
		$user_ids = $bot_wp_user_id ? [ $bot_wp_user_id ] : ChB_User::getAllBotUsersConnectedToWooUsers();
		foreach ( $user_ids as $wp_user_id ) {
			$bot_user = ChB_User::initUserByWPUserID( $wp_user_id );
			if ( $bot_user ) {
				echo $wp_user_id . "\n";
				if ( ! $show_only ) {
					$bot_user->unsetWooWPUserID();
				}
			}
		}

		echo "CLEARING ALL RMKT attrs\n";
		$user_ids = $bot_wp_user_id ? [ $bot_wp_user_id ] : ChB_WooRemarketing::getBotUserIds4Reminders( 0, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME );
		var_dump( $user_ids );
		if ( $user_ids ) {
			foreach ( $user_ids as $wp_user_id ) {
				echo $wp_user_id . "\n";
				if ( ! $show_only ) {
					delete_user_meta( $wp_user_id, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME );
					delete_user_meta( $wp_user_id, ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME );
				}
			}
		}

		echo "CLEARING ALL AC attrs\n";
		$user_ids = $bot_wp_user_id ? [ $bot_wp_user_id ] : ChB_WooRemarketing::getBotUserIds4Reminders( 0, ChB_WooAbandonedCart::USER_ATTR_AC_LAST_CREATION_TIME );
		var_dump( $user_ids );
		if ( $user_ids ) {
			foreach ( $user_ids as $wp_user_id ) {
				echo $wp_user_id . "\n";
				if ( ! $show_only ) {
					ChB_WooAbandonedCart::removeAbandonedCart4BotUserId( $wp_user_id );
				}
			}
		}

		if ( ! $show_only ) {
			echo "CLEARING ALL WY_SESSIONS LEFT\n";
			ChB_WYSession::removeAllWySessions( $bot_wp_user_id );
		}
	}

	/**
	 * for debugging
	 */
	public static function debugRemarketingInfo4UserId( $wp_user_id ) {

		echo "u={$wp_user_id}\n";
		$creation_time = get_user_meta( $wp_user_id, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME, true );
		if ( $creation_time ) {
			echo 'rmkt_lc_time=' . ChB_Common::timestamp2DateTime( $creation_time ) . "\n";
		}
		$reminder_time = get_user_meta( $wp_user_id, ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME, true );
		if ( $reminder_time ) {
			echo 'rmkt_nr_time=' . ChB_Common::timestamp2DateTime( $reminder_time ) . "\n";
		}
		$creation_time = get_user_meta( $wp_user_id, ChB_WooAbandonedCart::USER_ATTR_AC_LAST_CREATION_TIME, true );
		if ( $creation_time ) {
			echo 'ac_lc_time=' . ChB_Common::timestamp2DateTime( $creation_time ) . "\n";
		}
		$reminder_time = get_user_meta( $wp_user_id, ChB_WooAbandonedCart::USER_ATTR_AC_NEXT_REMINDER_TIME, true );
		if ( $reminder_time ) {
			echo 'ac_nr_time=' . ChB_Common::timestamp2DateTime( $reminder_time ) . "\n";
		}
		$woo_wp_user_id = get_user_meta( $wp_user_id, ChB_User::USER_ATTR_WOO_WP_USER_ID, true );
		echo 'woo_wp_user_id=';
		if ( $woo_wp_user_id ) {
			echo $woo_wp_user_id;
		}
		echo "\n" . 'woo_wy_sessions:' . "\n";
		$wy_session_ids = get_user_meta( $wp_user_id, ChB_WYSession::USER_ATTR_WY_SESSION_ID, false );
		if ( $wy_session_ids ) {
			foreach ( $wy_session_ids as $wy_session_id ) {
				echo $wy_session_id . '=';
				var_dump( ChB_WYSession::getWYSession( $wy_session_id ) );
				echo "\n";
			}
		}

		echo 'ac_info:' . "\n";
		var_dump( get_user_meta( $wp_user_id, ChB_WooAbandonedCart::USER_ATTR_AC_INFO, true ) );

		echo "\n\n" . 'all bot wy_sessions' . "\n";
		$wy_session_ids = ChB_WYSession::getBotWYSessionIds( $wp_user_id );
		if ( $wy_session_ids ) {
			$res = [];
			foreach ( $wy_session_ids as $wy_session_id ) {
				$res[ $wy_session_id ] = ChB_Settings()->kvs->get( ChB_WYSession::KVS_PREFIX_WY_SESSION . $wy_session_id );
			}
			var_dump( $res );
		}
	}

	public static function debugRemarketingInfo() {

		$mode = empty( $_GET['mode'] ) ? null : sanitize_text_field( $_GET['mode'] );
		echo "mode=show_all_users|cleanup|del_all\n\n";

		if ( $mode === 'show_all_users' ) {
			echo "ALL BOT-WOO connections\n";
			var_dump( ChB_User::getAllBotUsersConnectedToWooUsers() );

			echo "ALL RMKT users\n";
			var_dump( ChB_WooRemarketing::getBotUserIds4Reminders( 0, ChB_WooRemarketing::USER_ATTR_RMKT_LAST_ACTIVITY_TIME ) );
			echo "RMNDR RMKT users\n";
			var_dump( ChB_WooRemarketing::getBotUserIds4Reminders( time(), ChB_WooRemarketing::USER_ATTR_RMKT_NEXT_REMINDER_TIME ) );

			echo "ALL AC users\n";
			var_dump( ChB_WooRemarketing::getBotUserIds4Reminders( 0, ChB_WooAbandonedCart::USER_ATTR_AC_LAST_CREATION_TIME ) );
			echo "RMNDR AC users\n";
			var_dump( ChB_WooRemarketing::getBotUserIds4Reminders( time(), ChB_WooAbandonedCart::USER_ATTR_AC_NEXT_REMINDER_TIME ) );

			echo "\n\n" . 'all saved wy_sessions' . "\n";
			var_dump( ChB_Settings()->kvs->scanAllByPrefix( ChB_WYSession::KVS_PREFIX_WY_SESSION ) );

			return;
		}

		if ( $mode == 'cleanup' ) {
			ChB_WooRemarketing::cleanUp();

			return;
		}

		if ( $mode === 'del_all' ) {
			ChB_WooRemarketing::cleanUpAllRemarketingAttrs( false );

			return;
		}

		if ( empty( $_GET['bot_user_id'] ) ) {
			echo 'bot_user_id par not set!';

			return;
		}

		$bot_user_id = sanitize_text_field( $_GET['bot_user_id'] );

		if ( $mode === 'del' ) {
			ChB_WooRemarketing::cleanUpAllRemarketingAttrs( false, $bot_user_id );

			return;
		}

		ChB_WooRemarketing::debugRemarketingInfo4UserId( $bot_user_id );
	}

}