<?php
/**
 * Plugin Name:       Repejo WP Plugin
 * Plugin URI:        https://github.com/Repejo/repejo-wp-plugin
 * Description:        Gör att Repejo-checkouten kan länkas med givar-id direkt i adressen (t.ex. /signera/abc-123) istället för som frågeparameter, och renderar checkouten via shortcode/block.
 * Version:           0.1.0
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Author:            Repejo
 * Author URI:        https://repejo.se
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       repejo-wp-plugin
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'REPEJO_WP_PLUGIN_VERSION', '0.1.0' );
define( 'REPEJO_WP_PLUGIN_FILE', __FILE__ );
define( 'REPEJO_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REPEJO_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'REPEJO_WP_PLUGIN_SLUG', 'repejo-wp-plugin' );

/**
 * Minimal PSR-4 autoloader for the Repejo\WpPlugin namespace.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$path     = REPEJO_WP_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

// Optional Composer autoload (bundled by the release workflow, e.g. Plugin Update Checker).
if ( is_readable( REPEJO_WP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require REPEJO_WP_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * On activation the rewrite rules our pages rely on are not yet registered
 * (our init hook has not run for this request), so we flush on the next load
 * via a one-shot flag, and also flush here as a best effort.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		update_option( 'repejo_wp_plugin_needs_flush', '1', false );
		flush_rewrite_rules( false );
	}
);

/**
 * On deactivation our init hook will no longer add the custom rules, so a
 * flush clears them from the stored rewrite rule set.
 */
register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules( false );
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		( new Plugin() )->boot();
	}
);
