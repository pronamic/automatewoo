<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Variable_Abstract_Generate_Coupon class.
 */
abstract class Variable_Abstract_Generate_Coupon extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {

		$this->description = sprintf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag. */
			__( 'Generates a unique coupon based on a template coupon. <%1$s>View documentation<%2$s>.', 'automatewoo' ),
			'a href="' . Admin::get_docs_link( 'variables/personalized-coupons', 'variable-description' ) . '" target="_blank"',
			'/a'
		);

		$coupon = new Fields\Coupon();
		$coupon->set_name( 'template' );
		$coupon->set_required( true );
		$coupon->set_description( __( 'Name of the coupon that will be cloned.', 'automatewoo' ) );
		$this->add_parameter_field( $coupon );

		$this->add_parameter_text_field( 'expires', __( 'Number of days the coupon will be valid for. If left blank then the expiry set for the template coupon will be used.', 'automatewoo' ) );
		$this->add_parameter_text_field( 'prefix', __( "The prefix for the coupon code, defaults to 'aw-'. To remove the prefix set this field to a single space character.", 'automatewoo' ), false, 'aw-' );
		$this->add_parameter_text_field( 'limit', __( "The number of times the generated coupon can be used. Set to '0' for unlimited.", 'automatewoo' ), false, '1' );
	}

	/**
	 * Generate coupon.
	 *
	 * @param string   $email
	 * @param array    $parameters
	 * @param Workflow $workflow
	 * @return bool|string
	 */
	public function generate_coupon( $email, $parameters, $workflow ) {

		// requires a template
		if ( empty( $parameters['template'] ) ) {
			return false;
		}

		$coupon_generator = new Coupon_Generator();
		$template         = html_entity_decode( $parameters['template'] );
		$coupon_generator->set_template_coupon_code( $template );

		if ( ! $coupon_generator->get_template_coupon_id() ) {
			return false;
		}

		// override with parameter
		if ( isset( $parameters['prefix'] ) ) {
			$coupon_generator->set_prefix( $parameters['prefix'] );
		}

		if ( $workflow->is_test_mode() ) {
			$coupon_generator->set_suffix( '[test]' );
			$coupon_generator->set_description( __( 'AutomateWoo Test Coupon', 'automatewoo' ) );
		}

		$coupon_generator->set_code( $coupon_generator->generate_code() );

		// filter to enable use of coupon email restriction
		if ( apply_filters( 'automatewoo/variables/coupons/use_email_restriction', false, $workflow, $email, $parameters ) ) {
			$coupon_generator->set_email_restriction( $email );
		}

		// don't generate a new coupon every time we preview
		if ( $workflow->is_preview_mode() ) {
			return $coupon_generator->code;
		}

		if ( ! empty( $parameters['expires'] ) ) {
			$coupon_generator->set_expires( $parameters['expires'] );
		}

		if ( isset( $parameters['limit'] ) ) {
			$coupon_generator->set_usage_limit( $parameters['limit'] );
		}

		$coupon = $coupon_generator->generate_coupon();

		if ( ! $coupon ) {
			return false;
		}

		if ( $workflow->is_test_mode() ) {
			$coupon->update_meta_data( '_is_aw_test_coupon', true );
		}

		// store customer ID, workflow ID and log ID in coupon meta
		$coupon->update_meta_data( '_aw_workflow_id', $workflow->get_id() );

		$customer = Customer_Factory::get_by_email( $email );

		if ( $customer ) {
			$coupon->update_meta_data( '_aw_customer_id', $customer->get_id() );
		}

		$log = $workflow->get_current_log();

		// since 3.7
		if ( $log ) {
			$coupon->update_meta_data( '_aw_workflow_log_id', $log->get_id() );
		}

		$coupon = apply_filters( 'automatewoo/variables/generate_coupon', $coupon, $parameters, $workflow );

		$coupon->save();

		return $coupon->get_code();
	}
}
