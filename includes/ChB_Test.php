<?php

namespace ChatBot;


class ChB_Test {

	public static function getTestFlow( ChatBot $ChB ) {

		$text = '';
		$ChB->setParam( 'val', 'ok, got it.' . "\n" . $text );

		return ChB_FLOW_Common::sendTextMessage( $ChB );

	}

	public static function run() {
		ini_set( 'display_errors', 'On' );
		echo 'empty;)  ';
	}
}