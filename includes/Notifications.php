<?php

namespace AutomateWoo\Notifications;

use AutomateWoo\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for all notifications.
 *
 * @since 5.8.5
 *
 * @package AutomateWoo
 */
class Notifications extends Registry {

	/**
	 * Notification type that will be processed immediately when WP-Admin is loaded
	 *
	 * @var string
	 */
	const INSTANT = 'instant';

	/**
	 * Notification type that will be processed when AutomateWoo is activated or updated.
	 *
	 * @var string
	 */
	const ACTIVATION_OR_UPDATE = 'activate_or_update';

	/**
	 * Notification type that will be processed hourly
	 *
	 * @var string
	 */
	const SCHEDULED = 'scheduled';

	/**
	 * Static store of the includes map.
	 *
	 * @var array
	 */
	protected static $includes;

	/**
	 * Load all notifications.
	 *
	 * @return array
	 */
	public static function load_includes(): array {
		$includes = array(
			'minimum_wc_version'  => WCMinVersionCheck::class,
			'minimum_php_version' => PHPMinVersionCheck::class,
			'system_checks'       => SystemChecks::class,
			'welcome'             => WelcomeNotification::class,
			'mailchimp'           => MailchimpCheck::class,
			'activecampaign'      => ActiveCampaignCheck::class,
			'twilio'              => TwilioCheck::class,
			'bitly'               => BitlyCheck::class,
			'campaign_monitor'    => CampaignMonitorCheck::class,
			'referrals'           => ReferAFriendCheck::class,
			'birthdays'           => BirthdaysCheck::class,
		);

		return apply_filters( 'automatewoo/notifications/includes', $includes );
	}
}
