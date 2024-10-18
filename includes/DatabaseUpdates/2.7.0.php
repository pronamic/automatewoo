<?php
// phpcs:ignoreFile
/**
 * Update to 2.7 - ActiveCampaign changes
 */

if ( ! defined( 'ABSPATH' ) ) exit;


$workflows_query = new AutomateWoo\Workflow_Query();
$workflows_query->args['post_status'] = 'any';

$workflows = $workflows_query->get_results();

foreach ( $workflows as $workflow ) {

	/** @var $workflow AutomateWoo\Workflow */

	$actions = $workflow->get_meta( 'actions' );
	$update = false;

	if ( $actions ) foreach ( $actions as &$action ) {

		if ( empty( $action['action_name'] ) )
			continue;

		switch ( $action['action_name'] ) {

			case 'add_user_to_active_campaign_list':
			case 'active_campaign_add_tag':
				$update = true;

				if ( empty( $action['email'] ) ) $action['email'] = '{{ user.email }}';
				if ( empty( $action['first_name'] ) ) $action['first_name'] = '{{ user.firstname }}';
				if ( empty( $action['last_name'] ) ) $action['last_name'] = '{{ user.lastname }}';
				if ( empty( $action['phone'] ) ) $action['phone'] = '{{ user.billing_phone }}';

				break;

			case 'active_campaign_remove_tag':

				$update = true;

				if ( empty( $action['email'] ) )  $action['email'] = '{{ user.email }}';

				break;
		}

		if ( $update ) {
			$workflow->update_meta( 'actions', $actions );
		}
	}
}
