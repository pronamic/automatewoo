<?php

namespace AutomateWoo;

/**
 * @class Wishlist
 * @since 2.9.9
 */
class Wishlist {

	/** @var $id */
	public $id;

	/** @var $owner_id */
	public $owner_id;

	/** @var $items */
	public $items;


	/**
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}


	/**
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->owner_id );
	}


	/**
	 * @return Customer|bool
	 */
	public function get_customer() {
		return Customer_Factory::get_by_user_id( $this->get_user_id() );
	}


	/**
	 * @return string
	 */
	public function get_integration() {
		return Wishlists::get_integration();
	}


	/**
	 * Get wishlist items.
	 *
	 * @return int[]
	 */
	public function get_items() {
		if ( isset( $this->items ) ) {
			return $this->items;
		}

		$this->items = [];

		if ( $this->get_integration() === 'yith' ) {

			$products = YITH_WCWL()->get_products(
				[
					'wishlist_id' => $this->get_id(),
					'user_id'     => $this->get_user_id(),
					'session_id'  => false,
				]
			);

			if ( ! empty( $products ) ) {
				foreach ( $products as $product ) {
					// Before v3.0 products were arrays
					if ( is_array( $product ) ) {
						$this->items[] = $product['prod_id'];
					} elseif ( $product instanceof \YITH_WCWL_Wishlist_Item ) {
						$this->items[] = $product->get_product_id();
					}
				}
			}
		} elseif ( $this->get_integration() === 'woothemes' ) {

			$products = get_post_meta( $this->get_id(), '_wishlist_items', true );

			if ( $products ) {
				foreach ( $products as $product ) {
					$this->items[] = $product['product_id'];
				}
			}
		}

		$this->items = array_unique( Clean::ids( $this->items ) );

		return $this->items;
	}


	/**
	 * @return string
	 */
	public function get_link() {
		if ( $this->get_integration() === 'yith' ) {
			return YITH_WCWL()->get_wishlist_url();
		} elseif ( $this->get_integration() === 'woothemes' ) {
			if ( class_exists( 'WC_Wishlists_Pages' ) ) {
				// SEMGREP WARNING EXPLANATION
				// This is escaped with esc_url_raw, but semgrep only takes into consideration esc_url.
				// Also, the URL is not reaching any user input.
				return esc_url_raw(
					add_query_arg(
						[ 'wlid' => $this->get_id() ],
						\WC_Wishlists_Pages::get_url_for( 'view-a-list' )
					)
				);
			}
		}
		return '';
	}


	/**
	 * @return string
	 */
	protected function get_date_created_option_name() {
		return '_automatewoo_wishlist_date_created_' . $this->get_id();
	}


	/**
	 * @return DateTime|false UTC
	 * @throws \Exception Emits exception if the date created value isn't valid.
	 */
	public function get_date_created() {
		$val = get_option( $this->get_date_created_option_name() );
		if ( ! $val ) {
			return false;
		}

		return new DateTime( $val );
	}


	/**
	 * @param DateTime $date UTC
	 */
	public function set_date_created( $date ) {
		if ( ! is_a( $date, 'DateTime' ) ) {
			return;
		}
		update_option( $this->get_date_created_option_name(), $date->to_mysql_string(), false );
	}
}
