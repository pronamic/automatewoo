<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\Addon;
use AutomateWoo\Addons;
use AutomateWoo\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class AddonWelcome
 *
 * @since 5.2.0
 */
class AddonWelcome extends AbstractAdminNotice {

	/**
	 * The name of the add-on.
	 *
	 * @var Addon
	 */
	protected $addon;

	/**
	 * AddonWelcome constructor.
	 *
	 * @param Addon $addon
	 */
	public function __construct( Addon $addon ) {
		$this->addon = $addon;
	}

	/**
	 * Get the unique notice ID.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'addon_welcome_' . $this->addon->id;
	}

	/**
	 * Output/render the notice HTML.
	 */
	public function output() {
		$message = '';

		$start_url = $this->addon->get_getting_started_url();

		if ( $start_url ) {
			$message .= sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
				'View the <%1$s>getting started guide<%2$s>.',
				'a href="' . esc_url( $start_url ) . '" target="_blank"',
				'/a'
			);
		}

		/* translators: Addon name. */
		$strong = sprintf( __( 'Welcome to the %s add-on!', 'automatewoo' ), $this->addon->name );

		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => $this->get_id(),
				'type'              => 'info',
				'class'             => 'is-dismissible',
				'strong'            => $strong,
				'message'           => $message,
			]
		);
	}
}
