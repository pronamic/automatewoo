<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Log $log
 */

$notes = $log->get_notes();

$data_layer = $log->get_data_layer( 'object' );

$formatted_data = Admin_Data_Layer_Formatter::format( $data_layer );

?>

	<div class="automatewoo-modal__header">
		<h1><?php /* translators: Log ID. */ printf( esc_html__( 'Log #%s', 'automatewoo' ), esc_html( $log->get_id() ) ); ?></h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">

			<ul>
				<li><strong><?php esc_html_e( 'Workflow', 'automatewoo' ); ?>:</strong> <a href="<?php echo esc_url( get_edit_post_link( $log->get_workflow_id() ) ); ?>"><?php echo wp_kses_post( get_the_title( $log->get_workflow_id() ) ); ?></a></li>
				<li><strong><?php esc_html_e( 'Time', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( Format::datetime( $log->get_date(), 0 ) ); ?></li>

				<?php foreach ( $formatted_data as $item ) : ?>
					<li><strong><?php echo wp_kses_post( $item['title'] ); ?>:</strong> <?php echo wp_kses_post( $item['value'] ); ?></li>
				<?php endforeach; ?>

				<li><strong><?php esc_html_e( 'Tracking enabled', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( Format::bool( $log->is_tracking_enabled() ) ); ?></li>
				<li><strong><?php esc_html_e( 'Conversion tracking enabled', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( Format::bool( $log->is_conversion_tracking_enabled() ) ); ?></li>

				<?php if ( $log->is_tracking_enabled() ) : ?>
					<li><strong><?php esc_html_e( 'Opened', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( $log->has_open_recorded() ? Format::datetime( $log->get_date_opened() ) : __( 'No', 'automatewoo' ) ); ?></li>
					<li><strong><?php esc_html_e( 'Clicked', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( $log->has_click_recorded() ? Format::datetime( $log->get_date_clicked() ) : __( 'No', 'automatewoo' ) ); ?></li>
				<?php endif; ?>

			</ul>



			<?php if ( $notes ) : ?>
				<hr>

				<strong><?php esc_html_e( 'Log notes:', 'automatewoo' ); ?></strong><br>
				<?php foreach ( $notes as $note ) : ?>
					<p><?php echo wp_kses_post( $note ); ?></p>
				<?php endforeach; ?>

			<?php endif; ?>

			<hr>

			<?php if ( $log->is_anonymized() ) : ?>
				<strong><?php esc_html_e( 'Log data has been anonymized.', 'automatewoo' ); ?></strong>
			<?php else : ?>
				<?php
				$rerun_url = add_query_arg(
					[
						'action' => 'rerun',
						'log_id' => $log->get_id(),
					],
					Admin::page_url( 'logs' )
				);
				?>
				<a href="<?php echo esc_url( wp_nonce_url( $rerun_url, 'rerun_log' ) ); ?>" class="button"><?php esc_html_e( 'Re-run workflow (skips validation)', 'automatewoo' ); ?></a>
			<?php endif; ?>

		</div>
	</div>
