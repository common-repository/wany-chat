<?php

namespace ChatBot;


class ChB_Promo {

	/**
	 * @var ChB_PromoItem[]
	 */
	private array $_promo_items;

	private ?array $_promo_par;

	private ?ChB_User $user; //user can be empty

	const TEXT_OVER_IMAGE_SCOPE_ALL = 'ALL';
	const TEXT_OVER_IMAGE_SCOPE_NONE = 'NONE';

	/**
	 * ChB_Promo constructor.
	 *
	 * @param ChB_User|null $user
	 * @param array|null $promo_par
	 */
	public function __construct( ?ChB_User $user, $promo_par ) {
		$this->user       = $user;
		$this->_promo_par = $promo_par;
	}

	public function initPromos() {

		if ( isset( $this->_promo_items ) ) {
			return;
		}

		$this->_promo_items = [];

		try {
			$bot_discounts   = ChB_Settings()->getParam( 'bot_discounts' );
			$bot_promo_items = isset( $bot_discounts['promo_items'] ) && is_array( $bot_discounts['promo_items'] ) ? $bot_discounts['promo_items'] : [];

			$today_dt = new \DateTime( 'now', ChB_Settings()->timezone );
			$today    = $today_dt->format( 'Ymd' );

			foreach ( $bot_promo_items as $promo_id => $bot_promo_item ) {
				if ( empty( $bot_promo_item['is_on'] ) ) {
					continue;
				}

				if ( ! empty( $bot_promo_item['is_test'] ) ) {
					//test promo items visible only for shop managers
					if ( ! $this->user || ! ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $this->user->fb_user_id ) ) {
						continue;
					}
				}

				$date_from = empty( $bot_promo_item['from'] ) ? '' : $bot_promo_item['from'];
				if ( $date_from && $today < $date_from ) {
					continue;
				}

				if ( ! empty( $bot_promo_item['calc_days'] ) ) {
					if ( ! isset( $user_promos ) ) {
						$user_promos = $this->getUserPromosFromDB();
					}

					if ( empty( $user_promos[ $promo_id ]['calc_date_until'] ) ) {
						$date_until                                  = $today_dt->modify( '+' . $bot_promo_item['calc_days'] . ' day' )->format( 'Ymd' );
						$user_promos[ $promo_id ]['calc_date_until'] = $date_until;
						$user_promos_changed                         = true;
					} else {
						$date_until = $user_promos[ $promo_id ]['calc_date_until'];
					}
				} else {
					$date_until = empty( $bot_promo_item['until'] ) ? '' : $bot_promo_item['until'];
				}

				if ( $date_until && $today > $date_until ) {
					continue;
				}

				$is_newly_created = false;
				if ( ! empty( $bot_promo_item['track_newly_created'] ) ) {
					if ( ! isset( $user_promos ) ) {
						$user_promos = $this->getUserPromosFromDB();
					}
					if ( ! isset( $user_promos[ $promo_id ] ) ) {
						$user_promos[ $promo_id ] = [];
						$user_promos_changed      = true;
					}
				}

				$this->_promo_items[ $promo_id ] = new ChB_PromoItem(
					$promo_id,
					$is_newly_created,
					empty ( $bot_promo_item['percent'] ) ? '' : $bot_promo_item['percent'],
					$date_until,
					empty ( $bot_promo_item['filter_type'] ) ? '' : $bot_promo_item['filter_type'],
					empty ( $bot_promo_item['filter_value'] ) ? '' : $bot_promo_item['filter_value'],
					empty ( $bot_promo_item['text_over_image_scope'] ) ? '' : $bot_promo_item['text_over_image_scope'],
					empty ( $bot_promo_item['use_reminders'] )
				);
			}

			if ( ! empty( $user_promos_changed ) && ! empty( $user_promos ) ) {
				$this->updateUserPromosInDB( $user_promos );
			}

		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'initPromos: ' . $e->getMessage() );
		}
	}

	public function getNewlyCreatedPromoItem() {
		$this->initPromos();
		foreach ( $this->_promo_items as $promo_item ) {
			if ( $promo_item->is_newly_created ) {
				return $promo_item;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function getUserPromosFromDB() {
		$res = ( $this->user ? get_user_meta( $this->user->wp_user_id, ChB_User::USER_ATTR_PROMO, true ) : [] );

		return ( is_array( $res ) ? $res : [] );
	}

	private function updateUserPromosInDB( $val ) {
		if ( $this->user ) {
			update_user_meta( $this->user->wp_user_id, ChB_User::USER_ATTR_PROMO, $val );
		}
	}

	public function checkDiscount4User( ?\WC_Product $wc_product, $cat_id = null ) {
		$this->initPromos();

		foreach ( $this->_promo_items as $promo_item ) {
			if ( $promo_item->checkDiscount4User( $wc_product, $cat_id ) ) {
				return $promo_item;
			}
		}

		return false;
	}

	private function getPriceDetails4SingleProduct( $wc_product, $regular_price, $sale_price, $quantity ) {
		if ( $regular_price > 0 ) {
			$percent = null;
			$until   = null;
			if ( $regular_price != $sale_price && $sale_price !== '' ) {
				$percent  = intval( round( 100 * ( 1 - $sale_price / $regular_price ) ) );
				$until    = null;
				$sale_sum = $sale_price * $quantity;
			} elseif ( $promo_item = $this->checkDiscount4User( $wc_product, null ) ) {
				$percent  = $promo_item->percent;
				$until    = $promo_item->until;
				$sale_sum = ChB_Common::ceil( $regular_price * $quantity * ( 100 - $percent ) * 0.01 );
			}

			if ( $percent === null ) {
				$res = [
					'regular_sum' => $regular_price * $quantity,
				];
			} else {
				$res = [
					'regular_sum' => $regular_price * $quantity,
					'sale_sum'    => $sale_sum,
					'percent'     => $percent,
					'until'       => $until
				];
			}
		}

		return empty( $res ) ? [ 'regular_sum' => '' ] : $res;
	}

	public function getPriceDetails( \WC_Product $wc_product, $quantity = 1 ) {

		if ( $wc_product instanceof \WC_Product_Variable ) {
			$var_prices = $wc_product->get_variation_prices();
			foreach ( $var_prices['regular_price'] as $var_id => $regular_price ) {
				$sale_price  = $var_prices['sale_price'][ $var_id ];
				$cur_details = $this->getPriceDetails4SingleProduct( $wc_product, $regular_price, $sale_price, $quantity );

				//checking percent is same
				$cur_percent = ( empty( $cur_details['percent'] ) ? null : $cur_details['percent'] );
				if ( ! isset( $percent ) ) {
					$percent = $cur_percent;
				} elseif ( $percent !== $cur_percent ) {
					$percent = null;
				}

				//checking until is same
				$cur_until = ( empty( $cur_details['until'] ) ? null : $cur_details['until'] );
				if ( ! isset( $until ) ) {
					$until = $cur_until;
				} elseif ( $until !== $cur_until ) {
					$until = null;
				}

				//min, max
				if ( ! isset( $min_regular_sum ) || $min_regular_sum > $cur_details['regular_sum'] ) {
					$min_regular_sum = $cur_details['regular_sum'];
				}
				if ( ! isset( $max_regular_sum ) || $max_regular_sum < $cur_details['regular_sum'] ) {
					$max_regular_sum = $cur_details['regular_sum'];
				}

				$sale_sum = isset( $cur_details['sale_sum'] ) ? $cur_details['sale_sum'] : $cur_details['regular_sum'];
				if ( ! isset( $min_sale_sum ) || $min_sale_sum > $sale_sum ) {
					$min_sale_sum = $sale_sum;
				}
				if ( ! isset( $max_sale_sum ) || $max_sale_sum < $sale_sum ) {
					$max_sale_sum = $sale_sum;
				}
			}

			$regular_sum_str = '';
			if ( isset( $min_regular_sum ) && isset( $max_regular_sum ) ) {
				$has_discount = ( isset( $min_sale_sum ) && isset( $max_sale_sum ) &&
				                  ( $min_sale_sum !== $min_regular_sum || $max_sale_sum !== $max_regular_sum ) );

				$print_range = ( $min_regular_sum != $max_regular_sum ) || ( $has_discount && $min_sale_sum != $max_sale_sum );

				$regular_sum_str = ChB_Common::printPrice( $min_regular_sum, $has_discount ) .
				                   ( $print_range ? ' - ' . ChB_Common::printPrice( $max_regular_sum, $has_discount ) : '' );
				if ( $has_discount ) {
					$sale_sum_str = ChB_Common::printPrice( $min_sale_sum, false ) .
					                ( $print_range ? ' - ' . ChB_Common::printPrice( $max_sale_sum, false ) : '' );
				}
			}

			$min_sum = ( isset( $min_sale_sum ) ? $min_sale_sum : ( isset( $min_regular_sum ) ? $min_regular_sum : 0 ) );
			$max_sum = ( isset( $max_sale_sum ) ? $max_sale_sum : ( isset( $max_regular_sum ) ? $max_regular_sum : 0 ) );

			$res = [ 'regular_sum_str' => $regular_sum_str, 'avg_sum' => ( $min_sum + $max_sum ) * 0.5 ];

			if ( isset( $sale_sum_str ) ) {
				$res['sale_sum_str'] = $sale_sum_str;
			}

			if ( ! empty( $percent ) ) {
				$res['percent'] = $percent;
			}
			if ( ! empty( $until ) ) {
				$res['until'] = $until;
			}

		} elseif ( $wc_product instanceof \WC_Product_Variation || $wc_product instanceof \WC_Product_Simple ) {
			$res = $this->getPriceDetails4SingleProduct( $wc_product, $wc_product->get_regular_price(), $wc_product->get_sale_price(), $quantity );

			$res['regular_sum_str'] = ChB_Common::printPrice( $res['regular_sum'], isset( $res['sale_sum'] ) );
			if ( isset( $res['sale_sum'] ) ) {
				$res['sale_sum_str'] = ChB_Common::printPrice( $res['sale_sum'], false );
			}

			if ( isset( $res['sale_sum'] ) ) {
				$res['sum'] = $res['sale_sum'];
			} else {
				$res['sum'] = $res['regular_sum'];
			}

			$res['avg_sum'] = $res['sum'];
		}

		if ( ! empty( $res['percent'] ) ) {
			$res['promo_conditions'] = '🔥 -' . $res['percent'] . '%';
			if ( ! empty( $res['until'] ) ) {
				$res['promo_conditions'] .= ' ' . ChB_Lang::translateWithPars( ChB_Lang::LNG0053, $res['until'] );
			}
		}

		if ( isset( $res['sale_sum_str'] ) ) {
			$res['sum_str'] = $res['sale_sum_str'];
		} elseif ( isset( $res['regular_sum_str'] ) ) {
			$res['sum_str'] = $res['regular_sum_str'];
		}

		if ( empty( $res ) ) {
			$res = [ 'regular_sum' => '', 'regular_sum_str' => '', 'sum' => '', 'sum_str' => '', 'avg_sum' => '' ];
		}

		$res['all_str'] = ( empty( $res['sale_sum_str'] ) ? '' : '👉 ' . $res['sale_sum_str'] . "\n" ) .
		                  ( empty( $res['promo_conditions'] ) ? '' : '👉 ' . $res['promo_conditions'] . "\n" ) .
		                  ( empty( $res['regular_sum_str'] ) ? '' : '👉 ' . $res['regular_sum_str'] );

		return $res;
	}

	public function getTextOverImage( ?\WC_Product $wc_product, $cat_id = null ) {
		if ( $promo_item = $this->checkDiscount4User( $wc_product, $cat_id ) ) {
			if ( $promo_item->text_over_image_scope === self::TEXT_OVER_IMAGE_SCOPE_ALL ) {
				$res = [
					$promo_item->promo_id . ChB_Settings()->lang,
					intval( $promo_item->percent ) . '% OFF',
					70
				];
				if ( $promo_item->until ) {
					$res[] = ChB_Lang::translateWithPars( ChB_Lang::LNG0053, $promo_item->until );
					$res[] = 20;
				}

				return $res;
			}
		}

		return null;
	}

	public static function applyPromo2Cart( &$cart_details ) {
		$total                 = $cart_details['total'];
		$quantity              = $cart_details['quantity'];
		$current_valid_percent = 0;
		$current_valid_limit   = 0;

		$next_valid_conditions = [];
		$bot_discounts         = ChB_Settings()->getParam( 'bot_discounts' );

		$is_on_type        = false;
		$discount_priority = ! empty( $bot_discounts['priority'] ) && is_array( $bot_discounts['priority'] ) ? $bot_discounts['priority'] : [
			'qty',
			'sum'
		];
		foreach ( $discount_priority as $type ) {
			if ( empty( $bot_discounts[ $type ]['is_on'] ) || empty( $bot_discounts[ $type ]['limits'] ) ) {
				continue;
			}

			$ts_now = time();
			if ( ! empty( $bot_discounts[ $type ]['from'] ) ) {
				$dt_from = \DateTime::createFromFormat( 'YmdHis', $bot_discounts[ $type ]['from'] . '000000', ChB_Settings()->timezone );
				if ( $dt_from && $ts_now < $dt_from->getTimestamp() ) {
					continue;
				}
			}

			if ( ! empty( $bot_discounts[ $type ]['until'] ) ) {
				$dt_until = \DateTime::createFromFormat( 'YmdHis', $bot_discounts[ $type ]['until'] . '235959', ChB_Settings()->timezone );
				if ( $dt_until && $ts_now > $dt_until->getTimestamp() ) {
					continue;
				}
			}

			$is_on_type = $type;
			break;
		}

		if ( ! $is_on_type ) {
			return;
		}

		$value2cmp = ( $is_on_type === 'sum' ? $total : $quantity );
		foreach ( $bot_discounts[ $is_on_type ]['limits'] as $limit => $percent ) {
			if ( $value2cmp >= $limit ) {
				$current_valid_percent = $percent;
				$current_valid_limit   = $limit;
			} else {
				$next_valid_conditions[] = [ $percent, $limit ];
			}
		}

		if ( $current_valid_percent == 0 && empty( $next_valid_conditions ) ) {
			return;
		}

		if ( ! empty( $bot_discounts[ $is_on_type ]['banner_url'] ) ) {
			$banner_url = $bot_discounts[ $is_on_type ]['banner_url'];
			if ( is_array( $banner_url ) ) {
				if ( ! empty( $banner_url[ ChB_Settings()->lang ] ) ) {
					$banner_url = $banner_url[ ChB_Settings()->lang ];
				} elseif ( ! empty( $banner_url['_def_'] ) ) {
					$banner_url = $banner_url['_def_'];
				} else {
					$banner_url = '';
				}
			}

			if ( $banner_url && is_string( $banner_url ) ) {
				$cart_details['list_discount']['banner_url'] = $banner_url;
			}
		}

		if ( empty( $cart_details['list_discount']['banner_url'] ) && ! empty( $next_valid_conditions ) ) {
			$cart_details['list_discount']['next_conditions'] = '🔥🔥🔥🔥🔥🔥🔥🔥';
			foreach ( $next_valid_conditions as $next_valid_condition ) {
				if ( $is_on_type === 'sum' ) {
					$line = ChB_Lang::translateWithPars( ChB_Lang::LNG0101,
						ChB_Common::printPriceNoCurrency( $next_valid_condition[1], false, '_' ),
						$next_valid_condition[0] . '%' );
				} elseif ( $is_on_type === 'qty' ) {
					$line = ChB_Lang::translateWithPars( ChB_Lang::LNG0157,
						ChB_Common::printNumberNoSpaces( $next_valid_condition[1] ),
						$next_valid_condition[0] . '%' );
				} else {
					$line = '';
				}

				$cart_details['list_discount']['next_conditions'] .= "\n" . $line;
			}
			$cart_details['list_discount']['next_conditions'] .= "\n" . '🔥🔥🔥🔥🔥🔥🔥🔥';
		}

		if ( $current_valid_percent != 0 ) {
			$cart_details['list_discount']['current_valid_percent'] = $current_valid_percent;
			$cart_details['list_discount']['current_valid_limit']   = $current_valid_limit;
			$coeff                                                  = ( 100 - $current_valid_percent ) * 0.01;
			$new_total                                              = ChB_Common::ceil( $total * $coeff );

			$cart_details['total'] = $new_total;
			if ( $is_on_type === 'sum' ) {
				$conditions = '🔥 -' . $current_valid_percent . ChB_Lang::translate( ChB_Lang::LNG0094 );
			} elseif ( $is_on_type === 'qty' ) {
				$conditions = '🔥 -' . $current_valid_percent . ChB_Lang::translate( ChB_Lang::LNG0095 );
			} else {
				$conditions = '';
			}

			$new_total_calc = 0;
			$ind            = 1;
			foreach ( $cart_details['products_details'] as &$product_details ) {
				if ( $ind ++ == count( $cart_details['products_details'] ) ) {
					$product_details['total'] = $new_total - $new_total_calc;
				} else {
					$product_details['total'] = ChB_Common::ceil( $product_details['total'] * $coeff );
					$new_total_calc           += $product_details['total'];
				}
			}

			if ( empty( $cart_details['promo_conditions'] ) ) {
				$cart_details['promo_conditions'] = $conditions;
			} else {
				$cart_details['promo_conditions'] .= "\n" . $conditions;
			}
		}
	}

	public static function setPromo4ProductByText( $text, $product_id ) {
		$cond = explode( ' ', $text );
		if ( sizeof( $cond ) != 2 ) {
			return false;
		}
		if ( strtolower( $cond[1] ) === 'off' ) {
			self::setPromo4Product( $product_id, 0 );
		} else if ( is_numeric( $cond[1] ) ) {
			self::setPromo4Product( $product_id, $cond[1] );
		}
	}

	public static function setPromo4Product( $product_id, $discount ) {
		$wc_product = wc_get_product( $product_id );
		$products   = [];
		if ( $wc_product->is_type( 'variable' ) ) {
			foreach ( $wc_product->get_children() as $id ) {
				$products[] = wc_get_product( $id );
			}
		} else {
			$products[] = $wc_product;
		}

		$coeff = 1;
		if ( $discount > 0 ) {
			$coeff = ( 100 - $discount ) * 0.01;
		}
		foreach ( $products as $product ) {
			if ( $discount > 0 ) {
				$product->set_sale_price( intval( round( $product->get_regular_price() * $coeff ) ) );
			} else {
				$product->set_sale_price( '' );
			}
			$product->save();
		}
	}

	public static function getStrokeThroughNumber( $num ) {
		//'0̶1̶2̶3̶4̶5̶6̶7̶8̶9̶'
		$digits = str_split( (string) $num );
		$res    = '';
		foreach ( $digits as $digit ) {
			if ( $digit === '0' ) {
				$res .= '0̶';
			} elseif ( $digit === '1' ) {
				$res .= '1̶';
			} elseif ( $digit === '2' ) {
				$res .= '̶2̶';
			} elseif ( $digit === '3' ) {
				$res .= '̶̶3̶';
			} elseif ( $digit === '4' ) {
				$res .= '̶̶4̶';
			} elseif ( $digit === '5' ) {
				$res .= '̶̶5̶';
			} elseif ( $digit === '6' ) {
				$res .= '̶6̶̶';
			} elseif ( $digit === '7' ) {
				$res .= '̶7̶';
			} elseif ( $digit === '8' ) {
				$res .= '̶̶8̶';
			} elseif ( $digit === '9' ) {
				$res .= '̶̶9̶';
			} elseif ( $digit === ' ' ) {
				$res .= '̶̶ ̶';
			} else {
				$res .= $digit;
			}
		}

		return $res;
	}

	/**
	 * @return array
	 */
	public function getDiscount4UserReminderData() {
		$this->initPromos();

		$res = [];
		foreach ( $this->_promo_items as $promo_item ) {
			if ( $promo_item->use_reminders ) {
				$res[] = [
					'promo_id'         => $promo_item->promo_id,
					'percent'          => $promo_item->percent,
					'deadline'         => ( $promo_item->dt_until ? $promo_item->dt_until->getTimestamp() : '' ),
					'filter_type'      => $promo_item->filter_type,
					'filter_value'     => implode( '.', $promo_item->filter_values ),
					'is_newly_created' => $promo_item->is_newly_created
				];
			}
		}

		return $res;
	}
}