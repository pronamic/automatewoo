<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

defined( 'ABSPATH' ) || exit;

/**
 * Class EmailAddressWithName
 *
 * @since 5.2.0
 */
class EmailAddressWithName extends Field {

	/**
	 * @var string
	 */
	protected $type = 'email-address-with-name';

	/**
	 * Render the field.
	 *
	 * @param array $value
	 */
	public function render( $value ) {
		if ( $value ) {
			$value = Clean::recursive( (array) $value );
		} else {
			$value = [ '', '' ];
		}

		?>
		<div class="automatewoo-field-group automatewoo-field-group--email-address-with-name">
			<div class="automatewoo-field-group__fields">
				<?php
				( new Text() )
					->set_name_base( $this->get_name_base() )
					->set_name( $this->get_name() )
					->set_multiple()
					->set_placeholder( __( 'Name', 'automatewoo' ) )
					->set_variable_validation()
					->render( $value[0] );
				( new Text() )
					->set_name_base( $this->get_name_base() )
					->set_name( $this->get_name() )
					->set_multiple()
					->set_placeholder( __( 'Email', 'automatewoo' ) )
					->set_variable_validation()
					->render( $value[1] );
				?>
			</div>
		</div>

		<?php
	}

	/**
	 * Sanitizes the value of the field.
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	public function sanitize_value( $value ) {
		return Clean::recursive( $value );
	}
}
