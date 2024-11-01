<?php


namespace ChatBot;


class ChB_Encryption {

	private static $_instance;
	private $secret;
	private const OPTION_SECRET = 'wany_chat_secret';

	private function __construct() {
		$this->secret = get_option( self::OPTION_SECRET );
		if ( empty( $this->secret ) ) {
			try {
				$this->secret = bin2hex( random_bytes( 32 ) );
			} catch ( \Exception $ex ) {
			}
			if ( empty( $this->secret ) ) {
				$this->secret = uniqid( '', true );
			}
			update_option( self::OPTION_SECRET, $this->secret );
		}
	}

	private static function _get_instance() {

		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public static function encrypt( $text ) {

		$iv   = hex2bin( md5( microtime() . rand() ) );
		$data = openssl_encrypt( $text, 'AES-256-CBC', self::_get_instance()->secret, OPENSSL_RAW_DATA, $iv );

		return self::base64EncodeUrl( $iv . $data );
	}

	public static function decrypt( $text ) {

		$decoded = self::base64DecodeUrl( $text );
		$iv      = substr( $decoded, 0, 16 );
		$data    = substr( $decoded, 16 );

		return openssl_decrypt( $data, 'AES-256-CBC', self::_get_instance()->secret, OPENSSL_RAW_DATA, $iv );
	}

	public static function base64EncodeUrl( $string ) {
		return str_replace( [ '+', '/', '=' ], [ '.', '-', '_' ], base64_encode( $string ) );
	}

	public static function base64DecodeUrl( $string ) {
		return base64_decode( str_replace( [ '.', '-', '_' ], [ '+', '/', '=' ], $string ) );
	}
}