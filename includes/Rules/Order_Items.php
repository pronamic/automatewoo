<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Logic_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * @class Order_Items
 */
class Order_Items extends Product_Select_Rule_Abstract {

	public $data_item = 'order';


	public function init() {
		parent::init();

		$this->title = __( 'Order - Items', 'automatewoo' );
	}


	/**
	 * @param $order \WC_Order
	 * @param $compare
	 * @param $value
	 * @return bool
	 */
	public function validate( $order, $compare, $value ) {

		if ( ! $expected_product = wc_get_product( absint( $value ) ) ) {
			return false;
		}

		$includes = false;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$includes = Logic_Helper::match_products( $product, $expected_product );

			if ( $includes ) {
				break;
			}
		}

		switch ( $compare ) {
			case 'includes':
				return $includes;
			case 'not_includes':
				return ! $includes;
		}

	}
}
