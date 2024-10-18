<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Workflow as WorkflowModel;
use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Workflow data type class.
 */
class Workflow extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof WorkflowModel;
	}


	/**
	 * @param WorkflowModel $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		$workflow = Factory::get( $compressed_item );

		if ( ! $workflow || $workflow->get_status() === 'trash' ) {
			return false;
		}

		return $workflow;
	}

}
