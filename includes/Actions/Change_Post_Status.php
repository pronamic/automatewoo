<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Change_Post_Status
 * @since 2.0.0
 */
class Action_Change_Post_Status extends Action {

	public $required_data_items = [ 'post' ];


	function load_admin_details() {
		$this->title = __( 'Change Post Status', 'automatewoo' );
		$this->group = __( 'Other', 'automatewoo' );
	}


	function load_fields() {
		$post_status = new Fields\Select( false );
		$post_status->set_name('post_status');
		$post_status->set_title(__('Post status', 'automatewoo') );
		$post_status->set_options( get_post_statuses() );
		$post_status->set_required();

		$this->add_field($post_status);
	}



	function run() {
		$post = $this->workflow->data_layer()->get_item( 'post' );
		$status = $this->get_option( 'post_status' );

		if ( ! $status || ! $post )
			return;

		wp_update_post([
			'ID' => $post->ID,
			'post_status' => $status
		]);
	}

}
