<?php

namespace ChatBot;


class ChB_FLOW_MyOrders {
	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_myorders_getMyOrders' ) {
			return self::getMyOrders( $ChB );
		} elseif ( $ChB->task === 'manychat_myorders_sendFindOrderInput' ) {
			return self::sendFindOrderInput( $ChB );
		} elseif ( $ChB->task === 'manychat_myorders_openOrder' ) {
			return self::openOrder( $ChB );
		} elseif ( $ChB->task === 'manychat_myorders_getOrderJSON' ) {
			return self::getOrderJSON( $ChB );
		} elseif ( $ChB->task === 'manychat_myorders_openOrderSummary' ) {
			return self::openOrderSummary( $ChB );
		} elseif ( $ChB->task === 'manychat_myorders_checkOrdersExist' ) {
			return self::checkOrdersExist( $ChB );
		}

		return [];
	}

	public static function getMyOrders( ChatBot $ChB ) {

		$messages[] = [
			'type'    => 'text',
			'text'    => ChB_Lang::translate( ChB_Lang::LNG0085 ),
			'buttons' => [
				ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0086 ), [
					'task' => 'manychat_order_openCart',
					'view' => ChB_Common::VIEW_CART_FULL_DETAILS
				] )
			]
		];

		$orders = ChB_Orders::getMyOrders( $ChB->user );
		if ( empty( $orders ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => ChB_Lang::translate( ChB_Lang::LNG0035 ),
			];
		} else {
			foreach ( $orders as $order ) {
				$messages[] = self::getOrderMessage( $ChB, $order );
			}
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function sendFindOrderInput( ChatBot $ChB ) {
		return ChB_FLOW_Common::startFreeInputText(
			$ChB,
			'Please input order number:',
			'',
			[ 'task' => 'manychat_myorders_openOrder', 'text_callback_par' => 'order_id' ],
			[ 'task' => 'manychat_cmn_sendTextMessage', 'val' => 'Ok, nevermind :)' ],
			[ 'task' => 'manychat_cmn_sendTextMessage', 'val' => 'Ok, nevermind ;)' ],
			5,
			0
		);
	}

	public static function getOrderMessage( ChatBot $ChB, $order ) {
		return [
			'type'    => 'text',
			'text'    => '[' . $order['user_name'] . ' ' . $order['date_created'] . ']' . chr( 10 ) . ChB_Lang::translate( ChB_Lang::LNG0036 ) . ' #' . $order['number'] . ': ' . ChB_Common::printPrice( $order['total'] ),
			'buttons' => [
				self::getOpenOrderButton( $ChB, $order['number'] )
			]
		];
	}

	public static function getOpenOrderButton( ChatBot $ChB, $order_id, $caption = null ) {
		return ChatBot::makeDynamicBlockCallbackButton( $ChB,
			( $caption ? $caption : ChB_Lang::translate( ChB_Lang::LNG0014 ) ),
			[
				'task'     => 'manychat_myorders_openOrder',
				'order_id' => $order_id
			] );
	}

	public static function sendNotificationToCustomerOnOrderConfirmation( $order_id ) {

		$wc_order = wc_get_order( $order_id );
		if ( ! $wc_order ) {
			return false;
		}

		$wp_user_id = $wc_order->get_customer_id();
		if ( ! $wp_user_id ) {
			return false;
		}

		$user = ChB_User::initUserByWPUserID( $wp_user_id );
		if ( ! $user ) {
			return false;
		}

		$ChB = ChatBot::openTempChatBotSession( $user );
		if ( ! $ChB ) {
			return false;
		}

		$dates = ChB_Order::getEstimateDeliveryDates( time() );

		$text = '🤗 ' . $user->getHi() .
		        "\n📦 " . ChB_Lang::translate( ChB_Lang::LNG0114 ) .
		        ( $dates ? "\n" . ChB_Lang::translateWithPars( ChB_Lang::LNG0115, $dates['from'], $dates['to'] ) : '' );
		$text = ChB_Lang::maybeForceRTL( $text );

		$messages = [ [ 'type' => 'text', 'text' => $text ] ];

		$ChB->setParam( 'order_id', $order_id );
		$data = ChB_FLOW_MyOrders::openOrderSummary( $ChB, $messages );

		$fields = [
			'message_tag' => ChB_Constants::FB_MESSAGE_TAG_POST_PURCHASE_UPDATE,
			'data'        => $data
		];

		$res = ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );
		ChatBot::closeTempChatBotSession();

		return $res;
	}

	public static function openOrderSummary( ChatBot $ChB, $custom_messages = null ) {

		if ( ! ( $wc_order = ChB_Order::getWCOrder( $ChB->getParam( 'order_id' ) ) ) ) {
			return false;
		}

		$order = ChB_Order::getOrderSummary( $wc_order );
		if ( ! $order ) {
			return false;
		}

		if ( ! empty( $custom_messages ) ) {
			$messages = $custom_messages;
		}
		$messages[] = self::getOrderMessage( $ChB, $order );

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function getOrderJSON( ChatBot $ChB ) {

		$wc_order = ChB_Order::getWCOrder( $ChB->getParam( 'order_id' ) );
		if ( ! $wc_order ) {
			return ChB_FLOW_Common::makeResponse4APPAction();
		}

		if ( ! ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $wc_order->get_user_id() != $ChB->user->wp_user_id ) {
			return ChB_FLOW_Common::makeResponse4APPAction();
		}

		//example: &fields=status.payment_method.shipping_option_id.__cf__some_cf
		$fields = $ChB->getParam( 'fields' );
		$cf     = [];

		foreach ( $fields as $field ) {
			if ( substr( $field, 0, 6 ) === '__cf__' ) {
				$cf[ $field ] = substr( $field, 6 );
			}
		}

		if ( ! ( $order_details = ChB_Order::getOrderDetails( $wc_order, null, $cf ) ) ) {
			return ChB_FLOW_Common::makeResponse4APPAction();
		}

		$ind = 0;
		$res = [ ChB_Common::EMPTY_TEXT, ChB_Common::EMPTY_TEXT, ChB_Common::EMPTY_TEXT, ChB_Common::EMPTY_TEXT ];
		foreach ( $fields as $field ) {
			if ( $ind > 3 ) {
				break;
			}
			if ( ! empty( $order_details[ $field ] ) ) {
				$res[ $ind ] = $order_details[ $field ];
			}
			$ind ++;
		}

		return ChB_FLOW_Common::makeResponse4APPAction( $res[0], $res[1], $res[2], $res[3] );
	}

	public static function openOrder( ChatBot $ChB ) {

		$wc_order = ChB_Order::getWCOrder( $ChB->getParam( 'order_id' ) );
		if ( ! $wc_order ) {
			return false;
		}

		if ( ! ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $wc_order->get_user_id() != $ChB->user->wp_user_id ) {
			return false;
		}

		if ( ! ( $order_details = ChB_Order::getOrderDetails( $wc_order, null ) ) ) {
			return false;
		}

		$messages[] = [
			'type'               => 'cards',
			'image_aspect_ratio' => $order_details['grp_img']['aspect'],
			'elements'           =>
				[
					[
						'title'     => ChB_Lang::translateWithPars( ChB_Lang::LNG0103, ChB_Common::printNumberNoSpaces( $wc_order->get_order_number() ) ),
						'image_url' => $order_details['grp_img']['url']
					]
				]
		];

		ChB_FLOW_NewOrder::printProductsDetails( $ChB, $order_details['products_details'], $messages, false, false, null );

		if ( ! empty( $order_details['shipping_details'] ) && $order_details['shipping_details'] instanceof ChB_ShippingDetails ) {
			$messages[] = [
				'type' => 'text',
				'text' => $order_details['shipping_details']->printContactInfo()
			];
		}

		$buttons = [];
		ChB_FLOW_Manager::makeConfirmButton4Order( $ChB, $buttons, $wc_order );
		ChB_FLOW_Manager::makeMarkAsShippedButton4Order( $ChB, $buttons, $wc_order );
		ChB_FLOW_Manager::makeCompleteButton4Order( $ChB, $buttons, $wc_order );
		ChB_FLOW_Manager::makeCancelButton4Order( $ChB, $buttons, $wc_order );

		self::printTotals( $order_details, $messages, $buttons );
		self::printPaymentStatus( $wc_order, $messages );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function printTotals( &$details, &$messages, $buttons ) {

		if ( ! empty( $details['shipping_details'] ) && $details['shipping_details'] instanceof ChB_ShippingDetails ) {
			$messages[] = [
				'type' => 'text',
				'text' => $details['shipping_details']->printShippingText()
			];
		}

		$text = [];
		if ( $details['total'] != $details['subtotal'] ) {
			$text[] = ChB_Lang::translate( ChB_Lang::LNG0056 ) . ' ' . ChB_Common::printPrice( $details['subtotal'], true );
			if ( ! empty( $details['promo_conditions'] ) ) {
				$text[] = $details['promo_conditions'];
			}
			$text[] = ChB_Lang::translate( ChB_Lang::LNG0057 ) . ' ' . ChB_Common::printPrice( $details['total'] );
		} else {
			$text[] = ChB_Lang::translate( ChB_Lang::LNG0024 ) . ' ' . ChB_Common::printPrice( $details['total'] );
		}

		$messages[] = [
			'type'    => 'text',
			'text'    => implode( "\n", $text ),
			'buttons' => $buttons
		];
	}

	public static function printPaymentStatus( \WC_Order $wc_order, &$messages ) {

		$messages[] = [
			'type' => 'text',
			'text' => ChB_Lang::translate( ChB_Lang::LNG0171 ) . "\n" .
			          ( $wc_order->is_paid() ? ChB_Lang::translate( ChB_Lang::LNG0187 ) : ChB_Lang::translate( ChB_Lang::LNG0188 ) )
		];
	}

	public static function checkOrdersExist( ChatBot $ChB ) {
		$res     = 'no';
		$minutes = intval( $ChB->getParam( 'val' ) );
		if ( $minutes ) {
			$orders = ChB_Orders::getMyOrders( $ChB->user, 1 );
			if ( ! empty( $orders[0]['ts_date_created'] ) && ( $orders[0]['ts_date_created'] >= time() - $minutes * 60 ) ) {
				$res = 'yes';
			}
		}

		return ChB_FLOW_Common::makeResponse4APPAction( $res );
	}
}