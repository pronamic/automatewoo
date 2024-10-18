<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Actions\PreviewableInterface;
use AutomateWoo\Actions\TestableInterface;
use AutomateWoo\Exceptions\InvalidPreviewData;
use AutomateWoo\Workflows\Factory;
use WC_Order;
use WP_Query;

/**
 * @class Preview_Data
 * @since 2.4.6
 */
class Preview_Data {

	/**
	 * The current preview workflow.
	 *
	 * @since 4.6.0
	 *
	 * @var Workflow
	 */
	private static $preview_workflow;

	/**
	 * @param array $required_items list of specific data items to get for preview
	 *
	 * @return array
	 * @throws InvalidPreviewData When there's an error getting the preview data.
	 */
	static function get_preview_data_layer( $required_items = [] ) {
		$data_layer = [];

		if ( in_array( 'user', $required_items ) ) {
			$data_layer['user'] = wp_get_current_user();
		}

		if ( in_array( 'customer', $required_items ) ) {
			$data_layer['customer'] = Customer_Factory::get_by_user_id( get_current_user_id() );
		}

		/**
		 * Order and order item
		 */
		if ( in_array( 'order', $required_items ) || in_array( 'order_item', $required_items ) ) {
			$order = self::get_preview_order();
			$order_items = $order->get_items();

			if ( empty( $order_items ) ) {
				throw InvalidPreviewData::data_item_needed( 'order_item' );
			}

			$data_layer['order'] = $order;
			$data_layer['order_item'] = current( $order_items );
		}

		/**
		 * Product
		 */
		if ( in_array( 'product', $required_items ) ) {
			$product_ids = self::get_preview_product_ids();
			$data_layer['product'] = wc_get_product( $product_ids[0] );
		}

		/**
		 * Category
		 */
		if ( in_array( 'category', $required_items ) ) {
			$categories = get_terms(
				[
					'taxonomy' => 'product_cat',
					'order'    => 'count',
					'number'   => 1
				]
			);

			if ( empty( $categories ) ) {
				throw InvalidPreviewData::data_item_needed( 'category' );
			}

			$data_layer['category'] = current( $categories );
		}

		/**
		 * Cart
		 */
		if ( in_array( 'cart', $required_items ) ) {
			$cart = new Cart();
			$cart->set_id( 1 );
			$cart->set_total( 100 );
			$cart->set_user_id( get_current_user_id() );
			$cart->set_token();
			$cart->set_date_last_modified( new DateTime() );

			$product_ids = self::get_preview_product_ids( array( 'post_status' => 'publish' ) );
			$items 		 = [];

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				// Ensure only published products are included.
				if ( ! is_a( $product, 'WC_Product' ) || $product->get_status() !== 'publish' ) {
					continue;
				}

				$variation_id = 0;
				$variation = [];

				if ( $product->is_type('variable') ) {
					/** @var \WC_Product_Variable $product */
					$variations = $product->get_available_variations();
					if ( $variations ) {
						$variation_id = $variations[0]['variation_id'];
						$variation = $variations[0]['attributes'];
					}
				}

				$items[ uniqid() ] = [
					'product_id' => $product_id,
					'variation_id' => $variation_id,
					'variation' => $variation,
					'quantity' => 1,
					'line_subtotal' => (float) $product->get_price(),
					'line_subtotal_tax' => (float) wc_get_price_including_tax( $product ) - (float) $product->get_price(),
				];

			}

			if ( empty( $items ) ) {
				throw InvalidPreviewData::data_item_needed( 'product' );
			}

			$cart->set_items( $items );

			$cart->set_coupons([
				'10off' => [
					'discount_incl_tax' => '10',
					'discount_excl_tax' => '9',
					'discount_tax' => '1'
				]
			]);

			$data_layer['cart'] = $cart;
		}

		/**
		 * Wishlist
		 */
		if ( in_array( 'wishlist', $required_items ) ) {
			$wishlist        = new Wishlist();
			$wishlist->items = self::get_preview_product_ids();

			$data_layer['wishlist'] = $wishlist;
		}

		/**
		 * Guest
		 */
		if ( in_array( 'guest', $required_items ) ) {
			$guest = new Guest();
			$guest->set_email( 'guest@example.com' );
			$data_layer['guest'] = $guest;
		}

		/**
		 * Subscription
		 */
		if ( Integrations::is_subscriptions_active() && in_array( 'subscription', $required_items ) ) {
			$subscriptions = wcs_get_subscriptions(
				[
					'subscriptions_per_page' => 1
				]
			);

			if ( empty( $subscriptions ) ) {
				throw InvalidPreviewData::data_item_needed( 'subscription' );
			}

			$data_layer['subscription'] = current( $subscriptions );
		}

		/**
		 * Membership
		 */
		if ( Integrations::is_memberships_enabled() && in_array( 'membership', $required_items ) ) {
			$memberships = get_posts(
				[
					'post_type'      => 'wc_user_membership',
					'post_status'    => 'any',
					'posts_per_page' => 1,
				]
			);

			if ( empty( $memberships ) ) {
				throw InvalidPreviewData::data_item_needed( 'membership' );
			}

			$data_layer['membership'] = wc_memberships_get_user_membership( current( $memberships ) );
		}

		/**
		 * Bookings
		 */
		if ( Integrations::is_bookings_active() && in_array( 'booking', $required_items ) ) {
			try {
				$booking               = AW()->bookings_proxy()->get_most_recent_booking();
				$data_layer['booking'] = $booking;
				$data_layer['product'] = $booking->get_product();
			} catch ( \Exception $e ) {
				// rethrow as user-facing InvalidPreviewData exception
				throw InvalidPreviewData::data_item_needed( 'booking' );
			}
		}

		/**
		 * Sensei Integration
		 */
		if ( Integrations::is_sensei_lms_active() ) {
			if ( in_array( 'course', $required_items, true ) ) {
				$data_layer['course'] = self::get_preview_course();
			}

			if ( in_array( 'lesson', $required_items, true ) ) {
				$lessons = get_posts(
					[
						'post_type'      => 'lesson',
						'posts_per_page' => 1,
					]
				);

				if ( empty( $lessons ) ) {
					throw InvalidPreviewData::data_item_needed( 'lesson' );
				}

				$data_layer['lesson'] = current( $lessons );
			}

			if ( in_array( 'quiz', $required_items, true ) ) {
				$quizzes = get_posts(
					[
						'post_type'      => 'quiz',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
					]
				);

				if ( empty( $quizzes ) ) {
					throw InvalidPreviewData::data_item_needed( 'quiz' );
				}

				$data_layer['quiz'] = get_post( current( $quizzes ) );
			}

			if ( in_array( 'teacher', $required_items, true ) ) {
				$course  = self::get_preview_course();
				$teacher = get_user_by( 'id', $course->post_author );
				if ( $teacher ) {
					$data_layer['teacher'] = $teacher;
				} else {
					$data_layer['teacher'] = wp_get_current_user();
				}
			}

			if ( in_array( 'group', $required_items, true ) ) {
				$groups = get_posts(
					[
						'post_type'      => 'group',
						'posts_per_page' => 1,
					]
				);

				if ( empty( $groups ) ) {
					throw InvalidPreviewData::data_item_needed( 'group' );
				}

				$data_layer['group'] = current( $groups );
			}
		}

		/**
		 * Card
		 */
		if ( in_array( 'card', $required_items ) ) {
			$token = new \WC_Payment_Token_CC();
			$token->set_user_id( 0 );
			$token->set_card_type('visa');
			$token->set_expiry_month( 04 );
			$token->set_expiry_year( 2020 );
			$token->set_last4( 1234 );

			$data_layer['card'] = $token;
		}

		/**
		 * Download
		 */
		if ( in_array( 'download', $required_items, true ) ) {
			$data_store = \WC_Data_Store::load( 'customer-download' );
			$downloads  = $data_store->get_downloads(
				array(
					'limit' => 1,
					'order' => 'DESC',
				)
			);
			if ( ! empty( $downloads ) ) {
				$download               = current( $downloads );
				$data_layer['download'] = new Download( $download->get_download_id(), $download->get_product_id(), $download->get_order_id() );
			}
		}

		return apply_filters( 'automatewoo/preview_data_layer', $data_layer, $required_items );
	}


	/**
	 * Generate a previewable or testable action object filled with preview data.
	 *
	 * @param int    $workflow_id
	 * @param int    $action_number
	 * @param string $mode test|preview
	 *
	 * @return Action|PreviewableInterface|TestableInterface
	 *
	 * TODO Remove Action return type when code-hinting isn't required for public Action props
	 *
	 * @throws InvalidPreviewData When there's an error with the preview data.
	 */
	static function generate_preview_action( $workflow_id, $action_number, $mode = 'preview' ) {
		$preview_data = wp_unslash( get_option( 'aw_wf_preview_data_' . $workflow_id ) );
		$workflow = Factory::get( $workflow_id );
		if ( ! $workflow || ! $action_number || ! is_array( $preview_data ) ) {
			throw InvalidPreviewData::generic();
		}

		$trigger = Triggers::get( Clean::string( $preview_data['trigger_name'] ) );
		$action_fields = $workflow->format_action_fields( $preview_data['action_fields'] );
		if ( ! $trigger || ! $action_fields ) {
			throw InvalidPreviewData::generic();
		}

		try {
			$action = clone $workflow->get_action_from_action_fields( $action_fields );
		} catch ( Exception $e ) {
			throw InvalidPreviewData::generic();
		}

		if ( ! $action instanceof PreviewableInterface && ! $action instanceof TestableInterface ) {
			throw InvalidPreviewData::invalid_action();
		}

		// add the workflow in preview mode
		if ( $mode === 'test' ) {
			$workflow->enable_test_mode();
		} else {
			$workflow->enable_preview_mode();
		}

		// set the data layer from preview trigger
		$workflow->set_data_layer( Preview_Data::get_preview_data_layer( $trigger->get_supplied_data_items() ), true );

		$action->workflow = $workflow;

		// replace saved options with live preview data
		$action->set_options( $action_fields );

		return $action;
	}


	/**
	 * Get an order for preview.
	 *
	 * @param int $offset used to do multiple attempts to get a valid order
	 *
	 * @return WC_Order
	 * @throws InvalidPreviewData When no preview order is found.
	 */
	protected static function get_preview_order( $offset = 0 ) {
		if ( $offset > 8 ) {
			throw InvalidPreviewData::order_not_found();
		}

		$orders = wc_get_orders(
			[
				'type'   => 'shop_order',
				'limit'  => 1,
				'offset' => $offset,
				'return' => 'ids',
			]
		);

		if ( ! $orders ) {
			throw InvalidPreviewData::order_not_found();
		}

		$order = wc_get_order( $orders[0] );

		// if the order has a blank email, it will cause issues
		if ( $order && $order->get_billing_email() ) {
			return $order;
		}

		return self::get_preview_order( $offset + 1 );
	}

	/**
	 * Add customer language filter.
	 *
	 * @since 4.6.0
	 *
	 * @param Action $action
	 */
	public static function add_customer_language_filter( $action ) {
		self::$preview_workflow = $action->workflow;
		add_filter( 'automatewoo/customer/get_language', [ __CLASS__, 'filter_customer_language' ], 10, 2 );
	}

	/**
	 * Filter the current customers language when previewing.
	 * Make it the same as the preview workflow language.
	 *
	 * @since 4.6.0
	 *
	 * @param string $lang
	 * @param Customer $customer
	 *
	 * @return string
	 */
	public static function filter_customer_language( $lang, $customer ) {
		if ( ! self::$preview_workflow ) {
			return $lang;
		}

		$workflow_customer = self::$preview_workflow->data_layer()->get_customer();

		// Only filter the language of the customer in the preview workflow.
		if ( $workflow_customer && $customer->get_id() === $workflow_customer->get_id() ) {
			return (string) self::$preview_workflow->get_language();
		}

		return $lang;
	}

	/**
	 * Remove customer language filter.
	 *
	 * @since 4.6.0
	 *
	 * @param Action $action
	 */
	public static function remove_customer_language_filter( $action ) {
		self::$preview_workflow = null;
		remove_filter( 'automatewoo/customer/get_language', [ __CLASS__, 'filter_customer_language' ], 10 );
	}

	/**
	 * Get preview products.
	 *
	 * @param array $args Optional array of arguments to pass to WP_Query.
	 *
	 * @return array
	 *
	 * @throws InvalidPreviewData When no products found.
	 */
	protected static function get_preview_product_ids( $args = array() ): array {
		// Cache for request since this may be called multiple times
		static $products = null;
		if ( null === $products ) {
			$args = array_merge( array(
				'post_type'      => 'product',
				'posts_per_page' => 4,
				'fields'         => 'ids'
			), $args );

			$product_query = new WP_Query( $args );

			$products = $product_query->posts;
			if ( empty( $products ) ) {
				throw InvalidPreviewData::data_item_needed( 'product' );
			}
		}

		return $products;
	}

	/**
	 * Get a course for preview.
	 *
	 * @return \WP_Post
	 * @throws InvalidPreviewData When no preview course is found.
	 */
	protected static function get_preview_course() {
		$courses = get_posts(
			[
				'post_type'      => 'course',
				'posts_per_page' => 1,
			]
		);

		if ( empty( $courses ) ) {
			throw InvalidPreviewData::data_item_needed( 'course' );
		}

		return current( $courses );
	}

}
