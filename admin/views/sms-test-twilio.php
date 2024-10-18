<?php
/**
 * Twilio SMS Test Form
 */

defined( 'ABSPATH' ) || exit;

?>

	<div class="automatewoo-sms-test-container">

		<h3><?php esc_html_e( 'Send Test SMS', 'automatewoo' ); ?></h3>

		<table class="form-table">
			<tbody>

			<tr valign="top">

				<th scope="row" class="titledesc">
					<label><?php esc_html_e( 'Recipient', 'automatewoo' ); ?></label>
				</th>

				<td class="forminp">

					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Recipient', 'automatewoo' ); ?></span></legend>
						<input class="input-text" type="text" id="automatewoo-sms-test-recipient" value="" style="min-width:300px;">
					</fieldset>
				</td>
			</tr>

			<tr>

				<th scope="row" class="titledesc">
					<label><?php esc_html_e( 'Message', 'automatewoo' ); ?></label>
				</th>

				<td class="forminp">

					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Message', 'automatewoo' ); ?></span></legend>
						<textarea class="input-text regular-input" id="automatewoo-sms-test-message" style="min-width:300px; height: 75px;">Hello World!</textarea>
					</fieldset>
				</td>
			</tr>


			<tr valign="top">

				<th scope="row">
					<fieldset>
						<input id="automatewoo-sms-test-twilio"
							class="button-primary" type="button"
							data-loading-text="<?php esc_html_e( 'Sending...', 'automatewoo' ); ?>"
							value="<?php esc_html_e( 'Send', 'automatewoo' ); ?>"
						>
					</fieldset>
				</th>

				<td></td>
			</tr>

			</tbody>
		</table>


	</div>


