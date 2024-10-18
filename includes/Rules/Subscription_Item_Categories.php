<?php

namespace AutomateWoo\Rules;

use AutomateWoo\Rule_Order_Item_Categories;

defined( 'ABSPATH' ) || exit;

/**
 * Class for the 'Subscription - Item Categories' rule.
 */
class Subscription_Item_Categories extends Rule_Order_Item_Categories {

	/**
	 * Data type used by the rule.
	 *
	 * @var string
	 */
	public $data_item = 'subscription';

	/**
	 * Init the rule.
	 */
	public function init() {
		parent::init();
		$this->title = __( 'Subscription - Item Categories', 'automatewoo' );
	}
}
