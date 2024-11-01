<?php

namespace ChatBot;

class ChB_FLOW_NewOrder {

	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_order_chooseVariation' ) {

			return self::chooseVariation( $ChB );

		} elseif ( $ChB->task === 'manychat_order_chooseQuantity' ) {

			return self::chooseQuantity( $ChB );

		} elseif ( $ChB->task === 'manychat_order_updateCart' ) {

			return self::updateCart( $ChB );

		} elseif ( $ChB->task === 'manychat_order_updateCartConfirm' ) {

			$ChB->setParam( 'view', [ ChB_Common::VIEW_PLACING_ORDER ] );

			return self::updateCart( $ChB );

		} elseif ( $ChB->task === 'manychat_order_clearCartYN' ) {

			return self::clearCartYN( $ChB );

		} elseif ( $ChB->task === 'manychat_order_clearCart' ) {

			return self::clearCart( $ChB );

		} elseif ( $ChB->task === 'manychat_order_openCart' ) {

			return self::openCart( $ChB );

		} elseif ( $ChB->task === 'manychat_order_inputAddressPart' ) {

			return self::inputAddressPart( $ChB );

		} elseif ( $ChB->task === 'manychat_order_chooseShipping' ) {

			return self::chooseShipping( $ChB );

		} elseif ( $ChB->task === 'manychat_order_choosePayment' ) {

			return self::choosePayment( $ChB );

		} elseif ( $ChB->task === 'manychat_order_choosePayment4Order' ) {

			return self::choosePayment4Order( $ChB );

		} elseif ( $ChB->task === 'manychat_order_placeOrder' ) {

			return self::placeOrder( $ChB );

		} elseif ( $ChB->task === 'manychat_order_changePaymentOption4Order' ) {

			return self::changePaymentOption4Order( $ChB );

		}

		return [];
	}

	//--Sizes and quantity-----------------------------------------------------------------------------

	public static function chooseVariation( ChatBot $ChB, $pa_on_buy_button = null, $events = null ) {

		if ( ! empty( $ChB->getParam( 'var_id' ) ) ) {
			return self::chooseQuantity( $ChB );
		}

		$var_data = ChB_Catalogue::getProductVariationsData( $ChB->getParam( 'product_id' ), $ChB->getParam( 'pa_filter' ), $ChB->promo );
		if ( empty( $var_data['product_type'] ) ) {
			return [];
		}

		if ( $var_data['product_type'] === 'simple' ) {
			$ChB->setParam( 'var_id', $ChB->getParam( 'product_id' ) );

			return self::chooseQuantity( $ChB );
		}
		if ( ! empty( $var_data['auto_selected_var_id'] ) ) {
			$ChB->setParam( 'var_id', $var_data['auto_selected_var_id'] );

			return self::chooseQuantity( $ChB );
		}

		if ( ! $events ) {
			$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_CHOOSE_VAR );
			$events     = empty( $event_tags ) ? [] : ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_CHOOSE_VAR ], $event_tags );
		}

		$buttons  = [];
		$messages = [];
		//if everything is correct then ($show_prices === true) <=> ($pa_on_buy_button !== null)
		$show_prices = $var_data['is_last_attr'] && ( $var_data['next_attr_slug'] === $pa_on_buy_button );

		foreach ( $var_data['next_attr_values'] as $next_attr_value_key => $next_attr_value ) {

			if ( ! $ChB->getParam( 'pa_filter' ) ) {
				$cur_pa_filter = [ $var_data['next_attr_slug'] => $next_attr_value_key ];
			} else {
				$cur_pa_filter                                = $ChB->getParam( 'pa_filter' );
				$cur_pa_filter[ $var_data['next_attr_slug'] ] = $next_attr_value_key;
			}

			$button_caption     = ChB_Lang::translate( ChB_Lang::LNG0176 ) . $next_attr_value['name'];
			$price_message_text = '';

			if ( $var_data['is_last_attr'] ) {
				if ( $show_prices ) {
					$button_caption     = ChB_Lang::translate( ChB_Lang::LNG0042 ) . ' ' . $next_attr_value['name'];
					$price_message_text = '*' . $next_attr_value['name'] . "*\n" . $next_attr_value['price_details']['all_str'];
					if ( ChB_Settings()->getParam( 'use_cod' ) && ! empty( ChB_Settings()->getParam( 'product_view_settings' )['element']['show_cod'] ) ) {
						$price_message_text .= "\n" . '[' . ChB_Lang::translate( ChB_Lang::LNG0064 ) . ']';
					}
				}
				$button = ChatBot::makeDynamicBlockCallbackButton( $ChB, $button_caption,
					[
						'task'       => 'manychat_order_chooseQuantity',
						'product_id' => $ChB->getParam( 'product_id' ),
						'var_id'     => $next_attr_value['var_id'],
						'pa_filter'  => $cur_pa_filter,
						'evt'        => $events
					] );
			} else {
				$button = ChatBot::makeDynamicBlockCallbackButton( $ChB, $button_caption,
					[
						'task'       => 'manychat_order_chooseVariation',
						'product_id' => $ChB->getParam( 'product_id' ),
						'pa_filter'  => $cur_pa_filter,
						'evt'        => $events
					] );
			}
			if ( $show_prices ) {
				$messages[] = [
					'type'    => 'text',
					'text'    => $price_message_text,
					'buttons' => [ $button ]
				];
			} else {
				$buttons[] = $button;
			}
		}

		if ( ! $show_prices ) {
			$text     = ChB_Lang::translate( ChB_Lang::LNG0005 ) . ' ' . strtolower( $var_data['next_attr_name'] ) . ':';
			$messages = ChB_Common::makeManyButtons( $buttons, $text );
		}

		if ( $pa_on_buy_button ) {
			return $messages;
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function chooseQuantity( ChatBot $ChB ) {

		$available_quantity = ChB_Order::getAvailableStock( $ChB->getParam( 'var_id' ) );

		if ( empty( $available_quantity ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0040 ), true );
		}

		//if $ChB->item_id is not empty then we are here to change quantity
		$item_id = $ChB->getParam( 'item_id' );
		if ( ! $item_id && ChB_Settings()->getParam( 'use_default_quantity' ) ) {
			$ChB->setParam( 'qty', min( $available_quantity, ChB_Settings()->getParam( 'use_default_quantity' ) ) );

			return self::updateCart( $ChB );
		}

		$q_step                    = ChB_Settings()->getParam('q_step');
		$offset                    = $ChB->getParam( 'offset' );
		$lim                       = ( ChB_Settings()->getParam( 'max_input_quantity' ) ? min( $available_quantity, ChB_Settings()->getParam( 'max_input_quantity' ) ) : $available_quantity );
		$cur_lim                   = min( $offset + $q_step, $lim );
		$show_more_quantity_button = ( $lim > $offset + $q_step );

		$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_CHOOSE_QUANTITY );
		$events     = empty( $event_tags ) ? [] : ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_CHOOSE_QUANTITY ], $event_tags );

		$buttons = [];
		for ( $q = $offset + 1; $q <= $cur_lim; $q ++ ) {
			$url_pars = [
				'task'       => 'manychat_order_updateCart',
				'product_id' => $ChB->getParam( 'product_id' ),
				'var_id'     => $ChB->getParam( 'var_id' ),
				'pa_filter'  => $ChB->getParam( 'pa_filter' ),
				'qty'        => $q,
				'evt'        => $events
			];
			if ( $item_id ) {
				$url_pars['item_id'] = $item_id;
			}
			if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER ) ) {
				$url_pars['view']         = $ChB->getParam( 'view' );
				$url_pars['cart_version'] = $ChB->cart->getCartVersion();
			}
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0176 ) . $q, $url_pars );
		}

		//quantity paging
		if ( $show_more_quantity_button ) {
			$url_pars = [
				'task'       => 'manychat_order_chooseQuantity',
				'product_id' => $ChB->getParam( 'product_id' ),
				'var_id'     => $ChB->getParam( 'var_id' ),
				'offset'     => $offset + $q_step,
				'pa_filter'  => $ChB->getParam( 'pa_filter' ),
				'evt'        => $events
			];

			if ( $item_id ) {
				$url_pars['item_id'] = $item_id;
			}
			if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER ) ) {
				$url_pars['view']         = $ChB->getParam( 'view' );
				$url_pars['cart_version'] = $ChB->cart->getCartVersion();
			}

			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0116 ), $url_pars );
		}


		$messages = ChB_Common::makeManyButtons( $buttons, ChB_Lang::translate( ChB_Lang::LNG0041 ) );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function chooseShipping( ChatBot $ChB ) {

		$ChB->cart->getCartDetails( $ChB->promo );//to init cart totals
		$shipping_items = ChB_ShippingOption::getAvailableShipping4Cart( $ChB->cart );

		if ( count( $shipping_items ) < 2 ) {
			if ( count( $shipping_items ) === 1 ) {
				$shipping_option_id = array_keys( $shipping_items )[0];
				$ChB->cart->setShippingOptionId( $shipping_option_id );
			}
			$ChB->setParam( 'view', [ ChB_Common::VIEW_CHECK_ADDR ] );

			return self::inputAddressPart( $ChB );
		}

		$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_CHOOSE_SHIPPING );
		$events     = empty( $event_tags ) ? [] : ChB_Analytics::packEvents4Url( [ ChB_Analytics::EVENT_CHOOSE_SHIPPING ], $event_tags );

		$messages = [];
		foreach ( $shipping_items as $shipping_option_id => $shipping_item ) {
			$messages[] = [
				'type'    => 'text',
				'text'    => $shipping_item->title . ( $shipping_item->price ? ': ' . ChB_Common::printPrice( $shipping_item->price ) : '' ),
				'buttons' => [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0123 ),
						[
							'task'               => 'manychat_order_inputAddressPart',
							'view'               => ChB_Common::VIEW_CHECK_ADDR,
							'shipping_option_id' => $shipping_option_id,
							'evt'                => $events
						] )
				]
			];
		}


		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function choosePayment( ChatBot $ChB ) {

		$ChB->cart->activateSavedShippingOptionId();
		$payment_options = ChB_PaymentOption::getAvailablePaymentOptions( $ChB->cart->getActiveShippingOptionId() );
		if ( count( $payment_options ) < 2 ) {
			if ( count( $payment_options ) === 1 ) {
				$payment_option_id = array_keys( $payment_options )[0];
				$ChB->cart->setPaymentOptionId( $payment_option_id );
			}

			return self::placeOrder( $ChB );
		}

		$messages = [];
		$ind      = 0;
		foreach ( $payment_options as $payment_option ) {
			$messages[] = [
				'type'    => 'text',
				'text'    => ( $ind ++ === 0 ? ChB_Lang::translate( ChB_Lang::LNG0126 ) . "\n\n" : '' ) . ChB_Common::getKeyCapEmoji( $ind ) . ' ' . $payment_option->title,
				'buttons' => [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, $payment_option->select_payment_button_title,
						[
							'task'              => 'manychat_order_placeOrder',
							'cart_version'      => $ChB->getParam( 'cart_version' ),
							'payment_option_id' => $payment_option->id
						] )
				]
			];
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function choosePayment4Order( ChatBot $ChB ) {

		$order_id      = $ChB->getParam( 'order_id' );
		$order_details = ChB_Order::getOrderDetails( $order_id );
		if ( ! $order_details || ! ( $order_details['shipping_details'] instanceof ChB_ShippingDetails ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB );
		}

		$payment_options = ChB_PaymentOption::getAvailablePaymentOptions( $order_details['shipping_details']->getShippingOptionId() );
		if ( ! $payment_options ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB );
		}

		$messages = [];
		$ind      = 0;
		foreach ( $payment_options as $payment_option ) {
			$messages[] = [
				'type'    => 'text',
				'text'    => ( $ind ++ === 0 ? ChB_Lang::translate( ChB_Lang::LNG0126 ) . "\n\n" : '' ) . $payment_option->title,
				'buttons' => [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, $payment_option->select_payment_button_title,
						[
							'task'              => 'manychat_order_changePaymentOption4Order',
							'order_id'          => $order_id,
							'payment_option_id' => $payment_option->id
						] )
				]
			];
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	//--Phone, Email, Address --------------------------------------------------------------------------------------

	public static function confirmAddressPartToUse( ChatBot $ChB, $value, $addr_part_yes, $addr_part_no, $header_phrase ) {

		return
			[
				'version' => 'v2',
				'content' => [
					'messages' => [
						[
							'type'    => 'text',
							'text'    => ChB_Lang::translateWithPars( $header_phrase, $value ),
							'buttons' => [
								ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0006 ), [
									'task'           => 'manychat_order_inputAddressPart',
									'prev_addr_part' => $addr_part_yes
								] ),
								ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0007 ), [
									'task'      => 'manychat_order_inputAddressPart',
									'addr_part' => $addr_part_no
								] ),
							]
						]
					]
				]
			];
	}

	public static function showAddressPartOptions( ChatBot $ChB, $addr_part_info ) {

		$buttons = [];
		foreach ( $addr_part_info['options'] as $option ) {
			$buttons[] =
				ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( $option['caption'] ), [
					'task'           => 'manychat_order_inputAddressPart',
					'prev_addr_part' => $addr_part_info['id'],
					'set_value'      => $option['value']
				] );
		}

		return
			[
				'version' => 'v2',
				'content' => [
					'messages' => ChB_Common::makeManyButtons( $buttons, ChB_Lang::translate( $addr_part_info['text'] ) )
				]
			];
	}

	public static function inputAddressPart( ChatBot $ChB ) {

		if ( $cart_shipping_option_id = $ChB->getParam( 'shipping_option_id' ) ) {
			$ChB->cart->setShippingOptionId( $cart_shipping_option_id );
		}
		$ChB->cart->activateSavedShippingOptionId();

		$addr_part      = $ChB->getParam( 'addr_part' );
		$prev_addr_part = $ChB->getParam( 'prev_addr_part' );

		$is_checkout_button = $ChB->viewHas( ChB_Common::VIEW_CHECK_ADDR );
		$shipping_details   = new ChB_ShippingDetails( $ChB->cart );

		// user selected an option (not free text input), saving value to attr
		if ( $prev_addr_part && $set_value = $ChB->getParam( 'set_value' ) ) {
			$shipping_details->validateAndSaveAddrPartValue( $prev_addr_part, $set_value );
		}

		// Pushed checkout button and already has all address parts saved before
		if ( $is_checkout_button &&
		     $ChB->user->cartUserIsCurrentUser() &&
		     $shipping_details->checkUserHasAllAddressParts()
		) {

			$ChB->addView( ChB_Common::VIEW_PLACING_ORDER );

			return self::updateCart( $ChB );
		}
		if ( $addr_part ) {
			$force_input = true;
		} else {
			$force_input = false;
			$addr_part   = $shipping_details->getNextAddressPart( $prev_addr_part );
		}

		// address input is done
		if ( ! $addr_part ) {
			$ChB->addView( ChB_Common::VIEW_PLACING_ORDER );

			return self::updateCart( $ChB );
		}

		$text = '';

		if ( $shipping_details->addressPartIsInGroup( $addr_part, ChB_Common::ADDR_GROUP_NAME ) ) {

			$display_name    = $shipping_details->getDisplayName();
			$addr_final_part = $shipping_details->getAddressGroupFinalPart( ChB_Common::ADDR_GROUP_NAME );
			if ( $display_name && $is_checkout_button && ! $force_input ) {

				//skipping name input, if it is checkout button and we have display name for user
				//name can be changed later - before confirming order
				$addr_part = $shipping_details->getNextAddressPart( $addr_final_part );

			} else {
				$existing_value_to_use = $force_input ? null : $display_name;

				if ( $existing_value_to_use ) {
					return self::confirmAddressPartToUse( $ChB,
						$existing_value_to_use,
						$addr_final_part,
						$addr_part,
						ChB_Lang::LNG0167 );
				}

				if ( $addr_part === ChB_Common::ADDR_PART_FIRST_NAME ) {
					$text = ChB_Lang::translate( ChB_Lang::LNG0168 );
				} elseif ( $addr_part === ChB_Common::ADDR_PART_LAST_NAME ) {
					$text = ChB_Lang::translate( ChB_Lang::LNG0169 );
				} else {
					$addr_part_info = $shipping_details->getAddressPartInfo( $addr_part );
					if ( ! empty( $addr_part_info['options'] ) ) {
						return self::showAddressPartOptions( $ChB, $addr_part_info );
					} else {
						$text = ChB_Lang::translate( ChB_Lang::convertAssoc( $addr_part_info['text'] ) );
					}
				}

				if ( $addr_final_part !== $addr_part ) {
					//to force_input on the next step
					$next_add_part = $shipping_details->getNextAddressPart( $addr_part );
				}
			}
		}

		if ( $addr_part === ChB_Common::ADDR_PART_PHONE ) {

			$existing_value_to_use = $force_input ? null : $shipping_details->getPhone();
			if ( $existing_value_to_use ) {
				return self::confirmAddressPartToUse( $ChB,
					$existing_value_to_use,
					ChB_Common::ADDR_PART_PHONE,
					ChB_Common::ADDR_PART_PHONE,
					ChB_Lang::LNG0118 );
			}
			$ChB->addView( ChB_Common::VIEW_PHONE_INPUT );
			$text           = ChB_Lang::translate( ChB_Lang::LNG0120 );
			$error_callback = [
				'task' => 'manychat_cmn_sendTextMessage',
				'val'  => ChB_Lang::getKeyByPhrase( ChB_Lang::LNG0028 ),
				'view' => ChB_Common::VIEW_TRANSLATE_VAL . '.' . ChB_Common::VIEW_PHONE_INPUT
			];
		} elseif ( $addr_part === ChB_Common::ADDR_PART_EMAIL ) {

			$existing_value_to_use = $force_input ? null : $shipping_details->getEmail();
			if ( $existing_value_to_use ) {
				return self::confirmAddressPartToUse( $ChB,
					$existing_value_to_use,
					ChB_Common::ADDR_PART_EMAIL,
					ChB_Common::ADDR_PART_EMAIL,
					ChB_Lang::LNG0162 );
			}

			$ChB->addView( ChB_Common::VIEW_EMAIL_INPUT );
			$text = ChB_Lang::translate( ChB_Lang::LNG0163 );

			$error_callback = [
				'task' => 'manychat_cmn_sendTextMessage',
				'val'  => ChB_Lang::getKeyByPhrase( ChB_Lang::LNG0164 ),
				'view' => ChB_Common::VIEW_TRANSLATE_VAL . '.' . ChB_Common::VIEW_EMAIL_INPUT
			];
		} elseif ( $shipping_details->addressPartIsInGroup( $addr_part, ChB_Common::ADDR_GROUP_ADDRESS ) ) {

			$existing_value_to_use = $force_input ? null : $shipping_details->getConcatAddressLine();
			$addr_final_part       = $shipping_details->getAddressGroupFinalPart( ChB_Common::ADDR_GROUP_ADDRESS );
			if ( $existing_value_to_use ) {
				return self::confirmAddressPartToUse( $ChB,
					$existing_value_to_use,
					$addr_final_part,
					$addr_part,
					ChB_Lang::LNG0122 );
			}

			$error_text = '';
			if ( $addr_part === ChB_Common::ADDR_PART_COUNTRY ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0158 );
			} elseif ( $addr_part === ChB_Common::ADDR_PART_STATE ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0161 );
			} elseif ( $addr_part === ChB_Common::ADDR_PART_CITY ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0159 );
			} elseif ( $addr_part === ChB_Common::ADDR_PART_POSTCODE ) {
				$text = ChB_Lang::translate( ChB_Lang::LNG0160 );
			} elseif ( $addr_part === ChB_Common::ADDR_PART_ADDRESS_LINE ) {
				$text       = ChB_Lang::translate( ChB_Lang::LNG0124 );
				$error_text = ChB_Lang::getKeyByPhrase( ChB_Lang::LNG0125 );
			} else {
				$addr_part_info = $shipping_details->getAddressPartInfo( $addr_part );
				if ( ! empty( $addr_part_info['options'] ) ) {
					return self::showAddressPartOptions( $ChB, $addr_part_info );
				} else {
					$text = ChB_Lang::translate( ChB_Lang::convertAssoc( $addr_part_info['text'] ) );
				}
			}

			if ( $addr_final_part !== $addr_part ) {
				//to force_input on the next step
				$next_add_part = $shipping_details->getNextAddressPart( $addr_part );
			}

			if ( $error_text ) {
				$error_callback =
					[
						'task' => 'manychat_cmn_sendTextMessage',
						'val'  => $error_text,
						'view' => ChB_Common::VIEW_TRANSLATE_VAL
					];
			}

		} elseif ( $shipping_details->addressPartIsAdditional( $addr_part ) ) {
			$addr_part_info        = $shipping_details->getAddressPartInfo( $addr_part );
			$existing_value_to_use = ( $force_input || ! empty( $addr_part_info['force_input'] ) ) ? null : $shipping_details->printAddrPartValue( $addr_part_info );
			if ( $existing_value_to_use ) {
				return self::confirmAddressPartToUse( $ChB,
					$existing_value_to_use,
					$addr_part,
					$addr_part,
					ChB_Lang::convertAssoc( $addr_part_info['confirm_text'] ) );
			}

			if ( ! empty( $addr_part_info['options'] ) ) {
				return self::showAddressPartOptions( $ChB, $addr_part_info );
			} else {
				$text = ChB_Lang::translate( ChB_Lang::convertAssoc( $addr_part_info['text'] ) );
			}

			if ( ! empty( $addr_part_info['error_text_hook'] ) ) {
				$error_callback =
					[
						'task' => 'manychat_cmn_sendTextMessage',
						'val'  => $addr_part_info['error_text_hook'],
						'view' => ChB_Common::VIEW_TRANSLATE_HOOK
					];
			}

		}

		$validation_callback = [ 'addr_part' => $addr_part ];
		$success_callback    = [ 'task' => 'manychat_order_inputAddressPart', 'prev_addr_part' => $addr_part ];
		if ( ! empty( $next_add_part ) ) {
			$success_callback['addr_part'] = $next_add_part;
		}

		return ChB_FLOW_Common::startFreeInputText(
			$ChB,
			$text,
			$validation_callback,
			$success_callback,
			isset( $error_callback ) ? $error_callback : null,
			[
				'task' => 'manychat_cmn_sendTextMessage',
				'val'  => ChB_Lang::getKeyByPhrase( ChB_Lang::LNG0121 ),
				'view' => ChB_Common::VIEW_TRANSLATE_VAL
			],
			30,
			2
		);
	}


	//--Order--------------------------------------------------------------------------------------

	public static function updateCart( ChatBot $ChB ) {

		$ChB->user->finishFreeInput();
		//ChB_Pixel::schedulePixelEvent($ChB, $ChB->wp_user->ID, ChB_Pixel::EVENT_ADD_TO_CART, $ChB->product_id, $sizes['min_size']['price'], null);

		if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER ) && $ChB->cart->getCartVersion() != $ChB->getParam( 'cart_version' ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0077 ), true );
		}

		$event_tags = ChB_Analytics::getParentEventsTags( $ChB->events, ChB_Analytics::EVENT_CONFIRM_ORDER );

		$ChB->cart->updateCart( $ChB->getParam( 'var_id' ), $ChB->getParam( 'pa_filter' ), $ChB->getParam( 'item_id' ), $ChB->getParam( 'qty' ), $event_tags );
		$ChB->setParam( 'cart_version', $ChB->cart->getCartVersion() );


		// skipping checkout button: add to cart -> address
		if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_CHECK_ADDR ) && ! $ChB->cart->isEmpty() ) {
			$ChB->setParam( 'view', [ ChB_Common::VIEW_CHECK_ADDR ] );

			return self::inputAddressPart( $ChB );
		}

		// editing cart in "place order" view
		if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER ) && ! $ChB->cart->isEmpty() ) {
			$ChB->setParam( 'view', [ ChB_Common::VIEW_PLACING_ORDER ] );
		}

		return self::openCart( $ChB );//main scenario
	}

	public static function clearCartYN( ChatBot $ChB ) {

		$messages = [
			[
				'type'    => 'text',
				'text'    => ChB_Lang::translate( ChB_Lang::LNG0089 ),
				'buttons' => [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0006 ), [ 'task' => 'manychat_order_clearCart' ] ),
					ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0007 ), [ 'task' => 'manychat_order_openCart' ] )
				]
			]
		];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function clearCart( ChatBot $ChB ) {

		if ( $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER ) && $ChB->cart->getCartVersion() != $ChB->getParam( 'cart_version' ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0077 ), true );
		}

		$ChB->cart->clearCart();
		$ChB->setParam( 'cart_version', $ChB->cart->getCartVersion() );

		return ChB_FLOW_NewOrder::openCart( $ChB );
	}

	public static function makeCheckoutButton( ChatBot $ChB, $cart_details ) {

		$event_tags = ChB_Analytics::mergeEventTags( $cart_details['products_details'] );

		$button = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0084 ),
			[
				'task' => 'manychat_order_chooseShipping',
				'evt'  => ChB_Analytics::packEvent4Url( ChB_Analytics:: EVENT_CHECKOUT, [ 'tags' => $event_tags ] )
			] );

		return apply_filters( 'wany_hook_custom_checkout_button',
			$button,
			$ChB
		);
	}

	public static function makePlaceOrderButton( ChatBot $ChB, $cart_details ) {

		if ( ChB_Settings()->getParam( 'web_redirect' ) == ChB_Settings::SETTING_WEB_REDIRECT_PLACE_ORDER ) {
			$action_url = add_query_arg( ChB_WYSession::GET_PAR_CONNECT_TO_BOT_USER,
				ChB_WYSession::encodeBotUserForGetPar( $ChB->user->wp_user_id, ChB_WYSession::TASK_CHECKOUT ),
				wc_get_checkout_url() );

			$button = [
				'type'    => 'url',
				'url'     => $action_url,
				'caption' => ChB_Lang::translate( ChB_Lang::LNG0174 )
			];


		} else {
			$button = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0025 ),
				[
					'task'         => 'manychat_order_choosePayment',
					'cart_version' => $cart_details['version']
				] );
		}

		return apply_filters( 'wany_hook_custom_place_order_button', $button, $ChB );
	}

	public static function makeChoosePayment4OrderButton( ChatBot $ChB, $order_id, $title, $shipping_option_id ) {
		$payment_options = ChB_PaymentOption::getAvailablePaymentOptions( $shipping_option_id );
		if ( count( $payment_options ) > 1 ) {
			return ChatBot::makeDynamicBlockCallbackButton( $ChB, $title,
				[
					'task'     => 'manychat_order_choosePayment4Order',
					'order_id' => $order_id
				] );
		}

		return null;
	}

	public static function openCart( ChatBot $ChB, $out_message = null ) {

		$show_full_cart_details = $ChB->viewHas( ChB_Common::VIEW_CART_FULL_DETAILS );
		$edit_placing_order     = $ChB->viewHas( ChB_Common::VIEW_CART_EDIT_PLACING_ORDER );
		$is_placing_order       = $ChB->viewHas( ChB_Common::VIEW_PLACING_ORDER ) || $edit_placing_order;
		$is_cart                = ! $is_placing_order;

		if ( $is_placing_order ) {
			$ChB->cart->activateSavedShippingOptionId();
		}
		$cart_details = $ChB->cart->getCartDetails( $ChB->promo );

		if ( $edit_placing_order && ( $cart_details['version'] != $ChB->getParam( 'cart_version' ) ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0077 ), true );
		}

		if ( ! empty( $out_message ) ) {
			$messages[] = [ 'type' => 'text', 'text' => $out_message ];
		}

		if ( ! empty( $cart_details['out_messages'] ) ) {
			$messages[] = [ 'type' => 'text', 'text' => implode( chr( 10 ), $cart_details['out_messages'] ) ];
		}

		$buttons = [];
		if ( $is_cart ) {
			if ( ! $show_full_cart_details ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0086 ), [
					'task' => 'manychat_order_openCart',
					'view' => ChB_Common::VIEW_CART_FULL_DETAILS
				] );
			}

			if ( $show_full_cart_details && ! $ChB->cart->isEmpty() ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0091 ), [ 'task' => 'manychat_order_clearCartYN' ] );
			}
		}

		$messages[] = [
			'type'               => 'cards',
			'image_aspect_ratio' => $cart_details['grp_img']['aspect'],
			'elements'           =>
				[
					[
						'title'     => ( $is_cart ? ChB_Lang::translate( ChB_Lang::LNG0085 ) : ChB_Lang::translate( ChB_Lang::LNG0102 ) ),
						'subtitle'  => ChB_Lang::translateWithPars( $is_cart ? ChB_Lang::LNG0088 : ChB_Lang::LNG0104, ChB_Common::printNumberNoSpaces( $cart_details['num_of_lines'] ) ),
						'image_url' => $cart_details['grp_img']['url'],
						'buttons'   => $buttons
					]
				]
		];

		$show_edit_buttons     = $show_full_cart_details || $edit_placing_order;
		$edit_buttons_url_pars = ( $edit_placing_order ? [
			'view'         => ChB_Common::VIEW_CART_EDIT_PLACING_ORDER,
			'cart_version' => $cart_details['version']
		] : null );

		if ( $show_full_cart_details || $is_placing_order ) {
			self::printProductsDetails( $ChB, $cart_details['products_details'], $messages, false, $show_edit_buttons, $edit_buttons_url_pars );
		}

		$buttons = [];

		if ( $is_cart ) {
			if ( ! empty( $cart_details['list_discount']['next_conditions'] ) ) {
				$messages[] = [
					'type' => 'text',
					'text' => $cart_details['list_discount']['next_conditions']
				];
			}

			if ( ! empty( $cart_details['list_discount']['banner_url'] ) ) {
				$messages[] = [
					'type' => 'image',
					'url'  => $cart_details['list_discount']['banner_url']
				];
			}

			$buttons[] = ChB_FLOW_Catalogue::getCatalogButton( $ChB, ChB_Lang::LNG0032 );

			if ( ! $ChB->cart->isEmpty() ) {
				$buttons[] = self::makeCheckoutButton( $ChB, $cart_details );
			}
		}

		if ( $is_placing_order ) {
			$text = null;
			if ( ! empty( $cart_details['shipping_details'] ) && $cart_details['shipping_details'] instanceof ChB_ShippingDetails ) {
				$text = $cart_details['shipping_details']->printContactInfo();
			}

			if ( $text ) {
				$messages[] = [
					'type'    => 'text',
					'text'    => $text,
					'buttons' => [
						ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0166 ),
							[
								'task' => 'manychat_order_inputAddressPart',
							] )
					]
				];
			}

			if ( ! $edit_placing_order ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0026 ),
					[
						'task'         => 'manychat_order_openCart',
						'view'         => ChB_Common::VIEW_CART_EDIT_PLACING_ORDER,
						'cart_version' => $cart_details['version']
					] );
			}

			$buttons[] = self::makePlaceOrderButton( $ChB, $cart_details );
		}

		ChB_FLOW_MyOrders::printTotals( $cart_details, $messages, $buttons );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function placeOrder( ChatBot $ChB ) {

		$ChB->cart->activateSavedShippingOptionId();
		$user         = $ChB->user->getCartUser();
		$cart_details = $ChB->cart->getCartDetails( $ChB->promo );

		if ( ! $cart_details || $cart_details['version'] != $ChB->getParam( 'cart_version' ) ) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0077 ), true );
		}

		if ( ! $ChB->cart->actualizeCartStock() ) {
			// not enough stock - showing cart-order again
			$ChB->setParam( 'view', [ ChB_Common::VIEW_PLACING_ORDER ] );

			return self::openCart( $ChB );
		}

		if ( $payment_option_id = $ChB->getParam( 'payment_option_id' ) ) {
			$ChB->cart->setPaymentOptionId( $payment_option_id );
		}
		$shipping_option_id = $ChB->cart->getActiveShippingOptionId();
		$payment_option     = ChB_PaymentOption::getPaymentOptionById( $ChB->cart->getPaymentOptionId(), $shipping_option_id );

		$order_id = ChB_Order::createNewOrderFromCart( $cart_details, $payment_option, $user );

		if ( $order_id ) {

			$ChB->setParam( 'order_id', $order_id );
			$ChB->cart->clearCart( false );

			ChB_Pixel::schedulePixelEvent( $ChB, ChB_Pixel::EVENT_PURCHASE, null, null, $order_id );
			$messages = $payment_option->getMessagesOnOrderCreateUpdate( $ChB, $order_id, $shipping_option_id, true );

		} else {
			return ChB_FLOW_Common::somethingWentWrong( $ChB, ChB_Lang::translate( ChB_Lang::LNG0077 ), true );
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function changePaymentOption4Order( ChatBot $ChB ) {

		if ( ! $ChB->getParam( 'payment_option_id' ) || ! ( $order_id = $ChB->getParam( 'order_id' ) ) ||
		     ! ( $order_details = ChB_Order::getOrderDetails( $order_id ) ) ||
		     ! ( $order_details['shipping_details'] instanceof ChB_ShippingDetails )
		) {
			return ChB_FLOW_Common::somethingWentWrong( $ChB );
		}
		$shipping_option_id  = $order_details['shipping_details']->getShippingOptionId();
		$prev_payment_option = ChB_PaymentOption::getPaymentOptionById( isset( $order_details['payment_option_id'] ) ? $order_details['payment_option_id'] : null, $shipping_option_id );
		$payment_option      = ChB_PaymentOption::getPaymentOptionById( $ChB->getParam( 'payment_option_id' ), $shipping_option_id );

		if ( ChB_Order::changePaymentOption4Order( $order_id, $payment_option, $prev_payment_option ) ) {

			$messages = $payment_option->getMessagesOnOrderCreateUpdate( $ChB, $order_id, $shipping_option_id, false );

		} else {
			return ChB_FLOW_Common::somethingWentWrong( $ChB );
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	/**
	 * @param ChatBot $ChB
	 * @param \WC_Order|string $order
	 *
	 * @return array
	 */
	public static function getMessagesOnSuccessfulPayment( ChatBot $ChB, $order ) {

		$order_details = ChB_Order::getOrderDetails( $order, null );
		$messages      = [
			[
				'type' => 'text',
				'text' => ChB_Lang::translateWithPars( ChB_Lang::LNG0182, ChB_Common::printNumberNoSpaces( $order_details['order_id'] ) )
			]
		];

		return apply_filters( 'wany_hook_successful_payment_messages', $messages, $ChB, $order_details );
	}

	public static function printProductsDetails( ChatBot $ChB, &$products_details, &$messages, $print_imgs, $show_edit_buttons, $edit_buttons_url_pars ) {
		$ind = 1;
		foreach ( $products_details as $product_details ) {
			if ( $print_imgs ) {
				$messages[] = [
					'type' => 'image',
					'url'  => $product_details['image_url']
				];
			}

			$text = ChB_Common::getKeyCapEmoji( $ind ++ ) . ' ';
			$text .= $product_details['title'] . chr( 10 ) . 'SKU: ' . $product_details['SKU'] . "\n" .
			         ( ! empty( $product_details['attrs_str'] ) ? $product_details['attrs_str'] . "\n" : '' ) .
			         ChB_Lang::translate( ChB_Lang::LNG0020 ) . ' ' . $product_details['quantity'] . "\n";

			if ( $product_details['total'] != $product_details['subtotal'] ) {
				$text .= ChB_Lang::translate( ChB_Lang::LNG0054 ) . ' ' . ChB_Common::printPrice( $product_details['subtotal'], true ) . chr( 10 ) .
				         ChB_Lang::translate( ChB_Lang::LNG0055 ) . ' ' . ChB_Common::printPrice( $product_details['total'] );
			} else {
				$text .= ChB_Lang::translate( ChB_Lang::LNG0022 ) . ' ' . ChB_Common::printPrice( $product_details['total'] );
			}

			$text = ChB_Lang::maybeForceRTL( $text );

			$message = [
				'type' => 'text',
				'text' => $text
			];

			if ( $show_edit_buttons ) {
				$buttons  = [];
				$url_pars = [
					'task'       => 'manychat_order_chooseQuantity',
					'product_id' => $product_details['product_id'],
					'var_id'     => $product_details['var_id'],
					'item_id'    => $product_details['item_id'],
					'pa_filter'  => $product_details['pa_filter']
				];
				if ( ! empty( $edit_buttons_url_pars ) ) {
					$url_pars = array_merge( $url_pars, $edit_buttons_url_pars );
				}
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0087 ), $url_pars );

				$url_pars = [
					'task'       => 'manychat_order_updateCart',
					'product_id' => $product_details['product_id'],
					'var_id'     => $product_details['var_id'],
					'item_id'    => $product_details['item_id'],
					'qty'        => 0,
					'pa_filter'  => $product_details['pa_filter']
				];
				if ( ! empty( $edit_buttons_url_pars ) ) {
					$url_pars = array_merge( $url_pars, $edit_buttons_url_pars );
				}
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::translate( ChB_Lang::LNG0090 ), $url_pars );

				$message['buttons'] = $buttons;
			}

			$messages[] = $message;
		}
	}
}
