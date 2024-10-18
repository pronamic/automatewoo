<?php

namespace AutomateWoo\Actions\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define shared methods to add, remove or update date fields on a subscription.
 *
 * @since 5.5.17
 */
abstract class AbstractEditDateItem extends AbstractEditItem {

	/**
	 * @var string Date field name to update for this subscription.
	 */
	protected $date_field = '';

	/**
	 * @var string Subscription date to update.
	 */
	protected $subscription_date = '';

	/**
	 * Edit the item managed by this class on the subscription passed in the workflow's trigger
	 *
	 * @throws \Exception When there is an error.
	 */
	public function run() {
		$object       = $this->get_object_for_edit();
		$subscription = $this->get_subscription_to_edit();

		if ( ! $subscription ) {
			return;
		}

		$edited = $this->edit_subscription( $object, $subscription );
		if ( $edited ) {
			$this->add_note( $object, $subscription );
		}
	}

	/**
	 * Method to get the new date for the subscription.
	 *
	 * @return string MySQL date/time string representation of the DateTime object in UTC timezone.
	 */
	protected function get_object_for_edit() {
		$new_date = wcs_get_datetime_from(
			sprintf(
				'%1$s %2$s:00',
				$this->get_option( "new_{$this->date_field}_date" ),
				implode( ':', $this->get_option( "new_{$this->date_field}_time" ) )
			)
		);

		return $new_date ? wcs_get_datetime_utc_string( $new_date ) : 0;
	}

	/**
	 * Edit the item managed by this class on the subscription passed in the workflow's trigger
	 *
	 * @param string           $new_date     Date string.
	 * @param \WC_Subscription $subscription Instance of the subscription being edited by this action.
	 *
	 * @throws \Exception When there is an error.
	 *
	 * @return bool True if the subscription was edited, false if no change was made.
	 */
	public function edit_subscription( $new_date, $subscription ) {
		$subscription->update_dates( array( $this->subscription_date => $new_date ) );

		return true;
	}
}
