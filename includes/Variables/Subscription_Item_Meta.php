<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable_Order_Item_Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Access a subscription product line item's meta value by specifying a meta key.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Item_Meta extends Variable_Order_Item_Meta {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( "Can be used to display the value of a subscription item's meta field.", 'automatewoo' );
		$this->add_parameter_text_field( 'key', __( 'The key of the subscription item meta field.', 'automatewoo' ), true );
	}
}
