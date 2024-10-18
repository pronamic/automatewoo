<?php

namespace AutomateWoo;

/**
 * Class Abstract_Model_With_Meta_Table
 *
 * @since 4.6.0
 * @package AutomateWoo
 */
abstract class Abstract_Model_With_Meta_Table extends Model {

	/**
	 * Stores meta data changes for this object.
	 *
	 * @var array
	 */
	protected $meta_data_changes = [];

	/**
	 * Returns the ID of the model's meta table.
	 *
	 * @return string
	 */
	abstract public function get_meta_table_id();

	/**
	 * Get the table object used for meta data.
	 *
	 * @return Database_Table|false
	 */
	public function get_meta_table() {
		return Database_Tables::get( $this->get_meta_table_id() );
	}

	/**
	 * Get the meta data table name.
	 *
	 * @return string
	 */
	public function get_meta_table_name() {
		return $this->get_meta_table()->get_name();
	}

	/**
	 * Get the meta data object ID column. E.g. 'event_id'
	 *
	 * @return string
	 */
	public function get_meta_object_id_column() {
		return $this->get_meta_table()->get_object_id_column();
	}

	/**
	 * Get object's, yet to be applied, meta data changes.
	 *
	 * @return array
	 */
	public function get_meta_data_changes() {
		return $this->meta_data_changes;
	}

	/**
	 * Get a single meta value by key.
	 *
	 * Returns an empty string if field is empty or doesn't exist.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get_meta( $key ) {
		if ( ! $this->get_meta_table() ) {
			return '';
		}

		// Check unapplied changes first
		if ( array_key_exists( $key, $this->meta_data_changes ) ) {
			return $this->meta_data_changes[ $key ];
		}

		// Before returning from cache or db the object must exist
		if ( ! $this->exists ) {
			return '';
		}

		$cache_key   = $this->get_meta_cache_key( $key );
		$cache_group = $this->get_meta_cache_group();

		// Check cache
		if ( Cache::exists( $cache_key, $cache_group ) ) {
			$cached = Cache::get( $cache_key, $cache_group );
			return maybe_unserialize( $cached );
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_value FROM {$this->get_meta_table_name()} WHERE {$this->get_meta_object_id_column()} = %d AND meta_key = %s",
				$this->get_id(),
				$key
			),
			ARRAY_A
		);
		// phpcs:enable

		$value = $row ? $row['meta_value'] : '';

		Cache::set( $cache_key, $value, $cache_group );

		return maybe_unserialize( $value );
	}

	/**
	 * Updates a single meta data prop.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function update_meta( $key, $value ) {
		if ( ! $key ) {
			return;
		}

		$this->meta_data_changes[ $key ] = $value;

		// Meta data was immediately saved before v4.6
		// so save now for backwards compatibility
		$this->save_meta_data();
	}

	/**
	 * Deletes meta data by meta key.
	 *
	 * @since 4.0
	 *
	 * @param string $key
	 */
	public function delete_meta( $key ) {
		if ( ! $key ) {
			return;
		}

		$this->meta_data_changes[ $key ] = '';

		// Meta data was immediately saved before v4.6
		// so save now for backwards compatibility
		$this->save_meta_data();
	}

	/**
	 * Determine if specific meta field is set in the DB.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	private function meta_data_exists( $key ) {
		if ( ! $this->exists || ! $key ) {
			return false;
		}

		// Check if meta key exists in the cache, if not query the db.
		if ( Cache::exists( $this->get_meta_cache_key( $key ), $this->get_meta_cache_group() ) ) {
			$cached = Cache::get( $this->get_meta_cache_key( $key ), $this->get_meta_cache_group() );
			// If cached value is '' the meta data is actually deleted.
			return '' !== $cached;
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_row(
			$wpdb->prepare(
				"SELECT meta_id FROM {$this->get_meta_table_name()} WHERE {$this->get_meta_object_id_column()} = %d AND meta_key = %s",
				$this->get_id(),
				$key
			)
		);
		// phpcs:enable
	}

	/**
	 * Save object meta data.
	 *
	 * Applies meta data changes found in self::$meta_data_changes.
	 */
	public function save_meta_data() {
		if ( ! $this->exists || empty( $this->meta_data_changes ) ) {
			// The object must be saved before adding meta.
			return;
		}

		global $wpdb;

		foreach ( $this->meta_data_changes as $key => $value ) {
			$value = $this->prepare_meta_value_for_db( $value );

			if ( $value === '' ) {
				// Delete blank meta values
				if ( $this->meta_data_exists( $key ) ) {
					$wpdb->delete(
						$this->get_meta_table_name(),
						[
							$this->get_meta_object_id_column() => $this->get_id(),
							'meta_key' => $key,
						],
						[ '%d', '%s' ]
					);
				}
			} elseif ( $this->meta_data_exists( $key ) ) {
				// Update or insert the meta data
				$wpdb->update(
					$this->get_meta_table_name(),
					[ 'meta_value' => $value ],
					[
						$this->get_meta_object_id_column() => $this->get_id(),
						'meta_key'                         => $key,
					],
					[ '%s' ],
					[ '%d', '%s' ]
				);
			} else {
				$wpdb->insert(
					$this->get_meta_table_name(),
					[
						$this->get_meta_object_id_column() => $this->get_id(),
						'meta_key'                         => $key,
						'meta_value'                       => $value,
					],
					[ '%d', '%s', '%s' ]
				);
			}

			Cache::set( $this->get_meta_cache_key( $key ), $value, $this->get_meta_cache_group() );
		}

		$this->meta_data_changes = [];
	}

	/**
	 * Prepare meta value to be saved in database.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function prepare_meta_value_for_db( $value ) {
		$value = maybe_serialize( $value );

		if ( $value === false ) {
			$value = 0;
		}

		return $value;
	}

	/**
	 * Save the object including meta data.
	 */
	public function save() {
		parent::save();
		$this->save_meta_data();
	}

	/**
	 * Delete the object.
	 */
	public function delete() {
		global $wpdb;

		if ( ! $this->exists ) {
			return;
		}

		// Clear object meta data
		// Object meta values will still exist in cache but get_meta() checks if the object exists
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->get_meta_table_name()} WHERE {$this->get_meta_object_id_column()} = %d",
				$this->get_id()
			)
		);
		// phpcs:enable

		parent::delete();
	}

	/**
	 * Fill model with data.
	 *
	 * @param array $row
	 */
	public function fill( $row ) {
		if ( ! is_array( $row ) ) {
			return;
		}

		// remove meta columns
		unset( $row['meta_key'] );
		unset( $row['meta_value'] );
		unset( $row['meta_id'] );
		unset( $row[ $this->get_meta_object_id_column() ] );

		parent::fill( $row );
	}

	/**
	 * Get cache key for a specified meta key.
	 *
	 * @since 4.6.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function get_meta_cache_key( $key ) {
		return $this->get_id() . '_' . $key;
	}

	/**
	 * Get cache group for meta values.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	protected function get_meta_cache_group() {
		return $this->object_type . '_meta';
	}

	/**
	 * Add meta to object
	 *
	 * Alias for self::update_meta()
	 *
	 * @deprecated
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function add_meta( $key, $value ) {
		wc_deprecated_function( __METHOD__, '5.2.0', 'update_meta' );

		$this->update_meta( $key, $value );
	}
}
