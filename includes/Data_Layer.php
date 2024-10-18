<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Data_Layer
 */
class Data_Layer {

	/**
	 * When true, data from the order is used instead of the user data.
	 * E.g. order billing first name will be used instead of the first name in user meta.
	 * Also the language of the order overrides the customers language when this is true.
	 * This property also applies to subscriptions.
	 *
	 * In the case of a referral trigger the customer could be the advocate and the order will belong to the friend.
	 *
	 * @since 4.2
	 * @var bool
	 */
	public $order_belongs_to_customer = true;

	private $data = [];


	/**
	 * @param array $data
	 */
	function __construct( $data = [] ) {
		if ( is_array( $data ) ) {
			$this->data = $data;

			// Add shop data item
			$this->data['shop'] = new ShopDataItem();
		}

		$this->init();
	}


	/**
	 * Initiate the data layer
	 */
	function init() {
		$this->ensure_customer_object_compatibility();
		do_action( 'automatewoo/data_layer/init' );
	}


	/**
	 * Auto fill customer based on user and user based on customer for compatibility with legacy triggers, rules etc
	 */
	function ensure_customer_object_compatibility() {
		if ( $this->get_customer() && ! $this->get_user() ) {

			if ( $this->get_customer()->is_registered() ) {
				$this->set_item( 'user', $this->get_customer()->get_user() );
			}
			else {
				// if the user is not registered at this point they may be a legacy order guest
				if ( $order = $this->get_order() ) {
					$this->set_item( 'user', AW()->order_helper->prepare_user_data_item( $order ) );
				}
				else {
					// IMPORTANT the customer might be a guest in which case remove the user completely
					unset( $this->data['user'] );
				}
			}
		}

		if ( $this->get_user() && ! $this->get_customer() ) {
			$this->set_item( 'customer', Customer_Factory::get_by_user_data_item( $this->get_user() ) );
		}

		// also inject customer objects for guests
		// but note that the guest data object hasn't been completely removed
		if ( $this->get_guest() && ! $this->get_customer() ) {
			$this->set_item( 'customer', Customer_Factory::get_by_guest_id( $this->get_guest()->get_id() ) );
		}
	}


	function clear() {
		$this->data = [];
	}


	/**
	 * Returns unvalidated data layer
	 * @return array
	 */
	function get_raw_data() {
		return $this->data;
	}


	/**
	 * @param $type
	 * @param $item
	 */
	function set_item( $type, $item ) {
		$this->data[$type] = $item;
	}


	/**
	 * @param string $type
	 * @return mixed
	 */
	function get_item( $type ) {

		if ( ! isset( $this->data[$type] ) ) {
			return false;
		}

		return aw_validate_data_item( $type, $this->data[$type] );
	}


	/**
	 * @return Customer|false
	 */
	function get_customer() {
		return $this->get_item( 'customer' );
	}


	/**
	 * @return Cart|false
	 */
	function get_cart() {
		return $this->get_item( 'cart' );
	}


	/**
	 * @return Guest|bool
	 */
	function get_guest() {
		return $this->get_item( 'guest' );
	}


	/**
	 * @return \WP_User|Order_Guest|false
	 */
	function get_user() {
		return $this->get_item( 'user' );
	}


	/**
	 * @return \WC_Order|false
	 */
	function get_order() {
		return $this->get_item( 'order' );
	}


	/**
	 * @return \WC_Subscription|false
	 */
	function get_subscription() {
		return $this->get_item( 'subscription' );
	}


	/**
	 * @return \WC_Booking|false
	 */
	function get_booking() {
		return $this->get_item( 'booking' );
	}


	/**
	 * @return array|\WC_Order_Item_Product|false
	 */
	function get_order_item() {
		return $this->get_item( 'order_item' );
	}


	/**
	 * @return \WC_Memberships_User_Membership|false
	 */
	function get_membership() {
		return $this->get_item( 'membership' );
	}


	/**
	 * @return Wishlist|false
	 */
	function get_wishlist() {
		return $this->get_item( 'wishlist' );
	}


	/**
	 * @return \WC_Product|false
	 */
	function get_product() {
		return $this->get_item( 'product' );
	}


	/**
	 * @return Order_Note|false
	 */
	function get_order_note() {
		return $this->get_item( 'order_note' );
	}


	/**
	 * @return \WP_Comment|false
	 */
	function get_comment() {
		return $this->get_item( 'comment' );
	}


	/**
	 * @return Review|false
	 */
	function get_review() {
		return $this->get_item( 'review' );
	}


	/**
	 * @return Workflow|false
	 */
	function get_workflow() {
		return $this->get_item( 'workflow' );
	}


	/**
	 * @return \WP_Term|false
	 */
	function get_category() {
		return $this->get_item( 'category' );
	}


	/**
	 * @return \WP_Term|false
	 */
	function get_tag() {
		return $this->get_item( 'tag' );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function get_course() {
		return $this->get_item( 'course' );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function get_lesson() {
		return $this->get_item( 'lesson' );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function get_quiz() {
		return $this->get_item( 'quiz' );
	}

	/**
	 * @return \WP_User|false
	 */
	public function get_teacher() {
		return $this->get_item( 'teacher' );
	}

	/**
	 * This should return the language of the customer in the data layer.
	 *
	 * @return string|bool
	 */
	function get_language() {

		if ( ! Integrations::is_wpml() ) {
			return false;
		}

		// only use the order language if the order belongs to the customer
		if ( $this->order_belongs_to_customer ) {
			if ( $order = $this->get_order() ) {
				if ( $lang = $order->get_meta( 'wpml_language' ) ) {
					return $lang;
				}
			}
		}


		if ( $customer = $this->get_customer() ) {
			if ( $lang = $customer->get_language() ) {
				return $lang;
			}
		}

		if ( $user = $this->get_user() ) {
			if ( $lang = Language::get_user_language( $user ) ) {
				return $lang;
			}
		}

		if ( $guest = $this->get_guest() ) {
			if ( $lang = Language::get_guest_language( $guest ) ) {
				return $lang;
			}
		}

		return false;
	}


	/**
	 * Alias for $this->get_language()
	 * @return bool|string
	 */
	function get_customer_language() {
		return $this->get_language();
	}


	/**
	 * Gets the customer email based on the data layer.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_email() {
		$customer = $this->get_customer();

		if ( ! $customer ) {
			return '';
		}

		if ( $customer->is_registered() ) {
			// If the customer has an account always use the account email over a order billing email
			// The reason for this is that a customer could change their account email and their
			// orders or subscriptions will not be updated.
			return $customer->get_email();
		}
		else {
			// If the customer is not registered use the order/subscription billing email over the
			// email stored in the guest account.
			$prop = '';

			if ( $this->order_belongs_to_customer ) {
				if ( $subscription = $this->get_subscription() ) {
					$prop = Clean::email( $subscription->get_billing_email() );
				}

				if ( ! $prop && $order = $this->get_order() ) {
					$prop = Clean::email( $order->get_billing_email() );
				}
			}

			if ( ! $prop ) {
				$prop = $customer->get_email();
			}

			return $prop;
		}
	}


	/**
	 * Gets the customer first name based on the data layer.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_first_name() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_first_name();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_first_name();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_first_name();
		}

		return $prop;
	}


	/**
	 * Gets the customer last name based on the data layer.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_last_name() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_last_name();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_last_name();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_last_name();
		}

		return $prop;
	}


	/**
	 * Gets the customer full name based on the data layer.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_full_name() {
		return trim( sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo' ), $this->get_customer_first_name(), $this->get_customer_last_name() ) );
	}


	/**
	 * Gets the customer billing phone.
	 * Doesn't parse or format.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_phone() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_phone();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_phone();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_phone();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing company.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_company() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_company();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_company();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_company();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing country code.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_country() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_country();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_country();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_country();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing state.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_state() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_state();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_state();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_state();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing city.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_city() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_city();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_city();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_city();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing postcode.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_postcode() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_postcode();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_postcode();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_postcode();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing address 1.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_address_1() {
		$prop = '';

		if ( $this->order_belongs_to_customer ) {
			if ( $subscription = $this->get_subscription() ) {
				$prop = $subscription->get_billing_address_1();
			}

			if ( ! $prop && $order = $this->get_order() ) {
				$prop = $order->get_billing_address_1();
			}
		}

		if ( ! $prop && $customer = $this->get_customer() ) {
			$prop = $customer->get_billing_address_1();
		}

		return $prop;
	}


	/**
	 * Gets the customer billing address 2.
	 *
	 * @since 4.2
	 * @return string
	 */
	function get_customer_address_2() {
		$address_2 = '';
		$address_1 = '';
		$customer  = $this->get_customer();

		// since address 2 is often blank, only fall back if both address fields are blank

		if ( $this->order_belongs_to_customer ) {
			$subscription = $this->get_subscription();
			$order        = $this->get_order();

			if ( $subscription ) {
				$address_2 = $subscription->get_billing_address_2();
				$address_1 = $subscription->get_billing_address_1();
			}

			if ( ! $address_2 && ! $address_1 && $order ) {
				$address_2 = $order->get_billing_address_2();
				$address_1 = $order->get_billing_address_1();
			}
		}

		if ( ! $address_2 && ! $address_1 && $customer ) {
			$address_2 = $customer->get_billing_address_2();
		}

		return $address_2;
	}


	/**
	 * @since 4.2
	 * @param bool $include_name
	 * @return array
	 */
	function get_customer_address_array( $include_name = true ) {
		$args = [];

		if ( $include_name ) {
			$args['first_name'] = $this->get_customer_first_name();
			$args['last_name'] = $this->get_customer_last_name();
		}

		$args['company'] = $this->get_customer_company();
		$args['address_1'] = $this->get_customer_address_1();
		$args['address_2' ] = $this->get_customer_address_2();
		$args['city'] = $this->get_customer_city();
		$args['state'] = $this->get_customer_state();
		$args['postcode'] = $this->get_customer_postcode();
		$args['country'] = $this->get_customer_country();

		return $args;
	}


	/**
	 * Is the data layer missing data?
	 *
	 * Data can be missing if it has been deleted e.g. if an order has been trashed.
	 *
	 * @since 4.6
	 *
	 * @return bool
	 */
	public function is_missing_data() {
		$is_missing = false;

		foreach ( $this->get_raw_data() as $data_item ) {
			if ( ! $data_item ) {
				$is_missing = true;
			}
		}

		return $is_missing;
	}

}
