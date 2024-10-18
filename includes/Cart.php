<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Cart
 */
class Cart extends Model {

	const STATUS_ACTIVE    = 'active';
	const STATUS_ABANDONED = 'abandoned';

	/** @var string */
	public $table_id = 'carts';

	/** @var string  */
	public $object_type = 'cart';

	/** @var float */
	public $calculated_total = 0;

	/** @var float */
	public $calculated_tax_total = 0;

	/** @var float */
	public $calculated_subtotal = 0;

	/** @var array */
	protected $translated_items_cache;


	/**
	 * @param bool|int $id
	 */
	function __construct( $id = false ) {
		if ( $id ) {
			$this->get_by( 'id', $id );
		}
	}


	/**
	 * @return string
	 */
	function get_status() {
		$status = $this->get_prop( 'status' );
		return $status ? Clean::string( $status ) : 'abandoned';
	}


	/**
	 * @param $status - active|abandoned
	 */
	function set_status( $status ) {
		$this->set_prop( 'status', Clean::string( $status ) );
	}


	/**
	 * Transition status, triggers change hooks
	 * @param $new_status - active|abandoned
	 */
	function update_status( $new_status ) {

		$old_status = $this->get_status();

		if ( $new_status == $old_status ) {
			return;
		}

		$this->set_status( $new_status );
		$this->save();
		do_action( 'automatewoo/cart/status_changed', $this, $old_status, $new_status );
	}


	/**
	 * @return int
	 */
	function get_user_id() {
		return Clean::id( $this->get_prop( 'user_id' ) );
	}


	/**
	 * @param int $user_id
	 */
	function set_user_id( $user_id ) {
		$this->set_prop( 'user_id', Clean::id( $user_id ) );
	}


	/**
	 * @return int
	 */
	function get_guest_id() {
		return Clean::id( $this->get_prop( 'guest_id' ) );
	}


	/**
	 * @param int $guest_id
	 */
	function set_guest_id( $guest_id ) {
		$this->set_prop( 'guest_id', Clean::id( $guest_id ) );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_last_modified() {
		return $this->get_date_column( 'last_modified' );
	}


	/**
	 * @param DateTime|string $date
	 */
	function set_date_last_modified( $date ) {
		$this->set_date_column( 'last_modified', $date );
	}


	/**
	 * @return bool|DateTime
	 */
	function get_date_created() {
		return $this->get_date_column( 'created' );
	}


	/**
	 * @param DateTime $date
	 */
	function set_date_created( $date ) {
		$this->set_date_column( 'created', $date );
	}


	/**
	 * @return float
	 */
	function get_total() {
		return (float) $this->get_prop( 'total' );
	}


	/**
	 * @param $total
	 */
	function set_total( $total ) {
		$this->set_prop( 'total', wc_format_decimal( $total ) );
	}


	/**
	 * @param $val
	 */
	function set_shipping_total( $val ) {
		$this->set_prop( 'shipping_total', wc_format_decimal( $val ) );
	}


	/**
	 * @param $val
	 */
	function set_shipping_tax_total( $val ) {
		$this->set_prop( 'shipping_tax_total', wc_round_tax_total( $val ) );
	}

	/**
	 * @return float
	 */
	function get_shipping_total() {
		return (float) $this->get_prop( 'shipping_total' );
	}


	/**
	 * @return float
	 */
	function get_shipping_tax_total() {
		return (float) $this->get_prop( 'shipping_tax_total' );
	}


	/**
	 * @return string
	 */
	function get_token() {
		return Clean::string( $this->get_prop( 'token' ) );
	}


	/**
	 * @param bool|string $token (optional)
	 */
	function set_token( $token = false ) {
		if ( ! $token ) {
			$token = aw_generate_key( 32 );
		}

		$this->set_prop( 'token', Clean::string( $token ) );
	}


	/**
	 * @return float
	 */
	function get_currency() {
		$currency = $this->get_prop( 'currency' );
		if ( $currency ) {
			return Clean::string( $currency );
		}
		return get_woocommerce_currency();
	}


	/**
	 * @param $currency
	 */
	function set_currency( $currency ) {
		$this->set_prop( 'currency', Clean::string( $currency ) );
	}


	/**
	 * @return string
	 */
	function get_shipping_total_html() {
		$total = get_option( 'woocommerce_tax_display_cart' ) === 'excl' ? $this->get_shipping_total() : $this->get_shipping_total() + $this->get_shipping_tax_total();

		if ( $total == 0 ) {
			$html = __( 'Free!', 'automatewoo' );
		} else {
			$html = $this->price( $total );
		}

		return apply_filters( 'automatewoo/cart/get_shipping_total_html', $html, $this );
	}


	/**
	 * @return bool
	 */
	function needs_shipping() {

		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return false;
		}

		$needs_shipping = false;

		if ( $this->has_items() ) {
			foreach ( $this->get_items() as $cart_item ) {
				$product = $cart_item->get_product();
				if ( $product && $product->needs_shipping() ) {
					$needs_shipping = true;
					break;
				}
			}
		}

		return $needs_shipping;
	}


	/**
	 * @return bool
	 */
	function has_coupons() {
		return sizeof( $this->get_coupons() ) > 0;
	}


	/**
	 * @return array
	 */
	function get_coupons() {
		$coupons = $this->get_prop( 'coupons' );
		return is_array( $coupons ) ? $coupons : [];
	}


	/**
	 * @param array $coupon_data
	 */
	function set_coupons( $coupon_data ) {
		$this->set_prop( 'coupons', (array) $coupon_data );
	}


	/**
	 * @return array
	 */
	function get_fees() {
		$fees = $this->get_prop( 'fees' );
		return is_array( $fees ) ? $fees : [];
	}


	/**
	 * @param array $fees_data
	 */
	function set_fees( $fees_data ) {
		$this->set_prop( 'fees', (array) $fees_data );
	}


	/**
	 * @return bool
	 */
	function has_items() {
		$items = $this->get_prop( 'items' );
		return is_array( $items ) && sizeof( $items ) > 0;
	}


	/**
	 * Get cart items.
	 *
	 * @return Cart_Item[]
	 */
	public function get_items() {
		$raw_item_data = $this->get_prop( 'items' );
		$items     = [];

		if ( is_array( $raw_item_data ) ) {
			$items = $this->convert_cart_item_data_to_cart_item_objects( $raw_item_data );

			if ( Language::is_multilingual() ) {
				if ( ! isset( $this->translated_items_cache ) ) {
					$this->translated_items_cache = $this->translate_items( $items, $this->get_language() );
				}

				$items = $this->translated_items_cache;
			}
		}

		return apply_filters( 'automatewoo/cart/get_items', $items );
	}

	/**
	 * Convert raw cart item data into cart item objects.
	 *
	 * @since 5.3.0
	 *
	 * @param array $raw_items
	 *
	 * @return Cart_Item[]
	 */
	protected function convert_cart_item_data_to_cart_item_objects( array $raw_items ): array {
		$parsed_items = [];

		foreach( $raw_items as $item_key => $item_data ) {
			if ( ! is_string( $item_key ) || ! is_array( $item_data ) ) {
				// Stored cart data is invalid.
				continue;
			}

			$parsed_items[ $item_key ] = new Cart_Item( $item_key, $item_data );
		}

		return $parsed_items;
	}


	/**
	 * @return array
	 */
	function get_items_raw() {
		$raw = [];
		foreach ( $this->get_items() as $item ) {
			$raw[ $item->get_key() ] = $item->get_data();
		}
		return $raw;
	}


	/**
	 * @since 4.2
	 * @return int
	 */
	function get_item_count() {
		$count = 0;

		foreach ( $this->get_items() as $item ) {
			$count += $item->get_quantity();
		}

		return apply_filters( 'automatewoo/cart/get_item_count', $count, $this->get_items() );
	}

	/**
	 * Get translated cart items in a specified language.
	 *
	 * @since 4.6.0
	 *
	 * @param Cart_Item[] $items
	 * @param string      $lang
	 *
	 * @return Cart_Item[]
	 */
	protected function translate_items( $items, $lang ) {
		if ( Language::is_multilingual() ) {
			foreach ( $items as &$item ) {
				$item->set_product_id( icl_object_id( $item->get_product_id(), 'product', true, $lang ) );
				$item->set_variation_id( icl_object_id( $item->get_variation_id(), 'product', true, $lang ) );
			}
		}

		return $items;
	}


	/**
	 * @param array $items
	 */
	function set_items( $items ) {
		$this->translated_items_cache = null;
		$this->set_prop( 'items', (array) $items );
	}


	/**
	 * @return Guest|false
	 */
	function get_guest() {
		if ( ! $this->get_guest_id() ) {
			return false;
		}
		return Guest_Factory::get( $this->get_guest_id() );
	}


	/**
	 * @return Customer|bool
	 */
	function get_customer() {
		if ( $this->get_user_id() ) {
			return Customer_Factory::get_by_user_id( $this->get_user_id() );
		}
		else {
			return Customer_Factory::get_by_guest_id( $this->get_guest_id() );
		}
	}

	/**
	 * @return string
	 */
	function get_language() {
		if ( $this->get_customer() ) {
			return $this->get_customer()->get_language();
		}
		return Language::get_default();
	}


	/**
	 * Updates the stored cart with the current time and cart items
	 */
	function sync() {
		$this->set_date_last_modified( new DateTime() );
		$this->set_items( $this->get_cart_for_sync() );

		$coupon_data = [];

		foreach( WC()->cart->get_applied_coupons() as $coupon_code ) {
			$coupon_data[$coupon_code] = [
				'discount_incl_tax' => WC()->cart->get_coupon_discount_amount( $coupon_code, false ),
				'discount_excl_tax' => WC()->cart->get_coupon_discount_amount( $coupon_code ),
				'discount_tax' => WC()->cart->get_coupon_discount_tax_amount( $coupon_code )
			];
		}

		$this->set_coupons( $coupon_data );
		$this->set_fees( WC()->cart->get_fees() );
		$this->set_currency( get_woocommerce_currency() );
		$this->set_shipping_tax_total( WC()->cart->shipping_tax_total );
		$this->set_shipping_total( WC()->cart->shipping_total );

		$this->calculate_totals();

		$this->set_total( $this->calculated_total );

		if ( $this->get_status() == 'abandoned' ) {
			$this->update_status( 'active' );
		}
		else {
			$this->save();
		}
	}

	/**
	 * Returns the contents of the cart in an array with the product title but without the 'data' element.
	 * Based on WC core WC()->session->get_cart_for_session()
	 *
	 * @since 5.6.9
	 *
	 * @return array Contents of the cart
	 */
	public function get_cart_for_sync(): array {
		$cart         = WC()->cart->get_cart();
		$cart_session = array();

		foreach ( $cart as $key => $values ) {
			$cart_session[ $key ]                  = $values;
			$cart_session[ $key ]['product_title'] = $cart_session[ $key ]['data']->get_title();

			unset( $cart_session[ $key ]['data'] ); // Unset product object.
		}

		return $cart_session;
	}

	function calculate_totals() {

		$this->calculated_subtotal = 0;
		$this->calculated_tax_total = 0;
		$this->calculated_total = 0;

		$tax_display = get_option( 'woocommerce_tax_display_cart' );

		foreach( $this->get_items() as $item ) {
			$this->calculated_tax_total += $item->get_line_subtotal_tax();
			$this->calculated_total += $item->get_line_subtotal() + $item->get_line_subtotal_tax();
			$this->calculated_subtotal += $tax_display === 'excl' ? $item->get_line_subtotal() : $item->get_line_subtotal() + $item->get_line_subtotal_tax();
		}

		foreach ( $this->get_coupons() as $coupon_code => $coupon ) {
			$this->calculated_total -= $coupon[ 'discount_incl_tax' ];
			$this->calculated_tax_total -= $coupon['discount_tax'];
		}

		foreach ( $this->get_fees() as $fee ) {
			$this->calculated_total += ( $fee->amount + $fee->tax );
			$this->calculated_tax_total += $fee->tax;
		}

		$this->calculated_tax_total += $this->get_shipping_tax_total();
		$this->calculated_total += $this->get_shipping_total();
		$this->calculated_total += $this->get_shipping_tax_total();
	}


	/**
	 * @param float $price
	 * @return string
	 */
	function price( $price ) {
		return wc_price( $price, apply_filters( 'automatewoo/cart/price_args', [], $this ) );
	}


	/**
	 * Save
	 */
	function save() {

		if ( ! $this->exists && ! $this->has_prop( 'created' ) ) {
			$this->set_date_created( new DateTime() );
		}

		parent::save();
	}


}

