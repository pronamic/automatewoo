<?php

namespace AutomateWoo;

/**
 * @var Tool_Abstract[]                    $tools
 * @var Admin\Controllers\Tools_Controller $controller
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap woocommerce automatewoo-page automatewoo-page--tools">

	<h1><?php echo wp_kses_post( $controller->get_heading() ); ?></h1>

	<?php $controller->output_messages(); ?>

	<div id="poststuff">
		<table class="aw_tools_table wc_status_table widefat" cellspacing="0"><tbody>

			<?php foreach ( $tools as $tool ) : ?>
				<tr>
					<td class="">
						<a href="<?php echo esc_url( $controller->get_route_url( 'view', $tool ) ); ?>"><?php echo wp_kses_post( $tool->title ); ?></a>
					</td>

					<td class="">
						<span class="description"><?php echo wp_kses_post( $tool->description ); ?></span>
					</td>
				</tr>

			<?php endforeach; ?>

		</tbody></table>
	</div>

</div>


