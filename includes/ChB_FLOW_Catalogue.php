<?php

namespace ChatBot;

class ChB_FLOW_Catalogue {
	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_cat_getCatalog' ) {

			return self::getCatalog( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_getProductBrands' ) {

			return self::getProductBrands( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_getProductCats' ) {

			return self::getProductCats( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_getProducts' ) {

			return self::getProducts( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_openProduct' ) {

			return self::openProduct( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_checkSizesDetails' ) {

			return self::checkSizesDetails( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_talkToHuman' ) {

			return self::talkToHuman( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_getProducts4AdsJSON' ) {

			return self::getProducts4AdsJSON( $ChB );

		} elseif ( $ChB->task === 'manychat_cat_ping' ) {

			return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => 'ping' ] ] ] ];

		}

		return [];
	}

	public static function getCatalog( ChatBot $ChB ) {
		$ChB->setParam( 'parent', ChB_Settings()->getParam( 'parent_cat' ) );

		return self::getProductCats( $ChB );
	}

	public static function getCatalogButton( ChatBot $ChB, $caption ) {
		return [
			'type'    => 'flow',
			'caption' => ChB_Lang::translate( $caption ),
			'target'  => ChB_ManyChat::getMCFlowNS( $ChB->user->channel, 'flow_catalog' )
		];
	}

	public static function getProductBrands( ChatBot $ChB, $custom_messages = null ) {

		$cards  = [];
		$brands = ChB_Catalogue::getProductBrands();
		foreach ( $brands as $brand ) {
			$cards[] = self::getBrandCatsCard( $ChB, $brand->slug, $brand->description );
		}

		if ( empty( $custom_messages ) ) {
			$messages = [];
		} else {
			$messages = $custom_messages;
		}

		$messages[] = [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0113 ) ];

		$messages[] = [
			'type'               => 'cards',
			'elements'           => $cards,
			'image_aspect_ratio' => 'square'
		];

		$messages[] = [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0071 ) ];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function getProductCats( ChatBot $ChB, $custom_messages = null ) {
		$args = [
			'parent'    => $ChB->getParam( 'parent' ),
			'cat_slugs' => $ChB->getParam( 'cat_slugs' ),
			'brand'     => $ChB->getParam( 'brand' ),
			'ca_filter' => $ChB->getParam( 'ca_filter' ),
			'offset'    => $ChB->getParam( 'offset' )
		];

		$cards        = [];
		$page_of_cats = ChB_Catalogue::getProductCats( $args, 10, ( ChB_Settings()->getParam( 'multi_brand' ) ? 1 : 0 ), $ChB->promo );

		if ( ! ChB_Settings()->getParam( 'multi_brand' ) ) {
			$all_brands_ind  = - 1;
			$all_brands_card = null;
		} else {
			if ( sizeof( $page_of_cats['cats'] ) <= 3 ) {
				$all_brands_ind = sizeof( $page_of_cats['cats'] ) + 1;
			} else {
				$all_brands_ind = round( sizeof( $page_of_cats['cats'] ) / 2 );
			}
			$all_brands_card = self::getAllBrandsCard( $ChB );
		}

		$ind = 0;
		foreach ( $page_of_cats['cats'] as $cat ) {
			if ( $ind ++ == $all_brands_ind ) {
				$cards[] = $all_brands_card;
			}
			$cat_num_of_products = $page_of_cats['cats_num_of_products'][ $cat->term_id ];
			$cards[]             = self::getProductCatCard( $ChB, $cat, $ChB->getParam( 'brand' ), $ChB->getParam( 'ca_filter' ), $cat_num_of_products, in_array( $cat->term_id, $page_of_cats['cats_with_subcats'] ) );
		}

		if ( $page_of_cats['next_page_exists'] > 0 ) {
			list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
				'task'      => 'manychat_cat_getProductCats',
				'parent'    => $ChB->getParam( 'parent' ),
				'brand'     => $ChB->getParam( 'brand' ),
				'ca_filter' => $ChB->getParam( 'ca_filter' ),
				'cat_slugs' => $ChB->getParam( 'cat_slugs' ),
				'offset'    => $page_of_cats['new_offset']
			], false, true );

			$buttons = [
				[
					'type'    => 'dynamic_block_callback',
					'method'  => 'post',
					'payload' => $payload,
					'url'     => $url,
					'caption' => ChB_Lang::translate( ChB_Lang::LNG0075 )
				]
			];

			$card = [
				'image_url'  => ChB_Catalogue::getNextPageImageUrl( ChB_Settings()->getParam( 'cats_aspect_ratio' ) ),
				'action_url' => $action_url,
				'buttons'    => $buttons
			];

			if ( $page_of_cats['cats_left'] > 0 ) {
				$card['title'] = ChB_Lang::translateWithPars( ChB_Lang::LNG0081, ChB_Common::printNumberNoSpaces( $page_of_cats['cats_left'] ) );
			} else {
				$card['title'] = ChB_Lang::translate( ChB_Lang::LNG0072 );
			}

			$cards[] = $card;
		}

		if ( empty( $custom_messages ) ) {
			$messages = [];
		} else {
			$messages = $custom_messages;
		}

		$messages[] = [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0073 ) ];

		$messages[] = [
			'type'               => 'cards',
			'elements'           => $cards,
			'image_aspect_ratio' => ( ChB_Settings()->getParam( 'cats_aspect_ratio' ) === 'square' ? 'square' : 'horizontal' )
		];

		$messages[] = [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0071 ) ];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function getProductCatCard( ChatBot $ChB, $cat, $brand, $ca_filter, $cat_num_of_products, $cat_has_subcats ) {
		if ( $cat_has_subcats ) {
			list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
				'task'      => 'manychat_cat_getProductCats',
				'parent'    => $cat->slug,
				'brand'     => $brand,
				'ca_filter' => $ca_filter
			], false, true );
		} else {
			list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
				'task'     => 'manychat_cat_getProducts',
				'cat_slug' => $cat->slug,
				'brand'    => $brand,
				'view'     => [ ChB_Common::VIEW_SIZES ]
			], false, true );
		}

		$buttons = [
			[
				'type'    => 'dynamic_block_callback',
				'method'  => 'post',
				'url'     => $url,
				'payload' => $payload,
				'caption' => ChB_Lang::translate( ChB_Lang::LNG0046 )
			]
		];

		ChB_FLOW_Manager::makeMarkButton4Category( $ChB, $buttons, $cat->slug, $brand );

		$element = [
			'title'      => $cat->name . ( $brand ? ' ' . strtoupper( $brand ) : '' ),
			'subtitle'   => ChB_Lang::translateWithPars( ChB_Lang::LNG0082, ChB_Common::printNumberNoSpaces( $cat_num_of_products ) ),
			'image_url'  => $cat->image_url,
			'action_url' => $action_url,
			'buttons'    => $buttons
		];

		if ( $promo_item = $ChB->promo->checkDiscount4User( null, $cat->term_id ) ) {
			if ( $promo_condition = $promo_item->printPromo4UserConditions() ) {
				$element['subtitle'] .= chr( 10 ) . $promo_condition;
			}
		}

		return $element;
	}

	public static function getAllBrandsCard( ChatBot $ChB ) {
		$element = [
			'title'     => ChB_Lang::translate( ChB_Lang::LNG0048 ),
			'subtitle'  => ChB_Lang::translate( ChB_Lang::LNG0050 ) . ' ' . ChB_Lang::translate( ChB_Settings()->getParam( 'brands_names_list' ) ) . '  ↓↓↓↓↓↓',
			'image_url' => ChB_Catalogue::getAllBrandsImageUrl(),
			'buttons'   => [ ChB_FLOW_Catalogue::getCatalogButton( $ChB, ChB_Lang::LNG0048 ) ]
		];

		return $element;
	}

	public static function getBrandCatsCard( ChatBot $ChB, $brand, $brand_desc = null ) {
		$brand_upper = strtoupper( $brand );
		list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
			'task'  => 'manychat_cat_getProductCats',
			'brand' => $brand
		], false, true );
		$buttons = [
			[
				'type'    => 'dynamic_block_callback',
				'method'  => 'post',
				'url'     => $url,
				'payload' => $payload,
				'caption' => ChB_Lang::translate( ChB_Lang::LNG0047 ) . ' ' . $brand_upper
			]
		];

		if ( empty( $brand_desc ) ) {
			$subtitle = ChB_Lang::translate( ChB_Lang::LNG0050 ) . ' ' . $brand_upper . ' ↓↓↓↓↓↓';
		} else {
			$subtitle = ChB_Lang::translate( $brand_desc );
		}

		$element = [
			'title'      => $brand_upper,
			'subtitle'   => $subtitle,
			'action_url' => $action_url,
			'image_url'  => ChB_Catalogue::getBrandImageUrl( $brand ),
			'buttons'    => $buttons
		];

		if ( $promo_item = $ChB->promo->checkDiscount4User( null ) ) {
			if ( $promo_condition = $promo_item->printPromo4UserConditions() ) {
				$element['subtitle'] .= chr( 10 ) . $promo_condition;
			}
		}

		return $element;
	}

	public static function getSizeRanges( ChatBot $ChB ) {
		$size_ranges = ChB_Catalogue::getSizeRanges4Cat( $ChB->getParam( 'cat_slug' ), $ChB->getParam( 'brand' ) );
		if ( $size_ranges ) {
			$buttons = [];
			foreach ( $size_ranges as $size_range_name => $size_slugs ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0176 ) . $size_range_name, [
					'task'      => 'manychat_cat_getProducts',
					'cat_slug'  => $ChB->getParam( 'cat_slug' ),
					'brand'     => $ChB->getParam( 'brand' ),
					'size_slug' => $size_slugs
				] );
			}
		}
		if ( empty( $buttons ) ) {
			return false;
		}

		$messages = ChB_Common::makeManyButtons( $buttons, ChB_Lang::translate( ChB_Lang::LNG0138 ) );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function getProducts( ChatBot $ChB, $custom_messages = null ) {

		if ( $ChB->viewHas( ChB_Common::VIEW_SIZES ) && $ChB->getParam( 'cat_slug' ) ) {
			$size_ranges_res = self::getSizeRanges( $ChB );
			if ( ! empty( $size_ranges_res ) ) {
				return $size_ranges_res;
			}
		}

		if ( empty( $custom_messages ) ) {
			$custom_messages = [];
		}

		if ( ! $ChB->viewHas( ChB_Common::VIEW_PRODUCT_CARD2WEB ) ) {
			$custom_messages[] = [ 'type' => 'text', 'text' => ChB_Lang::translate( ChB_Lang::LNG0074 ) ];
		}

		if ( $ChB->viewHas( ChB_Common::VIEW_EXT ) && $ChB->getParam( 'cat_slug' ) ) {
			$ids = ChB_Catalogue::getProductIdsByParameters( $ChB->getParam( 'cat_slug' ), null, null, null, 'instock', true );
			$ChB->setParam( 'product_ids', ChB_Common::getRandomSubarray( $ids, 10, false ) );
			$ChB->setParam( 'cat_slug', null );
		}

		$args = [
			'cat_slug'    => $ChB->getParam( 'cat_slug' ),
			'brand'       => $ChB->getParam( 'brand' ),
			'size_slug'   => $ChB->getParam( 'size_slug' ),
			'pmin'        => $ChB->getParam( 'pmin' ),
			'pmax'        => $ChB->getParam( 'pmax' ),
			'view'        => $ChB->getParam( 'view' ),
			'search'      => $ChB->getParam( 'search' ),
			'wc_tags'     => $ChB->getParam( 'wc_tags' ),
			'product_ids' => $ChB->getParam( 'product_ids' ),
			'pa_filter'   => $ChB->getParam( 'pa_filter' ),
			'offset'      => $ChB->getParam( 'offset' )
		];

		list( $messages ) = self::getProductsGallery( $ChB, $args, $ChB->promo, $ChB->getParam( 'text_over_image' ), $custom_messages );

		if ( $ChB->viewHas( 'info' ) ) {
			$messages = ChB_Common::cardsAsText( $messages );
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function getProductsGallery( ChatBot $ChB, $args, ChB_Promo $promo, $text_over_image, $custom_messages ) {
		$messages = empty( $custom_messages ) ? [] : ( is_string( $custom_messages ) ? [
			[
				'type' => 'text',
				'text' => $custom_messages
			]
		] : $custom_messages );

		$is_ext_request  = ChatBot::argsViewHas( $args, ChB_Common::VIEW_EXT );
		$show_brand_card = ( ! empty( $args['brand'] ) && ! empty( ChB_Settings()->getParam( 'view_settings' )['show_brand_card'] ) );
		$aspect_ratio    = ( empty( $args['aspect_ratio'] ) ? 'square' : $args['aspect_ratio'] );

		if ( $use_open_web_button = ChatBot::argsViewHas( $args, ChB_Common::VIEW_PRODUCT_CARD2WEB ) ) {
			$use_open_button = false;
			$use_buy_button  = false;
		} else {
			if ( ! empty( ChB_Settings()->getParam( 'product_view_settings' )['list']['buy'] ) ) {
				$use_open_button = false;
				$use_buy_button  = true;
			} elseif ( ! empty( ChB_Settings()->getParam( 'product_view_settings' )['list']['buy_open'] ) ) {
				$use_open_button = true;
				$use_buy_button  = true;
			} else {
				$use_open_button = true;
				$use_buy_button  = false;
			}
		}

		$view_try_on = ChatBot::argsViewHas( $args, ChB_Common::VIEW_TRY_ON );
		if ( $view_try_on ) {
			$args['user'] = $ChB->user;
		}

		$decrease_num_of_cards_by = ( $show_brand_card ? 1 : 0 );//уменьшаем на одну карточку, т.е. карточку брэнд в галерее товаров
		$page_of_products         = ChB_Catalogue::getProducts( $args, 0, $decrease_num_of_cards_by, $promo, $text_over_image );

		$products   = $page_of_products['products'];
		$brand_card = null;
		if ( $show_brand_card ) {
			if ( sizeof( $products ) <= 3 ) {
				$brand_ind = sizeof( $products ) + 1;
			} else {
				$brand_ind = round( sizeof( $products ) / 2 );
			}
			$brand_card = self::getBrandCatsCard( $ChB, $args['brand'] );
		} else {
			$brand_ind = - 1;
		}

		$event_tags4open      = ChB_Analytics::getParentEventsTags( $ChB->events, ( $use_buy_button ? ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT : ChB_Analytics::EVENT_OPEN_PRODUCT ) );
		$event_tags4next_page = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_LIST_NEXT_PAGE );
		$events4next_page     = ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_LIST_NEXT_PAGE ], $event_tags4next_page );

		$event_tags4try_on = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_TRY_ON );
		$event_tags4try_on = ChB_Analytics::addTagToTags( ChB_Analytics::TAG_TRY_ON, $event_tags4try_on );

		$cards = [];
		$ind   = 0;
		foreach ( $products as $product ) {
			if ( $ind ++ == $brand_ind ) {
				$cards[] = $brand_card;
			}

			if ( ! $is_ext_request ) {
				$buttons = [];

				if ( $use_open_web_button ) {

					list( $button, $action_url ) = self::makeOpenProductOnWebButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0175 ), $product['id'], true );
					$buttons[] = $button;

				} else {
					if ( $use_open_button ) {
						list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
							'task'       => 'manychat_cat_openProduct',
							'product_id' => $product['id'],
							'evt'        => ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_OPEN_PRODUCT ], $event_tags4open, [ 'pr_id' => $product['id'] ] )
						], false, true );

						$buttons[] = [
							'type'    => 'dynamic_block_callback',
							'url'     => $url,
							'payload' => $payload,
							'caption' => ChB_Lang::translate( ChB_Lang::LNG0046 ),
							'method'  => 'post',
						];
					}

					if ( $use_buy_button ) {
						list( $button, $action_url ) = self::makeBuyButton(
							$ChB,
							ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_BUY_FROM_LIST_PRODUCT ], $event_tags4open, [ 'pr_id' => $product['id'] ] ),
							ChB_Lang::translate( ChB_Lang::LNG0042 ),
							$product['id'],
							null,
							true );
						$buttons[] = $button;
					}
				}

				if ( $product['has_try_on'] ) {
					$events4try_on = ChB_Analytics::packEvents4Url( [ $view_try_on ? ChB_Analytics::EVENT_TRY_ON_AGAIN : ChB_Analytics::EVENT_TRY_ON ], $event_tags4try_on, [ 'pr_id' => $product['id'] ] );
					if ( $view_try_on ) {
						$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0144 ),
							[
								'task'       => 'manychat_tryon_showTryOn',
								'product_id' => $product['id'],
								'cat_slug'   => $ChB->getParam( 'cat_slug' ),
								'view'       => ChB_Common::VIEW_TRY_ON_AGAIN,
								'evt'        => $events4try_on
							] );
					} else {
						$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0145 ),
							[
								'task'       => 'manychat_tryon_showTryOn',
								'product_id' => $product['id'],
								'cat_slug'   => $ChB->getParam( 'cat_slug' ),
								'evt'        => $events4try_on
							] );
					}
				}
				ChB_FLOW_Manager::makeMarkButton4Product( $ChB, $buttons, $product['id'] );
			} else {
				list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
					'task'       => 'manychat_cat_openProduct',
					'product_id' => $product['id']
				], true, true );
				$action_url = null;
				$buttons    = [
					[
						'type'    => 'url',
						'caption' => ChB_Lang::translate( ChB_Lang::LNG0046 ),
						'url'     => $action_url
					]
				];
			}

			$element = [
				'title'      => $product['name'],
				'image_url'  => $product['image_url'],
				'action_url' => $action_url,
				'buttons'    => $buttons
			];

			$subtitle = [];
			if ( ! empty( $product['price_details']['sale_sum_str'] ) ) {
				$subtitle[] = $product['price_details']['sale_sum_str'];
			}
			if ( ! empty( $product['price_details']['promo_conditions'] ) ) {
				$subtitle[] = $product['price_details']['promo_conditions'];
			}
			if ( ! empty( $product['price_details']['regular_sum_str'] ) ) {
				$subtitle[] = $product['price_details']['regular_sum_str'];
			}
			if ( ! empty( $product['availability'] ) ) {
				$subtitle[] = self::printProductAvailability( $product, null );
			}
			if ( ! empty( $product['attrs_txt'] ) ) {
				$subtitle[] = $product['attrs_txt'];
			}
			if ( ! empty( $product['sdesc'] ) ) {
				$subtitle[] = $product['sdesc'];
			}

			$element['subtitle'] = implode( "\n", $subtitle );

			$cards[] = $element;
		}
		if ( $ind <= $brand_ind ) {
			$cards[] = $brand_card;
		}

		if ( $page_of_products['next_page_exists'] > 0 ) {
			list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB,
				[
					'task'        => 'manychat_cat_getProducts',
					'cat_slug'    => empty( $args['cat_slug'] ) ? '' : $args['cat_slug'],
					'brand'       => empty( $args['brand'] ) ? '' : $args['brand'],
					'size_slug'   => empty( $args['size_slug'] ) ? '' : $args['size_slug'],
					'pmin'        => empty( $args['pmin'] ) ? '' : $args['pmin'],
					'pmax'        => empty( $args['pmax'] ) ? '' : $args['pmax'],
					'view'        => empty( $args['view'] ) ? null : $args['view'],
					'search'      => empty( $args['search'] ) ? '' : $args['search'],
					'pa_filter'   => empty( $args['pa_filter'] ) ? null : $args['pa_filter'],
					'wc_tags'     => empty( $args['wc_tags'] ) ? null : $args['wc_tags'],
					'product_ids' => empty( $args['product_ids'] ) ? null : $args['product_ids'],
					'evt'         => $events4next_page,
					'offset'      => $page_of_products['new_offset']
				],
				false, true );

			$buttons = [
				[
					'type'    => 'dynamic_block_callback',
					'method'  => 'post',
					'url'     => $url,
					'payload' => $payload,
					'caption' => ChB_Lang::translate( ChB_Lang::LNG0075 )
				]
			];

			$card = [
				'image_url'  => ChB_Catalogue::getNextPageImageUrl( $aspect_ratio ),
				'action_url' => $action_url,
				'buttons'    => $buttons
			];

			if ( $page_of_products['products_left'] > 0 ) {
				$card['title'] = ChB_Lang::translateWithPars( ChB_Lang::LNG0076, ChB_Common::printNumberNoSpaces( $page_of_products['products_left'] ) );
			} else {
				$card['title'] = ChB_Lang::translate( ChB_Lang::LNG0072 );
			}

			$cards[] = $card;
		}

		$messages[] = [
			'type'               => 'cards',
			'image_aspect_ratio' => $aspect_ratio,
			'elements'           => $cards
		];

		if ( count( $cards ) > 1 ) {
			$messages[] = [
				'type' => 'text',
				'text' => ChB_Lang::translate( ChB_Lang::LNG0071 )
			];
		}

		return [ $messages, count( $cards ) ];
	}

	public static function getProducts4AdsJSON( ChatBot $ChB ) {

		$messages[] = [ 'message' => [ 'text' => ChB_Lang::translate( ChB_Lang::LNG0074 ) ] ];

		$args = [
			'cat_slug'    => $ChB->getParam( 'cat_slug' ),
			'brand'       => $ChB->getParam( 'brand' ),
			'size_slug'   => $ChB->getParam( 'size_slug' ),
			'pmin'        => $ChB->getParam( 'pmin' ),
			'pmax'        => $ChB->getParam( 'pmax' ),
			'view'        => $ChB->getParam( 'view' ),
			'search'      => $ChB->getParam( 'search' ),
			'wc_tags'     => $ChB->getParam( 'wc_tags' ),
			'product_ids' => $ChB->getParam( 'product_ids' ),
			'pa_filter'   => $ChB->getParam( 'pa_filter' ),
			'offset'      => $ChB->getParam( 'offset' )
		];

		$page_of_products = ChB_Catalogue::getProducts( $args, 0, 0, $ChB->promo, $ChB->getParam( 'text_over_image' ) );
		$products         = $page_of_products['products'];

		$cards = [];
		foreach ( $products as $product ) {
			//MANYCHATJSON(9724449)::ACT::e341c02b33e41893123e741d1c47c9a2
			list( $url, $payload, $action_url ) = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
				'task'       => 'manychat_cat_openProduct',
				'product_id' => $product['id']
			], true, true );

			$element = [
				'title'    => $product['name'],
				'subtitle' => $product['price'] . ( ! empty( $product['sdesc'] ) ? chr( 10 ) . $product['sdesc'] : '' ),
				'buttons'  => [
					[
						'type'    => 'postback',
						'payload' => get_post_meta( $product['id'], 'action4json', true ),
						'title'   => ChB_Lang::translate( ChB_Lang::LNG0046 )
					]
				],

				'image_url'      => $product['image_url'],
				'default_action' => [
					'type' => 'web_url',
					'url'  => $action_url
				]
			];

			$cards[] = $element;
		}

		$messages[] = [
			'message'          => [
				'attachment' => [
					'type'    => 'template',
					'payload' => [
						'template_type'      => 'generic',
						'elements'           => $cards,
						'image_aspect_ratio' => 'square'
					]
				]
			],
			'receiving_app_id' => ChB_Settings()->getParam( 'manychat_receiving_app_id' )
		];

		$messages[] = [
			'message' => [
				'text' => ChB_Lang::translate( ChB_Lang::LNG0071 )
			]
		];

		$messages[] = [
			'message' => [
				'text' => ChB_Lang::translate( ChB_Lang::LNG0079 )
			]
		];

		return $messages;
	}

	public static function talkToHuman( ChatBot $ChB ) {

		if ( $ChB->getParam( 'product_id' ) ) {
			$ChB->setParam( 'product_ids', [ $ChB->getParam( 'product_id' ) ] );
		}

		$messages[] = [
			'type' => 'text',
			'text' => ChB_Lang::translate( ChB_Lang::LNG0043 ) . "\n" . ChB_Lang::translate( ChB_Lang::LNG0044 )
		];

		if ( ! $ChB->viewHas( ChB_Common::VIEW_TTH_ON_PAYMENT ) ) {
			if ( $ChB->viewHas( ChB_Common::VIEW_SITE_PROD_BTN ) ) {
				$messages = array_merge( self::getProductExplainMessages( $ChB, true ), $messages );
			} else {
				$messages = array_merge( $messages, self::getProductExplainMessages( $ChB ) );
			}
		}

		ChB_Events::scheduleSingleEventOnShutdown( $ChB, ChB_Events::CHB_EVENT_SEND_MANAGER_TALK_TO_HUMAN, [ 'ChB_Customer' => $ChB ] );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function getProductExplainMessages( ChatBot $ChB, $show_product_image = false ) {
		$product_id = $ChB->getParam( 'product_id' );
		$product    = ChB_Catalogue::openProduct( $product_id, $ChB->user, $ChB->promo );
		$add_info   = apply_filters( 'wany_hook_explain_messages_add_info', '' );

		$text = "ℹ " . ChB_Lang::translate( ChB_Lang::LNG0131 ) .
		        "\n*" . $product['name'] . "*" .
		        ( ! empty ( $product['availability'] ) ? "\n\n" . self::printProductAvailability( $product, "👉", true ) . "\n👉 " . ChB_Lang::translate( ChB_Lang::LNG0132 ) : '' ) .
		        "\n👉 " . ChB_Lang::translate( ChB_Lang::LNG0133 ) . " " .
		        ( strlen( $product['price_details']['sum_str'] ) > 15 ? "\n👉 " : "" ) . $product['price_details']['sum_str'] .
		        ( ChB_Settings()->getParam( 'use_cod' ) ? "\n👉 " . ChB_Lang::translate( ChB_Lang::LNG0136 ) : '' ) .
		        ( ! empty( $add_info ) ? "\n👉 " . $add_info : '' ) .
		        ( ChB_Settings()->getParam( 'shipping_cost_code' ) === ChB_Common::SHIPPING_FREE ? '. ' . ChB_Lang::translate( ChB_Lang::LNG0135 ) : '' ) .
		        "\n👉 " . ChB_Lang::translate( ChB_Lang::LNG0137 );

		$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION );
		$events4buy = ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_BUY_FROM_PRODUCT_EXPLANATION ], $event_tags );

		$buttons = [ self::makeBuyButton( $ChB, $events4buy, ChB_Lang::translate( ChB_Lang::LNG0042 ), $product['id'], null ) ];

		if ( $show_product_image ) {
			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           =>
					[
						[
							'title'     => $product['name'],
							'subtitle'  => self::printProductAvailability( $product ),
							'image_url' => $product['image_url']
						]
					]
			];
		}

		$messages[] = [
			'type'    => 'text',
			'text'    => $text,
			'buttons' => $buttons
		];

		return $messages;
	}

	public static function makeTalkToHumanButton( ChatBot $ChB, $product_id = null, $events = null ) {

		$pars = [
			'task' => 'manychat_cat_talkToHuman',
		];

		if ( $product_id ) {
			$pars['product_id'] = $product_id;
		}

		if ( $events ) {
			$pars['evt'] = $events;
		}

		$button = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0063 ), $pars );

		return apply_filters( 'wany_hook_talk_to_human_button', $button, $ChB, $product_id, $events );
	}

	public static function makeOpenProductOnWebButton( ChatBot $ChB, $caption, $product_id, $output_action_url = false ) {

		$action_url = add_query_arg( ChB_WYSession::GET_PAR_CONNECT_TO_BOT_USER, ChB_WYSession::encodeBotUserForGetPar( $ChB->user->wp_user_id ), ChB_Catalogue::getProductPermalink( $product_id ) );
		$button     = [
			'type'    => 'url',
			'url'     => $action_url,
			'caption' => $caption
		];

		return $output_action_url ? [ $button, $action_url ] : $button;
	}

	public static function makeBuyButton( ChatBot $ChB, $events, $caption, $product_id, $variation_id, $output_action_url = false ) {

		if ( ChB_Settings()->isWebRedirectOnBUY() ) {

			return self::makeOpenProductOnWebButton( $ChB, $caption, $product_id, $output_action_url );

		} else {
			$pars = ChatBot::makeDynamicBlockCallbackPars( $ChB, [
				'task'       => 'manychat_order_chooseVariation',
				'evt'        => $events,
				'product_id' => $product_id,
				'var_id'     => $variation_id,
				'pa_filter'  => $ChB->getParam( 'pa_filter' )
			], false, $output_action_url );

			if ( $output_action_url ) {
				list( $button_url, $payload, $action_url ) = $pars;
			} else {
				$action_url = null;
				list( $button_url, $payload ) = $pars;
			}

			$button = [
				'type'    => 'dynamic_block_callback',
				'method'  => 'post',
				'url'     => $button_url,
				'payload' => $payload,
				'caption' => $caption
			];
		}

		return $output_action_url ? [ $button, $action_url ] : $button;
	}

	/**
	 * @param $product array
	 *
	 * @param string $bullet
	 *
	 * @param bool $ucfirst
	 *
	 * @return string
	 */
	public static function printProductAvailability( $product, $bullet = 'ℹ', $ucfirst = false ) {
		if ( empty( $product['availability'] ) ) {
			return '';
		}
		$res = '';
		foreach ( $product['availability'] as $availability_line ) {
			$res .= ( $res ? "\n" : ' ' ) . ChB_Lang::maybeForceRTL( ( $bullet ? $bullet . ' ' : '' ) . ( $ucfirst ? ucfirst( $availability_line ) : $availability_line ) );
		}

		return $ucfirst ? ucfirst( $res ) : $res;
	}

	public static function openProduct( ChatBot $ChB ) {

		$product_id = $ChB->getParam( 'product_id' );

		if ( $ChB->viewHas( ChB_Common::VIEW_PRODUCT_CARD2WEB ) ) {
			$ChB->setParam( 'product_ids', [ $product_id ] );

			return self::getProducts( $ChB );
		}

		$product = ChB_Catalogue::openProduct( $product_id, $ChB->user, $ChB->promo );
		ChB_Settings()->tic( 'openProductFlow' );

		if ( ! $product ) {
			return ChB_FLOW_Common::getReplyForEmpty( $ChB );
		}

		$messages[] = [
			'type'               => 'cards',
			'image_aspect_ratio' => 'square',
			'elements'           =>
				[
					[
						'title'     => $product['name'],
						'subtitle'  => self::printProductAvailability( $product, null ),
						'image_url' => $product['image_url']
					]
				]
		];

		foreach ( $product['gallery_image_urls'] as $gallery_image_url ) {
			$messages[] = [
				'type'               => 'cards',
				'image_aspect_ratio' => 'square',
				'elements'           =>
					[
						[
							'title'     => $product['name'],
							'subtitle'  => self::printProductAvailability( $product, null ),
							'image_url' => $gallery_image_url
						]
					]
			];
		}

		$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_BUY_PRODUCTS );
		$events4buy = ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_BUY_PRODUCTS ], $event_tags, [ 'pr_id' => $product_id ] );
		$events4tth = ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_TTH ], $event_tags, [ 'pr_id' => $product_id ] );

		$video_card = self::makeCard4ProductVideo( $product['SKU'], $product['id'], $ChB->user->fb_user_id, $event_tags );
		if ( ! empty( $video_card ) ) {
			$messages[] = $video_card;
		}

		if ( ! empty( $product['attn'] ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => '⚠ ' . $product['attn']
			];
		}

		if ( ! empty( $product['sdesc'] ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => $product['sdesc']
			];
		}

		if ( ! empty( $product['attrs_txt'] ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => $product['attrs_txt']
			];
		}

		if ( ChB_Settings()->getParam( 'use_livechat' ) ) {
			$buttons    = [
				ChB_FLOW_Catalogue::makeTalkToHumanButton( $ChB, $product_id, $events4tth )
			];
			$messages[] = [
				'type'    => 'text',
				'text'    => ChB_Lang::translate( ChB_Lang::LNG0062 ),
				'buttons' => $buttons
			];
		}

		$text = '';
		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user->fb_user_id ) ) {
			$text = ChB_Lang::maybeForceRTL( 'SKU: ' . $product['SKU'] );
		}

		if ( ! empty( $product['availability'] ) ) {
			$text = "\n" . self::printProductAvailability( $product );
		}

		if ( $text ) {
			$messages[] = [
				'type' => 'text',
				'text' => $text
			];
		}

		if ( empty( $product['pa_on_buy_button'] ) || ChB_Settings()->isWebRedirectOnBUY() ) {

			$button_caption = ChB_Settings()->isWebRedirectOnBUY() ? ChB_Lang::translate( ChB_Lang::LNG0177 ) : ChB_Lang::translate( ChB_Lang::LNG0042 );
			$buttons        = [ self::makeBuyButton( $ChB, $events4buy, $button_caption, $product_id, null ) ];

			$price_message_text = $product['price_details']['all_str'];
			if ( ChB_Settings()->getParam( 'use_cod' ) && ! empty( ChB_Settings()->getParam( 'product_view_settings' )['element']['show_cod'] ) ) {
				$price_message_text .= "\n" . '[' . ChB_Lang::translate( ChB_Lang::LNG0064 ) . ']';
			}

			$messages[] = [
				'type'    => 'text',
				'text'    => $price_message_text,
				'buttons' => &$buttons
			];
		} else {
			$messages = array_merge( $messages, ChB_FLOW_NewOrder::chooseVariation( $ChB, $product['pa_on_buy_button'], $events4buy ) );
			$buttons  = &$messages[ count( $messages ) - 1 ]['buttons'];
		}

		ChB_FLOW_Manager::makeMarkButton4Product( $ChB, $buttons, $ChB->getParam( 'product_id' ) );
		unset( $buttons );

		ChB_Pixel::schedulePixelEvent( $ChB, ChB_Pixel::EVENT_VIEW_CONTENT, $product_id, $product['price_details']['avg_sum'], null );
		$ChB->user->addProduct2LastProductsOpenedByUser( $product_id );

		ChB_Settings()->toc( 'openProductFlow' );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function checkSizesDetails( ChatBot $ChB ) {

		$size2find           = $ChB->getParam( 'val' );
		$last_opened_product = ChB_Analytics::getLastUnansweredProduct( $ChB->user->fb_user_id );
		if ( empty( $last_opened_product['product_id'] ) ) {
			return [];
		}

		$last_opened_product_id = $last_opened_product['product_id'];
		ChB_Analytics::registerEvent( ChB_Analytics::EVENT_OPEN_DF_SIZE, [
			'pr_id' => $last_opened_product_id,
			'tags'  => $last_opened_product['event_tags']
		], $ChB->user->fb_user_id );
		$events = ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_BUY_FROM_DF_SIZE ], $last_opened_product['event_tags'], [ 'pr_id' => $last_opened_product_id ] );

		$product = ChB_Catalogue::openProduct( $last_opened_product_id, $ChB->user, $ChB->promo, [ 'size2find' => $size2find ] );
		if ( ! $product ) {
			return [];
		}

		$text               = '';
		$price_text         = $product['price_details']['all_str'] . "\n";
		$messages           = [];
		$text_on_buy_button = ChB_Lang::translate( ChB_Lang::LNG0042 );
		$size_found_instock = false;
		if ( $product['size2find']['only_one_size'] ) {
			$text               .= ChB_Lang::translate( ChB_Lang::LNG0149 );
			$size_found_instock = true;
		} else {
			list( $size_chart_url, $size_chart_desc ) = ChB_Catalogue::getSizesChart4Product( $last_opened_product_id );
			if ( ! empty( $size_chart_url ) ) {
				$messages[] = [
					'type' => 'text',
					'text' => ChB_Lang::translate( ChB_Lang::LNG0150 ) . ' "' . $size_chart_desc . '"'
				];
				$messages[] = [ 'type' => 'image', 'url' => $size_chart_url ];
			}

			if ( ! empty( $product['size2find']['size_found'] ) ) {
				if ( $product['size2find']['size_found']['instock'] ) {
					$text               .= ChB_Lang::translateWithPars( ChB_Lang::LNG0151, $product['size2find']['size_found']['title'] ) . "\n";
					$text_on_buy_button = ChB_Lang::translateWithPars( ChB_Lang::LNG0045, $product['size2find']['size_found']['title'] );
					$attr_value_slug    = $product['size2find']['size_found']['attr_value_slug'];
					$size_found_instock = true;
				} else {
					$text .= ChB_Lang::translateWithPars( ChB_Lang::LNG0152, $product['size2find']['size_found']['title'] ) . "\n";
				}
			} elseif ( ! empty( $size2find ) && $size2find !== ChB_Common::EMPTY_TEXT ) {
				$text .= ChB_Lang::translateWithPars( ChB_Lang::LNG0154, $size2find ) . "\n";
			}

			if ( empty( $attr_value_slug ) ) {
				$text .= self::printProductAvailability( $product, null, true );
			}
		}

		$messages[] = [
			'type'               => 'cards',
			'image_aspect_ratio' => 'horizontal',
			'elements'           => [
				[
					'image_url' => $product['image_url'],
					'title'     => ChB_Catalogue::getProductName( $last_opened_product_id )
				]
			]
		];

		if ( ! empty( $attr_value_slug ) ) {
			$pa_filter                                 = $ChB->getParam( 'pa_filter' );
			$pa_filter[ ChB_Common::PA_ATTR_SIZE_KEY ] = $attr_value_slug;
			$ChB->setParam( 'pa_filter', $pa_filter );
		}
		$buttons = [ ChB_FLOW_Catalogue::makeBuyButton( $ChB, $events, $text_on_buy_button, $last_opened_product_id, null ) ];
		if ( $text ) {
			$messages[] = [ 'type' => 'text', 'text' => 'ℹ ' . $text ];
		}
		$messages[] = [ 'type' => 'text', 'text' => $price_text, 'buttons' => $buttons ];

		if ( ! $size_found_instock ) {
			$size_slugs4more_products = null;
			$cats_slug4more_products  = ChB_Catalogue::getCatsSlugsByProductId( $last_opened_product_id )[0];
			$size_slugs4more_products = ChB_Catalogue::findCatSizes( $cats_slug4more_products, $size2find );

			if ( ! empty( $size_slugs4more_products ) ) {
				$text            = ChB_Lang::translate( ChB_Lang::LNG0155 );
				$event_tags4list = ChB_Analytics::addTagToTags( ChB_Analytics::TAG_DF_SIZE_SIMILAR, $last_opened_product['event_tags'] );
				ChB_Analytics::addEvent( $ChB->events, ChB_Analytics::EVENT_LIST_PRODUCTS, $event_tags4list );
				list( $messages_more_products, $count_cards ) = self::getProductsGallery( $ChB, [
					'cat_slug'     => $cats_slug4more_products,
					'size_slug'    => $size_slugs4more_products,
					'aspect_ratio' => 'horizontal'
				], $ChB->promo, null, $text );
				if ( $count_cards > 0 ) {
					ChB_Analytics::registerEvent( ChB_Analytics::EVENT_LIST_PRODUCTS, [ 'tags' => $event_tags4list ], $ChB->user->fb_user_id );
					$messages = array_merge( $messages, $messages_more_products );
				}
			}
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function sendRelatedProducts( ChatBot $ChB ) {

		try {
			ChB_Common::my_log( 'sendRelatedProducts  ' . $ChB->user->fb_user_id );
			if ( in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'users_ignore_reminders' ) ) || $ChB->user->getBlock4UserReminders() ) {
				ChB_Common::my_log( 'ignoring.. ' . $ChB->user->fb_user_id );

				return;
			}

			$last_opened_products = $ChB->user->getLastProductsOpenedByUser();
			if ( $last_opened_products ) {
				$product_gender               = ChB_Catalogue::userGender2ProductGender( $ChB->user->getGender() );
				$limit                        = 10;
				$last_opened_products_reverse = array_reverse( $last_opened_products );
				$related_product_ids          = ChB_Catalogue::getRelatedProducts4List( $ChB->user, $last_opened_products_reverse, $product_gender, $limit );
				$ChB->user->clearLastProductsOpenedByUser();

				ChB_Analytics::addEvent( $ChB->events, ChB_Analytics::EVENT_LIST_PRODUCTS, [ ChB_Analytics::TAG_RELATED ] );
				ChB_Analytics::registerEvent( ChB_Analytics::EVENT_LIST_PRODUCTS, [ 'tags' => [ ChB_Analytics::TAG_RELATED ] ], $ChB->user->fb_user_id );

				if ( ! empty( $related_product_ids ) ) {
					self::sendSelectedProducts( $ChB, false, null, null, null, $related_product_ids, ChB_Lang::translate( ChB_Lang::LNG0083 ) );
				}
			}

		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'sendRelatedProducts Exception ' . $e->getMessage() . ' === ' . $e->getTraceAsString() );
		}
	}

	public static function sendExtBotProducts( ChatBot $ChB, $product_id ) {

		try {
			ChB_Common::my_log( 'sendProductsFromExtBot  ' . $ChB->user->fb_user_id );
			if ( in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'users_ignore_reminders' ) ) || $ChB->user->getBlock4UserReminders() ) {
				ChB_Common::my_log( 'ignoring.. ' . $ChB->user->fb_user_id );

				return;
			}

			if ( empty( ChB_Settings()->getParam( 'ext_bots' )['on'] ) ) {
				return;
			}
			$ext_bot_id   = ChB_Settings()->getParam( 'ext_bots' )['on'];
			$ext_bot_pars = ChB_Settings()->getParam( 'ext_bots' )['pars'][ $ext_bot_id ];

			$cat_slug       = ChB_Catalogue::getCatSlugByProductId( $product_id );
			$product_gender = ChB_Catalogue::userGender2ProductGender( $ChB->user->getGender() );

			ChB_Common::my_log( 'sendProductsFromExtBot  pr_id=' . $product_id . ' cat=' . $cat_slug );

			if ( ! empty( $ext_bot_pars['cats'][ $cat_slug ] ) ) {
				$ext_cat_slug = $ext_bot_pars['cats'][ $cat_slug ];
			} elseif ( ! empty( $ext_bot_pars['cats'][ '_DEFAULT_' . $product_gender ] ) ) {
				$ext_cat_slug = $ext_bot_pars['cats'][ '_DEFAULT_' . $product_gender ];
			} elseif ( ! empty( $ext_bot_pars['cats']['_DEFAULT_'] ) ) {
				$ext_cat_slug = $ext_bot_pars['cats']['_DEFAULT_'];
			}

			if ( empty( $ext_cat_slug ) ) {
				ChB_Common::my_log( 'cannot get ext_cat_slug.. ' . $ChB->user->fb_user_id );

				return;
			}

			// We pass add_par to ext bot,
			// He adds contents of the add_par to the ext bot links
			// When user hits the result links (int the ext bot), we can track where user came from
			$ext_data = $ChB->makeExtBotQuery( $ext_bot_pars['domain'],
				[
					'task'     => 'manychat_cat_getProducts',
					'cat_slug' => $ext_cat_slug,
					'view'     => ChB_Common::VIEW_EXT,
					'add_par'  => urlencode( 'evt[]=' . ChB_Analytics::packEvent4Url( ChB_Analytics::EVENT_EXT_IN,
							[
								'out_cat'  => $cat_slug,
								'in_cat'   => $ext_cat_slug,
								'out_bot'  => ChB_Settings()->getParam( 'ext_bots' )['this_bot'],
								'out_usid' => $ChB->user->fb_user_id
							] ) )
				] );
			if ( ! empty( $ext_data ) ) {
				$ext_data = json_decode( $ext_data, true );
			}
			if ( empty( $ext_data ) ) {
				ChB_Common::my_log( $ext_data, true, 'cannot decode ext data.. ' . $ChB->user->fb_user_id );

				return;
			}

			$event_pars = [ 'out_cat' => $cat_slug, 'in_cat' => $ext_cat_slug, 'in_bot' => $ext_bot_id ];
			foreach ( $ext_data['content']['messages'] as &$message ) {
				if ( $message['type'] === 'cards' ) {
					foreach ( $message['elements'] as &$card ) {
						if ( ! empty( $card['buttons'] ) ) {
							foreach ( $card['buttons'] as &$button ) {

								// Changing CF_Events automatically fires rule 'RRB Events' -
								// this is how we track button click
								// But the action 'set_field_value' is NOT launching if button url leads to m.me,
								// that's why we wrap it in redirect_url

								if ( ! empty( $button['url'] ) ) {
									$button['url'] =
										ChB_Settings()->redirect_url .
										'&url=' . urlencode( $button['url'] );
								}
								$button['actions'] = [
									[
										'action'     => 'set_field_value',
										'field_name' => ChB_Constants::CF_Events,
										'value'      => ChB_Analytics::packEvent4Url( ChB_Analytics::EVENT_EXT_CLICK_OUT, $event_pars )
									]
								];
							}
						}
					}
				}
			}

			unset( $message, $card, $button );

			$text = $ChB->user->getHi() . chr( 10 ) .
			        ChB_Lang::translateWithPars( ChB_Lang::LNG0117, $ext_bot_pars['name'] ) . chr( 10 ) .
			        ChB_Lang::translate( $ext_bot_pars['message'] );

			array_unshift( $ext_data['content']['messages'], [ 'type' => 'text', 'text' => $text ] );
			$fields = [ 'data' => $ext_data ];
			$result = ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );
			if ( $result ) {
				ChB_Analytics::registerEvent( ChB_Analytics::EVENT_EXT_OUT, $event_pars, $ChB->user->fb_user_id );
			}
		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'sendProductsFromExtBot Exception ' . $e->getMessage() . ' === ' . $e->getTraceAsString() );
		}
	}

	public static function sendProductsReminder( ChatBot $ChB ) {

		ChB_Common::my_log( 'sendProductsReminder ' . $ChB->user->fb_user_id );
		if ( in_array( $ChB->user->fb_user_id, ChB_Settings()->getParam( 'users_ignore_reminders' ) ) || $ChB->user->getBlock4UserReminders() ) {
			ChB_Common::my_log( 'ignoring.. ' . $ChB->user->fb_user_id );

			return;
		}
		$product_ids = ChB_Catalogue::getRecommendedProducts( $ChB->user, 10 );

		if ( empty( $product_ids ) ) {
			ChB_Common::my_log( 'sendProductsReminder ' . $ChB->user->fb_user_id . ' empty products_id' );

			return;
		}

		$text = $ChB->user->getHi() . chr( 10 ) .
		        ChB_Lang::translateWithPars( ChB_Lang::LNG0112, '' );

		ChB_Analytics::addEvent( $ChB->events, ChB_Analytics::EVENT_LIST_PRODUCTS, [ ChB_Analytics::TAG_REMINDER ] );
		ChB_Analytics::registerEvent( ChB_Analytics::EVENT_LIST_PRODUCTS, [ 'tags' => [ ChB_Analytics::TAG_REMINDER ] ], $ChB->user->fb_user_id );

		self::sendSelectedProducts( $ChB, false, null, null, null, $product_ids, $text );
	}

	public static function sendSelectedProducts( ChatBot $ChB, $is_get, $cat_slug, $brand, $size_slug, $product_ids, $text, $trailing_text = null ) {

		ChB_Common::my_log( 'sendSelectedProducts ' . $ChB->user->fb_user_id );
		$custom_messages = ( empty( $text ) ? null : ( is_string( $text ) ? [
			[
				'type' => 'text',
				'text' => $text
			]
		] : $text ) );

		$args = [
			'cat_slug'    => $cat_slug,
			'brand'       => $brand,
			'size_slug'   => $size_slug,
			'product_ids' => $product_ids,
			'view'        => $ChB->getParam( 'view' )
		];

		list( $messages, $count ) = self::getProductsGallery( $ChB, $args, $ChB->promo, null, $custom_messages );

		if ( empty( $count ) ) {
			return false;
		}

		if ( ! empty( $trailing_text ) ) {
			$trailing_custom_messages = is_string( $trailing_text ) ? [
				[
					'type' => 'text',
					'text' => $trailing_text
				]
			] : $trailing_text;
			$messages                 = array_merge( $messages, $trailing_custom_messages );
		}

		if ( ! $is_get && $messages ) {
			$fields = [ 'data' => [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ] ];

			return ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	//could be useful later
	public static function getProductsShopMore4Cart( ChatBot $ChB, $custom_message = null ) {

		$product_ids    = $ChB->cart->getCartProductsIds();
		$product_gender = ChB_Catalogue::userGender2ProductGender( $ChB->user->getGender() );

		if ( ! empty( $product_ids ) ) {
			$product_ids = ChB_Catalogue::getRelatedProducts4List( $ChB->user, $product_ids, $product_gender, 40 );
		} else {
			$product_ids = ChB_Catalogue::getSomePopularProducts( 18, $product_gender, false );
		}

		list( $messages ) = ChB_FLOW_Catalogue::getProductsGallery( $ChB, [ 'product_ids' => $product_ids ], $ChB->promo, null, null );

		if ( ! empty( $custom_message ) ) {
			array_unshift( $messages, [ 'type' => 'text', 'text' => $custom_message ] );
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function makeCard4ProductVideo( $product_sku, $product_id, $fb_user_id, $event_tags ) {
		$video_filename = $product_sku . '.mp4';
		$pic_filename   = $product_sku . '-blur.png';

		if ( ! file_exists( ChB_Settings()->getParam( 'videos_path' ) . $video_filename ) ) {
			return false;
		}

		$evt_par = '';
		$evt_par .= '&evt[]=' . ChB_Analytics::packEvent4Url( ChB_Analytics::EVENT_VIEW_VIDEO, [
				'tags'  => $event_tags,
				'pr_id' => $product_id
			] );

		$buttons[] = [
			'type'         => 'url',
			'caption'      => ChB_Lang::translate( ChB_Lang::LNG0105 ),
			'url'          => ChB_Settings()->getParam( 'videos_url' ) . $video_filename . '?product_video=' . ChB_Settings()->salt . '&fb_user_id=' . $fb_user_id . $evt_par,
			'webview_size' => 'medium'
		];

		return [
			'type'               => 'cards',
			'image_aspect_ratio' => 'square',
			'elements'           =>
				[
					[
						'title'     => ChB_Lang::translate( ChB_Lang::LNG0106 ),
						'image_url' => ChB_Settings()->getParam( 'videos_url' ) . $pic_filename,
						'buttons'   => $buttons
					]
				]
		];
	}

}