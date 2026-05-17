<?php
/**
 * Turns the three classic silent failure modes into visible wp-admin notices
 * so a non-technical client sees the problem here instead of via a broken
 * donor flow:
 *
 *   1. Permalinks set to "Plain" — the rewrite engine never runs.
 *   2. Embed base URL not configured — nothing renders.
 *   3. An opted-in page has child pages — the (alphanumeric) rule can shadow
 *      those real children.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diagnostics {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( '' === (string) get_option( 'permalink_structure', '' ) ) {
			$this->notice(
				'error',
				sprintf(
					/* translators: %s: link to the Permalinks settings screen. */
					__( 'Repejo: Permalänkar står på "Enkel". De fina länkarna (/sida/id) fungerar inte förrän du väljer en annan permalänkstruktur under %s.', 'repejo-wp-plugin' ),
					'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Inställningar → Permalänkar', 'repejo-wp-plugin' ) . '</a>'
				)
			);
		}

		if ( '' === $this->settings->embed_base_url() ) {
			$this->notice(
				'warning',
				sprintf(
					/* translators: %s: link to the plugin settings screen. */
					__( 'Repejo: Embed-URL är inte konfigurerad. Checkouten renderas inte förrän du fyller i den under %s.', 'repejo-wp-plugin' ),
					'<a href="' . esc_url( admin_url( 'options-general.php?page=repejo-wp-plugin' ) ) . '">' . esc_html__( 'Inställningar → Repejo', 'repejo-wp-plugin' ) . '</a>'
				)
			);
		}

		$shadowed = $this->pages_with_children();
		if ( array() !== $shadowed ) {
			$this->notice(
				'warning',
				sprintf(
					/* translators: %s: comma separated page titles. */
					__( 'Repejo: Följande sidor har Repejo-länkformat aktiverat men har även undersidor, som kan döljas av regeln: %s. Använd ett snävare id-mönster eller flytta undersidorna.', 'repejo-wp-plugin' ),
					'<strong>' . esc_html( implode( ', ', $shadowed ) ) . '</strong>'
				)
			);
		}
	}

	/**
	 * Titles of opted-in pages that have at least one published child page.
	 *
	 * @return string[]
	 */
	private function pages_with_children(): array {
		$rewrite = new Rewrite( $this->settings );
		$titles  = array();

		foreach ( array_keys( $rewrite->opted_in_pages() ) as $page_id ) {
			$children = get_posts(
				array(
					'post_type'     => 'page',
					'post_parent'   => $page_id,
					'post_status'   => 'publish',
					'numberposts'   => 1,
					'fields'        => 'ids',
					'no_found_rows' => true,
				)
			);
			if ( array() !== $children ) {
				$titles[] = get_the_title( $page_id );
			}
		}

		return $titles;
	}

	private function notice( string $type, string $html ): void {
		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			esc_attr( $type ),
			wp_kses_post( $html )
		);
	}
}
