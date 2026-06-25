<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order_Note class.
 *
 * Supports notes added to subscriptions.
 *
 * @since 2.2
 */
class Order_Note {

	/** @var int */
	public $id;

	/** @var string */
	public $content;

	/**
	 * The ID of the associated order or subscription.
	 *
	 * @var int
	 */
	public $order_id;

	/** @var bool */
	public $is_customer_note;


	/**
	 * @param int    $id
	 * @param string $content
	 * @param int    $order_id
	 */
	public function __construct( $id, $content, $order_id ) {
		$this->id       = $id;
		$this->content  = $content;
		$this->order_id = $order_id;
	}


	/**
	 * @return bool
	 */
	public function is_customer_note() {
		if ( ! isset( $this->is_customer_note ) ) {
			$this->is_customer_note = (bool) get_comment_meta( $this->id, 'is_customer_note', true );
		}
		return $this->is_customer_note;
	}


	/**
	 * @return string
	 */
	public function get_type() {
		return $this->is_customer_note() ? 'customer' : 'private';
	}
}
