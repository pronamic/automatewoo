<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Privacy
 * @since 4.0
 */
class Privacy_Abstract extends \WC_Abstract_Privacy {


	/**
	 * @param string $id
	 * @param string $name
	 * @param callable $callback
	 */
	public function add_exporter( $id, $name, $callback ) {
		parent::add_exporter( $id, $name, $callback );
	}


	/**
	 * @param string $id
	 * @param string $name
	 * @param callable $callback
	 */
	public function add_eraser( $id, $name, $callback ) {
		parent::add_eraser( $id, $name, $callback );
	}


	/**
	 * Parse export data in array format where keys are the name of the data.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function parse_export_data_array( $data ) {
		$return = [];
		foreach( $data as $name => $value ) {
			$value = trim( $value );
			if ( $name && $value ) {
				$return[] = [
					'name' => $name,
					'value' => $value,
				];
			}
		}
		return $return;
	}


}
