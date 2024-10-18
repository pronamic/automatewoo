<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Integration_ActiveCampaign
 *
 * Updated in AutomateWoo version 6.1.0 to work with the ActiveCampaign
 * REST API instead of relying on the now abandoned SDK.
 *
 * @since 6.0.19
 */
class Integration_ActiveCampaign extends Integration {

	/** @var string */
	public $integration_id = 'activecampaign';

	/** @var string */
	private $api_key;

	/** @var string */
	private $api_url;

	/** @var int */
	public $request_count = 1;

	/** @var array */
	private $active_tags = array();

	/**
	 * Constructor.
	 *
	 * @param string $api_url
	 * @param string $api_key
	 */
	public function __construct( $api_url, $api_key ) {
		$this->api_url = trailingslashit( $api_url ) . 'api/3/';
		$this->api_key = $api_key;
	}

	/**
	 * Test the current API key to confirm that AutomateWoo can communicate with the ActiveCampaign API
	 *
	 * @return bool
	 */
	public function test_integration(): bool {
		return 200 === $this->request( 'users/me' )->get_response_code();
	}

	/**
	 * Check if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) Options::activecampaign_enabled();
	}

	/**
	 * Create or update a contact on ActiveCampaign
	 *
	 * @see https://developers.activecampaign.com/reference/create-a-new-contact
	 *
	 * @param string $email Email address for the contact
	 * @param string $first_name First name for the contact
	 * @param string $last_name Last name for the contact
	 * @param string $phone Phone number for the contact
	 * @param string $company Company for the contact
	 *
	 * @return bool|array An array containing contact details or false if unsuccessful
	 */
	public function sync_contact( $email, $first_name = false, $last_name = false, $phone = false, $company = false ) {
		$data = array(
			'contact' => array(
				'email' => $email,
			),
		);

		if ( $first_name ) {
			$data['contact']['firstName'] = $first_name;
		}

		if ( $last_name ) {
			$data['contact']['lastName'] = $last_name;
		}

		if ( $phone ) {
			$data['contact']['phone'] = $phone;
		}

		$response = $this->request( 'contact/sync', $data, 'POST' )->get_body();

		if ( ! isset( $response['contact']['id'] ) ) {
			return false;
		}

		$contact = $response['contact'];

		if ( $company && ! $this->sync_company( $contact['id'], $contact['orgid'], $company ) ) {
			return false;
		}

		return $contact;
	}

	/**
	 * Sync a company record for a contact
	 *
	 * @see https://developers.activecampaign.com/reference/create-an-account-1
	 *
	 * @param string $contact_id ID of the contact to sync company data for
	 * @param string $current_company_id ID of the company currently associated with the contact ("0" = none)
	 * @param string $company_name Name of the company to sync
	 *
	 * @return bool
	 */
	public function sync_company( $contact_id, $current_company_id, $company_name ): bool {
		$account = $this->get_account( $company_name, true );

		if ( ! isset( $account['id'] ) ) {
			return false;
		}

		if ( $current_company_id !== $account['id'] ) {
			// If an association already exists then it will need to be deleted before creating a new one.
			if ( $current_company_id !== '0' ) {
				if ( ! $this->delete_account_association( $contact_id ) ) {
					$this->log( esc_html__( 'There was an error when attempting to delete an association', 'automatewoo' ) );

					return false;
				}
			}

			$association = array(
				'accountContact' => array(
					'contact' => $contact_id,
					'account' => $account['id'],
				),
			);

			$link_account = $this->request( 'accountContacts', $association, 'POST' )->get_body();

			if ( ! isset( $link_account['accounts'][0]['id'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retreive an account based on the name
	 *
	 * @see https://developers.activecampaign.com/reference/list-all-accounts
	 *
	 * @param string $account_name Name of the account to search for
	 * @param bool   $create_missing_account If true then the account will be created if not found
	 *
	 * @return bool|array
	 */
	public function get_account( $account_name, $create_missing_account = false ) {
		$response = $this->request( 'accounts', array( 'search' => $account_name ) )->get_body();

		if ( isset( $response['accounts'][0] ) ) {
			return $response['accounts'][0];
		}

		if ( $create_missing_account ) {
			$data = array(
				'account' => array(
					'name' => $account_name,
				),
			);

			$response = $this->request( 'accounts', $data, 'POST' )->get_body();

			if ( isset( $response['account']['id'] ) ) {
				return $response['account'];
			}
		}

		return false;
	}

	/**
	 * Delete a Contact/ Account association based on the Contact ID
	 *
	 * @see https://developers.activecampaign.com/reference/delete-an-association-1
	 *
	 * @param string $contact_id ID of the contact to delete an account association for
	 *
	 * @return bool
	 */
	public function delete_account_association( $contact_id ): bool {
		$data = array(
			'filters[contact]' => $contact_id,
		);

		$response = $this->request( 'accountContacts', $data )->get_body();

		if ( isset( $response['accountContacts'][0]['id'] ) ) {
			$response = $this->request( 'accountContacts/' . $response['accountContacts'][0]['id'], array(), 'DELETE' );

			if ( $response->is_successful() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all lists from the ActiveCampaign API
	 *
	 * @see https://developers.activecampaign.com/reference/retrieve-all-lists
	 *
	 * @return array
	 */
	public function get_lists(): array {
		$cache = Cache::get_transient( 'ac_lists' );
		if ( $cache ) {
			return $cache;
		}

		$response = $this->request( 'lists' )->get_body();

		if ( ! isset( $response['lists'] ) ) {
			$this->log( __( 'Unexpected response when trying to retrieve all lists', 'automatewoo' ) );

			return [];
		}

		$clean_lists = [];
		foreach ( $response['lists'] as $list ) {
			$clean_lists[ $list['id'] ] = $list['name'];
		}

		Cache::set_transient( 'ac_lists', $clean_lists, 0.15 );

		return $clean_lists;
	}

	/**
	 * Add a contact to a list
	 *
	 * @see https://developers.activecampaign.com/reference/update-list-status-for-contact
	 *
	 * @param string $contact ID of the contact to add to a list
	 * @param string $list ID of the list to add the contact to
	 *
	 * @return bool
	 */
	public function add_contact_to_list( $contact, $list ) {
		$data = array(
			'contactList' => array(
				'list'    => $list,
				'contact' => $contact,
				'status'  => '1',
			),
		);

		return $this->request( 'contactLists', $data, 'POST' )->is_successful();
	}

	/**
	 * Check is the contact exists in ActiveCampaign.
	 * Result from API is cached for 5 minutes.
	 *
	 * @see https://developers.activecampaign.com/reference/list-all-contacts
	 *
	 * @param string $email The email address to look up
	 *
	 * @return bool|array False if contact is not found or the contact data if successful
	 */
	public function is_contact( $email ) {
		$cache_key = 'aw_ac_is_contact_' . md5( $email );

		$cache = get_transient( $cache_key );
		if ( $cache ) {
			return $cache === 'yes';
		}

		$contact = $this->get_contact( $email );

		set_transient( $cache_key, false !== $contact ? 'yes' : 'no', MINUTE_IN_SECONDS * 5 );

		return $contact;
	}

	/**
	 * Retreive data for an existing contact
	 *
	 * @param string $email The email address to retreive contact data for
	 *
	 * @return bool|array
	 */
	public function get_contact( $email ) {
		if ( ! is_email( $email ) ) {
			$this->log( __( 'Invalid email was supplied when checking if contact exists in ActiveCampaign', 'automatewoo' ) );

			return false;
		}

		$response = $this->request( 'contacts', array( 'email' => $email ) )->get_body();

		return isset( $response['contacts'][0] ) ? $response['contacts'][0] : false;
	}

	/**
	 * Delete the transient for a given email address
	 *
	 * @param string $email The email address to clear the contact transient for
	 *
	 * @return void
	 */
	public function clear_contact_transients( $email ) {
		delete_transient( 'aw_ac_is_contact_' . md5( $email ) );
	}


	/**
	 * Retreive all custom fields
	 *
	 * @see https://developers.activecampaign.com/reference/retrieve-fields
	 *
	 * @return array
	 */
	public function get_contact_custom_fields(): array {
		$cache = Cache::get_transient( 'ac_contact_fields' );
		if ( $cache ) {
			return $cache;
		}

		$response = $this->request( 'fields' )->get_body();

		if ( ! isset( $response['fields'] ) ) {
			$this->log( __( 'Unexpected response when trying to retrieve all custom fields', 'automatewoo' ) );

			return [];
		}

		$fields = [];

		foreach ( $response['fields'] as $item ) {
			$fields[ $item['id'] ] = $item;
		}

		Cache::set_transient( 'ac_contact_fields', $fields, 0.15 );

		return $fields;
	}

	/**
	 * Get a tag ID by its name
	 *
	 * @param string $tag Name of the tag to get the ID for
	 * @param bool   $create_missing_tag If true then the tag will be created if not found
	 * @param bool   $retry_on_error Whether to retry if an error occurs when creating a tag. It will clear the cache and try again.
	 *
	 * @return string|bool
	 */
	public function get_tag_id( $tag, $create_missing_tag = false, $retry_on_error = true ) {
		$transient = 'ac_tags';

		if ( empty( $this->active_tags ) ) {
			$cache = Cache::get_transient( $transient );
			if ( $cache ) {
				$this->active_tags = $cache;
			} else {
				$response          = $this->paginated_request( 'tags' );
				$this->active_tags = $response['tags'];

				Cache::set_transient( $transient, $this->active_tags, 0.15 );
			}
		}

		$match = array_search( strtolower( $tag ), array_map( 'strtolower', array_column( $this->active_tags, 'tag' ) ), true );

		if ( false !== $match ) {
			return $this->active_tags[ $match ]['id'];
		}

		if ( $create_missing_tag ) {
			// Tag not found so we need to create it.
			$data = array(
				'tag' => array(
					'tag'     => $tag,
					'tagType' => 'contact',
				),
			);

			$response = $this->request( 'tags', $data, 'POST' )->get_body();

			if ( ! isset( $response['tag']['id'] ) ) {

				if ( isset( $response['error'] ) && strpos( $response['error'], 'Duplicate entry' ) !== false && $retry_on_error ) {
					// Somehow the tag was created by another process in the meantime or externally so we clear the cache and try again.
					Cache::delete_transient( $transient );
					$this->active_tags = [];

					return $this->get_tag_id( $tag, $create_missing_tag, false );
				}
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				$this->log( 'Unexpected response when attempting to create a tag. Response: ' . print_r( $response, true ) . 'Tags: ' . print_r( $this->active_tags, true ) );

				// phpcs:enable
				return false;
			}

			$this->active_tags[] = $response['tag'];
			// Refresh cache to include new tag
			Cache::set_transient( $transient, $this->active_tags, 0.15 );

			return $response['tag']['id'];
		}

		return false;
	}

	/**
	 * Add tags to an existing contact
	 *
	 * @param string $contact ID of the contact to add tags to
	 * @param array  $tags An array of tags to add
	 *
	 * @return bool
	 */
	public function add_tags( $contact, $tags ) {
		if ( empty( $tags ) ) {
			return false;
		}

		$successful = true;

		foreach ( $tags as $tag ) {
			$tag_id = $this->get_tag_id( $tag, true );

			if ( $tag_id ) {
				$data = [
					'contactTag' => array(
						'contact' => $contact,
						'tag'     => $tag_id,
					),
				];

				if ( ! $this->request( 'contactTags', $data, 'POST' )->is_successful() ) {
					$successful = false;
				}
			}
		}

		return $successful;
	}

	/**
	 * Send a request to the ActiveCampaign API
	 *
	 * @see https://developers.activecampaign.com/reference/overview
	 *
	 * @param string $path Path to the API endpoint
	 * @param array  $data Data to include with the request
	 * @param string $method The HTTP method for this request
	 *
	 * @return \ActiveCampaign|false
	 */
	public function request( $path, $data = [], $method = 'GET' ) {
		$url          = $this->api_url . $path;
		$request_args = [
			'timeout'   => 10,
			'method'    => $method,
			'sslverify' => false,
			'headers'   => [
				'Api-Token' => $this->api_key,
			],
		];

		++$this->request_count;

		// avoid overloading the api
		if ( $this->request_count % 4 === 0 ) {
			sleep( 2 );
		}

		switch ( $method ) {
			case 'GET':
				// SEMGREP WARNING EXPLANATION
				// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
				$url = esc_url_raw( add_query_arg( array_map( 'urlencode', $data ), $url ) );
				break;
			case 'DELETE':
				$request_args['method'] = 'DELETE';
				break;
			default:
				$request_args['body'] = wp_json_encode( $data );
				break;
		}

		$response = new Remote_Request( $url, $request_args );
		$this->maybe_log_request_errors( $response );

		return $response;
	}

	/**
	 * Send a request to the ActiveCampaign API with pagination
	 *
	 * @see https://developers.activecampaign.com/reference/pagination
	 *
	 * @param string $path Path to the API endpoint
	 * @param array  $data Data to include with the request
	 * @param string $method The HTTP method for this request
	 * @param int    $page The page to fetch.
	 * @param array  $results Accumulated results for all the pages.
	 *
	 * @return array
	 */
	public function paginated_request( $path, $data = [], $method = 'GET', $page = 0, $results = [] ) {

		// Set offset based on the $page and the api_pagination_limit
		$data['limit']  = $this->get_api_pagination_limit();
		$data['offset'] = $page * $this->get_api_pagination_limit();

		// Get elements for the current page and add them to the results.
		$request = $this->request( $path, $data, $method )->get_body();
		$results = array_merge( $results, $request[ $path ] );

		// If there are more elements left. Fetch again with the next page.
		if ( $request['meta']['total'] > count( $results ) ) {
			return $this->paginated_request( $path, $data, $method, ++$page, $results );
		}

		return [
			$path  => $results,
			'meta' => [ 'total' => $request['meta']['total'] ],
		];
	}

	/**
	 * @return void
	 * @deprecated
	 */
	protected function get_sdk() {
		wc_deprecated_function( __METHOD__, '6.1.0', 'request' );
	}


	/**
	 * Clear all cached data
	 *
	 * @return void
	 */
	public function clear_cache_data() {
		Cache::delete_transient( 'ac_lists' );
		Cache::delete_transient( 'ac_tags' );
		Cache::delete_transient( 'ac_contact_fields' );
	}


	/**
	 * Get the pagination limit.
	 *
	 * @return int
	 */
	public function get_api_pagination_limit() {

		/**
		 * Filter the pagination limit for Active Campaign REST API.
		 *
		 * @since 6.0.31
		 * @filter automatewoo/integrations/active_campaign/pagination
		 */
		return apply_filters( 'automatewoo/integrations/active_campaign/pagination', 100 );
	}
}
