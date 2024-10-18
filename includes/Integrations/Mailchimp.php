<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Integration class for Mailchimp.
 *
 * @class Integration_Mailchimp
 */
class Integration_Mailchimp extends Integration {

	/**
	 * ID of this integration.
	 *
	 * @var string
	 */
	public $integration_id = 'mailchimp';

	/**
	 * The Mailchimp API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * The root API URL for Mailchimp.
	 *
	 * @var string
	 */
	private $api_root = 'https://<dc>.api.mailchimp.com/3.0';

	/**
	 * Constructor.
	 *
	 * @param string $api_key The Mailchimp API key.
	 */
	public function __construct( $api_key ) {
		list( $key, $data_center ) = explode( '-', $api_key );

		$this->api_key  = $key;
		$this->api_root = str_replace( '<dc>', $data_center, $this->api_root );
	}


	/**
	 * Test the current API key to confirm that AutomateWoo can communicate with the Mailchimp API
	 *
	 * @return bool
	 */
	public function test_integration(): bool {
		return $this->request( 'GET', '/' )->is_successful();
	}

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::mailchimp_enabled();
	}

	/**
	 * Make a request to the API.
	 *
	 * @param string $method   The request method.
	 * @param string $endpoint The request endpoint.
	 * @param array  $args     Additional request arguments.
	 *
	 * @return Remote_Request
	 */
	public function request( $method, $endpoint, $args = [] ) {
		$request_args = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $this->api_key ),
			],
			'timeout'   => 15,
			'method'    => $method,
			'sslverify' => false,
		];

		$url = $this->api_root . $endpoint;

		switch ( $method ) {
			case 'GET':
				// SEMGREP WARNING EXPLANATION
				// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
				$url = esc_url_raw( add_query_arg( array_map( 'urlencode', $args ), $url ) );
				break;

			default:
				$request_args['body'] = wp_json_encode( $args );
				break;
		}

		$request = new Remote_Request( $url, $request_args );
		$this->maybe_log_request_errors( $request );

		return $request;
	}

	/**
	 * Get Mailchimp lists.
	 *
	 * @return array
	 */
	public function get_lists() {
		$cache = Cache::get_transient( 'mailchimp_lists' );
		if ( $cache ) {
			return $cache;
		}

		$request = $this->request(
			'GET',
			'/lists',
			[
				'count' => 100,
			]
		);

		$clean_lists = [];

		if ( $request->is_successful() ) {
			$body = $request->get_body();

			if ( is_array( $body['lists'] ) ) {
				foreach ( $body['lists'] as $list ) {
					$clean_lists[ $list['id'] ] = $list['name'];
				}
			}
		}

		Cache::set_transient( 'mailchimp_lists', $clean_lists, 0.15 );

		return $clean_lists;
	}

	/**
	 * Get the fields for a particular list.
	 *
	 * @param string $list_id The list ID.
	 *
	 * @return array
	 */
	public function get_list_fields( $list_id ) {
		if ( ! $list_id ) {
			return [];
		}

		$cache_key = "mailchimp_list_fields_$list_id";
		$cache     = Cache::get_transient( $cache_key );
		if ( $cache ) {
			return (array) $cache;
		}

		$request = $this->request(
			'GET',
			"/lists/$list_id/merge-fields",
			[
				'count' => 100,
			]
		);

		if ( ! $request->is_successful() ) {
			return [];
		}

		$body   = $request->get_body();
		$fields = isset( $body['merge_fields'] ) ? $body['merge_fields'] : [];

		Cache::set_transient( $cache_key, $fields, 0.15 );

		return $fields;
	}

	/**
	 * Get interest categories for a particular list.
	 *
	 * @param string $list_id The list ID.
	 * @return array
	 */
	public function get_list_interest_categories( $list_id ) {
		if ( ! $list_id ) {
			return [];
		}

		$cache_key = "mc_list_interests_$list_id";
		$cache     = Cache::get_transient( $cache_key );
		if ( $cache ) {
			return (array) $cache;
		}

		$data    = [];
		$request = $this->request(
			'GET',
			"/lists/$list_id/interest-categories",
			[
				'count' => 100,
			]
		);

		if ( ! $request->is_successful() ) {
			return [];
		}

		$body       = $request->get_body();
		$categories = isset( $body['categories'] ) ? (array) $body['categories'] : [];
		foreach ( $categories as $category ) {
			$data[ $category['id'] ] = [
				'id'        => $category['id'],
				'title'     => $category['title'],
				'interests' => $this->get_interest_categories_interests( $list_id, $category['id'] ),
			];
		}

		Cache::set_transient( $cache_key, $data, 0.15 );

		return $data;
	}

	/**
	 * Get interests for a particular category from the API.
	 *
	 * Protected method due to not being cached, use $this->get_list_interest_categories()
	 *
	 * @param string $list_id     The list ID.
	 * @param string $category_id The category ID.
	 *
	 * @return array
	 */
	protected function get_interest_categories_interests( $list_id, $category_id ) {
		$data    = [];
		$request = $this->request(
			'GET',
			"/lists/$list_id/interest-categories/$category_id/interests",
			[
				'count' => 100,
			]
		);

		if ( ! $request->is_successful() ) {
			return [];
		}

		$body      = $request->get_body();
		$interests = isset( $body['interests'] ) ? (array) $body['interests'] : [];
		foreach ( $interests as $interest ) {
			$data[ $interest['id'] ] = $interest['name'];
		}

		return $data;
	}

	/**
	 * Determine whether a contact is part of the given list.
	 *
	 * This does not reveal whether they are a subscriber. For that, see the is_subscribed_to_list() method.
	 *
	 * @param string $email   The email address.
	 * @param string $list_id The list ID.
	 *
	 * @return bool
	 */
	public function is_contact( $email, $list_id ) {
		$status = $this->get_subscriber_status_for_list( $email, $list_id );

		return '' !== $status && '404' !== $status;
	}

	/**
	 * Determine whether a contact is subscribed to the given list.
	 *
	 * This method should be used for determining if a customer is subscribed to marketing emails. For transactional
	 * emails,
	 *
	 * @param string $email   The email address.
	 * @param string $list_id The Mailchimp list ID.
	 *
	 * @return bool
	 */
	public function is_subscribed_to_list( $email, $list_id ) {
		$status = $this->get_subscriber_status_for_list( $email, $list_id );

		return 'subscribed' === $status;
	}

	/**
	 * Update interest groups for a contact.
	 *
	 * $interests should be an array with the interest ID as the key and
	 * true or false as the value, depending on whether adding or removing the group
	 *
	 * @param string $email     The contact's email address.
	 * @param string $list_id   The list ID.
	 * @param array  $interests Array of interests.
	 *
	 * @return Remote_Request
	 */
	public function update_contact_interest_groups( $email, $list_id, $interests ) {
		$subscriber_hash = md5( $email );

		$args = [
			'email_address' => $email,
			'status_if_new' => 'subscribed',
			'interests'     => $interests,
		];

		return $this->request( 'PUT', "/lists/$list_id/members/$subscriber_hash", $args );
	}

	/**
	 * Clear the cache for lists.
	 */
	public function clear_cache_data() {
		Cache::delete_transient( 'mailchimp_lists' );
	}

	/**
	 * Get the subscriber information for a particular list.
	 *
	 * @param string $email   The email address to check.
	 * @param string $list_id The list ID.
	 *
	 * @return string
	 */
	private function get_subscriber_status_for_list( $email, $list_id ) {
		// Check the cache first.
		$cache_key = "mailchimp_status_for_list_{$list_id}";
		if ( Temporary_Data::exists( $cache_key, $email ) ) {
			return Temporary_Data::get( $cache_key, $email );
		}

		$email_hash = md5( $email );

		// will return 404 if subscriber doesn't exists, so don't log errors for this request
		$this->log_errors = false;
		$request          = $this->request( 'GET', "/lists/{$list_id}/members/{$email_hash}", [] );
		$this->log_errors = true;

		// Bail and don't cache the HTTP error.
		if ( $request->is_http_error() ) {
			return '';
		}

		$body   = $request->get_body();
		$status = isset( $body['status'] ) ? (string) $body['status'] : '';

		Temporary_Data::set( $cache_key, $email, $status );

		return $status;
	}

	/**
	 * Get the tags for a particular member.
	 *
	 * @since 4.8.0
	 *
	 * @param string $email   The member's email address.
	 * @param string $list_id The list ID.
	 *
	 * @return array
	 */
	public function get_member_tags( $email, $list_id ) {
		$return  = [];
		$hash    = md5( $email );
		$request = $this->request(
			'GET',
			"/lists/{$list_id}/members/{$hash}/tags",
			[
				'count' => 100,
			]
		);

		if ( ! $request->is_successful() ) {
			return $return;
		}

		$body = $request->get_body();
		$tags = isset( $body['tags'] ) ? (array) $body['tags'] : [];
		foreach ( $tags as $tag ) {
			$return[ strtolower( $tag['name'] ) ] = $tag['name'];
		}

		return $return;
	}

	/**
	 * Update the tags for a particular member.
	 *
	 * The array of $tags should have the tag name as an index, and either true or false
	 * as a value. Tags with "true" as a value will be added to the member, and tags
	 * with "false" as a value will be removed from the member.
	 *
	 * Example: Add tag "top buyer" and remove "no sales":
	 *
	 * [
	 *     'top buyer' => true,
	 *     'no sales'  => false,
	 * ]
	 *
	 * @since 4.8.0
	 *
	 * @param string $email   The member's email address.
	 * @param string $list_id The list ID.
	 * @param array  $tags    Array of tags to update.
	 *
	 * @return Remote_Request The request
	 */
	public function update_member_tags( $email, $list_id, $tags ) {
		$hash = md5( $email );
		$args = [];
		foreach ( $tags as $tag => $active ) {
			$args[] = [
				'name'   => (string) $tag, // Must be a string.
				'status' => $active ? 'active' : 'inactive',
			];
		}

		return $this->request(
			'POST',
			"/lists/{$list_id}/members/{$hash}/tags",
			[
				'tags' => $args,
			]
		);
	}
}
