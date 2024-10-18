<?php

namespace AutomateWoo\Rest_Api\Schema;

/**
 * Context interface.
 *
 * Provides constants for REST API contexts.
 *
 * @since 4.9.0
 *
 * @package AutomateWoo\Rest_Api\Schema
 */
interface Context {

	const VIEW      = 'view';
	const EDIT      = 'edit';
	const ALL       = [ self::VIEW, self::EDIT ];
	const VIEW_ONLY = [ self::VIEW ];
	const EDIT_ONLY = [ self::EDIT ];
}
