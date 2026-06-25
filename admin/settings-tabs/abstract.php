<?php
namespace AutomateWoo;

/**
 * Admin_Settings_Tab_Abstract class.
 */
abstract class Admin_Settings_Tab_Abstract {

	/**
	 * Settings tab id.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Settings tab name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Settngs tab messages.
	 *
	 * @var array
	 */
	protected $messages = [];

	/**
	 * Settings tab errors.
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Settings tab settings.
	 *
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Property for determining whether to display the settings tab title.
	 *
	 * @var boolean
	 */
	public $show_tab_title = true;

	/**
	 * Settings tab prefix.
	 *
	 * @var string
	 */
	public $prefix = 'automatewoo_';


	/**
	 * Get tab id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Get tab url.
	 *
	 * @return string
	 */
	public function get_url() {
		return admin_url( 'admin.php?page=automatewoo-settings&tab=' . $this->get_id() );
	}


	/**
	 * Optional method
	 */
	public function load_settings() {}


	/**
	 * Get tab settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->load_settings();
			do_action( 'automatewoo/admin/settings/' . $this->get_id(), $this ); // allow third parties to add settings
		}
		return $this->settings;
	}

	/**
	 * Output settings tab.
	 */
	public function output() {
		$this->output_settings_form();
	}

	/**
	 * Output settings form.
	 */
	public function output_settings_form() {
		Admin::get_view(
			'settings-form',
			[
				'tab' => $this,
			]
		);
	}

	/**
	 * Output settings fields.
	 */
	public function output_settings_fields() {
		foreach ( $this->get_settings() as $setting ) {
			$this->output_settings_field( $setting );
		}
	}


	/**
	 * Output setting form field..
	 *
	 * $field options:
	 *
	 * id
	 * type
	 * class
	 * css
	 * default
	 * placeholder
	 * required
	 * wrapper_attributes
	 * custom_attributes
	 *
	 * @param array $field
	 */
	public function output_settings_field( $field ) {
		if ( ! isset( $field['type'] ) || ! isset( $field['id'] ) ) {
			return;
		}

		$field = wp_parse_args(
			$field,
			[
				'title'              => '',
				'class'              => '',
				'css'                => '',
				'default'            => '',
				'placeholder'        => '',
				'required'           => false,
				'wrapper_attributes' => [],
				'wrapper_class'      => '',
				'custom_attributes'  => [],
			]
		);

		// Switch based on type
		switch ( $field['type'] ) {
			// Section Titles
			case 'title':
				echo '<div id="aw_settings_section_' . esc_attr( $field['id'] ) . '" class="aw-settings-section">';

				if ( ! empty( $field['title'] ) ) {
					echo '<h2>' . esc_html( $field['title'] ) . '</h2>';
				}
				if ( ! empty( $field['desc'] ) ) {
					echo wp_kses_post( wpautop( wptexturize( $field['desc'] ) ) );
				}
				echo '<table class="form-table">' . "\n\n";

				break;

			// Section Ends
			case 'sectionend':
				echo '</table></div>';
				break;

			// Standard text inputs and subtypes like 'number'
			case 'text':
			case 'email':
			case 'number':
			case 'password':
				$option_value = $this->get_option( $field );
				$this->output_field_start( $field );

				?>
					<input
						name="<?php echo esc_attr( $field['id'] ); ?>"
						id="<?php echo esc_attr( $field['id'] ); ?>"
						type="<?php echo esc_attr( $field['type'] ); ?>"
						style="<?php echo esc_attr( $field['css'] ); ?>"
						value="<?php echo esc_attr( $option_value ); ?>"
						class="<?php echo esc_attr( $field['class'] ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						<?php $this->output_attributes_array( $field, 'custom_attributes' ); ?>
						/> <?php $this->output_description( $field ); ?>
				<?php

				$this->output_field_end();
				break;

			// Textarea
			case 'textarea':
				$option_value = $this->get_option( $field );
				$this->output_field_start( $field );
				$this->output_description( $field );

				?>

					<textarea
						name="<?php echo esc_attr( $field['id'] ); ?>"
						id="<?php echo esc_attr( $field['id'] ); ?>"
						style="<?php echo esc_attr( $field['css'] ); ?>"
						class="<?php echo esc_attr( $field['class'] ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						<?php $this->output_attributes_array( $field, 'custom_attributes' ); ?>
						><?php echo esc_textarea( $option_value ); ?></textarea>

				<?php

				$this->output_field_end();
				break;

			// Select boxes
			case 'select':
			case 'multiselect':
				$option_value = $this->get_option( $field );
				$this->output_field_start( $field );

				if ( $field['type'] === 'multiselect' ) {
					$field['class'] .= ' wc-enhanced-select';
					$field_name      = $field['id'] . '[]';
				} else {
					$field_name = $field['id'];
				}

				?>
					<select
						name="<?php echo esc_attr( $field_name ); ?>"
						id="<?php echo esc_attr( $field['id'] ); ?>"
						style="<?php echo esc_attr( $field['css'] ); ?>"
						class="<?php echo esc_attr( $field['class'] ); ?>"
						data-placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						<?php $this->output_attributes_array( $field, 'custom_attributes' ); ?>
						<?php echo ( 'multiselect' === $field['type'] ) ? 'multiple="multiple"' : ''; ?>
						>
						<?php
						foreach ( $field['options'] as $key => $val ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"
								<?php
								if ( is_array( $option_value ) ) {
									selected( in_array( (string) $key, $option_value, true ), true );
								} else {
									selected( $option_value, (string) $key );
								}
								?>
							><?php echo esc_html( $val ); ?></option>
							<?php
						}
						?>
					</select> <?php $this->output_description( $field ); ?>
				<?php

				$this->output_field_end();
				break;

			// Single page selects
			case 'single_select_page':
				$this->output_field_start( $field );

				$args = [
					'name'             => $field['id'],
					'id'               => $field['id'],
					'sort_column'      => 'menu_order',
					'sort_order'       => 'ASC',
					'show_option_none' => ' ',
					'class'            => $field['class'],
					'echo'             => false,
					'selected'         => absint( $this->get_option( $field ) ),
				];

				if ( isset( $field['args'] ) ) {
					$args = wp_parse_args( $field['args'], $args );
				}

				?>
				<?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'automatewoo' ) . "' style='" . $field['css'] . "' class='" . $field['class'] . "' id=", wp_dropdown_pages( $args ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php $this->output_description( $field ); ?>
				<?php
				$this->output_field_end();

				break;

			// Checkbox input
			case 'checkbox':
				$this->output_field_start( $field );
				$option_value = $this->get_option( $field );

				?>
					<fieldset>
						<label for="<?php echo esc_attr( $field['id'] ); ?>">
							<input
								name="<?php echo esc_attr( $field['id'] ); ?>"
								id="<?php echo esc_attr( $field['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $field['class'] ) ? $field['class'] : '' ); ?>"
								value="1"
								<?php checked( $option_value, 'yes' ); ?>
								<?php $this->output_attributes_array( $field, 'custom_attributes' ); ?>
							/>
						</label> <?php $this->output_description( $field ); ?>
					</fieldset>
					<?php
					$this->output_field_end();
				break;

			case 'tinymce':
				$this->output_field_start( $field );

				wp_editor(
					$this->get_option( $field ),
					$field['id'],
					[
						'textarea_name' => $field['id'],
						'tinymce'       => true, // default to visual
						'quicktags'     => true,
						'textarea_rows' => 14,
					]
				);

				$this->output_description( $field );
				$this->output_field_end();

				break;
		}
	}

	/**
	 * Output the start of a field.
	 *
	 * @param  array $field
	 */
	private function output_field_start( $field ) {
		?>
			<tr id="<?php echo esc_attr( $field['id'] ); ?>_field_row"
				class="aw-settings-row aw-settings-row--type-<?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( $field['wrapper_class'] ); ?>"
				valign="top" <?php $this->output_attributes_array( $field, 'wrapper_attributes' ); ?>>
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field['id'] ); ?>">
						<?php
						if ( '' !== (string) $field['title'] ) {
							echo esc_html( $field['title'] );
						} elseif ( ! empty( $field['desc'] ) ) {
							// No visible title (e.g. grouped checkboxes): expose the description to assistive tech as the field's accessible name.
							printf( '<span class="screen-reader-text">%s</span>', esc_html( $field['desc'] ) );
						}
						echo $field['required'] ? '<span class="aw-required-asterisk"></span>' : '';
						?>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_html_class( $field['type'] ); ?>">
					<div class="automatewoo-settings__input-wrap">
					<?php $this->output_tooltip( $field ); ?>
		<?php
	}

	/**
	 * Output the end of a field.
	 */
	private function output_field_end() {
		echo '</div></td></tr>';
	}


	/**
	 * Output tooltip.
	 *
	 * @param array $field
	 */
	private function output_tooltip( $field ) {
		if ( isset( $field['desc_tip'] ) ) {
			$field['tooltip'] = $field['desc_tip']; // backwards compat
		}

		if ( empty( $field['tooltip'] ) ) {
			return;
		}

		Admin::help_tip( $field['tooltip'], false );
	}


	/**
	 * Output field description.
	 *
	 * @param array $field
	 */
	private function output_description( $field ) {
		if ( empty( $field['desc'] ) ) {
			return;

		}

		$description = $field['desc'];

		if ( $description && in_array( $field['type'], [ 'textarea', 'select' ], true ) ) {
			$description = '<p class="description" style="margin-top:2px">' . $description . '</p>';
		} elseif ( $description ) {
			$description = '<span class="description">' . $description . '</span>';
		}

		echo wp_kses_post( $description );
	}


	/**
	 * Converts array of attributes to HTML string.
	 *
	 * @param array  $field_data
	 * @param string $attributes_key
	 * @return string
	 */
	private function output_attributes_array( $field_data, $attributes_key ) {
		if ( empty( $field_data[ $attributes_key ] ) || ! is_array( $field_data[ $attributes_key ] ) ) {
			return;
		}

		$string = '';

		foreach ( $field_data[ $attributes_key ] as $attribute => $attribute_value ) {
			$string .= esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '" ';
		}

		echo $string; // phpcs:ignore WordPress.Security.EscapeOutput
	}



	/**
	 * Get an option.
	 *
	 * @param array $field
	 * @return string|string[]
	 */
	public function get_option( $field ) {
		if ( ! $field['id'] ) {
			return false;
		}

		$option_name = $field['id'];
		$default     = $field['default'];

		// Array value
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}

			// Single value
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = wp_unslash( $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}

	/**
	 * Get an option cast to a declared type.
	 *
	 * Settings are stored (and returned by {@see get_option()}) as strings (or
	 * arrays of strings), which can be awkward for callers — including
	 * third-party plugins that extend this abstract — that need a typed value
	 * (e.g. comparing an int against a constant).
	 *
	 * This is an opt-in, backwards-compatible accessor: it only casts when the
	 * field declares a `type_cast` key, and otherwise returns exactly what
	 * {@see get_option()} returns. The stored value and the form-rendering path
	 * (which keeps using {@see get_option()}) are unchanged, so existing
	 * behaviour — for AutomateWoo and for extenders that do not opt in — is
	 * untouched.
	 *
	 * Supported `type_cast` values: `int`, `float`, `bool`, `array`. For `int`
	 * and `float`, array values are cast element-wise. `bool` treats the stored
	 * checkbox value `yes` (and `1`/`true`) as true.
	 *
	 * @since 6.6.0
	 *
	 * @param array $field The field definition.
	 * @return mixed The option value, cast when the field declares `type_cast`.
	 */
	public function get_typed_option( $field ) {
		// get_option() reads $field['default'] unconditionally; settings
		// definitions may omit it, so normalise it to avoid an undefined-key
		// notice when callers pass a bare field.
		if ( ! isset( $field['default'] ) ) {
			$field['default'] = null;
		}

		$value = $this->get_option( $field );
		$cast  = isset( $field['type_cast'] ) ? $field['type_cast'] : '';

		// Leave unset/false (no value, or invalid field id) and un-typed fields
		// exactly as get_option() returned them.
		if ( '' === $cast || null === $value || false === $value ) {
			return $value;
		}

		switch ( $cast ) {
			case 'int':
				return is_array( $value ) ? array_map( 'intval', $value ) : (int) $value;
			case 'float':
				return is_array( $value ) ? array_map( 'floatval', $value ) : (float) $value;
			case 'bool':
				return $this->cast_option_to_bool( $value );
			case 'array':
				if ( is_array( $value ) ) {
					return $value;
				}
				return '' === $value ? array() : array( $value );
			default:
				return $value;
		}
	}

	/**
	 * Cast a stored option value to a boolean.
	 *
	 * Mirrors how checkbox settings persist their value (`yes`/`no`).
	 *
	 * @param mixed $value The stored option value.
	 * @return bool
	 */
	private function cast_option_to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( $value, array( 'yes', '1', 1, 'true', true ), true );
	}

	/**
	 * Save settings.
	 *
	 * @param array $fields Which fields to save. If empty, all fields will be saved.
	 *
	 * @return void
	 */
	public function save( $fields = array() ): void {
		$settings = $this->get_settings();
		$saved    = $this->save_fields( $settings, $fields );

		if ( $saved ) {
			$this->add_message( __( 'Your settings have been saved.', 'automatewoo' ) );
		}
	}

	/**
	 * Test an enabled integration and show immediate feedback when credentials fail.
	 *
	 * @param Integration|bool $integration        Integration instance or false.
	 * @param string           $integration_name   Integration display name.
	 * @param string           $notification_class Optional notification class to clear on success.
	 *
	 * @return bool
	 */
	protected function validate_integration_on_save( $integration, string $integration_name, string $notification_class = '' ): bool {
		if ( ! $integration instanceof Integration || ! $integration->is_enabled() ) {
			return true;
		}

		if ( $integration->test_integration() ) {
			if ( $notification_class && is_callable( [ $notification_class, 'possibly_delete_note' ] ) ) {
				call_user_func( [ $notification_class, 'possibly_delete_note' ] );
			}

			return true;
		}

		$this->add_integration_validation_error( $integration_name );

		return false;
	}

	/**
	 * Show immediate feedback when integration credentials are missing or invalid.
	 *
	 * @param string $integration_name Integration display name.
	 *
	 * @return void
	 */
	protected function add_integration_validation_error( string $integration_name ): void {
		$this->add_error(
			sprintf(
				/* translators: %1$s: Integration name. */
				__( 'Unable to communicate with the %1$s API. Please check your %1$s settings.', 'automatewoo' ),
				$integration_name
			)
		);
	}


	/**
	 * Save fields.
	 *
	 * @param array $settings Settings to save.
	 * @param array $fields   Which fields to save. If empty, all fields will be saved.
	 *
	 * @return bool
	 */
	public function save_fields( $settings, $fields ): bool {
		if ( empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return false;
		}

		foreach ( $settings as $option ) {
			// If fields have been specified and current option is not in the list, skip it.
			if ( ! empty( $fields ) && ! in_array( $option['id'], $fields, true ) ) {
				continue;
			}

			$this->save_field( $option );
		}

		return true;
	}


	/**
	 * Save a field.
	 *
	 * @param array $setting
	 */
	public function save_field( $setting ) {
		if ( ! isset( $setting['id'] ) || ! isset( $setting['type'] ) ) {
			return;
		}

		// skip title and section end fields
		if ( in_array( $setting['type'], [ 'sectionend', 'title' ], true ) ) {
			return;
		}

		$option_name = $setting['id'];
		$raw_value   = isset( $_POST[ $setting['id'] ] ) ? wp_unslash( $_POST[ $setting['id'] ] ) : null; // phpcs:ignore
		$autoload    = empty( $setting['autoload'] ) ? false : $setting['autoload'];

		// Format the value based on option type.
		switch ( $setting['type'] ) {
			case 'checkbox':
				$value = is_null( $raw_value ) ? 'no' : 'yes';
				break;

			case 'textarea':
			case 'tinymce':
				$value = wp_kses_post( trim( $raw_value ) );
				break;

			case 'multiselect':
			case 'multi_select_countries':
				$value = array_filter(
					Clean::recursive( (array) $raw_value ),
					function ( $value ) {
						return ! empty( $value );
					}
				);
				break;

			default:
				$value = Clean::recursive( $raw_value );
				break;
		}

		if ( is_string( $value ) ) {
			$value = wp_encode_emoji( $value );
		}

		if ( is_null( $value ) ) {
			return;
		}

		update_option( $option_name, $value, $autoload );
	}


	/**
	 * Add a message.
	 *
	 * @param string $strong
	 * @param string $more
	 */
	public function add_message( $strong, $more = '' ) {
		$this->messages[] = [
			'strong' => $strong,
			'more'   => $more,
		];
	}

	/**
	 * Add an error.
	 *
	 * @param string $error
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;
	}


	/**
	 * Output messages + errors.
	 */
	public function output_messages() {

		if ( count( $this->errors ) > 0 ) {
			foreach ( $this->errors as $error ) {
				echo '<div class="error"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}
		} elseif ( count( $this->messages ) > 0 ) {
			foreach ( $this->messages as $message ) {
				echo '<div class="updated"><p><strong>' . esc_html( $message['strong'] ) . '</strong>' . wp_kses_post( $message['more'] ) . '</p></div>';
			}
		}
	}


	/**
	 * Start a settings section. Adds a title to the settings property.
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $description
	 */
	public function section_start( $id, $title = '', $description = '' ) {
		$this->settings[] = [
			'type'  => 'title',
			'id'    => $this->prefix . $id,
			'title' => $title,
			'desc'  => $description,
		];
	}


	/**
	 * End a settings section. Adds a "section end" to the settings property.
	 *
	 * @param string $id
	 */
	public function section_end( $id ) {
		$this->settings[] = [
			'type' => 'sectionend',
			'id'   => $this->prefix . $id,
		];
	}


	/**
	 * Add a setting to the settings property.
	 *
	 * @param string $id
	 * @param array  $args
	 */
	public function add_setting( $id, $args ) {

		$setting = [
			'id'       => $this->prefix . $id,
			'autoload' => false,
		];

		$default_value = $this->get_default( $id );

		if ( $default_value !== false ) {

			if ( in_array( $args['type'], [ 'text', 'textarea', 'number' ], true ) ) {
				$setting['placeholder'] = $default_value;

				if ( ! empty( $args['set_default'] ) ) {
					$setting['default'] = $default_value;
				}
			} else {
				$setting['default'] = $default_value;
			}
		}

		$setting          = array_merge( $setting, $args );
		$this->settings[] = $setting;
	}


	/**
	 * Get default option value.
	 *
	 * @param string $id
	 * @return mixed
	 */
	protected function get_default( $id ) {
		return isset( AW()->options()->defaults[ $id ] ) ? AW()->options()->defaults[ $id ] : false;
	}
}
