<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Functions for email click tracking and unsubscribes
 *
 * @class Emails
 */
class Emails {

	/**
	 * Support for custom from name and from email per template by using an array
	 *
	 * custom_template => [
	 * 	template_name
	 * 	from_name
	 * 	from_email
	 * ]
	 *
	 * @var array
	 */
	static $templates = [
		'default' => 'WooCommerce Default',
		'plain' => 'None',
	];


	/**
	 * Get the from name for outgoing emails.
	 *
	 * @param string|bool $template_id
	 * @return string
	 */
	static function get_from_name( $template_id = false ) {

		$from_name = false;

		if ( $template_id ) {
			// check if template has a custom name
			$template = self::get_template( $template_id );

			if ( is_array( $template ) && isset( $template['from_name'] ) ) {
				$from_name = $template['from_name'];
			}
		}

		if ( ! $from_name ) {
			$from_name = AW()->options()->email_from_name;
		}

		if ( ! $from_name ) {
			$from_name = get_option( 'woocommerce_email_from_name' );
		}

		$from_name = apply_filters( 'automatewoo/mailer/from_name', $from_name, $template_id );
		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}


	/**
	 * Get the from address for outgoing emails.
	 * @param string|bool $template_id
	 * @return string
	 */
	static function get_from_address( $template_id = false ) {

		$from_email = false;

		if ( $template_id ) {
			// check if template has a custom from email
			$template = self::get_template( $template_id );

			if ( is_array( $template ) && isset( $template['from_email'] ) ) {
				$from_email = $template['from_email'];
			}
		}

		if ( ! $from_email ) {
			$from_email = AW()->options()->email_from_address;
		}

		if ( ! $from_email ) {
			$from_email = get_option( 'woocommerce_email_from_address' );
		}

		$from_address = apply_filters( 'automatewoo/mailer/from_address', $from_email, $template_id );
		return sanitize_email( $from_address );
	}


	/**
	 * @param $template_id
	 * @return bool|string|array
	 */
	static function get_template( $template_id ) {

		if ( ! $template_id )
			return false;

		$templates = self::get_email_templates( false );
		return isset( $templates[ $template_id ] ) ? $templates[ $template_id ] : false;
	}


	/**
	 * @param bool $names_only : whether to include extra template data or just id => name
	 * @return array
	 */
	static function get_email_templates( $names_only = true ) {

		$templates = apply_filters( 'automatewoo_email_templates', self::$templates );

		if ( ! $names_only )
			return $templates;

		$flat_templates = [];

		foreach ( $templates as $template_id => $template_data ) {
			$flat_templates[$template_id] = is_array( $template_data ) ? $template_data['template_name'] : $template_data;
		}

		return $flat_templates;
	}


	/**
	 * Parse email recipients and special args in the string
	 *
	 * Arg format is like so: email@example.org --notracking --other-param
	 *
	 * @param string $recipient_string
	 * @return array
	 */
	static function parse_recipients_string( $recipient_string ) {
		$items = [];

		foreach( explode(',', $recipient_string ) as $recipient ) {
			$recipient = Clean::string( $recipient );
			$recipient_parts = explode( ' ', $recipient );

			if ( is_email( $recipient_parts[0] ) ) {
				$email = Clean::email( $recipient_parts[0] );
				unset( $recipient_parts[0] );
			}
			else {
				continue;
			}

			$params = [];
			foreach ( $recipient_parts as $recipient_part ) {
				if ( strpos( $recipient_part, '--' ) === 0 ) {
					$params[ substr( $recipient_part, 2 ) ] = true;
				}
			}

			$params = wp_parse_args( $params, [
				'notracking' => false
			]);

			$items[ $email ] = $params;
		}

		return $items;
	}



	/**
	 * @param $input
	 * @param bool $remove_invalid
	 * @return array
	 */
	static function parse_multi_email_field( $input, $remove_invalid = true ) {

		$emails = [];

		$input = preg_replace( '/\s/u', '', $input ); // remove whitespace
		$input = explode(',', $input );

		foreach ( $input as $email ) {
			if ( ! $remove_invalid || is_email( $email ) ) {
				$emails[] = Clean::email( $email );
			}
		}

		return $emails;
	}

}
