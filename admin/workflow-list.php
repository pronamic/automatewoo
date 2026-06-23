<?php

namespace AutomateWoo;

use AutomateWoo\Workflows\TimingDescriptionGenerator;
use AutomateWoo\Workflows\Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Admin_Workflow_List
 * @since 2.6.1
 */
class Admin_Workflow_List {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'manage_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_posts_custom_column', [ $this, 'column_data' ], 10, 2 );
		add_filter( 'bulk_actions-edit-aw_workflow', [ $this, 'bulk_actions' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'row_actions' ], 10, 2 );
		add_filter( 'request', [ $this, 'filter_request_query_vars' ] );
		add_filter( 'views_edit-aw_workflow', [ $this, 'filter_views' ] );

		$this->statuses();
	}

	/**
	 * Alters the columns displayed in the Posts list table.
	 *
	 * @param string[] $columns An associative array of column headings.
	 * @return string[] Altered column headings.
	 */
	public function columns( $columns ) {

		unset( $columns['date'] );
		unset( $columns['stats'] );
		unset( $columns['likes'] );

		$columns['timing']           = __( 'Timing', 'automatewoo' );
		$columns['times_run']        = __( 'Run Count', 'automatewoo' );
		$columns['queued']           = __( 'Queue Count', 'automatewoo' );
		$columns['aw_status_toggle'] = '<span class="screen-reader-text">' . esc_html__( 'Status', 'automatewoo' ) . '</span>';

		return $columns;
	}

	/**
	 * Alters a custom column in the Posts list table.
	 *
	 * @param string $column The name of the column to display.
	 * @param int    $post_id The current post ID.
	 */
	public function column_data( $column, $post_id ) {
		$workflow = Factory::get( $post_id );

		if ( ! $workflow ) {
			return;
		}

		switch ( $column ) {

			case 'timing':
				echo wp_kses( $this->get_timing_text( $workflow ), array( 'b' => array() ) );
				break;

			case 'times_run':
				$count = $workflow->get_times_run();
				if ( $count ) {
					echo '<a href="' . esc_url( add_query_arg( '_workflow', $workflow->get_id(), Admin::page_url( 'logs' ) ) ) . '">' . esc_attr( $count ) . '</a>';
				} else {
					echo '-';
				}
				break;

			case 'queued':
				$count = $workflow->get_current_queue_count();
				if ( $count ) {
					echo '<a href="' . esc_url( add_query_arg( '_workflow', $workflow->get_id(), Admin::page_url( 'queue' ) ) ) . '">' . esc_attr( $count ) . '</a>';
				} else {
					echo '-';
				}
				break;

			case 'aw_status_toggle':
				if ( 'manual' === $workflow->get_type() ) {
					$url = Admin::page_url( 'manual-workflow-runner', $workflow->get_id() );
					printf(
						'<a href="%s" class="button button-primary alignright">%s</a>',
						esc_url( $url ),
						esc_html__( 'Run', 'automatewoo' )
					);
				} else {
					printf(
						'<button type="button" class="%s" data-workflow-id="%s" data-aw-switch="%s">%s</button>',
						'aw-switch js-toggle-workflow-status',
						esc_attr( $workflow->get_id() ),
						esc_attr( $workflow->is_active() ? 'on' : 'off' ),
						esc_html__( 'Toggle Status', 'automatewoo' )
					);
				}
				break;

		}
	}


	/**
	 * Tweak workflow statuses
	 */
	public function statuses() {

		global $wp_post_statuses;

		/* translators: %s: the count of published workflows */
		$wp_post_statuses['publish']->label_count = _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'automatewoo' );
	}


	/**
	 * Alters the items in the bulk actions menu of the list table.
	 *
	 * @param array $actions An array of the available bulk actions.
	 *
	 * @return array The altered bulk actions.
	 */
	public function bulk_actions( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Alters the array of row action links on the Posts list table.
	 *
	 * @param string[] $actions An array of row action links. Defaults are
	 *                           'Edit', 'Quick Edit', 'Restore', 'Trash',
	 *                           'Delete Permanently', 'Preview', and 'View'.
	 *
	 * @return string[] The altered array of row action links.
	 */
	public function row_actions( $actions ) {
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}


	/**
	 * Get the timing description string for a workflow.
	 *
	 * @param Workflow $workflow The workflow to get the timing description string.
	 * @return string The timing description string.
	 */
	public function get_timing_text( $workflow ) {
		try {
			return ( new TimingDescriptionGenerator( $workflow ) )->generate();
		} catch ( \Exception $e ) {
			return '-';
		}
	}


	/**
	 * Is manual view?
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function is_manual_view() {
		return (bool) aw_get_url_var( 'filter_manual' );
	}

	/**
	 * Filter workflow list main request query vars.
	 *
	 * @param array $query_vars
	 *
	 * @return array
	 */
	public function filter_request_query_vars( $query_vars ) {
		$is_all_view = empty( $query_vars['post_status'] );

		// Include disabled workflows in all view
		if ( $is_all_view ) {
			$query_vars['post_status'] = [ 'publish', 'aw-disabled' ];
		}

		if ( $this->is_manual_view() ) {
			$query_vars['meta_query'] = [
				[
					'key'   => 'type',
					'value' => 'manual',
				],
			];
		}

		return $query_vars;
	}

	/**
	 * Filter views on the workflow list table.
	 *
	 * @since 5.0.0
	 *
	 * @param array $views
	 *
	 * @return array
	 */
	public function filter_views( $views ) {
		$url = remove_query_arg( 'post_status', add_query_arg( 'filter_manual', 1 ) );

		$views['manual'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
			esc_url( $url ),
			$this->is_manual_view() ? esc_attr( 'current' ) : '',
			esc_html__( 'Manual', 'automatewoo' ),
			number_format_i18n( Workflows::get_manual_workflows_count() )
		);

		$search_term = (string) get_query_var( 's' );
		if ( '' !== $search_term ) {
			$views = $this->update_view_counts_for_search( $views, $search_term );
		}

		$trash = aw_array_extract( $views, 'trash' );
		if ( $trash ) {
			$views['trash'] = $trash;
		}

		return $views;
	}

	/**
	 * Update view counts to reflect the current search query.
	 *
	 * @since 6.5.0
	 *
	 * @param array  $views       The view links.
	 * @param string $search_term The search term from the main query.
	 *
	 * @return array The updated view links.
	 */
	private function update_view_counts_for_search( array $views, string $search_term ): array {
		$status_map = [
			'all'         => [ 'publish', 'aw-disabled' ],
			'publish'     => [ 'publish' ],
			'aw-disabled' => [ 'aw-disabled' ],
			'trash'       => [ 'trash' ],
		];

		foreach ( $status_map as $view_key => $post_statuses ) {
			if ( ! isset( $views[ $view_key ] ) ) {
				continue;
			}

			$count = $this->get_workflow_search_count( $search_term, $post_statuses );

			$views[ $view_key ] = preg_replace(
				'/(<span class="count">\()[^)]+(\)<\/span>)/',
				'${1}' . number_format_i18n( $count ) . '${2}',
				$views[ $view_key ]
			);
		}

		if ( isset( $views['manual'] ) ) {
			$count = $this->get_workflow_search_count(
				$search_term,
				[ 'publish', 'aw-disabled' ],
				[
					[
						'key'   => 'type',
						'value' => 'manual',
					],
				]
			);

			$views['manual'] = preg_replace(
				'/(<span class="count">\()[^)]+(\)<\/span>)/',
				'${1}' . number_format_i18n( $count ) . '${2}',
				$views['manual']
			);
		}

		return $views;
	}

	/**
	 * Get the count of workflows matching a search term and status.
	 *
	 * @since 6.5.0
	 *
	 * @param string $search_term  The search term.
	 * @param array  $post_statuses The post statuses to query.
	 * @param array  $meta_query   Optional meta query arguments.
	 *
	 * @return int The number of matching workflows.
	 */
	private function get_workflow_search_count( string $search_term, array $post_statuses, array $meta_query = [] ): int {
		$query_args = [
			'post_type'              => 'aw_workflow',
			'post_status'            => $post_statuses,
			's'                      => $search_term,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query = new \WP_Query( $query_args );

		return $query->found_posts;
	}
}
