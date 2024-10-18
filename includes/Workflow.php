<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use AutomateWoo\Formatters\Formattable;
use AutomateWoo\Triggers\ManualInterface;
use InvalidArgumentException;
use WC_Subscription;

/**
 * @class Workflow
 */
class Workflow {

	const POST_TYPE = 'aw_workflow';

	/** @var int */
	public $id;

	/** @var \WP_Post */
	public $post;

	/** @var string */
	public $title;

	/** @var Trigger */
	private $trigger;

	/** @var Actions[] */
	private $actions;

	/** @var Data_Layer */
	private $data_layer;

	/** @var Variables_Processor */
	private $variable_processor;

	/** @var Workflow_Location */
	private $location;

	/** @var Workflow_Location  */
	private $tax_location;

	/** @var Log */
	public $log;

	/** @var bool */
	public $exists = false;

	/** @var bool */
	public $preview_mode = false;

	/** @var bool */
	public $test_mode = false;

	/** @var bool */
	public $is_setup = false;


	/**
	 * @param $post mixed (object or post ID)
	 */
	function __construct( $post ) {

		if ( ! $post instanceof \WP_Post ) {
			// Get from id
			$post = get_post($post);
		}

		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return;
		}

		$this->exists = true;
		$this->post = $post;
		$this->id = $post->ID;
		$this->title = $post->post_title;
	}


	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id ? Clean::id( $this->id ) : 0;
	}

	/**
	 * Set workflow ID.
	 *
	 * @since 5.1.0
	 * @param int $id
	 */
	public function set_id( int $id ) {
		$this->id = $id;
	}


	/**
	 * @return string
	 */
	function get_title() {
		return $this->title;
	}


	/**
	 * Get the GMT created date from the post object.
	 * Return a converted date if the GMT date is zero (happens with an auto saved draft).
	 *
	 * @return string
	 */
	public function get_date_created() {
		if ( '0000-00-00 00:00:00' === $this->post->post_date_gmt ) {
			return get_gmt_from_date( $this->post->post_date );
		}

		return $this->post->post_date_gmt;
	}

	/**
	 * Get the workflow type.
	 *
	 * @since 5.0.0
	 * @return string A valid workflow type.
	 */
	public function get_type() {
		$type = $this->get_meta( 'type' );
		return array_key_exists( $type, Workflows::get_types() ) ? $type : 'automatic';
	}

	/**
	 * Set the workflow type.
	 *
	 * @since 5.0.0
	 * @param string $type
	 * @throws InvalidArgumentException If $type param is invalid.
	 */
	public function set_type( $type ) {
		if ( ! array_key_exists( $type, Workflows::get_types() ) ) {
			throw new InvalidArgumentException( 'Invalid workflow type.' );
		}

		$this->update_meta( 'type', $type );
	}

	/**
	 * @return Variables_Processor
	 */
	function variable_processor() {

		if ( ! isset( $this->variable_processor ) ) {
			$this->variable_processor = new Variables_Processor( $this );
		}

		return $this->variable_processor;
	}

	/**
	 * Process a single variable string and return the value.
	 *
	 * For example: "customer.email" or "order.items | template: 'order-table'"
	 * This method should only be used if the workflow has a data layer.
	 *
	 * @since 4.4.0
	 *
	 * @param string $variable_string
	 *
	 * @return string
	 */
	function process_variable( $variable_string ) {
		return $this->variable_processor()->process_field( '{{' . $variable_string . '}}', true );
	}


	/**
	 * @return Data_Layer
	 */
	function data_layer() {
		if ( ! isset( $this->data_layer ) ) {
			$this->data_layer = new Data_Layer();
		}

		return $this->data_layer;
	}


	/**
	 * @return Trigger|ManualInterface|false
	 */
	function get_trigger() {
		if ( ! isset( $this->trigger ) ) {

			$this->trigger = false;
			$trigger_name = $this->get_trigger_name();

			if ( $trigger_name && Triggers::get( $trigger_name ) ) {
				// @todo clone triggers just to retrieve options now seems a little confusing and inefficient
				$this->trigger = clone Triggers::get( $trigger_name );
				$this->trigger->set_options( $this->get_trigger_options() );
			}
		}

		return $this->trigger;
	}


	/**
	 * @return Action[]
	 */
	function get_actions() {
		if ( ! isset( $this->actions ) ) {

			$this->actions = [];
			$actions_data = $this->get_actions_data();

			if ( ! is_array( $actions_data ) ) {
				return $this->actions;
			}

			$n = 1;
			foreach ( $actions_data as $action ) {
				try {
					$action_obj = clone $this->get_action_from_action_fields( $action );
					$action_obj->set_options( $action );
					$this->actions[ $n ] = $action_obj;
					$n++;
				} catch ( Exception $e ) {
					continue;
				}
			}
		}

		return $this->actions;
	}


	/**
	 * Returns the saved actions with their data
	 *
	 * @param $number
	 * @return Action|false
	 */
	function get_action( $number ) {

		$actions = $this->get_actions();

		if ( ! isset( $actions[$number] ) ) {
			return false;
		}

		return $actions[$number];
	}


	/**
	 * @param Data_Layer|array $data_layer
	 * @param bool $skip_validation
	 * @param bool $force_immediate
	 */
	function maybe_run( $data_layer, $skip_validation = false, $force_immediate = false ) {

		// setup language and data before validation occurs
		$this->setup( $data_layer );

		if ( $this->is_missing_required_data() ) {
			return;
		}

		if ( $skip_validation || $this->validate_workflow() ) {

			if ( $this->get_timing_type() === 'immediately' || $force_immediate ) {
				$this->run();
			}
			else {
				$this->queue();
			}
		}

		$this->cleanup();
	}

	/**
	 * Check if workflow is missing some required data.
	 *
	 * This must be run after the setup() method.
	 *
	 * @since 4.6
	 *
	 * @return bool
	 */
	public function is_missing_required_data(){
		if ( ! $this->exists ) {
			return true;
		}

		if ( ! $this->get_trigger() ) {
			return true;
		}

		if ( $this->data_layer()->is_missing_data() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	function validate_workflow() {

		if ( ! $this->is_active() )
			return false;

		if ( ! $trigger = $this->get_trigger() )
			return false;

		if ( ! $trigger->validate_workflow_language( $this ) )
			return false;

		if ( ! $trigger->validate_workflow( $this ) )
			return false;

		if ( ! $this->validate_rules() )
			return false;

		if ( ! apply_filters( 'automatewoo_custom_validate_workflow', true, $this ) )
			return false;

		return true;
	}


	/**
	 * @return bool
	 */
	function validate_rules() {

		$rule_options = $this->get_rule_data();

		// no rules exists
		if ( empty( $rule_options ) )
			return true;

		foreach ( $rule_options as $rule_group ) {

			$is_group_valid = true;

			foreach ( $rule_group as $rule ) {

				// rules have AND relationship so all must return true
				if ( ! $this->validate_rule( $rule ) ) {
					$is_group_valid = false;
					break;
				}
			}

			// groups have an OR relationship so if one is valid we can break the loop and return true
			if ( $is_group_valid )
				return true;
		}

		// no groups were valid
		return false;
	}


	/**
	 * Returns true if rule is missing data so that the rule is skipped
	 *
	 * @param array $rule
	 * @return bool
	 */
	function validate_rule( $rule ) {

		if ( ! is_array( $rule ) )
			return true;

		$rule_name = isset( $rule['name'] ) ? $rule['name'] : false;
		$rule_compare = isset( $rule['compare'] ) ? $rule['compare'] : false;
		$rule_value = isset( $rule['value'] ) ? $rule['value'] : false;

		// its ok for compare to be false for boolean type rules
		if ( ! $rule_name ) {
			return true;
		}

		$rule_object = Rules::get( $rule_name );

		// rule doesn't exists
		if ( ! $rule_object )
			return false;

		// get the data required to validate the rule
		$data_item = $this->data_layer()->get_item( $rule_object->data_item );

		if ( ! $data_item )
			return false;

		// some rules need the full workflow object
		$rule_object->set_workflow( $this );

		// Check the expected rule value is valid.
		try {
			$rule_object->validate_value( $rule_value );
		} catch ( \Exception $e ) {
			// Always return false if the rule value is invalid
			return false;
		}

		return $rule_object->validate( $data_item, $rule_compare, $rule_value );
	}


	/**
	 * @return bool
	 */
	function run() {

		if ( AW_PREVENT_WORKFLOWS ) {
			Logger::info( 'prevented-workflows', $this->title );
			return false;
		}

		do_action( 'automatewoo/workflow/before_run', $this );

		if ( ! $this->is_test_mode() ) {
			$this->create_run_log();
		}

		$actions = $this->get_actions();

		foreach ( $actions as $action ) {

			$action->workflow = $this;

			try {
				do_action('automatewoo_before_action_run', $action, $this );
				$action->run();
				do_action('automatewoo_after_action_run', $action, $this );
			}
			catch( \Exception $e ) {
				// Log exceptions as errors
				$this->log_action_error( $action, $e->getMessage() );
			}
		}

		do_action( 'automatewoo_after_workflow_run', $this );

		return true;
	}


	/**
	 * Reset the workflow object
	 * Clears any data that is related to the last run
	 * The trigger and actions don't need to be reset because their data flows from the workflow options not the workflow data layer
	 */
	function reset_data() {
		$this->data_layer()->clear();
		$this->log = null;
		$this->location = null;
		$this->tax_location = null;
	}

	/**
	 * Create queued event for the workflow.
	 *
	 * @return Queued_Event|false
	 */
	function queue() {
		$date = $this->get_queue_date();

		if ( ! $date ) {
			return false;
		}

		$queue = new Queued_Event();
		$queue->set_workflow_id( $this->get_id() );
		$queue->set_date_due( $date );
		$queue->store_data_layer( $this->data_layer() );
		$queue->save();

		return $queue;
	}

	/**
	 * Get the date the workflow should be queued for.
	 *
	 * @since 5.0.0
	 *
	 * @return DateTime|bool
	 */
	public function get_queue_date() {
		$date = false;

		switch( $this->get_timing_type() ) {
			case 'delayed':
				$date              = new DateTime();
				$current_timestamp = time();
				$date->setTimestamp( $current_timestamp + $this->get_timing_delay( $current_timestamp ) );
				break;
			case 'scheduled':
				$date = $this->calculate_scheduled_datetime();
				break;
			case 'fixed':
				$date = $this->get_fixed_time();
				break;
			case 'datetime':
				$date = $this->get_variable_time();
				break;
		}

		return apply_filters( 'automatewoo/workflow/queue_date', $date, $this );
	}

	/**
	 * Setup the state of the workflow before it is validated or checked
	 * @param array|Data_Layer|bool $data
	 */
	function setup( $data = false ) {

		// the only time data is false is in preview mode
		if ( $data ) {
			$this->set_data_layer( $data, true );
		}

		if ( Language::is_multilingual() ) {
			$lang = $this->get_language();
			Language::set_current( $lang );

			global $woocommerce_wpml;

			if ( $woocommerce_wpml ) {
				/** @var $woocommerce_wpml \woocommerce_wpml */
				$woocommerce_wpml->emails->change_email_language( $lang );
			}
		}

		// Ensure mailer and gateways are loaded in case they need to insert data into the emails
		WC()->mailer();
		WC()->payment_gateways();
		WC()->shipping();

		add_filter( 'woocommerce_get_tax_location', [ $this, 'filter_tax_location' ], 50, 2 );

		$this->is_setup = true;
	}


	/**
	 * Clean up after workflow run
	 */
	function cleanup() {

		// reset language
		if ( Language::is_multilingual() ) {
			Language::set_original();
		}

		remove_filter( 'woocommerce_get_tax_location', [ $this, 'filter_tax_location' ], 50 );

		$this->is_setup = false;
	}


	/**
	 * Record that the workflow has been run
	 */
	function create_run_log() {

		$this->log = new Log();
		$this->log->set_workflow_id( $this->get_id() );
		$this->log->set_date( new DateTime() );

		if ( $this->is_tracking_enabled() ) {
			$this->log->set_tracking_enabled( true );

			if ( $this->is_conversion_tracking_enabled() ) {
				$this->log->set_conversion_tracking_enabled( true );
			}
		}

		$this->log->save();
		$this->log->store_data_layer( $this->data_layer() );

		do_action( 'automatewoo_create_run_log', $this->log, $this );
	}



	/**
	 * @return int
	 */
	function get_times_run() {

		$cache_key = 'times_run/workflow=' . $this->get_id();
		$cache = Cache::get_transient( $cache_key );

		if ( $cache !== false ) {
			return (int) $cache;
		}

		$query = new Log_Query();
		$query->where_workflow(  $this->get_id() );
		$count = $query->get_count();

		Cache::set_transient( $cache_key, $count, 720 );

		return $count;
	}


	/**
	 * @param bool $try_cache
	 * @return int|string
	 */
	function get_current_queue_count( $try_cache = true ) {

		$cache_key = 'current_queue_count/workflow=' . $this->get_id();
		$cache = Cache::get_transient( $cache_key );

		if ( $try_cache && $cache !== false ) {
			return $cache;
		}
		else {

			$query = new Queue_Query();
			$query->where_workflow( $this->get_id() );
			$count = $query->get_count();

			Cache::set_transient( $cache_key, $count, 720 );

			return $count;
		}
	}


	/**
	 * @param string $name
	 * @param bool $replace_vars
	 * @return mixed
	 */
	function get_option( $name, $replace_vars = false ) {

		$options = $this->get_meta( 'workflow_options' );

		if ( ! is_array( $options ) || ! isset( $options[$name] ) )
			return false;

		if ( $replace_vars ) {
			return $this->variable_processor()->process_field( $options[$name] );
		}

		return apply_filters( 'automatewoo/workflow/option', $options[$name], $name, $this );
	}


	/**
	 * Returns options are immediately, delayed, scheduled, datetime
	 * @since 2.9
	 * @return string
	 */
	function get_timing_type() {
		$when = Clean::string( $this->get_option( 'when_to_run' ) );
		if ( ! $when ) $when = 'immediately';
		return $when;
	}

	/**
	 * Return the delay period in seconds
	 *
	 * @since 2.9
	 * @param integer|null $current_timestamp the current timestamp, used for calculating 'month' delays.
	 * @return integer
	 */
	public function get_timing_delay( $current_timestamp = null ) {

		// Default to execution time.
		if ( is_null( $current_timestamp ) ) {
			$current_timestamp = time();
		}

		$timing_type = $this->get_timing_type();

		if ( ! in_array( $timing_type, [ 'delayed', 'scheduled' ] ) ) {
			return 0;
		}

		$number = $this->get_timing_delay_number();
		$unit   = $this->get_timing_delay_unit();

		$units = [
			'm'     => MINUTE_IN_SECONDS,
			'h'     => HOUR_IN_SECONDS,
			'd'     => DAY_IN_SECONDS,
			'w'     => WEEK_IN_SECONDS,
			'month' => MONTH_IN_SECONDS,
		];

		if ( ! $number || ! isset( $units[ $unit ] ) ) {
			return 0;
		} elseif ( 'month' === $unit ) {
			try {
				$delay_date = new DateTime();
				$delay_date->setTimestamp( $current_timestamp );
				$delay_date->add_natural_months( $number );
				return $delay_date->getTimeStamp() - $current_timestamp;
			} catch ( \Exception $exception ) {
				// Invalid $number of months.
				return 0;
			}
		}

		return $number * $units[ $unit ];
	}


	/**
	 * @return float
	 */
	function get_timing_delay_number() {
		return (float) $this->get_option('run_delay_value');
	}


	/**
	 * @return string
	 */
	function get_timing_delay_unit() {
		return Clean::string( $this->get_option('run_delay_unit') );
	}


	/**
	 * Calculate the next point in time that matches the workflow scheduling options
	 * @param bool|integer $current_timestamp - optional, not GMT
	 * @return bool|DateTime
	 */
	function calculate_scheduled_datetime( $current_timestamp = false ) {

		if ( $this->get_timing_type() !== 'scheduled' ) {
			return false;
		}

		if ( ! $current_timestamp ) {
			$current_timestamp = current_time( 'timestamp' ); // calculate based on the local timezone
		}

		// scheduled day and time are in the sites specified timezone
		$scheduled_time = $this->get_scheduled_time();
		$scheduled_days = $this->get_scheduled_days();
		$scheduled_time_seconds_from_day_start = Time_Helper::calculate_seconds_from_day_start( $scheduled_time );

		// get minimum datetime before scheduling can happen, if no delay is set then this will be now
		$min_wait_datetime = new DateTime;
		$min_wait_datetime->setTimestamp( $current_timestamp + $this->get_timing_delay( $current_timestamp ) );
		$min_wait_time_seconds_from_day_start = Time_Helper::calculate_seconds_from_day_start( $min_wait_datetime );

		// check to see if the scheduled time of day is later than the min wait time
		$is_scheduled_time_later_than_min_wait_time = $min_wait_time_seconds_from_day_start < $scheduled_time_seconds_from_day_start;

		// if the scheduled time comes before the current min wait time we can not schedule on the same day as the min wait
		// therefore update the min wait datetime so that is its midnight of the next day
		if ( ! $is_scheduled_time_later_than_min_wait_time ) {
			$min_wait_datetime->modify('+1 day');
		}

		$min_wait_datetime->set_time_to_day_start(); // set time to midnight, time will be added on later

		// check if scheduled day matches the min wait day
		if ( $scheduled_days && ! in_array( $min_wait_datetime->format( 'N' ), $scheduled_days ) ) {

			// advance time until a matching day is found
			while ( ! in_array( $min_wait_datetime->format( 'N' ), $scheduled_days ) ) {
				$min_wait_datetime->modify('+1 day');
			}
		}

		$scheduled_time = new DateTime;
		$scheduled_time->setTimestamp( $min_wait_datetime->getTimestamp() );
		$scheduled_time->modify( "{$scheduled_time_seconds_from_day_start} seconds" );
		$scheduled_time->convert_to_utc_time();

		return $scheduled_time;
	}


	/**
	 * @return string
	 */
	function get_scheduled_time() {
		return Clean::string( $this->get_option( 'scheduled_time' ) );
	}


	/**
	 * Returns empty if set to any day, 1 (for Monday) through 7 (for Sunday)
	 * @return array
	 */
	function get_scheduled_days() {
		return Clean::ids( $this->get_option( 'scheduled_day' ) );
	}


	/**
	 * @return DateTime|bool
	 */
	function get_fixed_time() {

		$date = Clean::string( $this->get_option('fixed_date') );
		$time = array_map( 'absint', (array) $this->get_option('fixed_time') );

		if ( ! $date ) {
			return false;
		}

		$datetime = new DateTime( $date );
		$datetime->setTime( isset($time[0]) ? $time[0] : 0, isset($time[1]) ? $time[1] : 0, 0 );
		$datetime->convert_to_utc_time();

		return $datetime;
	}


	/**
	 * Get scheduled date as set by variable timing option
	 * @return DateTime|bool
	 */
	function get_variable_time() {
		$datetime = $this->get_option( 'queue_datetime', true );

		if ( ! $datetime ) {
			return false;
		}

		$timestamp = strtotime( $datetime, current_time( 'timestamp' ) );

		$date = new DateTime();
		$date->setTimestamp( $timestamp );
		$date->convert_to_utc_time();

		return $date;
	}

	/**
	 * Get the name of the workflow's trigger.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	function get_trigger_name() {
		return Clean::string( $this->get_meta( 'trigger_name' ) );
	}

	/**
	 * Set the trigger for the workflow.
	 *
	 * @since 4.4.0
	 *
	 * @param $trigger_name
	 */
	function set_trigger_name( $trigger_name ) {
		$this->update_meta( 'trigger_name', Clean::string( $trigger_name ) );
		unset( $this->trigger );
	}

	/**
	 * Get the workflow trigger options.
	 * Values will be sanitized as per the fields set on the trigger object.
	 *
	 * @return array
	 */
	function get_trigger_options() {
		$options = $this->get_meta( 'trigger_options' );
		return is_array( $options ) ? $options : [];
	}

	/**
	 * Set the workflow trigger options.
	 *
	 * Also saves the trigger name if it's different.
	 *
	 * @since 4.4.0
	 *
	 * @param string $trigger_name
	 * @param array  $trigger_options
	 */
	function set_trigger_data( $trigger_name, $trigger_options ) {
		if ( $trigger_name !== $this->get_trigger_name() ) {
			$this->set_trigger_name( $trigger_name );
		}
		$this->update_meta( 'trigger_options', $this->sanitize_trigger_options( $trigger_name, $trigger_options ) );
		unset( $this->trigger );
	}


	/**
	 * Get's the sanitized value of workflow trigger option.
	 *
	 * @param string $name
	 * @param bool|string $default used when value is not set, this should only be if the option was added workflow was created
	 *
	 * @return mixed Will vary depending on the field type specified in the trigger's fields.
	 */
	function get_trigger_option( $name, $default = false ) {
		$options = $this->get_trigger_options();

		if ( isset( $options[$name] ) ) {
			$value = $options[$name];
		}
		else {
			$value = $default;
		}

		return $value;
	}

	/**
	 * Sanitizes an array of trigger data based on the fields of the trigger.
	 *
	 * @since 4.4.0
	 *
	 * @param string $trigger_name
	 * @param array  $raw_options
	 *
	 * @return array
	 */
	function sanitize_trigger_options( $trigger_name, $raw_options ) {
		if ( empty( $trigger_name ) ) {
			return [];
		}

		$trigger = Triggers::get( Clean::string( $trigger_name ) );

		if ( ! $trigger ) {
			return [];
		}

		$return = [];

		foreach( $raw_options as $name => $value ) {
			$name = Clean::string( $name );
			$field_obj = $trigger->get_field( $name );

			if ( $field_obj ) {
				$return[ $name ] = $field_obj->sanitize_value( $value );
			}
		}

		return $return;
	}

	/**
	 * Get actions data for the workflow.
	 *
	 * Values will be formatted as per the fields set on the action object.
	 *
	 * @since 4.4.0
	 * @since 4.8.0 Added formatting to the fields.
	 *
	 * @return array
	 */
	function get_actions_data() {
		$actions_data = $this->get_meta( 'actions' );
		return is_array( $actions_data ) ? array_map( [ $this, 'format_action_fields' ], $actions_data ) : [];
	}

	/**
	 * Set the workflow actions data.
	 *
	 * Values will be sanitized as per the fields set on the action object. Data is
	 * only sanitized before write, not before read.
	 *
	 * todo: Update data structure to use separate name and options keys
	 *
	 * @since 4.4.0
	 *
	 * @param array $raw_actions_data
	 */
	function set_actions_data( $raw_actions_data ) {
		$actions_data = array_map( [ $this, 'sanitize_action_fields' ], $raw_actions_data );
		// remove empty values from actions array
		$actions_data = array_filter( $actions_data );
		$this->update_meta( 'actions', $actions_data );
		unset( $this->actions );
	}

	/**
	 * Sanitizes a array of action fields for a single action.
	 *
	 * @since 4.4.0
	 *
	 * @param array $action_fields
	 *
	 * @return array
	 */
	function sanitize_action_fields( $action_fields ) {
		try {
			$action = $this->get_action_from_action_fields( $action_fields );
		} catch ( Exception $e ) {
			return [];
		}

		$sanitized = [
			'action_name' => $action->get_name(),
		];

		foreach( $action_fields as $name => $value ) {
			$name = Clean::string( $name );
			$field_obj = $action->get_field( $name );

			if ( $field_obj ) {
				$field_value = $field_obj->sanitize_value( $value );
				// encode emojis to avoid emoji serialization issues
				$field_value = Clean::encode_emoji( $field_value );
				$sanitized[ $name ] = $field_value;
			}
		}

		return $sanitized;
	}

	/**
	 * Format action fields according to the field type.
	 *
	 * @since 4.8.0
	 *
	 * @param array $action_fields
	 *
	 * @return array
	 */
	public function format_action_fields( $action_fields ) {
		try {
			$action = $this->get_action_from_action_fields( $action_fields );
		} catch ( Exception $e ) {
			return [];
		}

		$formatted = [
			'action_name' => $action->get_name(),
		];

		foreach ( $action_fields as $name => $value ) {
			$name      = Clean::string( $name );
			$field_obj = $action->get_field( $name );

			if ( $field_obj && $field_obj instanceof Formattable ) {
				$value = $field_obj->format_value( $value );
			}

			$formatted[ $name ] = $value;
		}

		return $formatted;
	}

	/**
	 * @param array $rule_options
	 */
	function set_rule_data( $rule_options ) {
		$this->update_meta( 'rule_options', $this->sanitize_rule_options( $rule_options ) );
	}


	/**
	 * @return array
	 */
	function get_rule_data() {
		$data = $this->get_meta( 'rule_options' );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Get the number of rule groups on the workflow.
	 *
	 * @since 5.0.0
	 *
	 * @return int
	 */
	public function get_rule_group_count() {
		return count( $this->get_rule_data() );
	}

	/**
	 * Sanitizes all rule groups for a workflow.
	 *
	 * @since 4.4.0
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	function sanitize_rule_options( $options ) {
		if ( ! is_array( $options ) ) {
			return [];
		}

		// Remove array key names from rules, as they are not needed.
		$cleaned = [];
		foreach ( $options as $rule_group ) {
			$rule_group = array_map( [ $this, 'sanitize_rule_option' ], $rule_group );
			$cleaned[]  = array_values( $rule_group );
		}

		return $cleaned;
	}


	/**
	 * Sanitizes a single rule.
	 *
	 * @param array $rule_fields
	 *
	 * @return array
	 * @since 4.3.0
	 */
	public function sanitize_rule_option( $rule_fields ) {
		$name = Clean::string( $rule_fields['name'] );
		$rule = Rules::get( $name );

		if ( isset( $rule_fields['value'] ) && $rule ) {
			$value = $rule->sanitize_value( $rule_fields['value'] );
		} else {
			// Rule may have been deleted in which case we don't need a value.
			$value = '';
		}

		$sanitized = [
			'name'    => $name,
			'compare' => isset( $rule_fields['compare'] ) ? Clean::string( $rule_fields['compare'] ) : '',
			'value'   => $value,
		];

		return $sanitized;
	}


	/**
	 * Returns the log currently created by this workflow run.
	 * If false is returned the log record may not have been created.
	 *
	 * @return Log|bool
	 */
	function get_current_log() {
		if ( isset( $this->log ) ) {
			return $this->log;
		}
		return false;
	}


	/**
	 * Check if workflow is exempt from unsubscribing and allow filtering of unsubscribed prop.
	 *
	 * @param Customer $customer
	 * @return bool
	 */
	function is_customer_unsubscribed( $customer ) {
		if ( $this->is_preview_mode() || $this->is_test_mode() ) {
			return false;
		}

		if ( $this->is_exempt_from_unsubscribing() ) {
			return false;
		}

		if ( ! $customer ) {
			return false;
		}

		return apply_filters( 'automatewoo/workflow/is_customer_unsubscribed', $customer->is_unsubscribed(), $this, $customer );
	}


	/**
	 * Returns false if the workflow is exempt from unsubscribing.
	 *
	 * @param Customer $customer
	 * @return bool|string
	 */
	function get_unsubscribe_url( $customer ) {
		if ( ! $customer ) {
			return false;
		}

		if ( $this->is_exempt_from_unsubscribing() ) {
			return false;
		}
		// SEMGREP WARNING EXPLANATION
		// This is escaped with esc_url_raw after, but semgrep only takes into consideration esc_url.
		// Also, the URL is just the My Account Page. Not reaching any user input.
		$url = add_query_arg(
			[
				'aw-action'    => 'unsubscribe',
				'workflow'     => $this->get_id(),
				'customer_key' => urlencode( $customer->get_key() ),
			],
			wc_get_page_permalink( 'myaccount' )
		);

		$url = apply_filters( 'automatewoo_unsubscribe_url', $url, $this->get_id(), $customer );

		return esc_url_raw( $url );
	}


	/**
	 * @return bool
	 */
	function is_exempt_from_unsubscribing() {
		$is_exempt = false;

		// this avoids breaking changes for any customers using this gist: https://gist.github.com/danielbitzer/c25209057ba063ed4adcb6764049f1b6
		if ( apply_filters( 'automatewoo_unsubscribe_url', home_url(), $this->get_id(), false ) === false ) {
			$is_exempt = true;
		}

		if ( $this->get_meta('is_transactional') ) {
			$is_exempt = true;
		}

		return apply_filters( 'automatewoo/workflow/is_exempt_from_unsubscribing', $is_exempt, $this->get_id(), $this );
	}


	/**
	 * @param $user \WP_User or guest user
	 *
	 * @return bool
	 */
	function is_first_run_for_user( $user ) {
		return $this->get_times_run_for_user( $user ) === 0;
	}


	/**
	 * Counts items in log and in queue for this user and workflow
	 *
	 * @param $user \WP_User|Order_Guest
	 * @return int
	 */
	function get_times_run_for_user( $user ) {
		$query = new Log_Query();
		$query->where_workflow( $this->get_id() );

		if ( $user->ID === 0 ) { // guest user
			$query->where_guest(  $user->user_email );
		}
		else {
			$query->where_user( $user->ID );
		}

		return count( $query->get_results() );
	}


	/**
	 * Counts items in log and in queue for this user and workflow
	 *
	 * @param Customer $customer
	 * @return int
	 */
	function get_run_count_for_customer( $customer ) {
		$query = new Log_Query();
		$query->where_workflow( $this->get_id() );
		$query->where_customer_or_legacy_user( $customer, true );
		return $query->get_count();
	}


	/**
	 * @param \WC_Order $order
	 * @return int
	 */
	function get_run_count_for_order( $order ) {
		$query = new Log_Query();
		$query->where_workflow( $this->get_id() );
		$query->where_order( $order->get_id() );
		return $query->get_count();
	}

	/**
	 * @param \WP_Post $course
	 * @return int
	 */
	public function get_run_count_for_course( $course ) {
		$query = new Log_Query();
		$query->where_workflow( $this->get_id() );
		$query->where_course( $course->ID );
		return $query->get_count();
	}

	/**
	 * Get times this workflow has run for a given subscription.
	 *
	 * @since 5.0.0
	 *
	 * @param WC_Subscription $subscription
	 *
	 * @return int
	 */
	public function get_run_count_for_subscription( WC_Subscription $subscription ) {
		return ( new Log_Query() )
			->where_workflow( $this->get_id() )
			->where_subscription( $subscription->get_id() )
			->get_count();
	}


	/**
	 * Counts items in log and in queue for this guest and workflow
	 *
	 * @param $guest Guest
	 * @return int
	 */
	function get_times_run_for_guest( $guest ) {
		$query = new Log_Query();
		$query->where_workflow( $this->get_id() );
		$query->where_guest( $guest->get_email() );
		return $query->get_count();
	}


	/**
	 * Checks the logs to see if a workflow has already run for set data items.
	 * This checks the log and the queue if necessary.
	 *
	 * The $within_timeframe parameter defines how far back to look for when the workflow has run.
	 * If $timeframe is false then the query looks for all time.
	 *
	 * @since 3.8
	 *
	 * @param array|string $query_data_items Use the ID of the data item/s. When multiple values are supplied an AND query is used.
	 *                                       E.g. Setting a product and customer will check if the workflow has run previously
	 *                                       for the same product AND the same customer.
	 * @param int|false    $within_timeframe in seconds
	 * @param bool         $skip_queue_query
	 *
	 * @return bool
	 */
	function has_run_for_data_item( $query_data_items, $within_timeframe = false, $skip_queue_query = false ) {
		if ( ! $this->is_setup ) {
			return false;
		}

		$query_data_items = (array) $query_data_items;

		$log_query = new Log_Query();
		$log_query->where_workflow( $this->get_translation_ids() );

		if ( $within_timeframe ) {
			$timeframe_date = new DateTime();
			$timeframe_date->setTimestamp( time() - $within_timeframe );
			$log_query->where_date( $timeframe_date, '>' );
		}

		foreach ( $query_data_items as $data_item_id ) {
			$log_query->where_data_layer( $data_item_id, $this->data_layer()->get_item( $data_item_id ) );
		}

		if ( $log_query->has_results() ) {
			return true;
		}

		// check if something is in the queue
		if ( $this->get_timing_type() !== 'immediately' && ! $skip_queue_query ) {
			$queue_query = new Queue_Query();
			$queue_query->where_workflow( $this->get_translation_ids() );

			if ( isset( $timeframe_date ) ) {
				$queue_query->where_date_created( $timeframe_date, '>' );
			}

			foreach ( $query_data_items as $data_item_id ) {
				$queue_query->where_data_layer( $data_item_id, $this->data_layer()->get_item( $data_item_id ) );
			}

			if ( $queue_query->has_results() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $name
	 * @param $item
	 */
	function set_data_item( $name, $item ) {
		$this->data_layer()->set_item( $name, $item );
	}


	/**
	 * @param array|Data_Layer $data_layer
	 * @param bool $reset_workflow_data
	 */
	function set_data_layer( $data_layer, $reset_workflow_data ) {

		if ( ! is_a( $data_layer, 'AutomateWoo\Data_Layer' ) ) {
			$data_layer = new Data_Layer( $data_layer );
		}

		if ( $reset_workflow_data ) {
			$this->reset_data();
		}

		$this->data_layer = $data_layer;
	}


	/**
	 * Retrieve and validate a data item
	 *
	 * @param $name string
	 * @return mixed
	 */
	function get_data_item( $name ) {
		return $this->data_layer()->get_item( $name );
	}


	/**
	 * Is workflow active.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! $this->exists ) {
			return false;
		}
		return $this->get_status() === 'active';
	}

	/**
	 * Get workflow status.
	 *
	 * Possible statuses are active|disabled|trash
	 *
	 * @since 4.6
	 *
	 * @return string
	 */
	public function get_status() {
		$status = $this->post->post_status;

		if ( 'publish' === $status || 'manual' === $this->get_type() ) {
			$status = 'active';
		}
		elseif ( $status === 'aw-disabled' ) {
			$status = 'disabled';
		}

		return $status;
	}


	/**
	 * @param string $status active|disabled or publish|aw-disabled
	 */
	function update_status( $status ) {

		if ( $status === 'active' ) {
			$post_status = 'publish';
		}
		elseif ( $status === 'disabled' ) {
			$post_status = 'aw-disabled';
		}
		else {
			$post_status = $status;
		}

		wp_update_post([
			'ID' => $this->get_id(),
			'post_status' => $post_status
		]);
	}


	/**
	 * @return bool
	 */
	function is_transactional() {
		return (bool) $this->get_meta( 'is_transactional' );
	}


	/**
	 * @return bool
	 */
	function is_tracking_enabled() {
		return (bool) $this->get_option( 'click_tracking' );
	}


	/**
	 * @return bool
	 */
	function is_conversion_tracking_enabled() {
		return (bool) $this->get_option( 'conversion_tracking' );
	}


	/**
	 * @return bool
	 */
	function is_ga_tracking_enabled() {
		return ( $this->is_tracking_enabled() && $this->get_ga_tracking_params() ) ;
	}


	/**
	 * @return string
	 */
	function get_ga_tracking_params() {
		return trim( $this->get_option( 'ga_link_tracking', true ) );
	}


	/**
	 * @param string $url
	 * @return string
	 */
	function append_ga_tracking_to_url( $url ) {

		if ( empty( $url ) || ! $this->is_ga_tracking_enabled() ) {
			return $url;
		}

		$params = [];
		parse_str( $this->get_ga_tracking_params(), $params );

		// SEMGREP WARNING EXPLANATION
		// URL is escaped later by the consumer.
		return add_query_arg( $params, $url );
	}

	/**
	 * @return int
	 */
	function get_order() {
		return (int) $this->post->menu_order ?: 0;
	}


	/**
	 * @return false|string
	 */
	function get_language() {
		if ( Integrations::is_wpml() ) {
			$info = wpml_get_language_information( null, $this->get_id() );
			if ( is_array( $info ) )
				return $info['language_code'];
		}
		return false;
	}


	/**
	 * Return array with all versions of this workflow including the original
	 * @return array
	 */
	function get_translation_ids() {

		if ( ! Integrations::is_wpml() ) {
			return [ $this->get_id() ];
		}

		global $sitepress;

		$ids = [];

		$translations = $sitepress->get_element_translations( $this->get_id(), 'post_post', false, true );

		if ( is_array( $translations ) ) {
			foreach ( $translations as $translation ) {
				$ids[] = $translation->element_id;
			}
		}

		$ids[] = $this->get_id(); // sometimes wpml doesn't return default language id?

		return Clean::ids( $ids );
	}


	/**
	 * @param $key
	 * @param bool $single
	 * @return mixed
	 */
	function get_meta( $key, $single = true ) {
		return get_post_meta( $this->get_id(), $key, $single );
	}


	/**
	 * @param $key
	 * @param $value
	 * @return bool|int
	 */
	public function update_meta( $key, $value ) {
		return update_post_meta( $this->get_id(), $key, $value );
	}


	/**
	 * Enabling preview mode also enables test mode
	 */
	function enable_preview_mode() {
		$this->preview_mode = true;
		$this->enable_test_mode();
	}


	/**
	 * Enable test mode
	 */
	function enable_test_mode() {
		$this->test_mode = true;
	}


	/**
	 * @return bool
	 */
	function is_test_mode() {
		return $this->test_mode;
	}


	/**
	 * @return bool
	 */
	function is_preview_mode() {
		return $this->preview_mode;
	}


	/**
	 * @param Action $action
	 * @param $note
	 */
	function log_action_note( $action, $note ) {
		if ( ! $log = $this->get_current_log() ) {
			return;
		}

		$log->add_note( $action->get_title() . ': ' . $note );
	}


	/**
	 * @param Action $action
	 * @param $error
	 */
	function log_action_error( $action, $error ) {
		if ( ! $log = $this->get_current_log() ) {
			return;
		}

		$log->add_note( $action->get_title() . ': ' . $error );

		$log->set_has_errors( true );
		$log->save();
	}


	/**
	 * Logs the error response from the Mailer class.
	 * Separates true mail errors from unsubscribes and blacklist errors and logs them accordingly.
	 *
	 * @param \WP_Error $error
	 * @param Action $action
	 */
	function log_action_email_error( $error, $action ) {
		if ( ! $log = $this->get_current_log() ) {
			return;
		}

		if ( $error->get_error_code() === 'email_unsubscribed' || $error->get_error_code() === 'email_blacklisted' ) {
			$this->log_action_note( $action, $error->get_error_message() );
			$log->set_has_blocked_emails( true );
			$log->save();
		}
		else {
			$this->log_action_error( $action, $error->get_error_message() );
		}
	}


	/**
	 * Returns the location based on the customer in the workflow data layer.
	 *
	 * @return Workflow_Location
	 */
	function get_location() {

		if ( ! isset( $this->location ) ) {
			$this->location = new Workflow_Location( $this );
			$this->location = apply_filters( 'automatewoo/workflow/location', $this->location, $this );
		}

		return $this->location;
	}


	/**
	 * @return Workflow_Location
	 */
	function get_tax_location() {

		if ( ! isset( $this->tax_location ) ) {
			$this->tax_location = new Workflow_Location( $this, get_option( 'woocommerce_tax_based_on' )  );
			$this->tax_location = apply_filters( 'automatewoo/workflow/tax_location', $this->tax_location, $this );
		}

		return $this->tax_location;
	}


	/**
	 * Set tax location for the current workflow user
	 *
	 * @param $location
	 * @param $tax_class
	 * @return array
	 */
	function filter_tax_location( $location, $tax_class ) {
		if ( 'base' === get_option( 'woocommerce_tax_based_on' ) ) {
			return $location;
		}

		return $this->get_tax_location()->get_location_array();
	}


	/**
	 * @param $name
	 * @param $item
	 * @deprecated
	 */
	function add_data_item( $name, $item ) {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		$this->set_data_item( $name, $item );
	}

	/**
	 * @deprecated use log_action_note
	 * @param Action $action
	 * @param $note
	 */
	function add_action_log_note( $action, $note ) {
		wc_deprecated_function( __METHOD__, '5.2.0' );

		$this->log_action_note( $action, $note );
	}

	/**
	 * Get an action based on field data.
	 *
	 * @since 4.8.0
	 *
	 * @param array $field_data
	 *
	 * @return Action The action object for the data.
	 * @throws Exception When the Action could not be resolved to an object.
	 */
	public function get_action_from_action_fields( $field_data ) {
		if ( ! is_array( $field_data ) || ! isset( $field_data['action_name'] ) ) {
			throw new Exception( 'Missing action_name key in array.' );
		}

		$action_name = Clean::string( $field_data['action_name'] );
		$action      = Actions::get( $action_name );
		if ( ! $action ) {
			throw new Exception( 'Could not retrieve the action.' );
		}

		return $action;
	}
}
