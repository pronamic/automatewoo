<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Logs class.
 */
class Dashboard_Widget_Logs extends Dashboard_Widget {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'logs';

	/**
	 * Get logs for widget.
	 *
	 * @return Log[]
	 */
	protected function get_logs() {
		$query = new Log_Query();
		$query->set_limit( 7 );
		$query->set_ordering( 'date', 'DESC' );

		return $query->get_results();
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		$logs = $this->get_logs();

		?>

		<div class="automatewoo-dashboard-list">

			<div class="automatewoo-dashboard-list__header">
				<div class="automatewoo-dashboard-list__heading">
					<?php esc_html_e( 'Recent logs', 'automatewoo' ); ?>
				</div>
				<a href="<?php echo esc_url( Admin::page_url( 'logs' ) ); ?>" class="automatewoo-arrow-link"></a>
			</div>

			<?php if ( $logs ) : ?>

				<div class="automatewoo-dashboard-list__items">
					<?php
					foreach ( $logs as $log ) :

						$workflow = $log->get_workflow();

						if ( ! $workflow ) {
							continue;
						}

						$modal_url = add_query_arg(
							[
								'action' => 'aw_modal_log_info',
								'log_id' => $log->get_id(),
							],
							admin_url( 'admin-ajax.php' )
						);

						?>

						<div class="automatewoo-dashboard-list__item">

							<a class="automatewoo-dashboard-list__item-button button view aw-button-icon js-open-automatewoo-modal" href="<?php echo esc_url( $modal_url ); ?>"><?php esc_html_e( 'View', 'automatewoo' ); ?></a>

							<a href="<?php echo esc_url( get_edit_post_link( $workflow->get_id() ) ); ?>" class="automatewoo-dashboard-list__item-title"><?php echo esc_html( $workflow->get_title() ); ?></a>
							<div class="automatewoo-dashboard-list__item-text">
								<?php
								Admin::get_view(
									'hoverable-date',
									[
										'date'          => $log->get_date(),
										'shorten_month' => true,
									]
								);
								?>
							</div>
						</div>

					<?php endforeach; ?>

				</div>

			<?php else : ?>

				<div class="automatewoo-dashboard-list__empty">
					<?php esc_html_e( 'No workflows have been run yet&hellip;', 'automatewoo' ); ?>
				</div>

			<?php endif; ?>

		</div>
		<?php
	}
}

return new Dashboard_Widget_Logs();
