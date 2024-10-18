<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Variable_Shop_Products
 */
class Variable_Shop_Products extends Variable_Abstract_Product_Display {

	public $support_limit_field = true;


	function load_admin_details() {

		$this->add_parameter_select_field( 'type', __( "Determines which products will be displayed.", 'automatewoo'), [
			'featured' => __( 'Featured', 'automatewoo' ),
			'sale' => __( 'Sale', 'automatewoo' ),
			'recent' => __( 'Recent', 'automatewoo' ),
			'top_selling' => __( 'Top Selling', 'automatewoo' ),
			'category' => __( 'By Product Category', 'automatewoo' ),
			'tag' => __( 'By Product Tag', 'automatewoo' ),
			'ids' => __( 'By Product IDs', 'automatewoo' ),
			'custom' => __( 'By Custom Filter', 'automatewoo' )
		], true );

		$this->add_parameter_text_field( 'ids', __( "Display products by ID, use '+' as a delimiter. E.g. 34+12+5", 'automatewoo'), true, '', [
			'show' => 'type=ids'
		] );

		$this->add_parameter_text_field( 'category', __( "Display products by product category slug. E.g. clothing or clothing+shoes", 'automatewoo'), true, '', [
			'show' => 'type=category'
		] );

		$this->add_parameter_text_field( 'tag', __( "Display products by product tag slug. E.g. winter or winter+summer", 'automatewoo'), true, '', [
			'show' => 'type=tag'
		] );

		$this->add_parameter_text_field( 'filter', __( "Display products by using a WP filter.", 'automatewoo'), true, '', [
			'show' => 'type=custom'
		] );

		$this->add_parameter_select_field( 'sort', __( "Set the sorting of the products.", 'automatewoo'), [
			''                => __( 'Default', 'automatewoo' ),
			'date-desc'       => __( 'Date added - Descending', 'automatewoo' ),
			'date-asc'        => __( 'Date added - Ascending', 'automatewoo' ),
			'title-desc'      => __( 'Title - Descending', 'automatewoo' ),
			'title-asc'       => __( 'Title - Ascending', 'automatewoo' ),
			'popularity-desc' => __( 'Popularity - Descending', 'automatewoo' ),
			'popularity-asc'  => __( 'Popularity - Ascending', 'automatewoo' ),
			'random'          => __( 'Random', 'automatewoo' ),
		] );


		parent::load_admin_details();

		$this->description = __( "Display your shop's products by various criteria.", 'automatewoo');
	}


	/**
	 * Get the value of the variable.
	 *
	 * @param array    $parameters
	 * @param Workflow $workflow
	 *
	 * @return string
	 */
	function get_value( $parameters, $workflow ) {
		$template = isset( $parameters['template'] ) ? $parameters['template'] : false;

		$query_args = $this->get_product_query_args( $parameters, $workflow );

		if ( ! $query_args ) {
			return false;
		}

		$products = aw_get_products( $query_args );

		$args = array_merge( $this->get_default_product_template_args( $workflow, $parameters ), [
			'products' => $products
		]);

		return $this->get_product_display_html( $template, $args );
	}

	/**
	 * Get product query args based on the variable params.
	 *
	 * @param array    $parameters
	 * @param Workflow $workflow
	 *
	 * @since 4.4.0
	 *
	 * @return array|false
	 */
	public function get_product_query_args( $parameters, $workflow ) {
		$type  = isset( $parameters['type'] ) ? $parameters['type'] : false;
		$sort  = isset( $parameters['sort'] ) ? $parameters['sort'] : '';
		$limit = isset( $parameters['limit'] ) ? absint( $parameters['limit'] ) : 8;

		if ( ! $type ) {
			return false;
		}

		$args = [
			'limit' => $limit,
		];

		$args += $this->parse_sort_param( $sort );

		switch ( $type ) {
			case 'ids':
				if ( empty( $parameters['ids'] ) ) {
					return false;
				}

				$args[ 'include' ] = $this->parse_ids_param( $parameters['ids'] );
				break;

			case 'category':
				if ( empty( $parameters['category'] ) ) {
					return false;
				}

				$args[ 'category' ] = $this->parse_taxonomy_param( $parameters['category'] );
				break;

			case 'tag':
				if ( empty( $parameters['tag'] ) ) {
					return false;
				}

				$args[ 'tag' ] = $this->parse_taxonomy_param( $parameters['tag'] );
				break;

			case 'featured':
				$args[ 'featured' ] = true;
				break;

			case 'sale':
				$on_sale = wc_get_product_ids_on_sale();
				if ( ! $on_sale ) {
					return false;
				}
				$args[ 'include' ] = $on_sale;
				break;

			case 'recent':
				// get twice as many products as needed so that random sorting doesn't
				// always result in the same recent products
				$product_ids = aw_get_recent_product_ids( $limit * 2 );

				if ( ! $product_ids ) {
					return false;
				}

				$args[ 'include' ] = $product_ids;
				break;

			case 'top_selling':
				// get twice as many products as needed so that random sorting doesn't
				// always result in the same top selling products
				$product_ids = aw_get_top_selling_product_ids( $limit * 2 );

				if ( ! $product_ids ) {
					return false;
				}

				$args[ 'include' ] = $product_ids;
				break;

			case 'custom':
				if ( empty( $parameters['filter'] ) ) {
					return false;
				}

				$product_ids = apply_filters( $parameters['filter'], [], $workflow, $parameters );
				$args[ 'include' ] = $product_ids;
				break;
		}

		return wp_parse_args( $args, $this->get_default_product_query_args() );
	}


	/**
	 * Parse the sort param value for product query.
	 *
	 * @since 4.4.0
	 *
	 * @param string $sorting
	 *
	 * @return array
	 */
	public function parse_sort_param( $sorting ) {
		// set default values
		$orderby = 'include';
		$order = false;

		$sorting = explode('-', $sorting );

		if ( ! empty( $sorting[0] ) ) {
			$orderby = $sorting[0];
		}

		if ( ! empty( $sorting[1] ) ) {
			$order = strtoupper( $sorting[1] );
		}

		// map 'title' to 'name'
		if ( $orderby === 'title' ) {
			$orderby = 'name';
		}

		if ( $orderby === 'random' ) {
			$orderby = 'rand';
			$order   = false;
		}

		return array_filter( [
			'orderby' => $orderby,
			'order'   => $order
		] );
	}


	/**
	 * Parse the taxonomy params for product query.
	 * Slugs should be separated by '+'.
	 *
	 * @since 4.4.0
	 *
	 * @param string $param
	 *
	 * @return array
	 */
	public function parse_taxonomy_param( $param ) {
		if ( empty( $param ) ) {
			return [];
		}

		$return = [];

		foreach ( explode( '+', $param ) as $slug ) {
			$return[] = $slug;
		}

		return $return;
	}

	/**
	 * Parse the IDs param for product query.
	 * IDs should be separated by '+'.
	 *
	 * @since 4.4.0
	 *
	 * @param string $param
	 *
	 * @return array
	 */
	public function parse_ids_param( $param ) {
		$ids = explode( '+', $param );
		return array_map( 'absint', $ids );
	}

}
