<?php


namespace ChatBot;


class ChB_PromoItem {

	public const FILTER_CAT = 'CAT';
	public const FILTER_TAG = 'TAG';

	public string $promo_id;
	public bool $is_newly_created;
	public ?string $filter_type;
	public array $filter_values;
	public ?string $text_over_image_scope;

	public ?string $until;
	public ?\DateTime $dt_until;
	public string $percent;
	public bool $use_reminders;

	/**
	 * ChB_PromoItem constructor.
	 *
	 * @param string $promo_id
	 * @param bool $is_newly_created
	 * @param string $percent
	 * @param string $until
	 * @param string $filter_type
	 * @param string $filter_values
	 * @param string $text_over_image_scope
	 * @param bool $use_reminders
	 */
	public function __construct( $promo_id, $is_newly_created, $percent, $until, $filter_type, $filter_values, $text_over_image_scope, $use_reminders ) {
		$this->promo_id         = $promo_id;
		$this->is_newly_created = $is_newly_created;
		if ( $until ) {
			$this->dt_until = new \DateTime( $until, ChB_Settings()->timezone );
			$this->dt_until->setTime( 23, 59, 59 );
			$this->until = $this->dt_until->format( 'd.m.Y' );
		} else {
			$this->dt_until = null;
			$this->until    = '';
		}
		$this->percent               = $percent;
		$this->filter_type           = $filter_type;
		$this->filter_values         = is_array( $filter_values ) ? $filter_values : [];
		$this->text_over_image_scope = $text_over_image_scope;
		$this->use_reminders         = $use_reminders;
	}

	public function checkDiscount4User( ?\WC_Product $wc_product, $cat_id ) {

		if ( $wc_product ) {
			$parent_product_id = ( $wc_product instanceof \WC_Product_Variation ? $wc_product->get_parent_id() : $wc_product->get_id() );

			if ( ! $this->filter_type ) {
				$res = true;
			} else {
				if ( $wc_product instanceof \WC_Product_Variation ) {
					$wc_parent_product = wc_get_product( $parent_product_id );
				} else {
					$wc_parent_product = $wc_product;
				}

				if ( $this->filter_type === self::FILTER_CAT ) {
					$ids = $wc_parent_product->get_category_ids();
				} elseif ( $this->filter_type === self::FILTER_TAG ) {
					$ids = $wc_parent_product->get_tag_ids();
				} else {
					$ids = null;
				}

				$res = $this->checkDiscount4UserFilterValue( $ids );
			}
		} else {
			//checking discount for cat
			if ( $this->filter_type === self::FILTER_CAT ) {
				$res = $this->checkDiscount4UserFilterValue( $cat_id );
			} elseif ( $this->filter_type === self::FILTER_TAG ) {
				$res = false;
			} else {
				$res = true;
			}
		}

		return $res;
	}

	public function checkDiscount4UserFilterValue( $values ) {
		if ( ! $values || ! is_array( $this->filter_values ) ) {
			return false;
		}

		if ( is_array( $values ) ) {
			return ( ! empty( array_intersect( $this->filter_values, $values ) ) );
		} else {
			return in_array( $values, $this->filter_values );
		}
	}

	public function printPromo4UserConditions() {

		$res = null;
		if ( $this->percent ) {
			$res = '🔥 -' . $this->percent . '%';
			if ( $this->until ) {
				$res .= ' ' . ChB_Lang::translateWithPars( ChB_Lang::LNG0053, $this->until );
			}
		}

		return $res;
	}
}