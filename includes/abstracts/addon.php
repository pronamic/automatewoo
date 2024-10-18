<?php
namespace AutomateWoo;

/**
 * Addon class.
 *
 * This class must remain named as 'includes/abstracts/addon.php' because it's what AW add-ons expect.
 */
abstract class Addon {

	/** @var Addon - must declare in child */
	protected static $_instance; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/** @var string */
	public $id;

	/** @var string */
	public $name;

	/** @var string */
	public $version;

	/** @var string */
	public $plugin_basename;

	/** @var string */
	public $plugin_path;

	/** @var string */
	public $file;

	/** @var string */
	public $min_php_version;

	/** @var string */
	public $min_automatewoo_version;

	/** @var string */
	public $min_woocommerce_version;

	/** @var array */
	public $db_updates = [];



	/**
	 * Method to init the add on
	 */
	abstract public function init();

	/**
	 * Required method to return options class
	 *
	 * @return Options_API
	 */
	abstract public function options();

	/**
	 * Optional installer method
	 */
	public function install() {}


	/**
	 * Constructor for add-on
	 *
	 * @param Plugin_Data|object $plugin_data
	 */
	public function __construct( $plugin_data ) {

		$this->id                      = $plugin_data->id;
		$this->name                    = $plugin_data->name;
		$this->version                 = $plugin_data->version;
		$this->file                    = $plugin_data->file;
		$this->min_automatewoo_version = $plugin_data->min_automatewoo_version;

		$this->plugin_basename = plugin_basename( $plugin_data->file );
		$this->plugin_path     = dirname( $plugin_data->file );

		add_action( 'automatewoo_init_addons', [ $this, 'register' ] );
		add_action( 'automatewoo_init_addons', [ $this, 'init' ] );
	}


	/**
	 * @param string $end
	 * @return string
	 */
	public function url( $end = '' ) {
		return untrailingslashit( plugin_dir_url( $this->plugin_basename ) ) . $end;
	}


	/**
	 * @param string $end
	 * @return string
	 */
	public function path( $end = '' ) {
		return untrailingslashit( $this->plugin_path ) . $end;
	}


	/**
	 * Check the version stored in the database and determine if an upgrade needs to occur
	 */
	public function check_version() {

		if ( version_compare( $this->version, $this->options()->version, '=' ) ) {
			return;
		}

		$this->install();

		if ( $this->is_database_upgrade_available() ) {
			if ( apply_filters( 'woocommerce_enable_auto_update_db', false ) ) {
				$this->do_database_update();
				return;
			} else {
				add_action( 'admin_notices', [ $this, 'data_upgrade_prompt' ] );
			}
		} else {
			$this->update_database_version();
		}
	}


	/**
	 * @return bool
	 */
	public function is_database_upgrade_available() {

		if ( version_compare( $this->version, $this->options()->version, '=' ) || empty( $this->db_updates ) ) {
			return false;
		}

		return $this->options()->version && version_compare( $this->options()->version, max( $this->db_updates ), '<' );
	}


	/**
	 * Handle updates
	 */
	public function do_database_update() {
		wp_raise_memory_limit( 'admin' );

		foreach ( $this->db_updates as $update ) {
			if ( version_compare( $this->options()->version, $update, '<' ) ) {
				include $this->path( "/includes/updates/$update.php" );
			}
		}

		$this->update_database_version();
	}


	/**
	 * Update version to current
	 */
	public function update_database_version() {
		update_option( $this->options()->prefix . 'version', $this->version, true );
		do_action( 'automatewoo_addon_updated' );
	}


	/**
	 * Renders prompt notice for user to update
	 */
	public function data_upgrade_prompt() {
		AW()->admin->get_view(
			'data-upgrade-prompt',
			[
				'plugin_name' => $this->name,
				'plugin_slug' => $this->id,
			]
		);
	}


	/**
	 * Registers the add-on.
	 *
	 * @since 4.6.0
	 */
	public function register() {
		Addons::register( $this );
	}


	/**
	 * Runs when the add-on plugin is activated.
	 */
	public function activate() {
		flush_rewrite_rules();
		AdminNotices::add_notice( 'addon_welcome_' . $this->id );
	}


	/**
	 * @return string
	 */
	public function get_getting_started_url() {
		return '';
	}


	/**
	 * @param Plugin_Data|mixed $data
	 * @return Addon|mixed
	 */
	public static function instance( $data ) {
		if ( is_null( static::$_instance ) ) {
			static::$_instance = new static( $data );
		}
		return static::$_instance;
	}
}


/**
 * @class Plugin_Data
 *
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */
class Plugin_Data {

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Main plugin file
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Minimum PHP version
	 *
	 * @var string
	 */
	public $min_php_version;

	/**
	 * Minimum AutomateWoo version
	 *
	 * @var string
	 */
	public $min_automatewoo_version;

	/**
	 * Minimum WooCommerce version
	 *
	 * @var string
	 */
	public $min_woocommerce_version;
}
