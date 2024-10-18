<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Variable $variable
 */

?>

	<div class="automatewoo-modal__header">
		<h1><?php echo esc_html( $variable->get_name() ); ?></h1>
	</div>

	<div class="automatewoo-modal__body">
		<div class="automatewoo-modal__body-inner">

			<?php if ( $variable->get_description() ) : ?>
				<p><?php echo wp_kses_post( $variable->get_description() ); ?></p>
			<?php endif; ?>

			<table class="automatewoo-table automatewoo-table--bordered aw-workflow-variable-parameters-table">

				<?php foreach ( $variable->get_parameter_fields() as $field ) : ?>

					<tr class="automatewoo-table__row aw-workflow-variables-parameter-row"
						data-parameter-name="<?php echo esc_attr( $field->get_name() ); ?>"
						<?php if ( isset( $field->meta['show'] ) ) : ?>
							data-parameter-show="<?php echo esc_attr( $field->meta['show'] ); ?>"
						<?php endif; ?>
						<?php echo ( $field->get_required() ? 'data-is-required="true"' : '' ); ?>
					>

						<td class="automatewoo-table__col automatewoo-table__col--label">
							<strong><?php echo esc_html( $field->get_name() ); ?></strong>
							<?php if ( $field->get_required() ) : ?>
								<span class="aw-required-asterisk"></span>
							<?php endif; ?>
							<?php Admin::help_tip( $field->get_description() ); ?>
						</td>
						<td class="automatewoo-table__col automatewoo-table__col--field">
							<?php $field->add_classes( 'aw-workflow-variable-parameter' ); ?>
							<?php $field->render( '' ); ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<?php if ( $variable->use_fallback ) : ?>
					<tr class="automatewoo-table__row">
						<td class="automatewoo-table__col automatewoo-table__col--label">
							<strong>fallback</strong>
							<?php Admin::help_tip( __( 'Displayed when there is no value found.', 'automatewoo' ) ); ?>
						</td>
						<td class="automatewoo-table__col automatewoo-table__col--field">
							<input type="text" name="fallback" class="automatewoo-field automatewoo-field--type-text aw-workflow-variable-parameter">
						</td>
					</tr>
				<?php endif; ?>

			</table>

			<div class="aw-workflow-variable-clipboard-form">
				<div id="aw_workflow_variable_preview_field" class="aw-workflow-variable-preview-field" data-variable="<?php echo esc_attr( $variable->get_name() ); ?>"></div>
				<button class="aw-clipboard-btn button button-primary button-large"><?php esc_html_e( 'Copy to clipboard', 'automatewoo' ); ?></button>
			</div>

		</div>
	</div>
