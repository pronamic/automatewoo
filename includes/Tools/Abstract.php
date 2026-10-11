<?php

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
	public function get_id() {
		return $this->id;
	}


	/**
	 * @param array $args
	 * @return bool|\WP_Error
	 */
	abstract public function process( $args );


	/**
	 * Message the tools page shows after process() returns true.
	 *
	 * The default keeps the historic wording, which hedges about background
	 * processing. A tool that finishes synchronously can override this with an
	 * accurate message, or return an empty string when process() queues its
	 * own messages.
	 *
	 * @since 6.9.1
	 *
	 * @return string
	 */
	public function get_success_message() {
		return __( 'Success - Items may be still be processing in the background.', 'automatewoo' );
	}


	/**
	 * @param array $args
	 */
	abstract public function display_confirmation_screen( $args );


	/**
	 * Optionally output a legend in the confirmation screen footer.
	 */
	public function display_confirmation_legend() {}


	/**
	 * @return Fields\Field[]
	 */
	public function get_form_fields() {
		return [];
	}


	/**
	 * @param array $args will be already sanitized
	 * @return bool|\WP_Error
	 */
	public function validate_process( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Part of the overridable signature; subclasses use $args.
		return true;
	}


	/**
	 * @param array $args
	 * @return array
	 */
	public function sanitize_args( $args ) {
		if ( ! $args ) {
			return [];
		}

		if ( isset( $args['workflow'] ) ) {
			$args['workflow'] = absint( $args['workflow'] );
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
