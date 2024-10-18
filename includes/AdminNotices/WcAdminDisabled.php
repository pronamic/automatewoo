<?php

namespace AutomateWoo\AdminNotices;

use AutomateWoo\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class WcAdminDisabled
 *
 * @since 5.1.3
 */
class WcAdminDisabled extends AbstractAdminNotice {

	/**
	 * Get the unique notice ID.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'wc_admin_disabled';
	}

	/**
	 * Init the notice, add hooks.
	 */
	public function init() {
		if ( false === apply_filters( 'woocommerce_admin_disabled', false ) ) {
			// WC Admin is not disabled, do not init the notice
			return;
		}

		// Add render hook
		parent::init();

		$transient = 'automatewoo_wc_admin_disabled_notice_added';

		if ( get_transient( $transient ) ) {
			// Notice was added in last 6 months
			return;
		}

		$this->add_notice();
		set_transient( $transient, true, MONTH_IN_SECONDS * 6 );
	}

	/**
	 * Output/render the notice HTML.
	 */
	public function output() {
		Admin::get_view(
			'simple-notice',
			[
				'notice_identifier' => $this->get_id(),
				'type'              => 'warning',
				'class'             => 'is-dismissible',
				'strong'            => __( 'Some AutomateWoo features require WooCommerce Admin.', 'automatewoo' ),
				'message'           => __( 'Admin features of AutomateWoo like presets and manual workflows require WooCommerce Admin which is disabled on your site.', 'automatewoo' ),
			]
		);
	}
}
