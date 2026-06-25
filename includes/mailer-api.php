<?php
/**
 * Static mailer API.
 *
 * Used to display dynamic AW content in email template files.
 *
 * @class AW_Mailer_API
 */
class AW_Mailer_API {

	/** @var AutomateWoo\Mailer */
	public static $mailer;

	/** @var AutomateWoo\Workflow*/
	public static $workflow;


	/**
	 * @param AutomateWoo\Mailer   $mailer
	 * @param AutomateWoo\Workflow $workflow
	 */
	public static function setup( $mailer, $workflow ) {
		self::$mailer   = $mailer;
		self::$workflow = $workflow;
	}


	/**
	 * Reset mailer and workflow.
	 */
	public static function cleanup() {
		self::$mailer   = false;
		self::$workflow = false;
	}


	/**
	 * @return bool|string
	 */
	public static function email() {
		if ( ! self::$mailer ) {
			return false;
		}
		return self::$mailer->email;
	}


	/**
	 * @return bool|string
	 */
	public static function subject() {
		if ( ! self::$mailer ) {
			return false;
		}
		return self::$mailer->subject;
	}


	/**
	 * @return bool|string
	 */
	public static function unsubscribe_url() {
		if ( ! self::$workflow ) {
			return false;
		}
		$customer = AutomateWoo\Customer_Factory::get_by_email( self::email() );
		return self::$workflow->get_unsubscribe_url( $customer );
	}


	/**
	 * $variable parameter doesn't need curly braces. E.g.
	 *
	 * "customer.email"
	 * "order.items | template: 'order-table'"
	 *
	 * @since 3.9
	 *
	 * @param string $variable
	 * @return bool|string
	 */
	public static function variable( $variable ) {
		if ( ! self::$workflow ) {
			return self::$workflow->process_variable( $variable );
		}
		return false;
	}


	/**
	 * @param WC_Product $product
	 * @param string     $size
	 * @return array|false|string
	 */
	public static function get_product_image( $product, $size = 'woocommerce_thumbnail' ) {

		$image_id = $product->get_image_id();

		if ( $image_id ) {
			$image_data = wp_get_attachment_image_src( $image_id, $size );

			if ( ! $image_data || empty( $image_data[0] ) ) {
				return apply_filters( 'automatewoo/email/product_placeholder_image', wc_placeholder_img( $size ), $size, $product );
			}

			$width  = isset( $image_data[1] ) ? absint( $image_data[1] ) : 0;
			$height = isset( $image_data[2] ) ? absint( $image_data[2] ) : 0;

			// Only output dimension attributes when they are known. Some image providers
			// (e.g. media offload or SVG plugins) return a URL without usable dimensions,
			// and emitting width="0"/height="0" would hide the image.
			$dimensions = ( $width && $height )
				? sprintf( ' width="%1$d" height="%2$d"', $width, $height )
				: '';

			$image = sprintf(
				'<img src="%1$s"%2$s class="aw-product-image" style="max-width: 100%%; height: auto;" alt="%3$s">',
				esc_url( $image_data[0] ),
				$dimensions,
				esc_attr( $product->get_name() )
			);

			return apply_filters( 'automatewoo/email/product_image', $image, $size, $product );
		} else {
			return apply_filters( 'automatewoo/email/product_placeholder_image', wc_placeholder_img( $size ), $size, $product );
		}
	}
}
