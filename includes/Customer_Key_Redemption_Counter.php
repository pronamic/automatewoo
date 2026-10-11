<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Counts, per UTC day, how often a raw `customer_key` from a legacy emailed
 * preferences link successfully resolves a customer.
 *
 * The three acceptance points behind the
 * `automatewoo/preferences/accept_customer_key` filter call {@see record()}
 * only after {@see Customer_Factory::get_by_key()} actually resolved a
 * customer — a merely-present or unresolvable key is not evidence that a real
 * legacy link is still circulating, so it is never counted. The resulting
 * decay curve (read with WP-CLI from the option) is what decides when the
 * acceptance window can safely close.
 *
 * Known limitation: only core's three acceptance points are observed. The
 * AutomateWoo Referrals add-on independently emits raw customer keys in
 * social share URLs (`?customer=<key>`) and resolves them itself, so where
 * Referrals is active these counts read lower than the real number of keys
 * in circulation, and closing the acceptance window here does not stop keys
 * circulating through Referrals.
 *
 * @since 6.9.1
 */
final class Customer_Key_Redemption_Counter {

	/**
	 * Option holding the per-day counts, keyed by UTC `Y-m-d` date string.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'automatewoo_customer_key_redemptions';

	/**
	 * Days of history to keep. 90 daily entries are enough to read a decay
	 * curve while keeping the option tiny (~3 KB at worst).
	 *
	 * @var int
	 */
	const DAYS_TO_KEEP = 90;

	/**
	 * Record one successful customer_key redemption against today's count.
	 *
	 * Call only after the key resolved a customer. Costs one option read and
	 * one option write per redemption; the option is not autoloaded, so
	 * ordinary front-end requests never load it.
	 */
	public static function record() {
		/**
		 * Filters whether successful customer_key redemptions are counted.
		 *
		 * Return false to disable the per-day redemption counter.
		 *
		 * @since 6.9.1
		 *
		 * @param bool $record Whether to record this redemption.
		 */
		if ( ! apply_filters( 'automatewoo/preferences/record_customer_key_redemptions', true ) ) {
			return;
		}

		$counts = get_option( self::OPTION_NAME );

		if ( ! is_array( $counts ) ) {
			$counts = [];
		}

		$today = gmdate( 'Y-m-d' );

		$counts[ $today ] = isset( $counts[ $today ] ) ? (int) $counts[ $today ] + 1 : 1;

		update_option( self::OPTION_NAME, self::prune( $counts ), false );
	}

	/**
	 * Drop entries older than the retention window so the option is bounded.
	 *
	 * `Y-m-d` strings compare correctly lexicographically; malformed keys are
	 * dropped too, so corrupt data cannot accumulate.
	 *
	 * @param array $counts Per-day counts keyed by `Y-m-d`.
	 * @return array
	 */
	private static function prune( array $counts ) {
		$cutoff = gmdate( 'Y-m-d', time() - self::DAYS_TO_KEEP * DAY_IN_SECONDS );

		foreach ( array_keys( $counts ) as $day ) {
			if ( ! is_string( $day ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) || $day < $cutoff ) {
				unset( $counts[ $day ] );
			}
		}

		return $counts;
	}
}
