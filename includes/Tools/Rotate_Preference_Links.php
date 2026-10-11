<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Tool_Rotate_Preference_Links
 * @since 6.9.1
 */
class Tool_Rotate_Preference_Links extends Tool_Abstract {

	/**
	 * @var string
	 */
	public $id = 'rotate_preference_links';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->title                  = __( 'Rotate preference links', 'automatewoo' );
		$this->description            = __( 'Invalidates every previously sent preference link. One-click unsubscribe links in emails already sent keep working for a grace period, so your bulk sending stays compliant. Customers will need a new link to manage their preferences.', 'automatewoo' );
		$this->additional_description = __( "This only invalidates tokenized links. Older links that contain a raw customer key aren't affected and keep working until customer key acceptance is switched off.", 'automatewoo' );
	}


	/**
	 * @param array $args
	 */
	public function display_confirmation_screen( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Part of the overridable signature; this tool takes no args.
		$grace_period = Preferences_Token::get_rotation_grace_period();

		if ( Preferences_Token::is_rotation_grace_open() ) {
			// A repeat rotation keeps the earlier rotation's grace but can't
			// extend the record any further back, so links minted since that
			// rotation lose their grace. Say exactly which links stop working.
			$message = sprintf(
				/* translators: %s: time until the earlier grace period ends, e.g. "6 days" */
				__( "Preference links were already rotated recently. You can rotate again now, but one-click unsubscribe links in emails sent since that rotation will stop working immediately. Links from emails sent before it aren't affected — they keep working for another %s, as promised. This can not be undone.", 'automatewoo' ),
				human_time_diff( time(), Preferences_Token::get_rotation_grace_ends_at() )
			);

			echo wp_kses_post( '<p>' . $message . '</p>' );
			return;
		}

		if ( $grace_period > 0 ) {
			$message = sprintf(
				/* translators: %s: human-readable grace period, e.g. "1 week" */
				__( "Are you sure you want to rotate preference links? Existing preference links stop working immediately. One-click unsubscribe links in emails already sent keep working for %s, so recipients can still opt out and your sender reputation isn't hurt. This can not be undone.", 'automatewoo' ),
				human_time_diff( 0, $grace_period )
			);
		} else {
			$message = __( 'Are you sure you want to rotate preference links? All existing unsubscribe and preference links stop working immediately, including unsubscribe links in emails already sent. Customers who received those emails must visit the site to opt out again. This can not be undone.', 'automatewoo' );
		}

		echo wp_kses_post( '<p>' . $message . '</p>' );
	}


	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	public function process( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Part of the overridable signature; this tool takes no args.
		$was_repeat_inside_grace = Preferences_Token::is_rotation_grace_open();
		$grace_period            = Preferences_Token::get_rotation_grace_period();

		Preferences_Token::rotate_all();

		if ( $was_repeat_inside_grace ) {
			// Tell the admin what actually happened: which cohort lost its
			// grace, and which one kept it.
			Admin\Controllers::get( 'tools' )->add_message(
				sprintf(
					/* translators: %s: time until the earlier grace period ends, e.g. "6 days" */
					__( "Preference links were rotated again. One-click unsubscribe links from emails sent between the two rotations stopped working immediately. Links from emails sent before the earlier rotation weren't affected — they keep working for another %s.", 'automatewoo' ),
					human_time_diff( time(), Preferences_Token::get_rotation_grace_ends_at() )
				)
			);
		} elseif ( $grace_period > 0 ) {
			Admin\Controllers::get( 'tools' )->add_message(
				sprintf(
					/* translators: %s: human-readable grace period, e.g. "1 week" */
					__( 'Preference links were rotated. Existing links stopped working, except one-click unsubscribe links in emails already sent — those keep working for %s.', 'automatewoo' ),
					human_time_diff( 0, $grace_period )
				)
			);
		} else {
			Admin\Controllers::get( 'tools' )->add_message(
				__( 'Preference links were rotated. All existing preference and unsubscribe links stopped working immediately.', 'automatewoo' )
			);
		}

		return true;
	}


	/**
	 * The rotation is two synchronous option writes — nothing is queued, so the
	 * generic "may still be processing in the background" line would be untrue.
	 * process() queues its own message describing exactly what the rotation did,
	 * so the controller has nothing to add.
	 *
	 * @since 6.9.1
	 *
	 * @return string
	 */
	public function get_success_message() {
		return '';
	}
}
