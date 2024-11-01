<?php


namespace ChatBot;


class ChB_WPAdminNotices {
	public const NOTICE_HELP_NOT_CONNECTED = 'help_nctd';
	public const NOTICE_HELP_CONNECTED = 'help_ctd';
	public const NOTICE_UPDATE_NOT_CONNECTED = 'upd_nctd';
	public const NOTICE_UPDATE_CONNECTED = 'upd_ctd';

	private const OPTION_NOTICES = 'wy_ntc';
	private ?array $ntcs;
	private int $wp_user_id;
	private $ts_now;

	public function __construct( $wp_user_id ) {
		$this->ntcs       = get_option( self::OPTION_NOTICES, [] );
		$this->wp_user_id = $wp_user_id;
		$this->ts_now     = time();
	}

	private function lastShowIsOlderThan( $ntc, $seconds ) {
		return ( empty( $this->ntcs[ $ntc ][ $this->wp_user_id ]['last_ts'] ) ? true : ( $this->ts_now - $this->ntcs[ $ntc ][ $this->wp_user_id ]['last_ts'] > $seconds ) );
	}

	private function setShow( $ntc ) {
		if ( empty( $this->ntcs[ $ntc ][ $this->wp_user_id ]['cnt'] ) ) {
			$this->ntcs[ $ntc ][ $this->wp_user_id ]['cnt'] = 1;
		} else {
			$this->ntcs[ $ntc ][ $this->wp_user_id ]['cnt'] ++;
		}

		$this->ntcs[ $ntc ][ $this->wp_user_id ]['last_ts'] = time();
		update_option( self::OPTION_NOTICES, $this->ntcs );
	}

	public static function getAll() {
		return get_option( self::OPTION_NOTICES, [] );
	}

	public static function initAdminNotices() {

		if ( ! current_user_can( 'update_plugins' ) || ! ( $wp_user_id = get_current_user_id() ) ) {
			return;
		}

		$new_version = ChB_Updater_WanyChat::instance()->checkPluginNewVersionWP();
		$wp_adm_ntcs  = new ChB_WPAdminNotices( $wp_user_id );
		$is_connected = ! empty( ChB_Settings()->auth->getWYToken() );
		$ntc          = null;
		$info         = null;

		$is_dissmisable = true;
		if ( $new_version ) {
			if ( $is_connected ) {
				$current_screen_is_settings = self::currentScreenIsSettings();
				if ( $current_screen_is_settings || $wp_adm_ntcs->lastShowIsOlderThan( self::NOTICE_UPDATE_CONNECTED, ChB_Constants::SECONDS_TWO_WEEKS ) ) {
					$ntc            = self::NOTICE_UPDATE_CONNECTED;
					$info           = $new_version;
					$is_dissmisable = ! $current_screen_is_settings;
				}
			} else {
				$current_screen_is_settings = self::currentScreenIsSettings();
				if ( $current_screen_is_settings || $wp_adm_ntcs->lastShowIsOlderThan( self::NOTICE_UPDATE_NOT_CONNECTED, ChB_Constants::SECONDS_TWO_WEEKS ) ) {
					$ntc            = self::NOTICE_UPDATE_NOT_CONNECTED;
					$info           = $new_version;
					$is_dissmisable = ! $current_screen_is_settings;
				}
			}
		}

		if ( ! $ntc ) {
			if ( $is_connected ) {
				if ( $wp_adm_ntcs->lastShowIsOlderThan( self::NOTICE_HELP_CONNECTED, ChB_Constants::SECONDS_TWO_WEEKS ) ) {
					if ( self::currentScreenIsSettings() ) {
						$ntc = self::NOTICE_HELP_CONNECTED;
					}
				}
			} else {
				$current_screen_is_settings = self::currentScreenIsSettings();
				if ( $current_screen_is_settings || $wp_adm_ntcs->lastShowIsOlderThan( self::NOTICE_HELP_NOT_CONNECTED, ChB_Constants::SECONDS_TWO_WEEKS ) ) {
					$ntc            = self::NOTICE_HELP_NOT_CONNECTED;
					$is_dissmisable = ! $current_screen_is_settings;
				}
			}
		}

		if ( $ntc ) {
			self::printAdminNotice( $ntc, $is_dissmisable, $info );
			if ( $is_dissmisable ) {
				$wp_adm_ntcs->setShow( $ntc );
			}
		}
	}

	private static function currentScreenIsSettings() {
		return ( $current_screen = get_current_screen() ) && $current_screen->id === ChB_Settings::SETTINGS_SCREEN_ID;
	}

	public static function getA( $text, $href, $target_blank = false ) {
		return '<u style="color:inherit; border-bottom: 2px dashed ' . ChB_Constants::COLOR_WANY_TEXT . '; text-decoration: none; cursor:pointer"><a ' . ( $target_blank ? 'target="_blank"' : '' ) . ' style="text-decoration: none; color: ' . ChB_Constants::COLOR_WANY_TEXT . '" href="' . $href . '">' . $text . '</a></u>';
	}

	public static function printAdminNotice( $ntc, $is_dismissible = true, $info = null ) {

		if ( $ntc === self::NOTICE_HELP_NOT_CONNECTED ) {
			$line1 = 'Questions or problems with Wany.Chat? Click ' . self::getA( 'HERE', 'https://m.me/wany.chat?ref=support1', true ) . ' to chat with us. It\'s FREE';
			$line2 = 'Sell more with Facebook Messenger and Instagram Direct';
		} elseif ( $ntc === self::NOTICE_HELP_CONNECTED ) {
			$line1 = 'Questions? Problems? Click ' . self::getA( 'HERE', 'https://m.me/wany.chat?ref=support1', true ) . ' to chat with us. It\'s FREE';
			$line2 = null;
		} elseif ( $ntc === self::NOTICE_UPDATE_NOT_CONNECTED || $ntc === self::NOTICE_UPDATE_CONNECTED ) {
			$line1 = 'New version of Wany.Chat is here! ' . self::getA( 'Update', admin_url( 'plugins.php#wany-chat' ) ) . ' to sell more with Facebook Messenger and Instagram Direct';
			$line2 = null;
		}

		if ( empty( $line1 ) ) {
			return;
		}

		$style = 'background-color: ' . ChB_Constants::COLOR_WANY_BG . ';';

		if ( $is_dismissible ) {
			$class = 'notice notice-info is-dismissible';
			$style .= $line2 ? 'padding: 10px 20px 0px 20px; ' : 'padding: 20px; ';
		} else {
			$class = '';
			$style .= 'margin-left: -20px; ';
			$style .= $line2 ? 'padding: 20px 20px 10px 20px; ' : 'padding: 20px; ';
		}

		echo '<div class="' . $class . '" style="' . $style . '">';
		echo '<h2 style="margin: 0; color: #000;">' . $line1 . '</h2>';
		if ( $line2 ) {
			echo '<div style="margin: 10px 0 10px 0;">' . $line2 . '</div>';
		}
		echo '</div>';
	}
}