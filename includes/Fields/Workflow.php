<?php

namespace AutomateWoo\Fields;

use AutomateWoo\Clean;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Workflow
 */
class Workflow extends Select {

	/** @var string */
	protected $name = 'workflow';

	/** @var array  */
	public $query_args = [];


	/**
	 * @param bool $show_placeholder
	 */
	public function __construct( $show_placeholder = true ) {
		parent::__construct( $show_placeholder );
		$this->set_title( __( 'Workflow', 'automatewoo' ) );
	}


	/**
	 * @return array
	 */
	public function get_options() {

		$args = array_merge(
			[
				'post_type'      => 'aw_workflow',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			],
			$this->query_args
		);

		$workflows = new \WP_Query( $args );

		$options = [];

		if ( $workflows->have_posts() ) {
			foreach ( $workflows->posts as $workflow ) {
				$options[ $workflow->ID ] = $workflow->post_title;
			}
		}

		return $options;
	}


	/**
	 * @param string $key
	 * @param mixed  $value
	 * @return $this
	 */
	public function add_query_arg( $key, $value ) {
		$this->query_args[ $key ] = $value;
		return $this;
	}


	/**
	 * Sanitizes the value of the field.
	 *
	 * @since 4.4.0
	 *
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_value( $value ) {
		if ( $this->multiple ) {
			return Clean::ids( $value );
		} else {
			return Clean::id( $value );
		}
	}
}
