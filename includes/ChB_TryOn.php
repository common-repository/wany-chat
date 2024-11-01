<?php


namespace ChatBot;


class ChB_TryOn {
	public static function productHasTryOn( $product_id ) {
		return ! empty( get_post_meta( $product_id, ChB_Constants::PROD_ATTR_HAS_TRY_ON, true ) );
	}

	public static function catHasTryOn( $cat_id_or_slug, $is_slug = true ) {
		if ( $is_slug ) {
			$cat_id_or_slug = get_term_by( 'slug', $cat_id_or_slug, 'product_cat' )->term_id;
		}

		return ! empty( get_term_meta( $cat_id_or_slug, ChB_Common::CAT_ATTR_HAS_TRY_ON, true ) );
	}

	public static function getDefaultCatSlug4TryOn( $user_gender ) {
		if ( ! empty( $user_gender ) && ! empty( ChB_Settings()->getTryOn( 'default_cat_slug' )[ $user_gender ] ) ) {
			return ChB_Settings()->getTryOn( 'default_cat_slug' )[ $user_gender ];
		}
		if ( ! empty( ChB_Settings()->getTryOn( 'default_cat_slug' )['_DEFAULT_'] ) ) {
			return ChB_Settings()->getTryOn( 'default_cat_slug' )['_DEFAULT_'];
		}

		return null;
	}

	public static function getProductTryOnImagePath( $product_id ) {
		if ( empty( ChB_Settings()->getTryOn( 'product_images_dir' ) ) ) {
			return false;
		}
		$path = ChB_Settings()->getTryOn( 'product_images_dir' ) . $product_id . '.png';

		return ( file_exists( $path ) ? $path : false );
	}

	public static function getUserPhoto4TryOn( ChB_User $user, $use_uploaded_pics = true, $use_profile_pic = false, $use_only_cache = true ) {
		if ( $use_uploaded_pics ) {
			$try_on_options = self::getUserTryOnOptions( $user );
			if ( ! empty( $try_on_options['pics'] ) ) {
				foreach ( $try_on_options['pics'] as $key => $filename ) {
					$path = ChB_Settings()->getTryOn( 'user_pics_dir' ) . $filename;
					list( $res ) = ChB_DressUp::checkUserImg4Hat( $path, $use_only_cache );
					if ( $res ) {
						return $path;
					}
				}
			}
		}

		if ( $use_profile_pic ) {
			//fb api
			$path = $user->getUserProfilePicFromFB();
			if ( $path ) {
				list( $res, $err_code ) = ChB_DressUp::checkUserImg4Hat( $path, $use_only_cache );
				if ( $res ) {
					return $path;
				}
				ChB_Common::my_log( 'getUserPhoto4TryOn ' . $user->fb_user_id . ' Cannot use FB API profile pic. Error code=' . $err_code );
			}

			//mc
			$path = $user->getUserProfilePicFromMC();
			if ( ! $path ) {
				return false;
			}

			list( $res ) = ChB_DressUp::checkUserImg4Hat( $path, $use_only_cache );

			return ( $res ? $path : false );
		}

		return false;
	}

	public static function saveUserPhoto4TryOn( ChB_User $user, $tmp_path ) {
		$img_key  = ChB_Common::my_rand_string( 8 );
		$filename = $img_key . '.' . pathinfo( $tmp_path, PATHINFO_EXTENSION );
		$new_path = ChB_Settings()->getTryOn( 'user_pics_dir' ) . $filename;
		copy( $tmp_path, $new_path );

		//creating medium size
		$medium_size = 720;
		$img         = imagecreatefromjpeg( $new_path );
		list( $img, $shrinked ) = ChB_Common::shrinkImage( $img, $medium_size );
		if ( $shrinked ) {
			imagejpeg( $img, $new_path, 100 );
		}
		imagedestroy( $img );

		list( $res, $err_code ) = ChB_DressUp::checkUserImg4Hat( $new_path, false );
		if ( $res ) {
			$try_on_options = self::getUserTryOnOptions( $user );
			if ( empty( $try_on_options['pics'] ) ) {
				$try_on_options['pics'][ $img_key ] = $filename;
			} else {
				$try_on_options['pics'] = [ $img_key => $filename ] + $try_on_options['pics'];
			}

			self::updateUserTryOnOptions( $user, $try_on_options );
		} else {
			unlink( $new_path );
		}

		return [ $res, $err_code ];
	}

	public static function deleteUserPhoto4TryOn( ChB_User $user, $img_key ) {
		$try_on_options = self::getUserTryOnOptions( $user );
		if ( isset( $try_on_options['pics'][ $img_key ] ) ) {
			@unlink( $try_on_options['pics'][ $img_key ] );
			unset( $try_on_options['pics'][ $img_key ] );
			self::updateUserTryOnOptions( $user, $try_on_options );
		}
	}

	public static function deleteAllUserPhotos4TryOn( ChB_User $user ) {
		$try_on_options = self::getUserTryOnOptions( $user );
		if ( ! empty( $try_on_options['pics'] ) ) {
			foreach ( $try_on_options['pics'] as $img_path ) {
				@unlink( $img_path );
			}
			unset( $try_on_options['pics'] );
			self::updateUserTryOnOptions( $user, $try_on_options );
		}
	}

	public static function getCurrentTryOnProduct( ChB_User $user ) {
		$try_on_options = self::getUserTryOnOptions( $user );

		return [
			empty( $try_on_options['product_id'] ) ? null : $try_on_options['product_id'],
			empty( $try_on_options['cat_slug'] ) ? null : $try_on_options['cat_slug']
		];
	}

	public static function setCurrentTryOnProduct( ChB_User $user, $product_id, $cat_slug ) {
		$try_on_options               = self::getUserTryOnOptions( $user );
		$try_on_options['product_id'] = $product_id;
		$try_on_options['cat_slug']   = $cat_slug;
		self::updateUserTryOnOptions( $user, $try_on_options );
	}

	public static function putOnHat( ChB_User $user, $product_id ) {
		if ( ChB_Settings()->getTryOn( 'is_try_on_demo' ) ) {
			$path = ChB_Settings()->try_on_demo_path . 'demo' . $product_id . '.jpg';
			if ( file_exists( $path ) ) {
				return ChB_Settings()->try_on_demo_url . 'demo' . $product_id . '.jpg';
			}
		}

		$user_img_path = self::getUserPhoto4TryOn( $user, true, true );

		if ( empty( $user_img_path ) ) {
			return false;
		}

		return ChB_DressUp::putOnHat2File( $user_img_path, $product_id );
	}


	public static function refreshTryOnProductsAttr() {
		if ( empty( ChB_Settings()->getTryOn( 'product_images_dir' ) ) ) {
			return;
		}

		$path                    = ChB_Settings()->getTryOn( 'product_images_dir' );
		$files                   = scandir( $path );
		$product_ids_with_try_on = [];
		foreach ( $files as $file ) {
			if ( $file === '.' || $file === '..' || is_dir( $path . $file ) ) {
				continue;
			}

			$product_id = str_replace( '.png', '', $file );
			if ( ! is_numeric( $product_id ) ) {
				continue;
			}

			$product_ids_with_try_on[] = $product_id;
		}

		$all_product_ids     = wc_get_products( [ 'return' => 'ids', 'limit' => - 1 ] );
		$cat_ids_with_try_on = [];
		foreach ( $all_product_ids as $product_id ) {
			$has_try_on = in_array( $product_id, $product_ids_with_try_on );
			update_post_meta( $product_id, ChB_Constants::PROD_ATTR_HAS_TRY_ON, ( $has_try_on ? 1 : 0 ) );
			if ( $has_try_on ) {
				$cat_ids_with_try_on = array_unique( array_merge( wc_get_product( $product_id )->get_category_ids(), $cat_ids_with_try_on ) );
			}
		}

		var_dump( $cat_ids_with_try_on );

		foreach ( $cat_ids_with_try_on as $cat_id ) {
			update_term_meta( $cat_id, ChB_Common::CAT_ATTR_HAS_TRY_ON, 1 );
		}

	}

	public static function getUserTryOnOptions( ChB_User $user ) {
		$try_on_options_attr = get_user_meta( $user->wp_user->ID, ChB_User::USER_ATTR_TRY_ON_OPTIONS, true );
		if ( empty( $try_on_options_attr ) ) {
			return false;
		}

		return json_decode( $try_on_options_attr, true );
	}

	public static function updateUserTryOnOptions( ChB_User $user, $try_on_options ) {
		return update_user_meta( $user->wp_user->ID, ChB_User::USER_ATTR_TRY_ON_OPTIONS, json_encode( $try_on_options ) );
	}

	public static function getUserTryOnState( ChB_User $user ) {
		return $user->getUserActionState( 'try_on_prcss' );
	}

	public static function setUserTryOnState( ChB_User $user, $ttl = ChB_Common::SECONDS_1H ) {
		$user->setUserActionState( 'try_on_prcss', $ttl );
	}

	public static function unsetUserTryOnState( ChB_User $user ) {

		$user->unsetUserActionState( 'try_on_prcss' );
	}
}
