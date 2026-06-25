<?php

namespace AutomateWoo;

use AutomateWoo\Actions\ActionInterface;
use AutomateWoo\Traits\MailServiceAction;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Mailchimp_Subscribe
 */
class Action_Mailchimp_Subscribe extends Action_Mailchimp_Abstract {

	/**
	 * Implements Action load_admin_details abstract method
	 *
	 * @see Action::load_admin_details()
	 */
	protected function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Add Contact To List', 'automatewoo' );
	}

	/**
	 * Implements Action load_fields abstract method
	 *
	 * @see Action::load_fields()
	 * @see MailServiceAction::load_subscribe_action_fields()
	 */
	public function load_fields() {
		$this->load_subscribe_action_fields( $this->get_mailchimp_lists() );
		$this->maybe_add_mailchimp_list_field_notice( $this->get_field( 'list' ) );
		$this->add_field(
			( new Fields\Checkbox() )
				->set_name( 'enable_marketing_permissions' )
				->set_title( __( 'Enable marketing permissions', 'automatewoo' ) )
				->set_description( __( 'Use this when the selected Mailchimp list has GDPR marketing permissions enabled and this workflow has captured consent.', 'automatewoo' ) )
		);
	}

	/**
	 * Implements Action run abstract method
	 *
	 * @throws \Exception When the action fails.
	 * @see ActionInterface::run()
	 */
	public function run() {
		$this->validate_required_fields();

		$list_id    = $this->get_option( 'list' );
		$email      = $this->get_contact_email_option();
		$first_name = $this->get_option( 'first_name', true );
		$last_name  = $this->get_option( 'last_name', true );

		$args            = [];
		$subscriber_hash = md5( $email );

		$args['email_address'] = $email;
		$args['status']        = $this->get_option( 'double_optin' ) ? 'pending' : 'subscribed';

		if ( $first_name || $last_name ) {
			$args['merge_fields'] = [
				'FNAME' => $first_name,
				'LNAME' => $last_name,
			];
		}

		$request = $this->mailchimp()->request( 'PUT', "/lists/$list_id/members/$subscriber_hash", $args );
		$this->maybe_log_action( $request );
		$this->maybe_enable_marketing_permissions( $request, $list_id, $subscriber_hash );
	}

	/**
	 * Enable Mailchimp marketing permissions for the subscriber when requested.
	 *
	 * @param Remote_Request $request         The initial subscriber request.
	 * @param string         $list_id         The list ID.
	 * @param string         $subscriber_hash The subscriber hash.
	 *
	 * @throws \Exception When the action fails.
	 */
	private function maybe_enable_marketing_permissions( $request, $list_id, $subscriber_hash ) {
		if ( ! $this->get_option( 'enable_marketing_permissions' ) ) {
			return;
		}

		$permissions = $this->get_enabled_marketing_permissions( $request );
		if ( ! $permissions ) {
			return;
		}

		$this->maybe_log_action(
			$this->mailchimp()->request(
				'PATCH',
				"/lists/$list_id/members/$subscriber_hash",
				[
					'marketing_permissions' => $permissions,
				]
			)
		);
	}

	/**
	 * Build an enabled marketing permissions payload from the Mailchimp response.
	 *
	 * @param Remote_Request $request The Mailchimp request.
	 * @return array
	 */
	private function get_enabled_marketing_permissions( $request ) {
		$body = $request->get_body();
		if ( empty( $body['marketing_permissions'] ) || ! is_array( $body['marketing_permissions'] ) ) {
			return [];
		}

		$permissions = [];
		foreach ( $body['marketing_permissions'] as $permission ) {
			if ( empty( $permission['marketing_permission_id'] ) ) {
				continue;
			}

			$permissions[] = [
				'marketing_permission_id' => Clean::string( $permission['marketing_permission_id'] ),
				'enabled'                 => true,
			];
		}

		return $permissions;
	}
}
