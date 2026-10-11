<?php

namespace AutomateWoo\Admin;

use AutomateWoo\Rest_Api\Controllers\ManualWorkflowRunner;
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

		$data = apply_filters( 'automatewoo/admin/asset_data', $data );

		// The manual runner REST routes reject batches outside these bounds, so keep the JS in step with them.
		$manual_runner              = is_array( $data['manualRunner'] ?? null ) ? $data['manualRunner'] : [];
		$manual_runner['batchSize'] = max(
			1,
			min( (int) ( $manual_runner['batchSize'] ?? 10 ), ManualWorkflowRunner::MAX_BATCH_SIZE )
		);
		$data['manualRunner']       = $manual_runner;

		$this->registry->add( 'automatewoo', $data );
	}
}
