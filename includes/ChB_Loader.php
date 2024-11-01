<?php

namespace ChatBot;

class ChB_Loader {
	public static function load( $dir = null ) {
		self::includes( $dir );
	}

	public static function includes( $dir ) {

		$dirs = [
			( $dir ? $dir : dirname( __FILE__ ) )
		];

		foreach ( $dirs as $cur_dir ) {
			foreach ( scandir( $cur_dir ) as $filename ) {
				$path = $cur_dir . '/' . $filename;
				if ( is_file( $path ) && strpos( $path, '.php' ) !== false ) {
					include_once $path;
				}
			}
		}
	}
}