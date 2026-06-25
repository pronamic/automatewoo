<?php

namespace AutomateWoo\Triggers;

use AutomateWoo\Data_Layer;
use AutomateWoo\Fields\Field;

/**
 * Interface TriggerInterface
 *
 * Defines the public contract shared by all triggers. The abstract
 * {@see \AutomateWoo\Trigger} class implements this interface, so every built-in
 * and third-party trigger that extends it already satisfies the contract.
 *
 * Only methods already implemented by the abstract trigger are declared here so
 * that adding the interface does not break any existing trigger.
 *
 * @since 6.6.0
 */
interface TriggerInterface {

	/**
	 * Register the hooks the trigger listens to.
	 *
	 * @return void
	 */
	public function register_hooks();

	/**
	 * Get the trigger's name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Set the trigger's name.
	 *
	 * @param string $name
	 */
	public function set_name( $name );

	/**
	 * Get the trigger's title.
	 *
	 * @return string
	 */
	public function get_title();

	/**
	 * Get the trigger's group.
	 *
	 * @return string
	 */
	public function get_group();

	/**
	 * Get the trigger's description.
	 *
	 * @return string|null
	 */
	public function get_description();

	/**
	 * Get the data items supplied by the trigger.
	 *
	 * @return array
	 */
	public function get_supplied_data_items();

	/**
	 * Get the trigger's fields.
	 *
	 * @return Field[]
	 */
	public function get_fields();

	/**
	 * Maybe run the workflows registered to this trigger for the given data layer.
	 *
	 * @param Data_Layer|array $data_layer
	 */
	public function maybe_run( $data_layer = [] );
}
