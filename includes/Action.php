<?php

namespace AutomateWoo;

use AutomateWoo\Actions\ActionInterface;
use AutomateWoo\Actions\PreviewableInterface;
use AutomateWoo\Fields\Field;

/**
 * Abstract Class Action
 *
 * All workflow actions extend this class.
 */
abstract class Action implements ActionInterface {

	/**
	 * The action's unique name/slug.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [];

	/**
	 * The action's title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The action's description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The action's group.
	 *
	 * @var string
	 */
	public $group;

	/**
	 * The action's fields objects.
	 *
	 * @var Field[]
	 */
	public $fields;

	/**
	 * Array containing the action's option values.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * The workflow the action belongs to.
	 *
	 * This prop may not be set depending on the context.
	 *
	 * @var Workflow
	 */
	public $workflow;

	/**
	 * Knows if admin details have been loaded.
	 *
	 * @var bool
	 */
	protected $has_loaded_admin_details = false;

	/**
	 * This method no longer has an explicit purpose and is deprecated.
	 *
	 * @deprecated
	 */
	public function init() {
		wc_deprecated_function( __METHOD__, '5.2.0' );
	}

	/**
	 * Method to load the action's fields.
	 *
	 * TODO make protected method
	 */
	public function load_fields() {}

	/**
	 * Method to set the action's admin props.
	 *
	 * Admin props include: title, group and description.
	 */
	protected function load_admin_details() {}

	/**
	 * Loads the action's admin props.
	 */
	protected function maybe_load_admin_details() {
		if ( ! $this->has_loaded_admin_details ) {
			$this->load_admin_details();
			$this->has_loaded_admin_details = true;
		}
	}

	/**
	 * Get the action's title.
	 *
	 * @param bool $prepend_group
	 * @return string
	 */
	public function get_title( $prepend_group = false ) {
		$this->maybe_load_admin_details();
		$group = $this->get_group();
		$title = $this->title ?: '';

		if ( $prepend_group && $group !== __( 'Other', 'automatewoo' ) ) {
			return $group . ' - ' . $title;
		}

		return $title;
	}

	/**
	 * Get the action's group.
	 *
	 * @return string
	 */
	public function get_group() {
		$this->maybe_load_admin_details();
		return $this->group ? $this->group : __( 'Other', 'automatewoo' );
	}

	/**
	 * Get the action's description.
	 *
	 * @return string
	 */
	public function get_description() {
		$this->maybe_load_admin_details();
		return $this->description ?: '';
	}

	/**
	 * Get the action's name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name ?: '';
	}

	/**
	 * Set the action's name.
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Get the action's description HTML.
	 *
	 * @return string
	 */
	public function get_description_html() {
		if ( ! $this->get_description() ) {
			return '';
		}

		return '<p class="aw-field-description">' . $this->get_description() . '</p>';
	}

	/**
	 * Adds a field to the action.
	 *
	 * Should only be called in the load_fields() method.
	 *
	 * @param Field $field
	 */
	protected function add_field( $field ) {
		$field->set_name_base( 'aw_workflow_data[actions]' );
		$this->fields[ $field->get_name() ] = $field;
	}

	/**
	 * Removes a field from the action.
	 *
	 * Should only be called in the load_fields() method.
	 *
	 * @param string $field_name
	 */
	protected function remove_field( $field_name ) {
		unset( $this->fields[ $field_name ] );
	}

	/**
	 * Gets specific field belonging to the action.
	 *
	 * @param string $name
	 *
	 * @return Field|false
	 */
	public function get_field( $name ) {
		$this->get_fields();

		if ( ! isset( $this->fields[ $name ] ) ) {
			return false;
		}

		return $this->fields[ $name ];
	}

	/**
	 * Gets the action's fields.
	 *
	 * @return Field[]
	 */
	public function get_fields() {
		if ( ! isset( $this->fields ) ) {
			$this->fields = [];
			$this->load_fields();
		}

		return $this->fields;
	}

	/**
	 * Get a list of required field names.
	 *
	 * @since 6.0.10
	 *
	 * @return Field[]
	 */
	public function get_required_fields(): array {
		$required_fields = [];
		foreach ( $this->get_fields() as $name => $field ) {
			if ( $field->get_required() ) {
				$required_fields[ $name ] = $field;
			}
		}

		return $required_fields;
	}

	/**
	 * Set the action's options.
	 *
	 * @param array $options
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

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
	public function get_option( $field_name, $process_variables = false, $allow_html = false ) {
		$value = $this->get_option_raw( $field_name );

		if ( is_string( $value ) ) {
			$value = $this->process_option_string_value( $value, $process_variables, $allow_html );
		} elseif ( is_array( $value ) ) {
			foreach ( $value as &$array_value ) {
				if ( is_string( $array_value ) ) {
					$array_value = $this->process_option_string_value( $array_value, $process_variables, $allow_html );
				}
			}
		}

		return apply_filters( 'automatewoo_action_option', $value, $field_name, $process_variables, $this );
	}

	/**
	 * Process an option string value converting any variables.
	 *
	 * @since 5.2.0
	 *
	 * @param string $value
	 * @param bool   $process_variables
	 * @param bool   $allow_html
	 *
	 * @return string
	 */
	protected function process_option_string_value( string $value, bool $process_variables, bool $allow_html ) {
		if ( $process_variables ) {
			return $this->workflow->variable_processor()->process_field( $value, $allow_html );
		} elseif ( ! $allow_html ) {
			return html_entity_decode( wp_strip_all_tags( $value ) );
		}
		return $value;
	}

	/**
	 * Get an option for use when editing the action.
	 *
	 * The value will be already sanitized by the field object.
	 * This is used to displaying an option value for editing.
	 *
	 * @since 4.4.0
	 *
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	public function get_option_raw( $field_name ) {
		if ( isset( $this->options[ $field_name ] ) ) {
			return $this->options[ $field_name ];
		}

		return false;
	}

	/**
	 * Used to dynamically load option values for an action field.
	 *
	 * TODO move to HasDynamicFieldOptions interface
	 *
	 * @param string       $field_name
	 * @param string|false $reference_field_value
	 *
	 * @return array
	 */
	public function get_dynamic_field_options( $field_name, $reference_field_value = false ) {
		return [];
	}

	/**
	 * Check requirements for the action.
	 *
	 * TODO move to HasRequirements interface
	 * TODO Ideally change behaviour to "get_requirements" rather than actually performing check
	 *
	 * Runs before an action is loaded in the admin area.
	 */
	public function check_requirements() {}

	/**
	 * Display a warning in the admin area.
	 *
	 * TODO move into admin/UI related code
	 *
	 * @param string $message
	 */
	public function warning( $message ) {
		if ( ! is_admin() ) {
			return;
		}
		?>
		<script type="text/javascript">
			alert('ERROR: <?php echo esc_html( $message ); ?>');
		</script>
		<?php
	}

	/**
	 * Get text for action deprecation warning.
	 *
	 * @return string
	 */
	protected function get_deprecation_warning() {
		return __( 'THIS ACTION IS DEPRECATED AND SHOULD NOT BE USED.', 'automatewoo' );
	}

	/**
	 * Does this action have a preview ability?
	 *
	 * @deprecated in 5.2.0 Use Previewable interface instead
	 * @see PreviewableInterface
	 *
	 * @return bool
	 */
	public function can_be_previewed() {
		wc_deprecated_function( __METHOD__, '5.2.0' );
		return $this instanceof PreviewableInterface;
	}

	/**
	 * Returns preview content.
	 *
	 * @deprecated in 5.2.0 Use Previewable interface instead
	 * @see PreviewableInterface
	 *
	 * @return string|\WP_Error
	 */
	public function preview() {
		wc_deprecated_function( __METHOD__, '5.2.0', PreviewableInterface::class );
		if ( $this instanceof PreviewableInterface ) {
			return $this->get_preview();
		}
	}

	/**
	 * Add a message to the actions logs in case the response is not successful.
	 *
	 * @param Remote_Request $request the request to log
	 * @throws \Exception When the request is not successful.
	 */
	protected function maybe_log_action( $request ) {
		if ( ! $request->is_successful() ) {
			$body = $request->get_body();
			$body = $body['detail'] ?? $request->get_body_raw();
			throw new \Exception( esc_html( $body ) );
		}
	}


	/**
	 * Validates the required fields in the action.
	 *
	 * @throws \Exception When there are required fields not present.
	 */
	protected function validate_required_fields() {
		$errors = array_filter(
			$this->get_fields(),
			function ( $field ) {
				return $field->get_required() && ! $this->get_option( $field->get_name() );
			}
		);

		if ( ! empty( $errors ) ) {
			$message = sprintf(
				/* translators: Comma separated list of required options. */
				__( 'The following required option(s) were not provided: %s.', 'automatewoo' ),
				implode(
					', ',
					array_map(
						function ( $error ) {
							return $error->get_title();
						},
						$errors
					)
				)
			);
			throw new \Exception( esc_html( $message ) );
		}
	}
}
