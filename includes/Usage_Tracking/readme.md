# AutomateWoo Usage Tracking

The files within this directory implement usage tracking for AutomateWoo. This builds on the native [WooCommerce Usage Tracking](https://woocommerce.com/usage-tracking/), and is only enabled when WooCommerce Tracking is enabled.

When a store opts in to WooCommerce usage tracking and they use AutomateWoo, they will also be opted in to the tracking added by AutomateWoo.

## What we track

Similar to WooCommerce, we track non-sensitive data about how a store is set up and managed. We **do not track or store personal data** from your clients.

* Plugin version
* Settings:
  * Whether opt-in is enabled, whether the checkbox is available on the checkout page, and whether the checkbox is available on the account sign-up page
  * Whether session tracking is enabled
  * Whether session tracking requires cookie consent and the name of the cookie
  * Whether presubmit capture is enabled
  * Whether the abandoned cart feature is enabled
  * Whether the communication account tab is enabled
  * Whether any of these integrations are in use: Mailchimp, Campaign Monitor, Active Campaign, Twilio, Bitly
* The names of actions that are in use
* The name of triggers that are in use
* The number of active automatic workflows
* The number of manual workflows
* The number of conversions that have been made
* The total value of all conversions
* Workflow Log data:
  * Number of times workflows have run
  * Number of times a workflow ran with conversion tracking enabled
  * Number of times a workflow ran with tracking enabled

We also track certain user and store events, including when AutomateWoo is first installed, when a workflow is first created, when a workflow runs, and when a conversion is recorded.

## Raw tracking data

### Usage tracking additional properties

We add on to the WooCommerce tracker array as follows:

```php
$data['extensions']['automatewoo'] = [
	'settings'                    => [
		'database_version'                         => (string) "version",
		'file_version'                             => (string) "version",
		'optin_enabled'                            => (bool) "enabled",
		'session_tracking_enabled'                 => (bool) "enabled",
		'session_tracking_requires_cookie_consent' => (bool) "enabled",
		'session_tracking_consent_cookie_name'     => (string) "name",
		'presubmit_capture_enabled'                => (bool) "enabled",
		'abandoned_cart_enabled'                   => (bool) "enabled",
		'checkout_optin_enabled'                   => (bool) "enabled",
		'account_optin_enabled'                    => (bool) "enabled",
		'communication_account_tab_enabled'        => (bool) "enabled",
		'mailchimp_integration_enabled'            => (bool) "enabled",
		'mailpoet_integration_enabled'             => (bool) "enabled",
		'campaign_monitor_integration_enabled'     => (bool) "enabled",
		'active_campaign_integration_enabled'      => (bool) "enabled",
		'twilio_integration_enabled'               => (bool) "enabled",
		'bitly_shorten_sms_links'                  => (bool) "shorten",
	],
	'active_actions'             => [ /* action names as keys, counts as values */ ],
	'active_triggers'            => [ /* trigger names as keys, counts as values */ ],
	'active_automatic_workflows' => (int) "count",
	'manual_workflows'           => (int) "count",
	'conversion_count'           => (int) "count",
	'conversion_value'           => (float) "value",
	'log_counts'                 => [
		'total'                       => (int) "count",
		'conversion_tracking_enabled' => (bool) "enabled",
		'tracking_enabled'            => (bool) "enabled",
	],
];
```

### Tracking events

All event names are prefixed by `wcadmin_aw_`.

* `workflow_before_run` &ndash; triggered when a workflow runs
* `workflow_created` &ndash; triggered when a workflow is first created
* `conversion_recorded` &ndash; triggered when a conversion is recorded
* `first_installed` &ndash; triggered when AutomateWoo is installed for the first time
* `manual_workflow_runner_select_workflow` &ndash; triggered when a workflow is selected when using the workflow runner
* `manual_run_workflow_button_clicked` &ndash; triggered when the run workflow button is clicked
* `manual_find_matching_cancel_button_clicked` &ndash; triggered when the workflow runner find matching items cancel button is clicked
* `manual_queue_items_cancel_button_clicked` &ndash; triggered when the workflow runner queue items cancel button is clicked
* `manual_run_workflow_complete` &ndash; triggered when the workflow runner completes queuing workflow items
* `notice_viewed` &ndash; triggered when an admin notice is displayed (includes `notice_identifier` property to distinguish which notice)
* `notice_link_clicked` &ndash; triggered when a link in an admin notice is clicked (includes `notice_identifier` and `link_type` properties)
* `notice_dismissed` &ndash; triggered when an admin notice is dismissed by the user (includes `notice_identifier` property)
* `workflow_tab_view` &ndash; triggered when each "AutomateWoo > Workflows" tab is viewed by the user 
* `preset_list_button_clicked` &ndash; fires when a button in the workflow presets list is clicked
* `preset_activation_alert_rendered` &ndash; triggered when a message to confirm preset activation is rendered (includes `is_active` property)
* `preset_activation_alert_closed` &ndash; triggered when a preset activation alert is closed 
		(includes properties `is_active`, and `action: 'confirm' | 'cancel' | 'dismiss'` )


## Available hooks

Hook name | Hook type | Default value | Additional Information
--------- | --------- | ------------- | ----------------------
`automatewoo/usage_tracking/enabled` | Filter | `true` | Enables or disables AutomateWoo usage tracking.
`automatewoo/usage_tracking/init` | Action | n/a | Runs before track events and tracker data is initialized.
`automatewoo/usage_tracking/addon_tracking_classes` | Filter | empty array | Allows add-ons to include their own usage tracking classes for initialization.
`automatewoo/usage_tracking/addon_base_properties` | Filter | empty array | Allows add-ons to include properties with all tracks events.
