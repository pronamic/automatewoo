<?php
/**
 * Workflow related templates for backbone.
 *
 * @package AutomateWoo
 */

defined( 'ABSPATH' ) || exit;

?>

<script id="tmpl-aw-trigger-compatibility-modal" type="text/template">
	<div class="automatewoo-modal__header">
		<h1><?php esc_html_e( 'Confirm change trigger?', 'automatewoo' ); ?></h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">
			<p><?php esc_html_e( 'Some of the rules or actions currently in use on this workflow are incompatible with the new trigger you have selected. If you continue they will be removed.', 'automatewoo' ); ?></p>
			<# if ( data.incompatibleRules.length ) { #>
				<p><strong><?php esc_html_e( 'Incompatible Rules: ', 'automatewoo' ); ?></strong>{{ data.incompatibleRules.join(', ') }}</p>
			<# } #>
			<# if ( data.incompatibleActions.length ) { #>
				<p><strong><?php esc_html_e( 'Incompatible Actions: ', 'automatewoo' ); ?></strong>{{ data.incompatibleActions.join(', ') }}</p>
			<# } #>
		</div>
	</div>

	<div class="automatewoo-modal__footer aw-pull-right">
		<button type="button" class="button js-close-automatewoo-modal"><?php esc_html_e( 'Cancel', 'automatewoo' ); ?></button>
		<button type="button" class="button button-primary js-confirm"><?php esc_html_e( 'Confirm', 'automatewoo' ); ?></button>
	</div>

</script>
