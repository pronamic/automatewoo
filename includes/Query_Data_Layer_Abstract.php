<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @class Query_Data_Layer_Abstract
 * @since 3.8
 */
abstract class Query_Data_Layer_Abstract extends Query_Abstract {

	/**
	 * @param string $data_type
	 * @return string
	 */
	abstract public function get_data_layer_meta_key( $data_type );

	/**
	 * @param string $data_type
	 * @param mixed  $data_object
	 * @return string
	 */
	abstract public function get_data_layer_meta_value( $data_type, $data_object );

	/**
	 * @param string      $data_type
	 * @param mixed       $data_value
	 * @param bool|string $compare - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	public function where_data_layer( $data_type, $data_value, $compare = false ) {
		if ( ! $data_value ) {
			return $this;
		}

		// if data value is in object form try to compress it for query
		if ( is_object( $data_value ) ) {
			$data_value = $this->get_data_layer_meta_value( $data_type, $data_value );
		}

		return $this->where_meta( $this->get_data_layer_meta_key( $data_type ), $data_value, $compare );
	}

	/**
	 * @param int         $customer_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_customer( $customer_id, $compare = false ) {
		return $this->where_data_layer( 'customer', $customer_id, $compare );
	}

	/**
	 * @param int         $order_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_order( $order_id, $compare = false ) {
		return $this->where_data_layer( 'order', $order_id, $compare );
	}

	/**
	 * @param string      $guest_email
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_guest( $guest_email, $compare = false ) {
		return $this->where_data_layer( 'guest', $guest_email, $compare );
	}

	/**
	 * @param int         $cart_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_cart( $cart_id, $compare = false ) {
		return $this->where_data_layer( 'cart', $cart_id, $compare );
	}

	/**
	 * @param int         $user_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_user( $user_id, $compare = false ) {
		return $this->where_data_layer( 'user', $user_id, $compare );
	}

	/**
	 * @param int         $comment_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_comment( $comment_id, $compare = false ) {
		return $this->where_data_layer( 'comment', $comment_id, $compare );
	}

	/**
	 * @param int         $wishlist_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_wishlist( $wishlist_id, $compare = false ) {
		return $this->where_data_layer( 'wishlist', $wishlist_id, $compare );
	}

	/**
	 * @param int         $review_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_review( $review_id, $compare = false ) {
		return $this->where_data_layer( 'review', $review_id, $compare );
	}

	/**
	 * @param int         $subscription_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_subscription( $subscription_id, $compare = false ) {
		return $this->where_data_layer( 'subscription', $subscription_id, $compare );
	}

	/**
	 * @param int         $product_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_product( $product_id, $compare = false ) {
		return $this->where_data_layer( 'product', $product_id, $compare );
	}

	/**
	 * @param int         $lesson_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_lesson( $lesson_id, $compare = false ) {
		return $this->where_data_layer( 'lesson', $lesson_id, $compare );
	}

	/**
	 * @param int         $quiz_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_quiz( $quiz_id, $compare = false ) {
		return $this->where_data_layer( 'quiz', $quiz_id, $compare );
	}

	/**
	 * @param int         $course_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_course( $course_id, $compare = false ) {
		return $this->where_data_layer( 'course', $course_id, $compare );
	}

	/**
	 * @param string      $download_id
	 * @param bool|string $compare
	 * @return $this
	 */
	public function where_download( $download_id, $compare = false ) {
		return $this->where_data_layer( 'download', $download_id, $compare );
	}
}
