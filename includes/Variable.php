<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Variable
 * @since 2.4
 */
abstract class Variable {

	/** @var string */
	protected $name;

	/** @var string */
	protected $description;

	/**
	 * Stores parameter field objects.
	 *
	 * @var Fields\Field[]
	 */
	protected $parameter_fields = [];

	/** @var string */
	protected $data_type;

	/** @var string */
	protected $data_field;

	/** @var bool */
	public $use_fallback = true;

	/** @var bool */
	public $has_loaded_admin_details = false;


	/**
	 * Optional method
	 */
	function init() {}


	/**
	 * Method to set description and other admin props
	 */
	function load_admin_details() {}


	function maybe_load_admin_details() {
		if ( ! $this->has_loaded_admin_details ) {
			$this->load_admin_details();
			$this->has_loaded_admin_details = true;
		}
	}


	/**
	 * Constructor
	 */
	function __construct() {
		$this->init();
	}


	/**
	 * Sets the name, data_type and data_field props
	 * @param $name
	 */
	function setup( $name ) {
		$this->name = $name;
		list( $this->data_type, $this->data_field ) = explode( '.', $this->name );
	}


	/**
	 * @return string
	 */
	function get_description() {
		$this->maybe_load_admin_details();
		return $this->description;
	}


	/**
	 * Get the parameter fields for the variable.
	 *
	 * @since 4.6.0
	 *
	 * @return Fields\Field[]
	 */
	public function get_parameter_fields() {
		$this->maybe_load_admin_details();
		return $this->parameter_fields;
	}


	/**
	 * @return string
	 */
	function get_name() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	function get_data_type() {
		return $this->data_type;
	}


	/**
	 * @return string
	 */
	function get_data_field() {
		return $this->data_field;
	}

	/**
	 * Add a parameter field to the variable.
	 *
	 * @since 4.6.0
	 *
	 * @param Fields\Field $field
	 */
	protected function add_parameter_field( Fields\Field $field ) {
		$this->parameter_fields[ $field->get_name() ] = $field;
	}

	/**
	 * Add a text parameter field to the variable.
	 *
	 * @param string $name
	 * @param string $description
	 * @param bool   $required
	 * @param string $placeholder
	 * @param array  $extra
	 */
	protected function add_parameter_text_field( $name, $description, $required = false, $placeholder = '', $extra = [] ) {
		$field = new Fields\Text();
		$field->set_name( $name );
		$field->set_description( $description );
		$field->set_required( $required );
		$field->set_placeholder( $placeholder );
		$field->meta = $extra;

		$this->add_parameter_field( $field );
	}

	/**
	 * Add a select parameter field to the variable.
	 *
	 * @param string $name
	 * @param string $description
	 * @param array  $options
	 * @param bool   $required
	 * @param array  $extra
	 */
	protected function add_parameter_select_field( $name, $description, $options = [], $required = false, $extra = [] ) {
		$field = new Fields\Select( false );
		$field->set_name( $name );
		$field->set_description( $description );
		$field->set_required( $required );
		$field->set_options( $options );
		$field->meta = $extra;

		$this->add_parameter_field( $field );
	}

}
