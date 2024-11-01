<?php


namespace ChatBot;


class ChB_WPAdminHooks {
	public static function init() {
		add_action( 'admin_init',
			function () {
				chb_load();

				do_action('wany_hook_check_schedule_update_version_db');

				add_filter( 'plugin_action_links_' . get_wy_plugin_basename(), [
					'ChatBot\ChB_WPAdminHooks',
					'add_action_links'
				] );

				add_action( 'admin_notices', [ 'ChatBot\ChB_WPAdminNotices', 'initAdminNotices' ] );

				//USER Custom Fields
				add_action( 'show_user_profile', [ 'ChatBot\ChB_WPAdminHooks', 'show_extra_profile_fields' ] );
				add_action( 'edit_user_profile', [ 'ChatBot\ChB_WPAdminHooks', 'show_extra_profile_fields' ] );
				add_action( 'personal_options_update', [ 'ChatBot\ChB_WPAdminHooks', 'save_extra_profile_fields' ] );
				add_action( 'edit_user_profile_update', [ 'ChatBot\ChB_WPAdminHooks', 'save_extra_profile_fields' ] );

				//Product cats
				add_action( 'product_cat_add_form_fields', [
					'ChatBot\ChB_WPAdminHooks',
					'pcat_taxonomy_add_new_meta_field'
				], 11, 1 );
				add_action( 'product_cat_edit_form_fields', [
					'ChatBot\ChB_WPAdminHooks',
					'pcat_taxonomy_edit_meta_field'
				], 11, 1 );
				add_action( 'edited_product_cat', [
					'ChatBot\ChB_WPAdminHooks',
					'pcat_save_taxonomy_custom_meta'
				], 10, 1 );
				add_action( 'create_product_cat', [
					'ChatBot\ChB_WPAdminHooks',
					'pcat_save_taxonomy_custom_meta'
				], 10, 1 );

				//pa_size synonyms
				if ( ! empty( ChB_Settings()->getParam( 'size_recognition' )['is_on'] ) ) {
					add_action( 'pa_size_add_form_fields', [
						'ChatBot\ChB_WPAdminHooks',
						'pa_size_taxonomy_add_new_meta_field'
					], 10, 1 );
					add_action( 'pa_size_edit_form_fields', [
						'ChatBot\ChB_WPAdminHooks',
						'pa_size_taxonomy_edit_meta_field'
					], 10, 1 );
					add_action( 'edited_pa_size', [
						'ChatBot\ChB_WPAdminHooks',
						'pa_size_save_taxonomy_custom_meta'
					], 10, 1 );
					add_action( 'create_pa_size', [
						'ChatBot\ChB_WPAdminHooks',
						'pa_size_save_taxonomy_custom_meta'
					], 10, 1 );
				}
			}
		);
	}

	public static function add_action_links( $actions ) {
		$mylinks = [
			'<a href="' . admin_url( 'options-general.php?page=rrbot-settings' ) . '">Settings</a>',
		];

		return array_merge( $mylinks, $actions );
	}

	//User
	public static function show_extra_profile_fields( $user ) {

		$rrbot_user_is_bot_user       = get_user_meta( $user->ID, ChB_User::USER_ATTR_IS_BOT_USER, true );
		$rrbot_user_is_bot_guest_user = get_user_meta( $user->ID, ChB_User::USER_ATTR_IS_BOT_GUEST_USER, true );
		$rrbot_user_channel           = get_user_meta( $user->ID, ChB_User::USER_ATTR_CHANNEL, true );
		$rrbot_user_lang              = get_user_meta( $user->ID, ChB_User::USER_ATTR_LANG, true );

		$rrbot_user_mc_user_id               = get_user_meta( $user->ID, ChB_User::USER_ATTR_MC_USER_ID, true );
		$rrbot_manager_settings              = get_user_meta( $user->ID, ChB_User::USER_ATTR_MANAGER_SETINGS, true );
		$rrbot_user_promo                    = get_user_meta( $user->ID, ChB_User::USER_ATTR_PROMO, true );
		$rrbot_user_opened_products          = get_user_meta( $user->ID, ChB_User::USER_ATTR_OPENED_PRODUCTS, true );
		$rrbot_user_opened_products_reminded = get_user_meta( $user->ID, ChB_User::USER_ATTR_OPENED_PRODUCTS_REMINDED, true );
		$rrbot_user_last_opened_products     = get_user_meta( $user->ID, ChB_User::USER_ATTR_LAST_OPENED_PRODUCTS, true );
		$rrbot_user_cart                     = get_user_meta( $user->ID, ChB_User::USER_ATTR_CART, true );
		$rrbot_user_options                  = get_user_meta( $user->ID, ChB_User::USER_ATTR_OPTIONS, true );
		$rrbot_user_status                   = get_user_meta( $user->ID, ChB_User::USER_ATTR_STATUS, true );
		$rrbot_user_try_on_options           = get_user_meta( $user->ID, ChB_User::USER_ATTR_TRY_ON_OPTIONS, true );

		?>
        <h3>WANY.CHAT</h3>

        <table class="form-table">
            <tr>
                <th><label for="contact">Wany.Chat User</label></th>
                <td>
                    <input type="checkbox" disabled="disabled" name="rrbot_user_is_bot_user" id="rrbot_user_is_bot_user"
                           value="1" <?php echo( $rrbot_user_is_bot_user ? 'checked' : '' ); ?> /><br>
                </td>
            </tr>
            <tr>
                <th><label for="contact">Wany.Chat Guest User</label></th>
                <td>
                    <input type="checkbox" disabled="disabled" name="rrbot_user_is_bot_guest_user"
                           id="rrbot_user_is_bot_guest_user"
                           value="1" <?php echo( $rrbot_user_is_bot_guest_user ? 'checked' : '' ); ?> /><br>
                </td>
            </tr>
            <tr>
                <th><label for="contact">Channel</label></th>
                <td>
                    <input type="text" name="rrbot_user_channel" id="rrbot_user_channel"
                           value='<?php echo esc_attr( $rrbot_user_channel ); ?>' class="regular-text"/><br/>
                </td>
            </tr>
            <tr>
                <th><label for="contact">WY Language</label></th>
                <td>
                    <input readonly type="text" name="rrbot_user_lang" id="rrbot_user_lang"
                           value='<?php echo esc_attr( $rrbot_user_lang ); ?>' class="regular-text"/><br/>
                </td>
            </tr>
			<?php if ( defined( 'RRB_EXT' ) ) { ?>
                <tr>
                    <th><label for="contact">ManyChat User ID</label></th>
                    <td>
                        <input type="text" name="rrbot_user_mc_user_id" id="rrbot_user_mc_user_id"
                               value='<?php echo esc_attr( $rrbot_user_mc_user_id ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Manager Settings</label></th>
                    <td>
                        <input type="text" name="rrbot_manager_settings" id="rrbot_manager_settings"
                               value='<?php echo esc_attr( $rrbot_manager_settings ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Promo</label></th>
                    <td>
                        <input type="text" name="rrbot_user_promo" id="rrbot_user_promo"
                               value='<?php echo esc_attr( $rrbot_user_promo ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Opened products</label></th>
                    <td>
                        <input type="text" name="rrbot_user_opened_products" id="rrbot_user_opened_products"
                               value='<?php echo esc_attr( $rrbot_user_opened_products ); ?>'
                               class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Opened products. Reminded</label></th>
                    <td>
                        <input type="text" name="rrbot_user_opened_products_reminded"
                               id="rrbot_user_opened_products_reminded"
                               value='<?php echo esc_attr( $rrbot_user_opened_products_reminded ); ?>'
                               class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Last opened products</label></th>
                    <td>
                        <input type="text" name="rrbot_user_last_opened_products" id="rrbot_user_last_opened_products"
                               value='<?php echo esc_attr( $rrbot_user_last_opened_products ); ?>'
                               class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Cart</label></th>
                    <td>
                        <input type="text" name="rrbot_user_cart" id="rrbot_user_cart"
                               value='<?php echo esc_attr( $rrbot_user_cart ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Options</label></th>
                    <td>
                        <input type="text" name="rrbot_user_options" id="rrbot_user_options"
                               value='<?php echo esc_attr( $rrbot_user_options ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Status</label></th>
                    <td>
                        <input type="text" name="rrbot_user_status" id="rrbot_user_status"
                               value='<?php echo esc_attr( $rrbot_user_status ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
                <tr>
                    <th><label for="contact">Try On Options</label></th>
                    <td>
                        <input type="text" name="rrbot_user_try_on_options" id="rrbot_user_try_on_options"
                               value='<?php echo esc_attr( $rrbot_user_try_on_options ); ?>' class="regular-text"/><br/>
                    </td>
                </tr>
			<?php } ?>
        </table>
		<?php
	}

	public static function save_extra_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		if ( isset( $_POST['rrbot_user_lang'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_LANG, sanitize_text_field( $_POST['rrbot_user_lang'] ) );
		}
		if ( isset( $_POST['rrbot_user_channel'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_CHANNEL, sanitize_text_field( $_POST['rrbot_user_channel'] ) );
		}
		if ( isset( $_POST['rrbot_user_mc_user_id'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_MC_USER_ID, sanitize_text_field( $_POST['rrbot_user_mc_user_id'] ) );
		}
		if ( isset( $_POST['rrbot_manager_settings'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_MANAGER_SETINGS, sanitize_text_field( $_POST['rrbot_manager_settings'] ) );
		}
		if ( isset( $_POST['rrbot_user_promo'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_PROMO, sanitize_text_field( $_POST['rrbot_user_promo'] ) );
		}
		if ( isset( $_POST['rrbot_user_opened_products'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_OPENED_PRODUCTS, sanitize_text_field( $_POST['rrbot_user_opened_products'] ) );
		}
		if ( isset( $_POST['rrbot_user_opened_products_reminded'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_OPENED_PRODUCTS_REMINDED, sanitize_text_field( $_POST['rrbot_user_opened_products_reminded'] ) );
		}
		if ( isset( $_POST['rrbot_user_last_opened_products'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_LAST_OPENED_PRODUCTS, sanitize_text_field( $_POST['rrbot_user_last_opened_products'] ) );
		}
		if ( isset( $_POST['rrbot_user_cart'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_CART, sanitize_text_field( $_POST['rrbot_user_cart'] ) );
		}
		if ( isset( $_POST['rrbot_user_options'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_OPTIONS, sanitize_text_field( $_POST['rrbot_user_options'] ) );
		}
		if ( isset( $_POST['rrbot_user_status'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_STATUS, sanitize_text_field( $_POST['rrbot_user_status'] ) );
		}
		if ( isset( $_POST['rrbot_user_try_on_options'] ) ) {
			update_user_meta( $user_id, ChB_User::USER_ATTR_TRY_ON_OPTIONS, sanitize_text_field( $_POST['rrbot_user_try_on_options'] ) );
		}

		return true;
	}

	//Product Cat Create page
	public static function pcat_taxonomy_add_new_meta_field() {
		?>

        <div class="form-field">
            <label for="rrbot_name_translation">NAME TRANSLATION</label>
            <input type="text" name="rrbot_name_translation" id="rrbot_name_translation">
        </div>

        <div class="form-field">
            <label for="rrbot_cat_gender">GENDER</label>
            <input type="text" name="rrbot_cat_gender" id="rrbot_cat_gender">
        </div>

        <div class="form-field">
            <label for="rrbot_cat_sizes">SIZES</label>
            <input type="text" name="rrbot_cat_sizes" id="rrbot_cat_sizes">
        </div>

        <div class="form-field">
            <label for="rrbot_cat_size_charts">SIZE CHARTS</label>
            <input type="text" name="rrbot_cat_size_charts" id="rrbot_cat_size_charts">
        </div>

        <div class="form-field">
            <label for="rrbot_attr_prod_attn">PRODUCT ATTENTION</label>
            <input type="text" name="rrbot_attr_prod_attn" id="rrbot_attr_prod_attn">
        </div>

		<?php
	}

	//Product Cat Edit page
	public static function pcat_taxonomy_edit_meta_field( $term ) {

		//getting term ID
		$term_id = $term->term_id;

		// retrieve the existing value(s) for this meta field.
		$rrbot_name_translation = get_term_meta( $term_id, ChB_Common::CAT_ATTR_NAME_TRANSLATION, true );
		$rrbot_cat_gender       = get_term_meta( $term_id, ChB_Common::CAT_ATTR_GENDER, true );
		$rrbot_cat_sizes        = get_term_meta( $term_id, ChB_Common::CAT_ATTR_SIZES, true );
		$rrbot_cat_size_charts  = get_term_meta( $term_id, ChB_Common::CAT_ATTR_SIZE_CHARTS, true );
		$rrbot_attr_prod_attn   = get_term_meta( $term_id, ChB_Common::CAT_ATTR_PROD_ATTENTION, true );

		$wy_thumbnail_id = absint( get_term_meta( $term_id, ChB_Common::CAT_ATTR_CAT_IMAGE_ID, true ) );
		if ( $wy_thumbnail_id ) {
			$image = wp_get_attachment_thumb_url( $wy_thumbnail_id );
		} else {
			$image = wc_placeholder_img_src();
		}

		?>
        <tr class="form-field term-thumbnail-wrap">
            <th scope="row" valign="top"><label><?php esc_html_e( 'Image for Wany.Chat', 'wany.chat' ); ?></label></th>
            <td>
                <div id="wy_product_cat_thumbnail" style="float: left; margin-right: 10px;"><img
                            src="<?php echo esc_url( $image ); ?>" width="60px" height="60px"/></div>
                <div style="line-height: 60px;">
                    <input type="hidden" id="wy_product_cat_thumbnail_id" name="wy_product_cat_thumbnail_id"
                           value="<?php echo esc_attr( $wy_thumbnail_id ); ?>"/>
                    <button type="button"
                            class="wy_upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
                    <button type="button"
                            class="wy_remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
                </div>
                <div class="clear"></div>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="rrbot_name_translation">Name translation</label></th>
            <td>
                <input type="text" name="rrbot_name_translation" id="rrbot_name_translation"
                       value='<?php echo esc_attr( $rrbot_name_translation ) ? esc_attr( $rrbot_name_translation ) : ''; ?>'>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="rrbot_cat_gender">Gender</label></th>
            <td>
                <input type="text" name="rrbot_cat_gender" id="rrbot_cat_gender"
                       value='<?php echo esc_attr( $rrbot_cat_gender ) ? esc_attr( $rrbot_cat_gender ) : ''; ?>'>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="rrbot_cat_sizes">Sizes</label></th>
            <td>
                <input type="text" name="rrbot_cat_sizes" id="rrbot_cat_sizes"
                       value='<?php echo esc_attr( $rrbot_cat_sizes ) ? esc_attr( $rrbot_cat_sizes ) : ''; ?>'>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="rrbot_cat_size_charts">Size charts</label></th>
            <td>
                <input type="text" name="rrbot_cat_size_charts" id="rrbot_cat_size_charts"
                       value='<?php echo esc_attr( $rrbot_cat_size_charts ) ? esc_attr( $rrbot_cat_size_charts ) : ''; ?>'>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="rrbot_attr_prod_attn">Product attention</label></th>
            <td>
                <input type="text" name="rrbot_attr_prod_attn" id="rrbot_attr_prod_attn"
                       value='<?php echo esc_attr( $rrbot_attr_prod_attn ) ? esc_attr( $rrbot_attr_prod_attn ) : ''; ?>'>
            </td>
        </tr>

		<?php
	}

	// Save extra taxonomy fields callback function.
	public static function pcat_save_taxonomy_custom_meta( $term_id ) {

		if ( isset( $_POST['rrbot_name_translation'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_NAME_TRANSLATION, sanitize_text_field( $_POST['rrbot_name_translation'] ) );
		}

		if ( isset( $_POST['rrbot_cat_gender'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_GENDER, sanitize_text_field( $_POST['rrbot_cat_gender'] ) );
		}

		if ( isset( $_POST['rrbot_cat_sizes'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_SIZES, sanitize_text_field( $_POST['rrbot_cat_sizes'] ) );
		}

		if ( isset( $_POST['rrbot_cat_size_charts'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_SIZE_CHARTS, sanitize_text_field( $_POST['rrbot_cat_size_charts'] ) );
		}

		if ( isset( $_POST['rrbot_attr_prod_attn'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_PROD_ATTENTION, sanitize_text_field( $_POST['rrbot_attr_prod_attn'] ) );
		}

		if ( isset( $_POST['wy_product_cat_thumbnail_id'] ) ) {
			update_term_meta( $term_id, ChB_Common::CAT_ATTR_CAT_IMAGE_ID, absint( sanitize_text_field( $_POST['wy_product_cat_thumbnail_id'] ) ) );
		}
	}

	//Product Size Create page
	public static function pa_size_taxonomy_add_new_meta_field() {
		?>
        <div class="form-field">
            <label for="synonyms">SYNONYMS</label>
            <input type="text" name="synonyms" id="synonyms">
        </div>

		<?php
	}

	//Product Size Edit page
	public static function pa_size_taxonomy_edit_meta_field( $term ) {

		//getting term ID
		$term_id = $term->term_id;

		// retrieve the existing value(s) for this meta field.
		$synonyms = get_term_meta( $term_id, ChB_Common::SIZE_ATTR_SYNONYMS, true );

		?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="synonyms">SYNONYMS</label></th>
            <td>
                <input type="text" name="synonyms" id="synonyms"
                       value='<?php echo esc_attr( $synonyms ) ? esc_attr( $synonyms ) : ''; ?>'>
            </td>
        </tr>
		<?php
	}

	// Save extra taxonomy fields callback function.
	public static function pa_size_save_taxonomy_custom_meta( $term_id ) {
		if ( isset( $_POST['synonyms'] ) ) {
			update_term_meta( $term_id, ChB_Common::SIZE_ATTR_SYNONYMS, sanitize_text_field( $_POST['synonyms'] ) );
		}
	}
}