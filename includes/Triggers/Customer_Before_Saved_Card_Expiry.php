<?php

namespace AutomateWoo;

use AutomateWoo\Exceptions\InvalidArgument;
use AutomateWoo\Triggers\AbstractBatchedDailyTrigger;
use RuntimeException;
use WC_Payment_Tokens;

defined( 'ABSPATH' ) || exit;

/**
 * Trigger_Customer_Before_Saved_Card_Expiry class.
 *
 * @since 3.7.0
 */
class Trigger_Customer_Before_Saved_Card_Expiry extends AbstractBatchedDailyTrigger {

	/**
	 * @var string[]
	 */
	public $supplied_data_items = [ 'customer', 'card' ];

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->title       = __( 'Customer Before Saved Card Expiry', 'automatewoo' );
		$this->description = sprintf(
			/* translators: %1$s docs URL */
			__(
				'This trigger runs a set number of days before a customer\'s saved card expires. Cards expire on the last calendar day of their expiry month. <a href="%1$s" target="_blank">Some payment gateways are not supported.</a>',
				'automatewoo'
			),
			Admin::get_docs_link( 'triggers/saved-card-expiry-notifications/' )
		);
		$this->description .= ' ' . $this->get_description_text_workflow_not_immediate();
		$this->group        = __( 'Customers', 'automatewoo' );
	}

	/**
	 * Load fields.
	 */
	public function load_fields() {
		$days_before = ( new Fields\Positive_Number() )
			->set_name( 'days_before_expiry' )
			->set_title( __( 'Days before expiry', 'automatewoo' ) )
			->set_required();

		$this->add_field( $days_before );
		$this->add_field( $this->get_field_time_of_day() );
	}


	/**
	 * Get credit cards based on the specified days before expiry field.
	 *
	 * @param Workflow $workflow
	 * @param int      $limit
	 * @param int      $offset
	 *
	 * @return array
	 */
	protected function get_cards_by_expiry( $workflow, $limit, $offset ) {
		global $wpdb;

		$days_before = absint( $workflow->get_trigger_option( 'days_before_expiry' ) );

		if ( ! $days_before ) {
			return [];
		}

		$date = new DateTime();
		Time_Helper::convert_from_gmt( $date ); // get cards based on the sites timezone
		$date->modify( "{$days_before} days" );

		$day_to_run    = (int) $date->format( 'j' );
		$days_in_month = (int) $date->format( 't' );

		if ( $days_in_month !== $day_to_run ) {
			return [];
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT token_id FROM {$wpdb->prefix}woocommerce_payment_tokens as tokens
				LEFT JOIN {$wpdb->payment_tokenmeta} AS m1 ON tokens.token_id = m1.payment_token_id
				LEFT JOIN {$wpdb->payment_tokenmeta} AS m2 ON tokens.token_id = m2.payment_token_id
				WHERE type = 'CC'
				AND m1.meta_key = 'expiry_year'
				AND m1.meta_value = %s
				AND m2.meta_key = 'expiry_month'
				AND m2.meta_value = %s
				LIMIT %d OFFSET %d
				",
				[
					$date->format( 'Y' ),
					$date->format( 'm' ),
					$limit,
					$offset,
				]
			),
			OBJECT_K
		);

		return array_keys( $results );
	}

	/**
	 * Get a batch of items to process for given workflow.
	 *
	 * @param Workflow $workflow
	 * @param int      $offset The batch query offset.
	 * @param int      $limit  The max items for the query.
	 *
	 * @return array[] Array of items in array format. Items will be stored in the database so they should be IDs not objects.
	 */
	public function get_batch_for_workflow( Workflow $workflow, int $offset, int $limit ): array {
		$items = [];

		foreach ( $this->get_cards_by_expiry( $workflow, $limit, $offset ) as $token_id ) {
			$items[] = [
				'token' => $token_id,
			];
		}

		return $items;
	}

	/**
	 * Process a single item for a workflow to process.
	 *
	 * @param Workflow $workflow
	 * @param array    $item
	 *
	 * @throws InvalidArgument If token is not set.
	 * @throws RuntimeException If token is not found.
	 */
	public function process_item_for_workflow( Workflow $workflow, array $item ) {
		if ( ! isset( $item['token'] ) ) {
			throw InvalidArgument::missing_required( 'token' );
		}

		$token = WC_Payment_Tokens::get( $item['token'] );
		if ( ! $token ) {
			throw new RuntimeException( 'Token was not found.' );
		}

		$workflow->maybe_run(
			[
				'customer' => Customer_Factory::get_by_user_id( $token->get_user_id() ),
				'card'     => $token,
			]
		);
	}

	/**
	 * @param Workflow $workflow
	 *
	 * @return bool
	 */
	public function validate_workflow( $workflow ) {
		// workflow should only run once for each card
		if ( $workflow->has_run_for_data_item( 'card' ) ) {
			return false;
		}

		return true;
	}
}
