<?php

namespace AutomateWoo\SystemChecks;

/**
 * Class AbstractSystemCheck
 *
 * @package AutomateWoo\SystemChecks
 */
abstract class AbstractSystemCheck {

	/** @var string */
	public $title;

	/** @var string */
	public $description;

	/** @var bool */
	public $high_priority = false;


	/**
	 * @return array
	 */
	abstract public function run();


	/**
	 * @param string $message
	 * @return array
	 */
	public function success( $message = '' ) {
		return [
			'success' => true,
			'message' => $message,
		];
	}


	/**
	 * @param string $message
	 * @return array
	 */
	public function error( $message = '' ) {
		return [
			'success' => false,
			'message' => $message,
		];
	}
}
