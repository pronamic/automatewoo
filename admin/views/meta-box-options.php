<?php

namespace AutomateWoo;

use AutomateWoo\Fields\Checkbox;
use AutomateWoo\Fields\Number;
use AutomateWoo\Fields\Text_Area;

defined( 'ABSPATH' ) || exit;

/**
 * @var Workflow $workflow
 */

global $post;

$transactional = new Checkbox();
$transactional->set_name_base( 'aw_workflow_data' )->set_name( 'is_transactional' );

$enable_tracking = new Checkbox();
$enable_tracking
	->set_name_base( 'aw_workflow_data[workflow_options]' )
	->set_name( 'click_tracking' )
	->add_classes( 'aw-checkbox-enable-click-tracking' );

$conversion_tracking = new Checkbox();
$conversion_tracking
	->set_name_base( 'aw_workflow_data[workflow_options]' )
	->set_name( 'conversion_tracking' )
	->add_classes( 'aw-checkbox-enable-conversion-tracking' );

$ga_link_tracking = new Text_Area();
$ga_link_tracking
	->set_rows( 3 )
	->set_name_base( 'aw_workflow_data[workflow_options]' )
	->set_name( 'ga_link_tracking' )
	->add_classes( 'automatewoo-field--monospace' )
	->add_extra_attr( 'spellcheck', 'false' )
	->set_placeholder( 'e.g. utm_source=automatewoo&utm_medium=email&utm_campaign=example' );

$workflow_order = new Number();
$workflow_order->set_name( 'menu_order' );

?>
<table class="automatewoo-table">
	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label automatewoo-label--inline-checkbox">
				<?php
				esc_html_e( 'Is transactional?', 'automatewoo' );
				$transactional->render( $workflow ? absint( $workflow->get_meta( 'is_transactional' ) ) : '' );
				Admin::help_tip( __( 'Is this workflow used for transactional emails instead of marketing emails? Checking this removes the unsubscribe link in email.', 'automatewoo' ) );
				?>
			</label>
		</td>
	</tr>

	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label automatewoo-label--inline-checkbox">
				<?php
				esc_html_e( 'Enable tracking', 'automatewoo' );
				$enable_tracking->render( $workflow ? $workflow->get_option( 'click_tracking' ) : '' );
				Admin::help_tip( __( 'Enables open and click tracking on emails sent from this workflow. SMS messages will also have click tracking but open tracking is not possible with SMS.', 'automatewoo' ) );
				?>
			</label>
		</td>
	</tr>

	<tr class="automatewoo-table__row js-require-email-tracking">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label automatewoo-label--inline-checkbox">
				<?php
				esc_html_e( 'Enable conversion tracking', 'automatewoo' );
				$conversion_tracking->render( $workflow ? $workflow->get_option( 'conversion_tracking' ) : '' );
				Admin::help_tip( __( 'Enables conversion tracking on purchases made as a result of this workflow.', 'automatewoo' ) );
				?>
			</label>
		</td>
	</tr>

	<tr class="automatewoo-table__row js-require-email-tracking">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php
				esc_html_e( 'Google Analytics link tracking', 'automatewoo' );
				Admin::help_tip( __( 'This will be appended to every URL in the email content or SMS body.', 'automatewoo' ) );
				?>
			</label>
			<?php $ga_link_tracking->render( $workflow ? $workflow->get_option( 'ga_link_tracking' ) : '' ); ?>
		</td>
	</tr>

	<tr class="automatewoo-table__row">
		<td class="automatewoo-table__col">
			<label class="automatewoo-label">
				<?php
				esc_html_e( 'Workflow order', 'automatewoo' );
				Admin::help_tip( __( 'The order that workflows will run.', 'automatewoo' ) );
				?>
			</label>
			<?php $workflow_order->render( $post ? $post->menu_order : '' ); ?>
		</td>
	</tr>
</table>
