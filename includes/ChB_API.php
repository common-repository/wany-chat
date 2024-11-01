<?php


namespace ChatBot;


class ChB_API {
	public static function run( ?\WP_REST_Request $request ) {

		$task         = empty( $_GET['task'] ) ? null : sanitize_text_field( $_GET['task'] );
		$post_body    = file_get_contents( 'php://input' );
		$post_payload = json_decode( $post_body, true );
		if ( ! is_array( $post_payload ) ) {
			$post_payload = [];
		}

		if ( $task === 'set_tokens' || $task === 'save_fb_page' || $task === 'messenger_webhook' ||
		     $task === 'instagram_webhook' || $task === 'get_mc_user_id' || $task === 'send_domain_is' ) {
			if ( ! ChB_Settings()->auth->authorize( $post_payload ) ) {
				ChB_Common::my_log( $post_payload, true, 'ACCESS DENIED ' . $task . ' payload=' );

				return ChB_Auth::getAccessDeniedErrorJSON();
			} else {
				ChB_Common::my_log( 'ACCESS GRANTED ' . $task );
			}
		}

		if ( $task === 'verify_wy_token' ) {
			return ChB_Auth::verifyWYToken_API( $post_payload );
		} elseif ( $task === 'set_tokens' ) {
			return ChB_Auth::setTokens_API( $post_payload );
		} elseif ( $task === 'save_fb_page' ) {
			return ChB_Auth::saveFBPage_API( $post_payload );
		} elseif ( $task === 'messenger_webhook' ) {
			return ChB_WebHookProcess::processWebHook( ChB_Constants::CHANNEL_FB, $post_payload );
		} elseif ( $task === 'instagram_webhook' ) {
			return ChB_WebHookProcess::processWebHook( ChB_Constants::CHANNEL_IG, $post_payload );
		} elseif ( $task === 'get_mc_user_id' ) {
			return ChB_Auth::getMCUserId_API( $post_payload );
		} elseif ( $task === 'send_domain_is' ) {
			return ChB_Auth::sendDomainIs_API( $post_payload );
		} elseif ( $task === 'update_plugin' ) {
			do_action('wany_hook_plugin_update');
		} elseif ( $task === 'manychat_test' ) {
			ChB_Test::run();

			return [];
		}

		return [];
	}

}