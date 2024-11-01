<?php


namespace ChatBot;


class ChB_FLOW_Manager {

	public static function run( ChatBot $ChB ) {
		if ( $ChB->task === 'manychat_manager_talkToHuman' )//action
		{
			return self::notifyManagersToTalkToHuman( $ChB );
		}

		if ( $ChB->task === 'manychat_manager_connectShopManager' ) {
			return self::sendManageNotificationsMenu( $ChB, null, true );
		}

		if ( ! ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) ) {
			return self::accessDenied();

		} elseif ( $ChB->task === 'manychat_manager_sendManageNotificationsMenu' ) {

			return self::sendManageNotificationsMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_editNotificationsList' ) {

			return self::editNotificationsList( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendNotificationMenu' ) {

			return self::sendNotificationMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendManagerMenu' ) {

			return self::sendManagerMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendFindCustomers' ) {

			return self::sendFindCustomers( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendFindCustomerInput' ) {

			return self::sendFindCustomerInput( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_markCustomer' ) {

			return self::markCustomer( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_unmarkCustomer' ) {

			return self::unmarkCustomer( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_markProduct' ) {

			return self::markProduct( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_unmarkProduct' ) {

			return self::unmarkProduct( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_showMarkedProducts' ) {

			return self::showMarkedProducts( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendMarkedProducts' ) {

			return self::sendMarkedProducts( $ChB, false );

		} elseif ( $ChB->task === 'manychat_manager_markCatSizeYN' ) {

			return self::markCategorySizeYN( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_markCatChooseSize' ) {

			return self::markCategoryChooseSize( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_markCat' ) {

			return self::markCategory( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_unmarkCat' ) {

			return self::unmarkCategory( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_showMarkedCat' ) {

			return self::showMarkedCategory( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_sendMarkedCat' ) {

			return self::sendMarkedProducts( $ChB, true );

		} elseif ( $ChB->task === 'manychat_manager_copyCustomerCart' ) {

			return self::copyCustomerCart( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getOrdersMenu' ) {

			return self::getOrdersMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getOrdersToConfirm2Ship' ) {

			return self::getOrdersToConfirm( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getOrdersToShip' ) {

			return self::getOrdersToShip( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getOrdersToComplete' ) {

			return self::getOrdersToComplete( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_setOrderStatus' ) {

			return self::setOrderStatus( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getAllUnansweredNotifications' ) {

			return self::getAllUnansweredNotifications( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_deleteNotification' ) {

			return self::deleteNotification( $ChB );

		} elseif ( $ChB->task === 'manychat_manager_getCustomerStats' ) {

			return self::getCustomerStats( $ChB );
		}

		return [];
	}

	public static function accessDenied() {
		$response = [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'text' => ';)', 'type' => 'text' ] ] ] ];
		ChB_Common::my_log( 'ChB_FLOW_Manager access denied' );

		return $response;
	}

	public static function sendManageNotificationsMenu( ChatBot $ChB, $custom_messages = null, $connect_shop_manager = false ) {
		$messages = empty( $custom_messages ) ? [] : $custom_messages;

		$is_shop_manager = ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user );
		$text            = '';
		if ( $connect_shop_manager && $ChB->getParam( 'val' ) ) {
			if ( $is_shop_manager ) {
				$text            = 'You are a shop manager already ;)';
				$is_shop_manager = true;
			} elseif ( ChB_Manager_Settings::setUserAsShopManager( $ChB->user, $ChB->getParam( 'val' ) ) ) {
				$text            = 'Congrats! You are successfully connected as a shop manager 😘';
				$is_shop_manager = true;
			} else {
				$is_shop_manager = false;
				$text            = "Sorry, wrong passphrase!\nCannot connect you ;)";
			}
		}

		if ( $text ) {
			$messages[] = [ 'type' => 'text', 'text' => $text ];
		}

		if ( $is_shop_manager ) {
			$messages[] = [ 'type' => 'text', 'text' => '--NOTIFICATIONS SETUP MENU--' ];

			$lists = ChB_Settings::getNotificationsLists( $ChB->user->fb_user_id );
			foreach ( $lists as $list_id => $list_info ) {
				if ( $list_info['subscriber_is_in'] ) {
					$view        = 'remove';
					$button_text = '👆 Unsubscribe me';
				} else {
					$view        = 'add';
					$button_text = '👆 Subscribe me';
				}

				$buttons    = [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, $button_text, [
						'task' => 'manychat_manager_editNotificationsList',
						'val'  => $list_id,
						'view' => $view,
					] )
				];
				$messages[] = [
					'text'    => '👉 ' . $list_info['desc'],
					'type'    => 'text',
					'buttons' => $buttons
				];
			}
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function editNotificationsList( ChatBot $ChB ) {
		$add     = $ChB->viewHas( 'add' );
		$list_id = $ChB->getParam( 'val' );
		$res     = ChB_Settings()->changeNotificationsList( $list_id, $ChB->user->fb_user_id, $add );
		$ChB->setParam( 'val', null );
		if ( $res ) {
			$lists = ChB_Settings::getNotificationsLists( $ChB->user->fb_user_id );

			$text = ( $add ? 'You are successfully subscribed to ' : 'You are successfully unsubscribed from ' )
			        . strtolower( $lists[ $list_id ]['desc'] );
		} else {
			$text = 'Oops! Something went wrong!';
		}

		$messages = [ [ 'type' => 'text', 'text' => $text ] ];

		return self::sendManageNotificationsMenu( $ChB, $messages );
	}

	public static function sendNotificationMenu( ChatBot $ChB, $custom_messages = null ) {
		$messages = empty( $custom_messages ) ? [] : $custom_messages;

		$buttons    = [
			ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark Customer ★', [
				'task'           => 'manychat_manager_markCustomer',
				'cus_fb_user_id' => $ChB->getParam( 'cus_fb_user_id' )
			] )
		];
		$messages[] = [
			'text'    => 'To send products please mark ★ this customer',
			'type'    => 'text',
			'buttons' => $buttons
		];

		if ( $ChB->getParam( 'product_ids' ) ) {
			list( $messages_prod ) = ChB_FLOW_Catalogue::getProductsGallery( $ChB, [ 'product_ids' => $ChB->getParam( 'product_ids' ) ], $ChB->promo, $ChB->getParam( 'text_over_image' ), null );
			if ( ! empty( $messages_prod ) ) {
				$messages[] = $messages_prod[0];
			}
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function markCustomer( ChatBot $ChB ) {

		$ChB->user->getManagerSettings()->markCustomer( $ChB->getParam( 'cus_fb_user_id' ) );

		return self::sendManagerMenu( $ChB );
	}

	public static function sendManagerMenu( ChatBot $ChB ) {
		$text                = '--MANAGER MENU--';
		$customer_fb_user_id = $ChB->user->getManagerSettings()->getMarkedCustomer();
		$quick_replies       = [];

		$buttonFindCustomer = self::makeFindCustomerButton( $ChB );

		$buttonOrdersMenu = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Orders Menu', [ 'task' => 'manychat_manager_getOrdersMenu' ] );
		if ( ChB_Cart::copyCartFromCustomer( $ChB->user->wp_user_id, $customer_fb_user_id, true ) ) {
			$buttonCopyCart = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Copy Cart 🛒', [ 'task' => 'manychat_manager_copyCustomerCart' ] );
		} else {
			$buttonCopyCart = null;
		}

		if ( ! $customer_fb_user_id ) {
			$text       .= "\n" . 'No marked customer ☆';
			$messages[] = [
				'text'    => $text,
				'type'    => 'text',
				'buttons' => [ $buttonFindCustomer, $buttonOrdersMenu ]
			];
		} else {
			$display_name = ChB_User::getSubscriberDisplayName( $customer_fb_user_id );
			$text         .= "\n" . '👉 Customer "' . $display_name . '" (' . $customer_fb_user_id . ') is marked ★';

			$buttons = [ ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Customer Stats', [ 'task' => 'manychat_manager_getCustomerStats' ] ) ];
			if ( $buttonCopyCart ) {
				$buttons[] = $buttonCopyCart;
			}
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Unmark Customer ☆', [ 'task' => 'manychat_manager_unmarkCustomer' ] );

			$messages[] = [ 'text' => $text, 'type' => 'text', 'buttons' => $buttons ];

			$text       = 'or..';
			$buttons    = [
				$buttonFindCustomer,
				$buttonOrdersMenu,
			];
			$messages[] = [ 'text' => $text, 'type' => 'text', 'buttons' => $buttons ];

			$quick_replies[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Show marked products', [ 'task' => 'manychat_manager_showMarkedProducts' ] );
			$quick_replies[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Send marked products', [
				'task'           => 'manychat_manager_sendMarkedProducts',
				'cus_fb_user_id' => $customer_fb_user_id
			] );
			$quick_replies[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Show marked category', [ 'task' => 'manychat_manager_showMarkedCat' ] );
			$quick_replies[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Send marked category', [
				'task'           => 'manychat_manager_sendMarkedCat',
				'cus_fb_user_id' => $customer_fb_user_id
			] );
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages, 'quick_replies' => $quick_replies ] ];

		return $response;
	}

	public static function unmarkCustomer( ChatBot $ChB ) {

		$ChB->user->getManagerSettings()->unmarkCustomer();

		return self::sendManagerMenu( $ChB );
	}

	public static function markProduct( ChatBot $ChB ) {

		$product_id = $ChB->getParam( 'product_id' );
		$ChB->user->getManagerSettings()->markProduct( $product_id );

		$messages[] = [
			'text' => ChB_Catalogue::getProductNameById( $product_id ) . ' marked ★',
			'type' => 'text'
		];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function unmarkProduct( ChatBot $ChB ) {

		$product_id = $ChB->getParam( 'product_id' );
		$ChB->user->getManagerSettings()->unmarkProduct( $product_id );

		$messages[] = [
			'text' => ChB_Catalogue::getProductNameById( $product_id ) . ' unmarked ☆',
			'type' => 'text'
		];

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function showMarkedProducts( ChatBot $ChB ) {
		$product_ids = $ChB->user->getManagerSettings()->getMarkedProducts();
		if ( $product_ids ) {
			list( $messages, $count ) = ChB_FLOW_Catalogue::getProductsGallery( $ChB, [ 'product_ids' => $product_ids ], $ChB->promo, $ChB->getParam( 'text_over_image' ), null );
			if ( $count && $customer_fb_user_id = $ChB->user->getManagerSettings()->getMarkedCustomer() ) {
				$quick_replies = [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Send marked products', [
						'task'           => 'manychat_manager_sendMarkedProducts',
						'cus_fb_user_id' => $customer_fb_user_id
					] )
				];
			}
		}

		if ( empty( $count ) ) {
			$messages[] = [ 'text' => ' No marked products', 'type' => 'text' ];
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
		if ( ! empty( $quick_replies ) ) {
			$response['content']['quick_replies'] = $quick_replies;
		}

		return $response;
	}

	public static function sendMarkedProducts( ChatBot $ChB, $is_send_cat ) {

		$is_ok       = true;
		$product_ids = [];
		$cat_slug    = null;
		$brand       = null;
		$size_slug   = null;

		if ( $is_send_cat ) {
			$cat = $ChB->user->getManagerSettings()->getMarkedCategory();
			if ( $cat ) {
				$cat_slug  = empty( $cat['cat_slug'] ) ? null : $cat['cat_slug'];
				$brand     = empty( $cat['brand'] ) ? null : $cat['brand'];
				$size_slug = empty( $cat['size_slug'] ) ? null : $cat['size_slug'];
			} else {
				$is_ok = false;
			}
		} else {
			$product_ids = $ChB->user->getManagerSettings()->getMarkedProducts();
			if ( ! $product_ids ) {
				$is_ok = false;
			}
		}

		if ( $is_ok ) {
			$customer_user = ChB_User::initUser( $ChB->getParam( 'cus_fb_user_id' ) );
			if ( $customer_user ) {
				$ChB_Customer = ChatBot::openTempChatBotSession( $customer_user );
			}

			if ( empty( $ChB_Customer ) ) {
				$text = 'Cannot send. Something went wrong ;)';
			} else {
				$res = ChB_FLOW_Catalogue::sendSelectedProducts( $ChB_Customer, false, $cat_slug, $brand, $size_slug, $product_ids, '⏳' );
				if ( ! $res ) {
					$text = 'Cannot send. Something went wrong ;)';
				} else {
					if ( ! is_array( $res ) ) {
						$res = json_decode( $res, true );
					}
					if ( isset( $res['status'] ) && $res['status'] == 'success' ) {
						$text = 'Marked products were sent ;)';
						if ( $is_send_cat ) {
							$ChB->user->getManagerSettings()->unmarkCategory();
						} else {
							$ChB->user->getManagerSettings()->unmarkAllProducts();
						}
					} elseif ( isset( $res['code'] ) && $res['code'] == 3011 ) {
						$text = 'Cannot send. Subscriber’s last interaction was more than 24 hours ago ;)';
					} else {
						$text = 'Cannot send. Something went wrong ;)';
					}
				}
			}

			$messages[] = [
				'text' => $text,
				'type' => 'text'
			];

			ChatBot::closeTempChatBotSession();
		} else {
			$messages[] = [
				'text' => 'No marked products',
				'type' => 'text'
			];
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function sendFindCustomerInput( ChatBot $ChB ) {
		return ChB_FLOW_Common::startFreeInputText(
			$ChB,
			'Please input name or user id:',
			'',
			[ 'task' => 'manychat_manager_sendFindCustomers', 'text_callback_par' => 'search' ],
			[ 'task' => 'manychat_cmn_sendTextMessage', 'val' => 'Ok, nevermind :)' ],
			[ 'task' => 'manychat_cmn_sendTextMessage', 'val' => 'Ok, nevermind ;)' ],
			5,
			0
		);
	}

	public static function makeFindCustomerButton( ChatBot $ChB ) {
		return ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Find Customer', [ 'task' => 'manychat_manager_sendFindCustomerInput' ] );
	}

	public static function sendFindCustomers( ChatBot $ChB ) {
		$wp_users = ChB_User::findWPUserIDs( $ChB->getParam( 'search' ) );
		if ( ! $wp_users ) {
			$messages[] = [ 'text' => 'No users found..', 'type' => 'text' ];
		} else {
			$limit = 20;
			if ( sizeof( $wp_users ) > $limit ) {
				$messages[] = [
					'text' => 'More than ' . $limit . ' customers found.. Showing first ' . $limit . ' results',
					'type' => 'text'
				];
			}

			$ind = 0;
			foreach ( $wp_users as $wp_user ) {
				if ( $ind ++ > $limit ) {
					break;
				}
				$customer_user = ChB_User::initUserByWPUserID( $wp_user->ID );
				if ( $customer_user ) {
					$buttons    = [
						ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark Customer ★', [
							'task'           => 'manychat_manager_markCustomer',
							'cus_fb_user_id' => $customer_user->fb_user_id
						] )
					];
					$messages[] = [
						'text'    => $customer_user->getUserDisplayName() . '(' . $customer_user->fb_user_id . ')',
						'type'    => 'text',
						'buttons' => $buttons
					];
				}
			}
		}

		$messages[] = [
			'text'    => 'You can try again ;)',
			'type'    => 'text',
			'buttons' => [ self::makeFindCustomerButton( $ChB ) ]
		];

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function markCategorySizeYN( ChatBot $ChB ) {

		$messages[] = [
			'text'    => 'Do you want to mark specific size?',
			'type'    => 'text',
			'buttons' => [
				ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Choose size', [
					'task'     => 'manychat_manager_markCatChooseSize',
					'cat_slug' => $ChB->getParam( 'cat_slug' ),
					'brand'    => $ChB->getParam( 'brand' )
				] ),
				ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 All sizes', [
					'task'      => 'manychat_manager_markCat',
					'cat_slug'  => $ChB->getParam( 'cat_slug' ),
					'brand'     => $ChB->getParam( 'brand' ),
					'size_slug' => ''
				] )
			]
		];

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function markCategoryChooseSize( ChatBot $ChB ) {

		$sizes   = ChB_Catalogue::getSizesFromCat( $ChB->getParam( 'cat_slug' ), $ChB->getParam( 'brand' ) );
		$buttons = [];
		foreach ( $sizes as $size_slug => $size_details ) {
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 ' . $size_details['title'],
				[
					'task'      => 'manychat_manager_markCat',
					'cat_slug'  => $ChB->getParam( 'cat_slug' ),
					'brand'     => $ChB->getParam( 'brand' ),
					'size_slug' => $size_slug
				] );
		}
		$messages = ChB_Common::makeManyButtons( $buttons, 'Please choose size:' );

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function markCategory( ChatBot $ChB ) {

		$ChB->user->getManagerSettings()->markCategory( $ChB->getParam( 'cat_slug' ), $ChB->getParam( 'brand' ), $ChB->getParam( 'size_slug' ) );

		$cat   = ChB_Catalogue::getProductCatBy( $ChB->getParam( 'cat_slug' ), 'slug', $ChB->getParam( 'brand' ) );
		$title = $cat->name;
		if ( $ChB->getParam( 'brand' ) ) {
			$title .= ', ' . $ChB->getParam( 'brand' );
		}
		if ( $ChB->getParam( 'size_slug' ) ) {
			$title .= ', size ' . ( ChB_Catalogue::getSizeDetails( $ChB->getParam( 'size_slug' ), true )['title'] );
		}

		$messages[] = [
			'text' => $title . ' marked ★',
			'type' => 'text'
		];

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function unmarkCategory( ChatBot $ChB ) {

		$ChB->user->getManagerSettings()->unmarkCategory();
		$cat = ChB_Catalogue::getProductCatBy( $ChB->getParam( 'cat_slug' ), 'slug', $ChB->getParam( 'brand' ) );

		$messages[] = [
			'text' => $cat->name . ' unmarked ☆',
			'type' => 'text'
		];

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function showMarkedCategory( ChatBot $ChB ) {

		$cat = $ChB->user->getManagerSettings()->getMarkedCategory();
		if ( $cat ) {
			$cat_slug  = empty( $cat['cat_slug'] ) ? null : $cat['cat_slug'];
			$brand     = empty( $cat['brand'] ) ? null : $cat['brand'];
			$size_slug = empty( $cat['size_slug'] ) ? null : $cat['size_slug'];

			$ChB->setParam( 'cat_slug', $cat_slug );
			$ChB->setParam( 'brand', $brand );
			$ChB->setParam( 'size_slug', $size_slug );

			$cat   = ChB_Catalogue::getProductCatBy( $cat_slug, 'slug', $brand );
			$title = 'Marked category ★: ' . $cat->name;
			if ( $brand ) {
				$title .= ', ' . $brand;
			}
			if ( $size_slug ) {
				$title .= ', size ' . ChB_Catalogue::getSizeDetails( $size_slug, true )['title'];
			}

			$args = [
				'cat_slug'  => $cat_slug,
				'brand'     => $brand,
				'size_slug' => $size_slug
			];

			list( $messages, $count ) = ChB_FLOW_Catalogue::getProductsGallery( $ChB, $args, $ChB->promo, $ChB->getParam( 'text_over_image' ), null );
			if ( $count ) {
				$messages[] = [ 'text' => $title, 'type' => 'text' ];
				if ( $customer_fb_user_id = $ChB->user->getManagerSettings()->getMarkedCustomer() ) {
					$quick_replies = [
						ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Send marked category', [
							'task'           => 'manychat_manager_sendMarkedCat',
							'cus_fb_user_id' => $customer_fb_user_id
						] )
					];
				}
			}
		}

		if ( empty( $count ) ) {
			$messages[] = [ 'text' => 'No marked category', 'type' => 'text' ];
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
		if ( ! empty( $quick_replies ) ) {
			$response['content']['quick_replies'] = $quick_replies;
		}

		return $response;
	}

	public static function makeMarkButton4Product( ChatBot $ChB, &$buttons, $product_id ) {

		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $ChB->user->getManagerSettings()->customerIsMarked() ) {
			if ( ! $ChB->user->getManagerSettings()->productIsMarked( $product_id ) ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark Product ★', [
					'task'       => 'manychat_manager_markProduct',
					'product_id' => $product_id
				] );
			} else {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Unmark Product ☆', [
					'task'       => 'manychat_manager_unmarkProduct',
					'product_id' => $product_id
				] );
			}
		}
	}

	public static function makeMarkButton4Category( ChatBot $ChB, &$buttons, $cat_slug, $brand ) {

		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $ChB->user->getManagerSettings()->customerIsMarked() ) {
			if ( ! $ChB->user->getManagerSettings()->categoryIsMarked( $cat_slug, $brand ) ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark Category ★', [
					'task'     => 'manychat_manager_markCatSizeYN',
					'cat_slug' => $cat_slug,
					'brand'    => $brand
				] );
			} else {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Unmark Category ☆', [
					'task'     => 'manychat_manager_unmarkCat',
					'cat_slug' => $cat_slug,
					'brand'    => $brand
				] );
			}
		}
	}

	public static function notifyManagersToTalkToHuman( ChatBot $ChB, $img_detect_data = null ) {

		$is_img_detect   = ! empty( $img_detect_data );
		$managers2notify = ! $is_img_detect ? ChB_Settings()->getParam( 'managers2notify_on_tth' ) : ChB_Settings()->getImageDetection( 'managers2notify' );

		if ( ! $is_img_detect ) {
			$ChB->user->setBlock4UserReminders();
		}

		if ( $ChB->viewHas( ChB_Common::VIEW_TTH_ON_PAYMENT ) ) {
			$text = 'wants to chat about payment';
		} elseif ( $is_img_detect ) {
			$text = 'sent the IMAGE';
		} elseif ( $ChB->getParam( 'product_ids' ) ) {
			$text = 'asked us about the product #' . implode( '#', $ChB->getParam( 'product_ids' ) );
		} else {
			$text = 'wants to talk to us';
		}

		$text = $ChB->user->getUserDisplayName() . '(' . $ChB->user->fb_user_id . ') ' . $text;

		foreach ( $managers2notify as $manager_id ) {

			if ( ChB_Settings()->getParam( 'notify_via_mc_flow' ) ) {
				self::sendNotificationViaManyChatFlow( $manager_id, $text, $ChB->user->mc_user_id );
			} else {
				$manager_user = ChB_User::initUser( $manager_id );
				if ( ! $manager_user || ! $manager_user->lastInteractionIsInside24H() ) {
					continue;
				}
				$ChB_Manager = ChatBot::openTempChatBotSession( $manager_user );

				$custom_messages = [
					[
						'type' => 'text',
						'text' => $text
					]
				];

				if ( $is_img_detect ) {
					if ( $manager_id == ChB_Settings()->getImageDetection( 'admin_id' ) ) {
						$custom_messages[] = [ 'type' => 'text', 'text' => json_encode( $img_detect_data['classes'] ) ];
					}

					if ( ! empty( $img_detect_data['output_img_url'] ) ) {
						$custom_messages[] = [ 'type' => 'image', 'url' => $img_detect_data['output_img_url'] ];
					} elseif ( ! empty( $img_detect_data['input_img_url'] ) ) {
						$custom_messages[] = [ 'type' => 'image', 'url' => $img_detect_data['input_img_url'] ];
					}
				}

				if ( $ChB->getParam( 'product_ids' ) ) {
					$ChB_Manager->setParam( 'product_ids', $ChB->getParam( 'product_ids' ) );
				}
				$ChB_Manager->setParam( 'cus_fb_user_id', $ChB->user->fb_user_id );

				$fields = [ 'data' => self::sendNotificationMenu( $ChB_Manager, $custom_messages ) ];
				ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB_Manager );
				ChatBot::closeTempChatBotSession();
			}
		}

		ChB_Notifications::registerQuestion( $ChB->user, $is_img_detect, ! $is_img_detect );

		return true;
	}

	public static function sendNotificationViaManyChatFlow( $subscriber_id, $text, $mc_user_id_for_livechat = null ) {

		if ( $mc_user_id_for_livechat ) {
			$text .= "\n\n🌍💬 " . ChB_ManyChat::getMCLiveChatLink( $mc_user_id_for_livechat );
		}

		ChB_ManyChat::sendPost2ManyChat( '/fb/subscriber/setCustomFieldByName', [
			'subscriber_id' => $subscriber_id,
			'field_name'    => ChB_ManyChat::CF_PAR1,
			'field_value'   => $text
		], null );

		ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendFlow', [
			'subscriber_id' => $subscriber_id,
			'flow_ns'       => ChB_Settings()->getParam( 'notify_via_mc_flow' )
		], null );

	}

	public static function copyCustomerCart( ChatBot $ChB ) {
		$customer_fb_user_id = $ChB->user->getManagerSettings()->getMarkedCustomer();
		$ChB->cart           = ChB_Cart::copyCartFromCustomer( $ChB->user->wp_user_id, $customer_fb_user_id, false );

		return ChB_FLOW_NewOrder::openCart( $ChB, "Customer's cart has been copied!" );
	}

	public static function getOrdersMenu( ChatBot $ChB ) {

		$text    = 'ORDERS MENU';
		$buttons = [];

		$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Orders to confirm', [ 'task' => 'manychat_manager_getOrdersToConfirm2Ship' ] );

		if ( ChB_Order::orderStatusExists( ChB_Order::getToShipStatus() ) ) {
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Orders to ship', [ 'task' => 'manychat_manager_getOrdersToShip' ] );
		}

		if ( ChB_Order::orderStatusExists( ChB_Order::getShippedStatus() ) ) {
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Orders to complete', [ 'task' => 'manychat_manager_getOrdersToComplete' ] );
		}

		$messages[] = [
			'text'    => $text,
			'type'    => 'text',
			'buttons' => $buttons
		];


		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function getOrdersToConfirm( ChatBot $ChB ) {

		$orders = ChB_Orders::getOrders( ChB_Order::getInitStatus() );

		$messages = [];
		if ( empty( $orders ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => 'No orders to confirm;)'
			];
		} else {
			foreach ( $orders as $order ) {
				$messages[] = ChB_FLOW_MyOrders::getOrderMessage( $ChB, $order );
			}
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function getOrdersToShip( ChatBot $ChB ) {

		$orders = [];
		if ( ChB_Order::orderStatusExists( ChB_Order::getToShipStatus() ) ) {
			$orders = ChB_Orders::getOrders( ChB_Order::getToShipStatus() );
		}

		$messages = [];
		if ( empty( $orders ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => 'No orders to ship;)'
			];
		} else {
			foreach ( $orders as $order ) {
				$messages[] = ChB_FLOW_MyOrders::getOrderMessage( $ChB, $order );
			}
		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function getOrdersToComplete( ChatBot $ChB ) {

		$orders = [];
		if ( ChB_Order::orderStatusExists( ChB_Order::getShippedStatus() ) ) {
			$orders = ChB_Orders::getOrders( ChB_Order::getShippedStatus() );
		}

		$messages = [];
		if ( empty( $orders ) ) {
			$messages[] = [
				'type' => 'text',
				'text' => 'No orders to complete'
			];
		} else {
			foreach ( $orders as $order ) {
				$messages[] = ChB_FLOW_MyOrders::getOrderMessage( $ChB, $order );
			}

		}

		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function setOrderStatus( ChatBot $ChB ) {

		$status = $ChB->getParam( 'val' );
		if ( ! $status ) {
			return false;
		}

		$res = false;
		if ( $status === ChB_Order::getInitStatus() ||
		     $status === ChB_Order::getEarlyCancelledStatus() ||
		     $status === ChB_Order::getToShipStatus() ||
		     $status === ChB_Order::getShippedStatus() ||
		     $status === ChB_Order::ORDER_STATUS_CANCELLED ||
		     $status === ChB_Order::ORDER_STATUS_COMPLETED ) {
			$res = ChB_Order::setOrderStatus( $ChB->getParam( 'order_id' ), $status, $ChB->user->fb_user_id );
		}

		if ( $res ) {
			//scheduling notification to customer
			if ( $status === ChB_Order::getToShipStatus() ) {
				ChB_Events::scheduleSingleEventOnShutdown( $ChB, ChB_Events::CHB_EVENT_SEND_CONFIRMATION_TO_USER, [ 'order_id' => $ChB->getParam( 'order_id' ) ] );
			}

			$text = 'Order #' . $ChB->getParam( 'order_id' ) . ' status changed to "' . strtoupper( $status ) . '"';
		} else {
			$text = 'Oops. Something went wrong ;)';
		}

		$messages = [ [ 'text' => $text, 'type' => 'text' ] ];
		$response = [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];

		return $response;
	}

	public static function sendNotificationsOnOrder( $order_id, $new_status ) {
		$wc_order          = wc_get_order( $order_id );
		$order_details4ntf = ChB_Order::getOrderDetails4Notification( $wc_order, $new_status );

		$customer_wp_user_id = $wc_order->get_customer_id();
		$customer_user       = ( $customer_wp_user_id ? ChB_User::initUserByWPUserID( $customer_wp_user_id ) : null );
		$channel_str         = $customer_user ? '[' . strtoupper( $customer_user->channel ) . '] ' : '';

		$via = ( ChB_Settings()->auth->isTestEnv() ? '[TEST] ' : '' ) .
		       ( $order_details4ntf['order_created_via'] === ChB_Order::ORDER_CREATED_VIA_BOT ? 'BOT: ' : 'SITE: ' );
		if ( $new_status == ChB_Order::getInitStatus() ) //new order
		{
			$text = "ℹ NOTIFICATION " . $channel_str . "ℹ\n" . $via . 'NEW ORDER #' . $order_details4ntf['order_id'] . ' TO CONFIRM!';
		} else {
			$text = "ℹ NOTIFICATION " . $channel_str . "ℹ\n" . $via . 'Order #' . $order_details4ntf['order_id'] . ' status changed to "' . strtoupper( $new_status ) .
			        ( empty( $order_details4ntf['manager_display_name'] ) ? '"' : '" by ' . $order_details4ntf['manager_display_name'] );
		}

		//scheduling notifications to managers
		foreach ( $order_details4ntf['managers2notify'] as $manager_id ) {
			if ( ChB_Settings()->getParam( 'notify_via_mc_flow' ) ) {
				self::sendNotificationViaManyChatFlow( $manager_id, $text, empty( $customer_user->mc_user_id ) ? null : $customer_user->mc_user_id );
			} else {
				$manager_user = ChB_User::initUser( $manager_id );
				if ( ! $manager_user || ! $manager_user->lastInteractionIsInside24H() ) {
					continue;
				}
				$ChB_Manager = ChatBot::openTempChatBotSession( $manager_user );

				$ChB_Manager->setParam( 'order_id', $order_details4ntf['order_id'] );
				$response = ChB_FLOW_MyOrders::openOrderSummary( $ChB_Manager, [
					[
						'type' => 'text',
						'text' => $text
					]
				] );
				$fields   = [ 'data' => $response ];
				$res      = ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB_Manager );
				ChatBot::closeTempChatBotSession();
			}
		}
	}

	public static function makeConfirmButton4Order( ChatBot $ChB, &$buttons, $order ) {
		if ( $order instanceof \WC_Order ) {
			$wc_order = $order;
		} else {
			$wc_order = wc_get_order();
		}

		if ( ChB_Order::orderStatusExists( ChB_Order::getToShipStatus() ) ) {
			if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $wc_order->get_status() === ChB_Order::getInitStatus() ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Confirm order 📦', [
					'task'     => 'manychat_manager_setOrderStatus',
					'order_id' => $wc_order->get_id(),
					'val'      => ChB_Order::getToShipStatus()
				] );
			}
		}
	}

	public static function makeMarkAsShippedButton4Order( ChatBot $ChB, &$buttons, $order ) {
		if ( $order instanceof \WC_Order ) {
			$wc_order = $order;
		} else {
			$wc_order = wc_get_order();
		}

		if ( ChB_Order::orderStatusExists( ChB_Order::getShippedStatus() ) ) {
			if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $wc_order->get_status() === ChB_Order::getToShipStatus() ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark as Shipped 📦', [
					'task'     => 'manychat_manager_setOrderStatus',
					'order_id' => $wc_order->get_id(),
					'val'      => ChB_Order::getShippedStatus()
				] );
			}
		}
	}

	public static function makeCompleteButton4Order( ChatBot $ChB, &$buttons, $order ) {
		if ( $order instanceof \WC_Order ) {
			$wc_order = $order;
		} else {
			$wc_order = wc_get_order();
		}

		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) && $wc_order->get_status() === ChB_Order::getShippedStatus() ) {
			$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Complete order 🐶', [
				'task'     => 'manychat_manager_setOrderStatus',
				'order_id' => $wc_order->get_id(),
				'val'      => ChB_Order::ORDER_STATUS_COMPLETED
			] );
		}
	}

	public static function makeCancelButton4Order( ChatBot $ChB, &$buttons, $order ) {
		if ( $order instanceof \WC_Order ) {
			$wc_order = $order;
		} else {
			$wc_order = wc_get_order();
		}

		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $ChB->user ) ) {
			$status = $wc_order->get_status();
			if ( $status === ChB_Order::getInitStatus() || $status === ChB_Order::getToShipStatus() ) {
				$new_status = ChB_Order::getEarlyCancelledStatus() ? ChB_Order::getEarlyCancelledStatus() : ChB_Order::ORDER_STATUS_CANCELLED;
			} elseif ( $status === ChB_Order::getShippedStatus() ) {
				$new_status = ChB_Order::ORDER_STATUS_CANCELLED;
			}

			if ( ! empty( $new_status ) ) {
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Cancel order 🐽', [
					'task'     => 'manychat_manager_setOrderStatus',
					'order_id' => $wc_order->get_id(),
					'val'      => $new_status
				] );
			}
		}
	}

	public static function getAllUnansweredNotifications( ChatBot $ChB ) {
		$ntfs  = ChB_Notifications::getUnansweredNotifications( 48 * 60 * 60 );
		$steps = [ 'lost', 'to_answer' ];
		$now   = time();
		foreach ( $steps as $step ) {
			if ( ! empty( $ntfs[ $step ] ) && count( $ntfs[ $step ] ) > 0 ) {
				$messages[] = [
					'text' => ( $step == 'lost' ? 'MORE than 24H' : 'LESS than 24H' ),
					'type' => 'text'
				];
			}

			foreach ( $ntfs[ $step ] as $subscriber_id => $ntf_val ) {
				$buttons      = [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Mark Customer ★', [
						'task'           => 'manychat_manager_markCustomer',
						'cus_fb_user_id' => $subscriber_id
					] ),
					ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Delete notification', [
						'task'           => 'manychat_manager_deleteNotification',
						'cus_fb_user_id' => $subscriber_id,
						'val'            => $ntf_val['ts']
					] ),
				];
				$display_name = ChB_User::getSubscriberDisplayName( $subscriber_id );
				$time         = ( $step == 'lost' ? intdiv( ( $now - $ntf_val['ts'] ), 86400 ) . 'd ' : intdiv( ( $now - $ntf_val['ts'] ), 3600 ) . 'h ' );
				$messages[]   = [
					'text'    => $display_name . ' ' . $time . ' (' . $subscriber_id . ')',
					'type'    => 'text',
					'buttons' => $buttons
				];
			}
		}

		if ( empty( $messages ) ) {
			$messages = [ [ 'type' => 'text', 'text' => 'all answered :)' ] ];
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}

	public static function deleteNotification( ChatBot $ChB ) {
		if ( ChB_Notifications::deleteNotification( $ChB->getParam( 'cus_fb_user_id' ), $ChB->getParam( 'val' ) ) ) {
			$text = 'done :)';
		} else {
			$text = 'something went wrong';
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => $text ] ] ] ];
	}

	public static function sendManager24HReminder( ChatBot $ChB ) {
		$messages = [
			[
				'type'    => 'text',
				'text'    => '❗❗ You are almost out of 24H window' . chr( 10 ) .
				             '👉 Please press any button or send any message to bot',
				'buttons' => [
					ChatBot::makeDynamicBlockCallbackButton( $ChB, '👆 Press me', [ 'task' => 'manychat_cmn_getDummy' ] )
				]
			]
		];
		$fields   = [ 'data' => [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ] ];

		return ChB_ManyChat::sendPost2ManyChat( '/fb/sending/sendContent', $fields, $ChB );

	}

	public static function getCustomerStats( ChatBot $ChB ) {

		$customer_fb_user_id = $ChB->user->getManagerSettings()->getMarkedCustomer();
		if ( $customer_fb_user_id ) {
			$customer_user = ChB_User::initUser( $customer_fb_user_id );
			if ( $customer_user ) {

				$display_name = ChB_User::getSubscriberDisplayName( $customer_fb_user_id );
				$text         = 'Customer "' . $display_name . '" (' . $customer_fb_user_id . ')';

				$orders_stats = ChB_Orders::getCustomerOrdersStats( $customer_user );

				$text .= "\n\n" . '👉 Completed orders: ' . ChB_Common::printPrice( $orders_stats['sum_completed'] ) . ' [' . $orders_stats['qty_completed'] . ( $orders_stats['qty_completed'] === 1 ? ' item' : ' items' ) . ']' .
				         "\n\n" . '👉 Cancelled orders: ' . ChB_Common::printPrice( $orders_stats['sum_cancelled'] ) . ' [' . $orders_stats['qty_cancelled'] . ( $orders_stats['qty_cancelled'] === 1 ? ' item' : ' items' ) . ']';
			}
		}

		if ( empty( $text ) ) {
			$text = 'Oops! Somethign went wrong :)';
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => [ [ 'type' => 'text', 'text' => $text ] ] ] ];
	}
}