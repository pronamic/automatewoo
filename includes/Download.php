<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Download class.
 *
 * @since 5.6.6
 */
class Download {

	/**
	 * Download ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the associated product.
	 *
	 * @var int
	 */
	public $product_id;

	/**
	 * The ID of the associated order.
	 *
	 * @var int
	 */
	public $order_id;

	/**
	 * Constructor.
	 *
	 * @param int $id
	 * @param int $product_id
	 * @param int $order_id
	 */
	public function __construct( $id, $product_id, $order_id ) {
		$this->id         = $id;
		$this->product_id = $product_id;
		$this->order_id   = $order_id;
	}

	/**
	 * Get the download ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the product ID.
	 *
	 * @return int
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * Get the order ID.
	 *
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Get the file name.
	 *
	 * @return string
	 */
	public function get_file_name() {
		$product = wc_get_product( $this->product_id );
		if ( ! $product ) {
			return '';
		}

		$file = $product->get_file( $this->id );
		if ( ! $file ) {
			return '';
		}

		return $file['name'];
	}

	/**
	 * Get the download URL.
	 *
	 * @return string
	 */
	public function get_download_url() {
		$order = wc_get_order( $this->order_id );
		if ( ! $order ) {
			return '';
		}

		// SEMGREP WARNING EXPLANATION
		// This is escaped by esc_url_raw but semgrep only takes into consideration esc_url.
		// Also, the URL is just a Home URL and the email is encoded. Rest of the params are just integer IDs.
		return esc_url_raw(
			add_query_arg(
				array(
					'download_file' => $this->product_id,
					'order'         => $order->get_order_key(),
					'email'         => rawurlencode( $order->get_billing_email() ),
					'key'           => $this->id,
				),
				trailingslashit( home_url() )
			)
		);
	}

	/**
	 * Get the download count.
	 *
	 * @return int
	 */
	public function get_download_count() {
		$data_store = \WC_Data_Store::load( 'customer-download' );
		$downloads  = $data_store->get_downloads(
			array(
				'download_id' => $this->id,
				'product_id'  => $this->product_id,
				'order_id'    => $this->order_id,
				'limit'       => 1,
				'order'       => 'DESC',
			)
		);

		if ( ! empty( $downloads ) ) {
			$download = current( $downloads );
			return $download->get_download_count();
		}

		return 0;
	}
}
