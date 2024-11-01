<?php

namespace ChatBot;

class ChB_Roles {
	public const ROLE_PRODUCT_DISCOUNTS = 'products_discounts';
	public const ROLE_SHOP_MANAGER = 'shop_manager';
	public const ROLE_PROMO_MANAGER = 'promo_manager';

	/**
	 * @param $role_name
	 * @param $user ChB_User|string
	 *
	 * @return bool
	 */
	public static function userHasRole( $role_name, $user ) {
		if ( $user instanceof ChB_User ) {
			$user = $user->fb_user_id;
		}
		if ( empty( ( ChB_Settings()->getParam( 'roles' )[ $role_name ] ) ) ) {
			return false;
		}

		return in_array( $user, ChB_Settings()->getParam( 'roles' )[ $role_name ] );
	}

	public static function setUserRole( $role_name, $fb_user_id ) {
		if ( ! self::userHasRole( $role_name, $fb_user_id ) ) {
			$roles = ChB_Settings()->getParam( 'roles' );
			if ( ! is_array( $roles ) ) {
				$roles = [];
			}
			$roles[ $role_name ][] = $fb_user_id;
			ChB_Settings()->updateRoles( $roles );
		}
	}
}