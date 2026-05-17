<?php
/**
 * Settings storage and the admin settings screen.
 *
 * The embed base URL is intentionally required and not hardcoded: the actual
 * Repejo checkout embed contract is owned by Repejo and provided per
 * deployment, so it is configured here (or overridden via the
 * `repejo_wp_plugin_embed_html` filter) rather than guessed.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	public const OPTION = 'repejo_wp_plugin_settings';

	/** @var array<string,string>|null */
	private ?array $cache = null;

	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/** @return array<string,string> */
	private function all(): array {
		if ( null === $this->cache ) {
			$stored      = get_option( self::OPTION, array() );
			$this->cache = wp_parse_args(
				is_array( $stored ) ? $stored : array(),
				array(
					'embed_base_url' => '',
					'target_param'   => 'rp_hrid',
					'id_pattern'     => '[A-Za-z0-9_-]+',
					'iframe_height'  => '800',
				)
			);
		}
		return $this->cache;
	}

	public function embed_base_url(): string {
		return $this->all()['embed_base_url'];
	}

	public function target_param(): string {
		return $this->all()['target_param'] ?: 'rp_hrid';
	}

	/**
	 * Regex fragment for a single donor-id path segment. Wrapped in a capture
	 * group by {@see Rewrite}. Falls back to a safe alphanumeric pattern.
	 */
	public function id_regex(): string {
		$pattern = trim( $this->all()['id_pattern'] );
		return '' === $pattern ? '[A-Za-z0-9_-]+' : $pattern;
	}

	public function iframe_height(): int {
		return max( 1, (int) $this->all()['iframe_height'] );
	}

	public function register_settings(): void {
		register_setting(
			'repejo_wp_plugin',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * @param mixed $input
	 * @return array<string,string>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();

		return array(
			'embed_base_url' => isset( $input['embed_base_url'] ) ? esc_url_raw( trim( (string) $input['embed_base_url'] ) ) : '',
			'target_param'   => isset( $input['target_param'] ) ? sanitize_key( $input['target_param'] ) : 'rp_hrid',
			'id_pattern'     => isset( $input['id_pattern'] ) ? trim( (string) $input['id_pattern'] ) : '[A-Za-z0-9_-]+',
			'iframe_height'  => isset( $input['iframe_height'] ) ? (string) absint( $input['iframe_height'] ) : '800',
		);
	}

	public function add_page(): void {
		add_options_page(
			__( 'Repejo WP Plugin', 'repejo-wp-plugin' ),
			__( 'Repejo', 'repejo-wp-plugin' ),
			'manage_options',
			'repejo-wp-plugin',
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = $this->all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Repejo WP Plugin', 'repejo-wp-plugin' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'repejo_wp_plugin' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="repejo_embed_base_url"><?php esc_html_e( 'Embed-URL för checkouten', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[embed_base_url]"
								id="repejo_embed_base_url" type="url" class="regular-text code"
								value="<?php echo esc_attr( $values['embed_base_url'] ); ?>"
								placeholder="https://checkout.repejo.se/..." />
							<p class="description">
								<?php esc_html_e( 'Bas-URL till Repejo-checkouten. Givar-id:t läggs på som frågeparameter (se nedan). Lämnas tom = inget renderas.', 'repejo-wp-plugin' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="repejo_target_param"><?php esc_html_e( 'Parameternamn för id', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[target_param]"
								id="repejo_target_param" type="text" class="regular-text code"
								value="<?php echo esc_attr( $values['target_param'] ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Den parameter Repejo-backend redan förstår. Standard: rp_hrid.', 'repejo-wp-plugin' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="repejo_id_pattern"><?php esc_html_e( 'Tillåtna tecken i id (regex)', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[id_pattern]"
								id="repejo_id_pattern" type="text" class="regular-text code"
								value="<?php echo esc_attr( $values['id_pattern'] ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Standard [A-Za-z0-9_-]+ (alfanumeriskt). Snävare mönster minskar risken att riktiga undersidor skuggas.', 'repejo-wp-plugin' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="repejo_iframe_height"><?php esc_html_e( 'iframe-höjd (px)', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[iframe_height]"
								id="repejo_iframe_height" type="number" min="1" class="small-text"
								value="<?php echo esc_attr( $values['iframe_height'] ); ?>" />
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
