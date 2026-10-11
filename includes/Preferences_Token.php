<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Generates and validates signed, expiring tokens that authorize access to the
 * communication-preferences surface (unsubscribe links, the preferences form, and
 * related workflow-emitted URLs) without relying on the durable, non-rotating
 * `customer_key`.
 *
 * Token format: `v1.{customer_id}.{expires}.{intent}.{workflow_id}.{sig}`.
 *
 * @since 6.9.1
 */
class Preferences_Token {

	/**
	 * Token format version.
	 *
	 * @var string
	 */
	const VERSION = 'v1';

	/**
	 * Default lifetime, in seconds, for tokens whose intent is not `unsubscribe`.
	 *
	 * @var int
	 */
	const DEFAULT_LIFETIME = 180 * DAY_IN_SECONDS;

	/**
	 * Pattern an intent must match. Deliberately restrictive: the HMAC input is
	 * `|`-delimited and the token string is `.`-delimited, so an intent containing
	 * either character could shift fields in either representation — which is why
	 * `.` and `|` can never be allowed here. Uppercase letters and underscores are
	 * permitted so add-on intents don't have to fall back to emitting a raw
	 * `customer_key`; intent comparisons stay exact and case-sensitive, so
	 * `Unsubscribe` is simply a different intent than `unsubscribe`. The `D`
	 * modifier stops `$` matching before a trailing newline (PCRE's default
	 * behaviour), which would otherwise let a newline into a token segment that
	 * is later embedded in a redirect `Location` header.
	 *
	 * @var string
	 */
	const INTENT_PATTERN = '/^[A-Za-z0-9_-]*$/D';

	/**
	 * Site option storing the site-wide token version. Bumping it invalidates
	 * every previously minted token.
	 *
	 * @var string
	 */
	const SITE_VERSION_OPTION = 'automatewoo_preferences_token_version';

	/**
	 * Option recording the most recent site-wide rotation: the site version it
	 * superseded and when it happened. Read by the rotation grace period.
	 *
	 * @var string
	 */
	const ROTATION_OPTION = 'automatewoo_preferences_token_rotation';

	/**
	 * Default grace period, in seconds, during which `unsubscribe`-intent
	 * tokens signed under the immediately superseded site version are still
	 * accepted after a rotation.
	 *
	 * Seven days: mail in flight is delivered within hours, but people click
	 * unsubscribe days after delivery, and Gmail/Yahoo require one-click
	 * unsubscribe (RFC 8058) to keep working for bulk senders — an abrupt
	 * cutover would hurt deliverability. An unsubscribe token authorizes
	 * suppression only, so briefly honouring a stale one is far less harmful
	 * than breaking the one-click requirement.
	 *
	 * @var int
	 */
	const DEFAULT_ROTATION_GRACE_PERIOD = 7 * DAY_IN_SECONDS;

	/**
	 * Customer meta key storing a per-customer token version. Bumping it
	 * invalidates only that customer's previously minted tokens.
	 *
	 * @var string
	 */
	const CUSTOMER_VERSION_META_KEY = 'preferences_token_version';

	/**
	 * Generate a signed preferences token for a customer.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer    The customer the token authorizes.
	 * @param string   $intent      Optional intent, e.g. 'unsubscribe'. Empty string is the
	 *                               management intent. Must match `^[A-Za-z0-9_-]*$`.
	 * @param int      $workflow_id Optional workflow ID associated with the token.
	 * @param int      $max_expires Optional cap (Unix timestamp) on the minted token's
	 *                               expiry. When non-zero, the token never expires later
	 *                               than this — rule R4: a token-authorized render must
	 *                               never mint a longer-lived token than the one that
	 *                               authorized it. Zero means no cap.
	 * @return string The generated token, or an empty string if the intent is invalid.
	 */
	public static function generate( Customer $customer, string $intent = '', int $workflow_id = 0, int $max_expires = 0 ): string {
		if ( ! preg_match( self::INTENT_PATTERN, $intent ) ) {
			return '';
		}

		$expires = 'unsubscribe' === $intent ? 0 : (int) ( time() + self::get_lifetime( $intent ) );

		if ( $max_expires > 0 && ( 0 === $expires || $expires > $max_expires ) ) {
			$expires = $max_expires;
		}

		return self::build_token( $customer->get_id(), $expires, $intent, $workflow_id, (int) self::get_customer_version( $customer ) );
	}

	/**
	 * Validate a token's authenticity, but tolerate expiry.
	 *
	 * A `false` return means the token is not authentic and must be refused.
	 * An array return only proves authenticity: every caller MUST branch on
	 * the returned `expired` flag, and may only honour an expired token when
	 * the token's own bound intent is `unsubscribe` (rule R1) — never based
	 * on request parameters. Tolerating expiry anywhere else silently revives
	 * stale credentials.
	 *
	 * @since 6.9.1
	 *
	 * @param string $token The token to validate.
	 * @return array|false {
	 *     Decoded token data, or false if the token is malformed, tampered, or
	 *     resolves to a customer that no longer exists.
	 *
	 *     @type Customer $customer    The resolved customer.
	 *     @type string   $intent      The token's bound intent.
	 *     @type int      $workflow_id The token's bound workflow ID.
	 *     @type int      $expires     Unix timestamp the token expires at, or 0 for never.
	 *     @type bool     $expired     Whether the token has expired.
	 * }
	 */
	public static function validate_allowing_expired( string $token ) {
		$segments = explode( '.', $token );

		if ( 6 !== count( $segments ) ) {
			return false;
		}

		list( $version, $customer_id, $expires, $intent, $workflow_id, $sig ) = $segments;

		if ( self::VERSION !== $version ) {
			return false;
		}

		if ( ! ctype_digit( $customer_id ) || ! ctype_digit( $expires ) || ! ctype_digit( $workflow_id ) ) {
			return false;
		}

		if ( ! preg_match( self::INTENT_PATTERN, $intent ) ) {
			return false;
		}

		$customer_id = (int) $customer_id;
		$expires     = (int) $expires;
		$workflow_id = (int) $workflow_id;

		$customer = Customer_Factory::get( $customer_id );

		if ( ! $customer instanceof Customer ) {
			return false;
		}

		$customer_version = (int) self::get_customer_version( $customer );
		$expected_sig     = self::sign( $customer_id, $expires, $intent, $workflow_id, $customer_version, self::get_site_version() );

		if ( ! hash_equals( $expected_sig, $sig ) &&
			! self::sig_matches_superseded_site_version( $sig, $customer_id, $expires, $intent, $workflow_id, $customer_version ) ) {
			return false;
		}

		// Only the 'unsubscribe' intent is allowed to never expire. Enforced here,
		// not only at mint, so a hostile or buggy token_lifetime filter can never
		// produce a never-expiring token for any other intent.
		if ( 0 === $expires && 'unsubscribe' !== $intent ) {
			return false;
		}

		$expired = 0 !== $expires && $expires < time();

		return [
			'customer'    => $customer,
			'intent'      => $intent,
			'workflow_id' => $workflow_id,
			'expires'     => $expires,
			'expired'     => $expired,
		];
	}

	/**
	 * Bump the site-wide token version, invalidating every previously minted
	 * token regardless of which customer it was minted for.
	 *
	 * The superseded version and the rotation time are recorded so that
	 * `unsubscribe`-intent tokens — and only those — keep verifying for the
	 * rotation grace period. See sig_matches_superseded_site_version().
	 *
	 * Rotating again while the previous rotation's grace window is still open is
	 * allowed — the tool exists for incident response, and an admin who discovers
	 * a leak was worse than thought must not be locked out for a week — but it
	 * does NOT advance the superseded pointer: the first rotation's cohort keeps
	 * the grace it was promised, for its originally scheduled remainder. What the
	 * second rotation sacrifices is the grace of mail sent BETWEEN the two
	 * rotations (typically hours' worth, against up to a week for the protected
	 * cohort): those tokens were signed under a version the record no longer
	 * reaches back to, so they are rejected immediately.
	 *
	 * @since 6.9.1
	 *
	 * @return bool Always true; kept for callers that check the result.
	 */
	public static function rotate_all(): bool {
		$superseded  = self::get_site_version();
		$new_version = $superseded + 1;

		if ( self::is_rotation_grace_open() ) {
			// Keep the superseded pointer and the grace clock exactly as the
			// first rotation set them; only re-bind the record to the new site
			// version so the stale-record guard keeps recognising it.
			$rotation                 = get_option( self::ROTATION_OPTION );
			$rotation['site_version'] = $new_version;
		} else {
			$rotation = [
				'superseded_version' => $superseded,
				'rotated_at'         => time(),
				'site_version'       => $new_version,
			];
		}

		update_option( self::ROTATION_OPTION, $rotation, false );
		update_option( self::SITE_VERSION_OPTION, $new_version );

		return true;
	}

	/**
	 * Get the Unix timestamp the current rotation grace window ends at, or 0
	 * when no grace window applies — no rotation recorded, the record does not
	 * describe the latest rotation (a stale record, e.g. left behind by a
	 * restored database, must not revive older versions), or the grace period
	 * is zero.
	 *
	 * @since 6.9.1
	 *
	 * @return int
	 */
	public static function get_rotation_grace_ends_at(): int {
		$rotation = get_option( self::ROTATION_OPTION );

		if ( ! is_array( $rotation ) || ! isset( $rotation['superseded_version'], $rotation['rotated_at'] ) ) {
			return 0;
		}

		// Stale-record guard: the record must have been written (or re-bound, for
		// a repeat rotation inside an open grace) under the current site version.
		// Records without the site_version field predate repeat rotations and can
		// only describe a single step back.
		$recorded_for = isset( $rotation['site_version'] )
			? (int) $rotation['site_version']
			: (int) $rotation['superseded_version'] + 1;

		if ( $recorded_for !== self::get_site_version() ) {
			return 0;
		}

		$grace_period = self::get_rotation_grace_period();

		// A non-positive grace period means the rotation cut over immediately: no
		// grace window ever existed, so don't return `rotated_at` as if one had.
		if ( $grace_period <= 0 ) {
			return 0;
		}

		return (int) $rotation['rotated_at'] + $grace_period;
	}

	/**
	 * Whether the most recent rotation's grace window is still open — i.e.
	 * `unsubscribe` tokens signed under the superseded site version are still
	 * being accepted, and a further rotation would be refused.
	 *
	 * @since 6.9.1
	 *
	 * @return bool
	 */
	public static function is_rotation_grace_open(): bool {
		return time() < self::get_rotation_grace_ends_at();
	}

	/**
	 * Get the rotation grace period, in seconds, during which `unsubscribe`
	 * tokens signed under the immediately superseded site version stay valid.
	 *
	 * @since 6.9.1
	 *
	 * @return int
	 */
	public static function get_rotation_grace_period(): int {
		/**
		 * Filters the rotation grace period, in seconds, for `unsubscribe`
		 * tokens after a site-wide rotation. Return 0 (or less) to make a
		 * rotation cut over immediately — see DEFAULT_ROTATION_GRACE_PERIOD
		 * for why that risks a bulk sender's one-click unsubscribe compliance.
		 *
		 * @since 6.9.1
		 *
		 * @param int $grace_period Grace period in seconds.
		 */
		return (int) apply_filters( 'automatewoo/preferences/token_rotation_grace_period', self::DEFAULT_ROTATION_GRACE_PERIOD );
	}

	/**
	 * Whether a signature that failed against the current site version was
	 * produced under the immediately superseded site version, within the
	 * rotation grace period, for a token bound to the `unsubscribe` intent.
	 *
	 * Everything is re-verified exactly as in the primary check — same
	 * customer version, same site binding, same secret — with only the site
	 * version substituted, so the grace never weakens any other guarantee.
	 * The grace deliberately reaches back exactly one version: a token from
	 * two or more rotations ago is rejected regardless of timing.
	 *
	 * @since 6.9.1
	 *
	 * @param string $sig              Signature segment presented by the token.
	 * @param int    $customer_id      Customer ID.
	 * @param int    $expires          Unix timestamp the token expires at, or 0 for never.
	 * @param string $intent           Token intent.
	 * @param int    $workflow_id      Associated workflow ID.
	 * @param int    $customer_version Customer's current token version.
	 * @return bool
	 */
	private static function sig_matches_superseded_site_version( string $sig, int $customer_id, int $expires, string $intent, int $workflow_id, int $customer_version ): bool {
		if ( 'unsubscribe' !== $intent ) {
			return false;
		}

		if ( ! self::is_rotation_grace_open() ) {
			return false;
		}

		$rotation       = get_option( self::ROTATION_OPTION );
		$superseded_sig = self::sign( $customer_id, $expires, $intent, $workflow_id, $customer_version, (int) $rotation['superseded_version'] );

		return hash_equals( $superseded_sig, $sig );
	}

	/**
	 * Build a token string from its component parts.
	 *
	 * @since 6.9.1
	 *
	 * @param int    $customer_id      Customer ID.
	 * @param int    $expires          Unix timestamp the token expires at, or 0 for never.
	 * @param string $intent           Token intent.
	 * @param int    $workflow_id      Associated workflow ID.
	 * @param int    $customer_version Customer's current token version.
	 * @return string
	 */
	private static function build_token( int $customer_id, int $expires, string $intent, int $workflow_id, int $customer_version ): string {
		$sig = self::sign( $customer_id, $expires, $intent, $workflow_id, $customer_version, self::get_site_version() );

		return implode(
			'.',
			[ self::VERSION, $customer_id, $expires, $intent, $workflow_id, $sig ]
		);
	}

	/**
	 * Compute the HMAC signature for a token's component parts.
	 *
	 * @since 6.9.1
	 *
	 * @param int    $customer_id      Customer ID.
	 * @param int    $expires          Unix timestamp the token expires at, or 0 for never.
	 * @param string $intent           Token intent.
	 * @param int    $workflow_id      Associated workflow ID.
	 * @param int    $customer_version Customer's current token version.
	 * @param int    $site_version     Site-wide token version to sign under. The current
	 *                                  version everywhere except the rotation grace check,
	 *                                  which retries against the superseded version.
	 * @return string 64-character hex sha256 HMAC.
	 */
	private static function sign( int $customer_id, int $expires, string $intent, int $workflow_id, int $customer_version, int $site_version ): string {
		$data = implode(
			'|',
			[
				self::VERSION,
				$customer_id,
				$expires,
				$intent,
				$workflow_id,
				$site_version,
				$customer_version,
				self::get_site_binding(),
			]
		);

		return hash_hmac( 'sha256', $data, self::get_secret() );
	}

	/**
	 * Get the value that binds a token to the current site.
	 *
	 * On multisite, `wp_salt( 'auth' )` (and therefore the token secret) is
	 * network-wide, and both version counters default to the same value on
	 * every site — so without a site binding a token minted on one site of
	 * the network would verify on every other site and resolve to whichever
	 * customer happens to share the numeric ID there. Including this value in
	 * the signed payload makes cross-site replay fail signature verification.
	 *
	 * `get_current_blog_id()` is used rather than `home_url()` because it is
	 * unique per site within a network and stable across domain or URL
	 * changes, which would otherwise silently invalidate every outstanding
	 * token whenever a site is re-domained.
	 *
	 * @since 6.9.1
	 *
	 * @return string
	 */
	private static function get_site_binding(): string {
		/**
		 * Filters the site-binding value signed into preferences tokens.
		 *
		 * Intended for unusual network setups (e.g. sites that migrate
		 * between blog IDs) that need a stable binding. Anyone able to add
		 * this filter can already control `automatewoo/preferences/token_secret`,
		 * so it grants no new forging power.
		 *
		 * @since 6.9.1
		 *
		 * @param string $binding The site-binding value.
		 */
		return (string) apply_filters( 'automatewoo/preferences/token_site_binding', (string) get_current_blog_id() );
	}

	/**
	 * Get the secret used to sign preferences tokens.
	 *
	 * @since 6.9.1
	 *
	 * @return string
	 */
	private static function get_secret(): string {
		$secret = hash_hmac( 'sha256', 'automatewoo-preferences-token', wp_salt( 'auth' ) );

		/**
		 * Filters the secret used to sign preferences tokens.
		 *
		 * @since 6.9.1
		 *
		 * @param string $secret The generated secret.
		 */
		return (string) apply_filters( 'automatewoo/preferences/token_secret', $secret );
	}

	/**
	 * Get the lifetime, in seconds, for a token of the given intent.
	 *
	 * @since 6.9.1
	 *
	 * @param string $intent Token intent.
	 * @return int
	 */
	private static function get_lifetime( string $intent ): int {
		/**
		 * Filters the lifetime, in seconds, of a preferences token.
		 *
		 * @since 6.9.1
		 *
		 * @param int    $lifetime Lifetime in seconds.
		 * @param string $intent   Token intent.
		 */
		return (int) apply_filters( 'automatewoo/preferences/token_lifetime', self::DEFAULT_LIFETIME, $intent );
	}

	/**
	 * Get the site-wide token version.
	 *
	 * @since 6.9.1
	 *
	 * @return int
	 */
	private static function get_site_version(): int {
		return (int) get_option( self::SITE_VERSION_OPTION, 0 );
	}

	/**
	 * Get a customer's token version.
	 *
	 * @since 6.9.1
	 *
	 * @param Customer $customer Customer.
	 * @return int
	 */
	private static function get_customer_version( Customer $customer ): int {
		return (int) $customer->get_meta( self::CUSTOMER_VERSION_META_KEY );
	}
}
