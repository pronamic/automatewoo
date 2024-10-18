<?php

namespace AutomateWoo;

use AutomateWoo\Fields\Select;

defined( 'ABSPATH' ) || exit;

/**
 * @var Workflow $workflow
 * @var Trigger  $current_trigger
 */
?>
<table class="automatewoo-table">
	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col automatewoo-table__col--label">
			<label><?php esc_html_e( 'Data type', 'automatewoo' ); ?> <span class="required">*</span></label>
			<?php Admin::help_tip( __( 'The data type determines which data the workflow can run for and which rules and actions you can use.', 'automatewoo' ) ); ?>
		</td>
		<td class="automatewoo-table__col automatewoo-table__col--field">
			<?php
			// Generate select options
			$options = [];
			foreach ( Triggers::get_manual_triggers() as $trigger ) {
				// todo update with real name, waiting on different PR
				$options[ $trigger->get_name() ] = ucfirst( $trigger->get_primary_data_type() );
			}

			( new Select() )
				->set_options( $options )
				->set_name( 'aw_workflow_data[manual_trigger_name]' )
				->add_classes( 'js-manual-trigger-select' )
				->render( $current_trigger ? $current_trigger->get_name() : '' );
			?>
		</td>
	</tr>
</table>
