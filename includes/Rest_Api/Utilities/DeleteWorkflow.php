<?php

namespace AutomateWoo\Rest_Api\Utilities;

use AutomateWoo\Workflow;


/**
 * Trait DeleteWorkflow
 *
 * @since   6.0.10
 * @package AutomateWoo\Rest_Api\Utilities
 */
trait DeleteWorkflow {

	use GetWorkflow;

	/**
	 * Delete a workflow by ID (will attempt to trash if possible).
	 *
	 * @param int  $id    The workflow ID.
	 * @param bool $force Force the workflow to be permanently deleted.
	 *
	 * @return Workflow The workflow object.
	 * @throws RestException When the workflow does not exist.
	 */
	protected function delete_workflow( int $id, bool $force = false ): Workflow {
		$workflow = $this->get_workflow( $id );

		$supports_trash = EMPTY_TRASH_DAYS > 0;

		/**
		 * Filter whether an item is trashable.
		 *
		 * Return false to disable trash support for the item.
		 *
		 * @param boolean $supports_trash Whether the item type supports trashing.
		 * @param WP_Post $post           The Post object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "woocommerce_rest_{$workflow->post->post_type}_trashable", $supports_trash, $workflow->post );

		if ( ! $supports_trash ) {
			throw new RestException(
				'rest_trash_not_supported',
				esc_html__( 'Trash is not supported.', 'automatewoo' ),
				501
			);
		}

		if ( ! $force && 'trash' === $workflow->get_status() ) {
			throw new RestException(
				'rest_already_trashed',
				esc_html(
					/* translators: Workflow ID. */
					sprintf( __( 'Workflow %d has already been trashed.', 'automatewoo' ), $id )
				),
				410
			);
		}

		if ( $force ) {
			$result = wp_delete_post( $id, $force );
		} else {
			$result = wp_trash_post( $id );
		}

		if ( ! $result ) {
			throw new RestException(
				'rest_delete_error',
				esc_html(
					/* translators: Workflow ID. */
					sprintf( __( 'Unable to delete workflow %d.', 'automatewoo' ), $id )
				),
				404
			);
		}

		// Update status after delete.
		$workflow->post->post_status = $force ? 'deleted' : 'trash';

		return $workflow;
	}
}
