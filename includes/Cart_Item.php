<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use WC_Product;

/**
 * @class Cart_Item
 * @since 3.2.6
 */
class Cart_Item {

	/** @var string */
	protected $key;

	/** @var array */
	protected $data;

	/** @var WC_Product */
	protected $product;

	/**
	 * @param string $key
	 * @param array $data
	 */
	public function __construct( string $key, array $data ) {
		$this->key  = $key;
		$this->data = $data;

		$this->prepare_cart_item_data();
	}

	/**
	 * Prepare cart item object and data.
	 *
	 * Duplicates some of the WC Cart Item functionality that is coupled to the session logic in WooCommerce core to
	 * maximise compatibility with other extensions.
	 *
	 * @since 5.3.0
	 *
	 * @see   \WC_Cart_Session::get_cart_from_session
	 */
	protected function prepare_cart_item_data() {
		// Load the associated product
		$product = wc_get_product( $this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id() );

		if ( empty( $product ) || ! $product->exists() || 0 >= $this->data['quantity'] ) {
			// TODO exception should be thrown here due to error when finding product
		}

		// Mimic how WC core adds the full product object to the cart_item data
		$this->data['data'] = $product;

		// Cache the product object since we'll need it later
		$this->product = $product;
	}


	/**
	 * Get the product object for the cart item.
	 *
	 * @return WC_Product
	 */
	public function get_product() {
		return $this->product;
	}


	/**
	 * @return string
	 */
	public function get_name(): string {
		$product = $this->get_product();

		if ( $product ) {
			return apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $this->data, $this->key );
		}
		return '';
	}

	/**
	 * Return the product_title if it's stored in the cart item data.
	 *
	 * @return string
	 */
	public function get_cart_item_name() {
		return isset( $this->data['product_title'] ) ? $this->data['product_title'] : '';
	}


	/**
	 * @return int
	 */
	function get_key() {
		return $this->key;
	}


	/**
	 * @return array
	 */
	function get_data() {
		return $this->data;
	}


	/**
	 * @return array
	 */
	function get_variation_data() {
		return isset( $this->data['variation'] ) && is_array( $this->data['variation'] ) ? $this->data['variation'] : [];
	}


	/**
	 * @return int
	 */
	function get_product_id() {
		return isset( $this->data['product_id'] ) ? Clean::id( $this->data['product_id'] ) : 0;
	}


	/**
	 * @param $id
	 */
	function set_product_id( $id ) {
		$this->data['product_id'] = Clean::id( $id );

		// Hack alert: Since we allow externally redefining the product_id we need to re-prepare the item data.
		$this->prepare_cart_item_data();
	}


	/**
	 * @return int
	 */
	function get_variation_id() {
		return isset( $this->data['variation_id'] ) ? Clean::id( $this->data['variation_id'] ) : 0;
	}


	/**
	 * @param $id
	 */
	function set_variation_id( $id ) {
		$this->data['variation_id'] = Clean::id( $id );

		// Hack alert: Since we allow externally redefining the variation_id we need to re-prepare the item data.
		$this->prepare_cart_item_data();
	}


	/**
	 * @return float
	 */
	function get_line_subtotal() {
		return isset( $this->data['line_subtotal'] ) ? floatval( $this->data['line_subtotal'] ) : 0;
	}


	/**
	 * @return float
	 */
	function get_line_subtotal_tax() {
		return isset( $this->data['line_subtotal_tax'] ) ? floatval( $this->data['line_subtotal_tax'] ) : 0;
	}

	/**
	 * Get item quantity.
	 *
	 * @return int|float
	 */
	function get_quantity() {
		$quantity = wc_stock_amount( isset( $this->data['quantity'] ) ? $this->data['quantity'] : 0 );

		return apply_filters( 'automatewoo/cart_item/get_quantity', $quantity, $this );
	}

	/**
	 * Gets and formats a list of cart item data + variations for display on the frontend.
	 *
	 * @param bool $flat (default: false)
	 * @return string
	 */
	public function get_item_data_html( $flat = false ) {
		$item_data = [];

		foreach ( $this->get_variation_data() as $name => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

			// If this is a term slug, get the term's nice name
			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					$value = $term->name;
				}
				$label = wc_attribute_label( $taxonomy );

				// If this is a custom option slug, get the options name.
			} else {
				$value = apply_filters( 'woocommerce_variation_option_name', $value );
				$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $this->get_product() );
			}

			// Check the nicename against the title.
			if ( '' === $value || wc_is_attribute_in_product_name( $value, $this->get_name() ) ) {
				continue;
			}

			$item_data[] = [
				'key'   => $label,
				'value' => $value,
			];
		}

		// Filter item data to allow 3rd parties to add more to the array
		$item_data = apply_filters( 'woocommerce_get_item_data', $item_data, $this->data );

		// Format item data ready to display
		foreach ( $item_data as $key => $data ) {
			// Set hidden to true to not display meta on cart.
			if ( ! empty( $data['hidden'] ) ) {
				unset( $item_data[ $key ] );
				continue;
			}
			$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
			$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
		}

		// Output flat or in list format
		if ( sizeof( $item_data ) > 0 ) {
			ob_start();

			if ( $flat ) {
				foreach ( $item_data as $data ) {
					echo esc_html( wp_strip_all_tags( $data['key'] ) ) . ': ' . wp_kses_post( $data['display'] ) . "\n";
				}
			} else {
				wc_get_template( 'cart/cart-item-data.php', [ 'item_data' => $item_data ] );
			}

			return ob_get_clean();
		}

		return '';
	}



}
