<?php

namespace AutomateWoo\RuleQuickFilters\Queries;

use AutomateWoo\HPOS_Helper;
use WC_Subscription;

defined( 'ABSPATH' ) || exit;

/**
 * Class SubscriptionQuery.
 *
 * @since   5.0.0
 * @package AutomateWoo\RuleQuickFilters\Queries
 */
class SubscriptionQuery extends AbstractQuery {

	/**
	 * Get data type for quick filtering.
	 *
	 * @return string
	 */
	public function get_data_type() {
		return 'subscription';
	}

	/**
	 * Get filter result object from ID.
	 *
	 * @param int $id
	 *
	 * @return WC_Subscription|false
	 */
	public function get_result_object( $id ) {
		return wcs_get_subscription( $id );
	}

	/**
	 * Get the datastore type to use for queries.
	 *
	 * @return DatastoreTypeInterface
	 *
	 * @since 5.5.23
	 */
	protected function get_datastore_type() {
		if ( HPOS_Helper::is_HPOS_enabled() ) {
			return new SubscriptionHighPerformanceDatastoreType();
		}

		return new SubscriptionPostDatastoreType();
	}
}
