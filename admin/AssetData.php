<?php

namespace AutomateWoo\Admin;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use InvalidArgumentException;

/**
 * Class AssetData.
 *
 * Note: this data is only loaded for pages powered by WC-Admin.
 *
 * @since   5.0.0
 * @package AutomateWoo\Admin
 */
class AssetData {

	/**
	 * @var AssetDataRegistry
	 */
	protected $registry;

	/**
	 * AssetData constructor.
	 *
	 * @param AssetDataRegistry $registry
	 */
	public function __construct( AssetDataRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Add data to WC asset data registry.
	 *
	 * @throws InvalidArgumentException Only throws when site is in debug mode.
	 */
	public function add_data() {
		$data = [
			'manualRunner' => [
				'batchSize'           => 10,
				'highVolumeThreshold' => 500,
			],
		];

		$this->registry->add(
			'automatewoo',
			apply_filters( 'automatewoo/admin/asset_data', $data )
		);
	}
}
