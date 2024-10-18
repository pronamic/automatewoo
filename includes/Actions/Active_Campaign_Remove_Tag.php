<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Action_Active_Campaign_Remove_Tag
 * @since 2.0.0
 */
class Action_Active_Campaign_Remove_Tag extends Action_Active_Campaign_Abstract {
	/**
	 * Load admin details and set Action title
	 *
	 * @return void
	 */
	public function load_admin_details() {
		parent::load_admin_details();
		$this->title = __( 'Remove Tags From Contact', 'automatewoo' );
	}

	/**
	 * Load Action fields
	 *
	 * @return void
	 */
	public function load_fields() {
		$this->add_contact_email_field();
		$this->add_tags_field()->set_required();
	}

	/**
	 * Run the workflow and remove tags from a ctonact
	 *
	 * @throws Exception Thrown if there is an error when attempting to delete a tag association.
	 * @return void
	 */
	public function run() {
		$email = Clean::email( $this->get_option( 'email', true ) );
		$tags  = $this->parse_tags_field( $this->get_option( 'tag', true ) );

		if ( empty( $tags ) ) {
			return;
		}

		$contact      = $this->activecampaign()->get_contact( $email );
		$response     = $this->activecampaign()->request( 'contacts/' . $contact['id'] . '/contactTags' )->get_body();
		$contact_tags = $response['contactTags'];

		foreach ( $tags as $tag ) {
			$tag_id              = $this->activecampaign()->get_tag_id( $tag, true );
			$contact_association = array_search( $tag_id, array_column( $contact_tags, 'tag' ), true );

			if ( false !== $contact_association ) {
				if ( ! $this->activecampaign()->request( 'contactTags/' . $contact_tags[ $contact_association ]['id'], array(), 'DELETE' )->is_successful() ) {
					throw new Exception( esc_html__( 'There was an error when attempting to delete a tag for a contact', 'automatewoo' ) );
				}
			}
		}
	}
}
