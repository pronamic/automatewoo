<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var Admin\Controllers\Guests $controller
 * @var Guest                    $guest
 * @var Customer                 $customer
 */

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--guest-details">

	<h1><?php echo wp_kses_post( $controller->get_heading() ); ?></h1>

	<?php $controller->output_messages(); ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1"></div>

			<div id="postbox-container-2">

				<div class="postbox automatewoo-metabox no-drag">
					<div class="inside">

						<table class="automatewoo-table automatewoo-table--two-column">

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Guest ID', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col">#<?php echo wp_kses_post( $guest->get_id() ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Name', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( $customer->get_full_name() ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Email', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo make_clickable( $customer->get_email() ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Address', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( $customer->get_formatted_billing_address( false ) ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Phone', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( $customer->get_billing_phone() ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Last active', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( Format::datetime( $guest->get_date_last_active() ) ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Created', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( Format::datetime( $guest->get_date_created() ) ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Order count', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( $customer->get_order_count() ); ?></td>
							</tr>

							<tr>
								<td class="automatewoo-table__col automatewoo-table__col--label"><?php esc_html_e( 'Total spent', 'automatewoo' ); ?></td>
								<td class="automatewoo-table__col"><?php echo wp_kses_post( wc_price( $customer->get_total_spent() ) ); ?></td>
							</tr>

						</table>

					</div>

				</div>

			</div>

		</div>
	</div>

</div>
