<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the rate limiter details to the WooCommerce system status report.
 *
 * @since 6.9.3
 * @internal
 */
class System_Status_Report {

	/**
	 * Output the AutomateWoo table.
	 */
	public static function output() {
		$ip   = Rate_Limiter::get_client_ip();
		$last = get_option( Rate_Limiter::LAST_THROTTLED_OPTION );

		$filter_state = has_filter( 'automatewoo/rate_limiter/client_ip' )
			? __( 'Filter in use', 'automatewoo' )
			: __( 'Filter not in use', 'automatewoo' );

		if ( is_array( $last ) && ! empty( $last['time'] ) ) {
			$last_text = sprintf(
				/* translators: 1: bucket name, 2: scope, 3: human time difference, e.g. "3 hours ago". */
				__( '%1$s (%2$s), %3$s', 'automatewoo' ),
				(string) ( $last['bucket'] ?? '' ),
				(string) ( $last['scope'] ?? '' ),
				sprintf(
					/* translators: %s: human time difference, e.g. "3 hours". */
					__( '%s ago', 'automatewoo' ),
					human_time_diff( (int) $last['time'] )
				)
			);
		} else {
			$last_text = __( 'None', 'automatewoo' );
		}

		$rows = [
			[
				__( 'Rate limit client IP', 'automatewoo' ),
				__( 'The address AutomateWoo uses to rate limit sign-ups and email capture. Stores behind a CDN or proxy may need the automatewoo/rate_limiter/client_ip filter, or all visitors share one limit.', 'automatewoo' ),
				( '' !== $ip ? $ip : __( 'Unknown', 'automatewoo' ) ) . ' – ' . $filter_state,
			],
			[
				__( 'Last rate limit', 'automatewoo' ),
				__( 'The most recent time a request was refused by a rate limit. If this appears often for real shoppers, the client IP may be wrong. Stores behind a CDN or proxy may need the client IP filter.', 'automatewoo' ),
				$last_text,
			],
		];
		?>
		<table class="wc_status_table widefat" cellspacing="0">
			<thead>
				<tr><th colspan="3" data-export-label="AutomateWoo"><h2><?php esc_html_e( 'AutomateWoo', 'automatewoo' ); ?></h2></th></tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td data-export-label="<?php echo esc_attr( $row[0] ); ?>"><?php echo esc_html( $row[0] ); ?></td>
						<td class="help"><?php Admin::help_tip( $row[1] ); ?></td>
						<td><?php echo esc_html( $row[2] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
