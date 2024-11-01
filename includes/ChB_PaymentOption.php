<?php


namespace ChatBot;


class ChB_PaymentOption {

	public string $id;
	public string $title;
	public ?string $select_payment_button_title;

	public string $order_init_status;
	public bool $is_confirmation_by_reciept_upload;
	public bool $is_cod;
	public string $order_created_text;
	public string $payment_text;
	/** @var callable */
	private $payment_button_callback;

	/** @var callable */
	private $tail_messages_callback;

	private static array $_registered_payment_options;

	public const WY_PAYMENT_OPTION_EMPTY = '_wany_empty_';
	public const WY_PAYMENT_OPTION_COD = '_wany_cod_';
	public const WY_PAYMENT_OPTION_MP = '_wany_mp_';
	public const WY_PAYMENT_OPTION_RK = '_wany_rk_';

	public function __construct( $id, $title, $select_payment_button_title, $order_init_status, $is_confirmation_by_reciept_upload, $order_created_text, $payment_text, $payment_button_callback, $tail_messages_callback ) {

		$this->id                                = $id;
		$this->title                             = $title;
		$this->select_payment_button_title       = ( $select_payment_button_title ? $select_payment_button_title : $this->title );
		$this->order_init_status                 = $order_init_status;
		$this->is_confirmation_by_reciept_upload = $is_confirmation_by_reciept_upload;
		$this->order_created_text                = $order_created_text;
		$this->payment_text                      = $payment_text;
		$this->payment_button_callback           = $payment_button_callback;
		$this->tail_messages_callback            = $tail_messages_callback;
	}

	private static function _registerPaymentOptions( ?string $shipping_option_id ) {

		self::$_registered_payment_options = [];
		if ( ChB_Settings()->getParam( 'use_cod' ) ) {
			self::$_registered_payment_options[ self::WY_PAYMENT_OPTION_COD ] = self::getPaymentOptionCOD();
		}

		if ( ChB_Settings()->auth->mercadoPagoIsOn() ) {
			self::$_registered_payment_options[ self::WY_PAYMENT_OPTION_MP ] = self::getPaymentOptionMercadoPago();
		}

		if ( ChB_Settings()->auth->robokassaIsOn() ) {
			self::$_registered_payment_options[ self::WY_PAYMENT_OPTION_RK ] = self::getPaymentOptionRobokassa();
		}


		self::$_registered_payment_options = apply_filters( 'wany_hook_register_payment_options', self::$_registered_payment_options, $shipping_option_id );
	}

	/**
	 * @param string|null $id
	 * @param string|null $shipping_option_id
	 *
	 * @return ChB_PaymentOption
	 */
	public static function getPaymentOptionById( ?string $id, ?string $shipping_option_id ) {


		if ( ! isset( self::$_registered_payment_options ) ) {
			self::_registerPaymentOptions( $shipping_option_id );
		}

		return ( $id && isset( self::$_registered_payment_options[ $id ] ) && self::$_registered_payment_options[ $id ] instanceof ChB_PaymentOption ?
			self::$_registered_payment_options[ $id ] : self::getPaymentOptionDefault()
		);

	}

	/**
	 * @param string|null $shipping_option_id
	 *
	 * @return ChB_PaymentOption[]
	 */
	public static function getAvailablePaymentOptions( ?string $shipping_option_id ) {
		if ( ! isset( self::$_registered_payment_options ) ) {
			self::_registerPaymentOptions( $shipping_option_id );
		}

		return self::$_registered_payment_options;
	}

	public static function getPaymentOptionCOD() {
		return new ChB_PaymentOption(
			self::WY_PAYMENT_OPTION_COD,
			ChB_Lang::translate( ChB_Lang::LNG0064 ),
			null,
			ChB_Order::getInitStatus(),
			false,
			ChB_Lang::translate( ChB_Lang::LNG0029 ) . "\n" .
			ChB_Lang::translate( ChB_Lang::LNG0030 ),
			ChB_Lang::translate( ChB_Lang::LNG0064 ),
			null,
			function ( ChatBot $ChB, $messages ) {

				$messages[] = [
					'type'    => 'text',
					'text'    => ChB_Lang::translate( ChB_Lang::LNG0031 ),
					'buttons' => [ ChB_FLOW_Catalogue::getCatalogButton( $ChB, ChB_Lang::LNG0032 ) ]
				];

				return $messages;
			}
		);
	}

	public static function getPaymentOptionDefault() {
		return new ChB_PaymentOption(
			self::WY_PAYMENT_OPTION_EMPTY,
			'',
			'',
			ChB_Order::getInitStatus(),
			false,
			ChB_Lang::translate( ChB_Lang::LNG0029 ) . "\n" .
			ChB_Lang::translate( ChB_Lang::LNG0030 ),
			'',
			null,
			function ( ChatBot $ChB, $messages ) {

				$messages[] = [
					'type'    => 'text',
					'text'    => ChB_Lang::translate( ChB_Lang::LNG0031 ),
					'buttons' => [ ChB_FLOW_Catalogue::getCatalogButton( $ChB, ChB_Lang::LNG0032 ) ]
				];

				return $messages;
			}
		);
	}

	public static function getPaymentOptionMercadoPago() {
		return new ChB_PaymentOption(
			self::WY_PAYMENT_OPTION_MP,
			'Mercado Pago',
			null,
			ChB_Order::getInitStatus(),
			false,
			ChB_Lang::translate( ChB_Lang::LNG0030 ),
			'Mercado Pago: ' . ChB_Lang::translate( ChB_Lang::LNG0178 ),
			function ( ChatBot $ChB, $order_id ) {
				return ChB_PaymentMercadoPago::genPaymentButton( $ChB, $order_id );
			},
			null
		);
	}

	public static function getPaymentOptionRobokassa() {
		return new ChB_PaymentOption(
			self::WY_PAYMENT_OPTION_RK,
			'Robokassa',
			null,
			ChB_Order::getInitStatus(),
			false,
			ChB_Lang::translate( ChB_Lang::LNG0030 ),
			ChB_Common::mb_ucfirst( ChB_Lang::translate( ChB_Lang::LNG0178 ) ),
			function ( ChatBot $ChB, $order_id ) {
				return ChB_PaymentRobokassa::genPaymentButton( $ChB, $order_id );
			},
			null
		);
	}

	public function getMessagesOnOrderCreateUpdate( ChatBot $ChB, $order_id, $shipping_option_id, $is_order_create ) {

		$messages = [
			[
				'type'    => 'text',
				'text'    => ChB_Lang::translateWithPars( $is_order_create ? $this->order_created_text : ChB_Lang::LNG0180, ChB_Common::printNumberNoSpaces( $order_id ) ),
				'buttons' => [ ChB_FLOW_MyOrders::getOpenOrderButton( $ChB, $order_id ) ]
			]
		];

		if ( $this->payment_text ) {

			$text = strtoupper( ChB_Lang::translate( ChB_Lang::LNG0171 ) );

			if ( $this->is_confirmation_by_reciept_upload ) {
				$text .= "\n" . ChB_Common::getKeyCapEmoji( 1 ) . ' ' . ChB_Lang::translate( ChB_Lang::LNG0173 );
			}

			$text .= "\n" . $this->payment_text;

			if ( $this->is_confirmation_by_reciept_upload ) {
				$text .= "\n\n" . ChB_Common::getKeyCapEmoji( 2 ) . ' ' . ChB_Lang::translate( ChB_Lang::LNG0172 );
			}

			$message = [
				'type' => 'text',
				'text' => $text
			];

			if ( is_callable( $this->payment_button_callback ) ) {
				$message['buttons'] = [ call_user_func( $this->payment_button_callback, $ChB, $order_id ) ];
			}


			if ( $button = ChB_FLOW_NewOrder::makeChoosePayment4OrderButton( $ChB, $order_id, ChB_Lang::translate( ChB_Lang::LNG0179 ), $shipping_option_id ) ) {
				$message['buttons'][] = $button;
			}
			$messages[] = $message;
		}

		if ( is_callable( $this->tail_messages_callback ) ) {
			$messages = call_user_func( $this->tail_messages_callback, $ChB, $messages );
		}

		return $messages;
	}

}