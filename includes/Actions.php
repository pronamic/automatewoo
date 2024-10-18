<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Actions
 * @since 2.9
 */
class Actions extends Registry {

	/** @var array */
	static $includes;

	/** @var array  */
	static $loaded = [];


	/**
	 * @return array
	 */
	static function load_includes() {

		$includes = [
			'send_email' => 'AutomateWoo\Action_Send_Email',
			'send_email_plain' => 'AutomateWoo\Action_Send_Email_Plain_Text',
			'send_email_raw' => 'AutomateWoo\Action_Send_Email_Raw',

			'customer_change_role' => 'AutomateWoo\Action_Customer_Change_Role',
			'customer_update_meta' => 'AutomateWoo\Action_Customer_Update_Meta',
			'customer_add_tags' => 'AutomateWoo\Action_Customer_Add_Tags',
			'customer_remove_tags' => 'AutomateWoo\Action_Customer_Remove_Tags',

			'change_order_status' => 'AutomateWoo\Action_Order_Change_Status',
			'update_order_meta' => 'AutomateWoo\Action_Order_Update_Meta',
			'resend_order_email' => 'AutomateWoo\Action_Order_Resend_Email',
			'trigger_order_action' => 'AutomateWoo\Action_Order_Trigger_Action',
			'order_update_customer_shipping_note' => 'AutomateWoo\Action_Order_Update_Customer_Shipping_Note',
			'order_add_note' => 'AutomateWoo\Action_Order_Add_Note',
		];

		$includes['order_item_update_meta'] = 'AutomateWoo\Action_Order_Item_Update_Meta';

		if ( AW()->options()->twilio_integration_enabled ) {
			$includes[ 'send_sms_twilio' ] = 'AutomateWoo\Action_Send_SMS_Twilio';
		}

		if ( Integrations::is_subscriptions_active() ) {
			$includes['change_subscription_status']            = Action_Subscription_Change_Status::class;
			$includes['subscription_update_meta']              = Action_Subscription_Update_Meta::class;
			$includes['subscription_send_invoice']             = Action_Subscription_Send_Invoice::class;
			$includes['subscription_update_schedule']          = Actions\Subscriptions\UpdateSchedule::class;
			$includes['subscription_add_product']              = Action_Subscription_Add_Product::class;
			$includes['subscription_update_product']           = Actions\Subscriptions\UpdateProduct::class;
			$includes['subscription_remove_product']           = Action_Subscription_Remove_Product::class;
			$includes['subscription_add_note']                 = Action_Subscription_Add_Note::class;
			$includes['subscription_add_coupon']               = Action_Subscription_Add_Coupon::class;
			$includes['subscription_remove_coupon']            = Action_Subscription_Remove_Coupon::class;
			$includes['subscription_add_shipping']             = Actions\Subscriptions\AddShipping::class;
			$includes['subscription_update_shipping']          = Actions\Subscriptions\UpdateShipping::class;
			$includes['subscription_remove_shipping']          = Actions\Subscriptions\RemoveShipping::class;
			$includes['subscription_update_currency']          = Actions\Subscriptions\UpdateCurrency::class;
			$includes['subscription_update_end_date']          = Actions\Subscriptions\UpdateEndDate::class;
			$includes['subscription_update_trial_end_date']    = Actions\Subscriptions\UpdateTrialEndDate::class;
			$includes['subscription_update_next_payment_date'] = Actions\Subscriptions\UpdateNextPaymentDate::class;
			$includes['subscription_recalculate_taxes']        = Actions\Subscriptions\RecalculateTaxes::class;
			$includes['subscription_regenerate_downloads']     = Actions\Subscriptions\RegenerateDownloadPermissions::class;
		}

		if ( Integrations::is_memberships_enabled() ) {
			$includes[ 'memberships_change_plan' ] = 'AutomateWoo\Action_Memberships_Change_Plan';
			$includes[ 'memberships_delete_user_membership' ] = 'AutomateWoo\Action_Memberships_Delete_User_Membership';
			$includes[ 'memberships_change_status' ] = 'AutomateWoo\Action_Memberships_Change_Status';
		}

		if ( Options::mailchimp_enabled() ) {
			$includes[ 'mailchimp_subscribe' ] = 'AutomateWoo\Action_Mailchimp_Subscribe';
			$includes[ 'mailchimp_unsubscribe' ] = 'AutomateWoo\Action_Mailchimp_Unsubscribe';
			$includes[ 'mailchimp_update_contact_field' ] = 'AutomateWoo\Action_Mailchimp_Update_Contact_Field';
			$includes[ 'mailchimp_add_to_group' ] = 'AutomateWoo\Action_Mailchimp_Add_To_Group';
			$includes[ 'mailchimp_remove_from_group' ] = 'AutomateWoo\Action_Mailchimp_Remove_From_Group';
			$includes[ 'mailchimp_update_tags' ] = 'AutomateWoo\Actions\Mailchimp_Update_Tags';
		}

		if ( Integrations::is_mailpoet_api_active() ) {
			$includes[ 'mailpoet_subscribe' ] = 'AutomateWoo\Action_Mailpoet_Subscribe';
			$includes[ 'mailpoet_unsubscribe' ] = 'AutomateWoo\Action_Mailpoet_Unsubscribe';
		}

		if ( AW()->options()->campaign_monitor_enabled ) {
			$includes[ 'campaign_monitor_add_subscriber' ] = 'AutomateWoo\Action_Campaign_Monitor_Add_Subscriber';
			$includes[ 'campaign_monitor_remove_subscriber' ] = 'AutomateWoo\Action_Campaign_Monitor_Remove_Subscriber';
		}

		if ( AW()->options()->active_campaign_integration_enabled ) {
			$includes[ 'add_user_to_active_campaign_list' ] = 'AutomateWoo\Action_Active_Campaign_Create_Contact';
			$includes[ 'active_campaign_add_tag' ] = 'AutomateWoo\Action_Active_Campaign_Add_Tag';
			$includes[ 'active_campaign_remove_tag' ] = 'AutomateWoo\Action_Active_Campaign_Remove_Tag';
			$includes[ 'active_campaign_update_custom_field' ] = 'AutomateWoo\Action_Active_Campaign_Update_Contact_Field';
		}

		$includes[ 'clear_queued_events' ] = 'AutomateWoo\Action_Clear_Queued_Events';
		$includes[ 'change_workflow_status' ] = 'AutomateWoo\Action_Change_Workflow_Status';

		$includes[ 'custom_function' ] = 'AutomateWoo\Action_Custom_Function';
		$includes[ 'update_product_meta' ] = 'AutomateWoo\Action_Update_Product_Meta';
		$includes[ 'change_post_status' ] = 'AutomateWoo\Action_Change_Post_Status';

		$includes[ 'add_to_mad_mimi_list' ] = 'AutomateWoo\Action_Add_To_Mad_Mimi_List';

		return apply_filters( 'automatewoo/actions', $includes );
	}

	/**
	 * Get all actions.
	 *
	 * @return Action[]
	 */
	public static function get_all() {
		return parent::get_all();
	}

	/**
	 * Get a single action.
	 *
	 * @param string $name
	 *
	 * @return Action|false
	 */
	public static function get( $name ) {
		return parent::get( $name );
	}

	/**
	 * Runs after a valid action is loaded.
	 *
	 * @param string $action_name
	 * @param Action $action
	 */
	public static function after_loaded( $action_name, $action ) {
		$action->set_name( $action_name );
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
		return $item instanceof Action;
	}

}
