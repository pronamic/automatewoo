<?php
/**
 * Update to 5.1.0
 *
 * - use `date-desc` sorting for existing `shop.products` variables
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Replace_Helper;
use AutomateWoo\Variables_Processor;
use AutomateWoo\Workflow;
use AutomateWoo\Workflows\Factory;
use AutomateWoo\Workflows\VariableParsing\ParsedVariable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database_Update_5_1_0
 *
 * @package AutomateWoo\DatabaseUpdates
 */
class Database_Update_5_1_0 extends AbstractDatabaseUpdate {

	const UPDATE_ITEMS_OPTIONS_KEY = 'automatewoo_update_items';

	/** @var string */
	protected $version = '5.1.0';

	/**
	 * Runs immediately before a database update begins.
	 */
	protected function start() {
		parent::start();

		// get list of workflows to update
		$workflows = $this->get_all_workflow_ids();

		update_option( self::UPDATE_ITEMS_OPTIONS_KEY, $workflows );
	}

	/**
	 * Called immediately after database update is completed.
	 */
	protected function finish() {
		parent::finish();

		delete_option( self::UPDATE_ITEMS_OPTIONS_KEY );
	}

	/**
	 * @return bool
	 */
	protected function process() {
		$items = get_option( self::UPDATE_ITEMS_OPTIONS_KEY );

		if ( empty( $items ) ) {
			return true; // no more items to process, return complete
		}

		$batch = array_splice( $items, 0, 5 );

		foreach ( $batch as $item ) {
			$workflow = Factory::get( $item );
			$this->update_shop_products_variables( $workflow );

			++$this->items_processed;
		}

		update_option( self::UPDATE_ITEMS_OPTIONS_KEY, $items );
		return false;
	}

	/**
	 * @return bool|int
	 */
	public function get_items_to_process_count() {
		if ( ! get_option( self::UPDATE_ITEMS_OPTIONS_KEY ) ) {
			$workflows_to_update = $this->get_all_workflow_ids();
		} else {
			$workflows_to_update = get_option( self::UPDATE_ITEMS_OPTIONS_KEY );
		}

		return count( $workflows_to_update );
	}

	/**
	 * @param Workflow $workflow
	 */
	private function update_shop_products_variables( $workflow ) {
		$actions = $workflow->get_meta( 'actions' );

		if ( ! $actions ) {
			return;
		}

		foreach ( $actions as &$action ) {
			foreach ( $action as $field_name => &$field_value ) {
				if ( 'action_name' === $field_name ) {
					continue;
				}

				$replacer = new Replace_Helper(
					$field_value,
					function ( $value ) {
						$variable = Variables_Processor::parse_variable( $value );
						if ( ! $variable instanceof ParsedVariable ) {
							return false;
						}

						if ( 'shop' === $variable->type && 'products' === $variable->field && ! array_key_exists( 'sort', $variable->parameters ) ) {
							$value .= ", sort: 'date-desc'";
						}

						return "{{ $value }}";
					},
					'variables'
				);

				$field_value = $replacer->process();
			}
		}

		$workflow->update_meta( 'actions', $actions );
	}

	/**
	 * Return the list of IDs of all the workflows in the database
	 *
	 * @return int[]
	 */
	private function get_all_workflow_ids() {
		return get_posts(
			[
				'post_type'      => 'aw_workflow',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
	}
}

return new Database_Update_5_1_0();
