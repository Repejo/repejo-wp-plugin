<?php
/**
 * Registers one rewrite rule per opted-in page so that
 * `/<page-path>/<donor-id>` resolves to that page instead of a 404,
 * and exposes the donor id as the `repejo_id` query var.
 *
 * The rule set is derived from page meta, so it changes whenever a page is
 * toggled or an opted-in page is moved/renamed. To stay correct even when a
 * change bypasses the normal save hooks (imports, WP-CLI, bulk edits) we keep
 * a signature of the current rule set and self-heal with a single deferred
 * flush when it drifts. We never flush on every request.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rewrite {

	public const META_KEY      = '_repejo_pretty_url';
	public const QUERY_VAR     = 'repejo_id';
	private const SIGNATURE_KEY = 'repejo_wp_plugin_rules_signature';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'add_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
	}

	/**
	 * Page IDs that have opted in, mapped to their current URI path.
	 *
	 * @return array<int,string> [ post_id => 'parent/child' ]
	 */
	public function opted_in_pages(): array {
		$ids = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'numberposts'            => -1,
				'fields'                 => 'ids',
				'meta_key'               => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'             => '1',            // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$map = array();
		foreach ( $ids as $id ) {
			$map[ (int) $id ] = trim( (string) get_page_uri( (int) $id ), '/' );
		}

		return $map;
	}

	public function add_rules(): void {
		$pages     = $this->opted_in_pages();
		$id_regex  = $this->settings->id_regex();

		foreach ( $pages as $id => $path ) {
			if ( '' === $path ) {
				continue;
			}

			$escaped_path = implode( '/', array_map( 'preg_quote', explode( '/', $path ) ) );

			add_rewrite_rule(
				'^' . $escaped_path . '/(' . $id_regex . ')/?$',
				'index.php?page_id=' . $id . '&' . self::QUERY_VAR . '=$matches[1]',
				'top'
			);
		}

		$this->maybe_flush( $pages );
	}

	/**
	 * @param string[] $vars
	 * @return string[]
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Flush at most once, and only when the rule set actually changed (or an
	 * activation/explicit flag asked for it). The flush is deferred to
	 * `shutdown` so it runs after the rules above are registered.
	 *
	 * @param array<int,string> $pages
	 */
	private function maybe_flush( array $pages ): void {
		$signature = md5( wp_json_encode( $pages ) );
		$forced    = '1' === get_option( 'repejo_wp_plugin_needs_flush' );

		if ( ! $forced && get_option( self::SIGNATURE_KEY ) === $signature ) {
			return;
		}

		add_action(
			'shutdown',
			static function () use ( $signature ): void {
				flush_rewrite_rules( false );
				update_option( self::SIGNATURE_KEY, $signature, false );
				delete_option( 'repejo_wp_plugin_needs_flush' );
			}
		);
	}
}
