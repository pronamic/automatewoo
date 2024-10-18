<?php

namespace AutomateWoo\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Class Before_After_Day
 *
 * @since 4.5
 *
 * @package AutomateWoo\Fields
 */
class Before_After_Day extends Field {

	/**
	 * The field's default name.
	 *
	 * @var string
	 */
	protected $name = 'before_after_day';

	/**
	 * The field type.
	 *
	 * @var string
	 */
	protected $type = 'field-group';

	/**
	 * Before_After_Day constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'When to run', 'automatewoo' );
	}

	/**
	 * Renders field.
	 *
	 * @param array $value
	 */
	public function render( $value ) {
		$value = (int) $value;

		// convert the integer value for the inputs
		if ( $value > 0 ) {
			$day_val  = $value;
			$type_val = 'days_after';
		} elseif ( $value < 0 ) {
			$day_val  = $value * - 1;
			$type_val = 'days_before';
		} else {
			$day_val  = '';
			$type_val = 'on_the_day';
		}

		$field_names = $this->get_name_base() . '[' . $this->get_name() . '][]';

		$types = [
			'on_the_day'  => __( 'On the day', 'automatewoo' ),
			'days_after'  => __( 'day(s) after', 'automatewoo' ),
			'days_before' => __( 'day(s) before', 'automatewoo' ),
		];

		?>
		<div class="automatewoo-before-after-day-field-group">
			<div class="automatewoo-before-after-day-field-group__fields">

				<input type="number" name="<?php echo esc_attr( $field_names ); ?>"
					value="<?php echo esc_attr( $day_val ); ?>"
					class="automatewoo-before-after-day-field-group__field automatewoo-before-after-day-field-group__field--days"
					min="1"
					placeholder="1"
				>

				<select name="<?php echo esc_attr( $field_names ); ?>"
						class="automatewoo-before-after-day-field-group__field automatewoo-before-after-day-field-group__field--type"
				>
					<?php foreach ( $types as $opt_name => $opt_value ) : ?>
						<option value="<?php echo esc_attr( $opt_name ); ?>" <?php selected( $type_val, $opt_name ); ?>><?php echo esc_html( $opt_value ); ?></option>
					<?php endforeach; ?>
				</select>

			</div>
		</div>
		<?php
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * Converts the field value to an integer.
	 *
	 * @param array $value
	 *
	 * @return int|false
	 */
	public function sanitize_value( $value ) {
		if ( is_int( $value ) ) {
			return $value;
		}

		// convert array format to a simple integer
		if ( is_array( $value ) && count( $value ) === 2 ) {
			$days = $value[0] ? $value[0] : 1;
			$type = $value[1];

			switch ( $type ) {
				case 'on_the_day':
					return 0;
				case 'days_after':
					return (int) $days;
				case 'days_before':
					return intval( $days ) * -1;
			}
		}

		return false;
	}
}
