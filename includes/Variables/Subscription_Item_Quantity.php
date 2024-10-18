<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable_Order_Item_Quantity;

defined( 'ABSPATH' ) || exit;

/**
 * Access the quantity of a subscription product line item.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Item_Quantity extends Variable_Order_Item_Quantity {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		$this->description = __( 'Can be used to display the quantity of a product line item on a subscription.', 'automatewoo' );
	}
}
