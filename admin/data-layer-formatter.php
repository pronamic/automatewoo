<?php

namespace AutomateWoo;

/**
 * @class Admin_Data_Layer_Formatter
 */
class Admin_Data_Layer_Formatter {


	/**
	 * @param Data_Layer $data_layer
	 * @return array
	 */
	public static function format( $data_layer ) {

		$data           = $data_layer->get_raw_data();
		$formatted_data = [];

		foreach ( $data as $data_type => $data_item ) {

			if ( ! $data_item ) {
				continue;
			}

			switch ( $data_type ) {

				case 'order':
					if ( ! $data_item->get_id() ) {
						break;
					}
					/** @var \WC_Order $data_item */
					$formatted_data[] = [
						'title' => __( 'Order', 'automatewoo' ),
						'value' => Format::html_id_link(
							$data_item->get_edit_order_url(),
							$data_item->get_id()
						),
					];
					break;

				case 'customer':
					$formatted_data[] = [
						'title' => __( 'Customer', 'automatewoo' ),
						'value' => Format::customer( $data_item ),
					];
					break;

				case 'guest':
					/** @var $data_item Guest */
					$formatted_data[] = [
						'title' => __( 'Guest', 'automatewoo' ),
						'value' => Format::email( $data_item->get_email() ),
					];
					break;

				case 'cart':
					/** @var $data_item Cart */
					$formatted_data[] = [
						'title' => __( 'Cart', 'automatewoo' ),
						'value' => '#' . $data_item->get_id(),
					];
					break;

				case 'review':
					/** @var $data_item Review */
					$formatted_data[] = [
						'title' => __( 'Review', 'automatewoo' ),
						'value' => Format::html_id_link(
							get_edit_comment_link( $data_item->get_id() ),
							$data_item->get_id()
						),
					];
					break;

				case 'product':
					/** @var $data_item \WC_Product */
					$formatted_data[] = [
						'title' => __( 'Product', 'automatewoo' ),
						'value' => Format::html_link(
							get_edit_post_link( $data_item->get_id() ),
							$data_item->get_title()
						),
					];
					break;

				case 'subscription':
					/** @var $data_item \WC_Subscription */
					$formatted_data[] = [
						'title' => __( 'Subscription', 'automatewoo' ),
						'value' => Format::html_id_link(
							$data_item->get_edit_order_url(),
							$data_item->get_id()
						),
					];
					break;

				case 'membership':
					/** @var $data_item \WC_Memberships_User_Membership */
					$formatted_data[] = [
						'title' => __( 'Membership', 'automatewoo' ),
						'value' => Format::html_id_link(
							get_edit_post_link( $data_item->id ),
							$data_item->id
						),
					];
					break;

				case 'wishlist':
					$formatted_data[] = [
						'title' => __( 'Wishlist', 'automatewoo' ),
						'value' => '#' . $data_item->id,
					];
					break;

				case 'course':
					/** @var $data_item \WP_Post */
					$link             = get_edit_post_link( $data_item->ID );
					$formatted_data[] = [
						'title' => __( 'Course', 'automatewoo' ),
						'value' => '<a href="' . esc_url( $link ) . '">' . esc_html( $data_item->post_title ) . '</a>',
					];
					break;

				case 'lesson':
					/** @var $data_item \WP_Post */
					$link             = get_edit_post_link( $data_item->ID );
					$formatted_data[] = [
						'title' => __( 'Lesson', 'automatewoo' ),
						'value' => '<a href="' . esc_url( $link ) . '">' . esc_html( $data_item->post_title ) . '</a>',
					];
					break;

				case 'group':
					/** @var $data_item \WP_Post */
					$link             = add_query_arg(
						array(
							'view'     => 'group_students',
							'page'     => 'student_groups',
							'group_id' => $data_item->ID,
						),
						admin_url( 'admin.php' )
					);
					$formatted_data[] = [
						'title' => __( 'Group', 'automatewoo' ),
						'value' => '<a href="' . esc_url( $link ) . '">' . esc_html( $data_item->post_title ) . '</a>',
					];
					break;

				case 'teacher':
					/** @var $data_item \WP_User */
					$link             = get_edit_user_link( $data_item->ID );
					$formatted_data[] = [
						'title' => __( 'Teacher', 'automatewoo' ),
						'value' => '<a href="' . esc_url( $link ) . '">' . esc_html( aw_get_full_name( $data_item ) ) . '</a>' . Format::email( $data_item->user_email ),
					];
					break;
			}
		}

		return apply_filters( 'automatewoo/formatted_data_layer', $formatted_data, $data_layer );
	}
}
