<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

$all_checks_success = true

?>

<table class="aw_system_check_table wc_status_table widefat" cellspacing="0"><tbody>

	<?php foreach ( System_Checks::get_all() as $check ) : ?>

		<?php
		$response = $check->run();

		if ( $response['success'] === false ) {
			$all_checks_success = false;
		}
		?>

		<tr>
			<td class="">
				<?php echo wp_kses_post( $check->title ); ?>
			</td>


			<td class="help">
				<?php Admin::help_tip( $check->description ); ?>
			</td>

			<td class="">

				<?php if ( $response['success'] === true ) : ?>
					<mark class="yes">&#10004; <?php echo wp_kses_post( $response['message'] ); ?></mark>
				<?php elseif ( $response['success'] === false ) : ?>
					<mark class="error">&#10005; <?php echo wp_kses_post( $response['message'] ); ?></mark>
				<?php else : ?>

				<?php endif; ?>

			</td>

		</tr>

	<?php endforeach; ?>

</tbody></table>

<?php

if ( $all_checks_success ) {
	delete_transient( 'automatewoo_background_system_check_errors' );
}
