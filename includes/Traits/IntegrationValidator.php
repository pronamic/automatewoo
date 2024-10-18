<?php

namespace AutomateWoo\Traits;

use AutomateWoo\Exceptions\InvalidIntegration;
use AutomateWoo\Integrations;

/**
 * Trait IntegrationValidator
 *
 * Validate that an integration is active and is a compatible version.
 *
 * @since 5.3.0
 */
trait IntegrationValidator {

	/**
	 * Validate WooCommerce Bookings integration.
	 *
	 * @throws InvalidIntegration If integration is inactive or incompatible.
	 */
	protected function validate_bookings_integration() {
		$name = 'WooCommerce Bookings';

		if ( ! defined( 'WC_BOOKINGS_VERSION' ) ) {
			throw InvalidIntegration::plugin_not_active( esc_html( $name ) );
		}
		if ( version_compare( WC_BOOKINGS_VERSION, Integrations::REQUIRED_BOOKINGS_VERSION, '<' ) ) {
			throw InvalidIntegration::plugin_version_not_supported( esc_html( $name ), esc_html( Integrations::REQUIRED_BOOKINGS_VERSION ) );
		}
	}
}
