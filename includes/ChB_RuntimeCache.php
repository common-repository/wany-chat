<?php


namespace ChatBot;


class ChB_RuntimeCache {

	private static array $_data;

	public static function get( $key ) {
		return ( isset( self::$_data[ $key ] ) ? self::$_data[ $key ] : null );
	}

	public static function set( $key, $value ) {
		self::$_data[ $key ] = $value;
	}
}