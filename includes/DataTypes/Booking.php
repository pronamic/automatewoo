<?php

namespace AutomateWoo\DataTypes;

use AutomateWoo\Proxies\BookingsInterface;
use AutomateWoo\Exceptions\Exception as ExceptionInterface;
use WC_Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Booking data type class.
 *
 * Note: This class is only loaded if the Bookings extension is active.
 *
 * @since 5.3.0
 */
class Booking extends AbstractDataType {

	/**
	 * @var BookingsInterface
	 */
	protected $bookings;

	/**
	 * Booking constructor.
	 *
	 * @param BookingsInterface $bookings Bookings service class.
	 */
	public function __construct( BookingsInterface $bookings ) {
		$this->bookings = $bookings;
	}

	/**
	 * Check that an item is a valid object.
	 *
	 * @param mixed $item
	 *
	 * @return bool
	 */
	public function validate( $item ): bool {
		return $item instanceof WC_Booking;
	}

	/**
	 * Compress a item to a storable format (typically an ID).
	 *
	 * @param mixed $item
	 *
	 * @return int|null Returns int if successful or null on failure.
	 */
	public function compress( $item ) {
		if ( $item instanceof WC_Booking ) {
			return $item->get_id();
		}

		return null;
	}

	/**
	 * Get the full item from its stored format.
	 *
	 * @param int|string|null $compressed_item
	 * @param array           $compressed_data_layer
	 *
	 * @return WC_Booking|null Returns a booking object or null on failure.
	 */
	public function decompress( $compressed_item, $compressed_data_layer ) {
		try {
			return $this->bookings->get_booking( intval( $compressed_item ) );
		} catch ( ExceptionInterface $e ) {
			return null;
		}
	}
}
