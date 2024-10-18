<?php

namespace AutomateWoo;

defined( 'ABSPATH' ) || exit;

/**
 * @var string $date
 * @var bool $is_gmt
 * @var bool $shorten_month
 */

if ( ! isset( $is_gmt ) ) {
	$is_gmt = true;
}

if ( ! isset( $shorten_month ) ) {
	$shorten_month = false;
}

$with_diff = Format::datetime( $date, false, $is_gmt, $shorten_month );
$no_diff   = Format::datetime( $date, 0, $is_gmt, $shorten_month );
if ( ! $with_diff ) {
	echo '-';
	return;
}

if ( $with_diff === $no_diff ) {
	echo esc_attr( $with_diff );
	return;
}

?>
<div class="automatewoo-hoverable-date"
	data-automatewoo-date-with-diff="<?php echo esc_attr( $with_diff ); ?>"
	data-automatewoo-date-no-diff="<?php echo esc_attr( $no_diff ); ?>"
><?php echo esc_attr( $with_diff ); ?></div>
