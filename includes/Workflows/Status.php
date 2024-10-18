<?php

namespace AutomateWoo\Workflows;

use AutomateWoo\Exceptions\InvalidStatus;

/**
 * Class Status
 *
 * @since 5.1.0
 */
final class Status {

	const ACTIVE   = 'active';
	const DISABLED = 'disabled';

	const POST_ACTIVE   = 'publish';
	const POST_DISABLED = 'aw-disabled';

	/**
	 * The equivalent post status.
	 *
	 * @var string
	 */
	private $post_status;

	/**
	 * A Workflow status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Status constructor.
	 *
	 * @param string $status The workflow status.
	 *
	 * @throws InvalidStatus When the status is not a known workflow status.
	 */
	public function __construct( string $status ) {
		$this->validate_status( $status );
		$this->status      = $status;
		$this->post_status = $this->get_available_statuses()[ $status ];
	}

	/**
	 * Get the post status.
	 *
	 * @return string
	 */
	public function get_post_status(): string {
		return $this->post_status;
	}

	/**
	 * Get the workflow status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Change the workflow status.
	 *
	 * Will return a new object.
	 *
	 * @param string $new_status The new workflow status.
	 *
	 * @return Status
	 */
	public function change_status_to( string $new_status ): Status {
		return new self( $new_status );
	}

	/**
	 * Validate a workflow status.
	 *
	 * @param string $status The status to validate.
	 *
	 * @throws InvalidStatus When the status is not a known workflow status.
	 */
	private function validate_status( string $status ) {
		$available_statuses = $this->get_available_statuses();
		if ( ! array_key_exists( $status, $available_statuses ) ) {
			throw InvalidStatus::unknown_status( esc_html( $status ) );
		}

		if ( ! is_string( $available_statuses[ $status ] ) || empty( $available_statuses[ $status ] ) ) {
			throw InvalidStatus::no_post_staus( esc_html( $status ) );
		}
	}

	/**
	 * Get the available workflow statuses and their post status mapping.
	 *
	 * @return array
	 */
	private function get_available_statuses(): array {
		$additional_statuses = apply_filters( 'automatewoo/workflow/statuses', [] );

		return array_merge(
			$additional_statuses,
			[
				self::ACTIVE   => self::POST_ACTIVE,
				self::DISABLED => self::POST_DISABLED,
			]
		);
	}
}
