<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Action_Send_SMS_Twilio
 */
class Action_Send_SMS_Twilio extends Action {


	function load_admin_details() {
		$this->title = __( 'Send SMS (Twilio)', 'automatewoo' );
		$this->group = __( 'SMS', 'automatewoo' );
		$this->description = __( 'It is recommended to include an unsubscribe link by using the variable {{ customer.unsubscribe_url }} in the SMS body.', 'automatewoo' );

		if ( AW()->options()->bitly_api && AW()->options()->bitly_shorten_sms_links ) {
			$bitly_text = __( 'Links in the SMS body will be shortened with the <%1$s>Bitly integration<%2$s>.', 'automatewoo' );
		} else {
			$bitly_text = __( 'To shorten links in the SMS body the <%1$s>Bitly integration<%2$s> must be enabled.', 'automatewoo' );
		}

		$this->description .= sprintf(
			' ' . $bitly_text,
			'a href="' . Admin::page_url( 'settings-bitly' ) . '" target="_blank"',
			'/a'
		);
	}


	function load_fields() {
		$sms_recipient = ( new Fields\Text() )
			->set_name( 'sms_recipient' )
			->set_title( __( 'SMS recipients', 'automatewoo' ) )
			->set_description( __( 'Multiple recipient numbers must be separated by commas. When using the {{ customer.phone }} variable the country code will be added automatically, if not already entered by the customer, by referencing the billing country.', 'automatewoo' ) )
			->set_variable_validation()
			->set_required();

		$sms_body = ( new Fields\Text_Area() )
			->set_name( 'sms_body' )
			->set_title( __( 'SMS body', 'automatewoo' ) )
			->set_rows(4)
			->set_variable_validation()
			->set_required();

		$this->add_field( $sms_recipient );
		$this->add_field( $sms_body );
	}


	/**
	 * @throws \Exception
	 */
	function run() {
		$recipients = Clean::comma_delimited_string( $this->get_option( 'sms_recipient' ) );
		$message = $this->get_option( 'sms_body', true );

		if ( empty( $message ) ) {
			throw new \Exception( __( 'Empty message body', 'automatewoo') );
		}

		$message = $this->process_urls_in_sms( $message );

		$valid_recipients_count = 0;
		foreach ( $recipients as $recipient_string ) {
			$recipient = $this->workflow->variable_processor()->process_field( $recipient_string );
			// Do not send SMS for a variable that resolved to an empty number.
			if( $recipient ) {
				$this->send_sms( $recipient, $recipient_string, $message );
				$valid_recipients_count++;
			}
		}
		if ( $valid_recipients_count == 0 ) {
			throw new \Exception( __( 'No valid recipients', 'automatewoo') );
		}
	}


	/**
	 * Sends an SMS to one recipient.
	 *
	 * @since 4.3.2
	 *
	 * @param string $recipient_phone The phone number of the SMS recipient.
	 * @param string $recipient_string Unprocessed recipient string.
	 * @param string $message         The body of the SMS
	 */
	public function send_sms( $recipient_phone, $recipient_string, $message ) {
		$twilio = Integrations::get_twilio();

		if ( ! $twilio ) {
			return;
		}

		$is_sms_to_customer = $this->is_recipient_the_primary_customer( $recipient_string );

		// check if this SMS is going to the workflow's primary customer
		if ( $is_sms_to_customer ) {
			$customer = $this->workflow->data_layer()->get_customer();

			// check if the customer is unsubscribed
			if ( $this->workflow->is_customer_unsubscribed( $customer ) ) {
				$error = new \WP_Error( 'unsubscribed', __( "The recipient is not opted-in to this workflow.", 'automatewoo' ) );
				$this->workflow->log_action_email_error( $error, $this );
				return;
			}

			// because the SMS is to the primary customer, use the customer's country to parse the phone number
			$recipient_phone = Phone_Numbers::parse( $recipient_phone, $customer->get_billing_country() );
		}
		else {
			$recipient_phone = Phone_Numbers::parse( $recipient_phone );
		}

		$request = $twilio->send_sms( $recipient_phone, $message );

		if ( $request->is_successful() ) {
			$this->workflow->log_action_note( $this, __( 'SMS successfully sent.', 'automatewoo' ) );
		}
		else {
			// don't throw exception since the error is only for one recipient
			$this->workflow->log_action_error( $this, $twilio->get_request_error_message( $request ) );
		}
	}


	/**
	 * Determines if a recipient is the primary customer for the workflow.
	 *
	 * Must be used before the $recipient_phone has variables processed.
	 *
	 * @param string $recipient_field
	 *
	 * @return bool
	 */
	public function is_recipient_the_primary_customer( $recipient_field ) {
		if ( ! $recipient_field ) {
			return false;
		}

		$is_primary_customer = false;

		if ( stristr( $recipient_field, 'customer.phone' ) || stristr( $recipient_field, 'order.billing_phone' ) ) {
			$is_primary_customer = true;
		}

		return apply_filters( 'automatewoo/sms/is_recipient_primary_customer', $is_primary_customer, $this );
	}


	/**
	 * Process the URLs in an SMS body.
	 * - Maybe converts URLs to trackable URLs
	 * - Maybe shortens URL
	 *
	 * @since 4.3.2
	 *
	 * @param string $sms
	 *
	 * @return string
	 */
	public function process_urls_in_sms( $sms ) {
		$replacer = new Replace_Helper( $sms, [ $this, 'callback_process_url' ], 'text_urls' );
		$processed = $replacer->process();
		return $processed;
	}


	/**
	 * Processes a single URL in the SMS body.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function callback_process_url( $url ) {
		$url = html_entity_decode( $url );

		// make URL trackable if enabled
		if ( $this->workflow->is_tracking_enabled() ) {
			// don't track unsubscribe clicks
			if ( ! strstr( $url, 'aw-action=unsubscribe' ) ) {
				$url = $this->workflow->append_ga_tracking_to_url( $url );
				$url = esc_url_raw( Tracking::get_click_tracking_url( $this->workflow, $url ) );
			}
		}

		// shorten links if enabled
		if ( AW()->options()->bitly_shorten_sms_links ) {
			$bitly = Integrations::get_bitly();

			if ( $bitly ) {
				$short_url = $bitly->shorten_url( $url );

				// Default to full url if bitly fails for any reason (e.g. exceeded rate limit).
				// https://github.com/woocommerce/automatewoo/issues/895
				if ( $short_url ) {
					$url = $short_url;
				}
			}
		}

		return $url;
	}


}
