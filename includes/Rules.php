<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Rules\Rule;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Rules
 * @since 2.6
 */
class Rules extends Registry {

	/** @var array */
	static $includes;

	/** @var array  */
	static $loaded = [];


	/**
	 * @return array
	 */
	static function load_includes() {

		$includes = [
			'customer_is_guest'             => 'AutomateWoo\Rules\Customer_Is_Guest',
			'customer_email'                => 'AutomateWoo\Rules\Customer_Email',
			'customer_role'                 => 'AutomateWoo\Rules\Customer_Role',
			'customer_tags'                 => 'AutomateWoo\Rules\Customer_Tags',
			'customer_country'              => 'AutomateWoo\Rules\Customer_Country',
			'customer_state'                => 'AutomateWoo\Rules\Customer_State',
			'customer_state_text_match'     => 'AutomateWoo\Rules\Customer_State_Text_Match',
			'customer_postcode'             => 'AutomateWoo\Rules\Customer_Postcode',
			'customer_city'                 => 'AutomateWoo\Rules\Customer_City',
			'customer_phone'                => 'AutomateWoo\Rules\Customer_Phone',
			'customer_company'              => 'AutomateWoo\Rules\Customer_Company',
			'customer_order_count'          => 'AutomateWoo\Rules\Customer_Order_Count',
			'customer_total_spent'          => 'AutomateWoo\Rules\Customer_Total_Spent',
			'customer_review_count'         => 'AutomateWoo\Rules\Customer_Review_Count',
			'customer_first_order_date'     => 'AutomateWoo\Rules\Customer_First_Order_Date',
			'customer_last_order_date'      => 'AutomateWoo\Rules\Customer_Last_Order_Date',
			'customer_2nd_last_order_date'  => 'AutomateWoo\Rules\Customer_2nd_Last_Order_Date',
			'customer_order_statuses'       => 'AutomateWoo\Rules\Customer_Order_Statuses',
			'customer_purchased_products'   => 'AutomateWoo\Rules\Customer_Purchased_Products',
			'customer_purchased_categories' => 'AutomateWoo\Rules\Customer_Purchased_Categories',
			'customer_meta'                 => 'AutomateWoo\Rules\Customer_Meta',
			'customer_last_review_date'     => 'AutomateWoo\Rules\Customer_Last_Review_Date',
			'customer_account_created_date' => 'AutomateWoo\Rules\Customer_Account_Created_Date',

			'order_status'                 => 'AutomateWoo\Rules\Order_Status',
			'order_total'                  => Rules\OrderTotal::class,
			'order_items'                  => 'AutomateWoo\Rules\Order_Items',
			'order_item_categories'        => 'AutomateWoo\Rule_Order_Item_Categories',
			'order_item_tags'              => 'AutomateWoo\Rule_Order_Item_Tags',
			'order_items_text_match'       => 'AutomateWoo\Rules\Order_Items_Text_Match',
			'order_item_count'             => 'AutomateWoo\Rules\Order_Item_Count',
			'order_line_count'             => 'AutomateWoo\Rules\Order_Line_Count',
			'order_coupons'                => 'AutomateWoo\Rules\Order_Coupons',
			'order_coupons_text_match'     => 'AutomateWoo\Rules\Order_Coupons_Text_Match',
			'order_coupon_count'           => 'AutomateWoo\Rules\Order_Coupon_Count',
			'order_payment_gateway'        => 'AutomateWoo\Rule_Order_Payment_Gateway',
			'order_shipping_country'       => Rules\OrderShippingCountry::class,
			'order_billing_country'        => 'AutomateWoo\Rules\Order_Billing_Country',
			'order_shipping_method'        => 'AutomateWoo\Rules\Order_Shipping_Method',
			'order_shipping_method_string' => Rules\OrderShippingMethodString::class,
			'order_created_via'            => 'AutomateWoo\Rules\Order_Created_Via',
			'order_meta'                   => 'AutomateWoo\Rules\Order_Meta',
			'order_has_cross_sells'        => Rules\OrderHasCrossSells::class,
			'order_is_customers_first'     => Rules\OrderIsCustomersFirst::class,
			'order_is_guest_order'         => 'AutomateWoo\Rules\Order_Is_Guest_Order',
			'order_customer_provided_note' => 'AutomateWoo\Rules\Order_Customer_Provided_Note',
			'order_paid_date'              => 'AutomateWoo\Rules\Order_Paid_Date',
			'order_created_date'           => 'AutomateWoo\Rules\Order_Created_Date',

			'review_rating' => 'AutomateWoo\Rules\Review_Rating',

			'product'            => 'AutomateWoo\Rules\Product',
			'product_categories' => 'AutomateWoo\Rules\Product_Categories',

			'order_item_product'   => 'AutomateWoo\Rules\Order_Item_Product',
			'order_item_quantity'  => 'AutomateWoo\Rules\Order_Item_Quantity',
			'order_item_total'     => 'AutomateWoo\Rules\Order_Item_Total',
			'order_item_subtotal'  => 'AutomateWoo\Rules\Order_Item_Subtotal',
			'order_item_tax_total' => 'AutomateWoo\Rules\Order_Item_Tax_Total',
			'order_item_meta'      => 'AutomateWoo\Rules\Order_Item_Meta',

			'cart_total'           => Rules\CartTotal::class,
			'cart_count'           => Rules\CartItemCount::class,
			'cart_items'           => 'AutomateWoo\Rules\Cart_Items',
			'cart_item_categories' => 'AutomateWoo\Rules\Cart_Item_Categories',
			'cart_item_tags'       => 'AutomateWoo\Rules\Cart_Item_Tags',
			'cart_coupons'         => 'AutomateWoo\Rules\Cart_Coupons',
			'cart_created_date'    => 'AutomateWoo\Rules\Cart_Created_Date',

			'guest_email'       => Rules\GuestEmail::class,
			'guest_order_count' => Rules\GuestOrderCount::class,

			'customer_run_count'              => 'AutomateWoo\Rules\Customer_Run_Count',
			'workflow_last_customer_run_date' => 'AutomateWoo\Rules\Workflow_Last_Customer_Run_Date',
			'order_run_count'                 => Rules\OrderRunCount::class,
			'guest_run_count'                 => Rules\GuestRunCount::class,

			'shop_current_datetime' => Rules\ShopCurrentDateTime::class,
		];

		if ( Integrations::is_subscriptions_active() ) {
			$includes['customer_has_active_subscription'] = 'AutomateWoo\Rules\Customer_Has_Active_Subscription';
			$includes['order_is_subscription_renewal']    = 'AutomateWoo\Rules\Order_Is_Subscription_Renewal';
			$includes['order_is_subscription_parent']     = 'AutomateWoo\Rules\Order_Is_Subscription_Parent';
			$includes['order_subscription_order_type']    = 'AutomateWoo\Rules\Order_Subscription_Order_Type';

			if ( class_exists( 'WCS_Retry_Manager' ) && \WCS_Retry_Manager::is_retry_enabled() ) {
				$includes['order_subscription_payment_retry_count'] = 'AutomateWoo\Rules\Order_Subscription_Failed_Automatic_Payment_Retry_Count';
			}

			$includes['subscription_status']             = 'AutomateWoo\Rules\Subscription_Status';
			$includes['subscription_payment_count']      = Rules\SubscriptionPaymentCount::class;
			$includes['subscription_payment_method']     = 'AutomateWoo\Rules\Subscription_Payment_Method';
			$includes['subscription_meta']               = 'AutomateWoo\Rules\Subscription_Meta';
			$includes['subscription_items']              = 'AutomateWoo\Rules\Subscription_Items';
			$includes['subscription_item_categories']    = Rules\Subscription_Item_Categories::class;
			$includes['subscription_coupons']            = 'AutomateWoo\Rules\Subscription_Coupons';
			$includes['subscription_coupons_text_match'] = 'AutomateWoo\Rules\Subscription_Coupons_Text_Match';
			$includes['subscription_coupon_count']       = 'AutomateWoo\Rules\Subscription_Coupon_Count';
			$includes['subscription_next_payment_date']  = 'AutomateWoo\Rules\Subscription_Next_Payment_Date';
			$includes['subscription_last_payment_date']  = 'AutomateWoo\Rules\Subscription_Last_Payment_Date';
			$includes['subscription_created_date']       = 'AutomateWoo\Rules\Subscription_Created_Date';
			$includes['subscription_trial_end_date']     = 'AutomateWoo\Rules\Subscription_Trial_End_Date';
			$includes['subscription_end_date']           = 'AutomateWoo\Rules\Subscription_End_Date';
			$includes['subscription_run_count']           = Rules\Subscription_Run_Count::class;

			/**
			 * @since 4.5.0
			 */
			if ( Integrations::is_subscriptions_active( '2.3' ) ) {
				$includes['subscription_can_renew_early'] = 'AutomateWoo\Rules\Subscription_Can_Renew_Early';
			}

			if ( Integrations::is_subscriptions_active( '2.5' ) ) {
				$includes['subscription_has_payment_method'] = 'AutomateWoo\Rules\Subscription_Has_Payment_Method';
			}

			$includes['subscription_requires_manual_renewal'] = 'AutomateWoo\Rules\Subscription_Requires_Manual_Renewal';
		}

		if ( Integrations::is_memberships_enabled() ) {
			$includes['customer_active_membership_plans'] = 'AutomateWoo\Rules\Customer_Active_Membership_Plans';
		}

		if ( Integrations::is_woo_pos() ) {
			$includes['order_is_pos'] = Rules\OrderIsPos::class;
		}

		if ( Options::mailchimp_enabled() ) {
			$includes['customer_is_mailchimp_subscriber'] = 'AutomateWoo\Rules\Customer_Is_Mailchimp_Subscriber';
		}

		if ( Integrations::is_sensei_lms_active() ) {
			$includes['sensei_have_not_started_first_lesson'] = 'AutomateWoo\Rules\Sensei_Have_Not_Started_First_Lesson';
			$includes['sensei_have_not_completed_lesson']     = 'AutomateWoo\Rules\Sensei_Have_Not_Completed_Lesson';
			$includes['sensei_have_not_completed_course']     = 'AutomateWoo\Rules\Sensei_Have_Not_Completed_Course';
			$includes['sensei_have_not_yet_taken_quiz']       = 'AutomateWoo\Rules\Sensei_Have_Not_Yet_Taken_Quiz';
			$includes['sensei_have_failed_quiz']              = 'AutomateWoo\Rules\Sensei_Have_Failed_Quiz';
			$includes['sensei_have_passed_quiz']              = 'AutomateWoo\Rules\Sensei_Have_Passed_Quiz';
		}

		return apply_filters( 'automatewoo/rules/includes', $includes );
	}


	/**
	 * @return Rule[]
	 */
	static function get_all() {
		return parent::get_all();
	}


	/**
	 * @param $rule_name
	 * @return Rule|false
	 */
	static function get( $rule_name ) {
		return parent::get( $rule_name );
	}


	/**
	 * @param string $rule_name
	 * @param Rule   $rule
	 */
	static function after_loaded( $rule_name, $rule ) {
		$rule->name = $rule_name;
	}

	/**
	 * Checks that an item is valid.
	 *
	 * @param mixed $item
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public static function is_item_valid( $item ) {
		return $item instanceof Rule;
	}

}
