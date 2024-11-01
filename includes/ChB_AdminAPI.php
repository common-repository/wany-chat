<?php


namespace ChatBot;


class ChB_AdminAPI {
	public static function run( ?\WP_REST_Request $request ) {
		$task = empty( $_GET['task'] ) ? null : sanitize_text_field( $_GET['task'] );
		if ( $task === ChB_Constants::RRBOT_ADMIN_TASK_GET_TOKENS_FORM ) {
			return ChB_SettingsPage::getWYConnectionSection( true, false );
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_REGEN_TOKENS ) {
			return ChB_Auth::regenerateTokens_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_CLEAR_FB_PAGE ) {
			return ChB_Auth::clearFBPage_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_CONNECT_TEST_DOMAIN ) {
			return ChB_Auth::connectTestDomain_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_DISCONNECT_TEST_DOMAIN ) {
			return ChB_Auth::disconnectTestDomain_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_CONNECT_MCLESS_DOMAIN ) {
			return ChB_Auth::connectMCLessDomain_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_DISCONNECT_MCLESS_DOMAIN ) {
			return ChB_Auth::disconnectMCLessDomain_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_SET_MERCADO_PAGO_TOKEN ) {
			return ChB_Auth::setMercadoPagoAccessToken_AdminAPI();
		} elseif ( $task === ChB_Constants::RRBOT_ADMIN_TASK_SET_ROBOKASSA_OPTIONS ) {
			return ChB_Auth::setRobokassaOptions_AdminAPI();
		}

		return [];
	}
}