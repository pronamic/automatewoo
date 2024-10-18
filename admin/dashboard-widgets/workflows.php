<?php

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Workflows class.
 */
class Dashboard_Widget_Workflows extends Dashboard_Widget {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'workflows';

	/**
	 * Get array of featured workflows.
	 *
	 * @return array
	 */
	protected function get_featured() {
		if ( ! $this->date_to || ! $this->date_from ) {
			return [];
		}

		$featured    = [];
		$logs        = $this->controller->get_logs();
		$conversions = $this->controller->get_conversions();
		$counts      = [];

		foreach ( $logs as $log ) {
			$counts[] = $log->get_workflow_id();
		}

		$counts = array_count_values( $counts );
		arsort( $counts, SORT_NUMERIC );
		$workflow = Factory::get( key( $counts ) );

		if ( $workflow ) {
			$featured[] = [
				'workflow'    => $workflow,
				'description' => __( 'most run workflow', 'automatewoo' ),
			];
		}

		if ( $conversions ) {
			$totals = [];

			foreach ( $conversions as $order ) {
				$workflow_id = absint( $order->get_meta( '_aw_conversion' ) );

				if ( isset( $totals[ $workflow_id ] ) ) {
					$totals[ $workflow_id ] += $order->get_total();
				} else {
					$totals[ $workflow_id ] = $order->get_total();
				}
			}

			arsort( $totals, SORT_NUMERIC );
			$workflow = Factory::get( key( $totals ) );

			if ( $workflow ) {
				$featured[] = [
					'workflow'    => $workflow,
					'description' => __( 'highest converting workflow', 'automatewoo' ),
				];
			}
		}

		return $featured;
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		$features = $this->get_featured();

		if ( empty( $features ) ) {
			$this->display = false;
			return;
		}

		?>

		<div class="automatewoo-dashboard__workflows">
			<?php foreach ( $features as $feature ) : ?>

				<?php
				/**
				 * For IDE.
				 *
				 * @var $workflow Workflow
				 */
				$workflow = $feature['workflow'];
				?>

				<a class="automatewoo-dashboard__workflow" href="<?php echo esc_url( get_edit_post_link( $workflow->get_id() ) ); ?>">

					<div class="automatewoo-dashboard__workflow-title"><?php echo esc_html( $workflow->get_title() ); ?></div>
					<div class="automatewoo-dashboard__workflow-description"><?php echo esc_html( $feature['description'] ); ?></div>

				</a>

			<?php endforeach; ?>
		</div>

		<?php
	}
}

return new Dashboard_Widget_Workflows();
