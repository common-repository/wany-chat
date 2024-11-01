<?php

namespace ChatBot;

class ChB_KeyValueStorageRedis extends ChB_KeyValueStorage {
	private \Redis $redis;

	private function __construct( \Redis $redis ) {
		$this->redis = $redis;
	}

	public static function connect( $redis_host, $redis_port ) {

		try {
			$redis = new \Redis();
			if ( $redis->connect( $redis_host, $redis_port ) ) {
				return new ChB_KeyValueStorageRedis( $redis );
			}
		} catch ( \Throwable $e ) {
			ChB_Common::my_log('error in ' . __CLASS__ . '::' . __FUNCTION__ . '() ' . $e->getMessage() . ' ' . $e->getTraceAsString());
		}

		return null;
	}

	public function close() {
		if ( $this->redis ) {
			$this->redis->close();
		}
	}

	public function kvs_version() {
		return 'RedisV1.0';
	}

	public function get( $key ) {
		return $this->redis->get( $key );
	}

	public function ttl( $key ) {
		return $this->redis->ttl( $key );
	}

	public function set( $key, $value ) {
		return $this->redis->set( $key, $value );
	}

	public function setex( $key, $ttl, $value ) {
		return $this->redis->setex( $key, $ttl, $value );
	}

	public function del( $key ) {
		return $this->redis->del( $key );
	}

	public function exists( $key ) {
		return $this->redis->exists( $key );
	}

	public function deleteAllKeysByPrefix( $prefix, $captcha_ts ) {
		if ( ! $this->redis ) {
			return false;
		}
		if ( time() - $captcha_ts > 20 ) {
			return false;
		}

		$keys = $this->scanAllKeysByPrefix( $prefix );

		return $this->redis->del( $keys );
	}

	public function scanAllKeysByPrefix( $prefix ) {
		if ( ! $this->redis ) {
			return false;
		}

		$it    = null;
		$count = 10000;
		$res   = null;

		while ( ( $keys = $this->redis->scan( $it, $prefix . '*', $count ) ) !== false ) {
			$res = ( empty( $res ) ? $keys : array_merge( $res, $keys ) );
		}

		return ( $res === null ? [] : $res );
	}

	public function scanAllByPrefix( $prefix ) {
		$res = [];

		$keys = $this->scanAllKeysByPrefix( $prefix );
		if ( $keys ) {
			foreach ( $keys as $key ) {
				$res[ $key ] = $this->get( $key );
			}
		}

		return $res;
	}
}