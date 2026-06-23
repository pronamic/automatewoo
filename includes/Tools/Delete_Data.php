<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * Tool to immediately and permanently delete all AutomateWoo data.
 *
 * Unlike the "Remove data on uninstall" setting (which only runs when the plugin
 * is deleted), this tool wipes the data on demand and shows the merchant exactly
 * what will be removed before they confirm. The empty tables are recreated so the
 * plugin keeps working with a clean slate.
 *
 * @class Tool_Delete_Data
 * @since 6.5.0
 */
class Tool_Delete_Data extends Tool_Abstract {

	/**
	 * Name of the arg holding the merchant's typed confirmation.
	 */
	const CONFIRMATION_ARG = 'confirmation';

	/**
	 * Exact text the merchant must type to confirm deletion.
	 */
	const CONFIRMATION_KEYWORD = 'DELETE';

	/**
	 * Tool ID. Must match the filename.
	 *
	 * @var string
	 */
	public $id = 'delete_data';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->title       = __( 'Delete all data', 'automatewoo' );
		$this->description = __( 'Permanently delete all AutomateWoo data, including workflows, logs, carts, customers, guests, queued events and settings. This cannot be undone.', 'automatewoo' );
	}

	/**
	 * Show the merchant exactly what will be deleted before they confirm.
	 *
	 * @param array $args Sanitized args.
	 * @return void
	 */
	public function display_confirmation_screen( $args ) {
		$summary = Data_Cleaner::get_summary();

		echo wp_kses_post(
			'<strong>' . esc_html__( 'This will permanently delete all AutomateWoo data. This cannot be undone.', 'automatewoo' ) . '</strong>'
		);

		if ( $summary ) {
			echo '<ul class="ul-disc">';
			foreach ( $summary as $type => $count ) {
				echo '<li>' . esc_html( self::format_summary_line( $type, $count ) ) . '</li>';
			}
			echo '</ul>';
		}

		printf(
			'<p><label>%s<br><input type="text" name="args[%s]" value="" autocomplete="off" autocapitalize="off" spellcheck="false" class="regular-text"></label></p>',
			esc_html__( 'Type DELETE to confirm', 'automatewoo' ),
			esc_attr( self::CONFIRMATION_ARG )
		);
	}

	/**
	 * Require the merchant to type the exact confirmation keyword.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	public function sanitize_args( $args ) {
		$args = parent::sanitize_args( $args );

		if ( isset( $args[ self::CONFIRMATION_ARG ] ) ) {
			$args[ self::CONFIRMATION_ARG ] = Clean::string( $args[ self::CONFIRMATION_ARG ] );
		}

		return $args;
	}

	/**
	 * Delete all data, then recreate the empty tables so the plugin stays functional.
	 *
	 * @param array $args Args.
	 * @return bool|\WP_Error
	 */
	public function process( $args ) {
		$args = $this->sanitize_args( $args );

		// Strict gate: the merchant must type the keyword exactly (case-sensitive).
		if ( ! isset( $args[ self::CONFIRMATION_ARG ] ) || self::CONFIRMATION_KEYWORD !== $args[ self::CONFIRMATION_ARG ] ) {
			return new \WP_Error(
				'confirmation_required',
				sprintf(
					/* translators: %s: the word DELETE. */
					__( 'Please type %s exactly to confirm. No data was deleted.', 'automatewoo' ),
					self::CONFIRMATION_KEYWORD
				)
			);
		}

		Data_Cleaner::delete_all();

		// Restore a clean, empty baseline so the still-active plugin keeps working.
		Database_Tables::install_tables();

		return true;
	}

	/**
	 * Format a single summary line with the correct singular/plural label.
	 *
	 * @param string $type  Type key from Data_Cleaner::get_summary().
	 * @param int    $count Row count.
	 * @return string
	 */
	private static function format_summary_line( $type, $count ) {
		switch ( $type ) {
			case 'workflows':
				/* translators: %s: number of workflows. */
				return sprintf( _n( '%s workflow', '%s workflows', $count, 'automatewoo' ), number_format_i18n( $count ) );
			case 'logs':
				/* translators: %s: number of log entries. */
				return sprintf( _n( '%s log entry', '%s log entries', $count, 'automatewoo' ), number_format_i18n( $count ) );
			case 'queue':
				/* translators: %s: number of queued events. */
				return sprintf( _n( '%s queued event', '%s queued events', $count, 'automatewoo' ), number_format_i18n( $count ) );
			case 'carts':
				/* translators: %s: number of tracked carts. */
				return sprintf( _n( '%s tracked cart', '%s tracked carts', $count, 'automatewoo' ), number_format_i18n( $count ) );
			case 'customers':
				/* translators: %s: number of customer records. */
				return sprintf( _n( '%s customer record', '%s customer records', $count, 'automatewoo' ), number_format_i18n( $count ) );
			case 'guests':
				/* translators: %s: number of guest records. */
				return sprintf( _n( '%s guest record', '%s guest records', $count, 'automatewoo' ), number_format_i18n( $count ) );
			default:
				return number_format_i18n( $count );
		}
	}
}
