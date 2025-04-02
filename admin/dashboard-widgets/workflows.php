<?php

namespace AutomateWoo;

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

		$featured = [];

		// Check for the most run workflow.
		$workflow = $this->controller->get_most_run_workflow();
		if ( $workflow ) {
			$featured[] = [
				'workflow'    => $workflow,
				'description' => __( 'most run workflow', 'automatewoo' ),
			];
		}

		// Get the highest converting workflow.
		$highest_converting_workflow = $this->controller->get_highest_converting_workflow();
		if ( $highest_converting_workflow ) {
			$featured[] = [
				'workflow'    => $highest_converting_workflow,
				'description' => __( 'highest converting workflow', 'automatewoo' ),
			];
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
