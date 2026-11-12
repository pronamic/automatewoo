<?php

namespace AutomateWoo\Async_Events;

use AutomateWoo\Clean;
use AutomateWoo\Customer;
use AutomateWoo\Customer_Factory;
use AutomateWoo\Guest_Factory;
use AutomateWoo\Language;
use AutomateWoo\Session_Tracker;

defined( 'ABSPATH' ) || exit;

/**
 * Class MC4WP_Form_Success
 *
 * @since 4.8.0
 * @package AutomateWoo
 */
class MC4WP_Form_Success extends Abstract_Async_Event {

	/**
	 * Init the event.
	 */
	public function init() {
		add_action( 'mc4wp_form_success', [ $this, 'schedule_event' ] );
	}

	/**
	 * Get the async event hook name.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hook_name(): string {
		return 'automatewoo/mc4wp_form_success_async';
	}

	/**
	 * Schedule event.
	 *
	 * @param \MC4WP_Form $form
	 */
	public function schedule_event( $form ) {
		$form_data = $form->get_data();

		if ( empty( $form_data['EMAIL'] ) ) {
			return;
		}

		$guest_existed = (bool) Guest_Factory::get_by_email( Clean::email( $form_data['EMAIL'] ) );
		$customer      = Customer_Factory::get_by_email( $form_data['EMAIL'] );

		if ( ! $customer instanceof Customer ) {
			return;
		}

		if ( Language::is_multilingual() && $this->may_update_language( $customer, $guest_existed ) ) {
			$customer->update_language( Language::get_current() );
		}

		$this->create_async_event( [ $form->ID, $customer->get_id() ] );
	}

	/**
	 * Whether this form submission may set the customer's stored language.
	 *
	 * The form email is typed by the visitor, so it may belong to someone else.
	 * It may set the language when the visitor is verified as this customer, or
	 * when the submission has just created the customer's guest record.
	 *
	 * @since 6.9.6
	 *
	 * @param Customer $customer
	 * @param bool     $guest_existed Whether a guest with this email existed before the submission.
	 *
	 * @return bool
	 */
	private function may_update_language( Customer $customer, bool $guest_existed ): bool {
		if ( Session_Tracker::is_customer_identity_verified( $customer ) ) {
			return true;
		}

		return ! $customer->is_registered() && ! $guest_existed;
	}
}
