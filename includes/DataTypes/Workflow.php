<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Workflow as WorkflowModel;
use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workflow data type class.
 */
class Workflow extends AbstractDataType {

	/**
	 * @param mixed $item
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof WorkflowModel;
	}


	/**
	 * @param WorkflowModel $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		$workflow = Factory::get( $compressed_item );

		if ( ! $workflow || $workflow->get_status() === 'trash' ) {
			return false;
		}

		return $workflow;
	}
}
