<?php

namespace AutomateWoo\Usage_Tracking;

use AutomateWoo\Clean;
use AutomateWoo\HPOS_Helper;
use AutomateWoo\Log_Query;
use AutomateWoo\Workflow_Query;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use wpdb;

/**
 * Tracker class.
 *
 * @package AutomateWoo\Usage_Tracking
 * @since   4.9.0
 */
class Tracker {

	/**
	 * The WordPress Database instance.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Tracker constructor.
	 *
	 * @param wpdb $wpdb The WordPress Database object.
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Hook our tracker data into the WC tracker data.
	 */
	public function init() {
		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) {
				return $this->add_data( $data );
			}
		);
	}

	/**
	 * Add our AutomateWoo data to the WC Tracker.
	 *
	 * @param array $data The existing array of tracker data.
	 *
	 * @return array
	 */
	private function add_data( $data = [] ) {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = [];
		}

		$conversion_data = $this->get_conversion_data();

		$data['extensions']['automatewoo'] = [
			'settings'                   => $this->get_settings(),
			'active_actions'             => $this->get_active_actions(),
			'active_triggers'            => $this->get_active_triggers(),
			'active_automatic_workflows' => $this->get_active_automatic_workflows(),
			'manual_workflows'           => $this->get_manual_workflows(),
			'conversion_count'           => $conversion_data['count'],
			'conversion_value'           => $conversion_data['total'],
			'log_counts'                 => $this->get_log_count_data(),
		];

		return $data;
	}

	/**
	 * Get settings data for AutomateWoo.
	 *
	 * @return array
	 */
	private function get_settings() {
		$options = AW()->options();

		return [
			'database_version'                         => $options::database_version(),
			'file_version'                             => $options::file_version(),
			'optin_enabled'                            => $options::optin_enabled(),
			'session_tracking_enabled'                 => $options::session_tracking_enabled(),
			'session_tracking_requires_cookie_consent' => $options::session_tracking_requires_cookie_consent(),
			'session_tracking_consent_cookie_name'     => $options::session_tracking_consent_cookie_name(),
			'presubmit_capture_enabled'                => $options::presubmit_capture_enabled(),
			'abandoned_cart_enabled'                   => $options::abandoned_cart_enabled(),
			'checkout_optin_enabled'                   => $options::checkout_optin_enabled(),
			'account_optin_enabled'                    => $options::account_optin_enabled(),
			'communication_account_tab_enabled'        => $options::communication_account_tab_enabled(),
			'mailchimp_integration_enabled'            => $options::mailchimp_enabled(),
			'campaign_monitor_integration_enabled'     => $options->campaign_monitor_enabled,
			'active_campaign_integration_enabled'      => $options->active_campaign_integration_enabled,
			'twilio_integration_enabled'               => $options->twilio_integration_enabled,
			'bitly_shorten_sms_links'                  => $options->bitly_shorten_sms_links,
		];
	}

	/**
	 * Get the action names that are actively in use for the store.
	 *
	 * @return array
	 */
	private function get_active_actions() {
		$sql = "SELECT meta.meta_value FROM {$this->wpdb->posts} AS posts
			LEFT JOIN {$this->wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			WHERE post_type = 'aw_workflow'
			AND post_status = 'publish'
			AND meta.meta_key = 'actions'";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = Clean::recursive( $this->wpdb->get_col( $sql ) );
		// phpcs:enable

		// Process the results into action names.
		$actions = [];
		foreach ( $results as $result ) {
			$data = maybe_unserialize( $result );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$counts = array_count_values( wp_list_pluck( $data, 'action_name' ) );
			foreach ( $counts as $action_name => $count ) {
				if ( ! array_key_exists( $action_name, $actions ) ) {
					$actions[ $action_name ] = $count;
				} else {
					$actions[ $action_name ] += $count;
				}
			}
		}

		return $actions;
	}

	/**
	 * Get the number of active automatic workflows.
	 *
	 * @return int
	 */
	private function get_active_automatic_workflows() {
		$query = $this->get_workflow_count_query();
		$query->set_status( 'active' );
		$query->set_type( 'automatic' );
		$query->get_results();

		return $query->get_found_rows();
	}

	/**
	 * Get the number of manual workflows.
	 *
	 * @return int
	 */
	private function get_manual_workflows() {
		$query = $this->get_workflow_count_query();
		$query->set_type( 'manual' );
		$query->get_results();

		return $query->get_found_rows();
	}

	/**
	 * Get a workflow query to be used for obtaining a count of workflows.
	 *
	 * @return Workflow_Query
	 */
	private function get_workflow_count_query() {
		$query = new Workflow_Query();
		$query->set_limit( 1 );
		$query->set_no_found_rows( false );
		$query->set_return( 'ids' );

		return $query;
	}

	/**
	 * Get the log count data for the Tracker.
	 *
	 * @return array
	 */
	private function get_log_count_data() {
		$log_query = new Log_Query();
		$count     = $log_query->get_count();

		$conversion_query   = clone $log_query;
		$conversion_enabled = $conversion_query->where( 'conversion_tracking_enabled', true )->get_count();

		$tracking_query   = clone $log_query;
		$tracking_enabled = $tracking_query->where( 'tracking_enabled', true )->get_count();

		return [
			'total'                       => $count,
			'conversion_tracking_enabled' => $conversion_enabled,
			'tracking_enabled'            => $tracking_enabled,
		];
	}

	/**
	 * Get the array of conversion data for the tracker.
	 *
	 * @return array
	 */
	private function get_conversion_data() {
		$statuses = array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() );
		$statuses = array_map( 'esc_sql', $statuses );

		// Build the query to get the results we need.
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$orders_table = OrdersTableDataStore::get_orders_table_name();
			$meta_table   = OrdersTableDataStore::get_meta_table_name();

			$sql = "
				SELECT orders.id AS order_id, orders.total_amount AS order_total
				FROM $orders_table AS orders
				LEFT JOIN $meta_table AS meta ON orders.id = meta.order_id
				WHERE orders.type = 'shop_order'
				AND orders.status IN ( '" . implode( "','", $statuses ) . "' )
				AND meta.meta_key = '_aw_conversion'
				GROUP BY order_id
			";
		} else {
			$sql = "
				SELECT posts.ID AS order_id, meta2.meta_value AS order_total
				FROM {$this->wpdb->posts} AS posts
				LEFT JOIN {$this->wpdb->postmeta} AS meta1 ON posts.ID = meta1.post_id
				LEFT JOIN {$this->wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id
				WHERE posts.post_type = 'shop_order'
				AND posts.post_status IN ( '" . implode( "','", $statuses ) . "' )
				AND meta1.meta_key = '_aw_conversion'
				AND meta2.meta_key = '_order_total'
				GROUP BY order_id
			";
		}

		// Get totals from temporary table
		$sql = "SELECT SUM(order_total) AS total, COUNT(order_id) AS count FROM ({$sql}) AS orders_table";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Get active triggers and the count of how many times they are used.
	 *
	 * @return array
	 */
	private function get_active_triggers() {
		$sql = "SELECT meta.meta_value FROM {$this->wpdb->posts} as posts
			LEFT JOIN {$this->wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			WHERE post_type = 'aw_workflow'
			AND post_status = 'publish'
			AND meta.meta_key = 'trigger_name'";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = Clean::recursive( $this->wpdb->get_col( $sql ) );
		// phpcs:enable

		return array_count_values( $results );
	}
}
