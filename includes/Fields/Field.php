<?php
// phpcs:ignoreFile

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

/**
 * @class Field
 */
abstract class Field {

	/** @var string - deprecated, use $title */
	protected $default_title;

	/** @var string - deprecated, use $name */
	protected $default_name;

	/** @var string */
	protected $title;

	/** @var string */
	protected $name;

	/** @var string */
	protected $type;

	/** @var string */
	protected $description;

	/** @var string trigger or action */
	protected $name_base;

	/** @var bool */
	protected $required = false;

	/** @var array */
	protected $classes = [];

	/**
	 * Extra attributes that will appended to the HTML field element.
	 *
	 * @var array
	 */
	protected $extra_attrs = [];

	/** @var string */
	protected $placeholder = '';

	/**
	 * Field meta data.
	 *
	 * This prop can be used when misc data needs to be added to the field.
	 * Not to be confused with $this->extra_attrs.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	public $meta = [];

	/**
	 * Output the field HTML.
	 *
	 * @param mixed $value
	 */
	abstract function render( $value );

	/**
	 * Field constructor.
	 */
	function __construct() {
		$this->classes[] = 'automatewoo-field';
		$this->classes[] = 'automatewoo-field--type-' . $this->type;
	}



	/**
	 * @param $name
	 * @return $this
	 */
	function set_name( $name ) {
		$this->name = $name;
		return $this;
	}


	/**
	 * @param $title
	 * @return $this
	 */
	function set_title( $title ) {
		$this->title = $title;
		return $this;
	}


	/**
	 * @return string
	 */
	function get_title() {
		return $this->title ? $this->title :$this->default_title;
	}


	/**
	 * @return string
	 */
	function get_name() {
		return $this->name ? $this->name :$this->default_name;
	}


	/**
	 * @return string
	 */
	function get_type() {
		return $this->type;
	}


	/**
	 * @param $description
	 * @return $this
	 */
	function set_description( $description ) {
		$this->description = $description;
		return $this;
	}


	/**
	 * @return string
	 */
	function get_description() {
		return $this->description;
	}


	/**
	 * @param $placeholder string
	 * @return $this
	 */
	function set_placeholder( $placeholder ) {
		$this->placeholder = $placeholder;
		return $this;
	}


	/**
	 * @return string
	 */
	function get_placeholder() {
		return $this->placeholder;
	}


	/**
	 * @param $classes string
	 * @return $this
	 */
	function add_classes( $classes ) {
		$this->classes = array_merge( $this->classes, explode( ' ', $classes ) );
		return $this;
	}


	/**
	 * @param bool $implode
	 * @return array|string
	 */
	function get_classes( $implode = true ) {
		if ( $implode ) {
			return implode( ' ', $this->classes );
		}
		return $this->classes;
	}


	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	function add_extra_attr( $name, $value = null ) {
		$this->extra_attrs[$name] = $value;
		return $this;
	}


	/**
	 * @param $name
	 * @return bool
	 */
	function has_data_attr( $name ) {
		return isset( $this->extra_attrs[ 'data-' . $name ] );
	}


	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	function add_data_attr( $name, $value = null ) {
		$this->add_extra_attr( 'data-' . $name, $value );
		return $this;
	}


	/**
	 * Outputs the extra field attrs in HTML attribute format.
	 */
	function output_extra_attrs() {
		$string = '';

		foreach ( $this->extra_attrs as $name => $value ) {
			if ( is_null( $value ) ) {
				$string .= esc_attr( $name ) . ' ';
			}
			else {
				$string .= esc_attr( $name ) . '="' . esc_attr( $value ) . '" ';
			}
		}

		echo $string;
	}


	/**
	 * @param bool $required
	 * @return $this
	 */
	function set_required( $required = true ) {
		$this->required = $required;
		return $this;
	}


	/**
	 * @return bool
	 */
	function get_required() {
		return $this->required;
	}


	/**
	 * @return $this
	 */
	function set_disabled() {
		$this->add_extra_attr( 'disabled', 'true' );
		return $this;
	}


	/**
	 * @param $name_base
	 * @return $this
	 */
	function set_name_base( $name_base ) {
		$this->name_base = $name_base;
		return $this;
	}


	/**
	 * @return bool
	 */
	function get_name_base() {
		return $this->name_base;
	}

	/**
	 * @return string
	 */
	function get_full_name() {
		return ( $this->get_name_base() ? $this->get_name_base() . '[' . $this->get_name() . ']' : $this->get_name() );
	}


	/**
	 * @param string $options
	 * @return $this
	 */
	function set_variable_validation( $options = '' ) {
		$this->set_validation( 'variables ' . $options );
		return $this;
	}


	/**
	 * If $options is left blank then the field not support variables
	 *
	 * @param string $options
	 * @return $this
	 */
	function set_validation( $options = '' ) {
		$this->add_data_attr( 'automatewoo-validate', $options );
		return $this;
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * This method runs before WRITING a value to the DB but doesn't run before READING.
	 *
	 * Defaults to sanitize as a single line string. Override this method for fields that should be sanitized differently.
	 *
	 * @since 4.4.0
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function sanitize_value( $value ) {
		return Clean::string( $value );
	}


}
