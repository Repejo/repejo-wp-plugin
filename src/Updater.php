<?php
/**
 * One-click updates from wp-admin via the Plugin Update Checker library,
 * pointed at this plugin's GitHub Releases. The release workflow attaches the
 * built zip as a release asset; PUC serves it as a normal plugin update.
 *
 * PUC is bundled by the release build (Composer). If it is absent (e.g. a
 * plain git checkout for development) updates are simply disabled — the
 * plugin still works.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Updater {

	/**
	 * Public GitHub repository that hosts the releases. Patched to the real
	 * URL when the repository is created.
	 */
	private const GITHUB_REPO = 'https://github.com/Repejo/repejo-wp-plugin';

	public function register(): void {
		add_action( 'init', array( $this, 'boot' ) );
	}

	public function boot(): void {
		$checker = $this->build_checker();
		if ( null === $checker ) {
			return;
		}

		$checker->setBranch( 'main' );

		$api = method_exists( $checker, 'getVcsApi' ) ? $checker->getVcsApi() : null;
		if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
			$api->enableReleaseAssets();
		}
	}

	/**
	 * @return object|null PUC update checker instance, or null if unavailable.
	 */
	private function build_checker() {
		// PUC v5.
		$v5 = 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory';
		if ( class_exists( $v5 ) ) {
			return $v5::buildUpdateChecker(
				self::GITHUB_REPO,
				REPEJO_WP_PLUGIN_FILE,
				REPEJO_WP_PLUGIN_SLUG
			);
		}

		// PUC v4 fallback.
		if ( class_exists( '\\Puc_v4_Factory' ) ) {
			return \Puc_v4_Factory::buildUpdateChecker(
				self::GITHUB_REPO,
				REPEJO_WP_PLUGIN_FILE,
				REPEJO_WP_PLUGIN_SLUG
			);
		}

		return null;
	}
}
