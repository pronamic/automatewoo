<?php

namespace AutomateWoo\Async_Events;

/**
 * Trait UniqueEventsForRequestHelper
 *
 * Helper to record and check whether async event has been created for the given item in the current request.
 *
 * @since 5.2.0
 */
trait UniqueEventsForRequestHelper {

	/**
	 * Array of IDs of items that that were created in the current request.
	 *
	 * @since 5.2.0
	 *
	 * @var int[]
	 */
	protected $created_event_item_ids = [];

	/**
	 * Check if a given item ID is unique for this event during the current request.
	 *
	 * @param int $item_id
	 *
	 * @return bool
	 */
	protected function check_item_is_unique_for_event( int $item_id ): bool {
		return in_array( $item_id, $this->created_event_item_ids, true );
	}

	/**
	 * Record that an event was added for the item ID.
	 *
	 * @param int $item_id
	 */
	protected function record_event_added_for_item( int $item_id ) {
		$this->created_event_item_ids[] = $item_id;
	}
}
