<?php


namespace ChatBot;


class ChB_SettingsPage {
	public static function init() {
		add_action( 'admin_menu', [ '\ChatBot\ChB_SettingsPage', 'rrbot_add_admin_menu' ], 11 );
		add_action( 'admin_init', [ '\ChatBot\ChB_SettingsPage', 'rrbot_settings_init' ], 11 );
		add_action( 'admin_enqueue_scripts', [ '\ChatBot\ChB_SettingsPage', 'rrbotWPAdminJS' ] );
	}

	public static function rrbotWPAdminJS( $hook ) {
		if ( $hook !== 'settings_page_rrbot-settings' ) {
			$screen = get_current_screen();
			if ( ! $screen || $screen->id !== 'edit-product_cat' ) {
				return;
			}
		}
		chb_load();

		$nonce = wp_create_nonce( 'wp_rest' );
		wp_enqueue_script( 'rrbot-wp-admin-js', plugins_url( '/rrbot-wp-admin.js', __FILE__ ), [], '1.8.6' );

		$script = 'var rrbot_admin_get_tokens_form = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_GET_TOKENS_FORM, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_regen_tokens_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_REGEN_TOKENS, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_clear_fb_page_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_CLEAR_FB_PAGE, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_connect_test_domain_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_CONNECT_TEST_DOMAIN, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_disconnect_test_domain_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_DISCONNECT_TEST_DOMAIN, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_connect_mcless_domain_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_CONNECT_MCLESS_DOMAIN, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_disconnect_mcless_domain_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_DISCONNECT_MCLESS_DOMAIN, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_set_mercado_pago_token_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_SET_MERCADO_PAGO_TOKEN, $nonce ) ) . '";';
		$script .= 'var rrbot_admin_set_robokassa_options_url = "' . esc_url_raw( ChB_Constants::genAdminAPIURL( ChB_Constants::RRBOT_ADMIN_TASK_SET_ROBOKASSA_OPTIONS, $nonce ) ) . '";';

		$script .= 'var rrb_str_choose_image = "' . esc_html__( 'Choose an image', 'woocommerce' ) . '";';
		$script .= 'var rrb_str_use_image = "' . esc_html__( 'Use image', 'woocommerce' ) . '";';
		$script .= 'var rrb_wc_placeholder_img_src = "' . esc_js( wc_placeholder_img_src() ) . '";';

		wp_add_inline_script( 'rrbot-wp-admin-js', $script, 'before' );
	}

	public static function rrbot_add_admin_menu() {
		add_options_page( 'Wany.Chat', 'Wany.Chat', 'manage_options', 'rrbot-settings', [
			'\ChatBot\ChB_SettingsPage',
			'rrbot_options_page'
		] );
	}

	public static function rrbot_settings_init() {
		chb_load();

		// If getting error "Options page not found in the allowed options list":
		// 1. Comment "register_setting()" line
		// 2. Open rrbot-settings form
		// 3. Uncomment "register_setting()" line. Profit!
		register_setting( 'rrbotPlugin', ChB_Settings::OPTION_NAME );

		$fields_info = ChB_Settings()->getFields4SettingsForm();

		if ( ! empty( $fields_info['submit_button_after'] ) ) {
			$submit_section = [
				'hidden'   => false,
				'title'    => '',
				'callback' => 'rrbot_settings_section_submit_callback',
				'fields'   => []
			];
			if ( $fields_info['submit_button_after'] === 'LAST' ) {
				$fields_info['sections']['submit'] = $submit_section;
			} elseif ( array_key_exists( $fields_info['submit_button_after'], $fields_info['sections'] ) ) {
				$new_sections = [];
				foreach ( $fields_info['sections'] as $section_name => $section ) {
					$new_sections[ $section_name ] = $section;
					if ( $section_name === $fields_info['submit_button_after'] ) {
						$new_sections['submit'] = $submit_section;
					}
				}
				$fields_info['sections'] = $new_sections;
			}
		}

		foreach ( $fields_info['sections'] as $section_name => $section ) {
			$section_id = 'rrbotPlugin_section_' . $section_name;
			add_settings_section(
				$section_id,
				( $section['hidden'] || ! $section['title'] ? '' : '<br>' . $section['title'] . '<br>' ),
				( $section['hidden'] ? '' : ( $section['callback'] ? [
					'\ChatBot\ChB_SettingsPage',
					$section['callback']
				] : '' ) ),
				'rrbotPlugin'
			);

			$options = get_option( ChB_Settings::OPTION_NAME );
			foreach ( $section['fields'] as $field ) {

				$render_field = ( ! empty( $fields_info['fields_render'][ $field ]['type'] ) ) ? $fields_info['fields_render'][ $field ]['type'] : (
				! empty( $fields_info['fields_render'][ $field ] ) ? $fields_info['fields_render'][ $field ] : 'render_field'
				);
				$field_class  = ( $section['hidden'] || in_array( $field, $fields_info['hidden_fields'] ) ? 'rrb-hidden ' : '' ) . 'rrb-field-' . $field;
				$field_title  = empty( $fields_info['fields_titles'][ $field ] ) ? ucfirst( str_replace( '_', ' ', $field ) ) : $fields_info['fields_titles'][ $field ];
				$field_value  = ( empty( $options[ $field ] ) ? '' : $options[ $field ] );
				$args         = [ 'label_for' => $field, 'class' => $field_class, 'value' => $field_value ];
				if ( ! empty( $fields_info['fields_render'][ $field ]['related'] ) ) {
					$args['related'] = $fields_info['fields_render'][ $field ]['related'];
				}

				$html_after = '';
				if ( ! empty( $fields_info['fields_descriptions'][ $field ] ) ) {
					$html_after = '<div style="margin-top: 10px">* ' . $fields_info['fields_descriptions'][ $field ] . '</div>';
				}
				if ( ! empty( $fields_info['fields_help_links'][ $field ] ) ) {
					$html_after .= '<div>' . $fields_info['fields_help_links'][ $field ] . '</div>';
				}
				if ( $html_after ) {
					$args['html_after'] = $html_after;
				}

				add_settings_field(
					$field,
					$field_title,
					[ '\ChatBot\ChB_SettingsPage', $render_field ],
					'rrbotPlugin',
					$section_id,
					$args
				);
			}
		}
	}

	public static function render_field( $args, $readonly = false ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];
		?>
        <input <?php echo( $readonly ? 'readonly' : '' ) ?> type='text' id='<?php echo esc_attr( $field_id ) ?>'
                                                            name='<?php echo esc_attr( $field_name ) ?>'
                                                            style="width: 600px;"
                                                            value='<?php echo esc_attr( $field_value ); ?>'>
		<?php
		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_readonly( $args ) {
		self::render_field( $args, true );
	}

	public static function render_field_textarea( $args, $readonly = false ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];
		?>
        <textarea <?php echo( $readonly ? 'readonly' : '' ) ?> rows="4" id='<?php echo esc_attr( $field_id ) ?>'
                                                               name='<?php echo esc_attr( $field_name ) ?>'
                                                               style="width: 600px;"><?php echo esc_attr( $field_value ) ?></textarea>
		<?php
		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_json( $args ) {
		if ( ! empty( $args['value'] ) ) {
			$args['value'] = str_replace( [ ',', ':' ], [ ', ', ' : ' ], json_encode( json_decode( $args['value'] ) ) );
		}
		self::render_field( $args );
	}

	public static function render_field_userlist( $args ) {
		$field_value = $args['value'];
		if ( $field_value && $users_ids = json_decode( $field_value, true ) ) {
			$userlist = ChB_User::printUserlist( ChB_Common::arrayOfStrings( $users_ids ) );
		} else {
			$userlist = '';
		}
		$field_name = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id   = 'rrbot_settings_' . $args['label_for'];
		?>
        <input readonly type='text' style="width: 600px;" value='<?php echo esc_attr( $userlist ); ?>'>
        <a href="" onclick="function show() {
                document.getElementById('<?php echo esc_attr( $field_id ); ?>').type =
                document.getElementById('<?php echo esc_attr( $field_id ); ?>').type === 'hidden' ? 'text' : 'hidden';};
                show(); return false;">🖉 Edit</a>
        <input type='hidden' id='<?php echo esc_attr( $field_id ); ?>' name='<?php echo esc_attr( $field_name ); ?>'
               style="width: 600px;" value='<?php echo esc_attr( $field_value ); ?>'>
		<?php
		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_shipping_code( $args ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];
		$options     = [
			ChB_Common::SHIPPING_FREE   => 'Free shipping',
			ChB_Common::SHIPPING_FLAT   => 'Flat rate',
			ChB_Common::SHIPPING_MANUAL => 'Manual rate',
			ChB_Common::SHIPPING_WOO    => 'Use WooCommerce Methods'
		];

		if ( strpos( $args['class'], 'rrb-hidden' ) === false ) {
			echo '<script>jQuery( document ).ready(function() {setShippingCostVisibility("' . esc_attr( $field_value ) . '");});</script>';
		}
		echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" onchange="setShippingCostVisibility(event.target.value); return false;" >';
		foreach ( $options as $option => $option_title ) {
			echo '<option value="' . esc_attr( $option ) . '"' . ( $field_value === $option ? ' selected' : '' ) . '>' . esc_attr( $option_title ) . '</option>';
		}
		echo '</select>';

		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_use_mercado_pago( $args ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];
		$form_id     = 'mercado_pago_settings';
		?>
        <script>
            jQuery(document).ready(function () {
                setVisible(<?php echo $field_value ? 'true' : 'false' ?>, ['<?php echo $form_id ?>']);
            });
        </script>
        <table style="border-spacing: 0">
            <tr>
                <td style="padding: 0">
                    <input type='checkbox' <?php echo( $field_value ? 'checked' : '' ); ?>
                           name='<?php echo esc_attr( $field_name ); ?>' value='1'
                           id='<?php echo esc_attr( $field_id ); ?>'
                           onclick='setVisible(this.checked, ["<?php echo $form_id ?>"]);'>
                </td>
            </tr>

            <tr id="<?php echo $form_id ?>" class='rrb-hidden'>
                <td style="padding: 0">
					<?php if ( ChB_Settings()->auth->mercadoPagoIsConnected() ) { ?>
                        <p style="margin: 20px 0 20px 0"> Mercado Pago is connected </p>
                        <div style="margin:10px 0 10px 0" class="button"
                             onclick='sendAjax(rrbot_admin_set_mercado_pago_token_url, null, true, "rrb-status");'>
                            Disconnect
                        </div>
					<?php } else { ?>
                        <p style="margin: 20px 0 20px 0"> To connect Mercado Pago please input <code>access token</code>
                            and push <code>connect</code>
                        <p style="margin: 20px 0 20px 0"> To connect Mercado Pago please input <code>access token</code>
                            and push <code>connect</code>
                            button:</p>
                        <div style="margin:10px 0 10px 0">
                            <input id="mp_access_token" style="width: 600px;">
                        </div>
                        <div class="button"
                             onclick='sendAjax(rrbot_admin_set_mercado_pago_token_url, null, true, "rrb-status", {token : "mp_access_token", use_mercado_pago: "<?php echo esc_attr( $field_id ); ?>"});'>
                            Connect
                        </div>
					<?php } ?>
                </td>
            </tr>
        </table>
		<?php
	}

	public static function render_field_use_robokassa( $args ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];
		$form_id     = 'robokassa_settings';

		?>
        <script>
            jQuery(document).ready(function () {
                setVisible(<?php echo( $field_value === ChB_PaymentRobokassa::ROBOKASSA_ENABLED || $field_value === ChB_PaymentRobokassa::ROBOKASSA_TEST_ENABLED ? 'true' : 'false' ) ?>, ['<?php echo $form_id ?>']);
            });
        </script>

		<?php

		$options = [
			ChB_PaymentRobokassa::ROBOKASSA_DISABLED     => 'Disabled',
			ChB_PaymentRobokassa::ROBOKASSA_TEST_ENABLED => 'Test Enabled',
			ChB_PaymentRobokassa::ROBOKASSA_ENABLED      => 'Enabled',
		];

		$robokassa_is_connected = ChB_Settings()->auth->robokassaIsConnected();

		echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" onchange="setVisible(event.target.value==\'' . ChB_PaymentRobokassa::ROBOKASSA_ENABLED . '\' || event.target.value==\'' . ChB_PaymentRobokassa::ROBOKASSA_TEST_ENABLED . '\', [\'' . $form_id . '\']);" >';
		foreach ( $options as $option => $option_title ) {
			echo '<option value="' . esc_attr( $option ) . '"' . ( $field_value === $option ? ' selected' : '' ) . '>' . esc_attr( $option_title ) . '</option>';
		}
		echo '</select><div id="' . $form_id . '" class="rrb-hidden">' .
		     '<p style="margin: 20px 0 20px 0">' .

		     ( $robokassa_is_connected ?
			     'Robokassa is connected' :
			     'Robokassa is <span style="color:red">NOT connected</span>. To connect Robokassa please fill in all the fields below and push <code>Connect</code> button' ) .
		     '</p>';
		?>

        <table style="width: 600px">
            <tr>
                <td style="white-space: nowrap"><label
                            for="<?php echo ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ?>">Merchant
                        Login:</label></td>
                <td style="width: 100%">
					<?php
					if ( $robokassa_is_connected ) {
						echo ChB_Settings()->auth->getRobokassaSetting( 'login' );
					} else {
						echo '<input style="width: 100%" style="width: 100%" id="' . ChB_Auth::ROBOKASSA_MERCHANT_LOGIN . '">';
					}
					?>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap"><label
                            for="<?php echo ChB_Auth::ROBOKASSA_PASSWORD_1 ?>">Password #1:</label></td>
                <td style="width: 100%">
					<?php
					if ( $robokassa_is_connected ) {
						echo '<input style="width: 100%" type="password" value="XXXXXXX" disabled>';
					} else {
						echo '<input style="width: 100%" id="' . ChB_Auth::ROBOKASSA_PASSWORD_1 . '">';
					}
					?>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap"><label
                            for="<?php echo ChB_Auth::ROBOKASSA_PASSWORD_2 ?>">Password #2:</label></td>
                <td style="width: 100%">
					<?php
					if ( $robokassa_is_connected ) {
						echo '<input style="width: 100%" type="password" value="XXXXXXX" disabled>';
					} else {
						echo '<input style="width: 100%" id="' . ChB_Auth::ROBOKASSA_PASSWORD_2 . '">';
					}
					?>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap"><label
                            for="<?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 ?>">Test Password
                        #1:</label></td>
                <td style="width: 100%">
					<?php
					if ( $robokassa_is_connected ) {
						echo '<input style="width: 100%" type="password" value="XXXXXXX" disabled>';
					} else {
						echo '<input style="width: 100%" id="' . ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 . '">';
					}
					?>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap"><label
                            for="<?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 ?>">Test Password
                        #2:</label></td>
                <td style="width: 100%">
					<?php
					if ( $robokassa_is_connected ) {
						echo '<input style="width: 100%" type="password" value="XXXXXXX" disabled>';
					} else {
						echo '<input style="width: 100%" id="' . ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 . '">';
					}
					?>
                </td>
            </tr>
            <tr>
                <td>
					<?php
					if ( $robokassa_is_connected ) { ?>
                        <div style="margin:10px 0 10px 0" class="button"
                             onclick='sendAjax(rrbot_admin_set_robokassa_options_url + "&mode=disconnect", null, true, "robokassa_form_status", null, "This will DISCONNECT plugin from Robokassa. Are you sure?");'>
                            Disconnect
                        </div>
					<?php } else { ?>
                        <div class="button"
                             onclick='sendAjax(rrbot_admin_set_robokassa_options_url + "&mode=connect", null, true, "robokassa_form_status",
                                     {<?php echo ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ?> : "<?php echo ChB_Auth::ROBOKASSA_MERCHANT_LOGIN ?>",
						     <?php echo ChB_Auth::ROBOKASSA_PASSWORD_1 ?> : "<?php echo ChB_Auth::ROBOKASSA_PASSWORD_1 ?>",
						     <?php echo ChB_Auth::ROBOKASSA_PASSWORD_2 ?> : "<?php echo ChB_Auth::ROBOKASSA_PASSWORD_2 ?>",
						     <?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 ?> : "<?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_1 ?>",
						     <?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 ?> : "<?php echo ChB_Auth::ROBOKASSA_TEST_PASSWORD_2 ?>"});'>
                            Connect
                        </div>
					<?php } ?>
                </td>
            </tr>

            <tr>
                <td style="color:red" id="robokassa_form_status" colspan="2"></td>
            </tr>
        </table>
		<?php $robokassa_url = 'https://' . ChB_Settings()->getDomainPath() . '/?' . ChB_PaymentRobokassa::GET_PAR_ROBOKASSA_BACK . '='; ?>
        <table style="width: 900px">
            <tr>
                <td colspan="2">
                    Please fill in the following fields in your Robokassa Account->Shop Settings->Technical Preferences
                    (Настройки->Технические настройки):
                </td>
            </tr>
            <tr>
                <td>
                    ResultURL:
                </td>
                <td><code><?php echo $robokassa_url ?>result</code>
                </td>
            </tr>
            <tr>
                <td>
                    SuccessURL:
                </td>
                <td><code><?php echo $robokassa_url ?>success</code>
                </td>
            </tr>
            <tr>
                <td>
                    FailURL:
                </td>
                <td><code><?php echo $robokassa_url ?>fail</code>
                </td>
            </tr>
            <tr>
                <td>
                    Method of sending data (Метод отсылки данных):
                </td>
                <td><code>POST</code>
                </td>
            </tr>
            <tr>
                <td>
                    Hash calculation algorithm (Алгоритм расчета хеша):
                </td>
                <td><code>MD5</code>
            </tr>
            <tr>
                <td colspan="2"><b>IMPORTANT</b>: Please create separate <code>Shop</code> in your Robokassa account to
                    use in Wany.Chat plugin
                </td>
            </tr>
        </table>
        </div>
		<?php
	}

	public static function render_field_checkbox( $args, $readonly = false ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$related     = empty( $args['related'] ) ? '' : '["rrbot_settings_' . implode( '", "rrbot_settings_', $args['related'] ) . '"]';

		if ( $related ) {
			?>
            <script>
                jQuery(document).ready(function () {
                    setReadonly(<?php echo $field_value ? 'false' : 'true'?>, <?php echo $related?>);
                });
            </script>
			<?php
		}
		?>
        <input <?php echo( $readonly ? 'disabled="disabled"' : '' ) ?>
                type='checkbox' <?php echo( $field_value ? 'checked' : '' ); ?>
                name='<?php echo esc_attr( $field_name ); ?>' value='1'
			<?php
			if ( $related ) {
				echo " onclick='setReadonly(!this.checked, " . $related . ");'";
			}
			?>

        >
		<?php if ( $readonly ) { ?>
            <input type='hidden' name='<?php echo esc_attr( $field_name ); ?>'
                   value='<?php echo esc_attr( $field_value ); ?>'>
		<?php } ?>
		<?php
		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_checkbox_readonly( $args ) {
		self::render_field_checkbox( $args, true );
	}

	public static function render_field_radio_button( $args ) {
		$field_value = $args['value'];
		$field_name  = 'rrbot_settings[' . $args['label_for'] . ']';
		$field_id    = 'rrbot_settings_' . $args['label_for'];

		$ind = 1;
		foreach ( $args['options'] as $option ) {
			$cur_field_id = $field_id . '_' . $ind ++;
			echo '<input type="radio" id="' . $cur_field_id . '" name="' . $field_name . '" value="' . $option['value'] . '" ' . ( $field_value == $option['value'] ? ' checked' : '' ) . '>';
			echo '<label style="vertical-align: text-bottom; margin-left: 5px;" for="' . $cur_field_id . '">' . $option['label'] . '</label><br><br>';
		}

		if ( ! empty( $args['html_after'] ) ) {
			echo $args['html_after'];
		}
	}

	public static function render_field_web_redirect( $args ) {
		$args['options'] = [
			[ 'value' => ChB_Settings::SETTING_WEB_REDIRECT_NO, 'label' => 'No redirect to website. Checkout in bot' ],
			[ 'value' => ChB_Settings::SETTING_WEB_REDIRECT_BUY, 'label' => 'Redirect to website on BUY button' ],
			[
				'value' => ChB_Settings::SETTING_WEB_REDIRECT_PLACE_ORDER,
				'label' => 'Redirect to website on "I Confirm Order" button'
			]
		];
		self::render_field_radio_button( $args );
	}

	public static function rrbot_settings_section_submit_callback() {
		echo '<p>Questions or problems with Wany.Chat? Click ' . ChB_WPAdminNotices::getA( 'HERE', 'https://m.me/wany.chat?ref=support2', true ) . ' to chat with us. It\'s FREE</p>';
		submit_button();
	}

	public static function rrbot_settings_section_wy_token_callback() {
		echo self::getWYConnectionSection( false, true );
	}

	public static function getWYConnectionSection( $to_json, $short_if_connected ) {
		$is_connected = ! empty( ChB_Settings()->auth->getWYToken() );

		if ( $is_connected && ChB_Settings()->auth->connectionIsDirect() && ! ChB_Settings()->auth->getFBAccessToken() ) {
			// if we are connected directly but fb_access_token is empty - disconnecting - something went wrong before
			$is_connected = false;
			ChB_Settings()->auth->setTokens( '', '', '', '' );
		}

		$is_error = false;
		if ( ! $is_connected ) {

			if ( ! ChB_Settings()->auth->getWYTokenToBeVerified() ) {

				ChB_Common::my_log( 'wy_token2verify is empty, requesting one' );
				$is_error = ! ChB_Settings()->auth->requestWYToken();

			} elseif ( ! ChB_Settings()->auth->checkWYTokenToBeVerifiedIsAlive() ) {

				ChB_Common::my_log( 'wy_token2verify has got old, requesting a new one..' );
				$is_error = ! ChB_Settings()->auth->requestWYToken();

			}

			if ( ! $is_error ) {
				$is_error = ( ! $is_connected && ! ChB_Settings()->auth->getWYTokenToBeVerified() );
			}
		}

		if ( $is_error ) {
			$html = 'ERROR. Something went wrong. Please try again later';
			if ( $to_json ) {
				return [ 'status' => 'error' ];
			} else {
				return $html;
			}
		}

		/**
		 * From this point plugin is either connected ($auth->getWYToken() not empty)
		 * or to be verified ($auth->getWYTokenToBeVerified() not empty)
		 */

		$html = '';
		if ( $is_connected ) {
			if ( $short_if_connected ) {

				$html .= '<div id="wany-tokens-form">' . self::getShortConnectionInfo( $is_connected );
				if ( ChB_Settings()->auth->connectionIsDirect() ) {
					$html .= '<br><br><div class="button wy-class-vis1" onclick="sendAjax(rrbot_admin_disconnect_mcless_domain_url, null, true, \'wy-token-status\', null, \'Do you really want to disconnect?\');">Disconnect</div>';
				} else {
					$html .= '.&nbsp;<u style="border-bottom: 3px dashed ' . ChB_Constants::COLOR_WANY_TEXT . '; text-decoration: none; cursor:pointer"' .
					         'onclick=\'sendAjax(rrbot_admin_get_tokens_form, "wany-tokens-form", false, "wany-tokens-form");\'>' .
					         'Show connection key</u></div>';
				}

			} elseif ( ChB_Settings()->auth->connectionIsMC() ) {
				$html .= self::getWYTokenFormHTML( $is_connected );
			}

		} else {

			$html .=
				'<div style="margin:50px 0 0 0">' .
				'<input type="radio" id="connection_type_manychat" name="connection_type" value="manychat" checked onclick="changeVisibility(this, \'wy-class-vis3\', \'wy-class-vis4\')">' .
				'<label style="margin:0 0 0 5px" for="connection_type_manychat">I have <b>ManyChat Pro</b> Account</label><br>' .
				'</div><div style="margin:20px 0 0 0">' .
				'<input type="radio" id="connection_type_native" name="connection_type" value="native" onclick="changeVisibility(this, \'wy-class-vis4\', \'wy-class-vis3\')">' .
				'<label style="margin:0 0 0 5px" for="connection_type_native">I don\'t have ManyChat Pro Account</label><br>' .
				'</div>';
			$html .= '<div style="margin:50px 0 0 0">';

			//MC
			$html .= '<div class="wy-class-vis3">' .
			         self::getWYTokenFormHTML( $is_connected ) . '</div>';

			//Direct
			$html .= '<div style="display: none" class="wy-class-vis4"><table style="border: 2px solid;padding: 20px; width: 440px">' .
			         '<tr><td style="padding: 10px">' . self::getFBLoginButton( true ) . '</td></tr></table></div></div>';
		}

		if ( $to_json ) {
			return [ 'status' => 'success', 'html' => $html ];
		} else {
			return $html;
		}
	}

	public static function getWYTokenFormHTML( $is_connected ) {

		$token    = ChB_Settings()->auth->getWYTokenToBeVerified() ? ChB_Settings()->auth->getWYTokenToBeVerified() : ChB_Settings()->auth->getWYToken();
		$hide_ext = ! defined( 'RRB_EXT0' ) && ! defined( 'RRB_EXT' );

		$html = '<style>.wy-key-td {border: 5px;border-style: dashed;padding: 5px;margin: 10px;border-color: ' . ChB_Constants::COLOR_WANY_TEXT . ';}</style>';
		$html .= '<table style="border: 2px solid;padding: 20px; width: 440px">' .
		         '<tr><td style="padding:10px; text-align: center">WANY.CHAT APP KEY FOR MANYCHAT:</td></tr><tr>' .
		         '<td class="wy-key-td">' .
		         '<input class="wy-class-vis1" type="text" style="border-style:hidden; text-align:center; width: 100%; display:block" readonly value="' . esc_attr( $token ) . '">' .
		         '<input id="wy_test_token" class="wy-class-vis2" type="text" style="border-style:hidden; text-align:center; width: 100%; display:none"/>' .
		         '</td></tr>';

		if ( ! $is_connected ) {
			$html .= '<tr><td style="padding: 10px">' .
			         '<span class="wy-class-vis1">Please copy this key, input it into <b>Wany.Chat</b> App settings in your ManyChat account and refresh this page ' . ChB_Settings::getHowToInstallLink() . '</span>' .
			         '<span class="wy-class-vis2" style="display: none">Please input key from connected production domain and push Connect</span>' .
			         '</td></tr>';

			$html .= '<tr><td style="padding: 10px">' .
			         '<div class="button wy-class-vis1" onclick="sendAjax(rrbot_admin_regen_tokens_url, null, true, \'wy-token-status\');">Regenerate Key</div>' .
			         '<div class="button wy-class-vis2" style="display: none" onclick="sendAjax(rrbot_admin_connect_test_domain_url, null, true, \'wy-token-status\', {wy_token : \'wy_test_token\'});">Connect Test Domain</div>' .
			         '</td></tr>';

			$html .= '<tr><td style="padding: 10px">';
			if ( ! $hide_ext ) {
				$html .= '<input id="wy_is_test_checkbox" type="checkbox" onclick="changeVisibility(this, \'wy-class-vis2\', \'wy-class-vis1\')">' .
				         '<label for="wy_is_test_checkbox">This is test domain</label>';
			}

			$html .= '</td></tr>';

		} else {
			$is_test_env           = ChB_Settings()->auth->isTestEnv();
			$confirm_text          = $hide_ext ? 'This will DISCONNECT plugin from Wany.Chat server. Are you sure?' : 'This will DISCONNECT plugin from ManyChat (both production and test domains). Are you sure?';
			$short_connection_info = self::getShortConnectionInfo( $is_connected );

			$html .= '<tr><td style="padding: 10px">' . $short_connection_info .
			         ( ChB_Settings()->auth->getMCPageID() ? '.<br>ManyChat account ID is <b>' . esc_html( ChB_Settings()->auth->getMCPageID() ) . '</b>' : '' ) .
			         ( ChB_Settings()->auth->getFBPageID() ? '.<br>Facebook page ID is <b>' . esc_html( ChB_Settings()->auth->getFBPageID() ) . '</b>' : '' ) .
			         '</b></td></tr>';

			$html .= '<tr><td style="padding: 10px">';
			if ( $is_test_env ) {
				$html .= '<div class="button" onclick="sendAjax(rrbot_admin_disconnect_test_domain_url, null, true, \'wy-token-status\', null, \'Do you really want to disconnect test domain?\');">Disconnect this Test Domain</div>';
			} else {
				$html .= '<div class="button" onclick="sendAjax(rrbot_admin_regen_tokens_url, null, true, \'wy-token-status\', null, \'' . esc_attr( $confirm_text ) . '\');">Disconnect</div>';
			}
			$html .= '</td></tr>';
		}

		$html .= '<tr><td><div id="wy-token-status"></div></td></tr></table>';


		return $html;
	}

	public static function getShortConnectionInfo( $is_connected ) {

		if ( $is_connected ) {
			if ( ChB_Settings()->auth->connectionIsDirect() ) {
				$short_connection_info = 'Facebook Page <b>' . esc_html( ChB_Settings()->getParam( 'fb_page_name' ) ? ChB_Settings()->getParam( 'fb_page_name' ) : ChB_Settings()->auth->getFBPageID() ) . '</b>. Facebook page ID is <b>' . ChB_Settings()->auth->getFBPageID() . '</b>';
			} else {
				$short_connection_info = '<b>ManyChat</b> account <b>' . esc_html( ChB_Settings()->getParam( 'mc_page_name' ) ? ChB_Settings()->getParam( 'mc_page_name' ) : ChB_Settings()->auth->getMCPageID() ) . '</b>';
			}

			$short_connection_info = 'Plugin is successfully connected to <b>Wany.Chat</b> server using ' . $short_connection_info .
			                         ( ChB_Settings()->auth->isTestEnv() ? ' as <b>TEST</b> domain' : '' );

		} else {
			$short_connection_info = '';
		}

		return $short_connection_info;
	}

	public static function getFBLoginButton( $is_direct_connection ) {
		if ( ! $is_direct_connection && ChB_Settings()->auth->getFBPageID() ) {
			$page = ChB_Settings()->getParam( 'fb_page_name' ) ? ChB_Settings()->getParam( 'fb_page_name' ) : ChB_Settings()->auth->getFBPageID();
		}

		return
			'<p>Connect your Facebook page' . ( empty( $page ) ? '' : ' <b>' . esc_html( $page ) . '</b>' ) . ' and Instagram Business Account to <b>Wany.Chat</b> App via Facebook login button</p>' .
			'<a class="button" style="color: white; background-color: rgb( 24, 119, 242 ); border: none; padding: 5px 40px;" href="' .
			esc_url( self::getFBLoginPageUrl( $is_direct_connection ) ) . '" width="500px" height="600px" frameborder="0" scrolling="no" allowfullscreen><b>Get Started</b></a>';
	}

	/**
	 * @param bool $is_direct_connection - not a ManyChat connection, so we don't know page_id and we'll have to select it
	 *
	 * @return string
	 */
	public static function getFBLoginPageUrl( $is_direct_connection = false ) {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;

		$select_page = $is_direct_connection ||
		               ( ! ChB_Settings()->auth->getFBPageID() && ! ChB_Settings()->getParam( 'ig_account_username' ) );

		return ChB_Constants::FACEBOOK_LOGIN_PAGE_URL .
		       ( $select_page ?
			       '&select_page=1' .
			       ( $is_direct_connection ?
				       //mc-less connection
				       '&wy_token=' . ChB_Settings()->auth->getWYTokenToBeVerified()
				       :
				       '&wy_token=' . ChB_Settings()->auth->getWYToken() .
				       '&mc_page_id=' . ChB_Settings()->auth->getMCPageID()
			       )
			       :
			       ( ChB_Settings()->auth->getFBPageID() ?
				       //fb-first mc account
				       '&fb_page_id=' . ChB_Settings()->auth->getFBPageID() .
				       '&mc_page_id=' . ChB_Settings()->auth->getMCPageID() .
				       '&fb_page_name=' . urlencode( ChB_Settings()->getParam( 'fb_page_name' ) ) .
				       '&wy_token=' . ChB_Settings()->auth->getWYToken()
				       :
				       //ig-first mc account
				       '&ig_account_username=' . urlencode( ChB_Settings()->getParam( 'ig_account_username' ) ) .
				       '&mc_page_id=' . urlencode( ChB_Settings()->auth->getMCPageID() ) .
				       '&wy_token=' . ChB_Settings()->auth->getWYToken()
			       )
		       ) .
		       '&save_fb_page_url=' . urlencode( 'https://' . ChB_Settings()->getDomainPath() . ChB_Constants::SAVE_FB_PAGE_URL ) .
		       '&referrer_url=' . urlencode( esc_url_raw( admin_url( 'options-general.php?page=' . $page ) ) );
	}

	public static function rrbot_settings_section_wanychat_fb_app_callback() {
		if ( ! ChB_Settings()->auth->getFBAccessToken() ) {
			echo self::getFBLoginButton( false );
		} else {
			$page     = ChB_Settings()->getParam( 'fb_page_name' ) ? ChB_Settings()->getParam( 'fb_page_name' ) : ChB_Settings()->auth->getFBPageID();
			$ig_ba_id = ChB_Settings()->auth->getIGBusinessAccountID();
			if ( $ig_ba_id ) {
				$text = 'Page <b>' . esc_html( $page ) . '</b> and its Instagram Business Account are connected to Wany.Chat Facebook App';
			} else {
				$text = 'Page <b>' . esc_html( $page ) . '</b> is connected to Wany.Chat Facebook App <br>Instagram Business Account is <b>NOT</b> connected';
			}

			?>
            <img width="300px" src="<?php echo esc_url( ChB_Settings()->wany_chat_logo_url ) ?>">
            <p><?php echo $text; ?></p>
			<?php if ( ! ChB_Settings()->auth->connectionIsDirect() ) { ?>
                <div class="button" onclick='sendAjax(rrbot_admin_clear_fb_page_url, null, true, "rrb-status");'>
                    Disconnect
                </div>
			<?php } ?>
            <div id="rrb-status">
            </div>
			<?php
		}
	}

	public static function rrbot_options_page() {
		chb_load();
		?>
        <h2>Wany.Chat Admin Page
            v<?php echo esc_attr( ChB_Updater_WanyChat::instance()->getPluginVersionFromDB() ) ?></h2>
        <style> .rrb-hidden {
                display: none;
            }</style>
        <form action='options.php' method='post'>
			<?php
			settings_fields( 'rrbotPlugin' );
			do_settings_sections( 'rrbotPlugin' );
			?>
        </form>
		<?php
	}
}