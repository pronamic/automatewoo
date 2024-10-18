<?php

namespace AutomateWoo;

use AutomateWoo\Triggers\ManualInterface;

defined( 'ABSPATH' ) || exit;

/**
 * @var Workflow $workflow
 * @var Trigger  $current_trigger
 */

// Group triggers
$trigger_list = [];
foreach ( Triggers::get_all() as $trigger ) {
	if ( $trigger instanceof ManualInterface ) {
		continue;
	}
	$trigger_list[ $trigger->get_group() ][] = $trigger;
}

$current_selected    = $current_trigger ? $current_trigger->get_name() : '';
$current_description = $current_trigger && $current_trigger->get_description()
	? $current_trigger->get_description_html()
	: '';
?>
<table class="automatewoo-table">
	<tr class="automatewoo-table__row" data-name="trigger_name" data-type="select" data-required="1">
		<td class="automatewoo-table__col automatewoo-table__col--label">
			<label><?php esc_html_e( 'Trigger', 'automatewoo' ); ?> <span class="required">*</span></label>
		</td>
		<td class="automatewoo-table__col automatewoo-table__col--field">
			<select name="aw_workflow_data[trigger_name]" class="automatewoo-field js-trigger-select">
				<option value=""><?php esc_html_e( '[Select]', 'automatewoo' ); ?></option>
				<?php foreach ( $trigger_list as $trigger_group => $triggers ) : ?>
					<optgroup label="<?php echo esc_attr( $trigger_group ); ?>">
						<?php foreach ( $triggers as $_trigger ) : /** @var Trigger $_trigger */ ?>
							<option value="<?php echo esc_attr( $_trigger->get_name() ); ?>" <?php selected( $_trigger->get_name(), $current_selected ); ?>>
								<?php echo esc_html( $_trigger->get_title() ); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>

			<div class="js-trigger-description">
				<?php echo $current_description; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</td>
	</tr>

	<?php
	if ( $workflow ) {
		Admin::get_view(
			'trigger-fields',
			[
				'trigger'     => $current_trigger,
				'workflow'    => $workflow,
				'fill_fields' => true,
			]
		);
	}
	?>
</table>
