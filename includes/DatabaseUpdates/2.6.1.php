<?php
// phpcs:ignoreFile
/**
 * Update to 2.6.1
 *
 * migrate all 'disabled like' post statuses to the new 'disabled' status
 */

if ( ! defined( 'ABSPATH' ) ) exit;


$workflows_query = new AutomateWoo\Workflow_Query();
$workflows_query->args['post_status'] = [ 'draft', 'pending', 'private' ];

$workflows = $workflows_query->get_results();

if ( $workflows ) foreach ( $workflows as $workflow ) {
	/** @var $workflow AutomateWoo\Workflow */
	wp_update_post([
		'ID' => $workflow->get_id(),
		'post_status' => 'aw-disabled'
	]);
}
