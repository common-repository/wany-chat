<?php

namespace ChatBot;

class ChB_Debug {

	public const SUPER_DEBUG_SETTINGS_PAR = 'super_debug';
	private const DEBUG_SETTINGS_PAR = 'dbg';

	private static function addSuperDebugUser( ChB_User $user ) {
		$userIdsSeparatedByHash = ChB_Settings()->getParam( self::SUPER_DEBUG_SETTINGS_PAR );
		$userIdsArray           = $userIdsSeparatedByHash ? explode( '#', $userIdsSeparatedByHash ) : [];
		if ( ! in_array( $user->wp_user_id, $userIdsArray, true ) ) {
			$userIdsArray[] = $user->wp_user_id;
			ChB_Settings()->setParam( self::SUPER_DEBUG_SETTINGS_PAR, implode( '#', $userIdsArray ), true );
		}
	}

	private static function removeSuperDebugUser( ChB_User $user ) {
		$userIdsSeparatedByHash = ChB_Settings()->getParam( self::SUPER_DEBUG_SETTINGS_PAR );
		$userIdsArray           = explode( '#', $userIdsSeparatedByHash );
		if ( ( $key = array_search( $user->wp_user_id, $userIdsArray, false ) ) !== false ) {
			unset( $userIdsArray[ $key ] );
			ChB_Settings()->setParam( self::SUPER_DEBUG_SETTINGS_PAR, implode( '#', $userIdsArray ), true );
		}
	}

	public static function userIsSuperDebug( ChB_User $user ) {
		$userIdsSeparatedByHash = ChB_Settings()->getParam( self::SUPER_DEBUG_SETTINGS_PAR );

		return $userIdsSeparatedByHash && in_array( $user->wp_user_id, explode( '#', $userIdsSeparatedByHash ) );
	}

	/**
	 * @param ChB_User $user
	 * @param $text string different options for text (BEFORE substr):
	 *          - dbgparspr1 - enables wany admin as super debug user
	 *          - dbgparspr0 - disables wany admin as super debug user
	 *          - dbgparlg2 - force-enables wany log on client side
	 *          - dbgparlg1 - enables wany log on client side, if it was not enabled
	 *          - dbgparlg0 - disables wany log on client side
	 *          - dbgparlvl2 - max detailed logging
	 *          - dbgparlvl1 - detailed logging
	 *          - dbgparlvl0 - basic logging
	 * @param array $messagingNode
	 */
	public static function processDebugKeyword( ChB_User $user, $text, array $messagingNode ) {

		$res = '--';
		if ( $text === 'spr1' ) {
			if ( isset( $messagingNode[ self::SUPER_DEBUG_SETTINGS_PAR ] ) ) {
				self::addSuperDebugUser( $user );
				$res = $text;
			}
		} elseif ( $text === 'spr0' ) {
			self::removeSuperDebugUser( $user );
			$res = $text;
		} elseif ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $user ) || self::userIsSuperDebug( $user ) ) {
			if ( $text === 'lg2' ) {
				self::enableLogging( true );
				$res = $text;
			} elseif ( $text === 'lg1' ) {
				self::enableLogging( false );
				$res = $text;
			} elseif ( $text === 'lg0' ) {
				self::enableLogging( true, true );
				$res = $text;
			} elseif ( $text === 'lgn' ) {
				if ( $log_path = ChB_Settings()->getParam( 'log_path' ) ) {
					if ( $parts = explode( '/', $log_path ) ) {
						$res = $parts[ sizeof( $parts ) - 1 ];
					}
				}

			} elseif ( $text === 'lvl2' || $text === 'lvl1' ) {
				//setting log level
				ChB_Settings()->setParam( self::DEBUG_SETTINGS_PAR, $text, true );
				$res = $text;
			} elseif ( $text === 'lvl0' ) {
				//setting log level
				ChB_Settings()->setParam( self::DEBUG_SETTINGS_PAR, null, true );
				$res = '0';
			}
		}

		return $res;
	}

	public static function enableLogging( $force = false, $disable = false ) {

		if ( ChB_Settings()->log_path && ! $force ) {
			return;
		}

		$log_path = ( $disable ? '' : self::getDefaultWYLogPath() );

		if ( $log_path ) {
			if ( ! file_exists( $log_path ) ) {
				$f = fopen( $log_path, 'w' );
				fputs( $f, 'START' );
				fclose( $f );
			}
		}

		ChB_Settings()->log_path = $log_path;
		ChB_Settings()->setParam( 'log_path', $log_path, true );
	}

	private static function getDefaultWYLogPath() {

		$wy_token = ChB_Settings()->auth->getWYToken();

		return wp_get_upload_dir()['basedir'] . '/wy' . ( $wy_token ? substr( $wy_token, 0, 12 ) : ChB_Common::my_rand_string( 12 ) ) . '.html';

	}

	public static function isDebug() {
		return ! empty( ChB_Settings()->getParam( self::DEBUG_SETTINGS_PAR ) );
	}

	public static function getDebugLevel() {
		return ChB_Settings()->getParam( self::DEBUG_SETTINGS_PAR );
	}
}
