<?php

namespace ChatBot;


abstract class ChB_KeyValueStorage {
	abstract public function kvs_version();

	abstract public function get( $key );

	abstract public function set( $key, $value );

	abstract public function setex( $key, $ttl, $value );

	abstract public function del( $key );

	abstract public function exists( $key );

	abstract public function deleteAllKeysByPrefix( $prefix, $captcha_ts );

	abstract public function scanAllKeysByPrefix( $prefix );

	abstract public function scanAllByPrefix( $prefix );
}