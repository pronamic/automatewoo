<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Trigger_Review_Posted
 */
class Trigger_Review_Posted extends Trigger {

	public $supplied_data_items = [ 'review', 'customer', 'product' ];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var string|array
	 */
	protected $required_async_events = 'review_approved';


	function load_admin_details() {
		$this->title = __( 'New Review Posted', 'automatewoo' );
		$this->group = __( 'Reviews', 'automatewoo' );
		$this->description = __( 'This trigger does not fire until the review has been approved. If a customer leaves multiple reviews on the same product the workflow will only run once.', 'automatewoo' );
	}


	function register_hooks() {
		add_action( 'automatewoo/review/posted_async', [ $this, 'catch_hooks' ] );
	}


	/**
	 * @param int $review_id
	 */
	function catch_hooks( int $review_id ) {
		$review = new Review( Clean::id( $review_id ) );

		if ( ! $review->exists ) {
			return;
		}

		$this->maybe_run([
			'customer' => Customer_Factory::get_by_review( $review ),
			'product' => wc_get_product( $review->get_product_id() ),
			'review' => $review
		]);
	}


	/**
	 * @param $workflow Workflow
	 * @return bool
	 */
	function validate_workflow( $workflow ) {
		$review = $workflow->data_layer()->get_review();

		// Bail if review is not approved (e.g. spam or trash).
		if ( ! $review || ! $review->is_approved() ) {
			return false;
		}

		// Run each workflow once for each review, the comment could be approved more than once
		if ( $workflow->has_run_for_data_item( 'review' ) ) {
			return false;
		}

		// Run each workflow once per product per customer, the customer could add multiple reviews on the product
		// NOTE we must use separate has_run_for_data_item() calls for this logic.
		if ( $workflow->has_run_for_data_item( [ 'product', 'customer' ] ) ) {
			return false;
		}

		return true;
	}

}
