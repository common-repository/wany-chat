<?php


namespace ChatBot;


class ChB_Image {

	public static function getARGBColorByRGBA( $r, $g, $b, $a = 0 ) {
		return self::getDecARGBColorByHexARGColor( self::getHexARGBColorByRGBA( $r, $g, $b, $a ) );
	}

	public static function getDecARGBColorByHexARGColor( $color ) {
		if ( is_string( $color ) ) {
			$color = str_replace( '#', '', $color );
		}

		return hexdec( $color );
	}

	public static function getHexARGBColorByRGBA( $r, $g, $b, $a = 0 ) {
		//php uses ARGB hex
		return sprintf( "%02x%02x%02x%02x", $a, $r, $g, $b );
	}

	public static function getRGBAByARGBColor( $color ) {
		return [ ( $color >> 16 ) & 255, ( $color >> 8 ) & 255, $color & 255, ( $color >> 24 ) & 127 ];
	}

	public static function colorIsTransparent( $color ) {
		return ( ( $color >> 24 ) & 127 ) === 127;
	}

	public static function getColorBlockImageUrlById( $id ) {
		return ChB_Settings()->assets_url . 'img/color' . ( $id % 8 + 1 ) . '.png';
	}
}