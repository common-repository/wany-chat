<?php


namespace ChatBot;


abstract class ChB_Payment {

	//production
	protected const TEST_LEVEL_PROD = 0;
	//order status check, no redirect to payment system
	protected const TEST_LEVEL_NO_REDIRECT = 1;
	//no order status check, no redirect to payment system
	protected const TEST_LEVEL_NO_ORDER_CHECK_NO_REDIRECT = 2;

	protected static function _genPaymentButton( ChatBot $ChB, $order_id, $pay_par_name, $caption ) {

		$wc_order = wc_get_order( $order_id );
		if ( ! ( $wc_order instanceof \WC_Order ) ) {
			ChB_Common::my_log( __FUNCTION__ . 'ERROR: order_id=' . $order_id );

			return [];
		}
		$pay_par = ChB_Encryption::encrypt( $order_id . '#' . $wc_order->get_status() );

		return [
			'type'    => 'url',
			'caption' => $caption,
			'url'     => add_query_arg( [ $pay_par_name => $pay_par ], site_url() )
		];
	}

	public static function checkOrderStatus( $pay_par_name, $test_level ) {
		$pars = explode( '#', ChB_Encryption::decrypt( $_GET[ $pay_par_name ] ) );
		if ( count( $pars ) < 2 ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP130: explode went wrong, get_par=' . $_GET[ $pay_par_name ] );
			self::exitSomethingWentWrong( 'EP130' );
		}

		list( $order_id, $expected_order_status ) = $pars;
		$wc_order = wc_get_order( $order_id );
		if ( ! ( $wc_order instanceof \WC_Order ) ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP140: order_id=' . $order_id . ' expected_order_status=' . $expected_order_status );
			self::exitSomethingWentWrong( 'EP140' );
		}

		if ( $test_level <= self::TEST_LEVEL_NO_REDIRECT ) {
			if ( $wc_order->get_status() !== $expected_order_status ) {
				ChB_Common::my_log( __FUNCTION__ . ' ERROR EP150: wrong order status order_id=' . $order_id . ' expected_order_status=' . $expected_order_status . ' current_status=' . $wc_order->get_status() );
				self::exitSomethingWentWrong( 'EP150', $wc_order );
			}
		}

		return $wc_order;
	}

	abstract public static function redirectToPayment();

	abstract public static function processBackUrl();

	protected static function processOrder( $order_id, $test_level, $expected_order_status = null ) {

		$wc_order = wc_get_order( $order_id );
		if ( ! ( $wc_order instanceof \WC_Order ) ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP100: order_id=' . $order_id . ' expected_order_status=' . $expected_order_status );
			self::exitSomethingWentWrong( 'EP100' );
		}

		if ( $test_level <= self::TEST_LEVEL_NO_REDIRECT && $expected_order_status ) {
			if ( $wc_order->get_status() !== $expected_order_status ) {
				ChB_Common::my_log( __FUNCTION__ . ' ERROR EP120: wrong order status order_id=' . $order_id . ' expected_order_status=' . $expected_order_status . ' current_status=' . $wc_order->get_status() );
				self::exitSomethingWentWrong( 'EP120', $wc_order );
			}
		}

		$wc_order->payment_complete();
		$wc_order->set_status( ChB_Order::getToShipStatus() );
		$wc_order->save();

		return $wc_order;
	}

	/**
	 * @param $order \WC_Order|string
	 */
	protected static function printSuccess( $order ) {

		$wc_order = is_string( $order ) ? wc_get_order( $order ) : $order;

		if ( ! ( $wc_order instanceof \WC_Order ) ) {
			ChB_Common::my_log( $order, 1, __FUNCTION__ . ' ERROR EP102: order=' );
			self::exitSomethingWentWrong( 'EP102' );
		}

		$wp_user_id = $wc_order->get_customer_id();
		if ( ! $wp_user_id ) {
			ChB_Common::my_log( __FUNCTION__ . ' ERROR EP103: order_id=' . $wc_order->get_id() );
			self::exitSomethingWentWrong( 'EP103' );
		}

		$ChB       = ChatBot::openTempChatBotSession( $wp_user_id, null, true );
		$is_mobile = ChB_Common::isMobile();

		self::printParagraph(
			ChB_Lang::translateWithPars( ChB_Lang::LNG0184, ChB_Common::printNumberNoSpaces( $wc_order->get_id() ) ) . ':' .
			( $is_mobile ? '<br>' : ' ' ) .
			ChB_Lang::translate( ChB_Lang::LNG0185 )
		);

		ChB_FLOW_Common::sendMessages( $ChB, ChB_FLOW_NewOrder::getMessagesOnSuccessfulPayment( $ChB, $wc_order ) );

		ChatBot::closeTempChatBotSession();

		exit( 0 );
	}

	/**
	 * @param null $code
	 * @param $order \WC_Order|string|null - order to use to determine user lang
	 * @param null $text
	 * @param bool $print_html
	 */
	public static function exitSomethingWentWrong( $code = null, $order = null, $text = null, $print_html = true ) {

		if ( $order ) {
			ChB_Settings()->setUserByOrder( $order );
		}

		if ( $text ) {
			$text .= ( $code ? '(code=' . $code . ')' : '' );
		} else {
			$text = ChB_Lang::translate( ChB_Lang::LNG0181 ) . ( $code ? '(code=' . $code . ')' : '' );
		}

		if ( $print_html ) {
			self::printParagraph( $text );
		} else {
			echo $text;
		}

		exit( 0 );
	}

	public static function printParagraph( $html ) {
		$is_mobile = ChB_Common::isMobile();
		?>
        <style>
            .rrb-wp-font {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                font-size: <?php echo $is_mobile ? '60px' : '20px'?>;
            }
        </style>
		<?php
		echo '<p class="rrb-wp-font">' . $html .

		     '<br><br><a href="javascript:void(0)" onclick="window.close();">&lt;&lt; ' .
		     ChB_Lang::translate( ChB_Lang::LNG0186 ) . '</a> 🤖</p>';

	}
}