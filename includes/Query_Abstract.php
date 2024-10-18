<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Query_Abstract
 * @since 3.8
 */
abstract class Query_Abstract {

	/**
	 * Set's the database table ID.
	 *
	 * @var string (required)
	 */
	public $table_id;

	/** @var string (optional) */
	public $meta_table_id;

	/**
	 * Set's the class to use for the return object.
	 *
	 * @var string (required)
	 */
	protected $model;

	/** @var array - top array level uses AND condition, for OR conditions nest */
	public $where = [];

	/** @var array - top array level uses AND condition, for OR conditions nest */
	public $where_meta = [];

	/**
	 * @var string Possible values: 'objects', 'ids'
	 */
	protected $return = 'objects';

	/** @var int */
	public $found_rows = 0;

	/** @var int */
	protected $limit;

	/** @var int */
	protected $offset;

	/** @var int */
	protected $page;

	/** @var string */
	protected $orderby;

	/** @var string */
	protected $order;

	/** @var int */
	protected $calc_found_rows = false;

	/** @var string */
	public $sql;

	/** @var bool */
	public $combine_wheres_with_or = false;


	/**
	 * Possible compare values: =, <, > IN, NOT IN
	 *
	 * @param $column string
	 * @param $value mixed
	 * @param $compare bool|string - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where( $column, $value, $compare = false ) {

		// if $column is not a column, do a meta query instead
		if ( ! array_key_exists( $column, $this->get_table_columns() ) ) {
			return $this->where_meta( $column, $value, $compare );
		}

		$this->where[] = [
			'column' => $column,
			'value' => $value,
			'compare' => $compare
		];

		return $this;
	}


	/**
	 * Does not support EXISTS or NOT EXISTS
	 *
	 * @param $key
	 * @param $value
	 * @param bool|string $compare - defaults to '=' or 'IN' if array
	 * @return $this
	 */
	function where_meta( $key, $value, $compare = false ) {

		$this->where_meta[] = [
			'key'  => $key,
			'value'  => $value,
			'compare' => $compare
		];

		return $this;
	}


	/**
	 * @param string $column The column to order_by the query
	 * @param string $order The ordering direction (ASC or DESC)
	 * @param array  $valid_sortable_columns The allowed sortable columns. By default, empty array (all allowed)
	 * @return $this
	 */
	function set_ordering( $column, $order = 'DESC', $valid_sortable_columns = [] ) {
		$this->orderby = Clean::order_by( $column, $valid_sortable_columns );
		$this->order = Clean::order( $order );
		return $this;
	}


	/**
	 * @param $i
	 * @return $this
	 */
	function set_limit( $i ) {
		$this->limit = absint($i);
		return $this;
	}

	/**
	 * @param bool $calc_found_rows defaults to true now, but will change to default to false soon
	 * @return $this
	 */
	function set_calc_found_rows( $calc_found_rows ) {
		$this->calc_found_rows = (bool) $calc_found_rows;
		return $this;
	}


	/**
	 * @param $i
	 * @return $this
	 */
	function set_offset( $i ) {
		$this->offset = absint($i);
		return $this;
	}


	/**
	 * Don't set a page if an offset is also set.
	 * Requires a limit to be set.
	 * @since 4.0
	 * @param int $page
	 * @return $this
	 */
	function set_page( $page ) {
		$this->page = absint( $page );
		return $this;
	}


	/**
	 * @deprecated in 5.1.0 use self::get_results_as_ids() instead
	 *
	 * @param string $return
	 * 	'objects' (default) | 'ids'
	 * @return $this
	 */
	function set_return( $return ) {
		wc_deprecated_function( __METHOD__, '5.1.0', '\AutomateWoo\Query_Abstract::get_results_as_ids' );
		$this->return = Clean::string( $return );
		return $this;
	}


	/**
	 * @return Database_Table
	 */
	function get_table() {

		if ( ! isset( $this->table_id ) ) {
			trigger_error( sprintf( 'AutomateWoo - %s is an incompatible subclass of %s. You need to update your AutomateWoo add-ons.', get_called_class(), get_class()), E_USER_ERROR );
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
	 * @return array
	 */
	function get_table_columns() {
		return $this->get_table()->get_columns();
	}


	/**
	 * @return Database_Table|false
	 */
	function get_meta_table() {
		if ( ! $this->meta_table_id ) return false;
		return Database_Tables::get( $this->meta_table_id );
	}


	/**
	 * @return string
	 */
	function get_meta_table_name() {
		if ( ! $this->meta_table_id ) return false;
		return $this->get_meta_table()->get_name();
	}


	/**
	 * @return string
	 */
	function get_meta_object_id_column() {
		if ( ! $this->meta_table_id ) return false;
		return $this->get_meta_table()->get_object_id_column();
	}


	/**
	 * @param array $where
	 * @return string
	 */
	private function parse_where( $where ) {

		global $wpdb;

		if ( ! is_array( $where ) ) {
			return '';
		}

		$column = $where['column'];
		$value = $where['value'];
		$compare = isset( $where['compare'] ) ? $where['compare'] : false;

		// Accepts AutomateWoo\DateTime, DateTime or WC_DateTime
		if ( is_a( $value, 'DateTime' ) ) {
			// always use UTC time
			/** @var \DateTime $value */
			$utc_date = new DateTime();
			$utc_date->setTimestamp( $value->getTimestamp() );
			$value = $utc_date->to_mysql_string();
		}

		if ( ! $compare ) {
			$compare = is_array( $value ) ? 'IN' : '='; // set default values
		}

		if ( is_array( $value ) ) {

			$value = "('" . implode( "','", esc_sql( $value ) ) . "')";

			return "$column $compare $value";
		}
		else {
			return $wpdb->prepare(
				"$column $compare %s",
				$value
			);
		}
	}


	/**
	 * @param array $query
	 */
	protected function build_where_query( &$query ) {

		$query_joins = [];
		$query_wheres = [];

		foreach( $this->where as $where ) {
			if ( isset( $where['column'] ) ) {
				$query_wheres[] = $this->parse_where( $where );
			}
			else {

				$ors = [];

				// nested OR conditions
				foreach( $where as $where_or ) {
					$ors[] = $this->parse_where( $where_or );
				}

				$query_wheres[] = '( ' . implode("\nOR ", $ors ) . ' )';
			}
		}

		if ( ! empty( $this->where_meta ) && $this->get_meta_table_name() ) {
			$i = 1;
			foreach( $this->where_meta as $where ) {

				$query_joins[] = "INNER JOIN {$this->get_meta_table_name()} AS mt$i ON ({$this->get_table_name()}.id = mt$i.{$this->get_meta_object_id_column()})";

				if ( isset( $where['key'] ) ) {

					$meta_key = esc_sql( $where['key'] );
					$where['column'] = "mt$i.meta_value";

					$query_wheres[] = "(mt$i.meta_key = '$meta_key' AND "
						. $this->parse_where( $where )
						. ")";
				}
				else {

					$ors = [];

					// nested OR conditions
					foreach( $where as $where_or ) {

						$meta_key = esc_sql( $where_or['key'] );
						$where_or['column'] = "mt$i.meta_value";

						$ors[] = "(mt$i.meta_key = '$meta_key' AND "
							. $this->parse_where( $where_or )
							. ")";
					}

					$query_wheres[] = '( ' . implode("\nOR ", $ors ) . ' )';

				}
				$i++;
			}
		}

		if ( $query_joins ) {
			$query[] = implode( "\n", $query_joins );
		}

		if ( ! empty( $query_wheres ) ) {
			$query[] = "WHERE";
			$query[] = implode($this->combine_wheres_with_or ? "\nOR " : "\nAND ", $query_wheres );
		}
	}


	/**
	 * @return Model[]|array
	 */
	function get_results() {

		global $wpdb;

		$return_fields = $this->return === 'ids' ? 'id' : '*';
		$found_rows = $this->calc_found_rows ? 'SQL_CALC_FOUND_ROWS ' : '';

		$query = [
			"SELECT {$found_rows}{$return_fields} FROM {$this->get_table_name()}"
		];

		$this->build_where_query( $query );

		$query[] = "GROUP BY {$this->get_table_name()}.id";

		if ( $this->orderby ) {
			$query[] = "ORDER BY {$this->orderby} {$this->order}";
		}

		if ( $this->limit ) {
			$query[] = "LIMIT $this->limit ";
		}

		if ( $this->offset ) {
			$query[] = "OFFSET $this->offset ";
		}
		elseif ( $this->page && $this->limit ) {
			$query[] = "OFFSET " . ( $this->limit * ( $this->page - 1 ) ) . " ";
		}

		$this->sql = apply_filters( 'automatewoo/query/get_results_sql', implode( "\n", $query ), $this );

		$results = $wpdb->get_results( $this->sql, ARRAY_A );

		if ( empty( $results ) ) {
			return [];
		}

		if ( $this->calc_found_rows ) {
			$this->found_rows = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
		}

		if ( $this->return === 'ids' ) {
			return wp_list_pluck( $results, 'id' );
		}
		else {

			if ( $this->model ) {
				$modelled_results = [];

				foreach ( $results as $result ) {
					/** @var Model $modelled_result */
					$modelled_result = new $this->model();
					$modelled_result->fill( $result );
					$modelled_results[] = $modelled_result;
				}

				return $modelled_results;
			}

			return $results;
		}
	}

	/**
	 * Get query results as item IDs.
	 *
	 * @since 5.1.0
	 *
	 * @return int[]
	 */
	public function get_results_as_ids() {
		$this->return = 'ids';
		$ids = array_map( 'intval', $this->get_results() );
		$this->return = 'objects';
		return $ids;
	}

	/**
	 * @return int
	 */
	function get_count() {

		global $wpdb;

		$query = [
			"SELECT COUNT(id) FROM {$this->get_table_name()}"
		];

		$this->build_where_query( $query );

		$this->sql = apply_filters( 'automatewoo/query/get_count_sql', implode( "\n", $query ), $this );

		return (int) $wpdb->get_var( $this->sql );
	}


	/**
	 * Checks to see if a query would have at least one result
	 * Then returns the query back to normal state
	 *
	 * @since 3.2.4
	 * @return bool
	 */
	function has_results() {

		$limit = $this->limit;
		$return = $this->return;
		$calc_found_rows = $this->calc_found_rows;

		$this->set_limit( 1 );
		$this->return = 'ids';
		$this->set_calc_found_rows( false );

		$results = $this->get_results();

		$this->limit = $limit;
		$this->return = $return;
		$this->calc_found_rows = $calc_found_rows;

		return ! empty( $results );
	}

}
