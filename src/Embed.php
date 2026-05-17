<?php
/**
 * Renders the Repejo checkout via the `[repejo_checkout]` shortcode and a
 * matching server-rendered Gutenberg block.
 *
 * The donor id is taken from the `repejo_id` query var that {@see Rewrite}
 * populates from the pretty URL. The actual markup is a configurable iframe
 * (base URL from {@see Settings}) and is fully overridable via the
 * `repejo_wp_plugin_embed_html` filter for script-based embeds.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Embed {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_shortcode( 'repejo_checkout', array( $this, 'shortcode' ) );
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * @param array<string,string>|string $atts
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array( 'id' => '' ),
			is_array( $atts ) ? $atts : array(),
			'repejo_checkout'
		);

		return $this->render( (string) $atts['id'] );
	}

	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			REPEJO_WP_PLUGIN_DIR . 'blocks/checkout',
			array(
				'render_callback' => function ( array $attributes ): string {
					return $this->render( (string) ( $attributes['id'] ?? '' ) );
				},
			)
		);
	}

	/**
	 * Resolve the donor id (explicit attribute wins, otherwise the query var
	 * from the pretty URL) and return the embed HTML.
	 */
	private function render( string $explicit_id ): string {
		$id = '' !== $explicit_id
			? $explicit_id
			: (string) get_query_var( Rewrite::QUERY_VAR, '' );

		$id   = sanitize_text_field( $id );
		$base = $this->settings->embed_base_url();

		/**
		 * Filter the full embed markup. Return a non-null string to take over
		 * rendering entirely (e.g. for a <script> based embed).
		 *
		 * @param string|null $html  Default null (let the plugin render).
		 * @param string      $id    Resolved donor id ('' if none).
		 * @param string      $base  Configured embed base URL ('' if unset).
		 */
		$custom = apply_filters( 'repejo_wp_plugin_embed_html', null, $id, $base );
		if ( is_string( $custom ) ) {
			return $custom;
		}

		if ( '' === $base ) {
			// Surfaced to admins via Diagnostics; never leak config state publicly.
			return current_user_can( 'manage_options' )
				? '<p><strong>Repejo:</strong> ' . esc_html__( 'Embed-URL är inte konfigurerad (Inställningar → Repejo).', 'repejo-wp-plugin' ) . '</p>'
				: '';
		}

		// add_query_arg() URL-encodes the key/value itself; passing raw values
		// avoids double-encoding.
		$src = add_query_arg(
			$this->settings->target_param(),
			$id,
			$base
		);

		return sprintf(
			'<iframe class="repejo-checkout" src="%s" width="100%%" height="%d" style="border:0;max-width:100%%;" loading="lazy" referrerpolicy="origin"></iframe>',
			esc_url( $src ),
			$this->settings->iframe_height()
		);
	}
}
