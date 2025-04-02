<?php

namespace AutomateWoo;

/**
 * Class to duplicate a workflow in WP Admin.
 *
 * @since   6.1.9
 * @package AutomateWoo
 */
class Workflow_Duplicate {

	/**
	 * Workflow_Duplicate constructor.
	 */
	public function __construct() {
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_link' ], 10, 2 );
		add_action( 'admin_action_aw_duplicate_workflow', [ $this, 'duplicate_workflow' ] );
	}

	/**
	 * Add duplicate link to workflow actions.
	 *
	 * @param array   $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function add_duplicate_link( $actions, $post ) {
		if ( 'aw_workflow' === $post->post_type ) {
			$actions['duplicate'] = sprintf(
				'<a href="%s">%s</a>',
				wp_nonce_url(
					admin_url( 'admin.php?action=aw_duplicate_workflow&post=' . $post->ID ),
					'duplicate_workflow_' . $post->ID
				),
				esc_html__( 'Duplicate', 'automatewoo' )
			);
		}
		return $actions;
	}

	/**
	 * Handle the duplication process.
	 */
	public function duplicate_workflow() {
		if ( ! isset( $_GET['post'] ) || ! check_admin_referer( 'duplicate_workflow_' . absint( $_GET['post'] ) ) ) {
			wp_die( esc_html__( 'Invalid request.', 'automatewoo' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate workflows.', 'automatewoo' ) );
		}

		$post_id  = absint( $_GET['post'] );
		$workflow = get_post( $post_id );
		if ( ! $workflow ) {
			wp_die(
				sprintf(
					/* translators: %s: workflow ID */
					esc_html__( 'Workflow creation failed, could not find original workflow: %d', 'automatewoo' ),
					(int) $post_id
				)
			);
		}

		$new_workflow_id = wp_insert_post(
			[
				/* translators: %s: original workflow title */
				'post_title'  => sprintf( esc_html__( 'Copy of %s', 'automatewoo' ), $workflow->post_title ),
				'post_status' => 'draft',
				'post_type'   => 'aw_workflow',
				'menu_order'  => $workflow->menu_order,
			],
			true
		);

		if ( is_wp_error( $new_workflow_id ) ) {
			wp_die(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Workflow creation failed: %s', 'automatewoo' ),
					esc_html( $new_workflow_id->get_error_message() )
				)
			);
		}

		$meta = get_post_meta( $post_id );
		if ( $meta ) {
			foreach ( $meta as $key => $values ) {
				// Skip private meta keys.
				if ( '_' === $key[0] ) {
					continue;
				}

				foreach ( $values as $value ) {
					add_post_meta( $new_workflow_id, $key, maybe_unserialize( $value ) );
				}
			}
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_workflow_id ) );
		exit;
	}
}
