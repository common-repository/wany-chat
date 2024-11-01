<?php

namespace ChatBot;


class ChB_FLOW_Promo {

	public static function run( ChatBot $ChB ) {
		if ( $ChB->task === 'manychat_promo_setPromo' ) {

			return self::setPromo( $ChB );

		}

		return [];
	}

	public static function setPromo( ChatBot $ChB ) {

		$product_ids = array_merge( ChB_Catalogue::getRecommendedProducts( $ChB->user ), $ChB->getParam( 'product_ids' ) );

		$args = [
			'cat_slug'    => $ChB->getParam( 'cat_slug' ),
			'brand'       => $ChB->getParam( 'brand' ),
			'size_slug'   => $ChB->getParam( 'size_slug' ),
			'pmin'        => $ChB->getParam( 'pmin' ),
			'pmax'        => $ChB->getParam( 'pmax' ),
			'view'        => $ChB->getParam( 'view' ),
			'search'      => $ChB->getParam( 'search' ),
			'wc_tags'     => $ChB->getParam( 'wc_tags' ),
			'product_ids' => $product_ids,
			'pa_filter'   => $ChB->getParam( 'pa_filter' ),
			'offset'      => $ChB->getParam( 'offset' )
		];

		list( $promo_cards ) = ChB_FLOW_Catalogue::getProductsGallery( $ChB, $args, $ChB->promo, null, null );

		if ( $ChB->viewHas( 'min' ) ) {
			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           => $promo_cards
			];
		} else {

//            $messages[] = ['type' => 'text',
//                            'text' => ChB_Lang::translateWithPars(ChB_Lang::LNG058, $ChB->firstname )];
//
//            $messages[] = ['type' => 'text',
//                            'text' => ChB_Lang::translate(ChB_Lang::LNG060)];

			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           => $promo_cards
			];

//			$messages[] = [
//				'type' => 'text',
//				'text' => ChB_Lang::translateWithPars( ChB_Lang::LNG059, $ChB->promo->discount4user_percent, $ChB->promo->discount4user_until )
//			];

//            $messages[] = ['type' => 'text',
//                'text' => ChB_Lang::translate(ChB_Lang::LNG061)];
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function getCouponUrl( $promo_id ) {
		$suff = [ ChB_Settings()->lang ];
		if ( ChB_Settings()->lang != 'en' ) {
			$suff[] = 'en';
		}

		$found    = false;
		$filename = null;
		foreach ( $suff as $suffix ) {
			$filename0 = 'promo_' . $promo_id . '_' . $suffix;
			$filename  = $filename0 . '.jpg';
			if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
				$found = true;
			} else {
				$filename = $filename0 . '.jpeg';
				if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
					$found = true;
				} else {
					$filename = $filename0 . '.png';
					if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
						$found = true;
					}
				}
			}
			if ( $found ) {
				break;
			}
		}

		if ( $found ) {
			return ChB_Settings()->uploads_url . $filename;
		} else {
			return null;
		}
	}

	public static function getInitPromoMessages( ChatBot $ChB ) {

		if ( ! ( $init_promo_item = $ChB->promo->getNewlyCreatedPromoItem() ) ) {
			return null;
		}

		$image_url = self::getCouponUrl( $init_promo_item->promo_id );
		if ( empty( $image_url ) ) {
			ChB_Common::my_log( 'getInitPromoMessages ' . $ChB->user->fb_user_id . ' no promo image png/jpg' . $init_promo_item->promo_id );

			return null;
		}

		if ( ! empty( ChB_Settings()->getParam( 'promo_headers' )[ $init_promo_item->promo_id ] ) ) {
			$text = ChB_Lang::translate( ChB_Settings()->getParam( 'promo_headers' )[ $init_promo_item->promo_id ] );
		}
		if ( ! empty( $text ) ) {
			$messages[] = [ 'type' => 'text', 'text' => $text ];
		}
		$messages[] = [ 'type' => 'image', 'url' => $image_url ];

		return $messages;
	}

	public static function sendPromoReminder( ChatBot $ChB, $args ) {

		ChB_Common::my_log( $args, true, 'sendPromoReminder' );

		if ( ! empty( $args['deadline'] ) ) {
			$diff = $args['deadline'] - time();
			if ( $diff <= 0 ) {
				return;
			}
			$days_left = intdiv( $diff, 86400 );
		} else {
			$days_left = false;
		}

		if ( ! ( $image_url = self::getCouponUrl( $args['promo_id'] ) ) ) {
			ChB_Common::my_log( 'sendPromoReminder ' . $ChB->user->fb_user_id . ' no promo image png/jpg' . $args['promo_id'] );

			return;
		}

		if ( $days_left !== false ) {
			if ( $days_left == 0 ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0109 );
			} elseif ( $days_left == 1 ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0108 );
			} else {
				$days_left_str = $days_left . ' ' . ChB_Lang::translateWithNumber( ChB_Lang::LNG0128, $days_left );
				$text          = ChB_Lang::translateWithPars( ChB_Lang::LNG0107, ChB_Common::printNumberNoSpaces( $days_left_str ) );
			}

			$text .= chr( 10 ) . ChB_Lang::translateWithPars( ChB_Lang::LNG0110, ChB_Common::printNumberNoSpaces( $args['percent'] ) );
		}
		$messages[] = [ 'type' => 'image', 'url' => $image_url ];

		if ( ! empty( $text ) ) {
			$messages[] = [ 'type' => 'text', 'text' => $text ];
		}

		if ( $args['filter_type'] == ChB_PromoItem::FILTER_CAT ) {
			$cat_id = explode( '.', $args['filter_value'] )[0];
			$ChB->setParam( 'cat_slug', get_term( $cat_id )->slug );
			$response = ChB_FLOW_Catalogue::getProducts( $ChB, $messages );
		} elseif ( $args['filter_type'] == ChB_PromoItem::FILTER_TAG ) {
			$wc_tag_ids = explode( '.', $args['filter_value'] );
			$wc_tags    = [];
			foreach ( $wc_tag_ids as $wc_tag_id ) {
				$wc_tag = get_term( $wc_tag_id );
				if ( ! empty( $wc_tag ) && ! is_wp_error( $wc_tag ) ) {
					$wc_tags[] = $wc_tag->slug;
				}
			}
			$ChB->setParam( 'wc_tags', $wc_tags );
			$response = ChB_FLOW_Catalogue::getProducts( $ChB, $messages );
		} elseif ( ChB_Settings()->getParam( 'multi_brand' ) ) {
			$response = ChB_FLOW_Catalogue::getProductBrands( $ChB, $messages );
		} else {
			$response = ChB_FLOW_Catalogue::getProductCats( $ChB, $messages );
		}

		$fields = [ 'data' => $response ];
		ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );
	}

}