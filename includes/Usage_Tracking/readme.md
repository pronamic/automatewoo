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
		'conversion_tracking_enabled' => (int) "count",
		'tracking_enabled'            => (int) "count",
	],
];
```

### Tracking events

All event names are prefixed by `wcadmin_aw_`.

Server-side Tracks events recorded through `AutomateWoo\Usage_Tracking\Tracks` include the base property `aw_version`. Add-ons can add more base properties through the `automatewoo/usage_tracking/addon_base_properties` filter. Client-side events recorded through `@woocommerce/tracks` or `AW.tracks.recordEvent()` do not add `aw_version` in this repository.

Event | Trigger | Properties
----- | ------- | ----------
`workflow_before_run` | A workflow starts running. | `aw_version` plus the workflow properties listed below.
`workflow_created` | A workflow is first created. | `aw_version` plus the workflow properties listed below.
`conversion_recorded` | A conversion is recorded from a workflow log. | `aw_version`, `order_currency`, `order_total`, `workflow_run_date`, `workflow_trigger`, `workflow_title`.
`first_installed` | AutomateWoo is installed for the first time. | `aw_version`.
`manual_workflow_runner_select_workflow` | A workflow is selected in the manual workflow runner. | `conversion_tracking_enabled`, `tracking_enabled`, `title`, `type`, `trigger_name`.
`manual_run_workflow_button_clicked` | The manual workflow runner advances after matching items are found. | `items_count`.
`manual_find_matching_cancel_button_clicked` | The find matching items step is cancelled. | No custom properties.
`manual_queue_items_cancel_button_clicked` | The queue items step is cancelled. | No custom properties.
`manual_run_workflow_complete` | The manual workflow runner finishes queueing items. | `items_count`, `conversion_tracking_enabled`, `tracking_enabled`, `title`, `type`, `trigger_name`.
`notice_viewed` | A tracked admin notice is displayed. | `notice_identifier`.
`notice_link_clicked` | A tracked admin notice link is clicked. | `notice_identifier`, `link_type`.
`notice_dismissed` | A tracked admin notice is dismissed. | `notice_identifier`.
`workflow_tab_view` | A tab on the "AutomateWoo > Workflows" screen is viewed. | `tab`.
`preset_list_button_clicked` | A button in the workflow presets list is clicked. | `action`, `preset_name`.
`preset_activation_alert_rendered` | The preset activation alert is rendered. | `is_active`.
`preset_activation_alert_closed` | The preset activation alert is closed. | `is_active`, `action` (`confirm`, `cancel`, or `dismiss`).

#### Workflow event properties

`workflow_before_run` and `workflow_created` use `AutomateWoo\Usage_Tracking\WorkflowTracksData` to flatten workflow data into one-level Tracks properties.

Static workflow properties:

* `conversion_tracking_enabled`
* `date_created`
* `ga_tracking_enabled`
* `status`
* `title`
* `tracking_enabled`
* `unsubscribe_exempt`
* `type`
* `trigger_name`

Dynamic workflow properties:

* `action_{index}` stores each configured action name. For example, `action_0 = send_email`.
* `trigger_{option}` stores scalar trigger option values. Nested trigger option arrays are flattened by appending keys, for example `trigger_order_status_0`.
* `rule_{group_index}_{rule_index}_{property}` stores rule group data after dropping saved rule group IDs and rule IDs. For example, `rule_0_0_name`, `rule_0_0_compare`, and `rule_0_0_value`.
* Workflow data can be extended with the `automatewoo/usage_tracking/workflow_data` filter.
* Rule values for `customer_email` and `customer_phone` are anonymized before being sent.

The external Tracks event registry is maintained outside this repository. This readme documents the in-repository event emitters and property shapes that should be kept in sync with that registry.


## Available hooks

Hook name | Hook type | Default value | Additional Information
--------- | --------- | ------------- | ----------------------
`automatewoo/usage_tracking/enabled` | Filter | `true` | Enables or disables AutomateWoo usage tracking.
`automatewoo/usage_tracking/init` | Action | n/a | Runs before track events and tracker data is initialized.
`automatewoo/usage_tracking/addon_tracking_classes` | Filter | empty array | Allows add-ons to include their own usage tracking classes for initialization.
`automatewoo/usage_tracking/addon_base_properties` | Filter | empty array | Allows add-ons to include properties with all tracks events.
