<?php

namespace ChatBot;


class ChB_Common {
	const DUPLICATE_MESSAGES_INTERVAL = 4;
	const DUPLICATE_MESSAGES_PREFIX = 'RRBOT_DUPL_';
	const LONG_REQUEST = 7; //seconds

	const SECONDS_YEAR = 3600 * 24 * 365;
	const SECONDS_12H = 3600 * 12;
	const SECONDS_1H = 3600;

	const REFRESH_IMG_PATHS_INTERVAL = 240 * 60 * 60; //10 days
	const IMG_PATHS_KEY = 'IMG_PATHS';

	const NO_SIZES = 'NO_SIZES';
	const PA_ATTR_SIZE_KEY = 'pa_size';
	const PA_ATTR_BRAND_KEY = 'pa_brand';
	const PA_ATTR_SEASON_KEY = 'pa_season';
	const PA_ATTR_COLOR_KEY = 'pa_color';

	const CAT_ATTR_HAS_TRY_ON = 'cat_has_try_on';

	const ADDR_MIN_LEN = 5;

	const ORDER_ATTR_RRBOT_INFO = 'rrbot_order_info';

	const FRAME_COEFF = 1.3;
	const FRAME_PREFIX = 'fr_';
	const IMG_MEDIUM_SIZE = [ 768, 0 ];
	const STANDARD_IMG_WIDTH = 500;

	const EMPTY_TEXT = '__EMP__';

	const KVS_PREFIX_TEMP = 'TMP__';

	const SHIPPING_FREE = 'FREE';
	const SHIPPING_MANUAL = 'MANUAL';
	const SHIPPING_FLAT = 'FLAT';
	const SHIPPING_WOO = 'WOO';

	const MANAGER_NOTIFICATION_PAUSE_MINUTES = 30;

	const WHITE_COLOR = 16777215;
	const MAX_UPLOAD_IMAGE_PX = 1200;

	const CAT_ATTR_NAME_TRANSLATION = 'rrbot_cat_name_translation';
	const CAT_ATTR_GENDER = 'rrbot_cat_gender';
	const CAT_ATTR_SHOPEE_PRODUCT_CATEGORY = 'shopee_product_category';
	const CAT_ATTR_GOOGLE_PRODUCT_CATEGORY = 'google_product_category';
	const CAT_ATTR_FB_PRODUCT_CATEGORY = 'fb_product_category';
	const CAT_ATTR_SIZES = 'rrbot_cat_sizes';
	const CAT_ATTR_SIZE_CHARTS = 'rrbot_cat_size_charts';
	const CAT_ATTR_PROD_ATTENTION = 'rrbot_attr_prod_attn';
	const CAT_ATTR_CAT_IMAGE_ID = 'rrbot_attr_cat_image_id';

	const SIZE_ATTR_SYNONYMS = 'rrbot_attr_syn';

	const PRODUCT_ATTR_TEXT4AUTO_IMG = 'rrbot_text4auto_img';
	const PRODUCT_ATTR_COLOR4AUTO_IMG = 'rrbot_color4auto_img';

	const GENDER_MEN = 'm';
	const GENDER_WOMEN = 'w';
	const GENDER_UNISEX = 'u';
	const GENDER_KID = 'k';
	const GENDER_BOY = 'b';
	const GENDER_GIRL = 'g';

	const EXT_USER = '__EXT_USER__';

	const VIEW_SITE_PROD_BTN = 'sitebtn';
	const VIEW_TTH_ON_PAYMENT = 'tth_payment';
	const VIEW_SIZES = 'sizes';
	const VIEW_PRODUCT_CARD2WEB = 'pv_card2web';
	const VIEW_TRY_ON = 'try_on';
	const VIEW_TRY_ON_AGAIN = 'try_on_again';
	const VIEW_EXT = 'ext';
	const VIEW_CART_EDIT_PLACING_ORDER = 'edit_order';
	const VIEW_CART_EDIT_CHECK_ADDR = 'edit_check_addr';
	const VIEW_CHECK_ADDR = 'check_addr';
	const VIEW_CART_FULL_DETAILS = 'full';
	const VIEW_PLACING_ORDER = 'confirm_order';

	const VIEW_PHONE_INPUT = 'phone_input';
	const VIEW_EMAIL_INPUT = 'email_input';
	const VIEW_TRANSLATE_VAL = 'translate_val';
	const VIEW_TRANSLATE_HOOK = 'translate_hook';

	const ADDR_PART_FIRST_NAME = 'first_name';
	const ADDR_PART_LAST_NAME = 'last_name';
	const ADDR_PART_PHONE = 'phone';
	const ADDR_PART_EMAIL = 'email';
	const ADDR_PART_COUNTRY = 'country';
	const ADDR_PART_STATE = 'state';
	const ADDR_PART_CITY = 'city';
	const ADDR_PART_POSTCODE = 'postcode';
	const ADDR_PART_ADDRESS_LINE = 'address_line_1';

	const ADDR_GROUP_NAME = 'name';
	const ADDR_GROUP_ADDRESS = 'address';

	const MANYCHAT_WIDGET_SITE_PRODUCT_BUTTON = 'RRB Site Product Page Button';

	public static function my_log( $message, $useDump = false, $prefix = null, $go_echo = false ) {

		if ( $useDump ) {
			$message = var_export( $message, true );
		}
		if ( $prefix != null ) {
			$message = $prefix . '  ' . $message;
		}

		if ( class_exists( '\ChatBot\ChB_Settings' ) ) {
			$timezone = ChB_Settings()->timezone;
			$log_path = ChB_Settings()->log_path;
		} else {
			if ( defined( 'WY_LOG_PATH' ) && defined( 'WY_TIMEZONE' ) ) {
				$timezone = new \DateTimeZone( WY_TIMEZONE );
				$log_path = WY_LOG_PATH;
			} else {
				return;
			}
		}

		$now     = new \DateTime( 'now', $timezone );
		$message = $now->format( DATE_RFC2822 ) . ': ' . $message . "\n";


		if ( ! empty( $log_path ) ) {
			error_log( $message, 3, $log_path );
		}

		if ( $go_echo ) {
			echo esc_html( $message );
		}
	}

	public static function my_debug_log( $message, $useDump = false, $prefix = null, $go_echo = false ) {
		if ( ChB_Debug::isDebug() ) {
			ChB_Common::my_log( $message, $useDump, $prefix, $go_echo );
		}
	}

	public static function milli() {
		return round( microtime( true ) * 1000 );
	}

	public static function my_GUID() {
		return sprintf( '%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 16384, 20479 ), mt_rand( 32768, 49151 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) );
	}

	public static function my_rand_string( $length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ) {
		$str   = '';
		$count = ( is_array( $charset ) ? count( $charset ) : strlen( $charset ) ) - 1;
		while ( $length -- ) {
			$str .= $charset[ mt_rand( 0, $count ) ];
		}

		return $str;
	}

	public static function isMobile() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}
		$useragent = $_SERVER['HTTP_USER_AGENT'];

		return preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $useragent, 0, 4 ) );
	}

	public static function uploadImage( $url_or_path, $post_id ) {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$attachmentId = '';
		if ( ! empty( $url_or_path ) ) {
			$ext    = '.jpg';
			$extpos = strrpos( $url_or_path, '.', - 1 );
			if ( $extpos !== false ) {
				if ( substr( $url_or_path, $extpos ) === '.png' ) {
					$ext = '.png';
				}
			}

			if ( strpos( $url_or_path, 'http://' ) === 0 ) {
				$file['tmp_name'] = self::downloadURL( $url_or_path );
			} else {
				$tmp_name = tempnam( get_temp_dir(), 'uploadImage' );
				if ( ! copy( $url_or_path, $tmp_name ) ) {
					ChB_Common::my_log( 'cannot copy ' . $url_or_path . ' to ' . $tmp_name );
					@unlink( $tmp_name );

					return false;
				}
				$file['tmp_name'] = $tmp_name;
			}

			$file['name'] = $post_id . '_' . self::my_GUID() . $ext;

			if ( is_wp_error( $file['tmp_name'] ) ) {
				@unlink( $file['tmp_name'] );
				ChB_Common::my_log( 'wp_error ' . $url_or_path );
				ChB_Common::my_log( $file['tmp_name']->get_error_messages(), true );

				return false;
			} else {
				//Делаем картинку квадратной
				if ( ChB_Common::squareImage( $file['tmp_name'], $file['tmp_name'] ) === false ) {
					ChB_Common::my_log( 'cannot square  ' . $file['tmp_name'] );
					@unlink( $file['tmp_name'] );

					return false;
				}

				$attachmentId = media_handle_sideload( $file, $post_id );
				if ( is_wp_error( $attachmentId ) ) {
					@unlink( $file['tmp_name'] );
					ChB_Common::my_log( 'wp_error media_handle_sideload' . $url_or_path );
					ChB_Common::my_log( $attachmentId->get_error_messages(), true );

					return false;
				}
			}
		}

		return $attachmentId;
	}

	public static function shrinkImage( &$img, $max_wh ) {
		$w = imagesx( $img );
		$h = imagesy( $img );

		if ( $w >= $h && $w > $max_wh ) {
			$new_w = $max_wh;
			$new_h = $h * $max_wh / $w;
		} elseif ( $h >= $w && $h > $max_wh ) {
			$new_w = $w * $max_wh / $h;
			$new_h = $max_wh;
		}

		if ( ! empty( $new_w ) && ! empty( $new_h ) ) {
			$new_img = imagecreatetruecolor( $new_w, $new_h );
			imagecopyresampled( $new_img, $img, 0, 0, 0, 0, $new_w, $new_h, $w, $h );
			imagedestroy( $img );

			return [ $new_img, true ];
		}

		return [ $img, false ];
	}

	public static function getMidsizeImg( $path, $return_img = false, $max_wh = 720 ) {

		$path_parts = pathinfo( $path );
		$file_ext   = $path_parts['extension'];
		if ( $file_ext === 'png' || $file_ext === 'PNG' ) {
			$img = imagecreatefrompng( $path );
		} else {
			$img = imagecreatefromjpeg( $path );
		}

		if ( empty( $img ) ) {
			return [ null, null ];
		}

		list( $img, $res ) = self::shrinkImage( $img, $max_wh );
		if ( $res ) {
			$path = $path_parts['dirname'] . '/' . $path_parts['filename'] . 'x' . $max_wh . '.' . $file_ext;
			if ( $file_ext === 'png' || $file_ext === 'PNG' ) {
				imagepng( $img, $path );
			} else {
				imagejpeg( $img, $path, 100 );
			}
		}

		if ( $return_img ) {
			return [ $path, $img ];
		} else {
			imagedestroy( $img );

			return [ $path, null ];
		}
	}

	public static function getWPFilesystem() {
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	public static function downloadURL( $url, $path_to = null ) {
		$wp_filesystem = ChB_Common::getWPFilesystem();
		if ( ! $wp_filesystem ) {
			return false;
		}

		set_time_limit( 0 );

		if ( ! $path_to ) {
			$url_path = parse_url( $url, PHP_URL_PATH );
			if ( ! empty( $url_path ) ) {
				$extension = pathinfo( $url_path, PATHINFO_EXTENSION );
			}
			if ( empty( $extension ) ) {
				$extension = 'jpg';
			}
			$path_to = tempnam( get_temp_dir(), 'dnldurl' ) . '.' . $extension;
		}

		$url      = str_replace( " ", "%20", $url );
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendGet ERROR' );

			return false;
		}
		$res = $wp_filesystem->put_contents( $path_to, $response['body'] );

		return ( $res ? $path_to : false );
	}

	public static function getAttachmentMediumSizeUrl( $att_id, $text_over_image = null, $aspect4text = 'square' ) {

		$img  = null;
		$size = self::IMG_MEDIUM_SIZE;
		if ( ! empty( $att_id ) && ! is_wp_error( $att_id ) ) {

			//Накладываем текст на картинку
			if ( ! empty( $text_over_image ) ) {
				$file_name = self::getAttachmentFilename( $att_id, $size );
				if ( ! empty( $file_name ) ) {
					$img = self::putTextOverImage( $file_name, $text_over_image, $aspect4text );
				}
			}

			//Основной сценарий (картинка без текста)
			if ( empty( $img ) || is_wp_error( $img ) ) {
				$image = wp_get_attachment_image_src( $att_id, $size );
				try {
					if ( ! empty( $image[0] ) ) {
						$img = $image[0];
					} else {
						$img = wp_get_attachment_url( $att_id );
						if ( is_wp_error( $img ) ) {
							$img = null;
						}
					}
				} catch ( \Exception $e ) {
					ChB_Common::my_log( 'getAttachmentMediumSizeUrl: ' . $e->getMessage() );
				}
			}
		}

		if ( ! empty( $img ) && ! is_wp_error( $img ) ) {
			return $img;
		} else {
			return wc_placeholder_img_src();
		}
	}

	public static function getAttachmentFilename( $att_id, $size ) {

		if ( ! empty( $att_id ) && ! is_wp_error( $att_id ) ) {
			$info = image_get_intermediate_size( $att_id, $size );
			if ( empty( $info['file'] ) ) {
				$info = wp_get_attachment_metadata( $att_id );
			}

			if ( ! empty( $info['file'] ) ) {
				return $info['file'];
			}
		}

		return null;
	}

	public static function putTextOverImage( $file_name, $text_over_image, $aspect = 'square' ) {

		$img                     = null;
		$suffix                  = $text_over_image[0];
		$img_file_with_text      = str_replace( '.', '-' . $suffix . '.', $file_name );
		$img_file_with_text_path = ChB_Settings()->uploads_path . $img_file_with_text;
		if ( file_exists( $img_file_with_text_path ) ) {
			$img = ChB_Settings()->uploads_url . $img_file_with_text;
		} else {
			$type    = 'jpg';
			$src_img = imagecreatefromjpeg( ChB_Settings()->uploads_path . $file_name );
			if ( ! $src_img ) {
				$type    = 'png';
				$src_img = imagecreatefrompng( ChB_Settings()->uploads_path . $file_name );
			}
			if ( $src_img ) {
				$width  = imagesx( $src_img );
				$height = imagesy( $src_img );

				$new_width  = self::STANDARD_IMG_WIDTH;
				$new_height = round( $height * $new_width / $width );
				$dst_img    = imagecreatetruecolor( $new_width, $new_height );

				imagecopyresampled( $dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
				$color = imagecolorallocate( $src_img, 255, 0, 0 );

				$font_size = $text_over_image[2];
				$text      = $text_over_image[1];
				if ( $aspect == 'square' ) {
					$xy = [
						'x1' => $new_width * 0.1,
						'y1' => $new_height * 0.8,
						'x2' => $new_width * 0.1,
						'y2' => $new_height * 0.9
					];
				} else {
					$xy = [
						'x1' => $new_width * 0.1,
						'y1' => $new_height * 0.5,
						'x2' => $new_width * 0.1,
						'y2' => $new_height * 0.6
					];
				}

				imagettftext( $dst_img, $font_size, 0, $xy['x1'], $xy['y1'], $color, ChB_Settings()->getTypography( 'promo_font_path1' ), $text );
				if ( isset( $text_over_image[4] ) ) {
					$font_size = $text_over_image[4];
					$text      = $text_over_image[3];
					imagettftext( $dst_img, $font_size, 0, $xy['x2'], $xy['y2'], $color, ChB_Settings()->getTypography( 'promo_font_path2' ), $text );
				}

				imagealphablending( $dst_img, true );

				if ( $type === 'jpg' ) {
					if ( imagejpeg( $dst_img, $img_file_with_text_path, 100 ) ) {
						$img = ChB_Settings()->uploads_url . $img_file_with_text;
					}
				} elseif ( $type === 'png' ) {
					if ( imagepng( $dst_img, $img_file_with_text_path, 100 ) ) {
						$img = ChB_Settings()->uploads_url . $img_file_with_text;
					}
				}
			}
		}

		if ( empty( $img ) ) {
			$img = ChB_Settings()->uploads_url . $file_name;
		}

		return $img;
	}

	public static function getAttachmentFullSizeUrl( $att_id ) {

		try {
			if ( ! empty( $att_id ) && ! is_wp_error( $att_id ) ) {
				$image = wp_get_attachment_image_src( $att_id, 'full' );
				if ( ! empty( $image[0] ) ) {
					$img = $image[0];
				} else {
					$img = wp_get_attachment_url( $att_id );
					if ( is_wp_error( $img ) ) {
						$img = null;
					}
				}
			}
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'getAttachmentFullizeUrl: ' . $e->getMessage() );
		}

		if ( ! empty( $img ) && ! is_wp_error( $img ) ) {
			return $img;
		} else {
			return wc_placeholder_img_src();
		}
	}


	public static function scaled_image_path( $attachment_id, $size = 'thumbnail' ) {
		$file = get_attached_file( $attachment_id, true );
		if ( empty( $size ) || $size === 'full' ) {
			// for the original size get_attached_file is fine
			return realpath( $file );
		}
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return false; // the id is not referring to a media
		}
		$info = image_get_intermediate_size( $attachment_id, $size );
		if ( ! is_array( $info ) || ! isset( $info['file'] ) ) {
			return false; // probably a bad size argument
		}

		ChB_Common::my_log( $info, true );

		return realpath( str_replace( wp_basename( $file ), $info['file'], $file ) );
	}

	public static function cacheResponse( &$response, $ttl ) {
		$response_key = ChB_Settings()->salt . ChB_Common::KVS_PREFIX_TEMP . ChB_Common::my_rand_string( 10 );
		ChB_Settings()->kvs->setex( $response_key, $ttl, json_encode( $response ) );

		return $response_key;
	}

	public static function getResponseFromCache( $response_key ) {
		if ( ! empty( $response_key ) ) {
			$response = ChB_Settings()->kvs->get( $response_key );
			if ( ! empty( $response ) ) {
				return json_decode( $response, true );
			}
		}

		return null;
	}

	public static function arrayOfStrings( $v ) {
		if ( $v === '' ) {
			return [ '' ];
		}
		if ( $v === null || $v === false ) {
			return [];
		}

		if ( ! is_array( $v ) ) {
			$v = [ $v ];
		}

		foreach ( $v as $key => $val ) {
			if ( is_string( $val ) ) {
				continue;
			}
			$v[ $key ] = is_numeric( $val ) ? strval( $val ) : '';
		}

		return $v;
	}

	private static function _getAttachmentMediumSizeUrlWithFrame( $att_url ) {
		try {
			$ud              = wp_upload_dir();
			$upload_dir_path = $ud['path'];
			$upload_dir_url  = $ud['url'];

			$filename         = substr( $att_url, strrpos( $att_url, '/', - 1 ) + 1 );
			$filepath_w_frame = $upload_dir_path . '/' . self::FRAME_PREFIX . $filename;
			if ( file_exists( $filepath_w_frame ) ) {
				if ( filesize( $filepath_w_frame ) > 64 ) {
					return $upload_dir_url . '/' . self::FRAME_PREFIX . $filename;
				} else {
					return null;
				}
			}
			$filepath = $upload_dir_path . '/' . $filename;
			self::_addBorder2Img( $filepath, $filepath_w_frame );
			if ( file_exists( $filepath_w_frame ) ) {
				if ( filesize( $filepath_w_frame ) > 64 ) {
					return $upload_dir_url . '/' . self::FRAME_PREFIX . $filename;
				} else {
					return null;
				}
			}
		} catch ( \Exception $e ) {
			ChB_Common::my_log( '_getAttachmentMediumSizeUrlWithFrame: ' . $e->getMessage() );
		}
	}

	private static function _addBorder2Img( $source, $dest ) {
		ChB_Common::my_log( '_addBorder2Img source: ' . $source );
		ChB_Common::my_log( '_addBorder2Img dest: ' . $dest );

		$imagetype = exif_imagetype( $source );
		if ( $imagetype === IMAGETYPE_PNG ) {
			$im = imagecreatefrompng( $source );
		} else {
			$im = imagecreatefromjpeg( $source );
		}

		$width  = imagesx( $im );
		$height = imagesy( $im );

		$img_adj_width  = $width * self::FRAME_COEFF;
		$img_adj_height = $height * self::FRAME_COEFF;
		$newimage       = imagecreatetruecolor( $img_adj_width, $img_adj_height );

		$border_color = imagecolorallocate( $newimage, 255, 0, 0 );//255, 255);
		imagefilledrectangle( $newimage, 0, 0, $img_adj_width, $img_adj_height, $border_color );
		imagecopyresized( $newimage, $im, ( $img_adj_width - $width ) / 2, ( $img_adj_height - $height ) / 2, 0, 0, $width, $height, $width, $height );

		if ( $imagetype === IMAGETYPE_PNG ) {
			imagepng( $newimage, $dest, 0 );
		} else {
			imagejpeg( $newimage, $dest, 100 );
		}
	}

	public static function squareImage( $src_path, $dst_path ) {
		$timepoint = hrtime( true );
		$res       = false;
		try {
			ChB_Common::my_log( 'squareImage ' . $src_path );
			$img = imagecreatefromstring( file_get_contents( $src_path ) );

			if ( $img === false ) {
				return false;
			}
			$width  = imagesx( $img );
			$height = imagesy( $img );

			$background_color = self::getBackgroundColor( $img, $width, $height );

			$left   = $width - 1;
			$right  = 0;
			$top    = $height - 1;
			$bottom = 0;
			for ( $i = 0; $i < $width; $i ++ ) {
				for ( $j = 0; $j < $height; $j ++ ) {
					$cur_color = imagecolorat( $img, $i, $j );
					if ( ! self::isBackground( $cur_color, $background_color ) ) {
						if ( $i < $left ) {
							$left = $i;
						}
						if ( $i > $right ) {
							$right = $i;
						}
						if ( $j < $top ) {
							$top = $j;
						}
						if ( $j > $bottom ) {
							$bottom = $j;
						}
					}
				}
			}

			$res        = 0;
			$dim        = 0;
			$new_width  = 0;
			$new_height = 0;
			if ( $right > $left && $bottom > $top ) {

				$res        = 1;
				$new_width  = $right - $left + 1;
				$new_height = $bottom - $top + 1;

				$frame_coeff = 1.05;
				$dim         = intval( round( max( $new_height, $new_width ) * $frame_coeff ) );

				$eq_coeff = 1.05;
				if ( $width === $height ) {
					if ( ( $dim >= $width && $dim / $width <= $eq_coeff )
					     || ( $width >= $dim && $width / $dim <= $eq_coeff ) ) {
						$res = 0;
					}
				}
			}

			if ( $res === 1 ) {
				$sq_image = imagecreatetruecolor( $dim, $dim );
				imagefilledrectangle( $sq_image, 0, 0, $dim - 1, $dim - 1, $background_color );

				$x = intval( round( ( $dim - $new_width ) / 2 ) );
				$y = intval( round( ( $dim - $new_height ) / 2 ) );
				imagecopy( $sq_image, $img, $x, $y, $left, $top, $new_width, $new_height );

				$dim_limit = ChB_Common::MAX_UPLOAD_IMAGE_PX;
				if ( $dim > $dim_limit ) {
					$sq_image_limit = imagecreatetruecolor( $dim_limit, $dim_limit );
					imagecopyresampled( $sq_image_limit, $sq_image, 0, 0, 0, 0, $dim_limit, $dim_limit, $dim, $dim );
					$sq_image = $sq_image_limit;
				}

				$res = ( imagejpeg( $sq_image, $dst_path, 100 ) ? 1 : false );
			}
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'squareImage Exception: ' . $e->getMessage() );
		}
		$eta = round( ( hrtime( true ) - $timepoint ) / 1e+6 );
		ChB_Common::my_log( 'squareImage res=' . $res . ' ' . $eta );

		return $res;
	}

	public static function isBackground( $color, $background_color ) {
		if ( $background_color !== self::WHITE_COLOR ) {
			return $color === $background_color;
		}

		return ( $color === self::WHITE_COLOR ||
		         $color === 16711422 || //FEFEFE
		         $color === 16645629 || //FDFDFD
		         $color === 16579836 ); //FCFCFC
	}

	public static function getBackgroundColor( $img, $width, $height ) {

		$c = imagecolorat( $img, 0, 0 );
		if ( isset( $cc[ $c ] ) ) {
			$cc[ $c ] ++;
		} else {
			$cc[ $c ] = 1;
		}

		$c = imagecolorat( $img, $width - 1, 0 );
		if ( isset( $cc[ $c ] ) ) {
			$cc[ $c ] ++;
		} else {
			$cc[ $c ] = 1;
		}

		$c = imagecolorat( $img, 0, $height - 1 );
		if ( isset( $cc[ $c ] ) ) {
			$cc[ $c ] ++;
		} else {
			$cc[ $c ] = 1;
		}

		$c = imagecolorat( $img, $width - 1, $height - 1 );
		if ( isset( $cc[ $c ] ) ) {
			$cc[ $c ] ++;
		} else {
			$cc[ $c ] = 1;
		}

		if ( sizeof( $cc ) === 4 ) //все разные
		{
			return self::WHITE_COLOR;
		}

		if ( sizeof( $cc ) === 1 ) //все одинаковые
		{
			return $c;
		}

		if ( sizeof( $cc ) === 2 ) { //две группы
			$keys = array_keys( $cc );
			if ( $keys[0] === self::WHITE_COLOR || $keys[1] === self::WHITE_COLOR ) {
				return self::WHITE_COLOR;
			} else {
				return $keys[0];
			}
		}

		if ( sizeof( $cc ) === 3 ) {
			foreach ( $cc as $color => $q ) {
				if ( $q === 2 ) {
					return $color;
				}
			}
		}

		return self::WHITE_COLOR;
	}

	public static function squareAllProductImages( $product_id ) {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$pf             = new \WC_Product_Factory();
		$wc_product     = $pf->get_product( $product_id );
		$changed1       = false;
		$atts_to_delete = [];
		for ( $step = 1; $step <= 2; $step ++ ) {

			if ( $step === 1 ) {
				$thumb_image_id = $wc_product->get_image_id();
				if ( empty( $thumb_image_id ) ) {
					continue;
				}
				$atts_ids = [ $thumb_image_id ];
			} else {
				$atts_ids = $wc_product->get_gallery_image_ids();
			}

			$new_atts_ids = [];
			$changed2     = false;
			foreach ( $atts_ids as $att_id ) {
				$src_file = get_attached_file( $att_id );
				$tmp_name = tempnam( get_temp_dir(), 'squareAllProductImages' );
				if ( ! copy( $src_file, $tmp_name ) ) {
					@unlink( $tmp_name );
					$new_atts_ids[] = $att_id;
					continue;
				}
				$file['name']     = $product_id . '_' . self::my_GUID() . '.jpg';
				$file['tmp_name'] = $tmp_name;

				//Делаем картинку квадратной
				if ( ChB_Common::squareImage( $file['tmp_name'], $file['tmp_name'] ) === false ) {
					@unlink( $file['tmp_name'] );
					$new_atts_ids[] = $att_id;
					continue;
				}

				$new_att_id = media_handle_sideload( $file, $product_id );
				if ( is_wp_error( $new_att_id ) ) {
					@unlink( $file['tmp_name'] );
					$new_atts_ids[] = $att_id;
					continue;
				}

				$new_atts_ids[]   = $new_att_id;
				$changed2         = true;
				$atts_to_delete[] = $att_id;
			}

			if ( $changed2 ) {
				$changed1 = true;
				if ( $step === 1 ) {
					$wc_product->set_image_id( $new_atts_ids[0] );
				} else {
					$wc_product->set_gallery_image_ids( $new_atts_ids );
				}
			}
		}

		if ( $changed1 ) {
			$wc_product->save();
			if ( ! empty( $atts_to_delete ) ) {
				foreach ( $atts_to_delete as $att_id ) {
					ChB_Common::my_log( 'squareAllProductImages: product_id=' . $product_id . ' deleting att_id=' . $att_id );
					wp_delete_attachment( $att_id, true );
				}
			}
		} else {
			ChB_Common::my_log( 'squareAllProductImages: product_id=' . $product_id . ' no changes ' );
		}
	}

	public static function getImageGeneratedByText( $product_or_term_id, $is_term = false ) {

		$attr_name_text = ChB_Common::PRODUCT_ATTR_TEXT4AUTO_IMG;
		if ( ! $is_term ) {
			$descs     = get_post_meta( $product_or_term_id, $attr_name_text );
			$color_hex = get_post_meta( $product_or_term_id, ChB_Common::PRODUCT_ATTR_COLOR4AUTO_IMG, true );
		} else {
			$descs     = get_term_meta( $product_or_term_id, $attr_name_text );
			$color_hex = get_term_meta( $product_or_term_id, ChB_Common::PRODUCT_ATTR_COLOR4AUTO_IMG, true );
		}
		if ( empty( $descs ) ) {
			return null;
		}

		$font_path = ChB_Settings()->getTypography( 'font4img_gen' );
		$rgb       = str_split( $color_hex, 2 );

		foreach ( $descs as $desc ) {
			if ( empty( $desc ) ) {
				continue;
			}
			$dim      = 500;
			$coeff1   = 0.06;
			$coeff2   = 0.4;
			$size     = 20;
			$hash     = crc32( $desc . $dim . $coeff1 . $coeff2 . $size );
			$filename = $product_or_term_id . '_' . $attr_name_text . '_' . $hash . '.png';
			$dst_path = ChB_Settings()->uploads_path . $filename;
			if ( file_exists( $dst_path ) ) {
				$res[] = ChB_Settings()->uploads_url . $filename;
				continue;
			}

			$new_image  = imagecreatetruecolor( $dim, $dim );
			$color      = imagecolorallocate( $new_image, hexdec( $rgb[0] ), hexdec( $rgb[1] ), hexdec( $rgb[2] ) );
			$color_text = imagecolorallocate( $new_image, 255, 255, 255 );

			imagefilledrectangle( $new_image, 0, 0, $dim - 1, $dim - 1, $color );

			$text = strtoupper( $desc );
			imagettftext( $new_image, $size, 0, $dim * $coeff1, $dim * $coeff2, $color_text, $font_path, $text );
			imagealphablending( $new_image, true );


			if ( imagejpeg( $new_image, $dst_path, 100 ) ? 1 : false ) {
				$res[] = ChB_Settings()->uploads_url . $filename;
			}
		}

		return empty( $res ) ? null : $res;
	}

	public static function getGroupImg( $products, $print_numbers ) {
		if ( ! $products ) {
			return [];
		}

		$num = sizeof( $products );
		if ( $num < 2 ) {
			return [];
		}
		if ( $num > 9 ) {
			$num = 9;
		}

		//500x260 либо 500x500
		$DEF_W = 500;
		$DEF_H = 260;
		$grids =
			[
				2 => [ 'aspect' => 'horizontal', 'rows' => 1 ],
				3 => [ 'aspect' => 'horizontal', 'rows' => 1 ],
				4 => [ 'aspect' => 'square', 'rows' => 2 ],
				5 => [ 'aspect' => 'horizontal', 'rows' => 2 ],
				6 => [ 'aspect' => 'horizontal', 'rows' => 2 ],
				7 => [ 'aspect' => 'square', 'rows' => 3 ],
				8 => [ 'aspect' => 'square', 'rows' => 3 ],
				9 => [ 'aspect' => 'square', 'rows' => 3 ]
			];

		try {
			$num_rows = $grids[ $num ]['rows'];
			$num_cols = intdiv( $num, $num_rows ) + ( ( $num % $num_rows > 0 ) ? 1 : 0 );

			$W = $DEF_W;
			$H = $grids[ $num ]['aspect'] == 'horizontal' ? $DEF_H : $DEF_W;

			$cell_w = intdiv( $W, $num_cols );
			$cell_h = intdiv( $H, $num_rows );

			ChB_Settings()->tic( 'imagecreatetruecolor' );
			$image = imagecreatetruecolor( $W, $H );
			imagefilledrectangle( $image, 0, 0, $W, $H, ChB_Common::WHITE_COLOR );
			$color_text = ChB_Settings()->getTypography( 'cart_font_color' );
			$font_path  = ChB_Settings()->getTypography( 'cart_font_path' );
			ChB_Settings()->toc( 'imagecreatetruecolor' );

			for ( $ind = 0; $ind < $num; $ind ++ ) {

				$product = $products[ $ind ];
				if ( $product instanceof \WC_Product ) {
					$wc_product = $product;
				} else {
					if ( empty( $pf ) ) {
						$pf = new \WC_Product_Factory();
					}
					$wc_product = $pf->get_product( $product );
				}

				$src_img_src = null;
				if ( ! empty( $wc_product ) ) {
					$att_id = $wc_product->get_image_id();
					if ( ! is_wp_error( $att_id ) && ! empty( $att_id ) ) {
						$src_img_src = wp_get_attachment_image_src( $att_id, [ $cell_w, 0 ] );
						if ( empty( $src_img_src[0] ) ) {
							$src_img_src = wp_get_attachment_image_src( $att_id, ChB_Common::IMG_MEDIUM_SIZE );
						} else {
							ChB_Common::my_log( 'cell img: ' . $src_img_src[0] );
						}
					}
				}

				if ( empty( $src_img_src[0] ) ) {
					ChB_Settings()->tic( 'imagecreatefromstring' );
					$src_img_src = wc_placeholder_img_src();
					$src_img     = imagecreatefromstring( file_get_contents( $src_img_src ) );
					ChB_Settings()->toc( 'imagecreatefromstring' );
				} else {
					$src_img = @imagecreatefromjpeg( $src_img_src[0] );
					if ( ! $src_img ) {
						$src_img = imagecreatefrompng( $src_img_src[0] );
						if ( ! $src_img ) {
							$src_img = imagecreatefromwebp( $src_img_src[0] );
						}
					}
				}

				$cur_row = intdiv( $ind, $num_cols ); //строки считаем с нуля
				$cur_col = $ind % $num_cols; //колонки считаем с нуля
				$cur_x   = $cur_col * $cell_w;
				$cur_y   = $cur_row * $cell_h;

				$src_w = imagesx( $src_img );
				$src_h = imagesy( $src_img );

				//Приложили картинку к высоте и смотрим вписывается ли длина
				$temp = $cell_h * $src_w / $src_h;
				if ( $temp < $cell_w ) {//длина вписывается
					$new_src_w = intval( round( $temp ) );
					$new_src_h = $cell_h;
				} else {
					//не вписывается, прикладываем длину
					$new_src_w = $cell_w;
					$new_src_h = intval( round( $cell_w * $src_h / $src_w ) );
				}

				$dst_offset_x = $cur_x + ( $cell_w - $new_src_w ) / 2;
				$dst_offset_y = $cur_y + ( $cell_h - $new_src_h ) / 2;

				ChB_Settings()->tic( 'imagecopyresampled' );
				imagecopyresampled( $image, $src_img, $dst_offset_x, $dst_offset_y, 0, 0, $new_src_w, $new_src_h, $src_w, $src_h );
				imagedestroy( $src_img );
				ChB_Settings()->toc( 'imagecopyresampled' );

				if ( $print_numbers ) {
					$text_over_image = $ind + 1;
					$coeff1          = 0.2;
					$coeff2          = 0.05;
					$coeff3          = 0.2;
					imagettftext( $image, $cell_h * $coeff1, 0, $cur_x + $cell_w * $coeff2, $cur_y + $cell_h * $coeff3, $color_text, $font_path, $text_over_image );
				}
			}

			if ( $H !== $W ) {
				$image_tmp = imagecreatetruecolor( $W, $W );
				imagefilledrectangle( $image_tmp, 0, 0, $W, $W, ChB_Common::WHITE_COLOR );
				imagecopyresampled( $image_tmp, $image, 0, ( $W - $H ) * 0.5, 0, 0, $W, $H, $W, $H );
				imagedestroy( $image );
				$image = $image_tmp;
			}

			$name = 'grp_' . ChB_Common::my_rand_string( 10 ) . '.jpg';

			ChB_Settings()->tic( 'imagejpeg' );
			if ( imagejpeg( $image, ChB_Settings()->uploads_path . $name, 100 ) ) {
				$res_url = ChB_Settings()->uploads_url . $name;
			}
			imagedestroy( $image );
			ChB_Settings()->toc( 'imagejpeg' );

		} catch ( \Throwable $e ) {
			ChB_Common::my_log( 'error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		if ( empty( $res_url ) ) {
			$res_url = wc_placeholder_img_src();
		}

		return [
			'aspect' => $grids[ $num ]['aspect'],
			'url'    => $res_url
		];
	}

	public static function reformatWCPriceHook( $return, $price, $args, $unformatted_price, $original_price ) {

		if ( isset( $args['wy_price_number'] ) ) {
			return ( $unformatted_price < 0 ? '-' : '' ) . $price;
		}

		if ( ! isset( $args['wy_price'] ) ) {
			return $return;
		}

		$currency_code = get_woocommerce_currency();
		if ( empty( ChB_Constants::CURRENCY_SIGNS[ $currency_code ] ) ) {
			return ( $unformatted_price < 0 ? '-' : '' ) . $price . ' ' . $currency_code;
		}

		$currency_sign = ChB_Constants::CURRENCY_SIGNS[ $currency_code ];
		$format        = str_replace( '&nbsp;', ' ', $args['price_format'] );

		return (
		$unformatted_price < 0 ?
			( '-' . sprintf( $format, $currency_sign, $price ) ) :
			sprintf( $format, $currency_sign, $price )
		);
	}

	public static function printPrice( $price, $stroke_through = false ) {

		if ( $stroke_through ) {
			return ChB_Lang::maybeForceLTR( ChB_Promo::getStrokeThroughNumber( wc_price( $price, [ 'wy_price' => 1 ] ) ) );
		} else {
			return ChB_Lang::maybeForceLTR( wc_price( $price, [ 'wy_price' => 1 ] ) );
		}

	}

	public static function printPriceNoCurrency( $price, $stroke_through = false, $thousands_sep = ' ' ) {
		if ( is_numeric( $price ) ) {
			$price_str = number_format( floatval( $price ), 0, ',', $thousands_sep );
			if ( $stroke_through ) {
				$price_str = ChB_Promo::getStrokeThroughNumber( $price_str );
			}

			return ChB_Lang::maybeForceLTR( $price_str );
		}

		return '';
	}

	public static function printPricePlain( $price ) {
		return ChB_Lang::maybeForceLTR( number_format( floatval( $price ), 0, '.', '' ) . ' ' . get_woocommerce_currency() );
	}

	public static function printNumberNoSpaces( $price ) {
		return ChB_Lang::maybeForceLTR( number_format( floatval( $price ), 0, '', '' ) );
	}

	public static function printFloat2( $x ) {
		return ChB_Lang::maybeForceLTR( number_format( floatval( $x ), 2, '.', '' ) );
	}

	public static function printFloat2Round( $x ) {
		return ChB_Lang::maybeForceLTR( round( floatval( $x ), 2 ) );
	}

	public static function makeManyButtons( $buttons, $first_caption ) {

		$messages                = [];
		$num_of_buttons_in_group = 3;
		$group_of_buttons        = [];
		$ind                     = 0;
		$caption                 = $first_caption;
		foreach ( $buttons as $button ) {

			$group_of_buttons[] = $button;

			if ( ( $ind % $num_of_buttons_in_group ) === ( $num_of_buttons_in_group - 1 ) ) {
				$messages[]       = [
					'type'    => 'text',
					'text'    => $caption,
					'buttons' => $group_of_buttons
				];
				$group_of_buttons = [];
				$caption          = ChB_Lang::translate( ChB_Lang::LNG0013 );
			}

			$ind ++;
		}

		if ( ! empty( $group_of_buttons ) ) {
			$messages[] = [
				'type'    => 'text',
				'text'    => $caption,
				'buttons' => $group_of_buttons
			];
		}

		return $messages;
	}

	public static function cardsAsText( $messages ) {

		$new_messages = [];
		foreach ( $messages as $message ) {
			if ( $message['type'] != 'cards' ) {
				$new_messages[] = $message;
			} else {
				foreach ( $message['elements'] as $card ) {
					$new_messages[] = [
						'type' => 'text',
						'text' =>
							$card['title'] . chr( 10 ) .
							$card['action_url'] . chr( 10 ) .
							$card['subtitle'] . chr( 10 ) .
							$card['image_url'] . chr( 10 )
					];
				}
			}
		}

		return $new_messages;
	}

	public static function sendGet( $url, $timeout = 30, $content_type = 'application/json' ) {

		$response = wp_remote_get( $url, [
			'headers' => [ 'Content-Type' => $content_type ],
			'timeout' => $timeout,
		] );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendGet ERROR' );

			return false;
		}

		return $response['body'];
	}

	public static function sendPost( $url, $fields, $timeout = 30 ) {

		$response = wp_remote_post( $url, [
			'headers'     => [ 'Content-Type' => 'application/json' ],
			'timeout'     => $timeout,
			'body'        => is_string( $fields ) ? $fields : json_encode( $fields ),
			'data_format' => 'body',
		] );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendPost ERROR' );

			return false;
		}
		ChB_Common::my_log( $response['body'], true, 'sendPost response' );

		return $response['body'];
	}

	public static function checkStatusIsSuccess( &$body ) {
		if ( ! is_array( $body ) ) {
			$body = json_decode( $body, true );
		}

		return isset( $body['status'] ) && ( $body['status'] === 'success' );
	}

	public static function sendDelete( $url, $timeout = 30 ) {

		$response = wp_remote_request( $url, [
			'timeout' => $timeout,
			'method'  => 'DELETE'
		] );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendDelete ERROR' );

			return false;
		}

		return $response['body'];
	}

	public static function getKeyCapEmoji( $digit ) {
		$emojis = [ '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '*️⃣	' ];
		if ( $digit > 10 ) {
			return $emojis[11];
		}

		return $emojis[ $digit ];
	}

	public static function getRandomWeightedSubArray_Keys( $weighted_array, $n, $weight_field_name, $filters = null ) {
		if ( ! empty( $filters ) ) {
			$filtered_weighted_array = [];
			foreach ( $weighted_array as $key => $value ) {
				$is_ok = true;
				foreach ( $filters as $filter_key => $filter_value ) {
					if ( $value[ $filter_key ] != $filter_value ) {
						$is_ok = false;
						break;
					}
				}
				if ( $is_ok ) {
					$filtered_weighted_array[ $key ] = $value;
				}
			}
			$weighted_array = $filtered_weighted_array;
		}

		if ( empty( $weighted_array ) ) {
			return [];
		}

		$total_weight = 0;
		foreach ( $weighted_array as $value ) {
			$total_weight += $value[ $weight_field_name ];
		}

		$res = [];
		//Делаем 2n попыток, потому что не берем дубли
		for ( $i = 0; $i < 2 * $n; $i ++ ) {
			if ( sizeof( $res ) >= $n ) {
				break;
			}
			$rand = rand( 1, $total_weight );
			$sum  = 0;
			foreach ( $weighted_array as $key => $value ) {
				$sum += $value[ $weight_field_name ];
				if ( $sum >= $rand ) {
					if ( ! in_array( $key, $res ) ) {
						$res[] = $key;
					}
					break;
				}
			}
		}

		return $res;
	}

	public static function getRandomSubarray( $arr, $num, $preserve_keys ) {
		if ( empty( $arr ) ) {
			return [];
		}
		if ( $num >= count( $arr ) ) {
			return $arr;
		}
		$keys = array_rand( $arr, $num );
		if ( empty( $keys ) ) {
			return [];
		}
		if ( $num === 1 ) {
			$keys = [ $keys ];
		}

		$res = [];
		foreach ( $keys as $key ) {
			if ( $preserve_keys ) {
				$res[ $key ] = $arr[ $key ];
			} else {
				$res[] = $arr[ $key ];
			}
		}

		return $res;
	}

	public static function round3( $val ) {
		return intval( round( $val, - 3, PHP_ROUND_HALF_UP ) );
	}

	public static function ceil( $val ) {
		if ( ChB_Settings()->getCeilDigits() ) {
			return intval( ceil( $val * pow( 10, - ChB_Settings()->getCeilDigits() ) ) * pow( 10, ChB_Settings()->getCeilDigits() ) );
		} else {
			return intval( ceil( $val ) );
		}
	}

	public static function arraysHaveTheSameValues( $a1, $a2 ) {
		if ( ! is_array( $a1 ) ) {
			return false;
		}
		if ( ! is_array( $a2 ) ) {
			return false;
		}
		if ( count( $a1 ) !== count( $a2 ) ) {
			return false;
		}

		foreach ( $a1 as $e ) {
			if ( ! in_array( $e, $a2 ) ) {
				return false;
			}
		}

		return true;
	}

	public static function arraysHaveTheSameKeysAndValues( $a1, $a2 ) {
		if ( ! $a1 && ! $a2 ) {
			return true;
		}
		if ( ! is_array( $a1 ) || ! is_array( $a2 ) || count( $a1 ) !== count( $a2 ) ) {
			return false;
		}

		foreach ( $a1 as $k => $v ) {
			if ( ! isset( $a2[ $k ] ) || $a2[ $k ] !== $v ) {
				return false;
			}
		}

		return true;
	}

	public static function printArrayCount( $a1 ) {
		return is_array( $a1 ) ? count( $a1 ) : 'NOT_ARRAY';
	}

	public static function sendTextMessageWithFBAPI( $recipient_id, $message_text, $is_response ) {
		if ( ! ChB_Settings()->auth->getFBAccessToken() ) {
			return false;
		}

		$messaging_type = $is_response ? 'RESPONSE' : 'UPDATE';

		$body = [
			'messaging_type' => $messaging_type,
			'recipient'      => [
				'id' => $recipient_id
			],
			'message'        => [ 'text' => $message_text ]
		];

		$url = 'https://graph.facebook.com/' . ChB_Constants::FB_API_VERSION . '/me/messages?access_token=' . ChB_Settings()->auth->getFBAccessToken();

		return self::sendPost( $url, $body );
	}

	public static function getUserInfoFromFBAPI( $subscriber_id ) {
		if ( ! ChB_Settings()->auth->getFBAccessToken() ) {
			return false;
		}

		$url = 'https://graph.facebook.com/' . ChB_Constants::FB_API_VERSION . '/' . $subscriber_id . '?access_token=' . ChB_Settings()->auth->getFBAccessToken();
		$res = self::sendGet( $url );
		if ( ! $res ) {
			return false;
		}
		$data = json_decode( $res, true );
		if ( ! $data || isset( $data['error'] ) ) {
			return false;
		}

		return $data;
	}

	public static function getPageInfoFromFBAPI() {
		if ( ! ChB_Settings()->auth->getFBAccessToken() || ! ChB_Settings()->auth->getFBPageID() ) {
			return false;
		}

		$url = 'https://graph.facebook.com/' . ChB_Constants::FB_API_VERSION . '/' . ChB_Settings()->auth->getFBPageID() . '?access_token=' . ChB_Settings()->auth->getFBAccessToken();
		$res = self::sendGet( $url );
		if ( ! $res ) {
			return false;
		}
		$data = json_decode( $res, true );
		if ( ! $data || isset( $data['error'] ) ) {
			return false;
		}

		return $data;
	}

	public static function json_decode( $str, $value_on_empty = null ) {
		if ( $str && ( $res = json_decode( $str, true ) ) ) {
			return $res;
		}

		return $value_on_empty;
	}

	public static function getTempUrl4File( $path ) {
		$id        = self::my_rand_string( 10 );
		$temp_path = ChB_Settings()->temp_uploads_path . 'temp_url_' . $id;
		$temp_url  = ChB_Settings()->temp_uploads_url . 'temp_url_' . $id;
		copy( $path, $temp_path );

		return $temp_url;
	}

	public static function timestamp2DateTime( $ts ) {
		$dt = new \DateTime( 'now', ChB_Settings()->timezone );
		$dt->setTimestamp( $ts );

		return $dt->format( 'Y-m-d H:i:s' );
	}

	/**
	 * @param $ts
	 *
	 * @return array 'day' - day of the week (1 to 7), 'time' - hour.minutes - 24-hour format, minutes from 00 to 59
	 * @throws \Exception
	 */
	public static function timestamp2WeekDayHourMinute( $ts ) {
		$dt = new \DateTime( 'now', ChB_Settings()->timezone );
		$dt->setTimestamp( $ts );

		return [ 'day' => intval( $dt->format( 'N' ) ), 'time' => $dt->format( 'H.i' ) ];
	}


	public static function getCurrentHourBeginningTS() {
		$now = time();

		return $now - ( $now % 3600 );
	}

	public static function utilIsDefined() {
		if ( function_exists( 'wany_chat_util_load' ) ) {
			wany_chat_util_load();

			return true;
		}

		return false;
	}

	public static function sanitizeInt( $vals ) {
		if ( is_array( $vals ) ) {
			$res = [];
			foreach ( $vals as $val ) {
				$res[] = intval( $val );
			}

			return $res;
		}

		return intval( $vals );
	}

	public static function sanitizeText( $vals ) {
		if ( is_array( $vals ) ) {
			$res = [];
			foreach ( $vals as $val ) {
				$res[] = sanitize_text_field( $val );
			}

			return $res;
		}

		return sanitize_text_field( $vals );
	}

	public static function sanitizeKeys( $vals ) {
		if ( is_array( $vals ) ) {
			$res = [];
			foreach ( $vals as $val ) {
				$res[] = sanitize_key( $val );
			}

			return $res;
		}

		return sanitize_key( $vals );
	}

	public static function mb_ucfirst( $str ) {
		return mb_strtoupper( mb_substr( $str, 0, 1 ) ) . mb_substr( $str, 1 );
	}
}