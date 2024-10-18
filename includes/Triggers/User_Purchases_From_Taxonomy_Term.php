<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_User_Purchases_From_Taxonomy_Term
 */
class Trigger_User_Purchases_From_Taxonomy_Term extends Trigger_Abstract_Order_Status_Base {


	function load_admin_details() {
		parent::load_admin_details();
		$this->title = __('Order Includes Product from Taxonomy Term', 'automatewoo');
	}


	function load_fields() {

		$taxonomy = new Fields\Taxonomy();
		$taxonomy->set_required();

		$term = new Fields\Taxonomy_Term();
		$term->set_required();

		$order_status = new Fields\Order_Status( false );
		$order_status->set_required();
		$order_status->set_default('wc-completed');

		$this->add_field( $taxonomy );
		$this->add_field( $term );
		$this->add_field( $order_status );
	}


	/**
	 * @param $workflow Workflow
	 * @return bool
	 */
	function validate_workflow( $workflow ) {
		$order = $workflow->data_layer()->get_order();

		if ( ! $order ) {
			return false;
		}

		$status_option    = $workflow->get_trigger_option( 'order_status' );
		$stored_term_data = $workflow->get_trigger_option( 'term' );

		$new_status = Temporary_Data::get( 'order_new_status', $order->get_id() );

		if ( ! $this->validate_status_field( $status_option, $new_status ) ) {
			return false;
		}

		if ( ! strstr( $stored_term_data, '|' ) ) {
			return false;
		}

		list( $term_id, $taxonomy ) = explode( '|', $stored_term_data );

		if ( ! $term_id || ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		foreach ( $order->get_items() as $order_item ) {
			/** @var \WC_Order_Item_Product $order_item */
			$product_terms = wp_get_object_terms( $order_item->get_product_id(), $taxonomy );

			if ( $product_terms ) {
				foreach( $product_terms as $product_term ) {
					if ( $product_term->term_id == $term_id ) {
						return true;
					}
				}
			}
		}

		return false;
	}

}
