<?php

namespace AutomateWoo\DataTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Teacher data type class.
 *
 * @since 5.6.10
 */
class SenseiTeacher extends AbstractDataType {

	/**
	 * Validate item.
	 *
	 * @param \WP_User $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof \WP_User;
	}

	/**
	 * Compress a data item to ID
	 *
	 * @param \WP_User $item The data item to compress.
	 *
	 * @return int
	 */
	public function compress( $item ) {
		return $item->ID;
	}

	/**
	 * Get the full item from its stored format.
	 *
	 * @param int   $compressed_item
	 * @param array $compressed_data_layer
	 *
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		if ( ! $compressed_item ) {
			return false;
		}

		return get_user_by( 'id', absint( $compressed_item ) );
	}

	/**
	 * Get singular name for data type.
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Teacher', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Teachers', 'automatewoo' );
	}
}
