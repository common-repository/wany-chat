<?php


namespace ChatBot;


class ChB_Redirect {
	public static function checkRedirect() {

		if ( isset( $_GET['task'] ) && $_GET['task'] === 'redirect' ) {
			$redirect_url = ( empty( $_GET['url'] ) ? null : sanitize_text_field( $_GET['url'] ) );
			header( 'Location: ' . $redirect_url );

			fastcgi_finish_request();

			if ( ! empty( $_GET['evt'] ) && ! empty( $_GET['user_id'] ) ) {
				$events = ChB_Analytics::unpackEventsFromUrl( null, sanitize_text_field( $_GET['evt'] ) );

				ChB_Analytics::registerEvents( $events, sanitize_text_field( $_GET['user_id'] ) );
			}
			exit();
		}
	}
}