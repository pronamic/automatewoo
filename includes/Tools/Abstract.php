<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Tool_Abstract
 * @since 2.4.5
 */
abstract class Tool_Abstract {

	/** @var string - this must directly correspond to the filename */
	public $id;

	/** @var string */
	public $title;

	/** @var string */
	public $description;

	/** @var string */
	public $additional_description;

	/** @var bool */
	public $is_background_processed = false;


	/**
	 * @return int
	 */
	function get_id() {
		return $this->id;
	}


	/**
	 * @param $args
	 * @return bool|\WP_Error
	 */
	abstract function process( $args );


	/**
	 * @param $args
	 */
	abstract function display_confirmation_screen( $args );


	/**
	 * @return Fields\Field[]
	 */
	function get_form_fields() {
		return [];
	}


	/**
	 * @param array $args will be already sanitized
	 * @return bool|\WP_Error
	 */
	function validate_process( $args ) {
		return true;
	}


	/**
	 * @param array $args
	 * @return array
	 */
	function sanitize_args( $args ) {
		if ( ! $args ) {
			return [];
		}

		if ( isset( $args['workflow'] ) ) {
			$args['workflow'] = absint( $args[ 'workflow' ] );
		}

		if ( isset( $args['date_from'] ) ) {
			$args['date_from'] = Clean::string( $args['date_from'] );
		}

		if ( isset( $args['date_to'] ) ) {
			$args['date_to'] = Clean::string( $args['date_to'] );
		}

		return $args;
	}


}
