<?php
/**
 * Workflow related templates for backbone.
 *
 * @since 5.1.0
 * @package AutomateWoo
 */

defined( 'ABSPATH' ) || exit;

?>

<script id="tmpl-aw-trigger-preset-activation-modal" type="text/template">
	<div class="automatewoo-modal__header">
		<h1>
		<# if ( data.isActive ) { #>
		<?php esc_html_e( 'Confirm activate workflow?', 'automatewoo' ); ?>
		<# } else { #>
		<?php esc_html_e( 'Confirm save disabled workflow?', 'automatewoo' ); ?>
		<# } #>
		</h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">
			<p>
				<# if ( data.isActive ) { #>
				<?php echo wp_kses_post( __( 'Before you <strong>activate</strong> this workflow,  did you remember to:', 'automatewoo' ) ); ?>
				<# } else { #>
				<?php echo wp_kses_post( __( 'Before you save this <strong>disabled</strong> workflow, did you remember to:', 'automatewoo' ) ); ?>
				<# } #>
			</p>
			<ul>
				<li>- <?php esc_html_e( 'Confirm the values for all trigger, rule, and action options?', 'automatewoo' ); ?></li>
				<li>- <?php esc_html_e( 'Review the text of any emails that will be sent to customers?', 'automatewoo' ); ?></li>
				<li>- <?php esc_html_e( 'Substitute placeholder coupons and/or discount values in an emails?', 'automatewoo' ); ?></li>
			</ul>
			<p>
				<?php esc_html_e( 'If so, click \'Confirm\'. If not, click \'Cancel\' to review the workflow again.', 'automatewoo' ); ?>
			</p>
		</div>
	</div>

	<div class="automatewoo-modal__footer aw-pull-right">
		<button type="button" class="button js-close-automatewoo-modal"><?php esc_html_e( 'Cancel', 'automatewoo' ); ?></button>
		<button type="button" class="button button-primary js-confirm"><?php esc_html_e( 'Confirm', 'automatewoo' ); ?></button>
	</div>

</script>
