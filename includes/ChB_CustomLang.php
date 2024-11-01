<?php


namespace ChatBot;


class ChB_CustomLang {

	const OPTION_PREFIX = 'wy_cstm_lng';
	const MENU_SLUG = 'wy_cstm_lng';

	private static array $_OPTIONS;

	public static function getCustomTranslation( $key, $lang ) {
		if ( ! isset( self::$_OPTIONS[ $key ] ) ) {
			self::$_OPTIONS[ $lang ] = get_option( self::getOptionName( $lang ) );
		}

		return empty( self::$_OPTIONS[ $lang ][ $key ] ) ? null : self::$_OPTIONS[ $lang ][ $key ];
	}

	public static function getOptionName( $lang ) {
		return self::OPTION_PREFIX . '_' . $lang;
	}

	public static function init() {
		add_action( 'admin_menu', [ '\ChatBot\ChB_CustomLang', 'addAdminMenu' ] );
		add_action( 'admin_init', [ '\ChatBot\ChB_CustomLang', 'settingsInit' ] );
	}

	public static function getLangListForSettingsPage() {

		$res = '';
		foreach ( ChB_Lang::LANGS as $lang => $lang_details ) {

			$link = menu_page_url( self::MENU_SLUG . '_' . $lang, false );
			$res  = $res . '<a target="_blank" href="' . $link . '"><code><b>' . $lang . '</b></code> ' . $lang_details['caption'] . '</a><br>';

		}

		return $res;
	}


	public static function addAdminMenu() {
		chb_load();

		foreach ( array_keys( ChB_Lang::LANGS ) as $lang ) {

			$slug = self::MENU_SLUG . '_' . $lang;
			$func = 'optionsPage_' . $lang;

			if ( method_exists( '\ChatBot\ChB_CustomLang', $func ) ) {

				add_options_page( 'Wany.Chat - ' . $lang, 'Wany.Chat - ' . $lang, 'manage_options', $slug, [
					'\ChatBot\ChB_CustomLang',
					$func
				] );

				remove_submenu_page( 'options-general.php', $slug );
			}
		}
	}

	public static function settingsInit() {
		chb_load();

		foreach ( ChB_Lang::LANGS as $lang => $lang_details ) {

			$option_name  = self::getOptionName( $lang );
			$page_name    = $option_name;
			$option_group = $option_name;

			register_setting( $option_group, $option_name );

			$lang_constants = ChB_Lang::getLangConstants();

			$section_id = $option_name . '_section_1';
			add_settings_section(
				$section_id,
				'Wany.Chat Custom Translation: ' . $lang_details['caption'],
				[ '\ChatBot\ChB_CustomLang', 'renderSection' ],
				$page_name
			);


			foreach ( $lang_constants as $lang_constant ) {
				if ( empty( $lang_constant[0] ) || empty( $lang_constant[1] ) ) {
					continue;
				}
				$key  = $lang_constant[0];
				$args = [
					'field_value' => self::getCustomTranslation( $key, $lang ),
					'field_name'  => $option_name . '[' . $key . ']',
					'field_id'    => $option_name . '_' . $key,
					'label_for'   => $key,
					'original'    => empty( $lang_constant[ $lang_details['ind'] ] ) ? '' : $lang_constant[ $lang_details['ind'] ]
				];

				add_settings_field(
					$key,
					$key,
					[ '\ChatBot\ChB_CustomLang', 'renderField' ],
					$page_name,
					$section_id,
					$args
				);

			}
		}

	}

	public static function renderSection() {
		?>
        Here you can customize translations used in your bot.<br>
        If you leave input blank, the default translation will be used.<br>
        Please leave strings like <code>%s1</code> and
        <code>%s2</code> as is. They will be replaced with actual values by bot.
		<?php
		submit_button();

	}

	public static function renderField( $args ) {
		$rows = ( str_contains( $args['original'], "\n" ) ? 2 : 1 );
		echo '<textarea rows="' . $rows . '"readonly style="width: 900px;margin-bottom: 10px" >' . $args['original'] . '</textarea><br>';
		?>
        <textarea id='<?php echo esc_attr( $args['field_id'] ) ?>'
                  name='<?php echo esc_attr( $args['field_name'] ) ?>'
                  style="width: 900px;"><?php echo esc_attr( $args['field_value'] ); ?></textarea>
		<?php
	}


	public static function optionsPage( $lang ) {
		chb_load();
		$page_name    = self::getOptionName( $lang );
		$option_group = $page_name;
		?>
        <form action='options.php' method='post'>
			<?php
			settings_fields( $option_group );
			do_settings_sections( $page_name );
			submit_button();
			?>
        </form>
		<?php
	}

	public static function optionsPage_en() {
		self::optionsPage( 'en' );
	}

	public static function optionsPage_ru() {
		self::optionsPage( 'ru' );
	}

	public static function optionsPage_vi() {
		self::optionsPage( 'vi' );
	}

	public static function optionsPage_ka() {
		self::optionsPage( 'ka' );
	}

	public static function optionsPage_it() {
		self::optionsPage( 'it' );
	}

	public static function optionsPage_es() {
		self::optionsPage( 'es' );
	}

	public static function optionsPage_pt() {
		self::optionsPage( 'pt' );
	}

	public static function optionsPage_fr() {
		self::optionsPage( 'fr' );
	}

	public static function optionsPage_ar() {
		self::optionsPage( 'ar' );
	}

	public static function printCSV4Translation() {

        $lang_constants = ChB_Lang::getLangConstants();

		echo "---\t" . ChB_Lang::LANGS['en']['text1'] . "\n";
		echo "---\t" . ChB_Lang::LANGS['en']['text2'] . "\n";

        foreach ( $lang_constants as $lang_constant ) {
            if ( ! empty( $lang_constant[1] ) ) {
                echo  $lang_constant[0] . "\t" . str_replace("\n", " ", $lang_constant[2]) . "\n";
            }
        }
	}

}
