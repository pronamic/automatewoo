<?php

namespace AutomateWoo\Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class User_Role
 */
class User_Role extends Select {

	/** @var string */
	protected $name = 'user_type'; // legacy name

	/** @var string[] */
	public const PROTECTED_ROLES = [ 'administrator', 'shop_manager' ];

	/**
	 * @param bool $allow_any If it is allowed to select "any" as value for this field.
	 * @param bool $allow_all_roles If roles in self::PROTECTED_ROLES should be shown as well.
	 */
	public function __construct( bool $allow_any = true, bool $allow_all_roles = true ) {
		parent::__construct();

		$this->set_title( __( 'User role', 'automatewoo' ) );

		if ( $allow_any ) {
			$this->set_placeholder( '[Any]' );
		}

		global $wp_roles;

		foreach ( $wp_roles->roles as $key => $role ) {
			if ( $allow_all_roles || ! in_array( $key, self::PROTECTED_ROLES, true ) ) {
				$this->options[ $key ] = $role['name'];
			}
		}
	}
}
