<?php
/**
 * Plugin orchestrator: wires every component to WordPress hooks.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/**
	 * Boot all components. Called once on `plugins_loaded`.
	 */
	public function boot(): void {
		load_plugin_textdomain(
			'repejo-wp-plugin',
			false,
			dirname( plugin_basename( REPEJO_WP_PLUGIN_FILE ) ) . '/languages'
		);

		$settings = new Settings();

		( new Rewrite( $settings ) )->register();
		( new PageSettings() )->register();
		( new Canonical() )->register();
		( new MetaTag( $settings ) )->register();
		$settings->register();
		( new Diagnostics( $settings ) )->register();
		( new Updater() )->register();
	}
}
