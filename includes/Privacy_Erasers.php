<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * @class Privacy_Erasers
 * @since 4.0
 */
class Privacy_Erasers {

	static $limit = 10;


	/**
	 * Anonymize logs
	 *
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_workflow_logs( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			return $response;
		}

		// query shouldn't be paged because items are being deleted in each batch
		$query = new Log_Query();
		$query->where_customer_or_legacy_user( $customer, true );
		$query->set_limit( self::$limit );

		$results = $query->get_results();
		$results_count = count( $results );
		$response['done'] = $results_count < self::$limit;

		if ( $response['done'] ) {
			$response['messages'][] = __( 'Anonymized customer workflow logs.', 'automatewoo' );
		}

		if ( $results ) {
			$response['items_retained'] = true;

			foreach( $results as $log ) {
				self::anonymize_personal_log_data( $log, $email );
			}
		}

		return $response;
	}


	/**
	 * @param Log $log
	 * @param string $email
	 */
	public static function anonymize_personal_log_data( $log, $email ) {
		$log_storage_keys_to_erase = array_keys( DataTypes::get_all() ); // all possible data types

		aw_array_remove_value( $log_storage_keys_to_erase, 'guest' );
		aw_array_remove_value( $log_storage_keys_to_erase, 'workflow' );

		$log->update_meta( Logs::get_data_layer_storage_key( 'guest' ), aw_anonymize_email( $email ) );

		foreach( $log_storage_keys_to_erase as $key ) {
			$log->delete_meta( Logs::get_data_layer_storage_key( $key ) );
		}

		$log->delete_meta( 'notes' );
		$log->update_meta( 'is_anonymized', true );
	}


	/**
	 * Remove all queued events for the customer.
	 *
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_workflow_queue( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			return $response;
		}

		// query shouldn't be paged because items are being deleted in each batch
		$query = new Queue_Query();
		$query->set_limit( self::$limit );
		$query->where_customer_or_legacy_user( $customer, true );

		$results = $query->get_results();
		$results_count = count( $results );
		$response['done'] = $results_count < self::$limit;

		if ( $response['done'] ) {
			$response['messages'][] = __( 'Removed workflow queued events.', 'automatewoo' );
		}

		if ( $results ) {
			$response['items_removed'] = true;

			foreach( $results as $result ) {
				$result->delete();
			}
		}

		return $response;
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_cart( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			return $response;
		}

		$removed = false;

		if ( $cart = Cart_Factory::get_by_user_id( $customer->get_user_id() ) ) {
			$removed = true;
			$cart->delete();
		}

		if ( $guest = Guest_Factory::get_by_email( Clean::email( $email ) ) ) {
			if ( $cart = $guest->get_cart() ) {
				$removed = true;
				$cart->delete();
			}
		}

		if ( $removed ) {
			$response['items_removed'] = true;
			$response['messages'][] = __( 'Removed saved cart.', 'automatewoo' );
		}

		return $response;
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function user_meta( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$user = get_user_by( 'email', $email );

		if ( ! $user instanceof \WP_User ) {
			return $response;
		}

		delete_user_meta( $user->ID, 'automatewoo_visitor_key' );
		delete_user_meta( $user->ID, '_automatewoo_customer_id' );
		delete_user_meta( $user->ID, 'automatewoo_email_preview_test_emails' );
		delete_user_meta( $user->ID, '_aw_order_count' );
		delete_user_meta( $user->ID, '_aw_order_ids' );
		delete_user_meta( $user->ID, '_aw_persistent_language' );

		do_action( 'automatewoo/privacy/erase_user_meta', $user ); // for add-ons

		$response['items_removed'] = true;
		$response['messages'][] = __( 'Removed AutomateWoo user meta.', 'automatewoo' );

		return $response;
	}


	/**
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function user_tags( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$user = get_user_by( 'email', $email );

		if ( ! $user instanceof \WP_User ) {
			return $response;
		}

		wp_set_object_terms( $user->ID, '', 'user_tag' );

		$response['items_removed'] = true;
		$response['messages'][] = __( 'Removed user tags.', 'automatewoo' );

		return $response;
	}


	/**
	 * Completely erases the guest object matching an email.
	 *
	 * @param string $email
	 * @param int $page
	 * @return array
	 */
	public static function customer_and_guest_object( $email, $page ) {
		$response = [
			'items_removed' => false,
			'items_retained' => false,
			'messages' => [],
			'done' => true,
		];

		$customer = Customer_Factory::get_by_email( $email );

		if ( ! $customer ) {
			return $response;
		}

		$customer->delete();

		if ( $guest = Guest_Factory::get_by_email( Clean::email( $email ) ) ) {
			$guest->delete();
		}

		$response['items_removed'] = true;
		$response['messages'][] = __( 'Removed AutomateWoo customer object.', 'automatewoo' );

		return $response;
	}


}
