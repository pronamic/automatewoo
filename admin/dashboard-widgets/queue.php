<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Queue class.
 */
class Dashboard_Widget_Queue extends Dashboard_Widget {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'queue';

	/**
	 * Get events for widget.
	 *
	 * @return Queued_Event[]
	 */
	protected function get_events() {
		$query = new Queue_Query();
		$query->where_failed( false );
		$query->set_limit( 7 );
		$query->set_ordering( 'date', 'ASC' );

		return $query->get_results();
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		$queue = $this->get_events();

		?>

		<div class="automatewoo-dashboard-list">

			<div class="automatewoo-dashboard-list__header">
				<div class="automatewoo-dashboard-list__heading">
					<?php esc_html_e( 'Upcoming queued events', 'automatewoo' ); ?>
				</div>
				<a href="<?php echo esc_url( Admin::page_url( 'queue' ) ); ?>" class="automatewoo-arrow-link"></a>
			</div>

			<?php if ( $queue ) : ?>

				<div class="automatewoo-dashboard-list__items">
					<?php
					foreach ( $queue as $event ) :
						$workflow = $event->get_workflow();

						if ( ! $workflow ) {
							continue;
						}
						?>

						<div class="automatewoo-dashboard-list__item">
							<a href="<?php echo esc_url( get_edit_post_link( $workflow->get_id() ) ); ?>" class="automatewoo-dashboard-list__item-title"><?php echo esc_html( $workflow->get_title() ); ?></a>
							<div class="automatewoo-dashboard-list__item-text"><?php echo esc_html( Format::datetime( $event->get_date_due() ) ); ?></div>
						</div>

					<?php endforeach; ?>
				</div>

			<?php else : ?>

				<div class="automatewoo-dashboard-list__empty">
					<?php esc_html_e( 'There are no events currently queued&hellip;', 'automatewoo' ); ?>
				</div>

			<?php endif; ?>

		</div>

		<?php
	}
}

return new Dashboard_Widget_Queue();
