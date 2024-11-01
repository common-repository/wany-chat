<?php


namespace ChatBot;


class ChB_Cron {
	public static function init() {
		add_filter( 'cron_schedules', [ 'ChatBot\ChB_Cron', 'addCronSchedule' ] );

		if ( ! wp_next_scheduled( 'wany_cron_every_minute' ) ) {
			wp_schedule_event( time(), 'every_minute', 'wany_cron_every_minute' );
		}

		add_action( 'wany_cron_every_minute', [ 'ChatBot\ChB_Cron', 'runWanyCronEveryMinute' ] );

		if ( ! wp_next_scheduled( 'wany_cron_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wany_cron_daily' );
		}

		add_action( 'wany_cron_daily', [ 'ChatBot\ChB_Cron', 'runWanyCronDaily' ] );
	}

	public static function addCronSchedule( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60,
			'display'  => __( 'One minute' ),
		);

		return $schedules;
	}

	public static function runWanyCronEveryMinute() {
		chb_load();
		ChB_WooRemarketing::processRemarketing();
	}

	public static function runWanyCronDaily() {
		chb_load();
		ChB_Common::my_log( 'runWanyCronDaily START' );

		if ( ChB_Settings()->kvs instanceof ChB_KeyValueStorageSQL ) {
			ChB_Settings()->kvs->cleanUpExpiredKeys();
		}

		ChB_WooRemarketing::cleanUp();

		ChB_Common::my_log( 'runWanyCronDaily FINISH' );
	}
}