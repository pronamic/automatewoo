<?php
// phpcs:ignoreFile

defined( 'ABSPATH' ) || exit;

/**
 * Simplified function for third-parties.
 *
 * @since 4.2
 *
 * @param string|int $email_or_user_id
 * @return bool
 */
function aw_is_customer_opted_in( $email_or_user_id ) {
	if ( is_numeric( $email_or_user_id ) ) {
		$customer = AutomateWoo\Customer_Factory::get_by_user_id( $email_or_user_id );
	}
	else {
		$customer = AutomateWoo\Customer_Factory::get_by_email( $email_or_user_id );
	}

	if ( ! $customer ) {
		return false;
	}

	return $customer->is_opted_in();
}


/**
 * @return int
 */
function aw_get_user_count() {

	if ( $cache = AutomateWoo\Cache::get_transient( 'user_count' ) )
		return $cache;

	global $wpdb;

	$count = absint( $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users" ) );

	AutomateWoo\Cache::set_transient( 'user_count', $count );

	return $count;
}


/**
 * Use if accuracy is not important, count is cached for a week
 * @return int
 */
function aw_get_user_count_rough() {

	if ( $cache = AutomateWoo\Cache::get_transient( 'user_count_rough' ) )
		return $cache;

	global $wpdb;

	$count = absint( $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->users" ) );

	AutomateWoo\Cache::set_transient( 'user_count_rough', $count, 168 );

	return $count;
}


/**
 * @since 4.3
 *
 * @return AutomateWoo\Customer|bool
 */
function aw_get_logged_in_customer() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	return AutomateWoo\Customer_Factory::get_by_user_id( get_current_user_id() );
}

/**
 * Gets the user's first order.
 *
 * @param string|int   $email_or_user_id User email or id.
 * @param string|array $status           Order status we want to query.
 *                                       Defaults to paid statuses.
 *
 * @since 4.4
 *
 * @return bool|WC_Order
 */
function aw_get_customer_first_order( $email_or_user_id, $status = '' ) {
	$query_args = [
		'type'    => 'shop_order',
		'limit'   => 1,
		'orderby' => 'date',
		'order'   => 'ASC'
	];

	if ( empty( $status ) ) {
		$query_args['status'] = wc_get_is_paid_statuses();
	} else {
		$query_args['status'] = $status;
	}

	// Validate $email_or_user_id.
	if ( is_numeric( $email_or_user_id ) && $user_id = AutomateWoo\Clean::id( $email_or_user_id ) ) {
		$query_args['customer_id'] = $user_id;
	} elseif ( $email = AutomateWoo\Clean::email( $email_or_user_id ) ) {
		$query_args['customer'] = $email;
	} else {
		return false;
	}

	$orders = wc_get_orders( $query_args );

	if ( ! empty( $orders ) ) {
		return $orders[0];
	}

	return false;
}
