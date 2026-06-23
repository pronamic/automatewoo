<?php

namespace AutomateWoo\Admin\Controllers;

use AutomateWoo;
use AutomateWoo\Options;
use AutomateWoo\Dashboard_Widget;
use AutomateWoo\Cache;
use AutomateWoo\Clean;
use AutomateWoo\DateTime;
use AutomateWoo\HPOS_Helper;
use AutomateWoo\Admin\Analytics\Rest_API;
use AutomateWoo\Workflows\Factory;

defined( 'ABSPATH' ) || exit;

/**
 * @class Dashboard
 */
class Dashboard extends Base {

	const HIDDEN_WIDGETS_USER_OPTION = 'automatewoo_dashboard_hidden_widgets';
	const SCREEN_OPTIONS_ACTION      = 'automatewoo_dashboard_screen_options';
	const SCREEN_OPTIONS_NONCE       = 'automatewoo_dashboard_screen_options_nonce';
	const SCREEN_OPTIONS_SUBMITTED   = 'automatewoo_dashboard_screen_options_submitted';
	const SCREEN_OPTIONS_WIDGETS     = 'automatewoo_dashboard_widgets';
	const VISIBLE_WIDGETS            = 'automatewoo_dashboard_visible_widgets';

	/** @var array */
	private $widgets;

	/** @var array */
	private $logs;

	/** @var array */
	private $carts;

	/** @var array */
	private $guests;

	/** @var array */
	private $optins_count;

	/** @var array */
	private $conversions;

	/** @var int */
	private $guests_count;

	/** @var int */
	private $active_carts_count;

	/** @var int */
	private $queued_count;

	/** @var Workflow */
	private $most_run_workflow;

	/** @var Workflow */
	private $highest_converting_workflow;

	/**
	 * Handle the main dashboard page output.
	 */
	public function handle() {

		wp_enqueue_script( 'automatewoo-dashboard' );

		$this->maybe_set_date_cookie();

		$widgets    = $this->get_visible_widgets( $this->get_widgets() );
		$date_arg   = $this->get_date_arg();
		$date_range = $this->get_date_range();
		$date_tabs  = [
			'90days' => __( '90 days', 'automatewoo' ),
			'30days' => __( '30 days', 'automatewoo' ),
			'14days' => __( '14 days', 'automatewoo' ),
			'7days'  => __( '7 days', 'automatewoo' ),
		];

		foreach ( $widgets as $i => $widget ) {
			$widget->set_date_range( $date_range['from'], $date_range['to'] );
			if ( ! $widget->display ) {
				unset( $widgets[ $i ] );
			}
		}

		$this->output_view(
			'page-dashboard',
			[
				'widgets'      => $widgets,
				'date_text'    => $date_tabs[ $date_arg ],
				'date_current' => $this->get_date_arg(),
				'date_tabs'    => $date_tabs,
			]
		);
	}

	/**
	 * Get all dashboard widgets.
	 *
	 * @return Dashboard_Widget[]
	 */
	public function get_widgets() {

		if ( ! isset( $this->widgets ) ) {

			$path = AW()->path( '/admin/dashboard-widgets/' );

			$includes = [];

			if ( Rest_API::is_enabled() ) {
				$includes[] = $path . 'analytics-workflows-run.php';
				$includes[] = $path . 'analytics-conversions.php';
				$includes[] = $path . 'analytics-email.php';
			} else {
				$includes[] = $path . 'chart-workflows-run.php';
				$includes[] = $path . 'chart-conversions.php';
				$includes[] = $path . 'chart-email.php';
			}

			$includes = apply_filters( 'automatewoo/dashboard/chart_widgets', $includes );

			$includes[] = $path . 'key-figures.php';
			$includes[] = $path . 'workflows.php';
			$includes[] = $path . 'logs.php';
			$includes[] = $path . 'queue.php';

			$includes = apply_filters( 'automatewoo/dashboard/widgets', $includes );

			foreach ( $includes as $include ) {
				/** @var Dashboard_Widget $class */
				$class                       = require_once $include;
				$class->controller           = $this;
				$this->widgets[ $class->id ] = $class;
			}
		}

		return $this->widgets;
	}

	/**
	 * Add dashboard Screen Options.
	 */
	public function screen_options() {
		$this->maybe_save_screen_options();

		add_filter( 'screen_options_show_screen', '__return_true' );
		add_filter( 'screen_options_show_submit', '__return_true' );
		add_filter( 'screen_settings', [ $this, 'render_screen_options' ], 10, 2 );
	}

	/**
	 * Render dashboard Screen Options.
	 *
	 * @param string     $settings Current screen settings HTML.
	 * @param \WP_Screen $screen Current screen.
	 *
	 * @return string
	 */
	public function render_screen_options( $settings, $screen ) {
		$widgets            = $this->get_widgets();
		$hidden_widget_ids  = $this->get_hidden_widget_ids();
		$default_label_map  = $this->get_default_widget_label_map();
		$screen_options_key = self::SCREEN_OPTIONS_WIDGETS . '[]';
		$visible_key        = self::VISIBLE_WIDGETS . '[]';

		ob_start();
		?>
		<fieldset class="metabox-prefs automatewoo-dashboard-screen-options">
			<legend><?php esc_html_e( 'Dashboard widgets', 'automatewoo' ); ?></legend>
			<input type="hidden" name="<?php echo esc_attr( self::SCREEN_OPTIONS_SUBMITTED ); ?>" value="1" />
			<input type="hidden" name="<?php echo esc_attr( self::SCREEN_OPTIONS_NONCE ); ?>" value="<?php echo esc_attr( wp_create_nonce( self::SCREEN_OPTIONS_ACTION ) ); ?>" />
			<?php foreach ( $widgets as $widget ) : ?>
				<?php
				$widget_id = $widget->get_id();
				$field_id  = 'automatewoo-dashboard-widget-' . $widget_id;
				?>
				<input type="hidden" name="<?php echo esc_attr( $screen_options_key ); ?>" value="<?php echo esc_attr( $widget_id ); ?>" />
				<label for="<?php echo esc_attr( $field_id ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $visible_key ); ?>" value="<?php echo esc_attr( $widget_id ); ?>" <?php checked( ! in_array( $widget_id, $hidden_widget_ids, true ) ); ?> />
					<?php echo esc_html( $this->get_widget_label( $widget, $default_label_map ) ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php

		return $settings . ob_get_clean();
	}

	/**
	 * Remove dashboard widgets hidden by the current user.
	 *
	 * @param Dashboard_Widget[] $widgets Dashboard widgets.
	 *
	 * @return Dashboard_Widget[]
	 */
	public function get_visible_widgets( $widgets ) {
		foreach ( $this->get_hidden_widget_ids() as $widget_id ) {
			unset( $widgets[ $widget_id ] );
		}

		return $widgets;
	}

	/**
	 * Get dashboard widgets hidden by the current user.
	 *
	 * @return string[]
	 */
	public function get_hidden_widget_ids() {
		$hidden_widget_ids = get_user_option( self::HIDDEN_WIDGETS_USER_OPTION );

		if ( ! is_array( $hidden_widget_ids ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'sanitize_key', $hidden_widget_ids ) ) );
	}

	/**
	 * Save dashboard Screen Options.
	 */
	private function maybe_save_screen_options() {
		if ( empty( $_POST[ self::SCREEN_OPTIONS_SUBMITTED ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::SCREEN_OPTIONS_NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::SCREEN_OPTIONS_NONCE ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::SCREEN_OPTIONS_ACTION ) ) {
			return;
		}

		$widget_ids = $this->get_posted_widget_ids();
		if ( empty( $widget_ids ) ) {
			return;
		}

		$visible_widget_ids = $this->get_posted_visible_widget_ids();
		$hidden_widget_ids  = array_values( array_diff( $widget_ids, $visible_widget_ids ) );

		update_user_option( get_current_user_id(), self::HIDDEN_WIDGETS_USER_OPTION, $hidden_widget_ids );
	}

	/**
	 * Get posted dashboard widget IDs.
	 *
	 * @return string[]
	 */
	private function get_posted_widget_ids() {
		if ( empty( $_POST[ self::SCREEN_OPTIONS_WIDGETS ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return [];
		}

		return $this->sanitize_widget_ids( (array) wp_unslash( $_POST[ self::SCREEN_OPTIONS_WIDGETS ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Get posted visible dashboard widget IDs.
	 *
	 * @return string[]
	 */
	private function get_posted_visible_widget_ids() {
		if ( empty( $_POST[ self::VISIBLE_WIDGETS ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return [];
		}

		return $this->sanitize_widget_ids( (array) wp_unslash( $_POST[ self::VISIBLE_WIDGETS ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Sanitize widget IDs.
	 *
	 * @param array $widget_ids Widget IDs.
	 *
	 * @return string[]
	 */
	private function sanitize_widget_ids( $widget_ids ) {
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $widget_ids ) ) ) );
	}

	/**
	 * Get default widget labels.
	 *
	 * @return string[]
	 */
	private function get_default_widget_label_map() {
		return apply_filters(
			'automatewoo/dashboard/widget_labels',
			[
				'analytics-workflows-run' => __( 'Workflows run', 'automatewoo' ),
				'chart-workflows-run'     => __( 'Workflows run', 'automatewoo' ),
				'analytics-conversions'   => __( 'Conversion revenue', 'automatewoo' ),
				'chart-conversions'       => __( 'Conversion revenue', 'automatewoo' ),
				'analytics-email'         => __( 'Messages sent', 'automatewoo' ),
				'chart-email'             => __( 'Messages sent', 'automatewoo' ),
				'key-figures'             => __( 'Key figures', 'automatewoo' ),
				'workflows'               => __( 'Featured workflows', 'automatewoo' ),
				'logs'                    => __( 'Recent logs', 'automatewoo' ),
				'queue'                   => __( 'Upcoming queued events', 'automatewoo' ),
			]
		);
	}

	/**
	 * Get the display label for a widget.
	 *
	 * @param Dashboard_Widget $widget Widget.
	 * @param string[]         $default_label_map Default labels keyed by widget ID.
	 *
	 * @return string
	 */
	private function get_widget_label( $widget, $default_label_map ) {
		$widget_id = $widget->get_id();

		if ( isset( $default_label_map[ $widget_id ] ) ) {
			return $default_label_map[ $widget_id ];
		}

		if ( ! empty( $widget->title ) ) {
			return $widget->title;
		}

		return ucwords( str_replace( [ '-', '_' ], ' ', $widget_id ) );
	}

	/**
	 * Get the date argument from the request.
	 *
	 * @return string
	 */
	public function get_date_arg() {

		$cookie_name = 'automatewoo_dashboard_date';

		if ( ! aw_request( 'date' ) && isset( $_COOKIE[ $cookie_name ] ) ) {
			return Clean::string( $_COOKIE[ $cookie_name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( aw_request( 'date' ) ) {
			$date = Clean::string( aw_request( 'date' ) );
			return $date;
		}

		return '30days';
	}

	/**
	 * Set the date cookie if a date is passed in the request.
	 */
	public function maybe_set_date_cookie() {
		if ( aw_request( 'date' ) ) {
			$date = Clean::string( aw_request( 'date' ) );
			$sent = headers_sent();
			if ( ! $sent ) {
				wc_setcookie( 'automatewoo_dashboard_date', $date, time() + MONTH_IN_SECONDS * 2, is_ssl() );
			}
		}
	}

	/**
	 * Get the date range for the current date argument.
	 *
	 * @return array
	 */
	public function get_date_range() {

		$range = $this->get_date_arg();

		$from = new DateTime();
		$to   = new DateTime();

		switch ( $range ) {
			case '14days':
				$from->modify( '-14 days' );
				break;
			case '7days':
				$from->modify( '-7 days' );
				break;
			case '30days':
				$from->modify( '-30 days' );
				break;
			case '90days':
				$from->modify( '-90 days' );
				break;
		}

		return apply_filters(
			'automatewoo/dashboard/date_range',
			[
				'from' => $from,
				'to'   => $to,
			]
		);
	}

	/**
	 * Get a list of logs for the current date range.
	 *
	 * @return AutomateWoo\Log[]
	 */
	public function get_logs() {
		if ( ! isset( $this->logs ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Log_Query();
			$query->where_date_between( $date['from'], $date['to'] );

			$this->logs = $query->get_results();
		}

		return $this->logs;
	}

	/**
	 * Get the count of active carts. The count is cached in the object cache.
	 *
	 * @return int
	 */
	public function get_active_carts_count() {
		if ( isset( $this->active_carts_count ) ) {
			return $this->active_carts_count;
		}

		$count = Cache::get( 'active_carts_count', 'dashboard' );
		if ( false !== $count ) {
			return (int) $count;
		}

		$query = new AutomateWoo\Cart_Query();
		$query->where_status( 'active' );
		$this->active_carts_count = (int) $query->get_count();
		Cache::set( 'active_carts_count', $this->active_carts_count, 'dashboard' );

		return $this->active_carts_count;
	}

	/**
	 * Get a list of guests for the current date range.
	 *
	 * @return AutomateWoo\Guest[]
	 */
	public function get_guests() {
		if ( ! isset( $this->guests ) ) {

			$date = $this->get_date_range();

			$query = new AutomateWoo\Guest_Query();
			$query->where( 'created', $date['from'], '>' );
			$query->where( 'created', $date['to'], '<' );

			$this->guests = $query->get_results();
		}

		return $this->guests;
	}

	/**
	 * Get the count of queued workflows. The count is cached in the object cache.
	 *
	 * @return int
	 */
	public function get_guests_count() {
		if ( isset( $this->guests_count ) ) {
			return $this->guests_count;
		}

		$cache_key = 'guests_count_' . $this->get_date_arg();
		$count     = Cache::get( $cache_key, 'dashboard' );
		if ( false !== $count ) {
			return (int) $count;
		}

		$date = $this->get_date_range();

		$query = new AutomateWoo\Guest_Query();
		$query->where( 'created', $date['from'], '>' );
		$query->where( 'created', $date['to'], '<' );

		$this->guests_count = (int) $query->get_count();
		Cache::set( $cache_key, $this->guests_count, 'dashboard' );

		return $this->guests_count;
	}

	/**
	 * Get the count of queued workflows. The count is cached in the object cache.
	 *
	 * @return int
	 */
	public function get_queued_count() {
		if ( isset( $this->queued_count ) ) {
			return $this->queued_count;
		}

		$cache_key = 'queued_workflow_count_' . $this->get_date_arg();
		$count     = Cache::get( $cache_key, 'dashboard' );
		if ( false !== $count ) {
			return (int) $count;
		}

		$date = $this->get_date_range();

		$query = new AutomateWoo\Queue_Query();
		$query->where_date_created_between( $date['from'], $date['to'] );

		$this->queued_count = (int) $query->get_count();
		Cache::set( $cache_key, $this->queued_count, 'dashboard' );

		return $this->queued_count;
	}

	/**
	 * Get customers who have opted IN or OUT
	 * (whichever is the opposite of the default configured setting).
	 *
	 * @return int
	 */
	public function get_optins_count() {
		if ( isset( $this->optins_count ) ) {
			return $this->optins_count;
		}

		$cache_key = 'optin_count_' . $this->get_date_arg();
		$count     = Cache::get( $cache_key, 'dashboard' );
		if ( false !== $count ) {
			return (int) $count;
		}

		$date = $this->get_date_range();

		$query = new AutomateWoo\Customer_Query();

		if ( Options::optin_enabled() ) {
			$query->where( 'subscribed', true );
			$query->where( 'subscribed_date', $date['from'], '>' );
			$query->where( 'subscribed_date', $date['to'], '<' );
		} else {
			$query->where( 'unsubscribed', true );
			$query->where( 'unsubscribed_date', $date['from'], '>' );
			$query->where( 'unsubscribed_date', $date['to'], '<' );
		}

		$this->optins_count = (int) $query->get_count();
		Cache::set( $cache_key, $this->optins_count, 'dashboard' );

		return $this->optins_count;
	}

	/**
	 * Get a list of orders that resulted in a conversion.
	 *
	 * @return \WC_Order[]
	 */
	public function get_conversions() {
		if ( ! isset( $this->conversions ) ) {
			$date = $this->get_date_range();

			$this->conversions = wc_get_orders(
				[
					'type'         => 'shop_order',
					'status'       => wc_get_is_paid_statuses(),
					'limit'        => -1,
					'meta_key'     => '_aw_conversion',
					'meta_compare' => 'EXISTS',
					'date_created' => $date['from']->getTimestamp() . '...' . $date['to']->getTimestamp(),
				]
			);
		}

		return $this->conversions;
	}

	/**
	 * Get the most run workflow.
	 *
	 * @since 6.1.9
	 *
	 * @return Workflow|null $workflow The most run workflow.
	 */
	public function get_most_run_workflow() {
		global $wpdb;

		if ( isset( $this->most_run_workflow ) ) {
			return $this->most_run_workflow;
		}

		$cache_key   = 'most_run_workflow_' . $this->get_date_arg();
		$workflow_id = Cache::get( $cache_key, 'dashboard' );
		if ( false !== $workflow_id ) {
			$workflow = Factory::get( $workflow_id );
			if ( $workflow && get_post_status( $workflow_id ) !== 'trash' ) {
				$this->most_run_workflow = $workflow;
				return $this->most_run_workflow;
			}

			// The cached workflow was trashed or deleted after being cached; fall through to the query.
			Cache::delete( $cache_key, 'dashboard' );
		}

		$date = $this->get_date_range();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS count, l.workflow_id
					FROM `{$wpdb->prefix}automatewoo_logs` AS l
					INNER JOIN `{$wpdb->posts}` AS p ON p.ID = l.workflow_id
					WHERE l.date >= %s AND l.date <= %s
					AND p.post_status != 'trash'
					GROUP BY l.workflow_id
					ORDER BY count DESC
					LIMIT 1",
				$date['from']->to_mysql_string(),
				$date['to']->to_mysql_string()
			)
		);

		if ( $row && $row->workflow_id ) {
			$this->most_run_workflow = Factory::get( $row->workflow_id );
			Cache::set( $cache_key, $row->workflow_id, 'dashboard' );
			return $this->most_run_workflow;
		}

		return null;
	}

	/**
	 * Get the highest converting workflow.
	 *
	 * @since 6.1.9
	 *
	 * @return Workflow|null $workflow The highest converting workflow.
	 */
	public function get_highest_converting_workflow() {
		global $wpdb;

		if ( isset( $this->highest_converting_workflow ) ) {
			return $this->highest_converting_workflow;
		}

		$cache_key   = 'highest_converting_workflow_' . $this->get_date_arg();
		$workflow_id = Cache::get( $cache_key, 'dashboard' );
		if ( false !== $workflow_id ) {
			$workflow = Factory::get( $workflow_id );
			if ( $workflow && get_post_status( $workflow_id ) !== 'trash' ) {
				$this->highest_converting_workflow = $workflow;
				return $this->highest_converting_workflow;
			}

			// The cached workflow was trashed or deleted after being cached; fall through to the query.
			Cache::delete( $cache_key, 'dashboard' );
		}

		if ( HPOS_Helper::is_HPOS_enabled() ) {
			$date     = $this->get_date_range();
			$statuses = "'" . implode( "','", array_map( 'aw_add_order_status_prefix', wc_get_is_paid_statuses() ) ) . "'";

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT SUM( orders.total_amount ) AS conversion_total, meta.meta_value AS workflow_id
						FROM {$wpdb->prefix}wc_orders AS orders
						INNER JOIN {$wpdb->prefix}wc_orders_meta AS meta ON orders.id = meta.order_id
						INNER JOIN {$wpdb->posts} AS posts ON posts.ID = meta.meta_value
						WHERE orders.status IN ( {$statuses} )
						AND orders.type = 'shop_order'
						AND meta.meta_key = '_aw_conversion'
						AND posts.post_status != 'trash'
						AND ( orders.date_created_gmt >= %s AND orders.date_created_gmt <= %s )
						GROUP BY meta.meta_value
						ORDER BY SUM( orders.total_amount ) DESC
						LIMIT 1",
					$date['from']->to_mysql_string(),
					$date['to']->to_mysql_string()
				)
			);
			// phpcs:enable

			if ( $row && $row->workflow_id ) {
				$this->highest_converting_workflow = Factory::get( $row->workflow_id );
				Cache::set( $cache_key, $row->workflow_id, 'dashboard' );
				return $this->highest_converting_workflow;
			}

			return null;
		}

		// Fallback for when HPOS is not enabled.
		$conversions = $this->get_conversions();
		$totals      = [];

		foreach ( $conversions as $order ) {
			$workflow_id = absint( $order->get_meta( '_aw_conversion' ) );

			if ( isset( $totals[ $workflow_id ] ) ) {
				$totals[ $workflow_id ] += $order->get_total();
			} else {
				$totals[ $workflow_id ] = $order->get_total();
			}
		}

		arsort( $totals, SORT_NUMERIC );

		// The HPOS path above filters out trashed workflows via SQL. This PHP check
		// is the equivalent filter for this non-HPOS fallback, which has no SQL filter.
		// Note: the raw post status is checked because Workflow::get_status() reports
		// manual-type workflows as 'active' regardless of their post status.
		foreach ( array_keys( $totals ) as $workflow_id ) {
			$workflow = Factory::get( $workflow_id );
			if ( $workflow && get_post_status( $workflow_id ) !== 'trash' ) {
				$this->highest_converting_workflow = $workflow;
				Cache::set( $cache_key, $workflow_id, 'dashboard' );
				return $this->highest_converting_workflow;
			}
		}

		return null;
	}
}

return new Dashboard();
