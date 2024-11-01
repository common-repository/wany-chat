<?php

namespace ChatBot;


class ChB_Pixel {
	const EVENT_VIEW_CONTENT = 'ViewContent';
	const EVENT_ADD_TO_CART = 'AddToCart';
	const EVENT_PURCHASE = 'Purchase';

	public static function sendEvent2Pixel( $args ) {

		if ( ! ChB_Settings()->getParam( 'pixel_id' ) ) {
			ChB_Common::my_log( 'sendEvent2Pixel: no pixel' );

			return;
		}

		$eventName   = $args['event_name'];
		$custom_data = [
			'currency'     => get_woocommerce_currency(),
			'content_type' => 'product_group'
		];

		$some_product_id = '';
		if ( ! empty( $args['product_ids'] ) ) {
			$product_ids                = $args['product_ids'];
			$custom_data['content_ids'] = $product_ids;
			if ( is_array( $product_ids ) && sizeof( $product_ids ) > 0 ) {
				$some_product_id = $product_ids[0];
			} else {
				$some_product_id = $product_ids;
			}
		}

		if ( ! empty( $args['price'] ) ) {
			$custom_data['value'] = $args['price'];
		}

		if ( ! empty( $args['order_id'] ) ) {
			if ( $order_details = ChB_Order::getOrderDetails( $args['order_id'], null ) ) {
				foreach ( $order_details['products_details'] as $product_details ) {
					$contents[]      = [ 'id' => $product_details['id'], 'quantity' => $product_details['quantity'] ];
					$some_product_id = $product_details['id'];
				}
				if ( ! empty( $contents ) ) {
					$custom_data['contents'] = $contents;
				}
				if ( empty( $args['price'] ) ) {
					if ( ! empty( $order_details['total'] ) ) {
						$custom_data['value'] = ChB_Common::printNumberNoSpaces( $order_details['total'] );
					}
				}
			}
		}

		$user      = $args['user'];
		$user_data = [];
		if ( $user->getFirstName() ) {
			$user_data['fn'] = hash( 'sha256', $user->getFirstName() );
		}
		if ( $user->getLastName() ) {
			$user_data['ln'] = hash( 'sha256', $user->getLastName() );
		}
		$ph = $user->getUserPhone();
		if ( ! empty( $ph ) ) {
			$user_data['ph'] = hash( 'sha256', $ph );
		}

		$data = [
			[
				'event_name'       => $eventName,
				'event_time'       => $args['event_time'],
				'event_source_url' => ChB_Settings()->ref_url_product . $some_product_id,
				'user_data'        => $user_data,
				'custom_data'      => $custom_data,
			]
		];

		ChB_Common::my_log( $data, true, 'sendPost2Pixel' );
		$fields = [ 'data' => $data, 'access_token' => ChB_Settings()->getParam( 'fb_marketing_api_token' ) ];
		$url    = 'https://graph.facebook.com/' . ChB_Constants::FB_API_VERSION . '/' . ChB_Settings()->getParam( 'pixel_id' ) . '/events';
		$result = ChB_Common::sendPost( $url, $fields );
		ChB_Common::my_log( $result, true, 'sendPost2Pixel' );
	}

	public static function schedulePixelEvent( ChatBot $ChB, $eventName, $product_ids, $price, $order_id ) {
		if ( ! is_numeric( $price ) ) {
			return;
		}
		$args = [
			'user'        => $ChB->user,
			'event_name'  => $eventName,
			'event_time'  => time(),
			'product_ids' => $product_ids,
			'price'       => $price,
			'order_id'    => $order_id
		];
		ChB_Events::scheduleSingleEventOnShutdown( $ChB, ChB_Events::CHB_EVENT_PIXEL, $args );
	}
}