<?php

/**
 * @var $workflow
 */

defined( 'ABSPATH' ) || exit;

?>
<table class="automatewoo-table">
	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php
				esc_html_e( 'Variables', 'automatewoo' );
				AutomateWoo\Admin::help_tip(
					__(
						'Click on a variable to see more info and copy it to the clipboard. Variables can be used in any action text field to add dynamic content. The available variables are set based on the selected trigger for this workflow.',
						'automatewoo'
					)
				);
				?>
			</label>

			<div class="aw-workflow-variables-container">
				<?php
				$data_types = AutomateWoo\Variables::get_list();
				foreach ( $data_types as $data_type => $vars ) :
					if ( $data_type === 'user' ) {
						$data_type = 'customer';
					}
					?>
					<div class="aw-variables-group" data-automatewoo-variable-group="<?php echo esc_attr( $data_type ); ?>">
						<?php
						// Order alphabetically
						ksort( $vars );
						foreach ( $vars as $variable => $file_path ) :
							?>
							<div class="aw-workflow-variable-outer">
								<span class="aw-workflow-variable"><?php echo esc_html( $data_type . '.' . $variable ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</td>
	</tr>
</table>
