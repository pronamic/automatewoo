<?php

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Subscription_Items
 */
class Subscription_Items extends Order_Items {

	/** @var string */
	public $data_item = 'subscription';

	/**
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->title = __( 'Subscription - Items', 'automatewoo' );
	}
}
