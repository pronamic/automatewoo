<?php

namespace AutomateWoo\Actions;

use AutomateWoo\Action_Mailchimp_Abstract;
use AutomateWoo\Fields\Checkbox;
use AutomateWoo\Integrations;

/**
 * Class Mailchimp_Update_Tags
 *
 * @package AutomateWoo\Actions
 * @since 4.8.0
 */
class Mailchimp_Update_Tags extends Action_Mailchimp_Abstract {

	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title       = __( 'Update Contact Tags', 'automatewoo' );
		$this->description = __( 'The contact must have been added to the list before updating tags.', 'automatewoo' );
	}

	/**
	 * Generate the fields the user can set for the action.
	 */
	public function load_fields() {
		$this->add_list_field();
		$this->add_field( $this->get_contact_email_field() );
		$this->add_tags_field( 'add_tags', __( 'Tags to add', 'automatewoo' ) );
		$this->add_field(
			( new Checkbox() )
				->set_name( 'remove_others' )
				->set_title( __( 'Remove all other tags', 'automatewoo' ) )
				->set_description( __( 'Whether to remove all other tags that are already assigned to the contact.', 'automatewoo' ) )
		);
		$this->add_tags_field( 'remove_tags', __( 'Tags to remove', 'automatewoo' ) )
			->add_data_attr( 'hide-when-checked', 'remove_others' );
	}

	/**
	 * Called when an action should be run.
	 *
	 * @throws \Exception When the contact isn't part of the list.
	 */
	public function run() {
		$this->validate_required_fields();

		$email       = $this->get_contact_email_option();
		$list        = $this->get_option( 'list' );
		$add_tags    = $this->parse_tags_field_keys( $this->get_option( 'add_tags', true ) );
		$remove_tags = $this->parse_tags_field_keys( $this->get_option( 'remove_tags', true ) );
		$remove      = $this->get_option( 'remove_others' );

		// Validate tag handling can proceed.
		if ( empty( $add_tags ) && empty( $remove_tags ) && false === $remove ) {
			throw new \Exception( esc_html__( 'Tags should not be empty.', 'automatewoo' ) );
		}

		$this->validate_contact( $email, $list );

		$existing_tags = $remove ? Integrations::mailchimp()->get_member_tags( $email, $list ) : [];
		$tag_updates   = [];

		// Tags that need to be added.
		$to_add = array_diff_key( $add_tags, $existing_tags );
		foreach ( $to_add as $tag ) {
			// Note: this uses the non-lowercase version of the tag in case the tag will be created on Mailchimp.
			$tag_updates[ $tag ] = true;
		}

		// Tags that need to be removed. Either existing tags, or only those selected by the store owner.
		if ( $remove ) {
			$to_remove = array_diff_key( $existing_tags, $add_tags );
			foreach ( $to_remove as $key => $tag ) {
				$tag_updates[ $key ] = false;
			}
		} else {
			foreach ( $remove_tags as $key => $tag ) {
				$tag_updates[ $key ] = false;
			}
		}

		// Skip the API call if there's nothing to change.
		if ( empty( $tag_updates ) ) {
			throw new \Exception( esc_html__( 'There was no tags to update.', 'automatewoo' ) );
		}

		$this->maybe_log_action( Integrations::mailchimp()->update_member_tags( $email, $list, $tag_updates ) );
	}
}
