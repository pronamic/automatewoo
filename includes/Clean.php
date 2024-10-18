<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * Sanitizer
 * @class Clean
 * @since 2.9
 */
class Clean {

	/**
	 * @param $string
	 * @return string
	 */
	static function string( $string ) {
		return sanitize_text_field( $string );
	}

	/**
	 * Sanitize the ORDER parameter allowing only ASC or DESC.
	 *
	 * @param string $order The order parameter provided on the input.
	 * @return string ASC or DESC. DESC by default.
	 */
	static function order( $order ) {
		return strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Sanitize the ORDER BY parameter allowing only valid column names.
	 *
	 * @param string $order_by The column attempted to set as order by
	 * @param array $valid_columns Valid column names available to set as order_by. If empty, all the columns are allowed
	 *
	 * @return string The sanitized column name. Or empty string if invalid column name.
	 */
	static function order_by( $order_by, $valid_columns = [] ) {

		if ( empty( $valid_columns ) || in_array( $order_by, $valid_columns ) ) {
			return $order_by;
		}

		return '';
	}

	/**
	 * @param $email
	 * @return string
	 */
	static function email( $email ) {
		return strtolower( sanitize_email( $email ) );
	}


	/**
	 * Sanitize a multi-line string. Will strip HTML tags.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	static function textarea( $text ) {
		return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $text ) ) );
	}

	/**
	 * Cleans a NON-localized price value so it's ready for DB storage.
	 *
	 * @since 4.4.0
	 *
	 * @param string|float $price
	 * @param int          $decimal_places
	 *
	 * @return string
	 */
	public static function price( $price, $decimal_places = null ) {
		if ( null === $decimal_places ) {
			$decimal_places = wc_get_price_decimals();
		}

		return wc_format_decimal( $price, $decimal_places );
	}

	/**
	 * Cleans a localized price value so it's ready for DB storage.
	 *
	 * WARNING - This method can only be called once on a price value.
	 * Using it multiple times can lead to prices multiplying by 10 when '.' is set to the store's thousands separator.
	 *
	 * @since 4.6.0
	 *
	 * @param string|float $price
	 * @param int          $decimal_places Optional - Uses the WC options value if not set.
	 *
	 * @return string
	 */
	public static function localized_price( $price, $decimal_places = null ) {
		if ( ! is_float( $price ) ) {
			$price = str_replace( wc_get_price_thousand_separator(), '', trim( (string) $price ) );
		}

		return self::price( $price, $decimal_places );
	}

	/**
	 * @param array $var
	 * @return array
	 */
	static function ids( $var ) {
		if ( is_array( $var ) ) {
			return array_filter( array_map( 'absint', $var ) );
		}
		elseif ( is_numeric( $var ) ) {
			return [ absint( $var ) ];
		}
		return [];
	}


	/**
	 * @param string|int $id
	 * @return int
	 */
	static function id( $id ) {
		return absint( $id );
	}


	/**
	 * @param $var
	 * @return array|string
	 */
	static function recursive( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ 'AutomateWoo\Clean', 'recursive' ], $var );
		}
		else {
			return is_scalar( $var ) ? self::string( $var ) : $var;
		}
	}


	/**
	 * @param string|array $values
	 * @return array
	 */
	static function multi_select_values( $values ) {

		// pre WC 3.0 multi selects were saved as comma delimited strings
		if ( is_string( $values ) ) {
			$values = explode( ',', $values );
		}

		if ( $values ) {
			return Clean::recursive( $values );
		}
		else {
			return [];
		}
	}


	/**
	 * Convert comma delimited string to array.
	 *
	 * @param string $list
	 * @return array
	 */
	static function comma_delimited_string( $list ) {
		$list = explode(',', Clean::string( $list ) );
		return array_filter( array_map( 'trim', $list ) );
	}


	/**
	 * Standardizes new line characters to \n.
	 *
	 * Serialize strings with CR new lines causes problems when is imported using WP Importer.
	 * See: https://github.com/woocommerce/automatewoo/issues/1183
	 *
	 * @since 6.0.2
	 *
	 * @param string $string String to standardize new line characters in.
	 * @return string String with new line characters standardized.
	 */
	public static function standarize_new_line_characters( $string ) {
		return str_replace( array( "\r\n", "\r" ), "\n", $string );
	}


	/**
	 * Performs a basic sanitize for AW email content permitting all HTML.
	 *
	 * Can contain unprocessed variables {{}}.
	 *
	 * @since 4.3
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	static function email_content( $content ) {
		$content = self::standarize_new_line_characters( $content );
		$content = wp_check_invalid_utf8( (string) $content );
		return $content;
	}


	/**
	 * HTML encodes emoji's in string or array.
	 *
	 * @since 4.3
	 *
	 * @param string|array $data
	 *
	 * @return string|array
	 */
	static function encode_emoji( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as &$field ) {
				if ( is_array( $field ) || is_string( $field ) ) {
					$field = self::encode_emoji( $field );
				}
			}
		}
		elseif ( is_string( $data ) ) {
			$data = wp_encode_emoji( $data );
		}
		return $data;
	}

}
