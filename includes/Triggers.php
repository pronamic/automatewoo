<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Triggers\ManualInterface;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Triggers
 * @since 2.9
 */
class Triggers extends Registry {

	/** @var array  */
	static $includes;

	/** @var array  */
	static $loaded = [];


	/**
	 * @return array
	 */
	static function load_includes() {

		$includes = [
			'order_status_changes' => 'AutomateWoo\Trigger_Order_Status_Changes',
			'order_status_changes_each_line_item' => 'AutomateWoo\Trigger_Order_Status_Changes_Each_Line_Item',
			'order_placed' => 'AutomateWoo\Trigger_Order_Created',
			'order_placed_each_line_item' => 'AutomateWoo\Trigger_Order_Created_Each_Line_Item',
			'order_payment_received' => 'AutomateWoo\Trigger_Order_Paid',
			'order_payment_received_each_line_item' => 'AutomateWoo\Trigger_Order_Paid_Each_Line_Item',
			'order_processing' => 'AutomateWoo\Trigger_Order_Processing',
			'order_completed' => 'AutomateWoo\Trigger_Order_Completed',
			'order_cancelled' => 'AutomateWoo\Trigger_Order_Cancelled',
			'order_on_hold' => 'AutomateWoo\Trigger_Order_On_Hold',
			'order_refunded' => 'AutomateWoo\Trigger_Order_Refunded',
			'order_refunded_manual' => 'AutomateWoo\Trigger_Order_Refunded_Manual',
			'order_pending' => 'AutomateWoo\Trigger_Order_Pending',
			'order_note_added' => 'AutomateWoo\Trigger_Order_Note_Added',
			'order_note_added_each_line_item' => Triggers\OrderNoteAddedEachLineItem::class,

			'user_new_account' => 'AutomateWoo\Trigger_Customer_New_Account',
			'user_absent' => 'AutomateWoo\Trigger_Customer_Win_Back',
			'users_total_spend' => 'AutomateWoo\Trigger_Customer_Total_Spend_Reaches',
			'users_order_count_reaches' => 'AutomateWoo\Trigger_Customer_Order_Count_Reaches',

			'user_purchases_from_taxonomy_term' => 'AutomateWoo\Trigger_User_Purchases_From_Taxonomy_Term',
			'user_purchases_product_variation_with_attribute' => 'AutomateWoo\Trigger_User_Purchases_Product_Variation_With_Attribute'
		];

		$includes[ 'customer_before_saved_card_expiry' ] = 'AutomateWoo\Trigger_Customer_Before_Saved_Card_Expiry';
		$includes[ 'customer_opted_in' ] = 'AutomateWoo\Trigger_Customer_Opted_In';
		$includes[ 'customer_opted_out' ] = 'AutomateWoo\Trigger_Customer_Opted_Out';

		if ( Options::abandoned_cart_enabled() ) {
			$includes[ 'abandoned_cart_customer' ] = 'AutomateWoo\Trigger_Abandoned_Cart_Customer';
			$includes[ 'abandoned_cart' ] = 'AutomateWoo\Trigger_Abandoned_Cart_User';
			$includes[ 'guest_abandoned_cart' ] = 'AutomateWoo\Trigger_Abandoned_Cart_Guest';
		}

		// reviews
		$includes[ 'review_posted' ] = 'AutomateWoo\Trigger_Review_Posted';

		if ( Integrations::is_subscriptions_active() ) {
			$includes[ 'subscription_created' ] = 'AutomateWoo\Trigger_Subscription_Created';
			$includes[ 'subscription_created_each_line_item' ] = 'AutomateWoo\Triggers\Subscription_Created_Each_Line_Item';
			$includes[ 'subscription_status_changed' ] = 'AutomateWoo\Trigger_Subscription_Status_Changed';
			$includes[ 'subscription_status_changed_each_line_item' ] = 'AutomateWoo\Trigger_Subscription_Status_Changed_Each_Line_Item';
			$includes[ 'subscription_before_renewal' ] = 'AutomateWoo\Trigger_Subscription_Before_Renewal';
			$includes[ 'subscription_before_end' ] = 'AutomateWoo\Trigger_Subscription_Before_End';
			$includes[ 'subscription_payment_complete' ] = 'AutomateWoo\Trigger_Subscription_Payment_Complete';
			$includes[ 'subscription_payment_failed' ] = 'AutomateWoo\Trigger_Subscription_Payment_Failed';
			$includes[ 'subscription_trial_end' ] = 'AutomateWoo\Trigger_Subscription_Trial_End';
			$includes[ 'subscription_note_added' ] = 'AutomateWoo\Trigger_Subscription_Note_Added';
			$includes[ 'subscription_order_created' ] = 'AutomateWoo\Triggers\Subscription_Order_Created';
			$includes[ 'subscription_order_paid' ] = 'AutomateWoo\Triggers\Subscription_Order_Paid';
			$includes[ 'subscription_order_status_changes' ] = 'AutomateWoo\Triggers\Subscription_Order_Status_Changes';
			$includes[ 'subscription_manual' ] = Triggers\SubscriptionManual::class;
		}

		if ( Integrations::is_bookings_active() ) {
			$includes['booking_created']        = Triggers\BookingCreated::class;
			$includes['booking_status_changed'] = Triggers\BookingStatusChanged::class;
		}

		if ( Integrations::is_memberships_enabled() ) {
			$includes[ 'membership_created' ] = 'AutomateWoo\Trigger_Membership_Created';
			$includes[ 'membership_status_changed' ] = 'AutomateWoo\Trigger_Membership_Status_Changed';
		}

		if ( Integrations::is_mc4wp() ) {
			$includes[ 'mc4wp_form_submission' ] = 'AutomateWoo\Trigger_MC4WP_Form_Submission';
		}

		if ( $wishlist_integration = Wishlists::get_integration() ) {
			$includes[ 'wishlist_item_goes_on_sale' ] = 'AutomateWoo\Trigger_Wishlist_Item_Goes_On_Sale';
			$includes[ 'wishlist_reminder' ] = 'AutomateWoo\Trigger_Wishlist_Reminder';

			if ( $wishlist_integration == 'yith' ) {
				$includes[ 'wishlist_item_added' ] = 'AutomateWoo\Trigger_Wishlist_Item_Added';
			}
		}

		// Sensei LMS triggers.
		if ( Integrations::is_sensei_lms_active() ) {
			$includes['sensei_course_signed_up']                 = 'AutomateWoo\Trigger_Sensei_Course_Signed_Up';
			$includes['sensei_course_completed']                 = 'AutomateWoo\Trigger_Sensei_Course_Completed';
			$includes['sensei_lesson_started']                   = 'AutomateWoo\Trigger_Sensei_Lesson_Started';
			$includes['sensei_lesson_completed']                 = 'AutomateWoo\Trigger_Sensei_Lesson_Completed';
			$includes['sensei_quiz_completed']                   = 'AutomateWoo\Trigger_Sensei_Quiz_Completed';
			$includes['sensei_quiz_passed']                      = 'AutomateWoo\Trigger_Sensei_Quiz_Passed';
			$includes['sensei_quiz_failed']                      = 'AutomateWoo\Trigger_Sensei_Quiz_Failed';
			$includes['sensei_quiz_specific_answer_selected']    = 'AutomateWoo\Trigger_Sensei_Specific_Answer_Selected';
			$includes['sensei_course_completed_by_all_students'] = 'AutomateWoo\Trigger_Sensei_Course_Completed_By_All_Students';
			$includes['sensei_course_not_yet_completed']         = 'AutomateWoo\Trigger_Sensei_Course_Not_Yet_Completed';
			if ( Integrations::is_sensei_pro_active() ) {
				$includes['sensei_student_added_to_group']     = 'AutomateWoo\Trigger_Sensei_Student_Added_To_Group';
				$includes['sensei_student_removed_from_group'] = 'AutomateWoo\Trigger_Sensei_Student_Removed_From_Group';
			}
		}

		$includes[ 'workflow_times_run_reaches' ] = 'AutomateWoo\Trigger_Workflow_Times_Run_Reaches';
		$includes[ 'guest_created' ] = 'AutomateWoo\Trigger_Guest_Created';
		$includes[ 'order_manual' ] = Triggers\OrderManual::class;

		// Downloadable content triggers.
		$includes[ 'file_downloaded' ] = 'AutomateWoo\Trigger_File_Downloaded';
		$includes[ 'file_not_yet_downloaded' ] = 'AutomateWoo\Trigger_File_Not_Yet_Downloaded';
		$includes[ 'downloadable_product_purchased' ] = 'AutomateWoo\Trigger_Downloadable_Product_Purchased';

		return apply_filters( 'automatewoo/triggers', $includes );
	}

	/**
	 * Get a single trigger.
	 *
	 * @param string $name
	 *
	 * @return Trigger|false
	 */
	public static function get( $name ) {
		return parent::get( $name );
	}


	/**
	 * Get all triggers.
	 *
	 * @return Trigger[]
	 */
	public static function get_all() {
		return parent::get_all();
	}

	/**
	 * Get all currently active triggers.
	 *
	 * Active triggers are those currently in use on an active workflow.
	 *
	 * @since 4.6.0
	 *
	 * @return Trigger[]
	 */
	public static function get_all_active() {
		$triggers = [];

		foreach ( Active_Triggers_Cache::get_active_triggers() as $trigger_name ) {
			$trigger = self::get( $trigger_name );

			if ( $trigger ) {
				$triggers[ $trigger_name ] = $trigger;
			}
		}

		return $triggers;
	}

	/**
	 * Load and init all triggers
	 */
	public static function init() {
		self::get_all();

		if ( ! did_action('automatewoo_init_triggers') ) {
			do_action('automatewoo_init_triggers');
		}
	}

	/**
	 * Runs after a valid trigger is loaded.
	 *
	 * @param string  $name
	 * @param Trigger $trigger
	 */
	public static function after_loaded( $name, $trigger ) {
		$trigger->set_name( $name );
	}

	/**
	 * Checks that a trigger object is valid.
	 *
	 * @param mixed $item
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_item_valid( $item ) {
		return $item instanceof Trigger;
	}

	/**
	 * Returns array of trigger names.
	 *
	 * @since 3.8
	 *
	 * @return array
	 */
	static function get_custom_time_of_day_triggers() {
		$return = [];
		foreach ( self::get_all() as $trigger ) {
			if ( $trigger::SUPPORTS_CUSTOM_TIME_OF_DAY ) {
				$return[] = $trigger->get_name();
			}
		}
		return $return;
	}

	/**
	 * Get the constructor args for an item.
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	protected static function get_item_constructor_args( string $name ): array {
		switch ( $name ) {
			case 'booking_created':
			case 'booking_status_changed':
				return [ AW()->bookings_proxy() ];
		}

		return [];
	}

	/**
	 * Return manual triggers.
	 *
	 * @since 5.0.0
	 *
	 * @return Trigger[]|ManualInterface[]
	 */
	public static function get_manual_triggers() {
		return array_filter(
			self::get_all(),
			function ( $trigger ) {
				return $trigger instanceof ManualInterface;
			}
		);

	}

}
