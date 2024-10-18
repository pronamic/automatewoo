<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\ActionScheduler\ActionScheduler;
use Automattic\WooCommerce\Admin\Notes\NotesUnavailableException;

defined( 'ABSPATH' ) || exit;

/**
 * Setup inbox notifications.
 *
 * @since 5.8.5
 */
class NotificationsInitializer {

	/**
	 * The type of notification.
	 *
	 * @var array
	 */
	public $notification_types = array(
		Notifications::INSTANT,
		Notifications::ACTIVATION_OR_UPDATE,
		Notifications::SCHEDULED,
	);

	/**
	 * @var ActionScheduler
	 */
	private $action_scheduler;

	/**
	 * Hook name for the Action Scheduler recurring action.
	 *
	 * @var string
	 */
	protected const ACTION_SCHEDULER_HOOK = 'automatewoo_scheduled_notifications';

	/**
	 * Hook name for the Action Scheduler action to run one time.
	 * Used for activation and update notifications.
	 *
	 * @var string
	 */
	protected const ACTION_SCHEDULER_SINGLE_HOOK = 'automatewoo_scheduled_notifications_once';

	/**
	 * Interval in seconds for scheduled action.
	 *
	 * @var int
	 */
	protected const ACTION_SCHEDULER_INTERVAL = 3600;

	/**
	 * Construct
	 *
	 * @param ActionScheduler $action_scheduler Action scheduler.
	 */
	public function __construct( ActionScheduler $action_scheduler ) {
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Add actions for Action Scheduler registration and admin notification processing.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'maybe_add_scheduled_action' ) );
		add_action( 'admin_init', array( $this, 'process_instant_notifications' ) );

		// Register Action Scheduler callback to process scheduled notifications.
		add_action( self::ACTION_SCHEDULER_HOOK, array( $this, 'process_scheduled_notifications' ) );

		// Schedule single run action to process activation or update notifications.
		add_action( 'automatewoo_activated', array( $this, 'schedule_activate_or_update_notifications' ) );
		add_action( 'automatewoo_installed', array( $this, 'schedule_activate_or_update_notifications' ) );
		add_action( self::ACTION_SCHEDULER_SINGLE_HOOK, array( $this, 'process_activate_or_update_notifications' ) );

		// Delete the scheduled action if AutomateWoo is deactivated.
		register_deactivation_hook( AUTOMATEWOO_FILE, array( $this, 'maybe_remove_scheduled_action' ) );
		register_deactivation_hook( AUTOMATEWOO_FILE, array( $this, 'maybe_delete_notes' ) );
	}

	/**
	 * Process immediate and scheduled notifications.
	 *
	 * @return void
	 */
	public function process_scheduled_notifications(): void {
		$this->run( Notifications::SCHEDULED );
	}

	/**
	 * Process immediate notifications.
	 *
	 * @return void
	 */
	public function process_instant_notifications(): void {
		$this->run( Notifications::INSTANT );
	}

	/**
	 * Process activation or upgrade notifications.
	 *
	 * @return void
	 */
	public function process_activate_or_update_notifications(): void {
		$this->run( Notifications::ACTIVATION_OR_UPDATE );
	}

	/**
	 * Loop through all notifications to add any that should be added.
	 *
	 * @param string $notification_type What types of notifications should be processed
	 *
	 * @return void|bool
	 */
	public function run( $notification_type = false ) {
		if ( ! in_array( $notification_type, $this->notification_types, true ) ) {
			return false;
		}

		// Get all Notifications from the Registry.
		$notes = apply_filters( 'automatewoo/notifications/loaded', Notifications::get_all() );

		foreach ( $notes as $note ) {
			try {
				if ( $note->notification_type() === $notification_type ) {
					/**
					 * Process each individual notification to see if a Note needs to be added.
					 *
					 * @var AbstractNotification $note.
					 */
					$note->process();
				}
			} catch ( NotesUnavailableException $e ) {
				continue;
			}
		}
	}

	/**
	 * Add a single action to Action Scheduler to run as soon as possible.
	 *
	 * @return void
	 */
	public function schedule_activate_or_update_notifications(): void {
		if ( ! $this->action_scheduler->next_scheduled_action( self::ACTION_SCHEDULER_SINGLE_HOOK ) ) {
			AW()->action_scheduler()->enqueue_async_action( self::ACTION_SCHEDULER_SINGLE_HOOK );
		}
	}

	/**
	 * Add recurring action to Action Scheduler if it doesn't exist.
	 *
	 * @return void
	 */
	public function maybe_add_scheduled_action(): void {
		if ( ! $this->action_scheduler->next_scheduled_action( self::ACTION_SCHEDULER_HOOK ) ) {
			$this->action_scheduler->schedule_recurring_action( time(), self::ACTION_SCHEDULER_INTERVAL, self::ACTION_SCHEDULER_HOOK );
		}
	}

	/**
	 * Remove recurring action in Action Scheduler if it exists.
	 *
	 * @return void
	 */
	public function maybe_remove_scheduled_action(): void {
		$this->action_scheduler->cancel( self::ACTION_SCHEDULER_HOOK );
	}

	/**
	 * Delete any existing Notes when AutomateWoo extension is deactivated.
	 *
	 * @return void
	 */
	public function maybe_delete_notes(): void {
		$notes = Notifications::get_all();
		foreach ( $notes as $note ) {
			try {
				/**
				 * Check each Note to see if it needs to be deleted.
				 *
				 * @var AbstractNotification $note.
				 */
				$note->deactivation();
				$note->possibly_delete_note();
			} catch ( NotesUnavailableException $e ) {
				continue;
			}
		}
	}
}
