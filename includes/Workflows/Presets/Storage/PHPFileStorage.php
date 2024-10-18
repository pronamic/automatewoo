<?php

namespace AutomateWoo\Workflows\Presets\Storage;

use AutomateWoo\Exceptions\InvalidPath;
use AutomateWoo\Workflows\Presets\ArrayPreset;
use AutomateWoo\Workflows\Presets\PresetInterface;

/**
 * PHPFileStorage class.
 *
 * @package AutomateWoo\Workflows\Presets\Storage
 * @since   5.1.0
 */
class PHPFileStorage extends FileStorage {

	/** @var string[] */
	protected $preset_files = [
		'abandoned-cart-12-hours.php',
		'abandoned-cart-4-hours.php',

		'credit-cards-expiry-no-coupon.php',
		'credit-cards-expiry-with-coupon.php',

		'cross-sell-related-products-no-coupon.php',
		'cross-sell-related-products-with-coupon.php',
		'cross-sell-first-time-customers-no-coupon.php',
		'cross-sell-first-time-customers-with-coupon.php',
		'cross-sell-repeat-customers-no-coupon.php',
		'cross-sell-repeat-customers-with-coupon.php',

		'loyalty-reward-repeat-customers-with-coupon.php',
		'loyalty-reward-repeat-customers-with-custom-offer.php',
		'loyalty-reward-high-spending-customers-with-coupon.php',

		'new-customers-welcome-no-coupon.php',
		'new-customers-welcome-with-coupon.php',

		'reviews-remind-customers-to-leave-a-review.php',
		'reviews-thank-you-with-coupon.php',
		'reviews-thank-you-multiple-reviews.php',
		'reviews-thank-you-for-5-star-review.php',

		'win-back-customers-recent-products-no-coupon.php',
		'win-back-customers-recent-products-with-coupon.php',

		// links to docs (require extensions)
		'loyalty-birthday-with-coupon.php',
		'wishlist-remind-customers.php',
	];

	/**
	 * PHPFileStorage constructor.
	 *
	 * @param string $storage_path The directory where files are stored.
	 *
	 * @throws InvalidPath When the directory doesn't exist.
	 */
	public function __construct( $storage_path ) {
		parent::__construct( $storage_path, 'php' );
	}

	/**
	 * Get the data for a preset given its name.
	 *
	 * @param string $name The preset name.
	 *
	 * @return PresetInterface
	 */
	protected function get_preset_data( string $name ): PresetInterface {
		$preset_data = include $this->find_presets()[ $name ];
		return new ArrayPreset( $name, $preset_data );
	}
}
