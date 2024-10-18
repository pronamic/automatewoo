<?php

namespace AutomateWoo;

use AutomateWoo\Fields\Select;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Variable_Order_Itemscount
 */
class Variable_Order_Itemscount extends Variable {

	public const INCLUDE_PARENT_BUNDLE_PRODUCT_OPTION                  = 'include_parent_bundle_product';
	public const EXCLUDE_PARENT_BUNDLE_PRODUCT_OPTION                  = 'exclude_parent_bundle_product';
	public const INCLUDE_PARENT_BUNDLE_PRODUCT_EXCLUDE_CHILDREN_OPTION = 'include_parent_bundle_product_exclude_children';


	/**
	 * Load admin details
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays the number of items in the order.', 'automatewoo' );

		if ( class_exists( 'WC_Product_Bundle' ) ) {
			$this->add_parameter_field( $this->get_bundle_product_parameter_field() );
		}
	}

	/**
	 * Get date format parameter field.
	 *
	 * @since 6.0.23
	 *
	 * @return Select
	 */
	protected function get_bundle_product_parameter_field() {
		$options = [
			self::INCLUDE_PARENT_BUNDLE_PRODUCT_OPTION => __( 'Include Parent Bundle Product and the children products', 'automatewoo' ),
			self::INCLUDE_PARENT_BUNDLE_PRODUCT_EXCLUDE_CHILDREN_OPTION => __( 'Include Parent Bundle Product and exclude the children products', 'automatewoo' ),
			self::EXCLUDE_PARENT_BUNDLE_PRODUCT_OPTION => __( 'Exclude Parent Bundle Product but include the children products', 'automatewoo' ),
		];

		return ( new Select( false ) )
			->set_name( 'bundle_product' )
			->set_description( __( 'How to handle counting for bundle products.', 'automatewoo' ) )
			->set_required( false )
			->set_options( $options );
	}


	/**
	 * @param \WC_Order $order The order object
	 * @param array     $parameters The variable parameters
	 * @return int The number of items in the order
	 */
	public function get_value( $order, $parameters ) {

		if ( isset( $parameters['bundle_product'] ) ) {
			$count = 0;
			foreach ( $order->get_items() as $item ) {
				if ( $item->get_meta( '_bundle_group_mode' ) === 'parent' && $parameters['bundle_product'] === self::EXCLUDE_PARENT_BUNDLE_PRODUCT_OPTION ) {
					continue;
				}

				if ( $item->get_meta( '_bundled_by' ) && $parameters['bundle_product'] === self::INCLUDE_PARENT_BUNDLE_PRODUCT_EXCLUDE_CHILDREN_OPTION ) {
					continue;
				}

				$count += $item->get_quantity();
			}

			return $count;

		} else {
			return $order->get_item_count();
		}
	}
}
