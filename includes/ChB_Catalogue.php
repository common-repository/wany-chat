<?php

namespace ChatBot;

class ChB_Catalogue {

	public static function getProductBrands() {
		$brand_terms = get_terms( ChB_Common::PA_ATTR_BRAND_KEY );
		$res         = [];

		foreach ( $brand_terms as $brand_term ) {
			$ids = wc_get_products( self::getProductQueryArgs(
				[
					'limit'     => 1,
					'return'    => 'ids',
					'tax_query' => [
						[
							'taxonomy' => ChB_Common::PA_ATTR_BRAND_KEY,
							'terms'    => $brand_term->term_id,
							'field'    => 'id'
						]
					]
				] ) );

			if ( ! empty( $ids ) ) {
				$res[] = $brand_term;
			}
		}

		return $res;
	}

	public static function getProductCats( $args, $limit, $decrease_num_of_cards_by, ChB_Promo $promo ) {

		ChB_Settings()->tic( 'getProductCats' );

		if ( empty( $args['parent'] ) ) {
			$parent_cat_id = null;
		} elseif ( is_numeric( $args['parent'] ) ) {
			$parent_cat_id = $args['parent'];//legacy 04.08.2021
		} else {
			$parent_cat = get_term_by( 'slug', $args['parent'], 'product_cat' );
			if ( ! $parent_cat ) {
				return false;
			}
			$parent_cat_id = $parent_cat->term_id;
		}

		list( $all_terminal_subcats, $subcats ) = self::getAllTerminalSubCatsRecursive( $parent_cat_id, $args );

		if ( empty( $all_terminal_subcats ) || empty( $subcats ) ) {
			return false;
		}

		$tax_query = [];
		if ( ! empty( $args['brand'] ) ) {
			$tax_query = [
				[
					'taxonomy' => ChB_Common::PA_ATTR_BRAND_KEY,
					'terms'    => $args['brand'],
					'field'    => 'name'
				]
			];
		}

		$cats                 = [];
		$cats_with_subcats    = [];
		$cats_num_of_products = [];
		foreach ( $subcats as &$current_subcats ) {
			$cat_id     = $current_subcats['cat_id'];
			$query_pars = self::getProductQueryArgs(
				[
					'category'     => empty( $current_subcats['subcats'] ) ? $current_subcats['cat_slug'] : $current_subcats['subcats'],
					'limit'        => - 1,
					'price_is_set' => 1,
					'return'       => 'ids'
				] );

			if ( ! empty( $tax_query ) ) {
				$query_pars['tax_query'] = $tax_query;
			}

			$cats_num_of_products[ $cat_id ] = count( wc_get_products( $query_pars ) );
			if ( ! $cats_num_of_products[ $cat_id ] ) {
				continue;
			}

			$cat = self::getProductCatBy( $cat_id, 'id', $args['brand'], $promo );
			if ( empty( $cat->image_url ) || $cat->image_url === wc_placeholder_img_src() ) {
				continue;
			}

			if ( ! empty( $current_subcats['subcats'] ) ) {
				$cats_with_subcats[] = $cat_id;
			}

			$cats[] = $cat;
		}

		if ( $decrease_num_of_cards_by > 0 ) {
			$limit -= $decrease_num_of_cards_by;
		}

		$new_cats = [];
		$offset   = $args['offset'];
		if ( sizeof( $cats ) - $offset <= $limit ) {
			for ( $i = $offset; $i < sizeof( $cats ); $i ++ ) {
				$new_cats[] = $cats[ $i ];
			}
			$new_offset       = - 1;
			$next_page_exists = false;
			$cats_left        = 0;
		} else {
			for ( $i = $offset; $i < $offset + $limit - 1; $i ++ ) {
				$new_cats[] = $cats[ $i ];
			}
			$new_offset       = $offset + $limit - 1;
			$next_page_exists = true;
			$cats_left        = sizeof( $cats ) - $offset - $limit + 1;
		}

		ChB_Settings()->toc( 'getProductCats' );

		return [
			'cats'                 => $new_cats,
			'new_offset'           => $new_offset,
			'next_page_exists'     => $next_page_exists,
			'cats_left'            => $cats_left,
			'cats_num_of_products' => $cats_num_of_products,
			'cats_with_subcats'    => $cats_with_subcats
		];
	}

	public static function getProductCatName( $cat_term ) {

		return apply_filters( 'wany_hook_translate_product_cat_name', $cat_term->name, $cat_term );
	}

	public static function getSizesFromCat( $cat_slug, $brand ) {
		ChB_Settings()->tic( 'getSizesFromCat' );
		$sizes = [];

		if ( ! empty( $brand ) ) {
			$tax_query[] = [
				'taxonomy' => ChB_Common::PA_ATTR_BRAND_KEY,
				'terms'    => $brand,
				'field'    => 'name'
			];
		}

		$query_pars = self::getProductQueryArgs( [
			'category' => $cat_slug,
			'orderby'  => 'category',
			'order'    => 'DESC',
			'return'   => 'ids',
		] );

		if ( ! empty( $tax_query ) ) {
			$query_pars['tax_query'] = $tax_query;
		}

		//1. Selecting simple products
		$query_pars['type']  = 'simple';
		$query_pars['limit'] = 1;
		$products_ids        = wc_get_products( $query_pars );
		if ( ! empty( $products_ids ) ) {
			$sizes[ ChB_Common::NO_SIZES ] = [ 'title' => ChB_Common::NO_SIZES ];
		}

		//2. Selecting variable products
		$query_pars['type']  = 'variable';
		$query_pars['limit'] = - 1;
		$products_ids        = wc_get_products( $query_pars );

		$sizes2check = [];
		foreach ( $products_ids as $product_id ) {
			$size_terms = wc_get_product_terms( $product_id, ChB_Common::PA_ATTR_SIZE_KEY );
			foreach ( $size_terms as $size_term ) {
				if ( ! isset( $sizes2check[ $size_term->slug ] ) ) {
					$sizes2check[ $size_term->slug ] = $size_term->name;
				}
			}
		}

		foreach ( $sizes2check as $size_slug => $size_name ) {
			list( $product_ids_filtered_by_size ) =
				self::getFilteredProductVariations( [ 'key' => ChB_Common::PA_ATTR_SIZE_KEY, 'value' => $size_slug ],
					$products_ids,
					1 );
			if ( ! empty( $product_ids_filtered_by_size ) ) {
				$sizes[ $size_slug ] = ChB_Catalogue::getSizeDetails( $size_slug, true );
			}
		}

		ChB_Settings()->toc( 'getSizesFromCat' );

		return $sizes;
	}

	public static function getSizeRanges4Cat( $cat_slug, $brand ) {
		$cat = get_term_by( 'slug', $cat_slug, 'product_cat' );
		if ( empty( $cat->term_id ) ) {
			return false;
		}
		$size_ranges_str = get_term_meta( $cat->term_id, ChB_Common::CAT_ATTR_SIZES, true );
		if ( empty( $size_ranges_str ) ) {
			return false;
		}

		$size_ranges = json_decode( $size_ranges_str );
		if ( empty( $size_ranges ) ) {
			return false;
		}

		$all_sizes_in_stock   = self::getSizesFromCat( $cat_slug, $brand );
		$filtered_size_ranges = [];
		foreach ( $size_ranges as $size_range_name => $size_range_slugs ) {
			foreach ( $size_range_slugs as $size_slug ) {
				if ( ! empty( $all_sizes_in_stock[ $size_slug ] ) ) {
					$filtered_size_ranges[ $size_range_name ] = $size_range_slugs;
					break;
				}
			}
		}

		return $filtered_size_ranges;
	}

	public static function getSizesChart4Product( $product_id ) {
		$cat = self::getCategoryTermByProductId( $product_id );
		if ( empty( $cat ) ) {
			return null;
		}
		$brand      = self::getBrandTermByProductId( $product_id );
		$brand_slug = ( empty( $brand->slug ) ? null : $brand->slug );

		$url                 = null;
		$desc                = null;
		$cat_size_charts_str = get_term_meta( $cat->term_id, ChB_Common::CAT_ATTR_SIZE_CHARTS, true );
		if ( ! empty( $cat_size_charts_str ) ) {
			$cat_size_charts = json_decode( $cat_size_charts_str, true );
			if ( ! empty( $brand_slug ) && ! empty( $cat_size_charts[ $brand_slug ] ) ) {
				$url  = ChB_Settings()->uploads_url . $cat_size_charts[ $brand_slug ];
				$desc = $brand->name . ', ' . self::getProductCatName( $cat );;
			} elseif ( ! empty( $cat_size_charts['_DEFAULT_'] ) ) {
				$url  = ChB_Settings()->uploads_url . $cat_size_charts['_DEFAULT_'];
				$desc = self::getProductCatName( $cat );;
			}
		} else {
			$filename = 'size-chart-' . $brand->slug . '-' . $cat->slug . '-' . ChB_Settings()->lang . '.jpg';
			if ( file_exists( ChB_Settings()->rrbot_uploads_path . $filename ) ) {
				$url  = ChB_Settings()->rrbot_uploads_url . $filename;
				$desc = $brand->name . ', ' . self::getProductCatName( $cat );
			} else {
				$filename = 'size-chart-' . $brand->slug . '-' . $cat->slug . '-en.jpg';
				if ( file_exists( ChB_Settings()->rrbot_uploads_path . $filename ) ) {
					$url  = ChB_Settings()->rrbot_uploads_url . $filename;
					$desc = $brand->name . ', ' . self::getProductCatName( $cat );
				} else {
					$filename = 'size-chart-' . $cat->slug . '-' . ChB_Settings()->lang . '.jpg';
					if ( file_exists( ChB_Settings()->rrbot_uploads_path . $filename ) ) {
						$url  = ChB_Settings()->rrbot_uploads_url . $filename;
						$desc = self::getProductCatName( $cat );
					} else {
						$filename = 'size-chart-' . $cat->slug . '-en.jpg';
						if ( file_exists( ChB_Settings()->rrbot_uploads_path . $filename ) ) {
							$url  = ChB_Settings()->rrbot_uploads_url . $filename;
							$desc = self::getProductCatName( $cat );
						}
					}
				}
			}
		}

		return [ $url, $desc ];
	}

	public static function getSizeDetails( $size_slug, $add_desc_and_name2title ) {
		if ( $size_slug === ChB_Common::NO_SIZES ) {
			return [ 'name' => ChB_Common::NO_SIZES, 'slug' => $size_slug, 'title' => ChB_Common::NO_SIZES ];
		}

		$term = get_term_by( 'slug', $size_slug, ChB_Common::PA_ATTR_SIZE_KEY );
		if ( empty( $term ) ) {
			return [ 'name' => '', 'slug' => $size_slug, 'title' => '' ];
		} elseif ( ! empty( $term->description ) ) {
			$name  = $term->name;
			$desc  = $term->description;
			$title = ( $add_desc_and_name2title ? $desc . ' (' . $name . ')' : $desc );

			return [
				'term_id' => $term->term_id,
				'name'    => $name,
				'slug'    => $size_slug,
				'title'   => $title,
				'desc'    => $desc
			];
		} else {
			return [ 'term_id' => $term->term_id, 'name' => $term->name, 'slug' => $size_slug, 'title' => $term->name ];
		}
	}

	public static function getProductCatImageUrl( $cat, $brand, $text_over_image ) {

		$image_url = null;
		if ( ! empty( $brand ) ) {
			$filename = $cat->slug . '-' . $brand . '1.jpg';
			if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
				$image_url = ChB_Settings()->uploads_url . $filename;
			} else {
				$filename = $cat->slug . '-' . $brand . '.jpg';
				if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
					$image_url = ChB_Settings()->uploads_url . $filename;
				}
			}

			if ( $image_url && $text_over_image ) {
				$image_url = ChB_Common::putTextOverImage( $filename, $text_over_image, ChB_Settings()->getParam( 'cats_aspect_ratio' ) );
			}
		}

		if ( ! $image_url ) {

			$thumbnail_id = get_term_meta( $cat->term_id, ChB_Common::CAT_ATTR_CAT_IMAGE_ID, true );
			if ( ! $thumbnail_id ) {
				$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
			}

			if ( $thumbnail_id ) {
				$image_url = ChB_Common::getAttachmentMediumSizeUrl( $thumbnail_id, $text_over_image, ChB_Settings()->getParam( 'cats_aspect_ratio' ) );
			} else {
				$image_urls = ChB_Common::getImageGeneratedByText( $cat->term_id, true );
				if ( ! empty( $image_urls[0] ) ) {
					$image_url = $image_urls[0];
				}
			}

			if ( ! $image_url ) {
				$image_url = ChB_Image::getColorBlockImageUrlById( $cat->term_id );
			}
		}

		return $image_url;
	}

	public static function getProductCatBy( $id_or_slug, $field, $brand, ChB_Promo $promo = null ) {

		if ( $field == 'id' ) {
			$cat = get_term( $id_or_slug, 'product_cat' );
		} else {
			$cat = get_term_by( 'slug', $id_or_slug, 'product_cat' );
		}

		$cat->name       = self::getProductCatName( $cat );
		$text_over_image = empty( $promo ) ? null : $promo->getTextOverImage( null, $cat->term_id );
		$cat->image_url  = self::getProductCatImageUrl( $cat, $brand, $text_over_image );

		return $cat;
	}

	public static function getAllBrandsImageUrl() {

		$image_url = null;
		$filename  = 'all-logos.png';
		if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
			$image_url = ChB_Settings()->uploads_url . $filename;
		}

		if ( empty( $image_url ) ) {
			$image_url = wc_placeholder_img_src();
		}

		return $image_url;
	}

	public static function getBrandImageUrl( $brand ) {

		$image_url = null;
		if ( ! empty( $brand ) ) {
			$filename = $brand . '-cover.png';
			if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
				$image_url = ChB_Settings()->uploads_url . $filename;
			} else {
				$filename = $brand . '-cover.jpg';
				if ( file_exists( ChB_Settings()->uploads_path . $filename ) ) {
					$image_url = ChB_Settings()->uploads_url . $filename;
				}
			}
		}

		if ( empty( $image_url ) ) {
			$image_url = wc_placeholder_img_src();
		}

		return $image_url;
	}

	public static function getNextPageImageUrl( $aspect_ratio ) {

		$image_url = null;

		if ( $aspect_ratio === 'square' ) {
			$filename = 'next-page-' . ChB_Settings()->lang . '.png';
		} else {
			$filename = 'next-page-horizontal-' . ChB_Settings()->lang . '.png';
		}

		if ( file_exists( ChB_Settings()->assets_path . 'img/' . $filename ) ) {
			$image_url = ChB_Settings()->assets_url . 'img/' . $filename;
		}

		if ( empty( $image_url ) && ChB_Settings()->lang !== 'en' ) {
			if ( $aspect_ratio === 'square' ) {
				$filename = 'next-page-en.png';
			} else {
				$filename = 'next-page-horizontal-en.png';
			}

			if ( file_exists( ChB_Settings()->assets_path . 'img/' . $filename ) ) {
				$image_url = ChB_Settings()->assets_url . 'img/' . $filename;
			}
		}

		if ( empty( $image_url ) ) {
			$image_url = wc_placeholder_img_src();
		}

		return $image_url;
	}

	public static function getDefaultPAs() {
		$size  = null;
		$color = null;
		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			if ( $taxonomy->attribute_name === 'color' || $taxonomy->attribute_name === 'colour' ) {
				$color = $taxonomy->attribute_name;
			} elseif ( $taxonomy->attribute_name === 'size' ) {
				$size = $taxonomy->attribute_name;
			}
		}

		$res = [];
		if ( $size ) {
			$res[] = 'pa_' . $size;
		}
		if ( $color ) {
			$res[] = 'pa_' . $color;
		}

		return $res;
	}

	public static function getProductAttrOnBuyButton( \WC_Product $wc_product ) {
		if ( ! ( $wc_product instanceof \WC_Product_Variable ) ) {
			return null;
		}
		if ( ! ChB_Settings()->getParam( 'pa_on_buy_button' ) ) {
			return null;
		}
		$product_attrs   = $wc_product->get_attributes();
		$variation_attrs = [];
		foreach ( $product_attrs as $attr_slug => $product_attr ) {
			if ( $product_attr->get_variation() ) {
				$variation_attrs[ $attr_slug ] = 1;
			}
		}

		return ( count( $variation_attrs ) === 1 && isset( $variation_attrs[ ChB_Settings()->getParam( 'pa_on_buy_button' ) ] ) ) ? ChB_Settings()->getParam( 'pa_on_buy_button' ) : null;
	}

	public static function getProductVariationsData( $product_id, $pa_filter, ChB_Promo $promo ) {
		$res        = [];
		$wc_product = wc_get_product( $product_id );

		$res['product_type'] = $wc_product->get_type();
		if ( $res['product_type'] === 'simple' ) {
			return $res;
		}

		if ( $res['product_type'] !== 'variable' ) {
			return null;
		}

		//with respect to 'woocommerce_hide_out_of_stock_items' option
		$variations = $wc_product->get_available_variations( 'objects' );
		if ( empty( $variations ) ) {
			return null;
		}

		$var_attr_keys = array_keys( $variations[0]->get_attributes() );
		if ( ! $var_attr_keys ) {
			return null;
		}

		if ( $pa_filter && count( $pa_filter ) === 1 && count( $var_attr_keys ) == 1 && isset( $pa_filter[ $var_attr_keys[0] ] ) ) {
			//special case used in checkSizesDetails()
			//we know slug of a size, size is the only attr, but we know don't know var_id
			$next_attr_key      = $var_attr_keys[0];
			$is_last_attr       = true;
			$auto_select_var_id = true;
		} else {
			//regular case
			if ( ! ChB_Settings()->getParam( 'pa_order' ) ) {
				$pa_order4next_attr = $var_attr_keys;
			} else {
				$pa_order4next_attr = array_merge( ChB_Settings()->getParam( 'pa_order' ), $var_attr_keys );
			}//not unique

			$next_attr_key = null;
			foreach ( $pa_order4next_attr as $pa_order_key ) {
				if ( in_array( $pa_order_key, $var_attr_keys ) && empty( $pa_filter[ $pa_order_key ] ) ) {
					$next_attr_key = $pa_order_key;
					break;
				}
			}
			if ( ! $next_attr_key ) {
				return null;
			}
			$is_last_attr = ( count( $var_attr_keys ) === ( empty( $pa_filter ) ? 0 : count( $pa_filter ) ) + 1 );
		}

		$product_attrs = $wc_product->get_attributes();
		if ( empty( $product_attrs[ $next_attr_key ] ) ) {
			return null;
		}
		$next_attr        = $product_attrs[ $next_attr_key ];
		$next_attr_values = [];
		foreach ( $variations as $variation ) {
			$var_attrs = $variation->get_attributes();
			$is_ok     = true;
			foreach ( $pa_filter as $pa_filter_key => $pa_filter_val ) {
				if ( ! empty( $var_attrs[ $pa_filter_key ] ) && $var_attrs[ $pa_filter_key ] !== $pa_filter_val ) {
					$is_ok = false;
					break;
				}
			}

			if ( ! $is_ok ) {
				continue;
			}

			$vals = empty( $var_attrs[ $next_attr_key ] ) ? self::getProductAttrOptions( $next_attr ) : [ $var_attrs[ $next_attr_key ] ];
			foreach ( $vals as $val ) {
				$next_attr_value = [ 'name' => self::getProductAttrValueNameBySlug( $val, $next_attr ) ];
				if ( $is_last_attr ) {
					$next_attr_value['var_id']        = $variation->get_id();
					$next_attr_value['price_details'] = $promo->getPriceDetails( $variation );
				}
				$next_attr_values[ $val ] = $next_attr_value;
			}
		}

		$res['next_attr_name']   = self::getProductAttrName( $next_attr );
		$res['next_attr_slug']   = $next_attr_key;
		$res['next_attr_values'] = $next_attr_values;
		$res['is_last_attr']     = $is_last_attr;

		if ( ! empty( $auto_select_var_id ) && $next_attr_values ) {
			foreach ( $next_attr_values as $next_attr_value ) {
				$res['auto_selected_var_id'] = $next_attr_value['var_id'];
				break;
			}
		}

		return $res;
	}

	public static function getPAFilterByVariation( \WC_Product_Variation $variation ) {
		return $variation->get_attributes();
	}

	public static function getProductAttrName( \WC_Product_Attribute $product_attr ) {
		if ( $product_attr->get_name() === ChB_Common::PA_ATTR_SIZE_KEY ) {
			return ChB_Lang::translate( ChB_Lang::LNG0002 );
		}

		if ( $product_attr->is_taxonomy() ) {
			$taxonomy = get_taxonomy( $product_attr->get_name() );
			if ( empty( $taxonomy->labels->singular_name ) ) {
				return '';
			}

			return $taxonomy->labels->singular_name;
		} else {
			return $product_attr->get_name();
		}
	}

	public static function getProductAttrValueNameBySlug( $val_slug, \WC_Product_Attribute $product_attr ) {
		if ( $product_attr->is_taxonomy() ) {
			$term = get_term_by( 'slug', $val_slug, $product_attr->get_name() );
			if ( $term ) {
				return $term->name;
			}

			return '';
		} else {
			return $val_slug;
		}
	}

	public static function getProductAttrOptions( \WC_Product_Attribute $product_attr, $get_names = false ) {
		if ( $product_attr->is_taxonomy() ) {
			$res = [];
			foreach ( $product_attr->get_options() as $term_id ) {
				$term = get_term( $term_id, $product_attr->get_name() );
				if ( $term && ! is_wp_error( $term ) ) {
					$res[] = ( $get_names ? $term->name : $term->slug );
				}
			}

			return $res;
		} else {
			return $product_attr->get_options();
		}
	}

	public static function getProductAttrAvailableValues( $product, $attr_slugs ) {

		$wc_product = ( $product instanceof \WC_Product ? $product : wc_get_product( $product ) );
		if ( ! ( $wc_product instanceof \WC_Product_Simple ) && ! ( $wc_product instanceof \WC_Product_Variable ) ) {
			return null;
		}

		$product_attrs         = $wc_product->get_attributes();
		$lines                 = [];
		$variations_attr_slugs = [];

		// non-variation attrs
		foreach ( $attr_slugs as $attr_slug ) {
			if ( ! empty( $product_attrs[ $attr_slug ] ) ) {
				if ( $product_attrs[ $attr_slug ]['variation'] ) {
					$variations_attr_slugs[] = $attr_slug;
				} else {
					$line = implode( ', ', self::getProductAttrOptions( $product_attrs[ $attr_slug ], true ) );
					if ( $line ) {
						$lines[ $attr_slug ] = $line;
					}
				}
			}
		}

		// variation attrs
		if ( ( $wc_product instanceof \WC_Product_Variable ) && $variations_attr_slugs ) {
			$attrs_values = self::getVariationsAttrValues( $wc_product, true, $variations_attr_slugs );
			foreach ( $attrs_values as $attr_slug => $attr_info ) {
				$line = '';
				foreach ( $attr_info['values'] as $attr_value_info ) {
					$line = ( $line ? $line . ", " . $attr_value_info['name'] : $attr_value_info['name'] );
				}

				if ( $line ) {
					$lines[ $attr_slug ] = $line;
				}
			}
		}

		$res = [];
		foreach ( $attr_slugs as $attr_slug ) {
			if ( isset( $lines[ $attr_slug ] ) ) {
				if ( $attr_slug === ChB_Common::PA_ATTR_SIZE_KEY ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0065 ) . ' ' . $lines[ $attr_slug ];
				} elseif ( $attr_slug === ChB_Common::PA_ATTR_COLOR_KEY ) {
					$res[] = ChB_Lang::translate( ChB_Lang::LNG0060 ) . ' ' . $lines[ $attr_slug ];
				} else {
					$res[] = self::getProductAttrName( $product_attrs[ $attr_slug ] ) . ': ' . $lines[ $attr_slug ];
				}
			}
		}

		return $res;
	}

	/**
	 * @param \WC_Product_Variable $wc_product
	 * @param $only_available bool
	 * @param $attr_slugs
	 *
	 * @return array|null
	 */
	public static function getVariationsAttrValues( \WC_Product_Variable $wc_product, $only_available, $attr_slugs ) {

		if ( $only_available ) {
			//with respect to 'woocommerce_hide_out_of_stock_items' option
			$variations = $wc_product->get_available_variations( 'objects' );
		} else {
			$ids        = $wc_product->get_children();
			$variations = [];
			$pf         = new \WC_Product_Factory();
			foreach ( $ids as $id ) {
				$variations[] = $pf->get_product( $id );
			}
		}

		if ( empty( $variations ) ) {
			return [];
		}

		$product_attrs = $wc_product->get_attributes();
		$res           = [];
		foreach ( $attr_slugs as $attr_slug ) {
			if ( empty( $product_attrs[ $attr_slug ] ) ) {
				continue;
			}
			$product_attr     = $product_attrs[ $attr_slug ];
			$all_values       = self::getProductAttrOptions( $product_attr );
			$attr_values_info = [];
			foreach ( $variations as $variation ) {
				$var_attrs = $variation->get_attributes();
				if ( ! isset( $var_attrs[ $attr_slug ] ) ) {
					continue;
				}

				if ( $var_attrs[ $attr_slug ] ) {
					$values = [ $var_attrs[ $attr_slug ] ];
				} else {
					$values = $all_values;
				}

				$instock = ( $variation->get_stock_status() === 'instock' );
				foreach ( $values as $attr_value_slug ) {
					if ( isset( $attr_values_info[ $attr_value_slug ] ) ) {
						$attr_values_info[ $attr_value_slug ]['instock'] = ( $instock || $attr_values_info[ $attr_value_slug ]['instock'] );
					} else {
						$attr_values_info[ $attr_value_slug ] = [
							'name'    => self::getProductAttrValueNameBySlug( $attr_value_slug, $product_attr ),
							'instock' => $instock
						];
					}
				}
			}
			$res[ $attr_slug ] = [
				'name'         => self::getProductAttrName( $product_attr ),
				'product_attr' => $product_attr,
				'values'       => $attr_values_info
			];
		}

		return $res;
	}

	public static function printPaFilter( \WC_Product $wc_product, $pa_filter ) {
		if ( ! $wc_product || ! $pa_filter ) {
			return '';
		}

		if ( ! ( $wc_product instanceof \WC_Product_Variable ) && ! ( $wc_product instanceof \WC_Product_Variation ) ) {
			return '';
		}

		$wc_parent_product = $wc_product instanceof \WC_Product_Variation ? wc_get_product( $wc_product->get_parent_id() ) : $wc_product;
		if ( ! $wc_parent_product ) {
			return '';
		}

		$res           = '';
		$product_attrs = $wc_parent_product->get_attributes();
		foreach ( $pa_filter as $attr_key => $attr_value_slug ) {
			if ( empty( $product_attrs[ $attr_key ] ) ) {
				continue;
			}
			$product_attr = $product_attrs[ $attr_key ];
			$res          = ( ! $res ? '' : $res . "\n" ) .
			                self::getProductAttrName( $product_attr ) . ': ' . self::getProductAttrValueNameBySlug( $attr_value_slug, $product_attr );
		}

		return $res;
	}

	public static function convertPAFilterToWCVariationFormat( $pa_filter ) {
		$variation = [];
		foreach ( $pa_filter as $attr_key => $attr_val ) {
			$variation[ 'attribute_' . $attr_key ] = $attr_val;
		}

		return $variation;
	}

	public static function findProductSize( \WC_Product $wc_product, $size2find ) {
		$only_one_size = false;
		if ( $wc_product instanceof \WC_Product_Variable ) {
			$attrs_values = self::getVariationsAttrValues( $wc_product, false, [ ChB_Common::PA_ATTR_SIZE_KEY ] );
			if ( empty( $attrs_values[ ChB_Common::PA_ATTR_SIZE_KEY ] ) ) {
				$only_one_size = true;
			}
		} else {
			$only_one_size = true;
		}

		if ( $only_one_size ) {
			return [ 'only_one_size' => true ];
		} elseif ( empty( $size2find ) || $size2find === ChB_Common::EMPTY_TEXT ) {
			return [ 'only_one_size' => false ];
		}

		$size_found  = null;
		$size2find_l = strtolower( $size2find );
		$size2find_u = strtoupper( $size2find );

		foreach ( $attrs_values[ ChB_Common::PA_ATTR_SIZE_KEY ]['values'] as $attr_value_slug => $attr_value_info ) {
			$term = get_term_by( 'slug', $attr_value_slug, ChB_Common::PA_ATTR_SIZE_KEY );
			if ( ! empty( $term->term_id ) ) {
				$synonyms = get_term_meta( $term->term_id, ChB_Common::SIZE_ATTR_SYNONYMS, true );
			}
			if ( ! empty( $synonyms ) ) {
				$syns = json_decode( $synonyms );
				if ( in_array( $size2find_l, $syns ) || in_array( $size2find_u, $syns ) ) {
					$size_found = [
						'title'           => $attr_value_info['name'],
						'instock'         => $attr_value_info['instock'],
						'attr_value_slug' => $attr_value_slug
					];
					break;
				}
			}
		}

		if ( empty( $size_found ) ) {
			return [ 'only_one_size' => false ];
		} else {
			return [
				'only_one_size' => false,
				'size_found'    => $size_found
			];
		}
	}

	public static function findCatSizes( $cat_slug, $size2find ) {
		$sizes = self::getSizesFromCat( $cat_slug, null );

		$sizes_found = [];
		$size2find_l = strtolower( $size2find );
		$size2find_u = strtoupper( $size2find );

		foreach ( $sizes as $size ) {
			if ( empty( $size['term_id'] ) ) //for ChB_Common::NO_SIZES
			{
				continue;
			}
			$synonyms = get_term_meta( $size['term_id'], ChB_Common::SIZE_ATTR_SYNONYMS, true );
			if ( ! empty( $synonyms ) ) {
				$syns = json_decode( $synonyms );
				if ( in_array( $size2find_l, $syns ) || in_array( $size2find_u, $syns ) ) {
					$sizes_found[] = $size['slug'];
				}
			}
		}

		return $sizes_found;
	}

	public static function getFilteredProductVariations( $pa_filter, $parent_ids = 0, $limit = - 1 ) {
		$query_pars = [
			'posts_per_page' => $limit,
			'post_type'      => 'product_variation',
			'post_status'    => 'publish',
			'fields'         => 'id=>parent',
			'meta_query'     => [
				[
					'key'   => '_stock_status',
					'value' => 'instock'
				]
			]
		];

		$query_pars['meta_query'][] = [
			'key'     => 'attribute_' . $pa_filter['key'],
			'value'   => $pa_filter['value'],
			'compare' => is_array( $pa_filter['value'] ) ? 'IN' : '=',
		];

		if ( ! empty( $parent_ids ) ) {
			$query_pars['post_parent__in'] = $parent_ids;
		}

		$q       = new \WP_Query( $query_pars );
		$parents = [];
		foreach ( $q->posts as $post ) {
			$parents[] = $post->post_parent;
		}
		wp_reset_postdata();

		return [ $parents, $q->posts ];
	}

	public static function getProductVariationIdByFilter( $product_id, $pa_filter, $check_stock = true, $status = 'publish' ) {
		$query_pars = [
			'post_parent' => $product_id,
			'post_type'   => 'product_variation',
			'fields'      => 'ids',
			'limit'       => 1
		];

		if ( $status ) {
			$query_pars['post_status'] = $status;
		}

		if ( $check_stock ) {
			$query_pars['meta_query'] = [
				[
					'key'   => '_stock_status',
					'value' => 'instock'
				]
			];
		}

		$query_pars['meta_query'][] = [
			'key'     => 'attribute_' . $pa_filter['key'],
			'value'   => $pa_filter['value'],
			'compare' => '=',
		];

		$q   = new \WP_Query( $query_pars );
		$res = ( $q && $q->posts ? $q->posts[0] : null );
		wp_reset_postdata();

		return $res;
	}

	/**
	 * Два прохода. Сначала выбираем с учетом цветов товаров, потом - без.
	 */
	public static function getRelatedProducts4List( ChB_User $user, &$product_ids, $product_gender, $limit ) {

		$related_product_ids = [];
		$cache               = [];
		$exclude_product_ids = array_merge( $product_ids, ChB_Order::getProductsOrderedByUser( $user ) );

		for ( $step = 1; $step <= 2; $step ++ ) {
			foreach ( $product_ids as $product_id ) {
				if ( sizeof( $related_product_ids ) >= $limit ) {
					break;
				}

				$color = null;
				if ( $step === 1 ) {
					$color = self::getColorByProductId( $product_id );
					if ( empty( $color ) ) {
						continue;
					}
				}

				$gender = self::getProductGender( $product_id );
				if ( $gender === ChB_Common::GENDER_UNISEX && ! empty( $product_gender ) ) {
					$gender = $product_gender;
				}

				$cur_limit           = $limit - sizeof( $related_product_ids );
				$exclude_product_ids = array_merge( $exclude_product_ids, $related_product_ids );
				$ids                 = ChB_Catalogue::getRelatedProducts( $exclude_product_ids, $color, $gender, $cur_limit, $cache );

				if ( ! empty( $ids ) ) {
					$related_product_ids = empty( $related_product_ids ) ? $ids : array_merge( $related_product_ids, $ids );
				}
			}
		}

		return $related_product_ids;
	}

	public static function getProductQueryArgs( $args ) {

		$args['status']       = 'publish';
		$args['visibility']   = 'visible';
		$args['stock_status'] = 'instock';
		$args['type']         = [ 'simple', 'variable' ];

		return $args;
	}

	public static function productIsVisible( $wc_product ) {
		if ( ! ( $wc_product instanceof \WC_Product ) ) {
			return false;
		}

		return $wc_product->get_status() === 'publish' &&
		       $wc_product->get_stock_status() === 'instock' &&
		       $wc_product->get_catalog_visibility() === 'visible' &&
		       ( $wc_product->get_type() === 'simple' || $wc_product->get_type() === 'variable' || $wc_product->get_type() === 'variation' ) &&
		       ! ( $wc_product->get_post_password() );
	}

	public static function getProductImage( \WC_Product $wc_product, $try_generate_by_text = false, $text_over_image = null, $add_full_size = false ) {
		if ( $thumbnail_id = $wc_product->get_image_id() ) {
			if ( $add_full_size ) {
				return [
					ChB_Common::getAttachmentMediumSizeUrl( $thumbnail_id, $text_over_image ),
					ChB_Common::getAttachmentFullSizeUrl( $thumbnail_id )
				];
			} else {
				return ChB_Common::getAttachmentMediumSizeUrl( $thumbnail_id, $text_over_image );
			}
		} elseif ( $try_generate_by_text ) {
			$image_urls = ChB_Common::getImageGeneratedByText( $wc_product->get_id() );
			if ( ! empty( $image_urls[0] ) ) {
				return $add_full_size ? [ $image_urls[0], $image_urls[0] ] : $image_urls[0];
			}
		}

		return $add_full_size ? [ null, null ] : null;
	}

	public static function getGalleryImages( \WC_Product $wc_product, $try_generate_by_text = false, $use_full_size = false ) {

		$gallery_image_ids  = $wc_product->get_gallery_image_ids();
		$gallery_image_urls = [];
		if ( $gallery_image_ids ) {
			foreach ( $gallery_image_ids as $gallery_image_id ) {
				if ( $use_full_size ) {
					$gallery_image_urls[] = ChB_Common::getAttachmentFullSizeUrl( $gallery_image_id );
				} else {
					$gallery_image_urls[] = ChB_Common::getAttachmentMediumSizeUrl( $gallery_image_id );
				}
			}
		} elseif ( $try_generate_by_text ) {
			$image_urls = ChB_Common::getImageGeneratedByText( $wc_product->get_id() );
			if ( ! empty( $image_urls[1] ) ) {
				for ( $i = 1; $i < sizeof( $image_urls ); $i ++ ) {
					$gallery_image_urls[] = $image_urls[ $i ];
				}
			}
		}

		return $gallery_image_urls;
	}

	public static function getProducts( $args, $limit, $decrease_num_of_cards_by, ChB_Promo $promo, $text_over_image ) {
		ChB_Settings()->tic( 'getProducts' );

		$size_slug   = empty( $args['size_slug'] ) ? null : $args['size_slug'];
		$pa_filter   = empty( $args['pa_filter'] ) ? null : $args['pa_filter'];
		$product_ids = empty( $args['product_ids'] ) ? [] : $args['product_ids'];
		$offset      = empty( $args['offset'] ) ? null : $args['offset'];

		if ( $limit == 0 ) {
			$limit = 10;
		}//paging in messenger
		$limit       -= $decrease_num_of_cards_by;
		$wc_products = [];
		$new_offset  = $offset;
		//List products by $product_ids (no other filters apply here)
		if ( ! empty( $product_ids ) && sizeof( $product_ids ) > $offset ) {
			$ind = 0;
			foreach ( $product_ids as $product_id ) {
				if ( sizeof( $wc_products ) >= $limit ) {
					break;
				}
				if ( $ind ++ < $offset ) {
					continue;
				}
				$new_offset ++;
				$wc_product = wc_get_product( $product_id );
				if ( self::productIsVisible( $wc_product ) ) {
					$wc_products[] = $wc_product;
				}
			}
		}

		$show_onsale       = ( ! empty( $args['view'] ) && in_array( 'onsale', $args['view'] ) );
		$query_is_possible = ( ! empty( $args['cat_slug'] ) || ! empty( $args['wc_tags'] ) || ! empty( $pa_filter )
		                       || ! empty( $args['pmin'] ) || ! empty( $args['pmax'] ) || $show_onsale || ! empty( $args['search'] ) );
		$do_query          = $query_is_possible;
		$pop_last_product  = false;
		$next_page_exists  = false;

		if ( ! $query_is_possible ) {
			if ( sizeof( $wc_products ) == $limit && sizeof( $product_ids ) > $new_offset ) {

				//Если страницу заполнили полностью, но $product_ids еще не все выведены
				$pop_last_product = true;
				$next_page_exists = true;
			}
		} else {
			if ( sizeof( $wc_products ) == $limit - 1 ) {
				$do_query         = false;
				$next_page_exists = true;
			} elseif ( sizeof( $wc_products ) >= $limit ) {
				$do_query         = false;
				$pop_last_product = true;
				$next_page_exists = true;
			}
		}
		$products_left = - 1;

		if ( $do_query ) {
			//Основной запрос по $cat_slug, $brand, $offset
			$query_offset = $new_offset - sizeof( $product_ids );

			$query_pars = self::getProductQueryArgs( [
				'orderby'  => 'date',
				'order'    => 'DESC',
				'limit'    => $limit,
				'paginate' => true,
				'offset'   => $query_offset
			] );

			if ( ! empty( $size_slug ) ) {
				if ( is_string( $size_slug ) && $size_slug === ChB_Common::NO_SIZES ||
				     is_array( $size_slug ) && $size_slug[0] === ChB_Common::NO_SIZES ) {
					$query_pars['type'] = 'simple';
				} else {
					$query_pars['type'] = 'variable';
					list( $product_ids_filtered_by_size ) = self::getFilteredProductVariations( [
						'key'   => ChB_Common::PA_ATTR_SIZE_KEY,
						'value' => $size_slug
					] );
					$query_pars['include'] = $product_ids_filtered_by_size;
				}
			}

			$tax_query = [];

			if ( ! empty( $args['wc_tags'] ) ) {
				$query_pars['tag'] = $args['wc_tags'];
			}

			if ( ! empty( $args['cat_slug'] ) ) {
				$query_pars['category'] = ( is_array( $args['cat_slug'] ) ? $args['cat_slug'] : [ $args['cat_slug'] ] );
				if ( ChB_Settings()->getTryOn( 'is_on' ) && ChB_TryOn::catHasTryOn( $args['cat_slug'] ) ) {
					$query_pars[ ChB_Constants::PROD_ATTR_HAS_TRY_ON . '_order' ] = 1;
				}
			}
			if ( ! empty( $args['brand'] ) ) {
				$tax_query[] = [
					'taxonomy' => ChB_Common::PA_ATTR_BRAND_KEY,
					'terms'    => $args['brand'],
					'field'    => 'name'
				];
			}

			if ( ! empty( $pa_filter ) ) {
				foreach ( $pa_filter as $pa_key => $pa_val ) {
					if ( ! is_array( $pa_val ) ) {
						$tax_query[] = [
							'taxonomy' => $pa_key,
							'terms'    => $pa_val,
							'field'    => 'slug'
						];
					} elseif ( count( $pa_val ) === 1 ) {
						$tax_query[] = [
							'taxonomy' => $pa_key,
							'terms'    => $pa_val[0],
							'field'    => 'slug'
						];
					} else {
						$tax_query[] = [
							'taxonomy' => $pa_key,
							'terms'    => $pa_val,
							'field'    => 'slug',
							'operator' => 'IN'
						];
					}
				}
			}

			if ( ! empty( $tax_query ) ) {
				$query_pars['tax_query'] = $tax_query;
			}

			if ( ! empty( $args['pmin'] ) ) {
				$query_pars['pmin'] = $args['pmin'];
			}

			if ( ! empty( $args['pmax'] ) ) {
				$query_pars['pmax'] = $args['pmax'];
			}

			if ( $show_onsale ) {
				$query_pars['onsale'] = 1;
			}

			if ( ! empty( $args['search'] ) ) {
				$query_pars['search'] = $args['search'];
			}

			$query_pars['price_is_set'] = 1;

			//query from db
			$query_result = wc_get_products( $query_pars );
			$res_products = $query_result->products;
			$total        = $query_result->total;

			//looping result, collecting products for a page
			$num_of_seen_products = 0;
			foreach ( $res_products as $res_product ) {
				if ( sizeof( $wc_products ) >= $limit ) {
					break;
				}
				$num_of_seen_products ++;

				if ( empty( $product_ids ) ) {
					$wc_products[] = $res_product;
					$new_offset ++;
				} else {
					$exists = false;
					foreach ( $wc_products as $wc_product ) {
						if ( $res_product->get_id() == $wc_product->get_id() ) {
							$exists = true;
							break;
						}
					}
					if ( ! $exists ) {
						$wc_products[] = $res_product;
						$new_offset ++;
					}
				}
			}

			if ( sizeof( $wc_products ) >= $limit ) {
				if ( $query_offset + $num_of_seen_products < $total ) {
					$pop_last_product = true;
					$next_page_exists = true;
					$products_left    = $total - ( $query_offset + $num_of_seen_products );
				}
			}
		}

		//Leaving one card for "next page"
		if ( $pop_last_product ) {
			array_pop( $wc_products );
			$new_offset --;
			if ( $products_left > 0 ) {
				$products_left ++;
			}
		}

		//Hera all products are already selected. Filling info in
		$products       = [];
		$try_on_enabled = ChB_Settings()->getTryOn( 'is_on' );
		foreach ( $wc_products as $wc_product ) {
			$product_id = $wc_product->get_id();
			$product    = [
				'id'    => $product_id,
				'name'  => ChB_Catalogue::getProductName( $wc_product ),
				'sdesc' => self::getProductDescription( $wc_product, true ),
			];

			if ( ! empty( ChB_Settings()->getParam( 'product_view_settings' )['list']['show_availability'] ) ) {
				$product['availability'] = ChB_Catalogue::getProductAttrAvailableValues( $wc_product, ChB_Settings()->getParam( 'product_view_settings' )['list']['show_availability'] );
			}

			$product['price_details'] = $promo->getPriceDetails( $wc_product );
			$product['has_try_on']    = ( $try_on_enabled ? ChB_TryOn::productHasTryOn( $product_id ) : false );

			$cur_text_over_image = empty( $text_over_image ) ? $promo->getTextOverImage( $wc_product, null ) : $text_over_image;
			$show_user_try_on    = $try_on_enabled && ! empty( $args['user'] ) && ! empty( $args['view'] ) && in_array( ChB_Common::VIEW_TRY_ON, $args['view'] );
			$image_url           = null;

			if ( $show_user_try_on ) {
				$image_url = ChB_TryOn::putOnHat( $args['user'], $product_id );
			}

			if ( ! $image_url ) {
				$image_url = self::getProductImage( $wc_product, true, $cur_text_over_image );
			}

			if ( ! $image_url ) {
				continue;
			}

			$product['image_url'] = $image_url;
			$txt                  = self::getAttrsText( $wc_product, 'list', ' ' );
			if ( ! empty( $txt ) ) {
				$product['attrs_txt'] = $txt;
			}

			$products[] = $product;
		}

		ChB_Settings()->toc( 'getProducts' );

		return [
			'products'         => $products,
			'new_offset'       => $new_offset,
			'next_page_exists' => $next_page_exists,
			'products_left'    => $products_left
		];
	}

	/**
	 * Если цвет задан, то в зависимости от настройки
	 *      - либо фильтруем по полу,
	 *      - либо упорядочиваем по нему (для семейных коллекций)
	 * Если цвет не задан, то фильтруем по полу. Вариантов будет много, поэтому выберем случайные.
	 * Ничего в выборку не добавляем. Если результатов меньше чем $limit - так и оставляем
	 */
	public static function getRelatedProducts( &$exclude_product_ids, $color, $gender, $limit, &$cache ) {
		ChB_Common::my_log( 'getRelatedProducts: c=' . $color . ' g=' . $gender . ' l=' . $limit . ' excl=' . implode( '#', $exclude_product_ids ) );
		$timepoint = hrtime( true );

		if ( ! empty( $color ) ) {
			$tax_query[] = [
				'taxonomy' => ChB_Common::PA_ATTR_COLOR_KEY,
				'terms'    => $color,
				'field'    => 'name'
			];
		}

		$query_pars = self::getProductQueryArgs( [
			'orderby' => 'category',
			'order'   => 'DESC',
			'return'  => 'ids',
			'limit'   => - 1
		] );

		if ( ! empty( $tax_query ) ) {
			$query_pars['tax_query'] = $tax_query;
		}

		$products_ids        = wc_get_products( $query_pars );
		$all_products_ids    = [];
		$do_filter_by_gender = ! $color || ChB_Settings()->getParam( 'filter_by_gender_in_related' );
		$do_sort             = ! $do_filter_by_gender; //no filter => sort

		foreach ( $products_ids as $cur_product_id ) {
			if ( in_array( $cur_product_id, $exclude_product_ids ) ) {
				continue;
			}

			$key = 'pg' . $cur_product_id;
			if ( ! empty( $cache[ $key ] ) ) {
				$cur_gender = $cache[ $key ];
			} else {
				$cur_gender    = self::getProductGender( $cur_product_id );
				$cache[ $key ] = $cur_gender;
			}

			if ( ! $do_filter_by_gender ||
			     ( $cur_gender === $gender || $cur_gender == ChB_Common::GENDER_UNISEX ) ) {

				if ( $do_sort ) {
					if ( $gender == $cur_gender ) {
						$cur_gender_ord = - 1;
					} elseif ( $cur_gender === ChB_Common::GENDER_UNISEX ) {
						$cur_gender_ord = 0;
					} elseif ( $gender === ChB_Common::GENDER_MEN && $cur_gender === ChB_Common::GENDER_WOMEN ||
					           $gender === ChB_Common::GENDER_WOMEN && $cur_gender === ChB_Common::GENDER_MEN ) {
						$cur_gender_ord = 1;
					} else {
						$cur_gender_ord = 2;
					}
				} else {
					$cur_gender_ord = 0;
				}//если фильтруем по полу, то не упорядочиваем

				$all_products_ids[] = [ $cur_product_id, $cur_gender_ord ];
			}
		}

		$res = [];
		if ( $limit === - 1 ) {
			$limit = sizeof( $all_products_ids );
		}

		if ( $limit !== 0 && sizeof( $all_products_ids ) > 0 ) {
			if ( empty( $color ) ) {
				//Цвет не задан - слишком много элементов
				//Поэтому отбираем случайные $limit штук
				if ( $limit >= sizeof( $all_products_ids ) ) {
					$keys = range( 0, sizeof( $all_products_ids ) - 1 );
				} else {
					if ( $limit === 1 ) {
						$keys = [ array_rand( $all_products_ids, 1 ) ];
					} else {
						$keys = array_rand( $all_products_ids, $limit );
					}
				}
			} else {
				//Цвет задан - элементов мало. Просто упорядочиваем их
				usort( $all_products_ids, function ( $a, $b ) {
					return $a[1] - $b[1];
				} );
				$keys = range( 0, min( $limit, sizeof( $all_products_ids ) ) - 1 );
			}

			foreach ( $keys as $key ) {
				$res[] = $all_products_ids[ $key ][0];
			}
		}

		$eta = round( ( hrtime( true ) - $timepoint ) / 1e+6 );

		ChB_Common::my_log( 'getRelatedProducts res=' . implode( '#', $res ) . ' time=' . $eta );

		return $res;
	}

	public static function getProductGender( $product_id ) {
		$cat        = self::getCategoryTermByProductId( $product_id );
		$brand_name = self::getBrandNameByProductId( $product_id );
		if ( empty( $cat ) ) {
			return ChB_Common::GENDER_UNISEX;
		}

		return self::getCategoryGender( $cat, $brand_name );
	}

	public static function getCategoryGender( $cat_term, $brand_name ) {

		$cat_slug = $cat_term->slug;
		if ( strpos( $cat_slug, 'women' ) !== false ) {
			$gender = ChB_Common::GENDER_WOMEN;
		} elseif ( strpos( $cat_slug, 'girl' ) !== false ) {
			$gender = ChB_Common::GENDER_GIRL;
		} elseif ( strpos( $cat_slug, 'men' ) !== false ) {
			$gender = ChB_Common::GENDER_MEN;
		} elseif ( strpos( $cat_slug, 'boy' ) !== false ) {
			$gender = ChB_Common::GENDER_BOY;
		} else {
			$gender = get_term_meta( $cat_term->term_id, ChB_Common::CAT_ATTR_GENDER, true );

			//json for some cats
			if ( substr( trim( $gender ), 0, 1 ) == '{' ) {
				$gender_pars = json_decode( $gender, true );
				if ( ! empty( $brand_name ) && isset( $gender_pars[ $brand_name ] ) ) {
					$gender = $gender_pars[ $brand_name ];
				} elseif ( isset( $gender_pars['_OTHER_'] ) ) {
					$gender = $gender_pars['_OTHER_'];
				} else {
					$gender = 'unisex';
				}
			}

			if ( empty( $gender ) ) {
				$gender = ChB_Common::GENDER_UNISEX;
			} elseif ( $gender == 'men' ) {
				$gender = ChB_Common::GENDER_MEN;
			} elseif ( $gender == 'women' ) {
				$gender = ChB_Common::GENDER_WOMEN;
			} elseif ( $gender == 'unisex' ) {
				$gender = ChB_Common::GENDER_UNISEX;
			} elseif ( $gender == 'kid' ) {
				$gender = ChB_Common::GENDER_KID;
			} elseif ( $gender == 'boy' ) {
				$gender = ChB_Common::GENDER_BOY;
			} elseif ( $gender == 'girl' ) {
				$gender = ChB_Common::GENDER_GIRL;
			}
		}

		return $gender;
	}

	public static function userGender2ProductGender( $user_gender ) {
		if ( $user_gender === 'male' ) {
			return ChB_Common::GENDER_MEN;
		} elseif ( $user_gender === 'female' ) {
			return ChB_Common::GENDER_WOMEN;
		}

		return null;
	}

	public static function getProductName( $product ) {
		if ( ! ( $product instanceof \WC_Product ) ) {
			$product = wc_get_product( $product );
		}

		return apply_filters( 'wany_hook_translate_product_name', $product->get_name(), $product );
	}

	public static function getProductPermalink( $product ) {
		if ( ! ( $product instanceof \WC_Product ) ) {
			$product = wc_get_product( $product );
		}

		return ( $product ? $product->get_permalink() : null );
	}

	public static function openProduct( $product_id, ?ChB_User $user, ChB_Promo $promo, ?array $args = null ) {
		$wc_product = wc_get_product( $product_id );
		if ( ! self::productIsVisible( $wc_product ) ) {
			return false;
		}
		if ( $user ) {
			$user->markProductOpenedByUser( $product_id );
		}

		list( $image_url, $full_size_image_url ) = self::getProductImage( $wc_product, true, null, true );

		$product = [
			'id'                 => $product_id,
			'name'               => self::getProductName( $wc_product ),
			'image_url'          => $image_url,
			'gallery_image_urls' => self::getGalleryImages( $wc_product, true ),
			'sdesc'              => self::getProductDescription( $wc_product ),
			'SKU'                => $wc_product->get_sku(),
			'attn'               => self::getProductAttentionText( $wc_product ),
			'pa_on_buy_button'   => ChB_Catalogue::getProductAttrOnBuyButton( $wc_product ),
			'price_details'      => $promo->getPriceDetails( $wc_product )
		];

		if ( ! empty( $args['extended'] ) ) {
			$product['brand']                   = self::getBrandNameByProductId( $product_id );
			$product['cat_slug']                = self::getCatSlugByProductId( $product_id );
			$product['google_product_category'] = self::getGoogleProductCategory( $product_id );
			$product['fb_product_category']     = self::getFBProductCategory( $product_id );
			$product['full_size_image_url']     = $full_size_image_url;
			$product['name_original']           = $wc_product->get_name();
			$product['permalink']               = $wc_product->get_permalink();
		}
		if ( ! empty( $args['size2find'] ) ) {
			$product['size2find']        = ChB_Catalogue::findProductSize( $wc_product, $args['size2find'] );
			$product['availability'] = ChB_Catalogue::getProductAttrAvailableValues( $wc_product, [ ChB_Common::PA_ATTR_SIZE_KEY ] );
		} elseif ( ! empty( ChB_Settings()->getParam( 'product_view_settings' )['element']['show_availability'] ) ) {
			$product['availability'] = ChB_Catalogue::getProductAttrAvailableValues( $wc_product, ChB_Settings()->getParam( 'product_view_settings' )['element']['show_availability'] );
		}

		$txt = self::getAttrsText( $wc_product, 'element', chr( 10 ) );
		if ( ! empty( $txt ) ) {
			$product['attrs_txt'] = $txt;
		}

		return $product;
	}

	public static function getProductAttentionText( \WC_Product $wc_product ) {
		foreach ( $wc_product->get_category_ids() as $cat_id ) {
			$text = get_term_meta( $cat_id, ChB_Common::CAT_ATTR_PROD_ATTENTION, true );
			if ( ! empty( $text ) ) {
				return ChB_Lang::translate( $text );
			}
		}

		return null;
	}

	public static function getAttrsText( $wc_product, $view, $delim ) {
		$lines = [];
		if ( ! empty( ChB_Settings()->getParam( 'product_view_settings' )[ $view ]['attrs'] ) ) {
			foreach ( ChB_Settings()->getParam( 'product_view_settings' )[ $view ]['attrs'] as $attr ) {
				$lines[] = $attr['icon'] . ' ' . $wc_product->get_attribute( $attr['name'] );
			}
		}
		if ( ! empty( $lines ) ) {
			return implode( $delim, $lines );
		} else {
			return null;
		}
	}

	public static function getCatSlugByProductId( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( sizeof( $terms ) == 0 ) {
			return false;
		}

		return $terms[0]->slug;
	}

	public static function getCatIdBySlug( $cat_slug ) {
		$term = self::getCatBySlug( $cat_slug );
		if ( empty( $term ) ) {
			return null;
		}

		return $term->term_id;
	}

	public static function getCatBySlug( $cat_slug ) {
		$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
		if ( empty( $term ) ) {
			return null;
		}

		if ( is_array( $term ) ) {
			return $term[0];
		} else {
			return $term;
		}
	}

	public static function getShopeeProductCategory( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		foreach ( $terms as $term ) {
			$shopee_product_category = get_term_meta( $term->term_id, ChB_Common::CAT_ATTR_SHOPEE_PRODUCT_CATEGORY, true );
			if ( ! empty( $shopee_product_category ) ) {
				return $shopee_product_category;
			}
		}

		return null;
	}

	public static function getGoogleProductCategory( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		foreach ( $terms as $term ) {
			$google_product_category = get_term_meta( $term->term_id, ChB_Common::CAT_ATTR_GOOGLE_PRODUCT_CATEGORY, true );
			if ( ! empty( $google_product_category ) ) {
				return $google_product_category;
			}
		}

		return null;
	}

	public static function getFBProductCategory( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		foreach ( $terms as $term ) {
			$google_product_category = get_term_meta( $term->term_id, ChB_Common::CAT_ATTR_FB_PRODUCT_CATEGORY, true );
			if ( ! empty( $google_product_category ) ) {
				return $google_product_category;
			}
		}

		return null;
	}

	public static function getSeasonByProductId( $product_id ) {
		$terms = wp_get_post_terms( $product_id, ChB_Common::PA_ATTR_SEASON_KEY );

		return ( ! is_wp_error( $terms ) && isset( $terms[0]->name ) ? $terms[0]->name : '' );
	}

	public static function getBrandNameByProductId( $product_id ) {
		$terms = wp_get_post_terms( $product_id, ChB_Common::PA_ATTR_BRAND_KEY );

		return ( ! is_wp_error( $terms ) && isset( $terms[0]->name ) ? $terms[0]->name : '' );
	}

	public static function getBrandTermByProductId( $product_id ) {
		$terms = wp_get_post_terms( $product_id, ChB_Common::PA_ATTR_BRAND_KEY );

		return ( ! is_wp_error( $terms ) && isset( $terms[0] ) ? $terms[0] : '' );
	}

	public static function getBrandTermBySlug( $brand_slug ) {
		$term = get_term_by( 'slug', $brand_slug, ChB_Common::PA_ATTR_BRAND_KEY );

		return ( ! is_wp_error( $term ) && isset( $term ) ? $term : '' );
	}

	public static function getCategoryTermByProductId( $product_id ) {
		$terms = wp_get_post_terms( $product_id, 'product_cat' );

		return ( ! is_wp_error( $terms ) && isset( $terms[0] ) ? $terms[0] : '' );
	}

	public static function getCatsSlugsByProductId( $product_id ) {
		$terms = wp_get_post_terms( $product_id, 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			return [];
		}
		$res = [];
		foreach ( $terms as $term ) {
			$res[] = $term->slug;
		}

		return $res;
	}

	public static function getColorByProductId( $product_id ) {
		$terms = get_the_terms( $product_id, ChB_Common::PA_ATTR_COLOR_KEY );

		return ( ! is_wp_error( $terms ) && isset( $terms[0]->name ) ? $terms[0]->name : '' );
	}

	public static function getProductIdsByParameters( $category, $seasons, $brands, $limit, $stock, $only_published ) {
		if ( $seasons ) {
			$tax_query[] = [
				'taxonomy' => ChB_Common::PA_ATTR_SEASON_KEY,
				'terms'    => $seasons,
				'field'    => 'slug'
			];
			if ( is_array( $seasons ) ) {
				$tax_query['operator'] = 'IN';
			}
		}
		if ( $brands ) {
			$tax_query[] = [
				'taxonomy' => ChB_Common::PA_ATTR_BRAND_KEY,
				'terms'    => $brands,
				'field'    => 'slug'
			];
			if ( is_array( $brands ) ) {
				$tax_query['operator'] = 'IN';
			}
		}

		$query_pars = self::getProductQueryArgs( [
			'return'  => 'ids',
			'orderby' => 'date',
			'order'   => 'DESC',
		] );

		if ( ! $only_published ) {
			unset( $query_pars['status'] );
			unset( $query_pars['visibility'] );
		}

		if ( $stock === 'all' ) {
			unset( $query_pars['stock_status'] );
		} else {
			$query_pars['stock_status'] = $stock;
		}

		if ( $category ) {
			$query_pars['category'] = $category;
		}
		if ( ! empty( $tax_query ) ) {
			$query_pars['tax_query'] = $tax_query;
		}
		if ( $limit ) {
			$query_pars['limit'] = $limit;
		} else {
			$query_pars['limit'] = - 1;
		}

		return wc_get_products( $query_pars );
	}

	public static function getProductDescription( \WC_Product $wc_product, $shorten = false ) {

		if ( ! ( $desc = apply_filters( 'wany_hook_get_product_description', '', $wc_product, $shorten ) ) ) {

			$desc = get_post_field( 'post_content', $wc_product->get_id() );

			return self::prepareProductDescription( $desc, $shorten );
		}

		return $desc;
	}

	public static function prepareProductDescription( $desc, $shorten ) {
		if ( $shorten && strlen( $desc ) > 150 ) {
			$desc = mb_substr( $desc, 0, 150 );
		}

		$desc =
			str_replace( "\r\n\r\n", "\r\n",
				strip_tags(
					html_entity_decode(
						$desc ) ) );

		if ( $shorten && strlen( $desc ) > 120 ) {
			$desc = mb_substr( $desc, 0, 120 ) . '...';
		} elseif ( strlen( $desc ) > 1000 ) {
			$desc = mb_substr( $desc, 0, 990 ) . '...';
		}

		return $desc;
	}

	public static function getProductWCAdminLink( $product_id ) {
		return 'https://' . ChB_Settings()->getDomainPath() . '/wp-admin/post.php?post=' . $product_id . '&action=edit';
	}

	public static function getProductNameById( $product_id ) {
		return get_the_title( $product_id );
	}

	public static function calcPopularProducts() {

		$user_ids = get_users( [ 'fields' => 'ID', 'limit' => - 1 ] );

		$alive_products = self::getAliveProductIds();

		$products = [];
		foreach ( $user_ids as $user_id ) {
			$product_ids_str = get_user_meta( $user_id, ChB_User::USER_ATTR_OPENED_PRODUCTS, true );
			if ( empty( $product_ids_str ) ) {
				continue;
			}

			$product_ids = explode( '#', $product_ids_str );
			foreach ( $product_ids as $product_id ) {
				if ( in_array( $product_id, $alive_products ) ) {
					$gender = self::getProductGender( $product_id );
					if ( ! isset( $products[ $product_id ] ) ) {
						$products[ $product_id ] = [ 'c' => 1, 'g' => $gender ];
					} else {
						$products[ $product_id ]['c'] ++;
					}
				}
			}
		}

		return $products;
	}

	public static function getSomePopularProducts( int $n, $product_gender, $allow_recalc ) {
		return ChB_Common::getRandomWeightedSubArray_Keys( ChB_Settings()->getPopularProducts( $allow_recalc ), $n, 'c', [ 'g' => $product_gender ] );
	}

	public static function getAliveProductIds() {
		return wc_get_products( self::getProductQueryArgs( [ 'limit' => - 1, 'return' => 'ids' ] ) );
	}

	public static function getSlugsByIds( $cat_ids ) {
		$res = [];
		foreach ( $cat_ids as $cat_id ) {
			$cat   = get_term( $cat_id );
			$res[] = $cat->slug;
		}

		return $res;
	}

	public static function getSimilarProducts4TryOn( $product_id = null, $cat_slug = null ) {

		$args = self::getProductQueryArgs(
			[
				'return'                            => 'ids',
				'limit'                             => - 1,
				ChB_Constants::PROD_ATTR_HAS_TRY_ON => 1
			] );

		if ( ! empty( $product_id ) ) {
			$args['exclude'] = [ $product_id ];
		}

		if ( ! empty( $cat_slug ) ) {
			$args['category'] = ( is_array( $cat_slug ) ? $cat_slug : [ $cat_slug ] );
		}

		$res = wc_get_products( $args );
		if ( empty( $product_id ) ) {
			return $res;
		}

		return array_merge( [ $product_id ], $res );
	}

	public static function getRecommendedProducts( ChB_User $user, $limit = 0 ) {
		//should we check if products were already shown to user
		$do_check_and_mark = ( $limit > 0 );

		//Products opened by user
		$product_ids = $user->getProductsOpenedByUser();
		ChB_Common::my_log( 'opened by user: ' . implode( '#', $product_ids ) );

		//Global followup products
		$products4followup = ChB_Settings()->getParam( 'products4followup' );
		$product_ids       = $product_ids ? array_merge( $product_ids, $products4followup ) : $products4followup;
		ChB_Common::my_log( 'global followup: ' . implode( '.', $products4followup ) );

		//leaving only alive prods
		$alive_products = self::getAliveProductIds();
		$product_ids    = array_intersect( $product_ids, $alive_products );

		//Adding popular products
		$num              = $limit > 0 ? $limit * 2 : 40;
		$product_gender   = ChB_Catalogue::userGender2ProductGender( $user->getGender() );
		$popular_products = self::getSomePopularProducts( $num, $product_gender, true );
		$product_ids      = empty( $product_ids ) ? $popular_products : array_merge( $product_ids, $popular_products );
		ChB_Common::my_log( 'popular: ' . implode( '#', $popular_products ) );

		//Excluding products already ordered by user
		$exclude_ordered = ChB_Order::getProductsOrderedByUser( $user );
		$product_ids     = array_diff( $product_ids, $exclude_ordered );
		ChB_Common::my_log( 'excluding ordered: ' . implode( '#', $exclude_ordered ) );

		if ( $do_check_and_mark ) {
			$prev_marked_product_ids = $user->getRemindedProductsOpenedByUser();
			$product_ids             = array_diff( $product_ids, $prev_marked_product_ids );
			ChB_Common::my_log( 'excluding reminded opened by user: ' . implode( '#', $prev_marked_product_ids ) );
		}

		if ( $limit > 0 ) {
			if ( sizeof( $product_ids ) < $limit ) {
				ChB_Common::my_log( 'getRecommendedProducts: not enough products, adding popular to ' . implode( '#', $product_ids ) );
				$product_ids = array_merge( $product_ids, self::getSomePopularProducts( $limit - sizeof( $product_ids ), $product_gender, true ) );
			}
			$product_ids = array_slice( $product_ids, 0, $limit );
		}

		if ( $do_check_and_mark ) {
			$user->markRemindedProductsOpenedByUser( array_merge( $product_ids, $prev_marked_product_ids ) );
		}

		return $product_ids;
	}

	/**
	 * @param $parent_cat_id
	 * @param $args
	 * @param $level
	 *
	 * @return array|null[]|null
	 *
	 * returns [$all_terminal_subcategories, $level0_subcategories_with_their_terminal_subcategories]
	 */
	private static function getAllTerminalSubCatsRecursive( $parent_cat_id, $args, $level = 0 ) {

		if ( $level > 10 ) {
			return null;
		}

		$term_query_args = [ 'taxonomy' => 'product_cat', 'hide_empty' => true ];

		if ( ! empty( $args['cat_slugs'] ) ) {
			// this can be non-empty only on level 0
			$term_query_args['slug'] = $args['cat_slugs'];

		} else {
			$term_query_args['parent'] = $parent_cat_id;

			if ( ! empty( $args['ca_filter'] ) ) {
				$meta_query = [];
				foreach ( $args['ca_filter'] as $key => $value ) {
					$meta_query[] = [ 'key' => $key, 'value' => $value, 'compare' => '=' ];
				}
				$term_query_args['meta_query'] = $meta_query;
			}
		}

		$cats = get_terms( $term_query_args );

		//current cat is terminal
		if ( empty( $cats ) ) {
			return ( $level == 0 ? [ null, null ] : null );
		}

		//order (for explicitly set cat_slugs on level 0)
		if ( ! empty( $args['cat_slugs'] ) ) {
			$cats_ord = [];
			foreach ( $args['cat_slugs'] as $cat_slug_ord ) {
				foreach ( $cats as $cat ) {
					if ( $cat->slug === $cat_slug_ord ) {
						$cats_ord[] = $cat;
						break;
					}
				}
			}
			$cats = &$cats_ord;
			unset ( $args['cat_slugs'] );
		}

		$cat_ids_slugs = [];
		$subcats       = [];
		foreach ( $cats as $cat ) {
			$cur_cat_ids_slugs = self::getAllTerminalSubCatsRecursive( $cat->term_id, $args, $level + 1 );
			if ( empty( $cur_cat_ids_slugs ) ) {
				$cat_ids_slugs[ $cat->term_id ] = $cat->slug;
			} else {
				$cat_ids_slugs = $cat_ids_slugs + $cur_cat_ids_slugs;
			}

			if ( $level == 0 ) {
				$subcats[] = [ 'cat_id' => $cat->term_id, 'cat_slug' => $cat->slug, 'subcats' => $cur_cat_ids_slugs ];
			}
		}

		if ( $level == 0 ) {
			return [ $cat_ids_slugs, $subcats ];
		} else {
			return $cat_ids_slugs;
		}
	}
}
