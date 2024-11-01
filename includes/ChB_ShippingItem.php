<?php


namespace ChatBot;


class ChB_ShippingItem {

	public ?string $title;
	public ?string $price;
	public bool $is_order;
	public ?string $wy_shipping_method_id;
	public ?string $wc_shipping_method_id;
	public ?string $wc_shipping_instance_id;
	/** @var callable */
	public $tail_messages_callback;
	public bool $undefined;

	public function __construct( $title = '', $price = null, $is_order = false, $wy_shipping_method_id = null, $wc_shipping_method_id = null, $wc_shipping_instance_id = null, $tail_messages_callback = null, $undefined = false ) {
		$this->title                   = $title;
		$this->price                   = $price;
		$this->is_order                = $is_order;
		$this->wy_shipping_method_id   = $wy_shipping_method_id;
		$this->wc_shipping_method_id   = $wc_shipping_method_id;
		$this->wc_shipping_instance_id = $wc_shipping_instance_id;
		$this->tail_messages_callback  = $tail_messages_callback;
		$this->undefined               = $undefined;

		if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_FREE ) {
			$this->price                  = 0;
			$this->tail_messages_callback = null;
		}
		if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_FLAT ) {
			$this->tail_messages_callback = null;
		}

		if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_WOO ) {
			$this->price = 0;
		}
	}

	public static function getWYShippingItem() {
		return new ChB_ShippingItem(
			ChB_Settings()->getParam( 'shipping_cost_code' ),
			ChB_Settings()->getParam( 'shipping_cost' ),
			false,
			ChB_Settings()->getParam( 'shipping_cost_code' ),
			null,
			null,
			function () {
				return ChB_Settings()->getParam( 'shipping_cost_text' );
			}
		);
	}

	public static function getUndefinedShippingItem() {
		return new ChB_ShippingItem(
			'',
			null,
			false,
			null,
			null,
			null,
			null,
			true
		);
	}


	public function printShipping() {


		if ( $this->is_order ) {
			$text = ( in_array( $this->title, [
				ChB_Common::SHIPPING_FLAT,
				ChB_Common::SHIPPING_FREE,
				ChB_Common::SHIPPING_MANUAL
			] ) ?
				'' : $this->title . ' ' );

			return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . ChB_Common::printPrice( $this->price ) . "\n" . $text;

		} elseif ( $this->wc_shipping_method_id ) {

			$lines = [];
			if ( $this->wc_shipping_method_id === 'free_shipping' ) {
				$lines[] = ChB_Lang::translate( ChB_Lang::LNG0078 );
			} elseif ( $this->price ) {
				$lines[] = ChB_Common::printPrice( $this->price );
			}
			if ( $this->title ) {
				$lines[] = $this->title;
			}
			if ( is_callable( $this->tail_messages_callback ) && $tail_text = call_user_func( $this->tail_messages_callback ) ) {
				$lines[] = $tail_text;
			}

			return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . implode( "\n", $lines );

		} elseif ( $this->wy_shipping_method_id ) {

			if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_FREE ) {
				return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . ChB_Lang::translate( ChB_Lang::LNG0078 );
			}

			if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_MANUAL ) {

				$lines = $this->price ? [ ChB_Lang::translate( ChB_Lang::LNG0019 ) . ' ' . ChB_Common::printPrice( $this->price ) ] : [];

				if ( is_callable( $this->tail_messages_callback ) && $tail_text = call_user_func( $this->tail_messages_callback ) ) {
					$lines[] = $tail_text;
				}

				return implode( "\n", $lines );
			}

			if ( $this->wy_shipping_method_id === ChB_Common::SHIPPING_WOO ) {
				if ( is_callable( $this->tail_messages_callback ) && $tail_text = call_user_func( $this->tail_messages_callback ) ) {
					return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . $tail_text;
				} else {
					return '';
				}
			}

			return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . ChB_Common::printPrice( $this->price );

		} elseif ( $this->undefined ) {

			if ( is_callable( $this->tail_messages_callback ) && $tail_text = call_user_func( $this->tail_messages_callback ) ) {
				return $tail_text;
			}

			return '';
		}

		return ChB_Lang::translate( ChB_Lang::LNG0023 ) . ': ' . ChB_Lang::translate( ChB_Lang::LNG0170 );

	}

}