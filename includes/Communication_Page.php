<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Communication_Page
 *
 * @since 4.0
 */
class Communication_Page {

	/**
	 * Intent that records a tracking opt-out straight from a link in an email footer.
	 *
	 * @since 6.9.1
	 *
	 * @var string
	 */
	const INTENT_TRACKING_OPT_OUT = 'tracking-opt-out';

	/** @var string $using_customer_key True if the customer is retreived using a customer key */
	private static $using_customer_key = false;

	/**
	 * True when the presenting credential is a token whose bound intent is
	 * non-empty. Rule R2 keys off this — the token's own bound intent — never
	 * off the `intent` request arg or any other request parameter.
	 *
	 * @since 6.9.1
	 *
	 * @var bool
	 */
	private static $intent_is_token_bound = false;

	/**
	 * Unix timestamp the presenting token expires at, when the render was
	 * authorized by a token; 0 when the render authority is a login or a raw
	 * `customer_key`, or when the token never expires. Rule R4 keys off this:
	 * a token-authorized render caps the form's re-minted hidden token at the
	 * presenting token's remaining expiry, so revisiting a leaked link can
	 * never extend its lifetime. Only a real login (or a `customer_key`
	 * during its acceptance window, which has no expiry anyway) mints a fresh
	 * full-lifetime token.
	 *
	 * @since 6.9.1
	 *
	 * @var int
	 */
	private static $presenting_token_expires = 0;

	/**
	 * Init runs when on the communication preferences page
	 */
	public static function init() {
		aw_no_page_cache();
	}

	/**
	 * Get the contents of the communication preferences shortcode
	 *
	 * A presented credential — an `aw_token`, or (behind the
	 * `automatewoo/preferences/accept_customer_key` filter) a raw
	 * `customer_key` — always performs the action it carries, even while a
	 * user is logged in: an unsubscribe click must always take effect. What
	 * renders afterwards depends on the strongest credential: a real login
	 * still gets the editable form (rule R2 does not apply to it — the
	 * session is the stronger credential), but only for the logged-in user's
	 * own data, so a credential resolving to a different customer performs no
	 * action — the visitor is told the link is for a different email address.
	 *
	 * @since 6.9.1 Accepts `aw_token`, honouring rule R1 (expiry is honoured
	 *              only for a token's own bound `unsubscribe` intent) and
	 *              rule R2 (a non-empty-intent token never renders the
	 *              editable form).
	 *
	 * @return string
	 */
	public static function output_preferences_shortcode() {
		// Reset for every call: in a single PHP process (e.g. tests) these
		// flags would otherwise leak from one shortcode render into the next.
		self::$using_customer_key       = false;
		self::$intent_is_token_bound    = false;
		self::$presenting_token_expires = 0;

		$token = Clean::string( aw_request( 'aw_token' ) );

		$customer      = false;
		$decoded       = false;
		$intent        = null;
		$workflow_id   = null;
		$token_expired = false;

		if ( $token ) {
			$decoded = Preferences_Token::validate_allowing_expired( $token );

			if ( $decoded && ( 'unsubscribe' === $decoded['intent'] || ! $decoded['expired'] ) ) {
				// Rule R1: only an unsubscribe-bound token is honoured while
				// expired; every other intent must be live.
				$customer    = $decoded['customer'];
				$intent      = $decoded['intent'];
				$workflow_id = $decoded['workflow_id'];
			} elseif ( $decoded ) {
				// Authentic, but expired, and not bound to 'unsubscribe'.
				$token_expired = true;
			}

			if ( $customer ) {
				self::$using_customer_key       = true;
				self::$intent_is_token_bound    = '' !== $intent;
				self::$presenting_token_expires = (int) $decoded['expires'];
			}
		}

		$redeemed_customer_key = false;

		if ( ! $customer && ! $token_expired ) {
			$customer_key = Clean::string( aw_request( 'customer_key' ) );

			if ( $customer_key && apply_filters( 'automatewoo/preferences/accept_customer_key', true ) ) {
				self::$using_customer_key = true;
				$customer                 = Customer_Factory::get_by_key( $customer_key );
				// Not counted yet: a resolved key can still be refused below
				// (a visitor logged in as a different customer), and a refused
				// redemption must not inflate the counts the acceptance-window
				// decision rests on. Recorded once the key is accepted.
				$redeemed_customer_key = (bool) $customer;
			}
		}

		if ( is_user_logged_in() ) {
			return self::output_preferences_shortcode_for_logged_in_user( $decoded, $customer, $intent, $workflow_id, $redeemed_customer_key );
		}

		if ( $token_expired ) {
			ob_start();
			self::output_preferences_form( false, 'link-expired' );
			return ob_get_clean();
		}

		if ( $redeemed_customer_key ) {
			// No login to refuse it against: the key is accepted and about to
			// authorize this render, so the redemption counts.
			Customer_Key_Redemption_Counter::record();
		}

		ob_start();
		self::output_preferences_form( $customer, $intent, $workflow_id );
		return ob_get_clean();
	}

	/**
	 * Render the preferences shortcode for a logged-in user.
	 *
	 * The action bound to a presented credential is applied first — an
	 * unsubscribe click always unsubscribes — but only when the credential
	 * resolves to the logged-in user's own customer; someone else's token or
	 * key must never act on, or reveal, another customer's data. That refusal
	 * is not silent: a valid credential for a different customer queues a
	 * notice telling the visitor to sign out to use the link. The editable
	 * form then renders for the logged-in customer, because the session — not
	 * the credential — is the render authority.
	 *
	 * @since 6.9.1
	 *
	 * @param array|false    $decoded             Decoded `aw_token` data from
	 *                                             Preferences_Token::validate_allowing_expired(),
	 *                                             or false when the request carried no
	 *                                             authentic token.
	 * @param Customer|false $credential_customer Customer resolved from the presented credential.
	 * @param string|null    $intent              Intent bound to the presenting token, if any.
	 * @param int|null       $workflow_id         Workflow bound to the presenting token, if any.
	 * @param bool           $used_customer_key   Whether the credential is a raw customer_key that
	 *                                             resolved a customer. Its redemption is counted here,
	 *                                             and only when the key is accepted — a key belonging
	 *                                             to a different customer is refused, and a refused
	 *                                             redemption must not inflate the counts that decide
	 *                                             when the customer_key acceptance window can close.
	 * @return string
	 */
	private static function output_preferences_shortcode_for_logged_in_user( $decoded, $credential_customer, $intent, $workflow_id, $used_customer_key = false ) {
		$customer = Customer_Factory::get_by_user_id( get_current_user_id() );

		if ( $customer && $credential_customer instanceof Customer && (int) $credential_customer->get_id() === (int) $customer->get_id() ) {
			if ( $used_customer_key ) {
				// The key resolved the logged-in customer's own record, so the
				// redemption is accepted whatever intent it carries.
				Customer_Key_Redemption_Counter::record();
			}

			if ( null === $intent ) {
				// A customer_key carries its action in the `intent` request arg.
				$intent      = Clean::string( aw_request( 'intent' ) );
				$workflow_id = Clean::id( aw_request( 'workflow' ) );
			}

			// Explicit empty-string check: the intent "0" is falsy in PHP but
			// is a non-empty intent and must still perform its action.
			if ( '' !== $intent && ! aw_request( 'automatewoo_save_changes' ) ) {
				self::perform_intent_action( $customer, $intent, (int) $workflow_id );
			}
		} elseif ( $customer && $credential_customer instanceof Customer ) {
			// The credential is valid but belongs to a different customer, so
			// its action is refused — but never silently: the visitor clicked a
			// real link (e.g. an unsubscribe) and must learn why nothing
			// happened. The other customer's email address is not disclosed,
			// because the person holding the link may not be its owner.
			wp_enqueue_style( 'automatewoo-communication-page' );
			wc_add_notice(
				__( "This link is for a different email address, so we didn't change any preferences. To use it, sign out first.", 'automatewoo' ),
				'notice'
			);
		}

		if ( $customer ) {
			self::maybe_notify_of_expired_token_for_logged_in_customer( $decoded, $customer );
		}

		// The render authority is the login, not the credential, so the
		// credential flags must not trigger the confirmation-only branch, and
		// (rule R4) the login mints a fresh full-lifetime token, uncapped by
		// whatever credential the request happened to carry.
		self::$using_customer_key       = false;
		self::$intent_is_token_bound    = false;
		self::$presenting_token_expires = 0;

		ob_start();
		self::output_preferences_form( $customer );
		return ob_get_clean();
	}

	/**
	 * If an authentic, expired, non-`unsubscribe` token resolves to the
	 * currently logged-in customer, show a notice that the link had expired
	 * rather than silently swallowing the click.
	 *
	 * @since 6.9.1
	 *
	 * @param array|false $decoded  Decoded token data from the shortcode's own
	 *                               validate_allowing_expired() call, passed through so the
	 *                               token isn't decoded (and the customer looked up) twice.
	 * @param Customer    $customer The logged-in customer.
	 */
	private static function maybe_notify_of_expired_token_for_logged_in_customer( $decoded, $customer ) {
		if ( ! $decoded || ! $decoded['expired'] || ! $decoded['customer'] instanceof Customer ) {
			return;
		}

		if ( 'unsubscribe' === $decoded['intent'] ) {
			// Rule R1 honours an expired unsubscribe token, so its opt-out already
			// ran and queued its own confirmation — a stale-link notice beside it
			// would contradict that confirmation. Reachable because
			// Preferences_Token::generate() can cap an unsubscribe token's expiry.
			return;
		}

		if ( (int) $decoded['customer']->get_id() !== (int) $customer->get_id() ) {
			return;
		}

		wp_enqueue_style( 'automatewoo-communication-page' );
		wc_add_notice( __( 'Your link had expired, so here are your current preferences.', 'automatewoo' ) );
	}

	/**
	 * Output the communication preferences page.
	 *
	 * A token bound to a non-empty intent (rather than a real login) performs
	 * its action and renders a confirmation only — per rule R2 it must never
	 * also render the editable management form, because that form's hidden
	 * field mints a fresh management token/key. A raw `customer_key` with an
	 * `intent` request arg performs any recognised action and then renders the
	 * editable form, matching the pre-token behaviour: the key already is the
	 * general management credential for its holder, so the form escalates
	 * nothing, and it keeps account-less customers on old links able to manage
	 * their preferences.
	 *
	 * @since 6.9.1 Gained the `$intent` and `$workflow_id` parameters so a
	 *              token-bound intent no longer has to be read back out of
	 *              `aw_request()`.
	 *
	 * @param Customer|false $customer    Customer to render preferences for.
	 * @param string|null    $intent      Intent bound to the presenting credential.
	 *                                     Falls back to the `intent` request param.
	 * @param int|null       $workflow_id Workflow bound to the presenting credential.
	 *                                     Falls back to the `workflow` request param.
	 */
	public static function output_preferences_form( $customer, $intent = null, $workflow_id = null ) {
		$data = [];

		// Consume the credential flags one-shot so a later direct call (e.g.
		// Communication_Account_Tab::endpoint_content() in the same process)
		// can never inherit a stale credential from an earlier render.
		$using_customer_key       = self::$using_customer_key;
		$intent_is_token_bound    = self::$intent_is_token_bound;
		$presenting_token_expires = self::$presenting_token_expires;

		self::$using_customer_key       = false;
		self::$intent_is_token_bound    = false;
		self::$presenting_token_expires = 0;

		wp_enqueue_style( 'automatewoo-communication-page' );

		if ( null === $intent ) {
			$intent = Clean::string( aw_request( 'intent' ) );
		}

		if ( null === $workflow_id ) {
			$workflow_id = Clean::id( aw_request( 'workflow' ) );
		}

		$data['intent'] = $intent;

		if ( ! $customer ) {
			aw_get_template( 'communication-preferences/communication-form-no-customer.php', $data );
			return;
		}

		$data['customer'] = $customer;

		// Explicit empty-string check, not truthiness: the intent "0" matches
		// the token intent pattern but is falsy in PHP, and treating it as "no
		// intent" here would let a "0"-intent token bypass rule R2 and reach
		// the editable form below.
		if ( $using_customer_key && '' !== $intent ) {
			if ( $intent_is_token_bound ) {
				// Rule R2: a token bound to a non-empty intent performs its
				// action and renders confirmation only — never the editable
				// form, whatever other parameters the request carries.
				self::render_confirmation( $customer, $intent, (int) $workflow_id );
				return;
			}

			// A raw customer_key with a recognised `intent` request arg performs
			// the action and then falls through to the editable form — the
			// legacy behaviour. Rule R2 does not apply here: a customer_key
			// already IS its holder's general management credential, so
			// rendering the form escalates nothing, and a customer without a
			// WordPress account following an old link would otherwise be left
			// with a confirmation and a sign-in prompt they can't use. An
			// unrecognised intent performs nothing and falls through the same
			// way.
			if ( ! aw_request( 'automatewoo_save_changes' ) ) {
				self::perform_intent_action( $customer, $intent, (int) $workflow_id );
			}
		}

		// The form's hidden management token. Rule R4: when a token authorized
		// this render, cap the re-minted token at the presenting token's
		// remaining expiry so revisiting a link never extends its lifetime.
		$data['preferences_token'] = Preferences_Token::generate( $customer, '', 0, $presenting_token_expires );

		aw_get_template( 'communication-preferences/communication-form.php', $data );
	}

	/**
	 * Perform a token/key-authorized intent action and render its confirmation.
	 *
	 * Never renders the editable management form (rule R2) — including for an
	 * intent this page does not recognise (e.g. a third-party value minted
	 * into a token by `Frontend::get_communication_page_permalink()`). Such
	 * an intent performs no action, but must still print an explanatory
	 * notice rather than a blank page: rendering the form instead would let
	 * any single-purpose token mint a fresh management credential, which is
	 * exactly what R2 exists to prevent, so graceful degradation here means
	 * feedback, never the form.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer    Customer the action applies to.
	 * @param string   $intent      Bound intent, e.g. 'unsubscribe'.
	 * @param int      $workflow_id Workflow that caused the opt-out, if any.
	 */
	private static function render_confirmation( Customer $customer, $intent, $workflow_id ) {
		$acted = self::perform_intent_action( $customer, $intent, $workflow_id );

		if ( ! $acted ) {
			wc_add_notice( __( "This link can't be used to change your preferences.", 'automatewoo' ), 'notice' );
		}

		if ( email_exists( $customer->get_email() ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %1$s my account link start, %2$s my account link end. */
					__( 'To manage other preferences, %1$ssign in to your account%2$s.', 'automatewoo' ),
					'<a href="' . wc_get_page_permalink( 'myaccount' ) . '">',
					'</a>'
				)
			);
		}

		wc_print_notices();
	}

	/**
	 * Apply a credential's bound intent action and queue its success notice.
	 *
	 * Unrecognised intents deliberately do nothing.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer    Customer the action applies to.
	 * @param string   $intent      Intent to act on, e.g. 'unsubscribe'.
	 * @param int      $workflow_id Workflow that caused the opt-out, if any.
	 * @return bool Whether the intent was recognised and its action performed.
	 */
	private static function perform_intent_action( Customer $customer, $intent, $workflow_id ) {
		if ( 'unsubscribe' === $intent ) {
			$customer->opt_out( $workflow_id );
			wc_add_notice( __( "Saved successfully! You won't receive marketing communications from us.", 'automatewoo' ) );
			return true;
		}

		if ( self::INTENT_TRACKING_OPT_OUT === $intent ) {
			$customer->opt_out_of_tracking();
			wc_add_notice( __( 'Saved successfully! We will no longer record when you open our emails or click links in them.', 'automatewoo' ) );
			return true;
		}

		return false;
	}

	/**
	 * Get the contents of the communication preferences signup form
	 *
	 * @return string
	 */
	public static function output_signup_form() {
		wp_enqueue_style( 'automatewoo-communication-page' );
		ob_start();
		aw_get_template( 'communication-preferences/signup-form.php' );
		return ob_get_clean();
	}
}
