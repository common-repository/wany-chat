<?php

namespace ChatBot;


class ChB_Manager_Settings {
	private $manager_settings_data;
	private $wp_user_id;

	public function __construct( ChB_User $user ) {
		if ( ChB_Roles::userHasRole( ChB_Roles::ROLE_SHOP_MANAGER, $user->fb_user_id ) ) {
			$this->manager_settings_data = self::getUserManagerSettingsData( $user->wp_user_id );
		}
		$this->wp_user_id = $user->wp_user_id;
	}

	public static function setUserAsShopManager( ChB_User $user, $passphrase ) {

		if ( ! $passphrase || $passphrase !== ChB_Settings()->getParam( 'shop_manager_passphrase' ) ) {
			return false;
		}
		ChB_Roles::setUserRole( ChB_Roles::ROLE_SHOP_MANAGER, $user->fb_user_id );

		//not turning on notifications on text messages ('$managers2notify') by default


		$options2update  = [];
		$managers2notify_on_tth = ChB_Settings()->getParam( 'managers2notify_on_tth' );
		if ( ! in_array( $user->fb_user_id, $managers2notify_on_tth ) ) {
			$managers2notify_on_tth[]                 = $user->fb_user_id;
			$options2update['managers2notify_on_tth'] = $managers2notify_on_tth;
		}
		$managers2notify_on_orders = ChB_Settings()->getParam( 'managers2notify_on_orders' );
		if ( ! in_array( $user->fb_user_id, $managers2notify_on_orders ) ) {
			$managers2notify_on_orders[]                 = $user->fb_user_id;
			$options2update['managers2notify_on_orders'] = $managers2notify_on_orders;
		}
		$managers2notify_on_completed_orders = ChB_Settings()->getParam( 'managers2notify_on_completed_orders' );
		if ( ! in_array( $user->fb_user_id, $managers2notify_on_completed_orders ) ) {
			$managers2notify_on_completed_orders[]                 = $user->fb_user_id;
			$options2update['managers2notify_on_completed_orders'] = $managers2notify_on_completed_orders;
		}

		ChB_Settings()->updateSomeOptions( $options2update );

		return true;
	}

	private static function getUserManagerSettingsData( $wp_user_id ) {
		$manager_settings_data = get_user_meta( $wp_user_id, ChB_User::USER_ATTR_MANAGER_SETINGS, true );
		if ( ! empty( $manager_settings_data ) ) {
			return json_decode( $manager_settings_data, true );
		} else {
			return [];
		}
	}

	public static function saveUserManagerSettingsData( $wp_user_id, $manager_settings_data ) {
		return update_user_meta( $wp_user_id, ChB_User::USER_ATTR_MANAGER_SETINGS, json_encode( $manager_settings_data ) );
	}

	public function markCustomer( $customer_fb_user_id ) {
		//При пометке пользователя сбрасываем все остальные пометки
		$this->manager_settings_data['marks'] = [ 'mcust' => $customer_fb_user_id ];

		return self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
	}

	public function unmarkCustomer() {
		$this->manager_settings_data['marks'] = [];

		return self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
	}

	public function getMarkedCustomer() {
		if ( empty( $this->manager_settings_data['marks']['mcust'] ) ) {
			return false;
		} else {
			return $this->manager_settings_data['marks']['mcust'];
		}
	}

	public function customerIsMarked() {
		return ! empty( $this->manager_settings_data['marks']['mcust'] );
	}

	public function markProduct( $product_id ) {
		if ( empty( $this->manager_settings_data['marks']['mprods'] ) ||
		     array_search( $product_id, $this->manager_settings_data['marks']['mprods'] ) === false ) {
			$this->manager_settings_data['marks']['mprods'][] = $product_id;

			return self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
		}

		return true;
	}

	public function unmarkProduct( $product_id ) {
		if ( empty( $this->manager_settings_data['marks']['mprods'] ) ) {
			return true;
		}

		if ( ( $key = array_search( $product_id, $this->manager_settings_data['marks']['mprods'] ) ) !== false ) {
			unset( $this->manager_settings_data['marks']['mprods'][ $key ] );
		}

		return self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
	}

	public function unmarkAllProducts() {
		if ( ! empty( $this->manager_settings_data['marks']['mprods'] ) ) {
			unset( $this->manager_settings_data['marks']['mprods'] );
			self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
		}
	}

	public function getMarkedProducts() {
		if ( empty( $this->manager_settings_data['marks']['mprods'] ) ) {
			return [];
		}

		return $this->manager_settings_data['marks']['mprods'];
	}

	public function productIsMarked( $product_id ) {
		if ( empty( $this->manager_settings_data['marks']['mprods'] ) ) {
			return false;
		}

		return in_array( $product_id, $this->manager_settings_data['marks']['mprods'] );
	}

	public function markCategory( $cat_slug, $brand, $size_slug ) {
		$val = [ 'cat_slug' => $cat_slug ];

		if ( $brand ) {
			$val['brand'] = $brand;
		}

		if ( $size_slug ) {
			$val['size_slug'] = $size_slug;
		}

		$this->manager_settings_data['marks']['mcat'] = $val;
		self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
	}

	public function unmarkCategory() {
		if ( ! empty( $this->manager_settings_data['marks']['mcat'] ) ) {
			unset( $this->manager_settings_data['marks']['mcat'] );
			self::saveUserManagerSettingsData( $this->wp_user_id, $this->manager_settings_data );
		}
	}

	public function getMarkedCategory() {
		if ( empty( $this->manager_settings_data['marks']['mcat'] ) ) {
			return false;
		}

		return $this->manager_settings_data['marks']['mcat'];
	}

	public function categoryIsMarked( $cat_slug, $brand ) {
		if ( empty( $this->manager_settings_data['marks']['mcat'] ) ) {
			return false;
		}

		return ( $this->manager_settings_data['marks']['mcat']['cat_slug'] === $cat_slug &&
		         ( ! $brand ||
		           isset( $this->manager_settings_data['marks']['mcat']['brand'] ) &&
		           $this->manager_settings_data['marks']['mcat']['brand'] === $brand
		         ) );
	}

	public static function getManagers2NotifyOnOrders( $status, $ignore_manager_id ) {
		$managers_ids = [];

		foreach ( ChB_Settings()->getParam( 'managers2notify_on_orders' ) as $manager_id ) {
			if ( empty( $ignore_manager_id ) || $ignore_manager_id != $manager_id ) {
				$managers_ids[] = $manager_id;
			}
		}

		if ( $status === ChB_Order::ORDER_STATUS_COMPLETED ) {
			foreach ( ChB_Settings()->getParam( 'managers2notify_on_completed_orders' ) as $manager_id ) {
				if ( ( empty( $ignore_manager_id ) || $ignore_manager_id != $manager_id ) && ! in_array( $manager_id, $managers_ids ) ) {
					$managers_ids[] = $manager_id;
				}
			}
		}

		return $managers_ids;
	}
}