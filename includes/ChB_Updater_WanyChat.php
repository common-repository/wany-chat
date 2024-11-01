<?php


namespace ChatBot;

if ( ! class_exists( '\ChatBot\ChB_Updater' ) ) {
	require_once dirname( __FILE__ ) . '/ChB_Updater.php';
}

class ChB_Updater_WanyChat extends ChB_Updater {
	public const PLUGIN_ID = 'wany-chat';
	protected static ChB_Updater $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		$this->plugin_id   = self::PLUGIN_ID;
		$this->plugin_file = get_wy_plugin_dir_path() . self::PLUGIN_ID . '.php';
		parent::__construct();
	}

	protected function getUpdateActions() {
		return self::UPDATES_ACTIONS;
	}


	///---------------------UPDATES ACTIONS----------------------------------------

	//PERMANENT
	// '1.3.1.0' - 28.04.2022
	// '1.3.5.1' - 25.06.2022
	// '1.3.5.3' - 29.06.2022
	// '1.3.11.7' - 06.02.2023
	// '1.3.15.11' - 31.08.2023
	const UPDATES_ACTIONS = [ '1.3.1.0', '1.3.5.1', '1.3.5.4', '1.3.11.7', '1.3.14.11', '1.3.15.11' ];

	public static function updateActionV1_3_15_11() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_15_11 ----------------->' );

		delete_metadata('user', 0, 'rrbot_usr_promo', '', true );

		return true;
	}

	public static function updateActionV1_3_1_0() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_1_0 ----------------->' );

		$tokens_options = get_option( 'RRB_TOKENS' );
		if ( ! empty( $tokens_options['page_id'] ) ) {
			$tokens_options['fb_page_id'] = $tokens_options['page_id'];
			update_option( 'RRB_TOKENS', $tokens_options );
		}

		$options2update = [ 'mc_page_name' => ChB_Settings()->getParam( 'fb_page_name' ) ];
		ChB_Common::my_log( $options2update, true, 'updating options' );
		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}

	public static function updateActionV1_3_5_1() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_5_1 ----------------->' );

		$options2update = [ 'addr_use_phone' => 1 ];
		ChB_Common::my_log( $options2update, true, 'updating options' );
		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}

	public static function updateActionV1_3_5_4() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_5_4 ----------------->' );

		$options2update = [ 'addr_use_name' => 1 ];
		ChB_Common::my_log( $options2update, true, 'updating options' );
		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}

	public static function updateActionV1_3_11_7() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_11_7 ----------------->' );

		$val = ChB_Settings()->getParam( 'web_redirect' );
		$val = ( $val == 1 ?
			$val = ChB_Settings::SETTING_WEB_REDIRECT_BUY :
			( $val == 2 ? ChB_Settings::SETTING_WEB_REDIRECT_PLACE_ORDER :
				ChB_Settings::SETTING_WEB_REDIRECT_NO
			) );

		$options2update = [ 'web_redirect' => $val ];
		ChB_Common::my_log( $options2update, true, 'updating options' );
		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}

	public static function updateActionV1_3_14_11() {
		ChB_Common::my_log( '<----------------- updateActionV1_3_14_11 ----------------->' );

		$options2update = [ 'managers2notify_on_tth' => ChB_Settings()->getParam('managers2notify') ];
		ChB_Common::my_log( $options2update, true, 'updating options' );
		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wany_hook_plugin_update_' . ChB_Updater_WanyChat::PLUGIN_ID, function () {
		ChB_Updater_WanyChat::instance()->updatePlugin();
	} );

	add_action( 'wany_hook_plugin_update', function () {
		ChB_Updater_WanyChat::instance()->updatePlugin();
	}, 10 );

	add_action( 'wany_hook_check_schedule_update_version_db', function () {
		ChB_Updater_WanyChat::instance()->checkScheduleUpdatePluginVersionInDB();
	} );
}