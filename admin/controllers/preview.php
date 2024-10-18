<?php
// phpcs:ignoreFile

namespace AutomateWoo\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) exit;

use AutomateWoo\Admin;
use AutomateWoo\Clean;
use AutomateWoo\Exceptions\InvalidPreviewData;
use AutomateWoo\Preview_Data;

/**
 * @class Preview
 */
class Preview extends Base {


	function handle() {
		switch( $this->get_current_action() ) {
			case 'loading':
				$this->output_loader();
				break;
			case 'preview-ui':
				$this->output_preview_ui();
				break;
		}
	}


	function output_loader() {
		Admin::get_view( 'email-preview-loader' );
	}


	function output_preview_ui() {

		$type = Clean::string( aw_request('type') );
		$args = Clean::recursive( aw_request('args') );

		$iframe_url = add_query_arg([
			'action' => 'aw_email_preview_iframe',
			'type' => $type,
			'args' => $args
		], admin_url( 'admin-ajax.php' ) );


		switch ( $type ) {
			case 'workflow_action':
				try {
					$action = Preview_Data::generate_preview_action( $args['workflow_id'], $args['action_number'] );
				} catch ( InvalidPreviewData $e ) {
					return wp_die( $e->getMessage() );
				}

				$email_subject = $action->get_option('subject', true );
				$template = $action->get_option( 'template' );
				break;

			default:
				$email_subject = '';
				$template = '';
		}

		$email_subject = apply_filters( 'automatewoo/email_preview/subject', $email_subject, $type, $args );
		$template = apply_filters( 'automatewoo/email_preview/template', $template, $type, $args );

		Admin::get_view('email-preview-ui', [
			'iframe_url' => $iframe_url,
			'type' => $type,
			'args' => $args,
			'email_subject' => $email_subject,
			'template' => $template
		]);

	}


}

return new Preview();
