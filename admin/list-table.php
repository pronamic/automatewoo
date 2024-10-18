<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * @class Admin_List_Table
 * @since 3.0
 */
abstract class Admin_List_Table extends \WP_List_Table {

	/** @var string - name of the table, used for classes */
	public $name;

	/** @var string  */
	public $nonce_action = 'automatewoo-report-action';

	/** @var bool */
	public $enable_search = false;

	/** @var string  */
	public $search_input_id = 'automatewoo_search_table';

	/** @var string */
	public $search_button_text;

	/** @var string */
	protected $default_param_orderby = '';

	/** @var string */
	protected $default_param_order = 'DESC';

	/** @var int */
	public $max_items = 0;


	/**
	 * @param array|string $args
	 */
	public function __construct( $args ) {
		$this->search_button_text = __( 'Search', 'automatewoo' );
		wp_enqueue_script( 'automatewoo-modal' );
		parent::__construct( $args );
	}


	/**
	 * Output the report
	 */
	public function output_report() {
		$this->prepare_items();
		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		$this->display();
		echo '</div>';
	}



	/**
	 * @param \WP_User $user
	 * @return string
	 */
	public function format_user( $user ) {
		if ( $user ) {
			/* translators: 1: User First name, 2: User Last name */
			$name  = esc_attr( sprintf( _x( '%1$s %2$s', 'full name', 'automatewoo' ), $user->first_name, $user->last_name ) );
			$email = esc_attr( $user->user_email );
			return "$name <a href='mailto:$email'>$email</a> ";
		} else {
			return $this->format_blank();
		}
	}


	/**
	 * @param string $email The email
	 * @return string
	 */
	public function format_guest( $email ) {
		if ( $email ) {
			return esc_attr( __( '[Guest]', 'automatewoo' ) ) . ' ' . Format::email( $email );
		} else {
			return $this->format_blank();
		}
	}


	/**
	 * @return array
	 */
	protected function get_table_classes() {
		return [ 'automatewoo-list-table', 'automatewoo-list-table--' . $this->name, 'widefat', 'fixed', 'striped', $this->_args['plural'] ];
	}


	/**
	 * @param DateTime $date
	 * @param bool     $is_gmt
	 * phpcs:ignore Squiz.Commenting.FunctionComment.InvalidNoReturn
	 * @return string Return is void but the view is returned as string via "include" statement.
	 */
	public function format_date( $date, $is_gmt = true ) {
		Admin::get_view(
			'hoverable-date',
			[
				'date'          => $date,
				'shorten_month' => true,
				'is_gmt'        => $is_gmt,
			]
		);
	}


	/**
	 * @param Workflow|false $workflow
	 * @return string
	 */
	public function format_workflow_title( $workflow ) {

		if ( ! $workflow || ! $workflow->exists ) {
			return $this->format_blank();
		} else {
			$return = '<a href="' . get_edit_post_link( $workflow->get_id() ) . '"><strong>' . $workflow->get_title() . '</strong></a>';

			if ( Language::is_multilingual() ) {
				$return .= ' [' . $workflow->get_language() . ']';
			}

			return $return;
		}
	}


	/**
	 * @return string
	 */
	public function format_blank() {
		return '-';
	}


	/**
	 * Renders the filters
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		?>
		<?php if ( method_exists( $this, 'filters' ) ) : ?>
			<div style="display: inline-block">
					<?php $this->filters(); ?>
					<?php submit_button( __( 'Filter', 'automatewoo' ), 'button', 'submit', false ); ?>
			</div>
		<?php endif ?>
		<?php
	}

	/**
	 * Renders the opening of the form
	 */
	public function output_form_open() {
		echo '<form class="automatewoo-list-table-form" method="get">';
		Admin::get_hidden_form_inputs_from_query( [ 'page', 'section', 'tab' ] );
		wp_nonce_field( $this->nonce_action, '_wpnonce', false );
	}


	/**
	 * Renders the closing of the form
	 */
	public function output_form_close() {
		echo '</form>';
	}


	/**
	 * Display the table plus the form elements
	 */
	public function display() {
		$this->views();
		$this->output_form_open();

		if ( $this->enable_search ) {
			$this->output_search();
		}

		$this->output_table();
		$this->output_form_close();
	}


	/**
	 * Displays the search box
	 */
	public function output_search() {
		$this->search_box( $this->search_button_text, $this->search_input_id );
	}


	/**
	 * Output the table only
	 */
	public function output_table() {
		parent::display();
	}


	/**
	 * Displays the workflow filter
	 */
	public function output_workflow_filter() {

		$workflow_id   = '';
		$workflow_name = '';

		// This function just gets a Workflow ID from the URL and displays some HTML in the UI so no need to check nonce necessarily.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['_workflow'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$workflow_id   = absint( $_GET['_workflow'] );
			$workflow_name = get_the_title( $workflow_id );
		}

		?>

		<select class="wc-product-search"
				style="width:203px;"
				name="_workflow"
				data-placeholder="<?php esc_attr_e( 'Search for a workflow&hellip;', 'automatewoo' ); ?>"
				data-action="aw_json_search_workflows"
				data-allow_clear="true"
		>
			<?php
			if ( $workflow_id ) {
				echo '<option value="' . esc_attr( $workflow_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $workflow_name ) . '</option>';
			}
			?>
		</select>

		<?php
	}


	/**
	 * Displays the customer filter.
	 */
	public function output_customer_filter() {
		$customer_string = '';
		$customer        = Customer_Factory::get( aw_request( 'filter_customer' ) );

		if ( $customer ) {
			$customer_string = esc_html( $customer->get_full_name() ) . ' (' . esc_html( $customer->get_email() ) . ')';
		}

		?>

		<select class="wc-product-search"
				style="width:203px;"
				name="filter_customer"
				data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'automatewoo' ); ?>"
				data-action="aw_json_search_customers"
				data-allow_clear="true"
		>
			<?php
			if ( $customer ) {
				echo '<option value="' . esc_attr( $customer->get_id() ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $customer_string ) . '</option>'; }
			?>
		</select>

		<?php
	}




	/**
	 * Override nonce used in this table, to use nonces declared in controllers
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) : ?>
					<?php if ( $this->get_bulk_actions() ) : ?>
					<div class="alignleft actions bulkactions">
						<?php $this->bulk_actions( $which ); ?>
					</div>
				<?php endif; ?>
				<?php
			endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}


	/**
	 * @return string
	 */
	protected function get_param_search() {
		return Clean::string( aw_request( 's' ) );
	}


	/**
	 * @return string
	 */
	protected function get_param_orderby() {
		return aw_request( 'orderby' ) ?: $this->default_param_orderby;
	}


	/**
	 * @return string
	 */
	protected function get_param_order() {
		return aw_request( 'order' ) ?: $this->default_param_order;
	}

	/**
	 * Generate the HTML for a view link.
	 *
	 * @param string $id
	 * @param string $title
	 * @param int    $count      The view item count (optional).
	 * @param bool   $is_default Is this the default view option?
	 * @param string $query_arg  Defaults to 'view'.
	 *
	 * @return string
	 * @since 4.6
	 */
	protected function generate_view_link_html( $id, $title, $count = null, $is_default = false, $query_arg = 'view' ) {
		// This function just gets a View ID from the URL and displays some HTML in the UI so no need to check nonce necessarily.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $query_arg ] ) ) {
			$view = $is_default ? $id : '';
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view = sanitize_key( $_GET[ $query_arg ] );
		}

		$url        = add_query_arg( $query_arg, $id );
		$class      = $id === $view ? 'current' : '';
		$count_html = '';

		if ( $count ) {
			$count_html = ' <span class="count">(' . esc_html( $count ) . ')</span>';
		}

		return '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $title ) . $count_html . '</a>';
	}

	/**
	 * Get the current view.
	 *
	 * @since 4.6
	 *
	 * @param string $default_view
	 * @param string $query_arg
	 *
	 * @return string
	 */
	public function get_current_view( $default_view = '', $query_arg = 'view' ) {
		// This function just returns GET[$query_arg] param sanitized and doesn't perform any operation
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ $query_arg ] ) ) {
			return $default_view;
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_key( $_GET[ $query_arg ] );
		}
	}
}
