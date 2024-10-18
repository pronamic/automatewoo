<?php

namespace AutomateWoo;

use AutomateWoo\Traits\TagField;

defined( 'ABSPATH' ) || exit;

/**
 * @class Action_Active_Campaign_Abstract
 */
abstract class Action_Active_Campaign_Abstract extends Action {
	use TagField;

	/** @var Integration_ActiveCampaign ActiveCampaign Integration class */
	private $activecampaign;

	/**
	 * Set admin details group value
	 *
	 * @return void
	 */
	public function load_admin_details() {
		$this->group = __( 'ActiveCampaign', 'automatewoo' );
	}

	/**
	 * Add contact email field to Action
	 *
	 * @return Fields\Text
	 */
	public function add_contact_email_field() {
		$email = ( new Fields\Text() )
			->set_name( 'email' )
			->set_title( __( 'Contact email', 'automatewoo' ) )
			->set_description( __( 'You can use variables such as {{ customer.email }} here.', 'automatewoo' ) )
			->set_required()
			->set_variable_validation();

		$this->add_field( $email );

		return $email;
	}

	/**
	 * Add additional contact fields to Action
	 *
	 * @return void
	 */
	public function add_contact_fields() {
		$first_name = ( new Fields\Text() )
			->set_name( 'first_name' )
			->set_title( __( 'First name', 'automatewoo' ) )
			->set_variable_validation();

		$last_name = ( new Fields\Text() )
			->set_name( 'last_name' )
			->set_title( __( 'Last name', 'automatewoo' ) )
			->set_variable_validation();

		$phone = ( new Fields\Text() )
			->set_name( 'phone' )
			->set_title( __( 'Phone', 'automatewoo' ) )
			->set_variable_validation();

		$company = ( new Fields\Text() )
			->set_name( 'company' )
			->set_title( __( 'Organization', 'automatewoo' ) )
			->set_variable_validation();

		$this->add_field( $first_name );
		$this->add_field( $last_name );
		$this->add_field( $phone );
		$this->add_field( $company );
	}

	/**
	 * Add tags field to Action
	 *
	 * @return Fields\Text
	 */
	public function add_tags_field() {
		$tag = $this->get_tags_field()
					->set_description( __( 'Add multiple tags separated by commas. Please note that tags are not case-sensitive.', 'automatewoo' ) );

		$this->add_field( $tag );

		return $tag;
	}

	/**
	 * Get the ActiveCampaign integration
	 *
	 * @return Integration_ActiveCampaign
	 */
	public function activecampaign(): Integration_ActiveCampaign {
		if ( ! $this->activecampaign ) {
			$this->activecampaign = Integrations::activecampaign();
		}

		return $this->activecampaign;
	}
}
