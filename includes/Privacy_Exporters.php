<?php
// phpcs:ignoreFile

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Privacy_Exporters
 * @since 4.0
 */
class Privacy_Exporters {

	static $limit = 10;


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_data( $email, $page ) {
		$response = [
			'data' => [],
			'done' => true,
		];

		$personal_data = self::get_customer_personal_data( $email );

		if ( ! $personal_data ) {
			return $response;
		}

		$item = [
			'group_id' => 'automatewoo_customer',
			'group_label' => __( 'Customer Data', 'automatewoo' ),
			'item_id' => 'customer',
			'data' => $personal_data,
		];

		$response['data'][] = $item;

		return $response;
	}


	/**
	 * @param string $email
	 * @return array
	 */
	static function get_customer_personal_data( $email ) {
		$data = [];
		$email = Clean::email( $email );

		// look for a customer but don't create a new one
		if ( $customer = Customer_Factory::get_by_email( $email, false ) ) {
			$data[ __( 'Customer ID', 'automatewoo' ) ] = $customer->get_id();
			$data[ __( 'Customer key', 'automatewoo' ) ] = $customer->get_key();
			$data[ __( 'Language', 'automatewoo' ) ] = $customer->get_language();
		}

		// user data
		$user = get_user_by( 'email', $email );

		if ( $user instanceof \WP_User ) {

			// show legacy tracking key
			if ( $tracking_key = get_user_meta( $user->ID, 'automatewoo_visitor_key', true ) ) {
				$data[ __( 'Tracking key', 'automatewoo' ) ] = $tracking_key;
			}

			$workflow_preview_emails = Clean::string( get_user_meta( $user->ID, 'automatewoo_email_preview_test_emails', true ) );

			if ( $workflow_preview_emails ) {
				$data[ __( 'Workflow preview emails', 'automatewoo' ) ] = $workflow_preview_emails;
			}

			if ( $tags = wp_get_object_terms( $user->ID, 'user_tag' ) ) {
				$tag_names = wp_list_pluck( $tags, 'name' );
				$data[ __( 'User tags', 'automatewoo' ) ] = implode( ', ', $tag_names );
			}
		}

		// get guest data
		if ( $guest = Guest_Factory::get_by_email( $email ) ) {
			$data[ __( 'Guest ID', 'automatewoo' ) ] = $guest->get_id();
			if ( $key = $guest->get_key() ) {
				$data[ __( 'Tracking key ID', 'automatewoo' ) ] = $key;
			}
			$data[ __( 'First name', 'automatewoo' ) ] = Clean::string( $guest->get_first_name( true ) );
			$data[ __( 'Last name', 'automatewoo' ) ] = Clean::string( $guest->get_last_name( true ) );
			$data[ __( 'Phone', 'automatewoo' ) ] = Clean::string( $guest->get_phone( true ) );
			$data[ __( 'Country', 'automatewoo' ) ] = Clean::string( $guest->get_country(true) );
			$data[ __( 'State', 'automatewoo' ) ] = Clean::string( $guest->get_state(true) );
			$data[ __( 'City', 'automatewoo' ) ] = Clean::string( $guest->get_city(true) );
			$data[ __( 'Address', 'automatewoo' ) ] = Clean::string( $guest->get_address_1(true) . ' ' . $guest->get_address_2(true) );
			$data[ __( 'Postcode', 'automatewoo' ) ] = Clean::string( $guest->get_postcode(true) );
			$data[ __( 'Company', 'automatewoo' ) ] = Clean::string( $guest->get_company(true) );
			$data[ __( 'Guest created', 'automatewoo' ) ] = Format::datetime( $guest->get_date_created(), 0 );
			$data[ __( 'Guest last active', 'automatewoo' ) ] = Format::datetime( $guest->get_date_last_active(), 0 );
		}

		$data = apply_filters( 'automatewoo/privacy/exported_customer_data', $data, $email );
		return Privacy::parse_export_data_array( $data );
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_cart( $email, $page ) {
		$response = [
			'data' => [],
			'done' => true,
		];

		if ( ! $customer = Customer_Factory::get_by_email( $email ) ) {
			return $response;
		}

		if ( ! $cart = $customer->get_cart() ) {
			return $response;
		}

		$cart_data = [];
		$cart_data[ __( 'Cart ID', 'automatewoo' ) ] = $cart->get_id();
		$cart_data[ __( 'Cart status', 'automatewoo' ) ] = $cart->get_status();
		$cart_data[ __( 'Cart token', 'automatewoo' ) ] = $cart->get_token();
		$cart_data[ __( 'Cart currency', 'automatewoo' ) ] = $cart->get_currency();
		$cart_data[ __( 'Cart shipping tax total', 'automatewoo' ) ] = $cart->price( $cart->get_shipping_tax_total() );
		$cart_data[ __( 'Cart shipping total', 'automatewoo' ) ] = $cart->price( $cart->get_shipping_total() );
		$cart_data[ __( 'Cart total', 'automatewoo' ) ] = $cart->price( $cart->get_total() );
		$cart_data[ __( 'Date last modified', 'automatewoo' ) ] = Format::datetime( $cart->get_date_last_modified(), 0 );
		$cart_data[ __( 'Date created', 'automatewoo' ) ] = Format::datetime( $cart->get_date_created(), 0 );
		$cart_data[ __( 'Coupons', 'automatewoo' ) ] = implode( ', ', array_keys( $cart->get_coupons() ) );

		$items = [];

		foreach( $cart->get_items() as $cart_item ) {
			$items[] = $cart_item->get_quantity() . ' x ' . $cart_item->get_name();
		}

		$cart_data[ __( 'Items', 'automatewoo' ) ] = implode( ', ', $items );


		$fees = [];

		foreach( $cart->get_fees() as $fee ) {
			$fees[] = $fee->name . ' ' . $cart->price( $fee->amount + $fee->tax );
		}

		$cart_data[ __( 'Fees', 'automatewoo' ) ] = implode( ', ', $fees );

		$item = [
			'group_id' => 'automatewoo_cart',
			'group_label' => __( 'Saved Cart Data', 'automatewoo' ),
			'item_id' => 'cart',
			'data' => Privacy::parse_export_data_array( $cart_data ),
		];

		$response['data'][] = $item;

		return $response;
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_workflow_logs( $email, $page ) {
		$response = [
			'data' => [],
			'done' => true,
		];

		if ( ! $customer = Customer_Factory::get_by_email( $email ) ) {
			return $response;
		}

		$query = new Log_Query();
		$query->where_customer_or_legacy_user( $customer, true, true );
		$query->set_limit( self::$limit );
		$query->set_page( $page );

		$results = $query->get_results();
		$results_count = count( $results );
		$response['done'] = $results_count < self::$limit;

		foreach( $results as $log ) {
			$item = [
				'group_id' => 'automatewoo_logs',
				'group_label' => __( 'Workflow logs', 'automatewoo' ),
				'item_id' => 'log-' . $log->get_id(),
				'data' => self::get_log_data( $log ),
			];
			$response['data'][] = $item;
		}

		return $response;
	}


	/**
	 * @param Log $log
	 * @return array
	 */
	public static function get_log_data( $log ) {
		$data = [];

		$workflow = $log->get_workflow();

		$data[ __( 'Log ID', 'automatewoo' ) ] = $log->get_id();
		if ( $workflow ) {
			$data[ __( 'Log workflow', 'automatewoo' ) ] = $workflow->get_title();
		}
		$data[ __( 'Log date', 'automatewoo' ) ] = Format::datetime( $log->get_date(), 0 );
		$data[ __( 'Tracking enabled', 'automatewoo' ) ] = Format::bool( $log->is_tracking_enabled() );
		$data[ __( 'Conversion tracking enabled', 'automatewoo' ) ] = Format::bool( $log->is_conversion_tracking_enabled() );

		if ( $notes = $log->get_meta( 'notes' ) ) {
			$data[ __( 'Notes', 'automatewoo' ) ] = implode( ', ', $notes );
		}

		if ( $tracking_data = $log->get_meta('tracking_data') ) {
			$data[ __( 'Click and open tracking', 'automatewoo' ) ] = print_r( $tracking_data, true );
		}

		$data_layer = Privacy_Exporters::format_data_layer( $log->get_data_layer('object') );
		$data[ __( 'Related data', 'automatewoo' ) ] = implode( ', ', $data_layer );

		return Privacy::parse_export_data_array( $data );
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_workflow_queue( $email, $page ) {
		$response = [
			'data' => [],
			'done' => true,
		];

		if ( ! $customer = Customer_Factory::get_by_email( $email ) ) {
			return $response;
		}

		$query = new Queue_Query();
		$query->where_customer_or_legacy_user( $customer, true, true );
		$query->set_limit( self::$limit );
		$query->set_page( $page );

		$results = $query->get_results();
		$results_count = count( $results );
		$response['done'] = $results_count < self::$limit;

		foreach( $results as $event ) {
			$item = [
				'group_id' => 'automatewoo_queue',
				'group_label' => __( 'Workflow queued events', 'automatewoo' ),
				'item_id' => 'queued-event-' . $event->get_id(),
				'data' => self::get_queued_event_data( $event ),
			];
			$response['data'][] = $item;
		}

		return $response;
	}


	/**
	 * @param Queued_Event $event
	 * @return array
	 */
	public static function get_queued_event_data( $event ) {
		$data = [];

		$workflow = $event->get_workflow();

		$data[ __( 'Event ID', 'automatewoo' ) ] = $event->get_id();
		if ( $workflow ) {
			$data[ __( 'Event workflow', 'automatewoo' ) ] = $workflow->get_title();
		}
		$data[ __( 'Date created', 'automatewoo' ) ] = Format::datetime( $event->get_date_created(), 0 );
		$data[ __( 'Date due', 'automatewoo' ) ] = Format::datetime( $event->get_date_due(), 0 );
		$data[ __( 'Failed', 'automatewoo' ) ] = Format::bool( $event->is_failed() );

		if ( $event->is_failed() ) {
			$data[ __( 'Failure code', 'automatewoo' ) ] = Format::bool( $event->get_failure_code() );
		}

		$data_layer = Privacy_Exporters::format_data_layer( $event->get_data_layer('object') );
		$data[ __( 'Related data', 'automatewoo' ) ] = implode( ', ', $data_layer );

		return Privacy::parse_export_data_array( $data );
	}


	/**
	 * @param Data_Layer $data_layer
	 * @return array
	 */
	public static function format_data_layer( $data_layer ) {
		$raw_data_layer = $data_layer->get_raw_data();
		$formatted_data = [];

		foreach ( $raw_data_layer as $data_type => $data_item ) {

			if ( ! $data_item ) {
				continue;
			}

			switch ( $data_type ) {

				// Exclude user data since it's possible that a different user could be viewing the data layer
				case 'user':
				case 'guest':
				case 'customer':
					break;

				case 'order':
					/** @var $data_item \WC_Order */
					$formatted_data[] = [
						'title' => __('Order', 'automatewoo'),
						'value' => '#' . $data_item->get_id(),
					];
					break;

				case 'cart':
					/** @var $data_item Cart */
					$formatted_data[] = [
						'title' => __( 'Cart', 'automatewoo' ),
						'value' => '#' . $data_item->get_id()
					];
					break;

				case 'review':
					/** @var $data_item Review */
					$formatted_data[] = [
						'title' => __( 'Review', 'automatewoo' ),
						'value' => 'Comment #' . $data_item->get_id()
					];
					break;

				case 'product':
					/** @var $data_item \WC_Product */
					$formatted_data[] = [
						'title' => __( 'Product', 'automatewoo' ),
						'value' => $data_item->get_title()
					];
					break;

				case 'subscription':
					/** @var $data_item \WC_Subscription */
					$formatted_data[] = [
						'title' => __( 'Subscription', 'automatewoo' ),
						'value' => '#' . $data_item->get_id(),
					];
					break;

				case 'membership':
					/** @var $data_item \WC_Memberships_User_Membership */
					$formatted_data[] = [
						'title' => __( 'Membership', 'automatewoo' ),
						'value' => "#$data_item->id"
					];
					break;

				case 'wishlist':
					$formatted_data[] = [
						'title' => __( 'Wishlist', 'automatewoo' ),
						'value' => '#' . $data_item->id
					];

					break;
			}
		}

		$formatted_data = apply_filters( 'automatewoo/privacy/exported_data_layer', $formatted_data, $raw_data_layer );

		$return = [];
		foreach( $formatted_data as $item ) {
			$return[] = $item['title'] . ': '. $item['value'];
		}
		return $return;
	}

}