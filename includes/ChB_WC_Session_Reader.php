<?php


namespace ChatBot;

( class_exists( '\WC_Session_Handler' ) && defined( 'WC_SESSION_CACHE_GROUP' ) ) || exit;

class ChB_WC_Session_Reader extends \WC_Session_Handler {

	/**
	 * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @return string
	 */
	private function get_cache_prefix() {
		return \WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP );
	}

	public function get_session_by_id( $customer_id ) {
		global $wpdb;

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, WC_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) ); // @codingStandardsIgnoreLine.
		}

		return maybe_unserialize( $value );
	}
}