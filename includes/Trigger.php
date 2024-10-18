<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Workflows\Factory;

/**
 * @class Trigger
 */
abstract class Trigger {

	/** @var string */
	public $title;

	/** @var string */
	public $name;

	/** @var string */
	public $description;

	/** @var string */
	public $group;

	/** @var array */
	public $supplied_data_items = [];

	/** @var array */
	public $fields = [];

	/** @var array */
	public $options;

	/** @var array */
	protected $rules;

	/** @var bool */
	protected $has_loaded_fields = false;

	/** @var bool */
	public $has_loaded_admin_details = false;

	/** @var array */
	protected $limit_trigger_to_specific_workflows = [];

	/**
	 * Async events required by the trigger.
	 *
	 * @since 4.8.0
	 * @var array|string
	 */
	protected $required_async_events;

	/**
	 * Define whether workflows using this trigger can be queued. Defaults to true
	 * @since 3.8
	 */
	const SUPPORTS_QUEUING = true;

	/**
	 * Define if workflows using this trigger are run daily at a specific time. Defaults to false.
	 * @since 3.8
	 */
	const SUPPORTS_CUSTOM_TIME_OF_DAY = false;


	abstract function register_hooks();


	/**
	 * Construct
	 */
	function __construct() {
		$this->init();
		$this->supplied_data_items[] = 'shop';

		// compatibility for user and customer objects
		if ( in_array( 'user', $this->supplied_data_items ) ) {
			$this->supplied_data_items[] = 'customer';
		}

		// backwards compat for custom triggers using the user data item, IMPORTANT to exclude guest triggers
		if ( in_array( 'customer', $this->supplied_data_items ) && ! in_array( 'guest', $this->supplied_data_items ) ) {
			$this->supplied_data_items[] = 'user';
		}

		$this->supplied_data_items = array_unique( $this->supplied_data_items );

		add_action( 'automatewoo_init_triggers', [ $this, 'register_hooks' ] );
	}


	/**
	 * Init
	 */
	function init() {}


	/**
	 * Method to set title, group, description and other admin props
	 */
	function load_admin_details() {}


	/**
	 * Registers any fields used on for a trigger
	 */
	function load_fields() {}


	/**
	 * Admin info loader
	 */
	function maybe_load_admin_details() {
		if ( ! $this->has_loaded_admin_details ) {
			$this->load_admin_details();
			$this->has_loaded_admin_details = true;
		}
	}


	/**
	 * Field loader
	 */
	function maybe_load_fields() {
		if ( ! $this->has_loaded_fields ) {
			$this->load_fields();
			$this->has_loaded_fields = true;
		}
	}


	/**
	 * @param \AutomateWoo\Workflow $workflow
	 * @return bool
	 */
	function validate_workflow( $workflow ) {
		return true;
	}

	/**
	 * @param $option object
	 */
	function add_field( $option ) {
		$option->set_name_base( 'aw_workflow_data[trigger_options]' );
		$this->fields[ $option->get_name() ] = $option;
	}


	/**
	 * @param $option_name
	 */
	function remove_field( $option_name ) {
		if ( isset( $this->fields[ $option_name ] ) ) {
			unset( $this->fields[ $option_name ] );
		}
	}


	/**
	 * @return array
	 */
	function get_supplied_data_items() {
		return $this->supplied_data_items;
	}


	/**
	 * @param string $name
	 *
	 * @return Fields\Field|false
	 */
	function get_field( $name ) {
		$this->maybe_load_fields();

		if ( ! isset( $this->fields[$name] ) ) {
			return false;
		}

		return $this->fields[$name];
	}


	/**
	 * @return Fields\Field[]
	 */
	function get_fields() {
		$this->maybe_load_fields();
		return $this->fields;
	}

	/**
	 * Get a list of required field names.
	 *
	 * @since 6.0.10
	 *
	 * @return Field[]
	 */
	public function get_required_fields(): array {
		$required_fields = [];
		foreach ( $this->get_fields() as $name => $field ) {
			if ( $field->get_required() ) {
				$required_fields[ $name ] = $field;
			}
		}

		return $required_fields;
	}

	/**
	 * @return bool
	 */
	function has_workflows() {
		$query = new \WP_Query([
			'post_type' => 'aw_workflow',
			'post_status' => 'publish',
			'fields' => 'ids',
			'posts_per_page' => 1,
			'meta_query' => [
				[
					'key' => 'trigger_name',
					'value' => $this->get_name()
				]
			],
			'suppress_filters' => true,
			'no_found_rows' => true
		]);

		return $query->post_count != 0;
	}


	/**
	 * @return array
	 */
	function get_workflow_ids() {
		if ( $this->limit_trigger_to_specific_workflows ) {
			// workflow ids have been explicitly set
			return $this->limit_trigger_to_specific_workflows;
		}

		// otherwise get all workflows that using this trigger, including other translations
		$query = new Workflow_Query();
		$query->set_return( 'ids' );
		$query->set_trigger( $this->get_name() );
		$workflows = $query->get_results();

		return $workflows;
	}


	/**
	 * @return Workflow[]
	 */
	function get_workflows() {
		$workflows = [];

		foreach ( $this->get_workflow_ids() as $workflow_id ) {
			if ( $workflow = Factory::get( $workflow_id ) ) {
				$workflows[] = $workflow;
			}
		}

		return apply_filters( 'automatewoo/trigger/workflows', $workflows, $this );
	}


	/**
	 * Every data item registered with the trigger should be supplied to this method in its object form.
	 * E.g. a 'user' should be passed as a WP_User object, and an 'order' should be passed as a WC_Order object
	 *
	 * @param Data_Layer|array $data_layer
	 */
	function maybe_run( $data_layer = [] ) {

		// Get all workflows that are registered to use this trigger
		if ( ! $workflows = $this->get_workflows() ) {
			return;
		}

		// Check if each workflow should be run based on its options
		foreach ( $workflows as $workflow ) {
			$workflow->maybe_run( $data_layer );
		}
	}


	/**
	 * @return string
	 */
	function get_name() {
		return $this->name;
	}


	/**
	 * @param string $name
	 */
	function set_name( $name ) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	function get_title() {
		$this->maybe_load_admin_details();
		return $this->title;
	}


	/**
	 * @return string
	 */
	function get_group() {
		$this->maybe_load_admin_details();
		return $this->group ? $this->group : __( 'Other', 'automatewoo' );
	}


	/**
	 * @return string|null
	 */
	function get_description() {
		$this->maybe_load_admin_details();
		return $this->description;
	}


	/**
	 * @return string
	 */
	function get_description_html() {

		if ( ! $this->get_description() )
			return '';

		return '<p class="aw-field-description">' . $this->get_description() .'</p>';
	}


	/**
	 * @param $options array
	 * @deprecated
	 */
	function set_options( $options ) {
		$this->options = $options;
	}


	/**
	 * Will return all data if $field is false
	 *
	 * @param string $field
	 * @return mixed
	 *
	 * @deprecated use $workflow->get_trigger_option()
	 */
	function get_option( $field ) {

		if ( ! $field ) return false;

		$value = false;

		if ( isset( $this->options[$field] ) ) {
			$value = $this->options[$field];
		}

		return apply_filters( 'automatewoo_trigger_option', $value, $field, $this );
	}



	/**
	 * This method is called just before a queued workflow runs
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	function validate_before_queued_event( $workflow ) {
		return true;
	}


	/**
	 * Checks if this trigger's language matches that of the user or guest
	 *
	 * @param Workflow $workflow
	 * @return bool
	 */
	function validate_workflow_language( $workflow ) {

		if ( ! Integrations::is_wpml() ) {
			return true;
		}

		if ( ! $workflow_lang = $workflow->get_language() ) {
			return true; // workflow has no set language
		}

		if ( ! $data_lang = $workflow->data_layer()->get_customer_language() ) {
			return true;
		}

		return $data_lang == $workflow_lang;
	}


	/**
	 * @since 4.2.0
	 * @param array|int $workflow_ids
	 */
	function limit_trigger_to_specific_workflows( $workflow_ids ) {
		$this->limit_trigger_to_specific_workflows = (array) $workflow_ids;
	}


	/**
	 * @since 4.2.0
	 * Removes trigger limitation and allows triggering for any workflow
	 */
	function remove_limit_trigger_to_specific_workflows() {
		$this->limit_trigger_to_specific_workflows = [];
	}



	protected function add_field_validate_queued_order_status() {

		$field = new Fields\Checkbox();
		$field->set_name('validate_order_status_before_queued_run');
		$field->set_title( __('Recheck status before run', 'automatewoo' ) );
		$field->default_to_checked = true;
		$field->set_description(
			__( "This is useful for Workflows that are not run immediately as it ensures the status hasn't changed since initial trigger." ,
				'automatewoo'  ) );

		$this->add_field( $field );
	}


	/**
	 *
	 */
	protected function add_field_user_pause_period() {

		$field = ( new Fields\Positive_Number() )
			->set_name( 'user_pause_period' )
			->set_title( __( 'Customer pause period (days)', 'automatewoo' ) )
			->set_description( __( 'Can be used to ensure that this trigger will only send once in a set period to a user or guest.', 'automatewoo' ) );
		$this->add_field( $field );
	}


	/**
	 * @param $object_name
	 */
	protected function add_field_recheck_status( $object_name ) {

		$field = ( new Fields\Checkbox() )
			->set_name( 'recheck_status_before_queued_run' )
			->set_title( __( 'Recheck status before run', 'automatewoo') )
			->set_default_to_checked()
			->set_description( sprintf( __(
				"This is useful for workflows that are not run immediately as it ensures the status of the %s hasn't "
				. "changed since initial trigger." , 'automatewoo'  ), $object_name ) );

		$this->add_field( $field );
	}


	/**
	 * Order status field must be named 'order_status'
	 *
	 * @param $trigger Trigger
	 * @param $order \WC_Order
	 * @deprecated
	 * @return bool
	 * @since 2.0
	 */
	protected function validate_order_status_field( $trigger, $order ) {

		wc_deprecated_function( __METHOD__, '5.2.0' );

		$status = Clean::string( $trigger->get_option('order_status') );

		if ( ! $status ) return true;

		$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

		// wrong status
		if ( $order->get_status() !== $status )
			return false;

		return true;
	}



	/**
	 * @param $workflow Workflow
	 * @return bool
	 */
	protected function validate_field_user_pause_period( $workflow ) {

		$period = $workflow->get_trigger_option( 'user_pause_period' );

		$customer = $workflow->data_layer()->get_customer();
		$user = $workflow->data_layer()->get_user();
		$guest = $workflow->data_layer()->get_guest();

		// There could be no pause period set, and the value of $period will be false.
		// In addition, users had been allowed to save a non-positive int, and these invalid values
		// were run with the same result as the default value. Because there are no logs recorded
		// with a future timestamp when querying, misleadingly made the validation result false.
		//
		// Here it returns invalid values earlier, following consistent results and avoiding
		// further log queries with invalid values.
		if ( ! $period || $period <= 0 ) {
			return true;
		}

		if ( ! $user && ! $guest && ! $customer ) return true; // must have a customer, user or guest

		$days = -1 * (int) $period;

		$period_date = new DateTime();
		$period_date->modify( "{$days} days" );

		// Check to see if this workflow has run since the period date
		$log_query = new Log_Query();
		$log_query->where_workflow( $workflow->get_translation_ids() );
		$log_query->where_date( $period_date, '>');

		if ( $customer ) {
			$log_query->where_customer( $customer->get_id() );
		}
		elseif ( $user ) {
			if ( $user->ID === 0 ) { // guest user
				$log_query->where_guest( $user->user_email );
			}
			else {
				$log_query->where_user( $user->ID );
			}
		}
		elseif( $guest ) {
			$log_query->where_guest( $guest->get_email() );
		}

		if ( $log_query->has_results() ) {
			return false;
		}

		return true;
	}


	/**
	 * @param $allowed_statuses array|string
	 * @param $current_status string
	 *
	 * @return bool
	 */
	protected function validate_status_field( $allowed_statuses, $current_status ) {
		// allow all if left blank
		if ( empty( $allowed_statuses ) ) return true;

		if ( is_array( $allowed_statuses ) ) {
			// multi status match
			$with_prefix_match = in_array( 'wc-' . $current_status, $allowed_statuses );
			$no_prefix_match = in_array( $current_status, $allowed_statuses );

			// at least one has to match
			if ( ! $with_prefix_match && ! $no_prefix_match )
				return false;
		}
		else {
			// single status match, remove prefix
			$allowed_statuses = 'wc-' === substr( $allowed_statuses, 0, 3 ) ? substr( $allowed_statuses, 3 ) : $allowed_statuses;

			if ( $allowed_statuses != $current_status )
				return false;
		}

		return true;
	}


	/**
	 * Get the order status change hook, async or instant
	 * @return string
	 */
	protected function get_hook_order_status_changed() {
		return AUTOMATEWOO_DISABLE_ASYNC_ORDER_STATUS_CHANGED ? 'woocommerce_order_status_changed' : 'automatewoo/order/status_changed_async';
	}


	/**
	 * Get the subscription status change hook, async or instant
	 * @return string
	 */
	protected function get_hook_subscription_status_changed() {
		return AUTOMATEWOO_DISABLE_ASYNC_SUBSCRIPTION_STATUS_CHANGED ? 'automatewoo/subscription/status_changed' : 'automatewoo/subscription/status_changed_async';
	}


	/**
	 * @return string
	 */
	protected function get_deprecation_warning() {
		return __( 'THIS TRIGGER IS DEPRECATED AND SHOULD NOT BE USED.', 'automatewoo' );
	}

	/**
	 * Get the trigger's required async events.
	 *
	 * @since 4.8.0
	 *
	 * @return array
	 */
	public function get_required_async_events() {
		return (array) $this->required_async_events;
	}

	/**
	 * Set the trigger's required async events.
	 *
	 * @since 4.8.0
	 *
	 * @param array|string $required_async_events
	 */
	public function set_required_async_events( $required_async_events ) {
		$this->required_async_events = $required_async_events;
	}





	/**
	 * Get the order paid, async or instant
	 *
	 * @deprecated because automatewoo/order/paid is now also async due to #158
	 *
	 * @return string
	 */
	protected function get_hook_order_paid() {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		return 'automatewoo/order/paid_async';
	}

	/**
	 * Used to dynamically load option values for a trigger field.
	 *
	 * @param string       $field_name
	 * @param string|false $reference_field_value
	 *
	 * @return array
	 * @since 5.6.6
	 */
	public function get_dynamic_field_options( $field_name, $reference_field_value = false ) {
		return [];
	}
}
