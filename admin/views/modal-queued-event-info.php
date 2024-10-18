<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Queued_Event $event
 */

$data_layer     = $event->get_data_layer();
$formatted_data = Admin_Data_Layer_Formatter::format( $data_layer );

?>

	<div class="automatewoo-modal__header">
		<h1><?php /* translators: Event ID. */ printf( esc_html__( 'Queued event #%s', 'automatewoo' ), esc_html( $event->get_id() ) ); ?></h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">

			<ul>
				<?php if ( $event->is_failed() ) : ?>
					<li><strong><?php esc_html_e( 'Failed', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( $event->get_failure_message() ); ?></li>
				<?php endif ?>

				<li><strong><?php esc_html_e( 'Workflow', 'automatewoo' ); ?>:</strong> <a href="<?php echo esc_url( get_edit_post_link( $event->get_workflow_id() ) ); ?>"><?php echo wp_kses_post( get_the_title( $event->get_workflow_id() ) ); ?></a></li>
				<li><strong><?php esc_html_e( 'Due to run', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( Format::datetime( $event->get_date_due(), 0 ) ); ?></li>
				<li><strong><?php esc_html_e( 'Created', 'automatewoo' ); ?>:</strong> <?php echo wp_kses_post( Format::datetime( $event->get_date_created(), 0 ) ); ?></li>

				<?php foreach ( $formatted_data as $item ) : ?>
					<li><strong><?php echo wp_kses_post( $item['title'] ); ?>:</strong> <?php echo wp_kses_post( $item['value'] ); ?></li>
				<?php endforeach; ?>

			</ul>

		</div>
	</div>
