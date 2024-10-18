<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Proxies\Bookings as BookingsProxy;
use AutomateWoo\Integrations;
use AutomateWoo\Registry;
use AutomateWoo\Wishlists;

defined( 'ABSPATH' ) || exit;

/**
 * Data types registry/service class.
 *
 * @since 2.4.6
 */
class DataTypes extends Registry {

	const BOOKING           = 'booking';
	const CARD              = 'card';
	const CART              = 'cart';
	const CATEGORY          = 'category';
	const COMMENT           = 'comment';
	const CUSTOMER          = 'customer';
	const DOWNLOAD          = 'download';
	const GUEST             = 'guest';
	const MEMBERSHIP        = 'membership';
	const ORDER             = 'order';
	const REFUND            = 'refund';
	const ORDER_ITEM        = 'order_item';
	const ORDER_NOTE        = 'order_note';
	const POST              = 'post';
	const PRODUCT           = 'product';
	const REVIEW            = 'review';
	const SENSEI_COURSE     = 'course';
	const SENSEI_TEACHER    = 'teacher';
	const SENSEI_LESSON     = 'lesson';
	const SENSEI_QUIZ       = 'quiz';
	const SENSEI_GROUP      = 'group';
	const SHOP              = 'shop';
	const SUBSCRIPTION      = 'subscription';
	const SUBSCRIPTION_ITEM = 'subscription_item';
	const TAG               = 'tag';
	const USER              = 'user';
	const WISHLIST          = 'wishlist';
	const WORKFLOW          = 'workflow';

	/** @var array */
	protected static $includes;

	/** @var array */
	protected static $loaded = [];

	/**
	 * @return array
	 */
	public static function load_includes() {
		$is_subscriptions_active = Integrations::is_subscriptions_active();
		$is_sensei_lms_active    = Integrations::is_sensei_lms_active();
		$is_sensei_pro_active    = Integrations::is_sensei_pro_active();

		return apply_filters(
			'automatewoo/data_types/includes',
			[
				self::BOOKING           => Integrations::is_bookings_active() ? Booking::class : null,
				self::CARD              => Card::class,
				self::CART              => Cart::class,
				self::CATEGORY          => ProductCategory::class,
				self::COMMENT           => Comment::class,
				self::CUSTOMER          => Customer::class,
				self::DOWNLOAD          => Download::class,
				self::GUEST             => Guest::class,
				self::MEMBERSHIP        => Integrations::is_memberships_enabled() ? Membership::class : null,
				self::ORDER_ITEM        => OrderItem::class,
				self::ORDER_NOTE        => OrderNote::class,
				self::ORDER             => Order::class,
				self::REFUND            => Refund::class,
				self::POST              => Post::class,
				self::PRODUCT           => Product::class,
				self::REVIEW            => Review::class,
				self::SENSEI_COURSE     => $is_sensei_lms_active ? SenseiCourse::class : null,
				self::SENSEI_TEACHER    => $is_sensei_lms_active ? SenseiTeacher::class : null,
				self::SENSEI_LESSON     => $is_sensei_lms_active ? SenseiLesson::class : null,
				self::SENSEI_QUIZ       => $is_sensei_lms_active ? SenseiQuiz::class : null,
				self::SENSEI_GROUP      => $is_sensei_lms_active && $is_sensei_pro_active ? SenseiGroup::class : null,
				self::SHOP              => Shop::class,
				self::SUBSCRIPTION_ITEM => $is_subscriptions_active ? SubscriptionItem::class : null,
				self::SUBSCRIPTION      => $is_subscriptions_active ? Subscription::class : null,
				self::TAG               => ProductTag::class,
				self::USER              => User::class,
				self::WISHLIST          => Wishlists::get_integration() ? Wishlist::class : null,
				self::WORKFLOW          => Workflow::class,
			]
		);
	}

	/**
	 * Get a data type object.
	 *
	 * @param string $data_type_id
	 *
	 * @return AbstractDataType|false
	 */
	public static function get( $data_type_id ) {
		return parent::get( $data_type_id );
	}

	/**
	 * Runs after a valid item is loaded.
	 *
	 * @param string           $data_type_id
	 * @param AbstractDataType $data_type
	 */
	public static function after_loaded( $data_type_id, $data_type ) {
		$data_type->set_id( $data_type_id );
	}

	/**
	 * Get data types that shouldn't be stored.
	 *
	 * @return array
	 */
	public static function get_non_stored_data_types() {
		return [ 'shop' ];
	}

	/**
	 * Check if a data type should be stored.
	 *
	 * @param string $data_type_id
	 *
	 * @return bool
	 * @since 5.1.0
	 */
	public static function is_non_stored_data_type( $data_type_id ) {
		return in_array( $data_type_id, self::get_non_stored_data_types(), true );
	}

	/**
	 * Checks that data type object is valid.
	 *
	 * @param mixed $item
	 *
	 * @return bool
	 * @since 4.9.0
	 */
	public static function is_item_valid( $item ) {
		return $item instanceof AbstractDataType;
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
			case self::BOOKING:
				return [ AW()->bookings_proxy() ];
		}

		return [];
	}
}
