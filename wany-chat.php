<?php
/**
 * Plugin Name: Wany.Chat
 * Version: 1.3.16.6
 * Requires at least: 5.5
 * Requires PHP: 7.4
 * WC requires at least: 5.4.1
 * Plugin URI: https://wany.chat/
 * Description: Turn your WooCommerce store into Selling Chatbot. Automatically
 */

require_once( dirname( __FILE__ ) . '/includes/ChB_CheckRequirements.php' );


if ( \ChatBot\ChB_CheckRequirements::check() && checkCrawlers() ) {

	add_action( 'woocommerce_init',
		function () {

			add_action( 'rest_api_init', function () {
				register_rest_route( 'wany-chat/v1', '/q', [
					'methods'             => [ 'GET', 'POST' ],
					'callback'            => 'run',
					'permission_callback' => '__return_true'
				] );
			} );

			add_action( 'rest_api_init', function () {
				register_rest_route( 'wany-chat/v1', '/api', [
					'methods'             => [ 'GET', 'POST' ],
					'callback'            => 'run_api',
					'permission_callback' => '__return_true'
				] );
			} );

			add_action( 'rest_api_init', function () {
				register_rest_route( 'wany-chat/v1', '/aapi', [
					'methods'             => [ 'GET', 'POST' ],
					'callback'            => 'run_admin_api',
					'permission_callback' => function () {
						return current_user_can( 'administrator' );
					}
				] );
			} );

			require_once dirname( __FILE__ ) . '/includes/ChB_Settings.php';

			require_once dirname( __FILE__ ) . '/includes/ChB_Ajax.php';
			\ChatBot\ChB_Ajax::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_Events.php';
			\ChatBot\ChB_Events::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_WooCommon.php';
			\ChatBot\ChB_WooCommon::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_WPAdminHooks.php';
			\ChatBot\ChB_WPAdminHooks::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_WPHooks.php';
			\ChatBot\ChB_WPHooks::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_SettingsPage.php';
			\ChatBot\ChB_SettingsPage::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_CustomLang.php';
			\ChatBot\ChB_CustomLang::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_Cron.php';
			\ChatBot\ChB_Cron::init();

			require_once dirname( __FILE__ ) . '/includes/ChB_Payment.php';
			require_once dirname( __FILE__ ) . '/includes/ChB_PaymentMercadoPago.php';
			\ChatBot\ChB_PaymentMercadoPago::init();
			require_once dirname( __FILE__ ) . '/includes/ChB_PaymentRobokassa.php';
			\ChatBot\ChB_PaymentRobokassa::init();

		} );
}

function checkCrawlers() {
	return ( empty( $_SERVER['HTTP_USER_AGENT'] ) || strpos( $_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit' ) === false );
}

function wooIsDefined() {
	return defined( 'WC_VERSION' );
}

function chb_load() {
	require_once dirname( __FILE__ ) . '/chb-load.php';

	do_action( 'wany_hook_chb_loaded' );
}

function run( $request ) {
	chb_load();

	return \ChatBot\ChatBot::run( null );
}

function run_api( $request ) {
	chb_load();

	return \ChatBot\ChB_API::run( $request );
}

function run_admin_api( $request ) {
	chb_load();

	return \ChatBot\ChB_AdminAPI::run( $request );
}

function get_wy_plugin_dir_url() {
	return plugin_dir_url( __FILE__ );
}

function get_wy_plugin_dir_path() {
	return plugin_dir_path( __FILE__ );
}

function get_wy_plugin_basename() {
	return plugin_basename( __FILE__ );
}
