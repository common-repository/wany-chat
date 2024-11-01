<?php


namespace ChatBot;


class ChB_FLOW_WooRemarketing {
	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_wac_setAbandonedCartReminders'  || $ChB->task === 'manychat_rmkt_setAbandonedCartReminders') {

			return self::setAbandonedCartReminders( $ChB );

		} elseif ( $ChB->task === 'manychat_wac_showWooAbandonedCart' || $ChB->task === 'manychat_rmkt_showWooAbandonedCart') {

			return self::showWooAbandonedCart( $ChB );

		} elseif ( $ChB->task === 'manychat_rmkt_showWooProductsToRemarket' ) {

			return self::showWooProductsToRemarket( $ChB );
		}

		return [];
	}

	public static function setAbandonedCartReminders( ChatBot $ChB ) {

		$wy_session_id = $ChB->getParam( 'val' );
		ChB_Common::my_log( 'setAbandonedCartReminders wys=' . $wy_session_id . ' bot_user=' . $ChB->user->wp_user_id );
		if ( $wy_session_id !== ChB_Common::EMPTY_TEXT ) {
			ChB_WYSession::connectWYSessionToBotUser( $ChB->user->wp_user_id, $wy_session_id );
		}

		return [ 'status' => 'success' ];
	}

	public static function showWooAbandonedCart( ChatBot $ChB ) {

		$ac_details = ChB_WooAbandonedCart::getAbandonedCartDetails( $ChB->user );

		$messages = [];
		if ( ! empty( $ac_details['cards'] ) ) {

			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           => $ac_details['cards']
			];
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function showWooProductsToRemarket( ChatBot $ChB ) {

		$ac_details = ChB_WooRemarketing::getProductsToRemarketDetails( $ChB );

		$messages = [];
		if ( ! empty( $ac_details['cards'] ) ) {

			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           => $ac_details['cards']
			];
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

}