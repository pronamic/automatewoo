<?php

namespace AutomateWoo\Variables;

use AutomateWoo\Clean;
use AutomateWoo\DataTypes\DataTypes;
use AutomateWoo\Variable;

defined( 'ABSPATH' ) || exit;

/**
 * HTML link wrapper for URL variables.
 */
class Link extends Variable {

	/**
	 * Load admin details.
	 */
	public function load_admin_details() {
		$this->description = __( 'Displays a URL variable as an HTML link.', 'automatewoo' );
		$this->add_parameter_text_field( 'text', __( 'Sets the link text. Defaults to the URL.', 'automatewoo' ) );
		$this->add_parameter_select_field(
			'target',
			__( 'Sets where the link opens.', 'automatewoo' ),
			[
				''        => __( 'Default', 'automatewoo' ),
				'_blank'  => __( 'New tab', 'automatewoo' ),
				'_self'   => __( 'Same frame', 'automatewoo' ),
				'_parent' => __( 'Parent frame', 'automatewoo' ),
				'_top'    => __( 'Full window', 'automatewoo' ),
			]
		);
		$this->add_parameter_text_field( 'class', __( 'Sets CSS classes for the link.', 'automatewoo' ) );
	}

	/**
	 * Get the variable's value.
	 *
	 * @param mixed $data_item
	 * @param array $parameters
	 * @param mixed $workflow
	 * @return string
	 */
	public function get_value( $data_item = null, $parameters = [], $workflow = null ) {
		if ( is_array( $data_item ) && ( 1 === func_num_args() || ! is_array( $parameters ) ) ) {
			$workflow   = 1 === func_num_args() ? null : $parameters;
			$parameters = $data_item;
			$data_item  = null;
		}

		if ( ! is_array( $parameters ) ) {
			$parameters = [];
		}

		$url = $this->get_url_value( $data_item, $parameters, $workflow );
		if ( '' === $url ) {
			return '';
		}

		$text        = isset( $parameters['text'] ) ? Clean::string( $parameters['text'] ) : '';
		$text        = '' === $text ? $url : $text;
		$class_attr  = $this->get_class_attribute( $parameters );
		$target      = $this->get_target( $parameters );
		$target_attr = $target ? ' target="' . esc_attr( $target ) . '"' : '';
		$rel_attr    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

		return sprintf(
			'<a href="%1$s"%2$s%3$s%4$s>%5$s</a>',
			esc_url( $url ),
			$class_attr,
			$target_attr,
			$rel_attr,
			esc_html( $text )
		);
	}

	/**
	 * Get the wrapped URL variable value.
	 *
	 * @param mixed $data_item
	 * @param array $parameters
	 * @param mixed $workflow
	 * @return string
	 */
	private function get_url_value( $data_item, array $parameters, $workflow ) {
		$url_field      = $this->get_url_variable_data_field();
		$url_parameters = $parameters;

		unset( $url_parameters['default'], $url_parameters['fallback'] );

		if ( $workflow && method_exists( $workflow, 'variable_processor' ) ) {
			$url = $workflow->variable_processor()->get_variable_value(
				$this->get_data_type(),
				$url_field,
				$url_parameters
			);
			$url = apply_filters(
				'automatewoo/variables/after_get_value',
				$url,
				$this->get_data_type(),
				$url_field,
				$url_parameters,
				$workflow
			);

			return trim( (string) $url );
		}

		$url_variable = \AutomateWoo\Variables::get_variable( $this->get_data_type() . '.' . $url_field );
		if ( ! $url_variable || ! method_exists( $url_variable, 'get_value' ) ) {
			return '';
		}

		if ( DataTypes::is_non_stored_data_type( $this->get_data_type() ) ) {
			return trim( (string) $url_variable->get_value( $url_parameters, $workflow ) );
		}

		if ( ! $data_item ) {
			return '';
		}

		return trim( (string) $url_variable->get_value( $data_item, $url_parameters, $workflow ) );
	}

	/**
	 * Get the wrapped URL variable data field.
	 *
	 * @return string
	 */
	private function get_url_variable_data_field() {
		$data_field = $this->get_data_field();
		return 'link' === $data_field ? 'url' : substr( $data_field, 0, -5 ) . '_url';
	}

	/**
	 * Get the sanitized class attribute.
	 *
	 * @param array $parameters
	 * @return string
	 */
	private function get_class_attribute( array $parameters ) {
		if ( empty( $parameters['class'] ) ) {
			return '';
		}

		$classes = array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', Clean::string( $parameters['class'] ) ) ) );
		if ( empty( $classes ) ) {
			return '';
		}

		return ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
	}

	/**
	 * Get the sanitized link target.
	 *
	 * @param array $parameters
	 * @return string
	 */
	private function get_target( array $parameters ) {
		$target = empty( $parameters['target'] ) ? '' : Clean::string( $parameters['target'] );
		return in_array( $target, [ '_blank', '_self', '_parent', '_top' ], true ) ? $target : '';
	}
}
