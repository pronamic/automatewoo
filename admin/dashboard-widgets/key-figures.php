<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard_Widget_Key_Figures class.
 */
class Dashboard_Widget_Key_Figures extends Dashboard_Widget {

	/**
	 * Widget's ID
	 *
	 * @var string
	 */
	public $id = 'key-figures';

	/**
	 * Get array of key figures.
	 *
	 * @return array
	 */
	protected function get_figures() {
		$figures = [];

		if ( ! $this->date_to || ! $this->date_from ) {
			return [];
		}

		$optins_count = $this->controller->get_optins_count();
		$queued_count = $this->controller->get_queued_count();

		$figures[] = [
			'name'  => __( 'workflows queued', 'automatewoo' ),
			'value' => $queued_count,
			'link'  => Admin::page_url( 'queue' ),
		];

		if ( Options::abandoned_cart_enabled() ) {
			$carts_count  = $this->controller->get_active_carts_count();
			$guests_count = $this->controller->get_guests_count();

			$figures[] = [
				'name'  => __( 'active carts', 'automatewoo' ),
				'value' => $carts_count,
				'link'  => Admin::page_url( 'carts' ),
			];

			$figures[] = [
				'name'  => __( 'guests captured', 'automatewoo' ),
				'value' => $guests_count,
				'link'  => Admin::page_url( 'guests' ),
			];
		}

		$figures[] = [
			'name'  => Options::optin_enabled() ? __( 'opt-ins', 'automatewoo' ) : __( 'opt-outs', 'automatewoo' ),
			'value' => $optins_count,
			'link'  => Admin::page_url( 'opt-ins' ),
		];

		return apply_filters( 'automatewoo/dashboard/key_figures', $figures );
	}

	/**
	 * Output the widget content.
	 */
	protected function output_content() {
		$figures = $this->get_figures();

		if ( empty( $figures ) ) {
			$this->display = false;
			return;
		}

		?>

		<div class="automatewoo-dashboard__figures">
			<?php foreach ( $figures as $figure ) : ?>
				<a href="<?php echo esc_url( $figure['link'] ); ?>" class="automatewoo-dashboard__figure">
					<div class="automatewoo-dashboard__figure-value"><?php echo esc_html( $figure['value'] ); ?></div>
					<div class="automatewoo-dashboard__figure-name"><?php echo esc_html( $figure['name'] ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>

		<?php
	}
}

return new Dashboard_Widget_Key_Figures();
