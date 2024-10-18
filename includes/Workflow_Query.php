<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Workflow_Query
 */
class Workflow_Query {

	/** @var string|array */
	public $trigger;

	/** @var int */
	public $limit = -1;

	/** @var array */
	public $args;

	/** @var string */
	public $return = 'objects';

	/**
	 * WP_Query instance of the last query.
	 *
	 * @since 4.9.0
	 *
	 * @var WP_Query
	 */
	protected $last_wp_query;


	function __construct() {
		$this->args = [
			'post_type' => 'aw_workflow',
			'post_status' => 'publish',
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'posts_per_page' => $this->limit,
			'meta_query' => [],
			'suppress_filters' => true,
			'no_found_rows' => true
		];
	}


	/**
	 * Set trigger name or array of names to query.
	 *
	 * @param string|array $trigger
	 */
	function set_trigger( $trigger ) {
		if ( $trigger instanceof Trigger ) {
			$this->trigger = $trigger->get_name();
		}
		else {
			$this->trigger = $trigger;
		}
	}

	/**
	 * Set included ids array to query.
	 *
	 * @param string|array $ids
	 */
	function set_include( $ids ) {
		$this->args['post__in'] = wp_parse_list( $ids );
	}

	/**
	 * Set per page limit query param.
	 *
	 * @param int $limit
	 */
	public function set_limit( $limit ) {
		$this->args['posts_per_page'] = absint( $limit );
	}

	/**
	 * Set page query param.
	 *
	 * @since 4.9.0
	 *
	 * @param int $page
	 */
	public function set_page( $page ) {
		$this->args['paged'] = absint( $page );
	}

	/**
	 * Set stats query param.
	 *
	 * Default status is active.
	 *
	 * @param string $status One of: any|active|disabled
	 */
	public function set_status( $status ) {
		$status_map = [
			'any'      => 'any',
			'active'   => 'publish',
			'disabled' => 'aw-disabled'
		];

		$this->args['post_status'] = isset( $status_map[ $status ] ) ? $status_map[ $status ] : $status;
	}

	/**
	 * Set type query param.
	 *
	 * @param string $type
	 *
	 * @since 5.0.0
	 */
	public function set_type( $type ) {
		$this->add_meta_query( 'type', $type );
	}

	/**
	 * Set search query param.
	 *
	 * @since 4.9.0
	 *
	 * @param string $term
	 */
	public function set_search( $term ) {
		$this->args['s'] = $term;
	}

	/**
	 * Set the value of the no_found_rows query param.
	 *
	 * Default value is true.
	 *
	 * Must be set to false in order to use $this->get_found_rows()
	 *
	 * @since 4.9.0
	 *
	 * @param bool $no_found_rows
	 */
	public function set_no_found_rows( $no_found_rows ) {
		$this->args['no_found_rows'] = (bool) $no_found_rows;
	}

	/**
	 * @param $return - objects|ids
	 */
	function set_return( $return ) {
		$this->return = $return;
	}

	/**
	 * Add a meta query.
	 *
	 * @since 5.1.0
	 *
	 * @param string $key     The meta key.
	 * @param string $value   The meta value.
	 * @param string $compare The meta compare.
	 */
	public function add_meta_query( $key, $value, $compare = '=' ) {
		$this->args['meta_query'][] = [
			'key' => $key,
			'value' => $value,
			'compare' => $compare,
		];
	}


	/**
	 * @return Workflow[]
	 */
	function get_results() {

		if ( $this->trigger ) {
			$this->args['meta_query'][] = [
				'key' => 'trigger_name',
				'value' => $this->trigger,
			];
		}

		if ( $this->return == 'ids' ) {
			$this->args['fields'] = 'ids';
		}
		$query = new WP_Query( $this->args );
		$posts = $query->posts;

		if ( ! $posts ) {
			return [];
		}

		$workflows = [];

		foreach ( $posts as $post ) {

			if ( $this->return == 'ids' ) {
				$workflows[] = $post;
			}
			else {
				$workflow = new Workflow($post);
				$workflows[] = $workflow;
			}

		}

		$this->last_wp_query = $query;
		return $workflows;
	}

	/**
	 * Get found rows for the last query.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public function get_found_rows() {
		if ( ! $this->last_wp_query instanceof WP_Query ) {
			return 0;
		}

		return $this->last_wp_query->found_posts;
	}


	/**
	 * Alias of self::set_trigger()
	 *
	 * @param string|array $trigger
	 */
	function set_triggers( $trigger ) {
		$this->set_trigger( $trigger );
	}

}
