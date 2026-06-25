<?php

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
	abstract public function render( $value );

	/**
	 * Field constructor.
	 */
	public function __construct() {
		$this->classes[] = 'automatewoo-field';
		$this->classes[] = 'automatewoo-field--type-' . $this->type;
	}



	/**
	 * @param string $name
	 * @return $this
	 */
	public function set_name( $name ) {
		$this->name = $name;
		return $this;
	}


	/**
	 * @param string $title
	 * @return $this
	 */
	public function set_title( $title ) {
		$this->title = $title;
		return $this;
	}


	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title ? $this->title : $this->default_title;
	}


	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name ? $this->name : $this->default_name;
	}


	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}


	/**
	 * @param string $description
	 * @return $this
	 */
	public function set_description( $description ) {
		$this->description = $description;
		return $this;
	}


	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}


	/**
	 * @param string $placeholder
	 * @return $this
	 */
	public function set_placeholder( $placeholder ) {
		$this->placeholder = $placeholder;
		return $this;
	}


	/**
	 * @return string
	 */
	public function get_placeholder() {
		return $this->placeholder;
	}


	/**
	 * @param string $classes
	 * @return $this
	 */
	public function add_classes( $classes ) {
		$this->classes = array_merge( $this->classes, explode( ' ', $classes ) );
		return $this;
	}


	/**
	 * @param bool $implode
	 * @return array|string
	 */
	public function get_classes( $implode = true ) {
		if ( $implode ) {
			return implode( ' ', $this->classes );
		}
		return $this->classes;
	}


	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return $this
	 */
	public function add_extra_attr( $name, $value = null ) {
		$this->extra_attrs[ $name ] = $value;
		return $this;
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function has_data_attr( $name ) {
		return isset( $this->extra_attrs[ 'data-' . $name ] );
	}


	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return $this
	 */
	public function add_data_attr( $name, $value = null ) {
		$this->add_extra_attr( 'data-' . $name, $value );
		return $this;
	}


	/**
	 * Outputs the extra field attrs in HTML attribute format.
	 */
	public function output_extra_attrs() {
		$string = '';

		foreach ( $this->extra_attrs as $name => $value ) {
			if ( is_null( $value ) ) {
				$string .= esc_attr( $name ) . ' ';
			} else {
				$string .= esc_attr( $name ) . '="' . esc_attr( $value ) . '" ';
			}
		}

		echo $string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $string is assembled from esc_attr()-escaped parts above.
	}


	/**
	 * @param bool $required
	 * @return $this
	 */
	public function set_required( $required = true ) {
		$this->required = $required;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function get_required() {
		return $this->required;
	}


	/**
	 * @return $this
	 */
	public function set_disabled() {
		$this->add_extra_attr( 'disabled', 'true' );
		return $this;
	}


	/**
	 * @param string $name_base
	 * @return $this
	 */
	public function set_name_base( $name_base ) {
		$this->name_base = $name_base;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function get_name_base() {
		return $this->name_base;
	}

	/**
	 * @return string
	 */
	public function get_full_name() {
		return ( $this->get_name_base() ? $this->get_name_base() . '[' . $this->get_name() . ']' : $this->get_name() );
	}


	/**
	 * @param string $options
	 * @return $this
	 */
	public function set_variable_validation( $options = '' ) {
		$this->set_validation( 'variables ' . $options );
		return $this;
	}


	/**
	 * @since 6.5.0
	 *
	 * @return bool
	 */
	public function supports_variables() {
		if ( ! $this->has_data_attr( 'automatewoo-validate' ) ) {
			return false;
		}

		$options = explode( ' ', $this->extra_attrs['data-automatewoo-validate'] );

		return in_array( 'variables', $options, true );
	}


	/**
	 * If $options is left blank then the field not support variables
	 *
	 * @param string $options
	 * @return $this
	 */
	public function set_validation( $options = '' ) {
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
	public function sanitize_value( $value ) {
		return Clean::string( $value );
	}
}
