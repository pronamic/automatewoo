<?php
namespace AutomateWoo;

/**
 * Export user tags to CSV file.
 */
class User_Tags_Export {

	/**
	 * @var \stdClass|\WP_Term
	 */
	public $tag;


	/**
	 * @param int $tag_id
	 */
	public function set_user_tag( $tag_id ) {
		$this->tag = get_term( $tag_id, 'user_tag' );
	}


	/**
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function get_users( $limit, $offset = 0 ) {
		$users    = [];
		$user_ids = [];

		if ( $this->tag ) {
			$user_ids = get_objects_in_term( $this->tag->term_id, 'user_tag' );
		}

		$user_ids = array_slice( $user_ids, $offset, $limit );

		foreach ( $user_ids as $id ) {
			$users[] = get_user_by( 'id', $id );
		}

		return $users;
	}


	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function generate_csv() {

		$limit  = empty( $_GET['limit'] ) ? null : absint( $_GET['limit'] );
		$offset = empty( $_GET['offset'] ) ? 0 : absint( $_GET['offset'] );

		$users = $this->get_users( $limit, $offset );

		if ( ! wp_verify_nonce( sanitize_key( aw_get_url_var( '_wpnonce' ) ), 'eut_export_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'automatewoo' ) );
		}

		if ( empty( $users ) ) {
			wp_die( esc_html__( 'There are no users with that tag.', 'automatewoo' ) );
		}

		$sitename = sanitize_file_name( get_bloginfo( 'name' ) );
		$tag_name = sanitize_file_name( $this->tag->name );

		$filename = $sitename . '-' . $tag_name . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		wp_raise_memory_limit( 'admin' );
		@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );

		$data_keys = [
			'ID',
			'user_login',
			'user_nicename',
			'user_email',
			'user_registered',
			'display_name',
		];

		$fields = array_merge( $data_keys );

		$headers = array();
		foreach ( $fields as $key => $field ) {
			$headers[] = '"' . strtolower( $field ) . '"';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( ',', $headers ) . "\n";

		foreach ( $users as $user ) {
			if ( ! $user ) {
				continue;
			}

			$data = [];

			foreach ( $fields as $field ) {
				$value  = isset( $user->{$field} ) ? $user->{$field} : '';
				$value  = is_array( $value ) ? serialize( $value ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				$data[] = '"' . str_replace( '"', '""', $value ) . '"';
			}

			echo implode( ',', $data ) . "\n";
		}
		// phpcs:enable

		exit;
	}
}
