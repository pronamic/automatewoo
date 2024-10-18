<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Customer
 *
 * This class uses direct DB queries to fetch order data for performance reason.
 * The usage of WC_Order_Query is limited and won't return a set of totals.
 *
 * @since 3.0.0
 */
class Customer extends Abstract_Model_With_Meta_Table {

	/** @var string */
	public $table_id = 'customers';

	/** @var string  */
	public $object_type = 'customer';

	/**
	 * @param bool|int $id
	 */
	function __construct( $id = false ) {
		if ( $id ) {
			$this->get_by( 'id', $id );
		}
	}

	/**
	 * Returns the ID of the model's meta table.
	 *
	 * @return string
	 */
	public function get_meta_table_id() {
		return 'customer-meta';
	}

	/**
	 * @return int
	 */
	function get_user_id() {
		return (int) $this->get_prop( 'user_id' );
	}


	/**
	 * @param $user_id
	 */
	function set_user_id( $user_id ) {
		$this->set_prop( 'user_id', (int) $user_id );
	}


	/**
	 * @return int
	 */
	function get_guest_id() {
		return (int) $this->get_prop( 'guest_id' );
	}


	/**
	 * @param $guest_id
	 */
	function set_guest_id( $guest_id ) {
		$this->set_prop( 'guest_id', (int) $guest_id );
	}


	/**
	 * Returns a unique key that can ID the customer. This is added to the customer upon creation.
	 *
	 * @return string
	 */
	function get_key() {
		return Clean::string( $this->get_prop( 'id_key' ) );
	}


	/**
	 * @param string $key
	 */
	function set_key( $key ) {
		$this->set_prop( 'id_key', Clean::string( $key ) );
	}


	/**
	 * Generates a new key for registered users that don't have one.
	 *
	 * @deprecated tracking keys are replaced with $this->get_key()
	 *
	 * @since 4.0
	 * @return string
	 */
	function get_tracking_key() {
		wc_deprecated_function( __METHOD__, '5.2.0', 'get_key' );

		return Clean::string( $this->get_linked_prop( 'tracking_key' ) );
	}


	/**
	 * Get the creation date of the customer's last paid order.
	 *
	 * @return bool|DateTime
	 */
	public function get_date_last_purchased() {
		return $this->get_date_column( 'last_purchased' );
	}


	/**
	 * @param DateTime|string $date
	 */
	function set_date_last_purchased( $date ) {
		$this->set_date_column( 'last_purchased', $date );
	}


	/**
	 * Get the creation date of the customer's first paid order.
	 *
	 * @since 4.4
	 *
	 * @return DateTime|bool
	 */
	function get_date_first_purchased() {
		if ( $this->is_registered() ) {
			$first_order = aw_get_customer_first_order( $this->get_user_id() );
		} else {
			$first_order = aw_get_customer_first_order( $this->get_email() );
		}

		if ( $first_order ) {
			return aw_normalize_date( $first_order->get_date_created() );
		}

		return false;
	}

	/**
	 * Takes into account the global optin_mode option.
	 *
	 * If the customer is unsubscribed then all workflows will still run but any emails sending to
	 * this customer will be rejected and marked in the logs.
	 *
	 * @return string
	 */
	function is_unsubscribed() {
		do_action( 'automatewoo/customer/before_is_unsubscribed', $this );

		if ( Options::optin_enabled() ) {
			$is_unsubscribed = ! $this->get_is_subscribed();
		}
		else { // opt-out
			$is_unsubscribed = $this->get_is_unsubscribed();
		}

		return apply_filters( 'automatewoo/customer/is_unsubscribed', $is_unsubscribed, $this );
	}


	/**
	 * @return bool
	 */
	function is_opted_in() {
		return ! $this->is_unsubscribed();
	}


	/**
	 * @return string
	 */
	function is_opted_out() {
		return $this->is_unsubscribed();
	}


	/**
	 * Mark a customer as subscribed
	 */
	function opt_in() {
		if ( $this->get_is_subscribed() ) {
			return; // already subscribed
		}

		$this->set_is_subscribed( true );
		$this->set_date_subscribed( new DateTime() );
		$this->set_is_unsubscribed( false );
		$this->save();
		do_action( 'automatewoo/customer/opted_in', $this );
	}


	/**
	 * Mark a customer as unsubscribed
	 */
	function opt_out() {
		if ( $this->get_is_unsubscribed() ) {
			return; // already unsubscribed
		}

		$this->set_is_unsubscribed( true );
		$this->set_date_unsubscribed( new DateTime() );
		$this->set_is_subscribed( false );
		$this->save();
		do_action( 'automatewoo/customer/opted_out', $this );
	}


	/**
	 * @param bool $unsubscribed
	 */
	function set_is_unsubscribed( $unsubscribed ) {
		$this->set_prop( 'unsubscribed', aw_bool_int( $unsubscribed ) );
	}


	/**
	 * @return bool
	 */
	function get_is_unsubscribed() {
		return (bool) $this->get_prop( 'unsubscribed' );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_unsubscribed() {
		return $this->get_date_column( 'unsubscribed_date' );
	}


	/**
	 * @param DateTime|string $date
	 */
	function set_date_unsubscribed( $date ) {
		$this->set_date_column( 'unsubscribed_date', $date );
	}


	/**
	 * @param bool $subscribed
	 */
	function set_is_subscribed( $subscribed ) {
		$this->set_prop( 'subscribed', aw_bool_int( $subscribed ) );
	}


	/**
	 * @return bool
	 */
	function get_is_subscribed() {
		return (bool) $this->get_prop( 'subscribed' );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_subscribed() {
		return $this->get_date_column( 'subscribed_date' );
	}


	/**
	 * @param DateTime|string $date
	 */
	function set_date_subscribed( $date ) {
		$this->set_date_column( 'subscribed_date', $date );
	}


	/**
	 * @return Guest|false
	 */
	function get_guest() {
		if ( $this->is_registered() ) {
			return false;
		}
		return Guest_Factory::get( $this->get_guest_id() );
	}


	/**
	 * @return \WP_User
	 */
	function get_user() {
		return get_userdata( $this->get_user_id() );
	}


	/**
	 * @return Cart
	 */
	function get_cart() {
		if ( $this->is_registered() ) {
			return Cart_Factory::get_by_user_id( $this->get_user_id() );
		}
		else {
			return Cart_Factory::get_by_guest_id( $this->get_guest_id() );
		}
	}


	/**
	 * Deletes the customer's stored cart.
	 *
	 * @since 4.3.0
	 */
	function delete_cart() {
		if ( $cart = $this->get_cart() ) {
			$cart->delete();
		}
	}


	/**
	 * @return bool
	 */
	function is_registered() {
		return $this->get_user_id() !== 0;
	}


	/**
	 * @return string
	 */
	function get_email() {
		return Clean::email( $this->get_linked_prop( 'email' ) );
	}


	/**
	 * @return string
	 */
	function get_first_name() {
		return $this->get_linked_prop( 'first_name' );
	}


	/**
	 * @return string
	 */
	function get_last_name() {
		return $this->get_linked_prop( 'last_name' );
	}


	/**
	 * @return string
	 */
	function get_full_name() {
		return trim( sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo' ), $this->get_first_name(), $this->get_last_name() ) );
	}


	/**
	 * @return string
	 */
	function get_billing_country() {
		return $this->get_linked_prop( 'billing_country' );
	}


	/**
	 * @return string
	 */
	function get_billing_state() {
		return $this->get_linked_prop( 'billing_state' );
	}


	/**
	 * @return string
	 */
	function get_billing_phone() {
		return $this->get_linked_prop( 'billing_phone' );
	}


	/**
	 * @return string
	 */
	function get_billing_postcode() {
		return $this->get_linked_prop( 'billing_postcode' );
	}


	/**
	 * @return string
	 */
	function get_billing_city() {
		return $this->get_linked_prop( 'billing_city' );
	}


	/**
	 * @return string
	 */
	function get_billing_address_1() {
		return $this->get_linked_prop( 'billing_address_1' );
	}


	/**
	 * @return string
	 */
	function get_billing_address_2() {
		return $this->get_linked_prop( 'billing_address_2' );
	}


	/**
	 * @return string
	 */
	function get_billing_company() {
		return $this->get_linked_prop( 'billing_company' );
	}


	/**
	 * @param bool $include_name
	 * @return array
	 */
	function get_address( $include_name = true ) {
		$args = [];

		if ( $include_name ) {
			$args['first_name'] = $this->get_first_name();
			$args['last_name'] = $this->get_last_name();
		}

		$args['company'] = $this->get_billing_company();
		$args['address_1'] = $this->get_billing_address_1();
		$args['address_2' ] = $this->get_billing_address_2();
		$args['city'] = $this->get_billing_city();
		$args['state'] = $this->get_billing_state();
		$args['postcode'] = $this->get_billing_postcode();
		$args['country'] = $this->get_billing_country();

		return $args;
	}

	/**
	 * @param bool $include_name
	 * @return string
	 */
	function get_formatted_billing_address( $include_name = true ) {
		return WC()->countries->get_formatted_address( $this->get_address( $include_name ) );
	}


	/**
	 * Get meta value using legacy meta system.
	 *
	 * The legacy meta system stored data in the WP user meta table when the customer was registered
	 * and in the AW guest meta table when the customer was a guest.
	 *
	 * It's worth noting that guest meta does not become user meta when a guest creates an account.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function get_legacy_meta( $key ) {

		if ( ! $key ) return false;

		if ( $this->is_registered() ) {
			return get_user_meta( $this->get_user_id(), $key, true );
		}
		elseif ( $guest = $this->get_guest() ) {
			return $guest->get_meta( $key );
		}
	}

	/**
	 * Update meta value using legacy meta system.
	 *
	 * @see \AutomateWoo\Customer::get_legacy_meta()
	 *
	 * @param string $key
	 * @param $value
	 * @return mixed
	 */
	function update_legacy_meta( $key, $value ) {

		if ( ! $key ) return false;

		if ( $this->is_registered() ) {
			update_user_meta( $this->get_user_id(), $key, $value );
		}
		elseif ( $guest = $this->get_guest() ) {
			$guest->update_meta( $key, $value );
		}
	}


	/**
	 * Get count of customer's orders.
	 *
	 * Includes orders that match user ID OR billing email.
	 *
	 * @return int
	 */
	public function get_order_count() {
		$count = $this->get_meta( 'order_count' );

		if ( '' !== $count ) {
			return (int) $count;
		}

		global $wpdb;

		$statuses = array_map( 'esc_sql', aw_get_counted_order_statuses() );

		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$table = OrdersTableDataStore::get_orders_table_name();
			$query = "
				SELECT COUNT(DISTINCT id)
				FROM $table as orders
				WHERE orders.type = 'shop_order'
				AND orders.status IN ('" . implode( "','", $statuses ) . "')
				AND {$this->get_customer_order_sql()}
			";
		} else {
			$query = "
				SELECT COUNT(DISTINCT ID)
				FROM $wpdb->posts as posts
				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				WHERE posts.post_type = 'shop_order'
				AND posts.post_status IN ('" . implode( "','", $statuses ) . "')
				AND {$this->get_customer_order_sql()}
			";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare( $query, $this->get_customer_order_sql_args() ) );

		$this->update_meta( 'order_count', $count );

		return $count;
	}

	/**
	 * Get total spent by the customer.
	 *
	 * Includes orders that match user ID OR billing email.
	 *
	 * @return float
	 */
	public function get_total_spent() {
		$total = $this->get_meta( 'total_spent' );

		if ( '' !== $total ) {
			return (float) $total;
		}

		global $wpdb;

		$statuses = array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() );
		$statuses = array_map( 'esc_sql', $statuses );

		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$table = OrdersTableDataStore::get_orders_table_name();
			$query = "
				SELECT SUM(total_amount)
				FROM $table as orders
				WHERE orders.type = 'shop_order'
				AND orders.status IN ('" . implode( "','", $statuses ) . "')
				AND {$this->get_customer_order_sql()}
			";
		} else {
			$query = "
				SELECT SUM(order_total) FROM (
					SELECT posts.ID as order_id, meta2.meta_value as order_total
					FROM $wpdb->posts as posts
					LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
					LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id
					WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ('" . implode( "','", $statuses ) . "')
					AND {$this->get_customer_order_sql()}
					AND meta2.meta_key = '_order_total'
					GROUP BY order_id
				) AS orders_table
			";
		}

		// Use formatting function to round the total
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total = (float) Format::decimal( $wpdb->get_var( $wpdb->prepare( $query, $this->get_customer_order_sql_args() ) ) );

		$this->update_meta( 'total_spent', $total );

		return $total;
	}

	/**
	 * Get SQL used for customer order queries.
	 *
	 * Used to get orders that match user ID OR email.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	protected function get_customer_order_sql() {
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$sql = '( orders.billing_email = %s )';

			if ( $this->is_registered() ) {
				$sql .= 'OR ( orders.customer_id = %s )';
			}
		} else {
			$sql = "( meta.meta_key = '_billing_email' AND meta.meta_value = %s )";

			if ( $this->is_registered() ) {
				$sql .= "OR ( meta.meta_key = '_customer_user' AND meta.meta_value = %s )";
			}
		}

		if ( $this->is_registered() ) {
			return "(( {$sql} ))";
		}

		return $sql;
	}

	/**
	 * Get SQL query args used for customer order queries.
	 *
	 * @since 4.6.0
	 *
	 * @return array
	 */
	protected function get_customer_order_sql_args() {
		$args = [ $this->get_email() ];

		if ( $this->is_registered() ) {
			$args[] = $this->get_user_id();
		}

		return $args;
	}

	/**
	 * @return string
	 */
	function get_role() {
		if ( $this->is_registered() && $user = $this->get_user() ) {
			return current( $user->roles );
		}
		else {
			return 'guest';
		}
	}


	/**
	 * Get the customer's language if site is multilingual.
	 *
	 * @return string
	 */
	public function get_language() {
		$lang = '';

		if ( Language::is_multilingual() ) {
			if ( $this->is_registered() ) {
				$lang = Language::get_user_language( $this->get_user() );
			} else {
				$lang = Language::get_guest_language( $this->get_guest() );
			}
		}

		return apply_filters( 'automatewoo/customer/get_language', $lang, $this );
	}

	/**
	 * Gets the user registered date, if the user is registered.
	 *
	 * @since 4.4
	 *
	 * @return DateTime|bool
	 */
	function get_date_registered() {
		$user = $this->get_user();

		if ( $user ) {
			// user_registered is saved in UTC
			return aw_normalize_date( $user->user_registered );
		}

		return false;
	}


	/**
	 * No need to save after using this method
	 * @param $language
	 */
	function update_language( $language ) {

		if ( ! Language::is_multilingual() || ! $language ) {
			return;
		}

		if ( $this->is_registered() ) {
			$user_lang = get_user_meta( $this->get_user_id(), '_aw_persistent_language', true );

			if ( $user_lang != $language ) {
				Language::set_user_language( $this->get_user_id(), $language );
			}
		}
		else {
			if ( $guest = $this->get_guest() ) {
				if ( $guest->get_language() != $language ) {
					$guest->set_language( $language );
					$guest->save();
				}
			}
		}
	}


	/**
	 * Get product and variation ids of all the customers purchased products
	 *
	 * @return array
	 */
	public function get_purchased_products() {
		global $wpdb;

		$transient_name = 'aw_cpp_' . md5( $this->get_id() . \WC_Cache_Helper::get_transient_version( 'orders' ) );
		$products       = get_transient( $transient_name );

		if ( $products === false ) {

			$statuses = array_map( 'esc_sql', aw_get_counted_order_statuses( true ) );

			if ( HPOS_Helper::is_HPOS_enabled() ) {
				$orders_table = OrdersTableDataStore::get_orders_table_name();

				$query = "
					SELECT im.meta_value FROM {$orders_table} as orders
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON orders.id = i.order_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
					WHERE orders.type = 'shop_order'
					AND orders.status IN ('" . implode( "','", $statuses ) . "')
					AND im.meta_key IN ( '_product_id', '_variation_id' )
					AND im.meta_value != 0
					AND {$this->get_customer_order_sql()}
				";
			} else {
				$query = "
					SELECT im.meta_value FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->postmeta} AS meta ON p.ID = meta.post_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
					WHERE p.post_status IN ( '" . implode( "','", $statuses ) . "' )
					AND im.meta_key IN ( '_product_id', '_variation_id' )
					AND im.meta_value != 0
					AND {$this->get_customer_order_sql()}
				";
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->get_col( $wpdb->prepare( $query, $this->get_customer_order_sql_args() ) );

			$products = array_unique( array_map( 'absint', $result ) );

			set_transient( $transient_name, $products, DAY_IN_SECONDS * 30 );
		}

		return $products;
	}



	/**
	 * @param $prop
	 * @return mixed
	 */
	function get_linked_prop( $prop ) {

		$guest = false;
		$user = false;

		if ( $this->is_registered() ) {
			if ( ! $user = $this->get_user() ) {
				return false;
			}
		}
		else {
			if ( ! $guest = $this->get_guest() ) {
				return false;
			}
		}

		switch ( $prop ) {
			case 'email':
				return $this->is_registered() ? $user->user_email : $guest->get_email();
				break;
			case 'first_name':
				return $this->is_registered() ? $user->first_name : $guest->get_first_name();
				break;
			case 'last_name':
				return $this->is_registered() ? $user->last_name : $guest->get_last_name();
				break;
			case 'billing_country':
				return $this->is_registered() ? $user->billing_country : $guest->get_country();
				break;
			case 'billing_state':
				return $this->is_registered() ? $user->billing_state : $guest->get_state();
				break;
			case 'billing_phone':
				return $this->is_registered() ? $user->billing_phone : $guest->get_phone();
				break;
			case 'billing_company':
				return $this->is_registered() ? $user->billing_company : $guest->get_company();
				break;
			case 'billing_address_1':
				return $this->is_registered() ? $user->billing_address_1 : $guest->get_address_1();
				break;
			case 'billing_address_2':
				return $this->is_registered() ? $user->billing_address_2 : $guest->get_address_2();
				break;
			case 'billing_postcode':
				return $this->is_registered() ? $user->billing_postcode : $guest->get_postcode();
				break;
			case 'billing_city':
				return $this->is_registered() ? $user->billing_city : $guest->get_city();
				break;
			case 'tracking_key':
				if ( $this->is_registered() ) {
					// not every registered user will have a key
					if ( ! $key = get_user_meta( $this->get_user_id(), 'automatewoo_visitor_key', true ) ) {
						$key = aw_generate_key( 32 );
						update_user_meta( $this->get_user_id(), 'automatewoo_visitor_key', $key );
					}
					return $key;
				}
				else {
					// guests are always created with a key
					return $guest->get_key();
				}
				break;
		}

	}

	/**
	 * Get reviews for our customer.
	 *
	 * @since 4.4
	 *
	 * @param array $args Arguments array.
	 *
	 * @return array|int
	 */
	function get_reviews( $args = [] ) {
		$query_args = wp_parse_args( $args, [
			'status' => 'approve',
			'count'  => false,
			'type'   => 'review',
			'parent' => 0,
		] );

		if ( $this->is_registered() ) {
			$query_args['user_id'] = $this->get_user_id();
		} else {
			$query_args['author_email'] = $this->get_email();
		}

		return get_comments( $query_args );
	}

	/**
	 * Get the customer's review count.
	 *
	 * NOTE: This excludes multiple reviews of the same product.
	 *
	 * @return int
	 */
	function get_review_count() {
		$cache_group = 'customer_review_count';

		if ( Cache::exists( $this->get_id(), $cache_group ) ) {
			$count = (int) Cache::get( $this->get_id(), $cache_group );
		} else {
			$count = $this->calculate_unique_product_review_count();
			Cache::set( $this->get_id(), $count, $cache_group );
		}

		return $count;
	}

	/**
	 * Calculate the customer's review count excluding multiple reviews on the same product.
	 *
	 * @since 4.5
	 *
	 * @return int
	 */
	function calculate_unique_product_review_count() {
		global $wpdb;
		$sql = "SELECT COUNT(DISTINCT comment_post_ID) FROM {$wpdb->comments}
				WHERE comment_parent = 0
				AND comment_approved = 1
				AND comment_type = 'review'
				AND (user_ID = %d OR comment_author_email = %s)";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, [ $this->get_user_id(), $this->get_email() ] ) );
	}

	/**
	 * Clear customer review count cache.
	 *
	 * @since 4.5
	 */
	function clear_review_count_cache() {
		Cache::delete( $this->get_id(), 'customer_review_count' );
	}

	/**
	 * Gets the last review date for the user.
	 *
	 * @since 4.4
	 *
	 * @return DateTime|bool
	 */
	function get_last_review_date() {
		$cache_key         = "customer_last_review_date";
		$last_comment_date = false;

		if ( Temporary_Data::exists( $cache_key, $this->get_id() ) ) {
			$last_comment_date = Temporary_Data::get( $cache_key, $this->get_id() );
		} else {
			$comments = $this->get_reviews( [ 'number' => 1 ] );

			if ( ! empty( $comments ) ) {
				$last_comment_date = $comments[0]->comment_date_gmt;
				Temporary_Data::set( $cache_key, $this->get_id(), $last_comment_date );
			}
		}

		if ( ! $last_comment_date ) {
			return false;
		}

		return new DateTime( $last_comment_date );
	}

	/**
	 * Get the date that a workflow last run for the customer.
	 *
	 * @since 4.4
	 *
	 * @param int|array|Workflow $workflow Workflow object, ID or array of IDs.
	 *
	 * @return DateTime|bool
	 */
	function get_workflow_last_run_date( $workflow ) {
		if ( ! $workflow ) {
			return false;
		}

		$query = new Log_Query();
		$query->where_workflow( $workflow );
		$query->where_customer_or_legacy_user( $this, true );
		$query->set_limit( 1 );
		$query->set_ordering( 'date', 'DESC' );
		$results = $query->get_results();

		if ( $results ) {
			return current( $results )->get_date();
		}

		return false;
	}

	/**
	 * Get customer's nth last paid order.
	 *
	 * @param int $n
	 *
	 * @return \WC_Order|bool
	 *
	 * @since 4.8.0
	 */
	public function get_nth_last_paid_order( $n ) {
		$query_args = [
			'type'    => 'shop_order',
			'limit'   => 1,
			'offset'  => $n - 1,
			'status'  => wc_get_is_paid_statuses(),
			'orderby' => 'date',
			'order'   => 'DESC',
		];

		if ( $this->is_registered() ) {
			$query_args['customer'] = [ $this->get_user_id(), $this->get_email() ];
		} else {
			$query_args['customer'] = $this->get_email();
		}

		return current( wc_get_orders( $query_args ) );
	}

}

