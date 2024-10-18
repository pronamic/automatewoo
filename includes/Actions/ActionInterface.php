<?php

namespace AutomateWoo\Actions;

use AutomateWoo\Fields\Field;

/**
 * Interface ActionInterface
 *
 * @since 5.2.0
 */
interface ActionInterface {

	/**
	 * Get the action's name.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Set the action's name.
	 *
	 * @param string $name
	 */
	public function set_name( $name );

	/**
	 * Get the action's title.
	 *
	 * @param bool $prepend_group
	 *
	 * @return string
	 */
	public function get_title( $prepend_group = false );

	/**
	 * Get the action's group.
	 *
	 * @return string
	 */
	public function get_group();

	/**
	 * Get the action's description.
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Gets specific field belonging to the action.
	 *
	 * @param string $name
	 *
	 * @return Field|false
	 */
	public function get_field( $name );

	/**
	 * Gets the action's fields.
	 *
	 * @return Field[]
	 */
	public function get_fields();

	/**
	 * Get a list of required field names.
	 *
	 * @since 6.0.10
	 *
	 * @return Field[]
	 */
	public function get_required_fields(): array;

	/**
	 * Set the action's options.
	 *
	 * @param array $options
	 */
	public function set_options( $options );

	/**
	 * Returns an option for use when running the action.
	 *
	 * Option value will already have been sanitized by it's field ::sanitize_value() method.
	 *
	 * @param string $field_name
	 * @param bool   $process_variables
	 * @param bool   $allow_html
	 *
	 * @return mixed Will vary depending on the field type specified in the action's fields.
	 */
	public function get_option( $field_name, $process_variables = false, $allow_html = false );

	/**
	 * Get an option for use when editing the action.
	 *
	 * The value will be already sanitized by the field object.
	 * This is used to displaying an option value for editing.
	 *
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	public function get_option_raw( $field_name );

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run();
}
