<?php

namespace ChatBot;

class ChB_KeyValueStorageSQL extends ChB_KeyValueStorage {
	private string $kvs_table_name;

	private function __construct() {
		global $wpdb;
		$table_prefix         = $wpdb->prefix ? $wpdb->prefix : 'wp_';
		$this->kvs_table_name = $table_prefix . 'chb_kvs';
	}

	public static function connect() {
		return new ChB_KeyValueStorageSQL();
	}

	public static function connectFirstTime() {
		ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime' );
		$kvs = self::connect();

		//checking maybe table already exists
		$rand_key = 'FIRSTTIMECONNECT_' . ChB_Common::my_rand_string( 20 );
		$res      = $kvs->setex( $rand_key, 120, '1' );
		if ( $res ) {
			ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime: KVS table already exists' );
		} else {
			//creating table
			$res = $kvs->createKVSTable();
			if ( $res ) {
				ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime: successfully created KVS table' );
				//trying to set a test key
				$rand_key = 'FIRSTTIMECONNETCT_' . ChB_Common::my_rand_string( 20 );
				$res      = $kvs->setex( $rand_key, 120, '1' );
				if ( $res ) {
					ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime: successfully set a test key' );
				} else {
					ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime: set a test key FAILED' );
				}
			} else {
				ChB_Common::my_log( 'ChB_KeyValueStorageSQL::connectFirstTime: FAILED to create KVS table' );
			}
		}

		return $res ? $kvs : false;
	}

	public function close() {
	}

	public function kvs_version() {
		return 'SQLV1.0';
	}

	public function get( $key ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return null;
		}

		try {
			$q   = 'SELECT chb_value FROM ' . $this->kvs_table_name . ' ' .
			       '	WHERE chb_key = %s AND (chb_ttl = 0 OR chb_ttl >= UNIX_TIMESTAMP())';
			$q   = $wpdb->prepare( $q, $key );
			$res = $wpdb->get_row( $q, ARRAY_A );

			return ( $res === null ? false : $res['chb_value'] );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::get() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return null;
	}

	public function ttl( $key ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return null;
		}

		try {
			$q   = 'SELECT CASE WHEN chb_ttl = 0 THEN -1 ELSE chb_ttl - UNIX_TIMESTAMP() END AS chb_ttl FROM ' . $this->kvs_table_name . ' ' .
			       '	WHERE chb_key = %s AND (chb_ttl = 0 OR chb_ttl >= UNIX_TIMESTAMP())';
			$q   = $wpdb->prepare( $q, $key );
			$res = $wpdb->get_row( $q, ARRAY_A );

			return ( $res === null ? - 2 : intval( $res['chb_ttl'] ) );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::ttl() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return null;
	}

	/**
	 * @param $key - for MYSQL 5.7 and upwards: max 768 characters, otherwise - 191
	 * @param $value
	 *
	 * @return bool
	 */
	public function set( $key, $value ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return false;
		}

		try {
			$q   = 'INSERT INTO ' . $this->kvs_table_name . ' (chb_key, chb_value, chb_ttl) VALUES(%s, %s, 0) ' .
			       'ON DUPLICATE KEY UPDATE chb_key=VALUES(chb_key), chb_value=VALUES(chb_value), chb_ttl=VALUES(chb_ttl)';
			$q   = $wpdb->prepare( $q, [ $key, $value ] );
			$res = $wpdb->query( $q );

			//result is the same as redis set() gives
			return ( $res !== false );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::set() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return false;
	}

	public function setex( $key, $ttl, $value ) {
		global $wpdb;
		if ( ! $wpdb || $ttl <= 0 ) {
			return false;
		}

		try {
			$q = 'INSERT INTO ' . $this->kvs_table_name . ' (chb_key, chb_value, chb_ttl) VALUES(%s, %s, UNIX_TIMESTAMP() + %d) ' .
			     'ON DUPLICATE KEY UPDATE chb_key=VALUES(chb_key), chb_value=VALUES(chb_value), chb_ttl=VALUES(chb_ttl)';
			$q = $wpdb->prepare( $q, [ $key, $value, $ttl ] );

			//this query returns
			//2 - if update on duplicate key happened
			//1 - if new record inserted
			//0 - if the exact same record already exists
			//false - on error
			$res = $wpdb->query( $q );

			//result is the same as redis setex() gives
			return ( $res !== false );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::setex() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return false;
	}

	public function del( $key ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return false;
		}

		if ( is_array( $key ) ) {
			$keys = &$key;
		} else {
			$keys = [ $key ];
		}

		$count = 0;
		foreach ( $keys as $cur_key ) {
			try {
				$q     = 'DELETE FROM ' . $this->kvs_table_name . ' WHERE chb_key = %s AND (chb_ttl = 0 OR chb_ttl >= UNIX_TIMESTAMP())';
				$q     = $wpdb->prepare( $q, $cur_key );
				$count += $wpdb->query( $q );
			} catch ( \Exception $e ) {
				ChB_Common::my_log( 'ChB_KeyValueStorage::del() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
			}
		}

		return $count;
	}

	public function exists( $key ) {
		return $this->get( $key ) !== false;
	}

	public function deleteAllKeysByPrefix( $prefix, $captcha_ts ) {
		if ( time() - $captcha_ts > 20 ) {
			return false;
		}
		global $wpdb;
		if ( ! $wpdb ) {
			return false;
		}

		try {
			$q = 'DELETE FROM ' . $this->kvs_table_name . ' WHERE chb_key LIKE %s';
			$q = $wpdb->prepare( $q, $wpdb->esc_like( $prefix ) . '%' );

			return $wpdb->query( $q );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::deleteAllKeysByPrefix() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return false;
	}

	public function scanAllKeysByPrefix( $prefix ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return [];
		}

		try {
			$q = 'SELECT chb_key FROM ' . $this->kvs_table_name . ' WHERE chb_key LIKE %s AND (chb_ttl = 0 OR chb_ttl >= UNIX_TIMESTAMP())';
			$q = $wpdb->prepare( $q, $wpdb->esc_like( $prefix ) . '%' );

			return $wpdb->get_col( $q );
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::scanAllKeysByPrefix() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return [];
	}

	public function scanAllByPrefix( $prefix ) {
		global $wpdb;
		if ( ! $wpdb ) {
			return [];
		}

		try {
			$q    = 'SELECT chb_key, chb_value FROM ' . $this->kvs_table_name . ' WHERE chb_key LIKE %s AND (chb_ttl = 0 OR chb_ttl >= UNIX_TIMESTAMP())';
			$q    = $wpdb->prepare( $q, $wpdb->esc_like( $prefix ) . '%' );
			$rows = $wpdb->get_results( $q, ARRAY_A );
			$res  = [];
			if ( $rows ) {
				foreach ( $rows as $row ) {
					$res[ $row['chb_key'] ] = $row['chb_value'];
				}
			}

			return $res;
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::scanAllKeysByPrefix() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		}

		return [];
	}

	public function createKVSTable() {
		global $wpdb;
		if ( empty( $wpdb ) ) {
			return false;
		}

		$lens = [768, 191];
		foreach ($lens as $len) {

			try {
				$q = 'CREATE TABLE `' . $this->kvs_table_name . '`(' .
				     '`chb_key` VARCHAR(' . $len . ') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,' .
				     '`chb_value` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,' .
				     '`chb_ttl` INT(11) UNSIGNED NOT NULL,' .
				     'PRIMARY KEY (`chb_key`),' .
				     'INDEX `chb_ttl_index`(`chb_ttl`)' .
				     ') ENGINE = InnoDB CHARSET = utf8 COLLATE utf8_bin;';

				if ($wpdb->query( $q ) !== false) {
					ChB_Common::my_log('success! len=' . $len);
					return true;
				}
			} catch ( \Exception $e ) {
				ChB_Common::my_log( 'ChB_KeyValueStorage::createKVSTable() len=' . $len . ' Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
			}
		}

		ChB_Common::my_log('failed to create kvs table');
		return false;
	}

	public function cleanUpExpiredKeys() {
		global $wpdb;
		if ( ! $wpdb ) {
			return false;
		}

		ChB_Common::my_log('KVS SQL cleanUpExpiredKeys START');
		$qty = 0;
		try {
			$q = 'DELETE FROM ' . $this->kvs_table_name . ' WHERE chb_ttl <> 0 AND chb_ttl < UNIX_TIMESTAMP()';

			$qty = $wpdb->query( $q );
			return $qty;
		} catch ( \Exception $e ) {
			ChB_Common::my_log( 'ChB_KeyValueStorage::cleanUpExpiredKeys() Exception: ' . $e->getMessage() . ' ' . $e->getTraceAsString() );
		} finally {
			ChB_Common::my_log('KVS SQL cleanUpExpiredKeys FINISH qty=' . $qty);
		}

		return false;
	}

	private const TESTPREF = 'testKVS';

	public static function testKVS( ChB_KeyValueStorage $kvs1, ChB_KeyValueStorage $kvs2 ) {
		$pref = self::TESTPREF;
		$ind  = 1190;
		$keys = [];

		$key    = $pref . $ind ++;
		$keys[] = $key;
		$val    = ChB_Common::my_rand_string( 100000 );

//		echo "\n\n------\n";
//		$res1 = $kvs1->setex($key, 10, $val);
//		$res2 = $kvs2->setex($key, 10, $val);
//		var_dump($res1);
//		var_dump($res2);
//		if ($res1 === $res2)
//			echo "\nsucces!\n";
//		else
//			echo "\nFAIL!!!\n";

//		$key = $pref . $ind++;
//		$keys[] = $key;
//		$val = ChB_Common::my_rand_string(1000);
//
//		echo "\n\n------\n";
//		$res1 = $kvs1->setex($key, 10, $val);
//		$res2 = $kvs2->setex($key, 10, $val);
//		var_dump($res1);
//		var_dump($res2);
//		if ($res1 === $res2)
//			echo "\nsucces!\n";
//		else
//			echo "\nFAIL!!!\n";

		echo "\n\n--CLEAN UP--\n";
		var_dump( $kvs2->cleanUpExpiredKeys() );

		echo "\n\n------\n";
		$res1 = $kvs1->ttl( $key );
		$res2 = $kvs2->ttl( $key );
		var_dump( $res1 );
		var_dump( $res2 );
		if ( $res1 === $res2 ) {
			echo "\nsucces!\n";
		} else {
			echo "\nFAIL!!!\n";
		}

	}

	public function importAllFromKVS( ChB_KeyValueStorage $kvs_src ) {
		$prefix = ChB_Settings()->salt;
		ChB_Settings()->tic( 'red_scan' );
		$kv = $kvs_src->scanAllByPrefix( $prefix );
		ChB_Settings()->toc( 'red_scan' );


		foreach ( $kv as $key => $val ) {
			ChB_Settings()->tic( 'red_get' );
			$ttl = $kvs_src->ttl( $key );
			ChB_Settings()->toc( 'red_get' );

			if ( $ttl > 0 ) {
				$this->setex( $key, $ttl, $val );
			} elseif ( $ttl ) {
				$this->set( $key, $val );
			}

			ChB_Settings()->tic( 'kvs_get' );
			$check_val = $this->get( $key );
			ChB_Settings()->toc( 'kvs_get' );
			ChB_Settings()->tic( 'kvs_ttl' );
			$check_ttl = $this->ttl( $key );
			ChB_Settings()->toc( 'kvs_ttl' );

			if ( $check_ttl !== $ttl || $check_val !== $val ) {
				echo "CHECK FAIL \n";
				echo 'key=' . esc_attr( $key ) . "\n";
				echo 'val=' . esc_attr( $val ) . "\n";
				echo 'ttl=' . esc_attr( $ttl ) . "\n\n";
				echo 'check_val=' . esc_attr( $check_val ) . "\n";
				echo 'check_ttl=' . esc_attr( $check_ttl ) . "\n\n";
			} else {
				echo "CHECK OK \n";
			}
		}
	}
}