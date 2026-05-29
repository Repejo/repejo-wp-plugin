<?php
/**
 * Injects the donor id into the page <head> as a <meta> tag so the Repejo
 * checkout component already present on the page can read it.
 *
 * The plugin renders no checkout itself. It only resolves the id and exposes
 * it in one consistent place for both URL forms:
 *
 *   - new pretty URL  /sida/<id>      -> from the `repejo_id` query var that
 *                                        {@see Rewrite} populates
 *   - legacy URL      /sida?rp_hrid=  -> from the request query parameter
 *
 * Contract for the front-end component:
 *
 *   <meta name="repejo-telemarketing-id" content="<id>">
 *
 * (the name is configurable under Inställningar → Repejo).
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaTag {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'wp_head', array( $this, 'render' ), 1 );
	}

	public function render(): void {
		$id = $this->resolve_id();
		if ( '' === $id ) {
			return;
		}

		printf(
			'<meta name="%s" content="%s" />' . "\n",
			esc_attr( $this->settings->meta_name() ),
			esc_attr( $id )
		);
	}

	/**
	 * Resolve the donor id: the rewritten pretty URL wins, otherwise fall
	 * back to the legacy query parameter so the component has a single
	 * source regardless of URL form.
	 */
	private function resolve_id(): string {
		$id = (string) get_query_var( Rewrite::QUERY_VAR, '' );

		if ( '' === $id ) {
			$param = $this->settings->source_param();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, public link param.
			if ( isset( $_GET[ $param ] ) ) {
				$id = sanitize_text_field( wp_unslash( (string) $_GET[ $param ] ) );
			}
		}

		return sanitize_text_field( $id );
	}
}
