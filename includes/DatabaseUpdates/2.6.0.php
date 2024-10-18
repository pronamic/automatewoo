<?php
// phpcs:ignoreFile
/**
 * Update to 2.6 - Workflow Rules
 */

$workflows_query = new AutomateWoo\Workflow_Query();
$workflows_query->args['post_status'] = 'any';

$workflows = $workflows_query->get_results();



function _aw_convert_tag_slugs_to_ids( $tag_slugs ) {
	$ids = [];
	foreach ( $tag_slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'user_tag' );
		$ids[] = $term->term_id;
	}

	return $ids;
}



foreach ( $workflows as $workflow ) {
	/** @var $workflow AutomateWoo\Workflow */

	if ( ! $workflow->get_trigger() ) continue; // bail if no trigger set


	$trigger_options = $workflow->get_trigger_options();
	$rules = $workflow->get_rule_data();
	$rule_group = [];


	if ( $types = $workflow->get_trigger_option('user_type') ) {
		$rule_group[] = [
			'name' => 'user_role',
			'compare' => 'is',
			'value' => $types
		];

		unset( $trigger_options['user_type'] );
	}


	if ( $tags = $workflow->get_trigger_option('user_tags') )
	{
		// convert tags slugs to ids
		$tag_ids =

		$rule_group[] = [
			'name' => 'user_tags',
			'compare' => 'matches_all',
			'value' => _aw_convert_tag_slugs_to_ids( $tags )
		];

		unset( $trigger_options['user_tags'] );
	}


	if ( $missing_tags = $workflow->get_trigger_option('user_missing_tags') )
	{
		$rule_group[] = [
			'name' => 'user_tags',
			'compare' => 'matches_none',
			'value' => _aw_convert_tag_slugs_to_ids( $missing_tags )
		];

		unset( $trigger_options['user_missing_tags'] );
	}


	if ( $order_total_gt = $workflow->get_trigger_option('order_total_greater_than') )
	{
		$rule_group[] = [
			'name' => 'order_total',
			'compare' => 'greater_than',
			'value' => $order_total_gt
		];

		unset( $trigger_options['order_total_greater_than'] );
	}


	if ( $order_total_lt = $workflow->get_trigger_option('order_total_less_than') )
	{
		$rule_group[] = [
			'name' => 'order_total',
			'compare' => 'less_than',
			'value' => $order_total_lt
		];

		unset( $trigger_options['order_total_less_than'] );
	}


	if ( $payment_gateway = $workflow->get_trigger_option( 'payment_gateway' ) )
	{
		$rule_group[] = [
			'name' => 'order_payment_gateway',
			'compare' => 'is',
			'value' => $payment_gateway
		];

		unset( $trigger_options['payment_gateway'] );
	}


	if ( $shipping_countries = $workflow->get_trigger_option( 'shipping_countries' ) )
	{
		$rule_group[] = [
			'name' => 'order_shipping_country',
			'compare' => 'is',
			'value' => $shipping_countries
		];

		unset( $trigger_options['shipping_countries'] );
	}


	if ( $billing_countries = $workflow->get_trigger_option( 'billing_countries' ) )
	{
		$rule_group[] = [
			'name' => 'order_billing_country',
			'compare' => 'is',
			'value' => $billing_countries
		];

		unset( $trigger_options['billing_countries'] );
	}


	if ( $shipping_methods = $workflow->get_trigger_option( 'shipping_methods' ) )
	{
		$rule_group[] = [
			'name' => 'order_shipping_method',
			'compare' => 'matches_any',
			'value' => $shipping_methods
		];

		unset( $trigger_options['shipping_methods'] );
	}



	if ( $is_users_first_order = $workflow->get_trigger_option('is_users_first_order') )
	{
		$rule_group[] = [
			'name' => 'order_is_customers_first',
			'compare' => '',
			'value' => 'yes'
		];

		unset( $trigger_options['is_users_first_order'] );
	}


	if ( $user_limit = $workflow->get_trigger_option('once_only' ) )
	{
		$rule_group[] = [
			'name' => 'user_run_count',
			'compare' => 'less_than',
			'value' => $user_limit
		];

		unset( $trigger_options['once_only'] );
	}

	if ( $guest_limit = $workflow->get_trigger_option('limit_per_guest' ) )
	{
		$rule_group[] = [
			'name' => 'guest_run_count',
			'compare' => 'less_than',
			'value' => $guest_limit
		];

		unset( $trigger_options['limit_per_guest'] );
	}


	if ( $is_pos = $workflow->get_trigger_option('is_pos' ) )
	{
		$rule_group[] = [
			'name' => 'order_is_pos',
			'compare' => '',
			'value' => $is_pos
		];

		unset( $trigger_options['is_pos'] );
	}



	/**
	 * Cart only migration since option name could have been used by a custom trigger
	 */
	if ( in_array( $workflow->get_trigger()->get_name(), [ 'guest_abandoned_cart', 'abandoned_cart' ] )  ) {
		if ( $cart_total_gt = $workflow->get_trigger_option('total_greater_than') ) {
			$rule_group[] = [
				'name' => 'cart_total',
				'compare' => 'greater_than',
				'value' => $cart_total_gt
			];

			unset( $trigger_options['total_greater_than'] );
		}


		if ( $cart_total_lt = $workflow->get_trigger_option('total_less_than') ) {
			$rule_group[] = [
				'name' => 'cart_total',
				'compare' => 'less_than',
				'value' => $cart_total_lt
			];

			unset( $trigger_options['total_less_than'] );
		}
	}









	if ( ! empty($rule_group) )
	{
		$rules[] = $rule_group;

		update_post_meta( $workflow->get_id(), 'trigger_options', $trigger_options );
		update_post_meta( $workflow->get_id(), 'rule_options', $rules );
	}

}
