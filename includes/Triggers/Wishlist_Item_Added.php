<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Trigger_Wishlist_Item_Added
 * @since 2.3
 */
class Trigger_Wishlist_Item_Added extends Trigger {

	/**
	 * Declare limit field support.
	 *
	 * @var boolean
	 */
	public $supplied_data_items = [ 'customer', 'wishlist', 'product' ];


	/**
	 * Method to set title, group, description and other admin props
	 */
	public function load_admin_details() {
		/* translators: Wishlist item title. */
		$this->title = sprintf( __( 'Customer Adds Product (%s)', 'automatewoo' ), Wishlists::get_integration_title() );
		$this->group = __( 'Wishlists', 'automatewoo' );
	}


	/**
	 * Registers any fields used on for a trigger
	 */
	public function load_fields() {
		$this->add_field_user_pause_period();
	}


	/**
	 * Register trigger hooks.
	 */
	public function register_hooks() {
		add_action( 'yith_wcwl_added_to_wishlist', [ $this, 'catch_hooks' ], 20, 3 );
	}


	/**
	 * Route hooks through here.
	 *
	 * @param int $product_id
	 * @param int $wishlist_id
	 * @param int $user_id
	 */
	public function catch_hooks( $product_id, $wishlist_id, $user_id ) {

		if ( ! $this->has_workflows() ) {
			return;
		}

		$integration = Wishlists::get_integration();

		if ( $integration === 'yith' ) {

			$wishlist = Wishlists::get_wishlist( $wishlist_id );

			$this->maybe_run(
				[
					'customer' => Customer_Factory::get_by_user_id( $user_id ),
					'wishlist' => $wishlist,
					'product'  => wc_get_product( $product_id ),
				]
			);
		} else {
			return;
		}
	}


	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		if ( ! $this->validate_field_user_pause_period( $workflow ) ) {
			return false;
		}

		return true;
	}


	/**
	 * @param Workflow $workflow
	 * @return bool
	 */
	public function validate_before_queued_event( $workflow ) {
		$wishlist = $workflow->data_layer()->get_wishlist();
		$product  = $workflow->data_layer()->get_product();

		if ( ! $wishlist || ! $product ) {
			return false;
		}

		if ( Wishlists::get_integration() !== 'yith' ) {
			return false;
		}

		// check product is still in wishlist
		if ( ! in_array( $product->get_id(), $wishlist->get_items(), true ) ) {
			return false;
		}

		return true;
	}
}
