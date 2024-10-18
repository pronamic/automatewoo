<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Tool_Abstract                      $tool
 * @var Admin\Controllers\Tools_Controller $controller
 */
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

		<form id="automatewoo_process_tool_form" method="post" action="<?php echo esc_url( $controller->get_route_url( 'validate', $tool ) ); ?>">

			<div class="automatewoo-metabox postbox">

				<table class="automatewoo-table">

					<?php foreach ( $tool->get_form_fields() as $field ) : ?>

						<tr class="automatewoo-table__row">

							<td class="automatewoo-table__col automatewoo-table__col--label">
								<?php Admin::help_tip( $field->get_description() ); ?>

								<label><?php echo wp_kses_post( $field->get_title() ); ?>
									<?php if ( $field->get_required() ) : ?>
										<span class="required">*</span>
									<?php endif; ?>
								</label>
							</td>

							<td class="automatewoo-table__col automatewoo-table__col--field">
								<?php
								// phpcs:disable WordPress.Security.NonceVerification.Missing
								$value = isset( $_POST['args'][ $field->get_name() ] )
									?
									sanitize_textarea_field( wp_unslash( $_POST['args'][ $field->get_name() ] ) )
									:
									false;
								// phpcs:enable
								$field->render( $value );

								?>
							</td>
						</tr>

					<?php endforeach; ?>

				</table>

				<div class="automatewoo-metabox-footer">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Next', 'automatewoo' ); ?></button>
				</div>
			</div>

		</form>


	</div>

</div>
