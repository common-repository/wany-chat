<?php


namespace ChatBot;


class ChB_CheckRequirements {

	static $details;

	public static function check() {
		$check = true;
		$required_php_version = '7.4';

		if ( ! defined( 'PHP_VERSION' ) || version_compare( PHP_VERSION, $required_php_version ) < 0 ) {
			$check   = false;
			self::$details = (empty(self::$details) ? '' : self::$details . '<br><br>') . 'Your PHP version is <b>' . PHP_VERSION . '</b>. Required version is <b>' . $required_php_version . '</b> or newer.<br>Please update your PHP to use Wany.Chat plugin!';
		}

		if ( ! is_ssl() ) {
			$check   = false;
			self::$details = (empty(self::$details) ? '' : self::$details. '<br><br>') . 'Your site doesn\'t seem to support <b>HTTPS</b> which is required by Wany.Chat plugin.<br>Please install SSL certificate!';
		}

		if ( $check ) {
			return true;
		}

		add_action( 'admin_menu', [ '\ChatBot\ChB_CheckRequirements', 'rrbot_add_admin_menu' ] );

	}

	public static function rrbot_add_admin_menu() {
		add_options_page( 'Wany.Chat', 'Wany.Chat', 'manage_options', 'rrbot-settings', [
			'\ChatBot\ChB_CheckRequirements',
			'rrbot_options_page'
		] );
	}

	public static function rrbot_options_page() {
		echo '<h2>Wany.Chat Admin Page</h2>';
		if ( ! empty( self::$details ) ) {
			echo self::$details;
		}
	}
}