<?php

namespace AutomateWoo\DataTypes;

/**
 * Class AbstractDataType.
 *
 * @since   2.4.6
 * @package AutomateWoo
 */
abstract class AbstractDataType {

	/**
	 * The ID (or slug) of the data type.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Get the data type ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the data type ID.
	 *
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Check that a data item is valid for it's type.
	 *
	 * @param mixed $item
	 *
	 * @return bool
	 */
	abstract public function validate( $item );

	/**
	 * Compress a data item to a storable format (typically an ID).
	 *
	 * $item should be validated before being passed to this method.
	 *
	 * @param mixed $item
	 *
	 * @return int|string|null Returns int|string if successful or null on failure.
	 */
	abstract public function compress( $item );

	/**
	 * Get the full item from its stored format.
	 *
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 *
	 * @return mixed
	 */
	abstract public function decompress( $compressed_item, $compressed_data_layer );

	/**
	 * Get singular name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Item', 'automatewoo' );
	}

	/**
	 * Get singular name for data type.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Items', 'automatewoo' );
	}
}
