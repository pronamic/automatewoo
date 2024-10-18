<?php
// phpcs:ignoreFile
/**
 * Update to 3.0.0
 * - migrate user data type to customer data type
 */

namespace AutomateWoo\DatabaseUpdates;

use AutomateWoo\Replace_Helper;
use AutomateWoo\Variables_Processor;
use AutomateWoo\Workflow;
use AutomateWoo\Workflows\Factory;
use AutomateWoo\Workflows\VariableParsing\ParsedVariable;

if ( ! defined( 'ABSPATH' ) ) exit;

class Database_Update_3_0_0 extends AbstractDatabaseUpdate {

	protected $version = '3.0.0';


	protected function start() {
		parent::start();

		$workflows = get_posts([
			'post_type' => 'aw_workflow',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
		]);

		update_option( 'automatewoo_update_items', $workflows );
	}


	protected function finish() {
		parent::finish();

		delete_option( 'automatewoo_update_items' );
	}


	/**
	 * @return bool
	 */
	protected function process() {

		$items = get_option( 'automatewoo_update_items' );

		if ( empty( $items ) ) {
			// no more items to process return complete...
			return true;
		}

		$batch = array_splice( $items, 0, 5 );

		foreach ( $batch as $item ) {
			$workflow = Factory::get( $item );
			$this->convert_workflow_from_user_to_customer_data_type( $workflow );
			$this->convert_legacy_abandoned_cart_workflow( $workflow );
			$this->items_processed++;
		}

		update_option( 'automatewoo_update_items', $items );
		return false;
	}


	/**
	 * @param Workflow $workflow
	 */
	function convert_workflow_from_user_to_customer_data_type( $workflow ) {

		$rules_to_convert = [
			'user_role' => 'customer_role',
			'user_tags' => 'customer_tags',
			'user_total_spent' => 'customer_total_spent',
			'user_order_count' => 'customer_order_count',
			'user_email' => 'customer_email',
			'user_meta' => 'customer_meta',
			'user_purchased_products' => 'customer_purchased_products',
			'user_order_statuses' => 'customer_order_statuses',
			'user_is_active_subscriber' => 'customer_has_active_subscription',
			'user_run_count' => 'customer_run_count',
		];

		$actions_to_convert = [
			'change_user_type' => 'customer_change_role',
			'update_user_meta' => 'customer_update_meta',
			'user_add_tags' => 'customer_add_tags',
			'user_remove_tag' => 'customer_remove_tags',
		];

		$rules = $workflow->get_rule_data();

		foreach ( $rules as &$rule_group ) {
			foreach ( $rule_group as &$rule ) {
				if ( array_key_exists( $rule['name'], $rules_to_convert ) ) {
					$rule['name'] = $rules_to_convert[ $rule['name'] ];
				}
			}
		}


		$actions = $workflow->get_meta( 'actions' );

		if ( $actions ) {

			foreach ( $actions as &$action ) {
				if ( array_key_exists( $action['action_name'], $actions_to_convert ) ) {
					$action['action_name'] = $actions_to_convert[ $action['action_name'] ];
				}
			}


			// convert variables
			foreach ( $actions as &$action ) {
				foreach ( $action as $field_name => &$field_value ) {
					if ( $field_name == 'action_name' ) {
						continue;
					}

					$replacer = new Replace_Helper( $field_value, function( $value ) {

						$user_variables_to_convert = [
							'id' => 'user_id',
							'firstname' => 'first_name',
							'lastname' => 'last_name',
							'billing_phone' => 'phone',
							'billing_country' => 'country',
						];

						$variable = Variables_Processor::parse_variable( $value );
						if ( ! $variable instanceof ParsedVariable ) {
							return false;
						}

						$value = aw_str_replace_first_match( $value, 'user.', 'customer.' );

						if ( $variable->type == 'user' && array_key_exists( $variable->field, $user_variables_to_convert ) ) {
							$value = aw_str_replace_first_match( $value, ".$variable->field", ".{$user_variables_to_convert[$variable->field]}" );
						}

						return "{{ $value }}";

					}, 'variables' );

					$field_value = $replacer->process();
				}
			}
		}

		$workflow->update_meta( 'rule_options', $rules );
		$workflow->update_meta( 'actions', $actions );
	}


	/**
	 * @param Workflow $workflow
	 */
	function convert_legacy_abandoned_cart_workflow( $workflow ) {

		$triggers = [ 'abandoned_cart', 'guest_abandoned_cart' ];
		$trigger = $workflow->get_trigger();

		if ( ! $trigger || ! in_array( $trigger->get_name(), $triggers ) ) {
			return;
		}

		$options = $workflow->get_meta( 'workflow_options' );
		$delay = $workflow->get_trigger_option('delay' );

		$options['when_to_run'] = 'delayed';
		$options['run_delay_value'] = $delay;
		$options['run_delay_unit'] = 'h';

		$workflow->update_meta( 'workflow_options', $options );
	}


}

return new Database_Update_3_0_0();
