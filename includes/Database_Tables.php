<?php

namespace AutomateWoo;

/**
 * @class Database_Tables
 * @since 2.8.2
 */
class Database_Tables extends Registry {

	/** @var array */
	public static $includes;

	/** @var Database_Table[] */
	public static $loaded = [];


	/**
	 * Updates any tables as required
	 */
	public static function install_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		foreach ( self::get_all() as $table ) {
			$table->install();
		}
	}


	/**
	 * Load the application Database Tables
	 *
	 * @return array The DatabaseTables classes to be loaded
	 */
	public static function load_includes() {
		$includes = [
			'carts'         => DatabaseTables\Carts::class,
			'customer-meta' => DatabaseTables\CustomerMeta::class,
			'customers'     => DatabaseTables\Customers::class,
			'guest-meta'    => DatabaseTables\GuestMeta::class,
			'guests'        => DatabaseTables\Guests::class,
			'log-meta'      => DatabaseTables\LogMeta::class,
			'logs'          => DatabaseTables\Logs::class,
			'queue'         => DatabaseTables\Queue::class,
			'queue-meta'    => DatabaseTables\QueueMeta::class,
		];

		return apply_filters( 'automatewoo/database_tables', $includes );
	}


	/**
	 * Get all the database tables
	 *
	 * @return Database_Table[] An array with all the database tables
	 */
	public static function get_all() {
		return parent::get_all();
	}

	/**
	 * Get a database table object.
	 *
	 * @param string $table_id The table to get
	 *
	 * @return Database_Table The database table class
	 *
	 * @throws Exception When table failed to load.
	 */
	public static function get( $table_id ) {
		$table = parent::get( $table_id );

		if ( $table instanceof Database_Table ) {
			return $table;
		}

		/* translators: Database table name. */
		throw new Exception( sprintf( esc_html__( "Failed to load the '%s' database table.", 'automatewoo' ), esc_html( $table_id ) ) );
	}
}
