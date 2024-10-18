<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Workflow $workflow
 */

global $post;
?>
<div class="submitbox" id="submitpost">

	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
	<div style="display:none;">
		<?php submit_button( __( 'Save', 'automatewoo' ), '', 'save' ); ?>
	</div>

	<table class="automatewoo-table">
		<tr id="automatewoo-workflow-type-field-row" class="automatewoo-table__row">
			<td class="automatewoo-table__col">
				<div class="automatewoo-input-group">
					<label class="automatewoo-input-group__addon automatewoo-input-group__addon--pad-right automatewoo-label--weight-normal">
						<?php esc_html_e( 'Type:', 'automatewoo' ); ?>
					</label>
					<div class="automatewoo-input-group__input">
						<?php
						( new Fields\Select( false ) )
							->add_classes( 'automatewoo-workflow-type-field' )
							->set_name( 'type' )
							->set_name_base( 'aw_workflow_data' )
							->set_options( Workflows::get_types() )
							->render( $workflow ? $workflow->get_type() : 'automatic' );
						?>
					</div>
				</div>
			</td>
		</tr>
		<tr id="automatewoo-workflow-status-field-row" class="automatewoo-table__row">
			<td class="automatewoo-table__col">
				<div class="automatewoo-input-group">
					<label class="automatewoo-input-group__addon automatewoo-input-group__addon--pad-right automatewoo-label--weight-normal">
						<?php esc_html_e( 'Status:', 'automatewoo' ); ?>
					</label>

					<div class="automatewoo-input-group__input">
						<?php
						if ( $workflow ) {
							$status = $workflow->is_active() ? 'active' : 'disabled';
						} else {
							$status = 'active';
						}

						( new Fields\Select( false ) )
							->set_name( 'workflow_status' )
							->set_options(
								[
									'active'   => __( 'Active', 'automatewoo' ),
									'disabled' => __( 'Disabled', 'automatewoo' ),
								]
							)
							->render( $status );
						?>
					</div>
				</div>
			</td>
		</tr>

		<?php if ( $post->post_status !== 'auto-draft' ) : ?>
			<tr class="automatewoo-table__row">
				<td class="automatewoo-table__col">
					<div>
						<?php
						printf(
							'%1$s <b>%2$s</b>',
							esc_html__( 'Created:', 'automatewoo' ),
							esc_html( Format::datetime( $post->post_date, 0, false ) )
						);
						?>
					</div>
				</td>
			</tr>
		<?php endif; ?>
	</table>

	<div id="major-publishing-actions">
		<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
			<div id="delete-action">
				<?php
				$delete_text = ( ! EMPTY_TRASH_DAYS )
					? __( 'Delete Permanently', 'automatewoo' )
					: __( 'Move to Trash', 'automatewoo' );
				?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">
					<?php echo esc_html( $delete_text ); ?>
				</a>
			</div>
		<?php endif; ?>

		<div id="publishing-action">
			<span class="spinner"></span>
			<input name="original_publish"
				type="hidden"
				id="original_publish"
				value="<?php esc_attr_e( 'Save', 'automatewoo' ); ?>" />
			<input name="save"
				type="submit"
				class="button button-primary button-large"
				id="publish"
				value="<?php esc_attr_e( 'Save', 'automatewoo' ); ?>" />

			<?php if ( $workflow ) : ?>
				<a
						id="automatewoo-workflow-run-btn"
						class="button button-primary button-large <?php echo 'manual' === $workflow->get_type() ? '' : 'hidden'; ?>">
					<?php esc_html_e( 'Run', 'automatewoo' ); ?>
				</a>
				<input name="automatewoo_redirect_to_runner" type="hidden" value="0">
			<?php endif; ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
