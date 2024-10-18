<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guest
 *
 * @property string $id
 * @property string $tracking_key
 * @property string $created
 * @property string $last_active
 */
class Guest extends Abstract_Model_With_Meta_Table {

	/** @var string */
	public $table_id = 'guests';

	/** @var string  */
	public $object_type = 'guest';

	/** @var string */
	private $formatted_billing_address;

	/**
	 * Returns the ID of the model's meta table.
	 *
	 * @return string
	 */
	public function get_meta_table_id() {
		return 'guest-meta';
	}

	/**
	 * @param $id
	 */
	function __construct( $id = false ) {
		if ( $id ) $this->get_by( 'id', $id );
	}


	/**
	 * @param string $email
	 */
	function set_email( $email ) {
		$this->set_prop( 'email', Clean::string( $email ) ); // clean as string for anonymized emails
	}


	/**
	 * @return string
	 */
	function get_email() {
		return Clean::string( $this->get_prop( 'email' ) ); // clean as string for anonymized emails
	}


	/**
	 * @deprecated Use customer key
	 *
	 * @param string $key
	 */
	function set_key( $key ) {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		$this->set_prop( 'tracking_key', Clean::string( $key ) );
	}


	/**
	 * @deprecated Use customer key
	 *
	 * @return string
	 */
	function get_key() {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		return Clean::string( $this->get_prop( 'tracking_key' ) );
	}


	/**
	 * @param DateTime $date
	 */
	function set_date_created( $date ) {
		$this->set_date_column( 'created', $date );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_created() {
		return $this->get_date_column( 'created' );
	}


	/**
	 * @param DateTime $date
	 */
	function set_date_last_active( $date ) {
		$this->set_date_column( 'last_active', $date );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_last_active() {
		return $this->get_date_column( 'last_active' );
	}


	/**
	 * @param string $language
	 */
	function set_language( $language ) {
		$this->set_prop( 'language', Clean::string( $language ) );
	}


	/**
	 * @return string
	 */
	function get_language() {
		return Clean::string( $this->get_prop( 'language' ) );
	}


	/**
	 * @since 4.0
	 * @param int $order_id
	 */
	function set_most_recent_order_id( $order_id ) {
		$this->set_prop( 'most_recent_order', Clean::id( $order_id ) );
	}


	/**
	 * Most recent order that isn't failed.
	 *
	 * @since 4.0
	 * @return int
	 */
	function get_most_recent_order_id() {
		return Clean::id( $this->get_prop( 'most_recent_order' ) );
	}


	/**
	 * @since 4.2
	 * @param string $version
	 */
	function set_version( $version ) {
		$this->set_prop( 'version', Clean::string( aw_version_str_to_int( $version ) ) );
	}


	/**
	 * @return string
	 */
	function get_version() {
		return aw_version_int_to_str( Clean::string( $this->get_prop( 'version' ) ) );
	}


	/**
	 * Updates the 'most_recent_order' cache.
	 * @return int
	 */
	function recache_most_recent_order_id() {
		$orders = wc_get_orders([
			'type' => 'shop_order',
			'customer' => $this->get_email(),
			'status' => aw_get_counted_order_statuses( false ),
			'limit' => 1,
			'orderby' => 'date',
			'return' => 'ids',
		]);

		if ( $orders ) {
			$this->set_most_recent_order_id( current( $orders ) );
		}
		else {
			$this->set_most_recent_order_id( 0 );
		}

		$this->save();

		return $this->get_most_recent_order_id();
	}


	/**
	 * @return string
	 */
	function get_full_name() {
		return trim( sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo' ), $this->get_first_name(), $this->get_last_name() ) );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_first_name( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_first_name', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_last_name( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_last_name', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_phone( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_phone', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_country( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_country', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_company( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_company', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_address_1( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_address_1', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_address_2( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_address_2', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_city( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_city', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_state( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_state', $presubmit_only );
	}


	/**
	 * @param bool $presubmit_only
	 * @return string
	 */
	function get_postcode( $presubmit_only = false ) {
		return $this->get_checkout_field( 'billing_postcode', $presubmit_only );
	}


	/**
	 * Update guest ip and active date
	 */
	function do_check_in() {
		$this->set_date_last_active( new DateTime() );
		$this->save();
	}


	/**
	 * Retrieve a valid checkout field if one is stored in meta or get from an order belonging to the guest
	 * @param $field
	 * @param bool $presubmit_only set true to bypass most recent order
	 * @return mixed
	 */
	function get_checkout_field( $field, $presubmit_only = false ) {
		if ( ! PreSubmit::is_checkout_capture_field( $field ) ) {
			return false;
		}

		if ( ! $presubmit_only ) {
			// first try to get from most recent order
			if ( $order = $this->get_most_recent_order() ) {
				return $this->get_checkout_field_from_order( $field, $order );
			}
		}

		// only look in meta if presubmit capture is enabled
		if ( Options::presubmit_capture_enabled() ) {
			return $this->get_meta( $field );
		}

		return false;
	}


	/**
	 * If $order is not set, most recent order will be used.
	 *
	 * @param string $field
	 * @param bool|\WC_Order $order
	 * @return mixed
	 */
	function get_checkout_field_from_order( $field, $order = false ) {
		if ( ! $order ) {
			if ( ! $order = $this->get_most_recent_order() ) {
				return false;
			}
		}

		$value = '';

		switch ( $field ) {
			case 'billing_first_name':
				$value = $order->get_billing_first_name();
				break;
			case 'billing_last_name':
				$value = $order->get_billing_last_name();
				break;
			case 'billing_company':
				$value = $order->get_billing_company();
				break;
			case 'billing_country':
				$value = $order->get_billing_country();
				break;
			case 'billing_phone':
				$value = $order->get_billing_phone();
				break;
			case 'billing_address_1':
				$value = $order->get_billing_address_1();
				break;
			case 'billing_address_2':
				$value = $order->get_billing_address_2();
				break;
			case 'billing_city':
				$value = $order->get_billing_city();
				break;
			case 'billing_state':
				$value = $order->get_billing_state();
				break;
			case 'billing_postcode':
				$value = $order->get_billing_postcode();
				break;
		}

		return $value;
	}


	/**
	 * @return \WC_Order|false
	 */
	function get_most_recent_order() {
		if ( ! $order_id = $this->get_most_recent_order_id() ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			// attempt to update if no order
			if ( $order_id = $this->recache_most_recent_order_id() ) {
				$order = wc_get_order( $order_id );
			}
		}

		return $order;
	}


	/**
	 * @return Cart|false
	 */
	function get_cart() {
		return Cart_Factory::get_by_guest_id( $this->get_id() );
	}


	/**
	 * The locked guest status is used by the presubmit capture module.
	 *
	 * If a guest is not locked their email address may be changed if the capture email address is modified.
	 * But allowing emails to change later can cause for confusing issues, hence this logic.
	 *
	 * A guest becomes locked 10 minutes after they are created or once they place an order.
	 *
	 * @return bool
	 */
	function is_locked() {
		if ( ! $created = $this->get_date_created() ) {
			return true; // guest has no created date so default to locked
		}

		$locking_timestamp = time() - (int) apply_filters( 'automatewoo/guest/locking_timeout', 600, $this ); // defaults to 10 minutes

		if ( $created->getTimestamp() < $locking_timestamp ) {
			return true;
		}

		if ( $this->get_most_recent_order_id() ) {
			return true;
		}

		return false;
	}


	/**
	 * @return string
	 */
	public function get_formatted_billing_address() {
		if ( ! $this->formatted_billing_address ) {

			$address = [
				'first_name' => $this->get_first_name(),
				'last_name' => $this->get_last_name(),
				'company' => $this->get_company(),
				'address_1' => $this->get_address_1(),
				'address_2' => $this->get_address_2(),
				'city' => $this->get_city(),
				'state' => $this->get_state(),
				'postcode' => $this->get_postcode(),
				'country' => $this->get_country()
			];

			$this->formatted_billing_address = WC()->countries->get_formatted_address( $address );
		}

		return $this->formatted_billing_address;
	}


	/**
	 * Delete any presubmit billing data excluding the actual guest email
	 * @since 4.0
	 */
	function delete_presubmit_data() {
		$fields = [
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_phone',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode'
		];

		foreach( $fields as $field ) {
			$this->delete_meta( $field );
		}
	}


	function delete_cart() {
		if ( $this->exists && $cart = $this->get_cart() ) {
			$cart->delete();
		}
	}


	function delete() {
		$this->delete_cart();
		parent::delete();
	}

}
