<?php

namespace AutomateWoo;

/**
 * @var Workflow $workflow
 * @var Action[] $actions
 * @var $action_select_box_values
 */

defined( 'ABSPATH' ) || exit;

// Translate the button text for use later.
$button_text = __( '+ Add Action', 'automatewoo' );

?>
<div class="aw-actions-container">
	<?php
	$n = 1;
	foreach ( $actions as $action ) {
		Admin::get_view(
			'action',
			[
				'workflow'                 => $workflow,
				'action'                   => $action,
				'action_number'            => $n,
				'action_select_box_values' => $action_select_box_values,
			]
		);
		++$n;
	}

	// Render blank action template
	Admin::get_view(
		'action',
		[
			'workflow'                 => $workflow,
			'action'                   => false,
			'action_number'            => false,
			'action_select_box_values' => $action_select_box_values,
		]
	);
	?>

	<?php if ( empty( $actions ) ) : ?>
		<div class="js-aw-no-actions-message">
			<p>
				<?php
				printf(
					/* translators: 1: <strong> tag, 2: button text for Add Action button, 3: </strong> tag */
					esc_html__( 'No actions. Click the %1$s%2$s%3$s button to create an action.', 'automatewoo' ),
					'<strong>',
					esc_html( $button_text ),
					'</strong>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>

<div class="automatewoo-metabox-footer">
	<a href="#" class="js-aw-add-action button button-primary button-large">
		<?php echo esc_html( $button_text ); ?>
	</a>
</div>
