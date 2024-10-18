<?php

namespace AutomateWoo\Rest_Api\Utilities;

/**
 * Controller_Namespace Trait.
 *
 * Used to provide the same namespace to all of our endpoints.
 *
 * @since 4.9.0
 */
trait Controller_Namespace {

	/**
	 * Controller namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructor for classes that utilize this trait.
	 */
	public function __construct() {
		$this->namespace = 'automatewoo';
	}
}
