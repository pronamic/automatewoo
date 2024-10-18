<?php

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trigger_Abstract_Downloadable_Content.
 *
 * @since 5.6.6
 * @package AutomateWoo
 */
abstract class Trigger_Abstract_Downloadable_Content extends Trigger {

	/**
	 * Sets supplied data for the trigger.
	 *
	 * @var array
	 */
	public $supplied_data_items = [ DataTypes::PRODUCT, DataTypes::ORDER, DataTypes::CUSTOMER, DataTypes::DOWNLOAD ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->group = __( 'Downloadable Content', 'automatewoo' );
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$product = ( new Fields\Product() )
					->set_name( 'product' )
					->set_title( __( 'Product', 'automatewoo' ) )
					->set_description( __( 'Select a product here to have this workflow trigger only for that specific product. Leave blank to run for all products.', 'automatewoo' ) )
					->set_allow_variations( true );

		$downloadable_files = ( new Fields\Select() )
							->set_name( 'downloadable_files' )
							->set_title( __( 'Downloadable Files', 'automatewoo' ) )
							->set_description( __( 'Select files here to have this workflow trigger only for those specific items. Leave blank to run for all downloadable files of the selected product.', 'automatewoo' ) )
							->set_multiple( true )
							->set_dynamic_options_reference( 'product' );

		$this->add_field( $product );
		$this->add_field( $downloadable_files );
	}

	/**
	 * Provide dynamic options for the downloadable files field.
	 *
	 * @param string $field_name            Field name.
	 * @param string $reference_field_value Reference field value.
	 * @return array
	 */
	public function get_dynamic_field_options( $field_name, $reference_field_value = false ) {
		$options = [];
		$field   = $this->get_field( $field_name );

		if ( ( $field && $field_name !== 'downloadable_files' ) || ! $reference_field_value ) {
			return [];
		}

		$product   = wc_get_product( $reference_field_value );
		$downloads = ( $product ) ? $product->get_downloads() : array();

		if ( ! empty( $downloads ) ) {
			foreach ( $downloads as $key => $file ) {
				$options[ $key ] = $file['name'] . ' (' . basename( $file['file'] ) . ')';
			}
		}

		return $options;
	}

	/**
	 * Maybe run downloadable content workflows.
	 *
	 * @param int        $download_id Download ID.
	 * @param WC_Product $product Product object.
	 * @param WC_Order   $order Order object.
	 * @param Customer   $customer Customer object.
	 * @param boolean    $prevent_duplicates Prevent duplicate triggers?
	 */
	protected function maybe_run_workflows( $download_id, $product, $order, $customer, $prevent_duplicates = false ) {
		foreach ( $this->get_workflows() as $workflow ) {
			$workflow_product_id         = Clean::id( $workflow->get_trigger_option( 'product' ) );
			$workflow_downloadable_files = Clean::multi_select_values( $workflow->get_trigger_option( 'downloadable_files' ) );

			if ( ! empty( $workflow_product_id ) && $workflow_product_id !== $product->get_id() ) {
				continue;
			}

			if ( ! empty( $workflow_downloadable_files ) && ! in_array( $download_id, $workflow_downloadable_files, true ) ) {
				continue;
			}

			if ( $prevent_duplicates ) {
				// Check in queue to prevent duplicate triggers.
				$queue_query = new Queue_Query();
				$queue_query->where_workflow( $workflow->get_id() );
				$queue_query->where_order( $order->get_id() );
				$queue_query->where_product( $product->get_id() );
				$queue_query->where_download( $download_id );
				if ( ! empty( $queue_query->get_results() ) ) {
					continue;
				}

				// Check in logs to prevent duplicate triggers.
				$log_query = new Log_Query();
				$log_query->where_workflow( $workflow->get_id() );
				$log_query->where_order( $order->get_id() );
				$log_query->where_product( $product->get_id() );
				$log_query->where_download( $download_id );
				if ( ! empty( $log_query->get_results() ) ) {
					continue;
				}
			}

			$workflow->maybe_run(
				[
					DataTypes::PRODUCT  => $product,
					DataTypes::ORDER    => $order,
					DataTypes::CUSTOMER => $customer,
					DataTypes::DOWNLOAD => new Download( $download_id, $product->get_id(), $order->get_id() ),
				]
			);
		}
	}
}
