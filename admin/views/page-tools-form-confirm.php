<?php

namespace AutomateWoo;

/**
 * @var Tool_Abstract                      $tool
 * @var Admin\Controllers\Tools_Controller $controller
 * @var array                              $args
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--tools">

	<?php
	Admin::get_view(
		'tool-header',
		[
			'tool'       => $tool,
			'controller' => $controller,
		]
	);
	?>

	<div id="poststuff">

		<form id="automatewoo_process_tool_form" method="post" action="<?php echo esc_url( $controller->get_route_url( 'confirm', $tool ) ); ?>">

			<?php wp_nonce_field( $tool->get_id() ); ?>

			<?php foreach ( $args as $key => $value ) : ?>
				<input type="hidden" name="args[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( stripslashes( $value ) ); ?>">
			<?php endforeach ?>

			<div class="automatewoo-metabox postbox">
				<div class="automatewoo-metabox-pad">
					<p><?php $tool->display_confirmation_screen( $args ); ?></p>
				</div>

				<div class="automatewoo-metabox-footer">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Confirm', 'automatewoo' ); ?></button>
				</div>
			</div>

		</form>

	</div>

</div>


