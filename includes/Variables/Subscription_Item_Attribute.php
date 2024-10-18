<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Variable_Order_Item_Attribute;

defined( 'ABSPATH' ) || exit;

/**
 * Access a subscription product line item's attribute by specifying the attributes slug.
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class Subscription_Item_Attribute extends Variable_Order_Item_Attribute {

	/**
	 * Method to set description and other admin props
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->description = __( "Can be used to display an attribute's term name for a variable product on a subscription.", 'automatewoo' );
	}
}
