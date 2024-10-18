<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

use AutomateWoo\Clean;
use UnexpectedValueException;

/**
 * @class Rule
 */
abstract class Rule {

	/** @var string */
	public $name;

	/** @var string */
	public $title;

	/** @var string */
	public $group;

	/** @var string string|number|object|select  */
	public $type;

	/**
	 * Define the data type used by the rule.
	 * This is a required property.
	 *
	 * @var string
	 */
	public $data_item;

	/** @var array  */
	public $compare_types = [];

	/** @var \AutomateWoo\Workflow */
	private $workflow;

	/** @var bool - e.g meta rules have 2 value fields so their value data is an stored as an array */
	public $has_multiple_value_fields = false;


	/**
	 * Constructor
	 */
	function __construct() {
		$this->init();
		$this->determine_rule_group();
	}


	/**
	 * Init the rule.
	 */
	abstract public function init();


	/**
	 * Validates that a given workflow data item passed the rule validation
	 * based on the supplied $compare_type and $value.
	 *
	 * @param mixed  $data_item    A valid workflow data item e.g. an instance of `\WC_Order` for an order based rule.
	 * @param string $compare_type The user selected compare type for the rule.
	 * @param mixed  $value        The user entered value for the rule. This value is validated by the validate_value() method beforehand.
	 *
	 * @return bool
	 */
	abstract public function validate( $data_item, $compare_type, $value );


	/**
	 * @param $workflow
	 */
	function set_workflow( $workflow ) {
		$this->workflow = $workflow;
	}


	/**
	 * @return \AutomateWoo\Workflow
	 */
	function get_workflow() {
		return $this->workflow;
	}


	/**
	 * @since 4.2
	 * @return \AutomateWoo\Data_Layer
	 */
	function data_layer() {
		return $this->get_workflow()->data_layer();
	}


	/**
	 * Get is/is not compare types.
	 *
	 * @since 4.6
	 *
	 * @return array
	 */
	public function get_is_or_not_compare_types() {
		return [
			'is'     => __( 'is', 'automatewoo' ),
			'is_not' => __( 'is not', 'automatewoo' ),
		];
	}


	/**
	 * @return array
	 */
	function get_string_compare_types() {
		return [
			'contains' => __( 'contains', 'automatewoo' ),
			'not_contains' => __( 'does not contain', 'automatewoo' ),
			'is' => __( 'is', 'automatewoo' ),
			'is_not' => __( 'is not', 'automatewoo' ),
			'starts_with' => __( 'starts with', 'automatewoo' ),
			'ends_with' => __( 'ends with', 'automatewoo' ),
			'blank' => __( 'is blank', 'automatewoo' ),
			'not_blank' => __( 'is not blank', 'automatewoo' ),
			'regex' => __( 'matches regex', 'automatewoo' ),
		];
	}


	/**
	 * @return array
	 */
	function get_multi_string_compare_types() {
		return [
			'contains' => __( 'any contains', 'automatewoo' ),
			'is' => __( 'any matches exactly', 'automatewoo' ),
			'starts_with' => __( 'any starts with', 'automatewoo' ),
			'ends_with' => __( 'any ends with', 'automatewoo' ),
		];
	}


	/**
	 * @return array
	 */
	function get_float_compare_types() {
		return $this->get_is_or_not_compare_types() + [
			'greater_than' => __( 'is greater than', 'automatewoo' ),
			'less_than' => __( 'is less than', 'automatewoo' ),
		];
	}


	/**
	 * @return array
	 */
	function get_integer_compare_types() {
		return $this->get_float_compare_types() + [
			'multiple_of' => __( 'is a multiple of', 'automatewoo' ),
			'not_multiple_of' => __( 'is not a multiple of', 'automatewoo' )
		];
	}

	/**
	 * Get multi-select match compare types.
	 *
	 * @since 4.6
	 *
	 * @return array
	 */
	public function get_multi_select_compare_types() {
		return [
			'matches_any'  => __( 'matches any', 'automatewoo' ),
			'matches_all'  => __( 'matches all', 'automatewoo' ),
			'matches_none' => __( 'matches none', 'automatewoo' ),
		];
	}

	/**
	 * Get includes or not includes compare types.
	 *
	 * @since 4.6
	 *
	 * @return array
	 */
	public function get_includes_or_not_compare_types() {
		return [
			'includes'     => __( 'includes', 'automatewoo' ),
			'not_includes' => __( 'does not include', 'automatewoo' ),
		];
	}

	/**
	 * @param $compare_type
	 * @return bool
	 */
	function is_string_compare_type( $compare_type ) {
		return array_key_exists( $compare_type, $this->get_string_compare_types() );
	}


	/**
	 * @param $compare_type
	 * @return bool
	 */
	function is_integer_compare_type( $compare_type ) {
		return array_key_exists( $compare_type, $this->get_integer_compare_types() );
	}


	/**
	 * @param $compare_type
	 * @return bool
	 */
	function is_float_compare_type( $compare_type ) {
		return array_key_exists( $compare_type, $this->get_float_compare_types() );
	}


	/**
	 * @param $compare_type
	 * @return bool
	 */
	function is_is_or_is_not_compare_type( $compare_type ) {
		return array_key_exists( $compare_type, $this->get_is_or_not_compare_types() );
	}


	/**
	 * Validate a string based rule value.
	 *
	 * @param string $actual_value
	 * @param string $compare_type
	 * @param string $expected_value
	 *
	 * @return bool
	 */
	function validate_string( $actual_value, $compare_type, $expected_value ) {

		$actual_value = (string) $actual_value;
		$expected_value = (string) $expected_value;

		// most comparisons are case insensitive
		$actual_value_lowercase   = strtolower( $actual_value );
		$expected_value_lowercase = strtolower( $expected_value );

		switch ( $compare_type ) {

			case 'is':
				return $actual_value_lowercase == $expected_value_lowercase;
				break;

			case 'is_not':
				return $actual_value_lowercase != $expected_value_lowercase;
				break;

			case 'contains':
				return strstr( $actual_value_lowercase, $expected_value_lowercase ) !== false;
				break;

			case 'not_contains':
				return strstr( $actual_value_lowercase, $expected_value_lowercase ) === false;
				break;

			case 'starts_with':
				return aw_str_starts_with( $actual_value_lowercase, $expected_value_lowercase );

			case 'ends_with':
				return aw_str_ends_with( $actual_value_lowercase, $expected_value_lowercase );

			case 'blank':
				return empty( $actual_value );
				break;

			case 'not_blank':
				return ! empty( $actual_value );
				break;

			case 'regex':
				// Regex validation must not use case insensitive values
				return $this->validate_string_regex( $actual_value, $expected_value );
		}

		return false;
	}


	/**
	 * Only supports 'contains', 'is', 'starts_with', 'ends_with'
	 *
	 * @param array $actual_values
	 * @param string $compare_type
	 * @param string $expected_value
	 * @return bool
	 */
	function validate_string_multi( $actual_values, $compare_type, $expected_value ) {

		if ( empty( $expected_value ) ) {
			return false;
		}

		// look for at least one item that validates the text match
		foreach ( $actual_values as $coupon_code ) {
			if ( $this->validate_string( $coupon_code, $compare_type, $expected_value ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $actual_value
	 * @param $compare_type
	 * @param $expected_value
	 * @return bool
	 */
	function validate_number( $actual_value, $compare_type, $expected_value ) {

		$actual_value = (float) $actual_value;
		$expected_value = (float) $expected_value;

		switch ( $compare_type ) {

			case 'is':
				return $actual_value == $expected_value;
				break;

			case 'is_not':
				return $actual_value != $expected_value;
				break;

			case 'greater_than':
				return $actual_value > $expected_value;
				break;

			case 'less_than':
				return $actual_value < $expected_value;
				break;

		}


		// validate 'multiple of' compares, only accept integers
		if ( ! $this->is_whole_number( $actual_value ) || ! $this->is_whole_number( $expected_value ) ) {
			return false;
		}

		$actual_value = (int) $actual_value;
		$expected_value = (int) $expected_value;

		switch ( $compare_type ) {

			case 'multiple_of':
				return $actual_value % $expected_value == 0;
				break;

			case 'not_multiple_of':
				return $actual_value % $expected_value != 0;
				break;
		}

		return false;
	}


	/**
	 * @param $number
	 * @return bool
	 */
	function is_whole_number( $number ) {
		$number = (float) $number;
		return floor( $number ) == $number;
	}


	/**
	 * Determine the rule group based on it's title.
	 *
	 * If the group prop is already set that will be used.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function determine_rule_group() {
		if ( isset( $this->group ) ) {
			return;
		}

		// extract the hyphenated part of the title and use as group
		if ( isset( $this->title ) && strstr( $this->title, '-' ) ) {
			list( $this->group ) = explode( ' - ', $this->title, 2 );
		}

		if ( empty( $this->group ) ) {
			$this->group = __( 'Other', 'automatewoo' );
		}
	}

	/**
	 * Validates string regex rule.
	 *
	 * @since 4.6.0
	 *
	 * @param string $string
	 * @param string $regex
	 *
	 * @return bool
	 */
	protected function validate_string_regex( $string, $regex ) {
		$regex = $this->remove_global_regex_modifier( trim( $regex ) );

		// Add '/' delimiters if none are provided in the regex.
		if ( ! preg_match( '#^/(.+)/[gi]*$#', $regex ) ) {

			// Escape any unescaped delimiters in the regex first.
			if ( preg_match( '#[^\\\\]/#', $regex ) ) {
				$regex = str_replace( '/', '\\/', $regex );
			}

			$regex = '/' . $regex . '/';
		}

		return (bool) @preg_match( $regex, $string );
	}

	/**
	 * Remove the global regex modifier as it is not supported by PHP.
	 *
	 * @since 4.6.0
	 *
	 * @param string $regex
	 *
	 * @return string
	 */
	protected function remove_global_regex_modifier( $regex ) {
		return preg_replace_callback( '/(\/[a-z]+)$/', function( $modifiers ){
			return str_replace( 'g', '', $modifiers[0] );
		}, $regex );
	}

	/**
	 * Sanitizes the rule's value.
	 *
	 * This method runs before WRITING a value to the DB but doesn't run before READING.
	 *
	 * @since 4.6.0
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function sanitize_value( $value ) {
		return Clean::recursive( $value );
	}

	/**
	 * Formats a rule's value for display in the rules UI.
	 *
	 * @since 4.6.0
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function format_value( $value ) {
		return $value;
	}

	/**
	 * Validate the rule's user entered value.
	 *
	 * @since 5.1.0
	 *
	 * @param mixed $value
	 *
	 * @throws UnexpectedValueException When the value is not valid.
	 */
	public function validate_value( $value ) {
		// Override this method in child classes.
	}

}
