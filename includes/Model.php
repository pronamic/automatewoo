<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Model
 *
 * @property $id
 */
abstract class Model {

	/** @var string : required for every model and must have a corresponding Database_Table */
	public $table_id;

	/** @var bool */
	public $exists = false;

	/** @var array */
	public $data = [];

	/** @var array - data as it last existed in the database */
	public $original_data = [];

	/** @var array */
	public $changed_fields = [];

	/** @var string */
	public $object_type;


	/**
	 * @return int
	 */
	function get_id() {
		return $this->id ? (int) $this->id : 0;
	}


	/**
	 * @param int $id
	 */
	function set_id( $id ) {
		$this->id = $id;
	}


	/**
	 * Fill model with data
	 *
	 * @param array $row
	 */
	function fill( $row ) {
		if ( ! is_array( $row ) ) {
			return;
		}

		$this->data = $row;
		$this->original_data = $row;
		$this->exists = true;

		do_action( 'automatewoo/object/load', $this );
	}


	/**
	 * @param $value string|int
	 * @param $field string
	 */
	function get_by( $field, $value ) {

		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "
				SELECT * FROM {$this->get_table_name()}
		 		WHERE $field = %s
			", $value
			), ARRAY_A
		);

		if ( ! $row )
			return;

		$this->fill( $row );
	}


	/**
	 * Magic method for accessing db fields
	 *
	 * @param string $key
	 * @return mixed
	 */
	function __get( $key ) {
		return $this->get_prop( $key );
	}


	/**
	 * Magic method for setting db fields
	 *
	 * @param $key
	 * @param $value
	 */
	function __set( $key, $value ) {
		$this->set_prop( $key, $value );
	}


	/**
	 * @param $key
	 * @param $value
	 */
	function set_prop( $key, $value ) {

		if ( is_array( $value ) && ! $value ) {
			$value = ''; // convert empty arrays to blank
		}

		$this->data[$key] = $value;
		$this->changed_fields[] = $key;
	}


	/**
	 * @param $key
	 * @return bool
	 */
	function has_prop( $key ) {
		return isset( $this->data[$key] );
	}


	/**
	 * @param $key
	 * @return mixed
	 */
	function get_prop( $key ) {
		if ( ! isset( $this->data[$key] ) ) {
			return false;
		}

		$value = $this->data[$key];
		$value = maybe_unserialize( $value );

		return $value;
	}


	/**
	 * @return Database_Table
	 */
	function get_table() {

		if ( ! isset( $this->table_id ) ) {
			trigger_error( sprintf( 'AutomateWoo - %s is an incompatible subclass of %s. You may need need to update your AutomateWoo add-ons.', get_called_class(), get_class()), E_USER_ERROR );
		}

		return Database_Tables::get( $this->table_id );
	}


	/**
	 * @return string
	 */
	function get_table_name() {
		return $this->get_table()->get_name();
	}


	/**
	 * Inserts or updates the model
	 * Only updates modified fields
	 *
	 * @return bool True on success, false on error.
	 */
	public function save() {

		global $wpdb;

		if ( $this->exists ) {
			// update changed fields
			$changed_data = array_intersect_key( $this->data, array_flip( $this->changed_fields ) );

			// serialize
			$changed_data = array_map( 'maybe_serialize', $changed_data );

			if ( empty( $changed_data ) ) {
				return true;
			}

			$updated = $wpdb->update(
				$this->get_table_name(),
				$changed_data,
				[ 'id' => $this->get_id() ],
				null,
				[ '%d' ]
			);

			if ( false === $updated ) {
				// Return here to prevent cache updates on error
				return false;
			}

			do_action( 'automatewoo/object/update', $this ); // cleans object cache
		}
		else {
			$this->data = array_map( 'maybe_serialize', $this->data );

			// insert row
			$wpdb->insert(
				$this->get_table_name(),
				$this->data
			);

			if ( $wpdb->insert_id ) {
				$this->exists = true;
				$this->id = $wpdb->insert_id;
			} else {
				$aw_catch_db_error = 'Error: '.$wpdb->last_error.' (Query: '.$wpdb->last_query.')';
				Logger::critical( 'errors', sprintf( __( 'Could not insert \'%1$s\' item to database. AutomateWoo tables may not be installed. (%2$s)', 'automatewoo' ), $this->object_type, $aw_catch_db_error) );

				// Return here to prevent cache updates on error
				return false;
			}

			do_action( 'automatewoo/object/create', $this ); // cleans object cache
		}

		// reset changed data
		// important to reset after cache hooks
		$this->changed_fields = [];
		$this->original_data = $this->data;

		return true;
	}


	/**
	 * @return void
	 */
	function delete() {
		global $wpdb;

		do_action( 'automatewoo/object/delete', $this ); // cleans object cache

		if ( ! $this->exists ) return;

		$wpdb->query( $wpdb->prepare( "
                DELETE FROM {$this->get_table_name()}
		 		WHERE id = %d
			", $this->get_id()
		));

		$this->exists = false;
	}


	/**
	 * @param $column
	 * @return bool|DateTime
	 */
	protected function get_date_column( $column ) {
		if ( $column && $prop = $this->get_prop( $column ) ) {
			return new DateTime( $prop );
		}

		return false;
	}


	/**
	 * Sets the value of a date column from  a mixed input.
	 *
	 * $value can be an instance of WC_DateTime the timezone will be ignored.
	 * If $value is a string it must be MYSQL formatted.
	 *
	 * @param string                                      $column
	 * @param \WC_DateTime|DateTime|\DateTime|string|null $value
	 */
	protected function set_date_column( $column, $value ) {
		if ( is_a( $value, 'DateTime' ) ) {
			/** @var \DateTime $value Accepts AutomateWoo\DateTime, DateTime or WC_DateTime */
			// convert to UTC time
			$utc_date = new DateTime();
			$utc_date->setTimestamp( $value->getTimestamp() );
			$this->set_prop( $column, $utc_date->to_mysql_string() );
		} elseif ( $value ) {
			$this->set_prop( $column, Clean::string( $value ) );
		} elseif ( null === $value ) {
			$this->set_prop( $column, null );
		}
	}
}
