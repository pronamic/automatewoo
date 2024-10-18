<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Registry\ItemConstructorArgsTrait;

/**
 * @class Variables
 * @since 2.4.6
 */
class Variables {

	use ItemConstructorArgsTrait;

	/** @var array */
	private static $loaded_variables = [];

	/** @var array */
	private static $variables_list;

	/** @var array */
	private static $included_variables = [
		'customer'          => [
			'email'           => Variable_Customer_Email::class,
			'first_name'      => Variable_Customer_First_Name::class,
			'last_name'       => Variable_Customer_Last_Name::class,
			'full_name'       => Variable_Customer_Full_Name::class,
			'order_count'     => Variable_Customer_Order_Count::class,
			'total_spent'     => Variable_Customer_Total_Spent::class,
			'address_line_1'  => Variable_Customer_Address_Line_1::class,
			'address_line_2'  => Variable_Customer_Address_Line_2::class,
			'country'         => Variable_Customer_Country::class,
			'state'           => Variable_Customer_State::class,
			'city'            => Variable_Customer_City::class,
			'postcode'        => Variable_Customer_Postcode::class,
			'phone'           => Variable_Customer_Phone::class,
			'company'         => Variable_Customer_Company::class,
			'generate_coupon' => Variable_Customer_Generate_Coupon::class,
			'meta'            => Variable_Customer_Meta::class,
			'user_id'         => Variable_Customer_User_ID::class,
			'username'        => Variable_Customer_Username::class,
			'tags'            => Variable_Customer_Tags::class,
			'unsubscribe_url' => Variable_Customer_Unsubscribe_URL::class,
		],
		'user'              => [],
		'order'             => [
			'id'                      => Variable_Order_ID::class,
			'number'                  => Variable_Order_Number::class,
			'status'                  => Variable_Order_Status::class,
			'date'                    => Variable_Order_Date::class,
			'date_paid'               => Variables\Order_Date_Paid::class,
			'date_completed'          => Variables\Order_Date_Completed::class,
			'subtotal'                => Variable_Order_Subtotal::class,
			'total'                   => Variable_Order_Total::class,
			'itemscount'              => Variable_Order_Itemscount::class,
			'items'                   => Variable_Order_Items::class,
			'cross_sells'             => Variable_Order_Cross_Sells::class,
			'related_products'        => Variable_Order_Related_Products::class,
			'billing_phone'           => Variable_Order_Billing_Phone::class,
			'billing_address'         => Variable_Order_Billing_Address::class,
			'shipping_address'        => Variable_Order_Shipping_Address::class,
			'view_url'                => Variable_Order_View_Url::class,
			'payment_url'             => Variable_Order_Payment_Url::class,
			'reorder_url'             => Variable_Order_Reorder_Url::class,
			'shipping_method'         => Variable_Order_Shipping_Method::class,
			'payment_method'          => Variable_Order_Payment_Method::class,
			'customer_note'           => Variable_Order_Customer_Note::class,
			'customer_details'        => Variable_Order_Customer_Details::class,
			'shipping_first_name'     => Variable_Order_Shipping_First_Name::class,
			'shipping_last_name'      => Variable_Order_Shipping_Last_Name::class,
			'shipping_address_line_1' => Variable_Order_Shipping_Address_Line_1::class,
			'shipping_address_line_2' => Variable_Order_Shipping_Address_Line_2::class,
			'shipping_city'           => Variable_Order_Shipping_City::class,
			'shipping_country'        => Variable_Order_Shipping_Country::class,
			'shipping_state'          => Variable_Order_Shipping_State::class,
			'shipping_postcode'       => Variable_Order_Shipping_Postcode::class,
			'shipping_company_name'   => Variable_Order_Shipping_Company_Name::class,
			'meta'                    => Variable_Order_Meta::class,
			'meta_date'               => Variable_Order_Meta_Date::class,
			'admin_url'               => Variable_Order_Admin_Url::class,
		],
		'refund'			=> [
			'amount' => Variable_Refund_Amount::class,
			'reason' => Variable_Refund_Reason::class,
		],
		'order_item'        => [
			'attribute' => Variable_Order_Item_Attribute::class,
			'meta'      => Variable_Order_Item_Meta::class,
			'quantity'  => Variable_Order_Item_Quantity::class,
		],
		'order_note'        => [
			'content' => Variable_Order_Note_Content::class,
		],
		'guest'             => [
			'email'           => Variable_Guest_Email::class,
			'generate_coupon' => Variable_Guest_Generate_Coupon::class,
			'first_name'      => Variable_Guest_First_Name::class,
			'last_name'       => Variable_Guest_Last_Name::class,
		],
		'review'            => [
			'content' => Variable_Review_Content::class,
			'rating'  => Variable_Review_Rating::class,
		],
		'comment'           => [
			'id'          => Variable_Comment_ID::class,
			'author_name' => Variables\CommentAuthorName::class,
			'author_ip'   => Variable_Comment_Author_IP::class,
			'content'     => Variable_Comment_Content::class,
		],
		'booking'           => [
			'id'         => Variables\BookingId::class,
			'cost'       => Variables\BookingCost::class,
			'resource'   => Variables\BookingResource::class,
			'status'     => Variables\BookingStatus::class,
			'persons'    => Variables\BookingPersons::class,
			'start_date' => Variables\BookingStartDate::class,
			'start_time' => Variables\BookingStartTime::class,
			'end_date'   => Variables\BookingEndDate::class,
			'end_time'   => Variables\BookingEndTime::class,
		],
		'product'           => [
			'id'                => Variable_Product_ID::class,
			'title'             => Variable_Product_Title::class,
			'current_price'     => Variable_Product_Current_Price::class,
			'regular_price'     => Variable_Product_Regular_Price::class,
			'featured_image'    => Variable_Product_Featured_Image::class,
			'permalink'         => Variable_Product_Permalink::class,
			'add_to_cart_url'   => Variable_Product_Add_To_Cart_Url::class,
			'sku'               => Variable_Product_Sku::class,
			'parent_sku'        => Variable_Product_Parent_Sku::class,
			'short_description' => Variable_Product_Short_Description::class,
			'description'       => Variable_Product_Description::class,
			'meta'              => Variable_Product_Meta::class,
			'meta_date'         => Variable_Product_Meta_Date::class,
		],
		'category'          => [
			'id'        => Variable_Category_ID::class,
			'title'     => Variable_Category_Title::class,
			'permalink' => Variable_Category_Permalink::class,
		],
		'wishlist'          => [
			'items'      => Variable_Wishlist_Items::class,
			'view_link'  => Variable_Wishlist_View_Link::class,
			'itemscount' => Variable_Wishlist_Itemscount::class,
		],
		'cart'              => [
			'id'         => Variables\CartId::class,
			'link'       => Variable_Cart_Link::class,
			'items'      => Variable_Cart_Items::class,
			'item_count' => Variable_Cart_Item_Count::class,
			'total'      => Variable_Cart_Total::class,
		],
		'subscription'      => [
			'id'                 => Variable_Subscription_ID::class,
			'status'             => Variable_Subscription_Status::class,
			'payment_method'     => Variable_Subscription_Payment_Method::class,
			'payment_count'      => Variables\Subscription_Payment_Count::class,
			'total'              => Variable_Subscription_Total::class,
			'view_order_url'     => Variable_Subscription_View_Order_Url::class,
			'start_date'         => Variable_Subscription_Start_Date::class,
			'next_payment_date'  => Variable_Subscription_Next_Payment_Date::class,
			'retry_payment_date' => Variable_Subscription_Retry_Payment_Date::class,
			'trial_end_date'     => Variable_Subscription_Trial_End_Date::class,
			'end_date'           => Variable_Subscription_End_Date::class,
			'last_payment_date'  => Variable_Subscription_Last_Payment_Date::class,
			'items'              => Variable_Subscription_Items::class,
			'billing_address'    => Variable_Subscription_Billing_Address::class,
			'shipping_address'   => Variable_Subscription_Shipping_Address::class,
			'meta'               => Variable_Subscription_Meta::class,
			'admin_url'          => Variable_Subscription_Admin_Url::class,
		],
		'subscription_item' => [
			'attribute' => Variables\Subscription_Item_Attribute::class,
			'meta'      => Variables\Subscription_Item_Meta::class,
			'quantity'  => Variables\Subscription_Item_Quantity::class,
		],
		'membership'        => [
			'id'           => Variable_Membership_ID::class,
			'plan_id'      => Variable_Membership_Plan_ID::class,
			'plan_name'    => Variable_Membership_Plan_Name::class,
			'status'       => Variable_Membership_Status::class,
			'date_started' => Variable_Membership_Date_Started::class,
			'date_expires' => Variable_Membership_Date_Expires::class,
			'renewal_url'  => Variable_Membership_Renewal_URL::class,
			'meta'         => Variable_Membership_Meta::class,
		],
		'card'              => [
			'type'         => Variable_Card_Type::class,
			'expiry_month' => Variable_Card_Expiry_Month::class,
			'expiry_year'  => Variable_Card_Expiry_Year::class,
			'last_four'    => Variable_Card_Last4::class,
		],
		'shop'              => [
			'title'            => Variable_Shop_Title::class,
			'tagline'          => Variable_Shop_Tagline::class,
			'url'              => Variable_Shop_Url::class,
			'admin_email'      => Variable_Shop_Admin_Email::class,
			'current_datetime' => Variable_Shop_Current_Datetime::class,
			'products'         => Variable_Shop_Products::class,
			'shop_url'         => Variables\Shop\ShopUrl::class,
		],
		'download'          => [
			'file_name' => Variable_Download_File_Name::class,
			'url'       => Variable_Download_URL::class,
		],
	];


	/**
	 * @return array
	 */
	static function get_list() {
		// cache the list after first generation
		if ( isset( self::$variables_list ) ) {
			return self::$variables_list;
		}

		$variables = self::$included_variables;

		if ( Integrations::is_subscriptions_active( '2.5' ) ) {
			$variables['subscription']['change_payment_method_url'] =
				Variable_Subscription_Change_Payment_Method_Url::class;
		}

		if ( class_exists( 'WC_Shipment_Tracking' ) ) {
			$variables['order']['tracking_number']   = Variable_Order_Tracking_Number::class;
			$variables['order']['tracking_url']      = Variable_Order_Tracking_Url::class;
			$variables['order']['date_shipped']      = Variable_Order_Date_Shipped::class;
			$variables['order']['shipping_provider'] = Variable_Order_Shipping_Provider::class;
		}

		/**
		 * @since 4.5.0
		 */
		if ( Integrations::is_subscriptions_active( '2.3' )
		     && class_exists( 'WCS_Early_Renewal_Manager' )
		     && \WCS_Early_Renewal_Manager::is_early_renewal_enabled()
		) {
			$variables['subscription']['early_renewal_url'] = Variable_Subscription_Early_Renewal_Url::class;
		}

		/*
		 * Sensei LMS variables.
		 *
		 * @since 5.6.10
		 */
		if ( Integrations::is_sensei_lms_active() ) {
			$variables['course'] = [
				'id'                 => Variable_Sensei_Course_ID::class,
				'title'              => Variable_Sensei_Course_Title::class,
				'url'                => Variable_Sensei_Course_URL::class,
				'results_url'        => Variable_Sensei_Course_Results_URL::class,
				'start_date'         => Variable_Sensei_Course_Start_Date::class,
				'students'           => Variable_Sensei_Course_Students::class,
				'students_admin_url' => Variable_Sensei_Course_Students_Admin_URL::class,
			];

			if ( Integrations::is_sensei_certificates_active() ) {
				$variables['course']['certificate_url'] = Variable_Sensei_Course_Certificate_URL::class;
			}

			$variables['teacher'] = [
				'email'      => Variable_Sensei_Teacher_Email::class,
				'first_name' => Variable_Sensei_Teacher_First_Name::class,
				'last_name'  => Variable_Sensei_Teacher_Last_Name::class,
				'full_name'  => Variable_Sensei_Teacher_Full_Name::class,
				'user_id'    => Variable_Sensei_Teacher_User_ID::class,
				'username'   => Variable_Sensei_Teacher_Username::class,
			];

			$variables['lesson'] = [
				'id'    => Variable_Sensei_Lesson_ID::class,
				'title' => Variable_Sensei_Lesson_Title::class,
				'url'   => Variable_Sensei_Lesson_URL::class,
			];

			$variables['quiz'] = [
				'id'       => Variable_Sensei_Quiz_ID::class,
				'title'    => Variable_Sensei_Quiz_Title::class,
				'grade'    => Variable_Sensei_Quiz_Grade::class,
				'passmark' => Variable_Sensei_Quiz_Passmark::class,
				'url'      => Variable_Sensei_Quiz_URL::class,
			];

			if ( Integrations::is_sensei_pro_active() ) {
				$variables['group'] = [
					'id'    => Variable_Sensei_Group_ID::class,
					'title' => Variable_Sensei_Group_Title::class,
				];
			}
		}

		self::$variables_list = apply_filters( 'automatewoo/variables', $variables );
		self::$variables_list = aw_array_move_to_end( self::$variables_list, 'shop' );
		return self::$variables_list;
	}


	/**
	 * Gets the classname for a variable but could also return a file.
	 *
	 * Files were used in the past. Started using class names in 5.0.0.
	 *
	 * @param string $data_type
	 * @param string $data_field
	 * @return false|string
	 */
	protected static function get_variable_classname_or_file( $data_type, $data_field ) {
		$list = self::get_list();

		if ( isset( $list[$data_type][$data_field] ) ) {
			return $list[$data_type][$data_field];
		}

		return false;
	}


	/**
	 * @param $variable_name string
	 * @return Variable|false
	 */
	static function get_variable( $variable_name ) {
		if ( isset( self::$loaded_variables[$variable_name] ) ) {
			return self::$loaded_variables[$variable_name];
		}

		list( $data_type, $data_field ) = explode( '.', $variable_name );

		$classname_or_file = self::get_variable_classname_or_file( $data_type, $data_field );

		if ( stristr( $classname_or_file, '.php' ) ) {
			// Load as file
			$file = $classname_or_file;

			if ( ! file_exists( $file ) ) {
				return false;
			}

			/** @var Variable $class */
			$class = require_once $file;

			if ( ! $class ) {
				return false;
			}
		} else {
			// Load as classname
			if ( ! $classname_or_file ) {
				return false;
			}
			$class = new $classname_or_file( ...static::get_item_constructor_args( $variable_name ) );
		}

		$class->setup( $variable_name );

		self::$loaded_variables[$variable_name] = $class;

		return $class;
	}

}
