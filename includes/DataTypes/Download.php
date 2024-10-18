<?php
namespace AutomateWoo\DataTypes;

use AutomateWoo\Clean;
use AutomateWoo\Download as DownloadModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Download data type class.
 */
class Download extends AbstractDataType {

	/**
	 * Check that a data item is valid for it's type.
	 *
	 * @param DownloadModel $item DownloadModel object.
	 * @return bool
	 */
	public function validate( $item ) {
		return $item instanceof DownloadModel;
	}


	/**
	 * Compress a data item to a ID.
	 *
	 * @param \WC_Customer_Download $item
	 * @return mixed
	 */
	public function compress( $item ) {
		return $item->get_id();
	}


	/**
	 * Get the full item from its stored format.
	 *
	 * @param string $compressed_item
	 * @param array  $compressed_data_layer
	 * @return mixed
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		$id      = Clean::string( $compressed_item );
		$order   = wc_get_order( $compressed_data_layer['order'] );
		$product = wc_get_product( $compressed_data_layer['product'] );

		if ( ! $id || ! $order || ! $product ) {
			return false;
		}

		$download = new DownloadModel( $id, $product->get_id(), $order->get_id() );
		if ( ! $download ) {
			return false;
		}

		return $download;
	}

	/**
	 * Get singular name for data type.
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return __( 'Download', 'automatewoo' );
	}

	/**
	 * Get plural name for data type.
	 *
	 * @return string
	 */
	public function get_plural_name() {
		return __( 'Downloads', 'automatewoo' );
	}
}
