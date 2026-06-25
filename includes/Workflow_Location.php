<?php

namespace AutomateWoo;

/**
 * @class Workflow_Location
 * @since 2.8.2
 */
class Workflow_Location {

	/** @var Workflow */
	public $workflow;

	/** @var string */
	public $country;

	/** @var string */
	public $state;

	/** @var string */
	public $postcode;

	/** @var string */
	public $city;

	/** @var string */
	public $based_on = 'billing';

	/** @var string */
	public $target_object_type;


	/**
	 * @param Workflow $workflow
	 * @param string   $based_on
	 */
	public function __construct( $workflow, $based_on = 'billing' ) {

		$this->workflow = $workflow;

		if ( in_array( $based_on, [ 'billing', 'shipping' ], true ) ) {
			$this->based_on = $based_on;
		}
	}


	/**
	 * @return string
	 */
	public function get_target_object_type() {
		if ( ! isset( $this->target_object_type ) ) {
			$this->target_object_type = $this->load_target_object_type();
		}

		return $this->target_object_type;
	}


	/**
	 * @return string
	 */
	protected function load_target_object_type() {

		// only use the order/subscription location if the order belongs to the customer
		if ( $this->workflow->data_layer()->order_belongs_to_customer ) {
			$order = $this->workflow->data_layer()->get_order();
			if ( $order ) {
				return 'order';
			}

			$subscription = $this->workflow->data_layer()->get_subscription();
			if ( $subscription ) {
				return 'subscription';
			}
		}

		$user = $this->workflow->data_layer()->get_user();
		if ( $user ) {
			return 'user';
		}

		$guest = $this->workflow->data_layer()->get_guest();
		if ( $guest ) {
			return 'guest';
		}

		return false;
	}


	/**
	 * @return \WC_Order|\WC_Subscription|\WP_User|Order_Guest|Guest|false
	 */
	public function get_target_object() {
		if ( $this->get_target_object_type() ) {
			return $this->workflow->data_layer()->get_item( $this->get_target_object_type() );
		}
		return false;
	}


	/**
	 * @return string
	 */
	public function get_country() {

		if ( ! isset( $this->country ) ) {

			$object = $this->get_target_object();

			switch ( $this->get_target_object_type() ) {

				case 'order':
					$this->country = $this->based_on === 'billing' ? $object->get_billing_country() : $object->get_shipping_country();
					break;

				case 'subscription':
					$this->country = $this->based_on === 'billing' ? $object->get_billing_country() : $object->get_shipping_country();
					break;

				case 'user':
					$this->country = $this->based_on === 'billing' ? get_user_meta( $object->ID, 'billing_country', true ) : get_user_meta( $object->ID, 'shipping_country', true );
					break;

				case 'guest':
					$this->country = $object->get_country();
					break;
			}

			if ( ! $this->country ) {
				$this->country = WC()->countries->get_base_country();
			}
		}

		return $this->country;
	}


	/**
	 * @return string
	 */
	public function get_state() {

		if ( ! isset( $this->state ) ) {

			$object = $this->get_target_object();

			switch ( $this->get_target_object_type() ) {

				case 'order':
					$this->state = $this->based_on === 'billing' ? $object->get_billing_state() : $object->get_shipping_state();
					break;

				case 'subscription':
					$this->state = $this->based_on === 'billing' ? $object->get_billing_state() : $object->get_shipping_state();
					break;

				case 'user':
					$this->state = $this->based_on === 'billing' ? get_user_meta( $object->ID, 'billing_state', true ) : get_user_meta( $object->ID, 'shipping_state', true );
					break;

				case 'guest':
					$this->state = $object->get_state();
					break;
			}

			if ( ! $this->state ) {
				$this->state = WC()->countries->get_base_state();
			}
		}

		return $this->state;
	}


	/**
	 * @return string
	 */
	public function get_postcode() {

		if ( ! isset( $this->postcode ) ) {

			$object = $this->get_target_object();

			switch ( $this->get_target_object_type() ) {

				case 'order':
					$this->postcode = $this->based_on === 'billing' ? $object->get_billing_postcode() : $object->get_shipping_postcode();
					break;

				case 'subscription':
					$this->postcode = $this->based_on === 'billing' ? $object->get_billing_postcode() : $object->get_shipping_postcode();
					break;

				case 'user':
					$this->postcode = $this->based_on === 'billing' ? get_user_meta( $object->ID, 'billing_postcode', true ) : get_user_meta( $object->ID, 'shipping_postcode', true );
					break;

				case 'guest':
					$this->postcode = $object->get_postcode();
					break;
			}

			if ( ! $this->postcode ) {
				$this->postcode = WC()->countries->get_base_postcode();
			}
		}

		return $this->postcode;
	}


	/**
	 * @return string
	 */
	public function get_city() {

		if ( ! isset( $this->city ) ) {

			$object = $this->get_target_object();

			switch ( $this->get_target_object_type() ) {

				case 'order':
					$this->city = $this->based_on === 'billing' ? $object->get_billing_city() : $object->get_shipping_city();
					break;

				case 'subscription':
					$this->city = $this->based_on === 'billing' ? $object->get_billing_city() : $object->get_shipping_city();
					break;

				case 'user':
					$this->city = $this->based_on === 'billing' ? get_user_meta( $object->ID, 'billing_city', true ) : get_user_meta( $object->ID, 'shipping_city', true );
					break;

				case 'guest':
					$this->city = $object->get_city();
					break;
			}

			if ( ! $this->city ) {
				$this->city = WC()->countries->get_base_city();
			}
		}

		return $this->city;
	}


	/**
	 * @return array
	 */
	public function get_location_array() {
		return [
			$this->get_country(),
			$this->get_state(),
			$this->get_postcode(),
			$this->get_city(),
		];
	}
}
