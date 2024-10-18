<?php
namespace AutomateWoo;

/**
 * @class User_Tags
 * @since 2.9.10
 */
class User_Tags {

	/**
	 * List of added user taxonomies.
	 *
	 * @var \WP_Taxonomy[]
	 */
	private static $taxonomies = [];


	/**
	 * Default constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_init', [ $this, 'handle_export' ] );

		// Menus
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_filter( 'parent_file', [ $this, 'parent_menu' ] );

		// User Profiles
		add_action( 'show_user_profile', [ $this, 'user_profile' ] );
		add_action( 'edit_user_profile', [ $this, 'user_profile' ] );
		add_action( 'personal_options_update', [ $this, 'save_profile' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_profile' ] );

		// List table
		add_filter( 'manage_users_columns', [ $this, 'inject_column_header' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'inject_column_row' ], 10, 3 );
		add_action( 'pre_user_query', [ $this, 'filter_admin_query' ] );
		add_filter( 'views_users', [ $this, 'filter_user_views' ], 1, 1 );
		add_action( 'restrict_manage_users', [ $this, 'inject_bulk_actions' ], 1, 1 );
		add_action( 'admin_init', [ $this, 'catch_bulk_edit_action' ] );
	}


	/**
	 * Create the user tags taxonomy
	 */
	public function register_taxonomy() {
		$taxonomy = 'user_tag';

		self::$taxonomies[ $taxonomy ] = register_taxonomy(
			$taxonomy,
			'user',
			[
				'public'                => false,
				'show_ui'               => true,
				'labels'                => [
					'name'                       => __( 'Tags', 'automatewoo' ),
					'singular_name'              => __( 'Tag', 'automatewoo' ),
					'menu_name'                  => __( 'Tags', 'automatewoo' ),
					'search_items'               => __( 'Search Tags', 'automatewoo' ),
					'popular_items'              => __( 'Popular Tags', 'automatewoo' ),
					'all_items'                  => __( 'All Tags', 'automatewoo' ),
					'edit_item'                  => __( 'Edit Tag', 'automatewoo' ),
					'update_item'                => __( 'Update Tag', 'automatewoo' ),
					'add_new_item'               => __( 'Add New Tag', 'automatewoo' ),
					'new_item_name'              => __( 'New Tag Name', 'automatewoo' ),
					'separate_items_with_commas' => __( 'Separate Tags with commas', 'automatewoo' ),
					'add_or_remove_items'        => __( 'Add or remove Tags', 'automatewoo' ),
					'choose_from_most_used'      => __( 'Choose from the most popular tags', 'automatewoo' ),
				],
				'rewrite'               => false,
				'capabilities'          => [
					'manage_terms' => 'edit_users',
					'edit_terms'   => 'edit_users',
					'delete_terms' => 'edit_users',
					'assign_terms' => 'read',
				],
				'update_count_callback' => [ $this, 'update_count' ],
			]
		);

		// Register any hooks/filters for the user tags taxonomy.
		add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'set_user_column' ] );
		add_action( "manage_{$taxonomy}_custom_column", [ $this, 'set_user_column_values' ], 10, 3 );
	}


	/**
	 * Handle exporting a list of users by tag.
	 */
	public function handle_export() {
		if ( ! isset( $_REQUEST['eut_export_csv'] ) ) {
			return;
		}

		$nonce = Clean::string( aw_request( '_wpnonce' ) );
		if ( ! wp_verify_nonce( $nonce, 'eut_export_csv' ) || ! current_user_can( 'edit_users' ) ) {
			wp_die( esc_html__( 'Unable to export users by tag.', 'automatewoo' ) );
		}

		$exporter = new User_Tags_Export();
		$exporter->set_user_tag( absint( aw_request( 'user_tag' ) ) );
		$exporter->generate_csv();
	}


	/**
	 * We need to manually update the number of users for a taxonomy term
	 *
	 * @see    _update_post_term_count()
	 * @param array  $terms    List of Term taxonomy IDs.
	 * @param Object $taxonomy Current taxonomy object of terms.
	 */
	public function update_count( $terms, $taxonomy ) {
		global $wpdb;

		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), [ 'term_taxonomy_id' => $term ] );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}


	/**
	 * Add each of the taxonomies to the users menu
	 */
	public function admin_menu() {
		$taxonomies = self::$taxonomies;
		ksort( $taxonomies );

		foreach ( $taxonomies as $key => $taxonomy ) {
			add_users_page(
				$taxonomy->labels->menu_name,
				$taxonomy->labels->menu_name,
				$taxonomy->cap->manage_terms,
				"edit-tags.php?taxonomy={$key}"
			);
		}
	}

	/**
	 * Fix a bug with highlighting the parent menu item
	 * By default, when on the edit taxonomy page for a user taxonomy, the Posts tab is highlighted
	 * This will correct that bug
	 *
	 * @param string $parent Parent menu item.
	 */
	public function parent_menu( $parent = '' ) {
		global $pagenow;

		// If we're editing one of the user taxonomies
		// We must be within the users menu, so highlight that
		if (
			( $pagenow === 'edit-tags.php' || $pagenow === 'term.php' ) &&
			! empty( $_GET['taxonomy'] ) && 'user_tag' === $_GET['taxonomy'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			$parent = 'users.php';
		}

		return $parent;
	}

	/**
	 * Correct the column names for user taxonomies
	 * Need to replace "Posts" with "Users"
	 *
	 * @param array $columns
	 * @return array
	 */
	public function set_user_column( $columns ) {
		unset( $columns['posts'] );
		$columns['users'] = __( 'Users', 'automatewoo' );

		if ( current_user_can( 'edit_users' ) ) {
			$columns['export'] = __( 'Export', 'automatewoo' );
		}

		return $columns;
	}

	/**
	 * Set values for custom columns in user taxonomies
	 *
	 * @param string $display
	 * @param string $column Slug for user column.
	 * @param int    $term_id Tag ID for the current column/row.
	 */
	public function set_user_column_values( $display, $column, $term_id ) {
		if ( 'users' === $column && false !== aw_get_url_var( 'taxonomy' ) ) {
			$term = get_term( $term_id, aw_get_url_var( 'taxonomy' ) );
			echo '<a href="' . esc_url( admin_url( 'users.php?user_tag=' . $term->slug ) ) . '">' . esc_html( $term->count ) . '</a>';
		} elseif ( 'export' === $column ) {
			$url = wp_nonce_url(
				add_query_arg(
					[
						'eut_export_csv' => '1',
						'user_tag'       => $term_id,
					]
				),
				'eut_export_csv'
			);

			echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Export to CSV', 'automatewoo' ) . '</a>';
		} else {
			echo '-';
		}
	}

	/**
	 * Add the taxonomies to the user view/edit screen
	 *
	 * @param \WP_User $user
	 */
	public function user_profile( $user ) {

		// Using output buffering as we need to make sure we have something before outputting the header
		// But we can't rely on the number of taxonomies, as capabilities may vary
		ob_start();

		foreach ( self::$taxonomies as $taxonomy => $taxonomy_args ) :

			// Check the current user can assign terms for this taxonomy
			if ( ! current_user_can( $taxonomy_args->cap->assign_terms ) ) {
				continue;
			}

			// Get all the terms in this taxonomy
			$terms = $this->get_all_terms( $taxonomy );

			?>
			<table class="form-table">
				<tr>
					<th>
						<label for=""><?php /* translators: Taxonomy label. */ printf( esc_html__( 'Select %s', 'automatewoo' ), esc_html( $taxonomy_args->labels->name ) ); ?></label>
					</th>
					<td>
						<?php if ( ! empty( $terms ) ) : ?>
							<?php foreach ( $terms as $term ) : ?>
								<input type="checkbox" name="<?php echo esc_attr( $taxonomy ); ?>[]"
									id="<?php echo esc_attr( "{$taxonomy}-{$term->slug}" ); ?>"
									value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( true, is_object_in_term( $user->ID, $taxonomy, $term ) ); ?> />
								<label for="<?php echo esc_attr( "{$taxonomy}-{$term->slug}" ); ?>"><?php echo esc_html( $term->name ); ?></label>
								<br/>
							<?php endforeach; ?>
						<?php else : ?>
							<?php /* translators: Taxonomy label. */ printf( esc_html__( 'There are no %s available.', 'automatewoo' ), esc_html( $taxonomy_args->labels->name ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php
		endforeach;

		// Output the above if we have anything, with a heading
		$output = ob_get_clean();
		if ( ! empty( $output ) ) {
			echo '<h3>', esc_html__( 'Taxonomies', 'automatewoo' ), '</h3>';
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Get all the terms including empty for a specific taxonomy.
	 *
	 * @param string $taxonomy
	 * @return WP_Term[]
	 */
	protected function get_all_terms( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * Save the custom user taxonomies when saving a users profile
	 *
	 * @param Integer $user_id - The ID of the user to update
	 */
	public function save_profile( $user_id ) {

		foreach ( self::$taxonomies as $key => $taxonomy ) {

			// Check the current user can edit this user and assign terms for this taxonomy
			if ( ! current_user_can( 'edit_user', $user_id ) && current_user_can( $taxonomy->cap->assign_terms ) ) {
				continue;
			}

			$terms = aw_get_post_var( $key );
			if ( empty( $terms ) ) {
				$terms = [];
			}

			// Save the data
			$terms = array_map( 'sanitize_key', $terms );
			wp_set_object_terms( $user_id, $terms, $key, false );
			clean_object_term_cache( $user_id, $key );
		}
	}


	/**
	 * Add tags column to users page.
	 *
	 * @param array $columns List of columns
	 * @return array
	 */
	public function inject_column_header( $columns ) {
		$pos   = 5;
		$part1 = array_slice( $columns, 0, $pos );
		$part2 = array_slice( $columns, $pos );
		return array_merge( $part1, [ 'user_tag' => __( 'Tags', 'automatewoo' ) ], $part2 );
	}


	/**
	 * Add tag values to user page.
	 *
	 * @param string $content Cell content.
	 * @param string $column  Current column slug.
	 * @param int    $user_id User ID for the current row.
	 *
	 * @return string
	 */
	public function inject_column_row( $content, $column, $user_id ) {
		if ( $column !== 'user_tag' ) {
			return $content;
		}

		$tags = wp_get_object_terms( $user_id, $column );
		if ( ! $tags ) {
			return '<span class="na">&ndash;</span>';
		} else {
			$termlist = array();
			foreach ( $tags as $tag ) {
				$termlist[] = '<a href="' . esc_url( admin_url( 'users.php?user_tag=' . $tag->slug ) ) . ' ">' . esc_html( $tag->name ) . '</a>';
			}

			return implode( ', ', $termlist );
		}
	}


	/**
	 * Filter the users in admin based on a tag.
	 *
	 * @param mixed $query
	 */
	public function filter_admin_query( $query ) {
		global $wpdb, $pagenow;

		if ( ! is_admin() && $pagenow !== 'users.php' ) {
			return;
		}

		$tag_slug = sanitize_text_field( aw_get_url_var( 'user_tag' ) );
		if ( ! empty( $tag_slug ) ) {
			$query->query_from  .= " INNER JOIN {$wpdb->term_relationships} ON {$wpdb->users}.ID = {$wpdb->term_relationships}.object_id INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id INNER JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id";
			$query->query_where .= $wpdb->prepare( " AND {$wpdb->terms}.slug = %s", $tag_slug );
		}
	}


	/**
	 * Prevent "All" view from being selected when viewing a filtered user list.
	 *
	 * @param array $views
	 * @return array
	 */
	public function filter_user_views( $views ) {
		$tag_slug = sanitize_text_field( aw_get_url_var( 'user_tag' ) );

		if ( ! empty( $tag_slug ) ) {
			$views['all'] = str_replace( 'current', '', $views['all'] );
		}
		return $views;
	}


	/**
	 * Add bulk actions for assigning tags.
	 *
	 * @param string $which Bulk action location "top" | "bottom".
	 */
	public function inject_bulk_actions( $which ) {

		if ( $which !== 'top' || ! current_user_can( 'edit_users' ) ) {
			return;
		}

		?>
		<label class="screen-reader-text" for="add_user_tag"><?php esc_html_e( 'Add tag&hellip;', 'automatewoo' ); ?></label>
		<select name="add_user_tag" id="add_user_tag">
			<option value=""><?php esc_html_e( 'Add tag&hellip;', 'automatewoo' ); ?></option>
			<?php $this->wp_dropdown_user_tags(); ?>
		</select>

		<label class="screen-reader-text" for="remove_user_tag"><?php esc_html_e( 'Remove tag&hellip;', 'automatewoo' ); ?></label>
		<select name="remove_user_tag" id="remove_user_tag">
			<option value=""><?php esc_html_e( 'Remove tag&hellip;', 'automatewoo' ); ?></option>
			<?php $this->wp_dropdown_user_tags(); ?>
		</select>
		<?php

		wp_nonce_field( 'automatewoo-change-user-tags', '_awnonce' );

		if ( class_exists( 'Members_Plugin' ) ) { // fix for members v2.0
			submit_button( esc_html__( 'Change', 'automatewoo' ), 'secondary', 'automatewoo-change-user-tags', false );
		}
	}

	/**
	 * Print out option html elements for user tags.
	 */
	protected function wp_dropdown_user_tags() {
		$tags = $this->get_all_terms( 'user_tag' );

		foreach ( $tags as $tag ) {
			echo "\n\t" . '<option value="' . esc_attr( $tag->term_id ) . '">' . esc_html( $tag->name ) . '</option>';
		}
	}

	/**
	 * Handle bulk edit actions for user tags.
	 */
	public function catch_bulk_edit_action() {
		global $pagenow;

		if ( $pagenow !== 'users.php' ) {
			return;
		}

		// Output bulk messages after page redirect.
		if ( 'tags_updated' === aw_request( 'aw_message' ) ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html__( 'Tags updated.', 'automatewoo' ) . '</p></div>';
		}

		// Confirm we are handling one of the tag bulk actions.
		if ( empty( $_GET['users'] ) || ! current_user_can( 'edit_users' ) || ! isset( $_GET['_awnonce'] ) ) {
			return;
		}

		if ( empty( $_GET['remove_user_tag'] ) && empty( $_GET['add_user_tag'] ) ) {
			return;
		}

		$nonce       = Clean::string( aw_request( '_awnonce' ) );
		$valid_nonce = wp_verify_nonce( $nonce, 'automatewoo-change-user-tags' );

		$users = array_map( 'absint', $_GET['users'] );

		if ( ! empty( $_GET['add_user_tag'] ) && $valid_nonce ) {
			foreach ( $users as $user_id ) {
				wp_add_object_terms( $user_id, absint( $_GET['add_user_tag'] ), 'user_tag' );
			}
		}

		if ( ! empty( $_GET['remove_user_tag'] ) && $valid_nonce ) {
			foreach ( $users as $user_id ) {
				wp_remove_object_terms( $user_id, absint( $_GET['remove_user_tag'] ), 'user_tag' );
			}
		}

		// Redirect to prevent other actions such as change role being triggered.
		wp_safe_redirect( admin_url( 'users.php?aw_message=tags_updated' ) );
		exit();
	}
}
