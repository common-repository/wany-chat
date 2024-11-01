<?php


namespace ChatBot;


class ChB_PaymentRobokassa extends ChB_Payment {

	public const ROBOKASSA_DISABLED = 'disabled';
	public const ROBOKASSA_TEST_ENABLED = 'test_enabled';
	public const ROBOKASSA_ENABLED = 'enabled';

	public const GET_PAR_ROBOKASSA_PAY = 'wy_rk_pay';
	public const GET_PAR_ROBOKASSA_BACK = 'wy_rk_back';

	private const TEST_LEVEL = self::TEST_LEVEL_PROD;

	public static function genPaymentButton( ChatBot $ChB, $order_id ) {

		return self::_genPaymentButton( $ChB, $order_id, self::GET_PAR_ROBOKASSA_PAY, ChB_Lang::translate( ChB_Lang::LNG0189 ) );
	}

	public static function init() {

		if ( ! empty( $_GET[ self::GET_PAR_ROBOKASSA_PAY ] ) ) {
			chb_load();
			add_action( 'template_redirect', [ 'ChatBot\ChB_PaymentRobokassa', 'redirectToPayment' ], 10, 0 );
		} elseif ( ! empty( $_REQUEST[ self::GET_PAR_ROBOKASSA_BACK ] ) ) {
			if ( $_REQUEST[ self::GET_PAR_ROBOKASSA_BACK ] === 'result' ) {
				chb_load();
				add_action( 'parse_request', [ 'ChatBot\ChB_PaymentRobokassa', 'processResult' ], 10, 0 );
			} elseif ( $_REQUEST[ self::GET_PAR_ROBOKASSA_BACK ] === 'success' || $_REQUEST[ self::GET_PAR_ROBOKASSA_BACK ] === 'fail' ) {
				chb_load();
				add_action( 'template_redirect', [ 'ChatBot\ChB_PaymentRobokassa', 'processBackUrl' ], 10, 0 );
			}
		}
	}

	public static function redirectToPayment() {

		$wc_order = self::checkOrderStatus( self::GET_PAR_ROBOKASSA_PAY, self::TEST_LEVEL );
		$order_id = $wc_order->get_id();

		$is_test = ChB_Settings()->auth->getRobokassaSetting( 'is_test' );
		$login   = ChB_Settings()->auth->getRobokassaSetting( 'login' );
		$pass1   = ChB_Settings()->auth->getRobokassaSetting( 'pass1' );

		$out_sum = self::getOutSum( $wc_order );

		// build CRC value
		$crc = md5( "$login:$out_sum:$order_id:$pass1" );

		$url = "https://auth.robokassa.ru/Merchant/Index.aspx?MerchantLogin=$login" .
		       "&OutSum=$out_sum&InvId=$order_id&Description=&SignatureValue=$crc" .
		       ( $is_test ? '&isTest=1' : '' );

		// redirecting to payment
		if ( self::TEST_LEVEL === self::TEST_LEVEL_PROD ) {

			//main scenario
			wp_redirect( $url );
		} else {

			//for testing

			$status = 'success';
			//$status = 'fail';

			if ( $status == 'success' ) {
				$pass2 = ChB_Settings()->auth->getRobokassaSetting( 'pass2' );
				$url   = add_query_arg(
					         [
						         self::GET_PAR_ROBOKASSA_BACK => 'result',
						         'OutSum'                     => $out_sum,
						         'InvId'                      => $order_id,
						         'SignatureValue'             => md5( "$out_sum:$order_id:$pass2" )
					         ], site_url() ) .
				         ( $is_test ? '&isTest=1' : '' );
				$res   = ChB_Common::sendPost( $url, [] );

				if ( $res !== 'OK' . $order_id ) {
					$status = 'fail';
				}
			}
			$url = add_query_arg(
				       [
					       self::GET_PAR_ROBOKASSA_BACK => $status,
					       'OutSum'                     => $out_sum,
					       'InvId'                      => $order_id,
					       'SignatureValue'             => md5( "$out_sum:$order_id:$pass1" )
				       ], site_url() ) .
			       ( $is_test ? '&isTest=1' : '' );

			wp_redirect( $url );

		}
		exit( 0 );

	}

	private static function checkCRC( $type = null ) {

		$pass_field = ( $type === 'result' ) ? 'pass2' : 'pass1';

		$out_sum  = ChB_Common::sanitizeText( $_REQUEST["OutSum"] );
		$order_id = ChB_Common::sanitizeText( $_REQUEST["InvId"] );
		$crc      = strtoupper( ChB_Common::sanitizeText( $_REQUEST["SignatureValue"] ) );

		// building crc to check
		$pass    = ChB_Settings()->auth->getRobokassaSetting( $pass_field );
		$cur_crc = strtoupper( md5( "$out_sum:$order_id:$pass" ) );

		return [ $cur_crc == $crc, $order_id ];
	}

	public static function processResult() {

		list ( $res, $order_id, ) = self::checkCRC( 'result' );
		if ( ! $res ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP300: crc doesn\'t match, order_id=' . $order_id );
			self::exitSomethingWentWrong( 'EP300', $order_id, 'ERROR', false );
			exit ( 0 );
		}

		parent::processOrder( $order_id, self::TEST_LEVEL );

		echo 'OK' . ChB_Common::printNumberNoSpaces( $order_id );
		exit ( 0 );
	}

	public static function processBackUrl() {

		list ( $res, $order_id, ) = self::checkCRC();
		if ( ! $res ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP310: crc doesn\'t match, order_id=' . $order_id );
			self::exitSomethingWentWrong( 'EP310', $order_id );
		}

		$back_url_status = ( $_REQUEST[ self::GET_PAR_ROBOKASSA_BACK ] === 'success' ? 'success' : 'fail' );

		if ( $back_url_status !== 'success' ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP320: back_url_status=' . $back_url_status . ' order_id=' . $order_id );
			self::exitSomethingWentWrong( 'EP320', $order_id );
		}

		parent::printSuccess( $order_id );
	}

	private static function getOutSum( \WC_Order $wc_order ) {
		return number_format( $wc_order->get_total(), 2, '.', '' );
	}

}