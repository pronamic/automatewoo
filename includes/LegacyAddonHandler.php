<?php

namespace AutomateWoo;

/**
 * Class LegacyAddonHandler
 *
 * @since 5.4.0
 */
class LegacyAddonHandler {

	/**
	 * @var string
	 */
	private $subscriptions_addon_file = '';

	const SUBSCRIPTIONS_ADDON_CLASS = 'AutomateWoo_Subscriptions';
	const SUBSCRIPTIONS_ADDON_NAME  = 'AutomateWoo - Subscriptions Add-on';

	/**
	 * Init this class.
	 */
	public function init() {
		add_filter( 'all_plugins', [ $this, 'filter_all_plugins_to_overwrite_legacy_addon_data' ] );

		$this->deactivate_legacy_addons();
	}

	/**
	 * Ensure legacy addons are deactivated.
	 */
	protected function deactivate_legacy_addons() {
		if ( $this->get_subscriptions_addon_file() ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( $this->get_subscriptions_addon_file() );
		}
	}

	/**
	 * Filter 'all_plugins' to overwrite the description for the legacy add-ons.
	 *
	 * @param array $all_plugins
	 *
	 * @return array[]
	 */
	public function filter_all_plugins_to_overwrite_legacy_addon_data( array $all_plugins ): array {
		$new_description = __(
			"<strong>Activation disabled</strong>. This plugin's features have been added to the main AutomateWoo plugin as of version 5.4. <strong>Please delete this plugin - it is no longer required.</strong>",
			'automatewoo'
		);

		foreach ( $all_plugins as $k => $plugin ) {
			if ( false !== strpos( $plugin['Name'], self::SUBSCRIPTIONS_ADDON_NAME ) ) {
				$all_plugins[ $k ]['Description'] = $new_description;
				break;
			}
		}

		return $all_plugins;
	}

	/**
	 * Gets the AutomateWoo_Subscriptions's class file path.
	 *
	 * @return string
	 */
	private function get_subscriptions_addon_file(): string {
		if ( empty( $this->subscriptions_addon_file ) && class_exists( self::SUBSCRIPTIONS_ADDON_CLASS ) ) {
			$addons_class = new \ReflectionClass( self::SUBSCRIPTIONS_ADDON_CLASS );

			$this->subscriptions_addon_file = $addons_class->getFileName();
		}

		return $this->subscriptions_addon_file;
	}
}
