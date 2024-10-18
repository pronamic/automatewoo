<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Add_To_Mad_Mimi_List
 */
class Action_Add_To_Mad_Mimi_List extends Action {

	public $required_data_items = [ 'customer' ];


	function load_admin_details() {
		$this->title = __( 'Add Customer to List', 'automatewoo' );
		$this->group = __( 'Mad Mimi', 'automatewoo' );
	}


	function check_requirements() {
		if ( ! function_exists('curl_init') ) {
			$this->warning( __('Server is missing CURL extension required to use the MadMimi API.', 'automatewoo' ) );
		}
	}



	function load_fields() {

		$email = new Fields\Text();
		$email->set_name('username');
		$email->set_title( __( 'Username (email)', 'automatewoo' ) );
		$email->set_required(true);

		$api_key = new Fields\Text();
		$api_key->set_name('api_key');
		$api_key->set_title( __( 'API key', 'automatewoo' ) );
		$api_key->set_required(true);
		$api_key->set_description( __( 'You can get your API key from the account section of Mad Mimi.', 'automatewoo' ) );

		$list = new Fields\Text();
		$list->set_name('list');
		$list->set_title( __( 'List name', 'automatewoo' ) );
		$list->set_required(true);

		$this->add_field($email);
		$this->add_field($api_key);
		$this->add_field($list);
	}


	function run() {

		$customer = $this->workflow->data_layer()->get_customer();
		$username = Clean::email( $this->get_option('username') );
		$api_key = $this->get_option('api_key');
		$list = $this->get_option('list');

		if ( ! $customer || ! $username || ! $api_key || ! $list ) {
			return;
		}

		$mad_mimi = new Integration_Mad_Mimi( $username, $api_key );

		$data = [
			'email' => $customer->get_email(),
			'firstname' => $customer->get_first_name(),
			'lastname' => $customer->get_last_name(),
			'add_list' => $list
		];

		$csv = $mad_mimi->build_csv( $data );

		$mad_mimi->request( 'POST', '/audience_members', [ 'csv_file' => $csv ] );
	}

}
