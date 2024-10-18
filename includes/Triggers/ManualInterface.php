<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Data_Layer;

/**
 * Manual trigger interface.
 *
 * @since   5.0.0
 * @package AutomateWoo\Triggers
 */
interface ManualInterface {

	/**
	 * Get primary data type.
	 *
	 * The primary data type is used for quick filtering.
	 *
	 * @return string
	 */
	public function get_primary_data_type();

	/**
	 * Get data layer from primary data item.
	 *
	 * Used to run the workflow manually.
	 *
	 * @param int $item_id
	 *
	 * @return Data_Layer|false
	 */
	public function get_data_layer( $item_id );
}
