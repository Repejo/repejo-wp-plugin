<?php
/**
 * Per-page opt-in: a checkbox in the page editor that turns the
 * `/<page>/<donor-id>` URL format on for that specific page.
 *
 * Toggling it changes the rewrite rule set; {@see Rewrite} detects that and
 * reflushes once. We also raise the explicit flush flag here so the change
 * takes effect on the immediate editor reload rather than the next request.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PageSettings {

	private const NONCE = 'repejo_wp_plugin_page_meta';

	public function register(): void {
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save' ), 10, 2 );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'repejo-wp-plugin-pretty-url',
			__( 'Repejo-länkformat', 'repejo-wp-plugin' ),
			array( $this, 'render' ),
			'page',
			'side',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		$enabled = '1' === get_post_meta( $post->ID, Rewrite::META_KEY, true );
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<label>
			<input type="checkbox" name="repejo_pretty_url" value="1" <?php checked( $enabled ); ?> />
			<?php esc_html_e( 'Aktivera Repejo-länkformat för denna sida', 'repejo-wp-plugin' ); ?>
		</label>
		<p class="description">
			<?php
			printf(
				/* translators: %s: example URL path for this page. */
				esc_html__( 'Tillåter adresser som %s utan att WordPress svarar 404.', 'repejo-wp-plugin' ),
				'<code>/' . esc_html( (string) get_page_uri( $post->ID ) ) . '/&lt;id&gt;</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) // phpcs:ignore WordPress.Security.NonceVerification
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE )
		) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		$enabled = isset( $_POST['repejo_pretty_url'] ) && '1' === $_POST['repejo_pretty_url'];

		if ( $enabled ) {
			update_post_meta( $post_id, Rewrite::META_KEY, '1' );
		} else {
			delete_post_meta( $post_id, Rewrite::META_KEY );
		}

		// Make the rule-set change take effect on the next request immediately.
		update_option( 'repejo_wp_plugin_needs_flush', '1', false );
	}
}
