<?php


namespace ChatBot;


class ChB_FLOW_Lang {
	public static function run( ChatBot $ChB ) {

		if ( $ChB->task === 'manychat_lng_initLang' ) {

			return ChB_FLOW_Lang::initLang( $ChB );

		} elseif ( $ChB->task === 'manychat_lng_getLangMenu' ) {

			return ChB_FLOW_Lang::getLangMenu( $ChB );

		} elseif ( $ChB->task === 'manychat_lng_changeLang' ) {

			return ChB_FLOW_Lang::changeLang( $ChB );

		}

		return [];
	}

	public static function getLangMenu( ChatBot $ChB ) {
		$used_languages = ChB_Lang::getUsedLanguages();
		$text           = '';
		$buttons        = [];
		foreach ( $used_languages as $lang_code ) {
			if ( ! empty( ChB_Lang::LANGS[ $lang_code ] ) ) {
				$text      .= ( $text ? "\n" . ChB_Lang::LANGS[ $lang_code ]['text1'] : ChB_Lang::LANGS[ $lang_code ]['text1'] );
				$buttons[] = ChatBot::makeDynamicBlockCallbackButton( $ChB, ChB_Lang::LANGS[ $lang_code ]['caption'], [
					'task' => 'manychat_lng_changeLang',
					'val'  => $lang_code
				] );
			}
		}

		return [ 'version' => 'v2', 'content' => [ 'messages' => ChB_Common::makeManyButtons( $buttons, $text ) ] ];
	}

	public static function getMessage4LangKW( ChatBot $ChB ) {
		$used_languages = ChB_Lang::getUsedLanguages();
		$caption        = ChB_Lang::translate(ChB_Lang::LNG0176);
		$text           = '';

		foreach ( $used_languages as $lang_code ) {
			if ( ! empty( ChB_Lang::LANGS[ $lang_code ] ) ) {
				$caption .= ChB_Lang::LANGS[ $lang_code ]['flag'];
				$text    .= ( $text ? "\n" . ChB_Lang::LANGS[ $lang_code ]['text2'] : ChB_Lang::LANGS[ $lang_code ]['text2'] );
			}
		}

		return [
			'type'    => 'text',
			'text'    => str_replace( '%s1', strtoupper( ChB_Constants::KW_LANG ), $text ),
			'buttons' => [ ChatBot::makeDynamicBlockCallbackButton( $ChB, $caption, [ 'task' => 'manychat_lng_getLangMenu' ] ) ]
		];
	}

	public static function initLang( ChatBot $ChB ) {
		return ChB_FLOW_Common::makeResponse4APPAction( ChB_Settings()->lang );
	}

	public static function changeLang( ChatBot $ChB ) {
		$messages = [
			[
				'type' => 'text',
				'text' => ChB_Lang::translate( ChB_Lang::LNG0008 )
			]
		];

		$args = [
			'user'    => $ChB->user,
			'actions' => [
				[
					'fields'   => [
						'field_name'  => ChB_ManyChat::CF_Lang,
						'field_value' => ChB_Settings()->lang
					],
					'endpoint' => '/fb/subscriber/setCustomFieldByName'
				],

				[
					'fields'   => [ 'flow_ns' => ChB_ManyChat::getMCFlowNS( $ChB->user->channel, 'flow_catalog' ) ],
					'endpoint' => '/fb/sending/sendFlow'
				]
			]
		];
		ChB_Events::scheduleSingleEventOnShutdown( $ChB, ChB_Events::CHB_EVENT_MC_API_POST, $args );

		return [ 'version' => 'v2', 'content' => [ 'messages' => $messages ] ];
	}
}