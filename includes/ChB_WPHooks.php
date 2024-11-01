<?php


namespace ChatBot;


class ChB_WPHooks {
	public static function init() {
		add_filter( 'wp_mail', [ 'ChatBot\ChB_WPHooks', 'filterDummyEmails' ] );
	}

	public static function filterDummyEmails( $args ) {
		chb_load();
		if ( empty( $args['to'] ) ) {
			return $args;
		}

		if ( is_string( $args['to'] ) ) {
			$emails = explode( ',', $args['to'] );
		} else {
			$emails = $args['to'];
		}

		if ( ! ( is_array( $emails ) ) ) {
			ChB_Common::my_log( $args['to'], 1, 'wp_mail hook: filterDummyEmails. Cannot parse list of emails' );

			return $args;
		}

		$args['to'] = [];
		foreach ( $emails as $email ) {
			if ( ChB_User::emailIsDummy( trim( $email ) ) ) {
				ChB_Common::my_log( 'wp_mail hook: filterDummyEmails. Clearing dummy email: ' . $email );
			} else {
				$args['to'][] = $email;
				ChB_Common::my_log( 'wp_mail hook: filterDummyEmails. Email is OK ' . $email );
			}
		}

		return $args;
	}

	public static function ignoreMailingLists() {

		try {
			if ( class_exists( '\MailPoet\WooCommerce\Subscription' ) ) {
				$_POST[ \MailPoet\WooCommerce\Subscription::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME ] = 1;
				if ( isset( $_POST[ \MailPoet\WooCommerce\Subscription::CHECKOUT_OPTIN_INPUT_NAME ] ) ) {
					unset( $_POST[ \MailPoet\WooCommerce\Subscription::CHECKOUT_OPTIN_INPUT_NAME ] );
				}
			}
		} catch ( \Exception $e ) {

		}

	}


}