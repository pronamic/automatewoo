<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Workflows\VariableParsing\ExcludedParsedVariable;
use AutomateWoo\Workflows\VariableParsing\ParsedVariable;
use AutomateWoo\Workflows\VariableParsing\VariableParser;

/**
 * Process variables into values. Is used on workflows and action options.
 *
 * @class Variable_Processor
 * @since 2.0.2
 */
class Variables_Processor {

	/** @var Workflow */
	public $workflow;


	/**
	 * @param $workflow
	 */
	function __construct( $workflow ) {
		$this->workflow = $workflow;
	}


	/**
	 * @param $text string
	 * @param bool $allow_html
	 * @return string
	 */
	function process_field( $text, $allow_html = false ) {

		$replacer = new Replace_Helper( $text, [ $this, '_callback_process_field' ], 'variables' );
		$value = $replacer->process();

		if ( ! $allow_html ) {
			$value = html_entity_decode( wp_strip_all_tags( $value ) );
		}

		return $value;
	}


	/**
	 * Callback function to process a variable string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function _callback_process_field( $string ) {
		$variable = self::parse_variable( $string );

		if ( $variable instanceof ExcludedParsedVariable ) {
			// Return excluded variable without processing
			return "{{ $variable->variable_string }}";
		}

		if ( ! $variable instanceof ParsedVariable ) {
			return '';
		}

		$parameters = $variable->parameters;
		$value      = $this->get_variable_value( $variable->type, $variable->field, $parameters );
		$value      = (string) apply_filters( 'automatewoo/variables/after_get_value', $value, $variable->type, $variable->field, $parameters, $this->workflow );

		if ( $value === '' ) {
			// backwards compatibility
			if ( isset( $parameters['default'] ) ) {
				$parameters['fallback'] = $parameters['default'];
			}

			// show default if set and no real value
			if ( isset( $parameters['fallback'] ) ) {
				$value = $parameters['fallback'];
			}
		}

		return $value;
	}


	/**
	 * Sanitize and parse a variable string into a usable object.
	 *
	 * @param string $string
	 *
	 * @return ParsedVariable|ExcludedParsedVariable|null
	 */
	public static function parse_variable( $string ) {
		$parser = new VariableParser();
		try {
			$parsed_variable = $parser->parse( $string );
		} catch ( \Exception $e ) {
			return null;
		}

		return $parsed_variable;
	}


	/**
	 * Get the value of a variable.
	 *
	 * @param string $data_type
	 * @param string $data_field
	 * @param array $parameters
	 *
	 * @return string
	 */
	function get_variable_value( $data_type, $data_field, $parameters = [] ) {

		// Short circuit filter for the variable value
		$short_circuit = (string) apply_filters( 'automatewoo_text_variable_value', false, $data_type, $data_field );

		if ( $short_circuit ) {
			return $short_circuit;
		}

		$this->convert_legacy_variables( $data_type, $data_field, $parameters );

		$variable_name = "$data_type.$data_field";
		$variable      = Variables::get_variable( $variable_name );

		$value = '';

		if ( $variable && method_exists( $variable, 'get_value' ) ) {

			if ( DataTypes::is_non_stored_data_type( $data_type ) ) {
				$value = $variable->get_value( $parameters, $this->workflow );
			} else {
				$data_item = $this->workflow->get_data_item( $variable->get_data_type() );

				if ( $data_item ) {
					$value = $variable->get_value( $data_item, $parameters, $this->workflow );
				}
			}
		}

		return (string) apply_filters( 'automatewoo/variables/get_variable_value', (string) $value, $this, $variable );
	}

	/**
	 * Handle legacy variable compatibility.
	 *
	 * @param string $data_type
	 * @param string $data_field
	 * @param array $parameters
	 */
	private function convert_legacy_variables( &$data_type, &$data_field, &$parameters ) {
		if ( $data_type === 'site' ) {
			$data_type = 'shop';
		}

		if ( $data_type === 'shop' ) {
			if ( $data_field === 'products_on_sale' ) {
				$data_field = 'products';
				$parameters['type'] = 'sale';
			}

			if ( $data_field === 'products_recent' ) {
				$data_field = 'products';
				$parameters['type'] = 'recent';
			}

			if ( $data_field === 'products_featured' ) {
				$data_field = 'products';
				$parameters['type'] = 'featured';
			}
		}
	}

}

