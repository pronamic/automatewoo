<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Review
 * @since 3.0.0
 */
class Review {

	/** @var int */
	private $comment_id = 0;

	/** @var int  */
	private $user_id = 0;

	/** @var string */
	private $email;

	/** @var int  */
	private $product_id = 0;

	/** @var \WP_Comment */
	private $comment;

	/** @var string */
	private $comment_status;

	/** @var bool */
	public $exists = false;


	/**
	 * @param \WP_Comment|int $comment
	 */
	public function __construct( $comment ) {
		if ( is_numeric( $comment ) ) {
			$comment = get_comment( $comment );
		}

		if ( ! $comment || 'review' !== $comment->comment_type || 'product' !== get_post_type( $comment->comment_post_ID ) ) {
			return;
		}

		$this->exists = true;
		$this->comment = $comment;
		$this->comment_id = (int) $comment->comment_ID;
		$this->user_id = (int) $comment->user_id;
		$this->product_id = (int) $comment->comment_post_ID;
		$this->email = Clean::email( $comment->comment_author_email );
		$this->comment_status = wp_get_comment_status( $comment );
	}


	/**
	 * @return int
	 */
	public function get_id() {
		return $this->comment_id;
	}


	/**
	 * @return int
	 */
	public function get_product_id() {
		return $this->product_id;
	}


	/**
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}


	/**
	 * @return int
	 */
	public function get_email() {
		return $this->email;
	}


	/**
	 * @return string
	 */
	public function get_content() {
		return Clean::textarea( $this->comment->comment_content );
	}


	/**
	 * @return int
	 */
	function get_rating() {
		return (int) get_comment_meta( $this->get_id(), 'rating', true );
	}

	/**
	 * Get the customer who made the review.
	 *
	 * @since 4.5
	 *
	 * @return Customer|bool
	 */
	public function get_customer() {
		return Customer_Factory::get_by_review( $this );
	}

	/**
	 * @return string
	 */
	public function get_comment_status() {
		return $this->comment_status;
	}

	/**
	 * @return boolean
	 */
	public function is_approved() {
		return $this->comment_status === 'approved';
	}

}
