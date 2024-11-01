<?php


namespace ChatBot;


class ChB_ManyChat {

	public const CF_PAR1 = 'RRB_Par1';
	public const CF_Lang = 'WY_Language';

	// value 'RRB_DF_SizeEntity' is specified as a Parameter Name in DF Intent
	public const CF_DF_SizeEntity = 'RRB_DF_SizeEntity';
	const MC_RRB_ROOT_FLOW_FOLDER_NAME = 'WANY.CHAT v1.3';
	const MC_FLOW_NAMES = [
		'flow_catalog'                    => 'WY Catalog',
		'flow_ig_catalog'                 => 'IG WY Catalog',
		'flow_try_on_demo'                => 'Try On Demo',
		'flow_ig_try_on_demo'             => 'IG Try On Demo',
		'flow_ig_connect'                 => 'IG Connect',
		'flow_open_product_by_ref_FBSHOP' => 'WY Open Product [FB Ref URL]',
		'flow_df_response'                => 'Dialog Flow Response'
	];

	public static function setCustomFields( ChatBot $ChB, $fields ) {
		return ChB_ManyChat::sendPost2ManyChat( '/fb/subscriber/setCustomFields', [ 'fields' => $fields ], $ChB );
	}

	public static function sendPost2ManyChat( $endpoint, $fields, ?ChatBot $ChB ) {
		ChB_Settings()->tic( 'sendPost2ManyChat' );
		$result = false;

		/**
		 * Used endpoints:
		 * /fb/sending/sendContent
		 * /fb/sending/sendFlow
		 * /fb/page/setBotFieldByName
		 * /fb/subscriber/setCustomFieldByName
		 * /fb/subscriber/setCustomFields
		 * /fb/subscriber/updateSubscriber
		 */
		try {

			if ( $endpoint === '/fb/sending/sendFlow' && ChB_Settings()->auth->connectionIsDirect() ) {
				$endpoint       = '/fb/sending/sendContent';
				$fields['data'] = ChB_FLOW_Common::getContent4Flow( $ChB, $fields['flow_ns'] );
				if ( ! $fields['data'] ) {
					ChB_Common::my_log( 'sendPost2ManyChat ' . $endpoint . ': cannot get ManyChatless content for flow=' . $fields['flow_ns'] );

					return false;
				}
				unset( $fields['flow_ns'] );
			};

			//some messages have already been created in native format (e.g. phone input)
			$is_native = ( isset( $fields['data']['version'] ) && $fields['data']['version'] === 'native' );
			if ( $is_native || $endpoint === '/fb/sending/sendContent' && ChB_Settings()->useNativeAPI( $ChB ) ) {
				return self::sendContentNativeAPI( $ChB, $fields['data'], $is_native );
			}

			//everything else sending via MC
			if ( ! ChB_Settings()->auth->getMCAppToken() ) {
				ChB_Common::my_log( 'sendPost2ManyChat ' . $endpoint . ': mc_app_token is empty, quitting' );

				return false;
			}

			if ( $ChB && $ChB->user->channel === ChB_Constants::CHANNEL_IG && $endpoint === '/fb/sending/sendContent' ) {
				$fields['data']['content']['type'] = 'instagram';
			}

			//$ChB is allowed to be empty, but $fields['subscriber_id'] should be set in this case
			if ( empty( $fields['subscriber_id'] ) && $endpoint !== '/fb/page/setBotFieldByName' ) {
				if ( $ChB && $ChB->user->mc_user_id ) {
					$fields['subscriber_id'] = $ChB->user->mc_user_id;
				} else {
					ChB_Common::my_log( 'sendPost2ManyChat ' . $endpoint . ': mc_user_id is empty, quitting' );

					return false;
				}
			}

			$response = wp_remote_post(
				'https://api.manychat.com' . $endpoint,
				[
					'headers'     => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . ChB_Settings()->auth->getMCAppToken()
					],
					'timeout'     => 30,
					'body'        => json_encode( $fields ),
					'data_format' => 'body'
				]
			);

			if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
				ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendPost2ManyChat ' . $endpoint . ' wp_remote_post ERROR' );

				return false;
			}
			$result = $response['body'];
			if ( $endpoint !== '/fb/subscriber/updateSubscriber' ) {
				ChB_Common::my_log( $result, true, 'sendPost2ManyChat ' . $endpoint );
			}
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'sendPost2ManyChat ' . $endpoint . ' Exception: ' . $e->getMessage() );
		} finally {
			ChB_Settings()->toc( 'sendPost2ManyChat' );
		}

		return $result;
	}

	public static function sendContentNativeAPI( ChatBot $ChB, $data, $is_native ) {
		$url = 'https://graph.facebook.com/' . ChB_Constants::FB_API_VERSION . '/me/messages?access_token=' . ChB_Settings()->auth->getFBAccessToken();
		if ( $is_native ) {
			$messages = $data['content']['messages'];
		} else {
			$messages = self::convertMC2Native( $ChB, $data );
		}
		$is_ok = true;
		ChB_Common::my_debug_log( $messages, 1, 'sendContentNativeAPI dbg messages' );
		foreach ( $messages as $message ) {
			ChB_Settings()->tic( 'sendContentNativeAPI' );
			$res  = ChB_Common::sendPost( $url, $message, 60 );
			$data = json_decode( $res, true );
			if ( empty( $data['recipient_id'] ) || empty( $data['message_id'] ) ) {
				$is_ok = false;
			}
			ChB_Settings()->toc( 'sendContentNativeAPI' );
		}
		if ( ! $is_ok ) {
			return false;
		} else {
			return [ 'status' => 'success' ];
		}
	}

	public static function convertMC2Native( ChatBot $ChB, $data ) {

		if ( empty( $data['content']['messages'] ) ) {
			return [];
		}

		$messages = [];
		foreach ( $data['content']['messages'] as $mc_message ) {
			if ( $mc_message['type'] === 'text' ) {

				if ( ! empty( $mc_message['buttons'] ) ) {
					$buttons = [];
					foreach ( $mc_message['buttons'] as $mc_button ) {
						if ( $button = self::convertButtonMC2Native( $ChB, $mc_button ) ) {
							$buttons[] = $button;
						}
					}

					if ( $buttons ) {
						if ( $ChB->user->channel === ChB_Constants::CHANNEL_IG ) {
							$messages[] = [
								'recipient' => [ 'id' => $ChB->user->fb_user_id ],
								'message'   => [
									'attachment' => [
										'type'    => 'template',
										'payload' => [
											'template_type' => 'generic',
											'elements'      => [
												[
													'title'   => $mc_message['text'],
													'buttons' => $buttons
												]
											]
										]
									]
								]
							];
						} else {
							$messages[] = [
								'recipient' => [ 'id' => $ChB->user->fb_user_id ],
								'message'   => [
									'attachment' => [
										'type'    => 'template',
										'payload' => [
											'template_type' => 'button',
											'text'          => $mc_message['text'],
											'buttons'       => $buttons
										]
									]
								]
							];
						}
					}
				} else {
					$messages[] = [
						'recipient' => [ 'id' => $ChB->user->fb_user_id ],
						'message'   => [ 'text' => $mc_message['text'] ]
					];
				}

			} elseif ( $mc_message['type'] === 'cards' ) {
				$elements = [];
				foreach ( $mc_message['elements'] as $mc_element ) {
					$buttons = [];
					if ( ! empty( $mc_element['buttons'] ) ) {
						foreach ( $mc_element['buttons'] as $mc_button ) {
							$buttons[] = self::convertButtonMC2Native( $ChB, $mc_button );
						}
					}

					$element = [];
					if ( ! empty( $mc_element['title'] ) ) {
						$element['title'] = $mc_element['title'];
					}
					if ( ! empty( $mc_element['subtitle'] ) ) {
						$element['subtitle'] = $mc_element['subtitle'];
					}
					if ( ! empty( $mc_element['image_url'] ) ) {
						$element['image_url'] = $mc_element['image_url'];
					}
					if ( $buttons ) {
						$element['buttons'] = $buttons;
					}

					if ( $element ) {
						$elements[] = $element;
					}
				}

				$messages[] = [
					'recipient' => [ 'id' => $ChB->user->fb_user_id ],
					'message'   => [
						'attachment' => [
							'type'    => 'template',
							'payload' => [
								'template_type'      => 'generic',
								'image_aspect_ratio' => $mc_message['image_aspect_ratio'],
								'elements'           => $elements
							]
						]
					]
				];
			}
		}

		return $messages;
	}

	public static function convertButtonMC2Native( ChatBot $ChB, &$mc_button ) {
		if ( $mc_button['type'] === 'url' ) {
			return [
				'type'  => 'web_url',
				'url'   => $mc_button['url'],
				'title' => $mc_button['caption']
			];
		} elseif ( $mc_button['type'] === 'dynamic_block_callback' ) {
			return [
				'type'    => 'postback',
				'title'   => $mc_button['caption'],
				'payload' => ChB_Constants::BASE64_MARKER . base64_encode( json_encode( [
						'wany_url' => $mc_button['url'],
						'payload'  => $mc_button['payload']
					] ) )
			];
		} elseif ( $mc_button['type'] === 'flow' ) {
			if ( ChB_Settings()->auth->connectionIsDirect() ) {
				//Full native case: using FB API to send messages, generate analogs for MC flows, which are then sent via FB API as well
				$mc_callback_button = ChB_FLOW_Common::getDynamicCallbackButton4FlowButton( $ChB, $mc_button );
				if ( $mc_callback_button ) {
					return [
						'type'    => 'postback',
						'title'   => $mc_callback_button['caption'],
						'payload' => json_encode( [
							'wany_url' => $mc_callback_button['url'],
							'payload'  => $mc_callback_button['payload']
						] )
					];
				}
			} elseif ( $ChB->user->mc_user_id && ! empty( $mc_button['target'] ) ) {
				// Hybrid case: using FB API to send messages, but MC flows are sent via MC API
				return [
					'type'    => 'postback',
					'title'   => $mc_button['caption'],
					'payload' => json_encode( [
						'flow'         => $mc_button['target'],
						'user_id'      => $ChB->user->mc_user_id,
						'mc_app_token' => ChB_Settings()->auth->getMCAppToken()
					] )
				];
			}
		}

		return null;
	}

	public static function getManyChatData() {
		$options                        = self::getFlowsFromManyChat();
		$options['mc_page_name']        = null;
		$options['fb_page_name']        = null;
		$options['fb_page_username']    = null;
		$options['ig_account_name']     = null;
		$options['ig_account_username'] = null;
		$res                            = ChB_ManyChat::sendGet2ManyChat( 'https://api.manychat.com/fb/page/getInfo' );

		if ( ! empty( $res ) ) {
			$data = json_decode( $res, true );
		}

		if ( ! empty( $data['status'] ) && $data['status'] === 'success' ) {
			$options['mc_page_name'] = $data['data']['name'];

			if ( ! empty( $data['data']['id'] ) ) { // manychat is connected via fb first
				$options['mc_page_getinfo_id'] = $data['data']['id'];
				$options['fb_page_name']       = $data['data']['name'];
				$options['fb_page_username']   = $data['data']['username']; //this can be empty for a facebook page
			} else { // manychat is connected via ig first
				$options['ig_account_name'] = $data['data']['name'];
			}
		}

		return $options;
	}

	public static function getFlowsFromManyChat() {
		$log_prefix = 'initFlowsFromManyChat: ';
		$options    = [];
		foreach ( self::MC_FLOW_NAMES as $flow_slug => $flow_name ) {
			$options[ $flow_slug ] = null;
		}

		$res = ChB_ManyChat::sendGet2ManyChat( 'https://api.manychat.com/fb/page/getFlows' );
		if ( empty( $res ) ) {
			return $options;
		}
		$data = json_decode( $res, true );
		if ( empty( $data['status'] ) || $data['status'] !== 'success' || empty( $data['data']['flows'] ) || empty( $data['data']['folders'] ) ) {
			ChB_Common::my_log( $log_prefix . 'unsuccessful request to manychat' );

			return $options;
		}
		$mc_flows   = $data['data']['flows'];
		$mc_folders = $data['data']['folders'];
		foreach ( $mc_folders as $mc_folder ) {
			if ( $mc_folder['id'] && $mc_folder['name'] === self::MC_RRB_ROOT_FLOW_FOLDER_NAME ) {
				$root_folder_id = $mc_folder['id'];
			}
		}

		if ( empty( $root_folder_id ) ) {
			ChB_Common::my_log( $log_prefix . 'cannot find root flow folder' );
		}

		$cache = [];
		foreach ( self::MC_FLOW_NAMES as $flow_slug => $flow_name ) {
			foreach ( $mc_flows as $mc_flow ) {
				if ( $flow_name == $mc_flow['name'] ) {
					$options[ $flow_slug ] = $mc_flow['ns'];
					if ( ! empty( $root_folder_id ) && self::folderIsInsideRoot( $mc_flow['folder_id'], $root_folder_id, $mc_folders, $cache ) ) {
						break;
					}
				}
			}
		}

		return $options;
	}

	public static function sendGet2ManyChat( $url ) {
		if ( empty( ChB_Settings()->auth->getMCAppToken() ) ) {
			ChB_Common::my_log( 'sendGet2ManyChat: mc_app_token is empty, quitting' );

			return false;
		}

		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . ChB_Settings()->auth->getMCAppToken()
				],
				'timeout' => 30
			]
		);

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'sendGet2ManyChat wp_remote_get ERROR' );

			return false;
		}

		return $response['body'];
	}

	public static function getMCLiveChatLinkHTML( $mc_user_id ) {
		return '<a target="_blank" href="' . esc_url_raw( ChB_ManyChat::getMCLiveChatLink( $mc_user_id ) ) . '"> ' . $mc_user_id . '</a>';
	}

	public static function getMCLiveChatLink( $mc_user_id ) {
		return 'https://manychat.com/fb' . ChB_Settings()->auth->getMCPageID() . '/chat/' . $mc_user_id;
	}

	private static function folderIsInsideRoot( $folder_id, $root_folder_id, &$mc_folders, &$cache, $level = 0 ) {
		if ( isset( $cache[ $folder_id ] ) ) {
			return $cache[ $folder_id ];
		}

		if ( $folder_id === $root_folder_id ) {
			return ( $cache[ $folder_id ] = true );
		}

		if ( $folder_id === 0 ) {
			return ( $cache[ $folder_id ] = false );
		}

		//anti endless recursion
		if ( $level > 20 ) {
			return ( $cache[ $folder_id ] = false );
		}

		foreach ( $mc_folders as $mc_folder ) {
			if ( $mc_folder['id'] === $folder_id ) {
				$res = self::folderIsInsideRoot( $mc_folder['parent_id'], $root_folder_id, $mc_folders, $cache, $level + 1 );

				return ( $cache[ $folder_id ] = $res );
			}
		}

		return ( $cache[ $folder_id ] = false );
	}

	public static function fireManyChatTrigger( $subscriber_id, $trigger_name, $context ) {

		if ( ! ChB_Settings()->auth->getMCAppToken() ) {
			return null;
		}

		$result = null;
		try {
			$response = wp_remote_post(
				'https://manychat.com/apps/wh',
				[
					'headers'     => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . ChB_Settings()->auth->getMCAppToken()
					],
					'timeout'     => 30,
					'body'        => json_encode( [

						'version'       => 1,
						'subscriber_id' => $subscriber_id,
						'trigger_name'  => $trigger_name,
						'context'       => $context
					] ),
					'data_format' => 'body'
				]
			);

			if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
				ChB_Common::my_log( ( is_wp_error( $response ) ? $response->get_error_messages() : '' ), true, 'fireManyChatTrigger ' . $trigger_name . ' wp_remote_post ERROR' );

				return false;
			}
			$result = $response['body'];
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'fireManyChatTrigger ' . $trigger_name . ' Exception: ' . $e->getMessage() );
		} finally {
			ChB_Settings()->toc( 'fireManyChatTrigger' );
		}

		return $result;
	}

	public static function initManyChatBotFields( $clear = false ) {

		if ( ! ChB_Settings()->auth->isTestEnv() ) {
			if ( $clear ) {
				$fields = [ ChB_Constants::BF_EmptyValue => null ];
			} else {
				$fields = [ ChB_Constants::BF_EmptyValue => ChB_Common::EMPTY_TEXT ];
			}
		}

		if ( ! empty( $fields ) ) {
			ChB_Common::my_log( $fields, true, 'initManyChatBotFields clear=' . ( $clear ? '1' : '0' ) );
			foreach ( $fields as $name => $value ) {
				self::sendPost2ManyChat( '/fb/page/setBotFieldByName', [
					'field_name'  => $name,
					'field_value' => $value
				], null );
			}
		}
	}

	/**
	 * @param $CFNames
	 * @param $user \WP_User|null|string (fb_user_id)
	 * @param null $mc_user_info
	 *
	 * @return array|bool
	 */
	public static function getCFValuesFromManyChat( $CFNames, $mc_user_info ) {
		if ( empty( $mc_user_info['custom_fields'] ) ) {
			return false;
		}

		$res = [];
		foreach ( $mc_user_info['custom_fields'] as $cf ) {
			foreach ( $CFNames as $CFName ) {
				if ( $CFName === $cf['name'] ) {
					$res[ $CFName ] = $cf['value'];
				}
			}
		}

		return $res;
	}

	public static function getUserChannelFromMC( $mc_user_id, &$mc_user_info = null ) {
		if ( ! $mc_user_info ) {
			$mc_user_info = self::getUserInfoFromMC( $mc_user_id );
			if ( ! $mc_user_info ) {
				return false;
			}
		}

		return ( empty( $mc_user_info['ig_id'] ) ? ChB_Constants::CHANNEL_FB : ChB_Constants::CHANNEL_IG );
	}

	public static function getUserInfoFromMC( $mc_user_id ) {
		$res  = ChB_ManyChat::sendGet2ManyChat( 'https://api.manychat.com/fb/subscriber/getInfo?subscriber_id=' . $mc_user_id );
		$info = json_decode( $res, true );

		return $info;
	}

	public static function getUserIGSIDFromMC( $mc_user_info ) {
		return ( empty( $mc_user_info['ig_id'] ) ? null : $mc_user_info['ig_id'] );
	}

	public static function getMCFlowNS( $channel, $flow_id ) {
		if ( $channel === ChB_Constants::CHANNEL_IG ) {
			if ( $flow_id === 'flow_catalog' ) {
				$flow_id = 'flow_ig_catalog';
			} elseif ( $flow_id === 'flow_try_on_demo' ) {
				$flow_id = 'flow_ig_try_on_demo';
			}
		}

		return ChB_Settings()->getParam( $flow_id );
	}
}