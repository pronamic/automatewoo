<?php

namespace AutomateWoo\Orders;

/**
 * Class StatusTransition.
 *
 * @since 5.2.0
 */
class StatusTransition {

	/**
	 * @var string
	 */
	protected $old_status;

	/**
	 * @var string
	 */
	protected $new_status;

	/**
	 * StatusTransition constructor.
	 *
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function __construct( string $old_status, string $new_status ) {
		$this->old_status = $old_status;
		$this->new_status = $new_status;
	}

	/**
	 * @return string
	 */
	public function get_old_status(): string {
		return $this->old_status;
	}

	/**
	 * @return string
	 */
	public function get_new_status(): string {
		return $this->new_status;
	}

	/**
	 * Is the order transitioning from an unpaid to paid status?
	 *
	 * @return bool
	 */
	public function is_becoming_paid(): bool {
		$statuses = wc_get_is_paid_statuses();
		return in_array( $this->new_status, $statuses, true ) && ! in_array( $this->old_status, $statuses, true );
	}

	/**
	 * Is the order transitioning from a paid to unpaid status?
	 *
	 * @return bool
	 */
	public function is_becoming_unpaid(): bool {
		$statuses = wc_get_is_paid_statuses();
		return ! in_array( $this->new_status, $statuses, true ) && in_array( $this->old_status, $statuses, true );
	}

	/**
	 * Is the order transitioning from an uncounted to counted status?
	 *
	 * @return bool
	 */
	public function is_becoming_counted(): bool {
		$statuses = aw_get_counted_order_statuses( false );
		return in_array( $this->new_status, $statuses, true ) && ! in_array( $this->old_status, $statuses, true );
	}

	/**
	 * Is the order transitioning from a counted to uncounted status?
	 *
	 * @return bool
	 */
	public function is_becoming_uncounted(): bool {
		$statuses = aw_get_counted_order_statuses( false );
		return ! in_array( $this->new_status, $statuses, true ) && in_array( $this->old_status, $statuses, true );
	}
}
