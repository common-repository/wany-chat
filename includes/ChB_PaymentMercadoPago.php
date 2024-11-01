<?php


namespace ChatBot;


class ChB_PaymentMercadoPago extends ChB_Payment {

	public const GET_PAR_MERCADO_PAGO_PAY = 'wy_mp_pay';
	public const GET_PAR_MERCADO_PAGO_BACK = 'wy_mp_back';

	private const TEST_LEVEL = ChB_Payment::TEST_LEVEL_PROD;

	public static function genPaymentButton( ChatBot $ChB, $order_id ) {

		return self::_genPaymentButton( $ChB, $order_id, self::GET_PAR_MERCADO_PAGO_PAY, ChB_Lang::translateWithPars( ChB_Lang::LNG0183, 'Mercado Pago' ) );
	}

	public static function init() {

		if ( ! empty( $_GET[ self::GET_PAR_MERCADO_PAGO_PAY ] ) ) {
			chb_load();
			add_action( 'template_redirect', [ 'ChatBot\ChB_PaymentMercadoPago', 'redirectToPayment' ], 10, 0 );
		} elseif ( ! empty( $_GET[ self::GET_PAR_MERCADO_PAGO_BACK ] ) ) {
			chb_load();
			add_action( 'template_redirect', [ 'ChatBot\ChB_PaymentMercadoPago', 'processBackUrl' ], 10, 0 );
		}
	}

	public static function redirectToPayment() {

		$wc_order     = self::checkOrderStatus( self::GET_PAR_MERCADO_PAGO_PAY, self::TEST_LEVEL );
		$order_id     = $wc_order->get_id();
		$order_status = $wc_order->get_status();

		$enc_success = ChB_Encryption::encrypt( $order_id . '#' . $order_status . '#' . 'success' );
		$enc_pending = ChB_Encryption::encrypt( $order_id . '#' . $order_status . '#' . 'pending' );
		$enc_failure = ChB_Encryption::encrypt( $order_id . '#' . $order_status . '#' . 'failure' );
		$fields      = [
			'back_urls' => [
				'success' => add_query_arg( [ self::GET_PAR_MERCADO_PAGO_BACK => $enc_success ], site_url() ),
				'pending' => add_query_arg( [ self::GET_PAR_MERCADO_PAGO_BACK => $enc_pending ], site_url() ),
				'failure' => add_query_arg( [ self::GET_PAR_MERCADO_PAGO_BACK => $enc_failure ], site_url() )
			]
		];

		$order_details = ChB_Order::getOrderDetails( $wc_order, null );
		foreach ( $order_details['products_details'] as $product_details ) {
			$fields['items'][] = [
				'title'       => $product_details['title'],
				'picture_url' => $product_details['image_url'],
				'quantity'    => $product_details['quantity'],
				'currency_id' => get_woocommerce_currency(),
				'unit_price'  => ChB_Common::printFloat2Round( $product_details['total'] / $product_details['quantity'] )
			];
		}

		if ( $order_details['shipping_details'] instanceof ChB_ShippingDetails ) {
			if ( $val = $order_details['shipping_details']->getFirstName() ) {
				$fields['payer']['name'] = $val;
			}
			if ( $val = $order_details['shipping_details']->getLastName() ) {
				$fields['payer']['surname'] = $val;
			}
			if ( $val = $order_details['shipping_details']->getEmail() ) {
				$fields['payer']['email'] = $val;
			}
		}
		$fields['metadata'] = [ 'order_id' => $order_id ];

		if ( self::TEST_LEVEL === 0 ) {
			$response = wp_remote_post(
				'https://api.mercadopago.com/checkout/preferences',
				[
					'headers'     => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . ChB_Settings()->auth->getMercadoPagoAccessToken()
					],
					'timeout'     => 30,
					'body'        => json_encode( $fields ),
					'data_format' => 'body'
				]
			);

			if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
				ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : $response ), true, __FUNCTION__ . ' wp_remote_post ERROR' );

				self::exitSomethingWentWrong( 'EP400', $wc_order );
			}

			$init_point = 'init_point';
			$body       = json_decode( $response['body'], true );
			if ( empty( $body[ $init_point ] ) ) {
				ChB_Common::my_log( ( $response ), true, __FUNCTION__ . ' empty init_point=' . $init_point );

				self::exitSomethingWentWrong( 'EP410', $wc_order );
			}
		}
		// redirecting to payment
		if ( self::TEST_LEVEL === self::TEST_LEVEL_PROD ) {
			wp_redirect( $body[ $init_point ] );
		} else {
			wp_redirect( $fields['back_urls']['success'] );
//			wp_redirect( $fields['back_urls']['failure'] );
		}
		exit( 0 );
	}

	public static function processBackUrl() {

		$pars = explode( '#', ChB_Encryption::decrypt( $_GET[ self::GET_PAR_MERCADO_PAGO_BACK ] ) );
		if ( count( $pars ) < 3 ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP420: explode went wrong, get_par=' . $_GET[ self::GET_PAR_MERCADO_PAGO_BACK ] );
			self::exitSomethingWentWrong( 'EP420' );
		}

		list( $order_id, $expected_order_status, $back_url_status ) = $pars;


		if ( $back_url_status !== 'success' ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP430: back_url_status=' . $back_url_status . ' order_id=' . $order_id . ' expected_order_status=' . $expected_order_status );
			self::exitSomethingWentWrong( 'EP430', $order_id );
		}

		$wc_order = parent::processOrder( $order_id, self::TEST_LEVEL, $expected_order_status );

		parent::printSuccess( $wc_order );
	}


}