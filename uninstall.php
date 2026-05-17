<?php
/**
 * Removes all plugin data on uninstall: options and the per-page opt-in meta.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'repejo_wp_plugin_settings' );
delete_option( 'repejo_wp_plugin_rules_signature' );
delete_option( 'repejo_wp_plugin_needs_flush' );

delete_post_meta_by_key( '_repejo_pretty_url' );

// The custom rewrite rules disappear once the plugin is gone; clear the
// cached rule set so WordPress regenerates it.
flush_rewrite_rules( false );
