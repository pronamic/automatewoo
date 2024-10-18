<?php
// phpcs:ignoreFile

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Gets a variable from the $_GET array but checks if it's set first.
 *
 * @since 4.4.0
 *
 * @param string $param
 *
 * @return mixed
 */
function aw_get_url_var( $param ) {
	if ( isset( $_GET[ $param ] ) ) {
		return $_GET[ $param ];
	}
	return false;
}


/**
 * Gets a variable from the $_POST array but checks if it's set first.
 *
 * @since 4.4.0
 *
 * @param string $param
 *
 * @return mixed
 */
function aw_get_post_var( $param ) {
	if ( isset( $_POST[ $param ] ) ) {
		return $_POST[ $param ];
	}
	return false;
}


/**
 * Gets a variable from the $_REQUEST array but checks if it's set first.
 *
 * @param $param
 * @return mixed
 */
function aw_request( $param ) {
	if ( isset( $_REQUEST[ $param ] ) ) {
		return $_REQUEST[ $param ];
	}
	return false;
}



/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 * @deprecated
 * @param string|array $var
 * @return string|array
 */
function aw_clean( $var ) {
	wc_deprecated_function( __FUNCTION__, '5.2.0' );

	if ( is_array( $var ) ) {
		return array_map( 'aw_clean', $var );
	}
	else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}


/**
 * @deprecated
 * @param $email
 * @return string
 */
function aw_clean_email( $email ) {
	wc_deprecated_function( __FUNCTION__, '5.2.0', 'Clean::email()' );

	return strtolower( sanitize_email( $email ) );
}



/**
 * @param $type string
 * @param $item
 *
 * @return mixed item of false
 */
function aw_validate_data_item( $type, $item ) {

	if ( ! $type || ! $item )
		return false;

	$valid = false;

	// Validate with the data type classes
	if ( $data_type = \AutomateWoo\DataTypes\DataTypes::get( $type ) ) {
		$valid = $data_type->validate( $item );
	}

	/**
	 * @since 2.1
	 */
	$valid = apply_filters( 'automatewoo_validate_data_item', $valid, $type, $item );

	if ( $valid ) return $item;

	return false;
}



/**
 * This is much like wc_get_template() but won't fail if the default template file is missing
 *
 * @param string $template_name
 * @param array $imported_variables (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 */
function aw_get_template( $template_name, $imported_variables = [], $template_path = '', $default_path = '' ) {

	if ( ! $template_path ) $template_path = 'automatewoo/';
	if ( ! $default_path ) $default_path = AW()->path( '/templates/' );

	if ( $imported_variables && is_array( $imported_variables ) ) {
		extract( $imported_variables );
	}

	$located = wc_locate_template( $template_name, $template_path, $default_path );

	if ( file_exists( $located ) ) {
		include $located; // nosemgrep No user input here. Also, we are checking the file existence and locating it with wc_locate_template
	}

}


/**
 * @deprecated
 * @param int $timestamp
 * @param bool|int $max_diff
 * @param bool $convert_from_gmt
 * @return string
 */
function aw_display_date( $timestamp, $max_diff = false, $convert_from_gmt = true ) {
	wc_deprecated_function( __FUNCTION__, '5.2.0', 'AutomateWoo\Format::date' );

	return AutomateWoo\Format::date( $timestamp, $max_diff, $convert_from_gmt );
}


/**
 * @deprecated
 * @param int $timestamp
 * @param bool|int $max_diff
 * @param bool $convert_from_gmt If its gmt convert it to site time
 * @return string|false
 */
function aw_display_time( $timestamp, $max_diff = false, $convert_from_gmt = true ) {
	wc_deprecated_function( __FUNCTION__, '5.2.0', 'AutomateWoo\Format::datetime' );

	return AutomateWoo\Format::datetime( $timestamp, $max_diff, $convert_from_gmt );
}


/**
 * @param $length int
 * @param bool $case_sensitive When false only lowercase letters will be included
 * @param bool $more_numbers
 * @return string
 */
function aw_generate_key( $length = 25, $case_sensitive = true, $more_numbers = false ) {

	$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

	if ( $case_sensitive ) {
		$chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}

	if ( $more_numbers ) {
		$chars .= '01234567890123456789';
	}

	$password = '';
	$chars_length = strlen( $chars );

	for ( $i = 0; $i < $length; $i++ ) {
		$password .= substr($chars, wp_rand( 0, $chars_length - 1), 1);
	}

	return $password;
}


/**
 * Generates a random key string for unique coupons.
 *
 * Doesn't use ambiguous characters like: 0 o i l 1.
 * Doesn't run any queries to check if the coupon is actually unique.
 *
 * @since 4.3.0
 *
 * @param int $length
 * @return string
 */
function aw_generate_coupon_key( $length = 10 ) {
	$chars = 'abcdefghjkmnpqrstuvwxyz23456789';
	$coupon_key = '';
	$chars_length = strlen( $chars );

	for ( $i = 0; $i < $length; $i++ ) {
		$coupon_key .= substr($chars, wp_rand( 0, $chars_length - 1), 1);
	}

	return $coupon_key;
}


/**
 * @param $price
 * @return float
 */
function aw_price_to_float( $price ) {

	$price = html_entity_decode( str_replace(',', '.', $price ) );

	$price = preg_replace( "/[^0-9\.]/", "", $price );

	return (float) $price;
}

/**
 * Get status to use when counting customer orders.
 *
 * This function will never return an empty array.
 *
 * @param bool $include_prefix
 *
 * @return array
 *
 * @since 2.7.1
 */
function aw_get_counted_order_statuses( $include_prefix = true ) {
	$default_statuses = array_merge( wc_get_is_paid_statuses(), [ 'on-hold' ] );
	$statuses         = array_filter( apply_filters( 'automatewoo/counted_order_statuses', $default_statuses ) );

	if ( ! $statuses ) {
		$statuses = $default_statuses;
	}

	if ( $include_prefix ) {
		$statuses = array_map( 'aw_add_order_status_prefix', $statuses );
	}

	return $statuses;
}


/**
 * @since 3.5.1
 * @param string $status
 * @return string
 */
function aw_add_order_status_prefix( $status ) {
	return 'wc-' . $status;
}


/**
 * @param $order WC_Order
 * @return array
 */
function aw_get_order_cross_sells( $order ) {
	$cross_sells = [];
	$in_order    = [];

	$items = $order->get_items();

	foreach ( $items as $item ) {
		$product = $item->get_product();

		if ( $product ) {
			$in_order[] = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$cross_sells = array_merge( $product->get_cross_sell_ids(), $cross_sells );
		}
	}

	return array_diff( $cross_sells, $in_order );
}


/**
 * @param $array
 * @param $value
 * @return void
 */
function aw_array_remove_value( &$array, $value ) {
	if ( ( $key = array_search( $value, $array ) ) !== false ) {
		unset( $array[$key] );
	}
}


/**
 * Removes an item by key from array and returns its value.
 *
 * @param $array
 * @param $key
 * @return mixed
 */
function aw_array_extract( &$array, $key ) {
	if ( ! is_array( $array ) || ! isset( $array[ $key ] ) ) {
		return false;
	}

	$var = $array[ $key ];
	unset( $array[ $key ] );

	return $var;
}


/**
 * Move an array item by key to the end of the array.
 *
 * @param array $array
 * @param string $key
 *
 * @return array
 */
function aw_array_move_to_end( $array, $key ) {
	if ( array_key_exists( $key, $array ) ) {
		$val           = aw_array_extract( $array, $key );
		$array[ $key ] = $val;
	}

	return $array;
}


/**
 * str_replace but limited to one replacement
 * @param string$subject
 * @param string$find
 * @param string $replace
 * @return string
 */
function aw_str_replace_first_match( $subject, $find, $replace = '' ) {
	$pos = strpos($subject, $find);
	if ($pos !== false) {
		return substr_replace($subject, $replace, $pos, strlen($find));
	}
	return $subject;
}


/**
 * @deprecated
 * @param string $subject
 * @param string $find
 * @param string $replace
 * @return string
 */
function aw_str_replace_start( $subject, $find, $replace = '' ) {
	wc_deprecated_function( __FUNCTION__, '5.2.0', 'aw_str_replace_first_match' );

	return aw_str_replace_first_match( $subject, $find, $replace = '' );
}

/**
 * Determine if a string starts with another string.
 *
 * @since 4.6.0
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function aw_str_starts_with( $haystack, $needle ) {
	return substr( $haystack, 0, strlen( $needle ) ) === $needle;
}

/**
 * Determine if a string ends with another string.
 *
 * @since 4.6.0
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function aw_str_ends_with( $haystack, $needle ) {
	$length = strlen( $needle );

	if ( $length == 0 ) {
		return true;
	}

	return substr( $haystack, -$length ) === $needle;
}


/**
 * Define cache blocking constants if not already defined
 * @since 3.6.0
 */
function aw_set_nocache_constants() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( "DONOTCACHEPAGE", true );
	}
	if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
		define( "DONOTCACHEOBJECT", true );
	}
	if ( ! defined( 'DONOTCACHEDB' ) ) {
		define( "DONOTCACHEDB", true );
	}
}


/**
 * Wrapper for nocache_headers which also disables page caching but allows object caching.
 *
 * @since 4.4.0
 */
function aw_no_page_cache() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( "DONOTCACHEPAGE", true );
	}
	nocache_headers();
}


/**
 * Get sanitized URL query args.
 *
 * @since 3.6.0
 * @param array $excluded Option to exclude some params
 * @return array
 */
function aw_get_query_args( $excluded = [] ) {
	$params = AutomateWoo\Clean::recursive( $_GET );

	foreach( $excluded as $key ) {
		unset( $params[ $key ] );
	}

	return $params;
}


/**
 * @since 3.6.1
 * @param string $country_code
 * @return string|bool
 */
function aw_get_country_name( $country_code ) {
	$countries = WC()->countries->get_countries();
	return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : false;
}


/**
 * @since 3.6.1
 * @param string $country_code
 * @param string $state_code
 * @return string|bool
 */
function aw_get_state_name( $country_code, $state_code ) {
	$states = WC()->countries->get_states( $country_code );
	return isset( $states[ $state_code ] ) ? $states[ $state_code ] : false;
}


/**
 * @since 3.8
 * @param mixed $val
 * @return int
 */
function aw_bool_int( $val ) {
	return intval( (bool) $val );
}


/**
 * @since 4.0
 * @param string $email
 * @return string
 */
function aw_anonymize_email( $email ) {
	if ( ! is_email( $email ) ) {
		return '';
	}
	$s1 = explode( '@', $email );
	$s2 = explode( '.', $s1[1], 2 );

	$anonymized = _aw_anonymize_email_part( $s1[0] ) . '@' . _aw_anonymize_email_part( $s2[0] ) . '.' . $s2[1];

	return apply_filters( 'automatewoo/anonymize_email', $anonymized, $email );
}


/**
 * @since 4.0
 * @param string $part
 * @return string
 */
function _aw_anonymize_email_part( $part ) {
	$to_keep = 2;
	$star_length = max( strlen( $part ) - $to_keep, 3 ); // min length of 3 stars
	return substr( $part, 0, $to_keep ) . str_repeat( '*', $star_length );
}


/**
 * @since 4.0
 * @param $email
 * @return bool
 */
function aw_is_email_anonymized( $email ) {
	if ( $email == 'deleted@site.invalid' ) {
		return true;
	}

	if ( strstr( $email, '***' ) !== false ) {
		return true;
	}

	return false;
}


/**
 * @since 4.1
 * @param $thing
 * @return bool
 */
function aw_is_error( $thing ) {
	return ( $thing instanceof AutomateWoo\Error || $thing instanceof WP_Error );
}


/**
 * Version can have a max of 3 parts, e.g. 4.1.0.1, isn't supported.
 * Max value of a single part is 999.
 *
 * @since 4.2
 * @param string $version
 * @return int
 */
function aw_version_str_to_int( $version ) {
	$parts = array_map( 'absint', explode( '.', (string) $version ) ); // convert to int here to remove any extra version info
	$padded = $parts[0]
		. str_pad( isset( $parts[1] ) ? $parts[1] : 0, 3, '0', STR_PAD_LEFT )
		. str_pad( isset( $parts[2] ) ? $parts[2] : 0, 3, '0', STR_PAD_LEFT );
	return (int) $padded;
}


/**
 * @since 4.2
 * @param int $version
 * @return string
 */
function aw_version_int_to_str( $version ) {
	$version = (string) (int) $version; // parse as int before convert to string
	$length = strlen( $version );

	if ( $length < 7 ) {
		return '0.0.0'; // incorrect format
	}

	$part3 = (int) substr( $version, -3, 3 );
	$part2 = (int) substr( $version, -6, 3 );
	$part1 = (int) substr( $version, 0, 3 - ( 9 - $length ) );
	return "$part1.$part2.$part3";
}

/**
 * Converts a 3-part version to a user-friendly 2-part format if possible.
 *
 * (For example, 5.1.0 => 5.1, but 5.1.1 => 5.1.1, and 5.0 => 5.0).
 *
 * @since 4.9.5
 * @param $version
 * return $string
 */
function aw_prettify_version( $version ) {
	return preg_replace(
		'/(\d+\.\d+)\.0+$/',
		'$1',
		$version
	);
}


/**
 * Converts a date object to a mysql formatted string.
 *
 * WC_Datetime objects are converted to UTC timezone.
 *
 * @since 4.4.0
 *
 * @param WC_DateTime|DateTime|AutomateWoo\DateTime $date
 *
 * @return string|false
 */
function aw_date_to_mysql_string( $date ) {
	if ( $date = aw_normalize_date( $date ) ) {
		return $date->to_mysql_string();
	}

	return false;
}

/**
 * Convert a date object to an instance of AutomateWoo\DateTime.
 *
 * WC_Datetime objects are converted to UTC timezone.
 *
 * @since 4.4.0
 *
 * @param WC_DateTime|DateTime|AutomateWoo\DateTime|string $input
 *
 * @return AutomateWoo\DateTime|false
 */
function aw_normalize_date( $input ) {
	if ( ! $input ) {
		return false;
	}

	try {
		if ( is_numeric( $input ) ) {
			$new = new AutomateWoo\DateTime();
			$new->setTimestamp( $input );
			return $new;
		}

		if ( is_string( $input ) ) {
			$new = new AutomateWoo\DateTime( $input );
			return $new;
		}

		if ( is_a( $input, 'AutomateWoo\DateTime' ) ) {
			return $input;
		}

		if ( is_a( $input, 'WC_DateTime' ) || is_a( $input, 'DateTime' ) ) {
			$new = new AutomateWoo\DateTime();
			$new->setTimestamp( $input->getTimestamp() );
			return $new;
		}
	} catch( \Exception $e ) {
		return false;
	}

	return false;
}


/**
 * Convert a date string to a WC_DateTime.
 *
 * Based on wc_string_to_datetime(), introduced in WooCommerce 3.1.0.
 *
 * @since  4.4.0
 * @param  string $time_string Time string.
 * @return WC_DateTime
 */
function aw_string_to_wc_datetime( $time_string ) {
	if ( function_exists( 'wc_string_to_datetime' ) ) {
		return wc_string_to_datetime( $time_string );
	} else {
		// Strings are defined in local WP timezone. Convert to UTC.
		if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $time_string, $date_bits ) ) {
			$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
			$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
		} else {
			$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $time_string ) ) ) );
		}
		$datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

		// Set local timezone or offset.
		if ( get_option( 'timezone_string' ) ) {
			$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
		} else {
			$datetime->set_utc_offset( wc_timezone_offset() );
		}

		return $datetime;
	}
}

/**
 * Get an array of post statuses that a post can have while being a draft.
 *
 * Note that 'draft' is deliberately not included based on how WC uses this status.
 *
 * @since 4.4.0
 *
 * @return array
 */
function aw_get_draft_post_statuses() {
	return [ 'auto-draft', 'new', 'wc-auto-draft' ];
}

/**
 * Get an array of draft order statuses (with or without prefix).
 *
 * @since 5.5.23
 *
 * @return array
 */
function aw_get_draft_order_statuses() {
	return [ 'auto-draft', 'new', 'checkout-draft', 'wc-auto-draft', 'wc-checkout-draft' ];
}

/**
 * Escape JSON for use on HTML or attribute text nodes.
 *
 * Copy of wc_esc_json() for compatibility.
 *
 * @since 4.8.0
 * @param string $json JSON to escape.
 * @param bool   $html True if escaping for HTML text node, false for attributes. Determines how quotes are handled.
 * @return string Escaped JSON.
 */
function aw_esc_json( $json, $html = false ) {
	return _wp_specialchars(
		$json,
		$html ? ENT_NOQUOTES : ENT_QUOTES,
		'UTF-8',
		true
	);
}

/**
 * Trigger a deprecated class error.
 *
 * This function should be called in the class file before the class is declared.
 *
 * @since 5.2.0
 *
 * @param string $class_name  The name of the deprecated class.
 * @param string $version     The version the class was deprecated.
 * @param string $replacement The replacement class name.
 */
function aw_deprecated_class( string $class_name, string $version, $replacement = null ) {
	if ( ! WP_DEBUG ) {
		return;
	}

	if ( $replacement ) {
		$message = sprintf(
			/* translators: 1: Deprecated class name, 2: Version number, 3: Replacement class name. */
			__( '%1$s is deprecated since version %2$s! Use %3$s instead.', 'automatewoo' ),
			$class_name,
			$version,
			$replacement
		);
	} else {
		$message = sprintf(
			/* translators: 1: Deprecated class name, 2: Version number. */
			__( '%1$s is deprecated since version %2$s with no alternative available.', 'automatewoo' ),
			$class_name,
			$version
		);
	}

	trigger_error( esc_html( $message ), E_USER_DEPRECATED );
}

/**
 * Get full name of the given user.
 *
 * @param \WP_User $user
 * @return string Full name.
 */
function aw_get_full_name( $user ) {
	if ( ! $user ) {
		return '';
	}

	return trim( sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo' ), $user->first_name, $user->last_name ) );
}

/**
 * Add product attributes to the product's permalink if it is a \WC_Order_Item_Product.
 *
 * Return an array containing the product object and its permalink because if the
 * passed product arg is a \WC_Order_Item_Product we should get \WC_Product from it and
 * returns back to the caller as the caller would use other methods from \WC_Product.
 *
 * @param \WC_Product|\WC_Order_Item_Product $product
 *
 * @since 6.0.4
 *
 * @return array
 */
function automatewoo_email_template_product_permalink( $product ) {
	if ( is_a( $product, 'WC_Order_Item_Product' ) ) {
		$item         = $product;
		$product      = $item->get_product();
		$product_meta = $item->get_formatted_meta_data();
		$permalink    = $product->get_permalink( [ 'item_meta_array' => $product_meta ] );
	} else {
		$permalink = $product->get_permalink();
	}

	return [
		'product'   => $product,
		'permalink' => $permalink,
	];
}

/**
 * Add product attributes to the product's name.
 *
 * Return an array containing the product object and its product name because if the
 * passed product arg is a \WC_Order_Item_Product we should get \WC_Product from it and
 * returns back to the caller as the caller would use other methods from \WC_Product.
 *
 * @param \WC_Product|\WC_Order_Item_Product $product
 *
 * @since 6.0.4
 *
 * @return array
 */
function automatewoo_email_template_product_name( $product ) {
	if ( is_a( $product, 'WC_Order_Item_Product' ) ) {
		$item         = $product;
		$product      = $item->get_product();
		$product_name = $product->get_name();
		$product_meta = $item->get_formatted_meta_data();

		// Part of the logic of generating product name refers to:
		// https://github.com/woocommerce/woocommerce/blob/462c690d613e1f5af3be9459b2aac8409a4587dc/plugins/woocommerce/includes/data-stores/class-wc-product-variation-data-store-cpt.php#L291-L313

		// Do not include attributes if the product has 3+ attributes.
		$should_include_attributes = apply_filters( 'woocommerce_product_variation_title_include_attributes', count( $product_meta ) < 3, $product );
		$separator                 = apply_filters( 'woocommerce_product_variation_title_attributes_separator', ' - ', $product );

		if ( $should_include_attributes ) {
			$title_suffix = [];

			foreach ( $product_meta as $meta ) {
				$value = $meta->value;
				if ( ! wc_is_attribute_in_product_name( $value, $product_name ) ) {
					$title_suffix[] = $value;
				}
			}

			if ( ! empty( $title_suffix ) ) {
				$product_name = $product_name . $separator . implode( ', ', $title_suffix );
			}
		}
	} else {
		$product_name = $product->get_name();
	}

	return [
		'product'      => $product,
		'product_name' => $product_name,
	];
}
