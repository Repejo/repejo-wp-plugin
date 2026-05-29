<?php
/**
 * Settings storage and the admin settings screen.
 *
 * Scope is intentionally tiny: the plugin only exposes the donor id, it does
 * not render the checkout. So all that is configurable is the id URL pattern,
 * the <meta> name the front-end component reads, and the legacy query
 * parameter to fall back to.
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
					'meta_name'    => 'repejo-telemarketing-id',
					'source_param' => 'rp_hrid',
					'id_pattern'   => '[A-Za-z0-9_-]+',
				)
			);
		}
		return $this->cache;
	}

	/** The <meta name="..."> the front-end component reads the id from. */
	public function meta_name(): string {
		$name = trim( $this->all()['meta_name'] );
		return '' === $name ? 'repejo-telemarketing-id' : $name;
	}

	/** Legacy query parameter to fall back to (the old ?rp_hrid= links). */
	public function source_param(): string {
		$param = $this->all()['source_param'];
		return '' === $param ? 'rp_hrid' : $param;
	}

	/**
	 * Regex fragment for a single donor-id path segment. Wrapped in a capture
	 * group by {@see Rewrite}. Falls back to a safe alphanumeric pattern.
	 */
	public function id_regex(): string {
		$pattern = trim( $this->all()['id_pattern'] );
		return '' === $pattern ? '[A-Za-z0-9_-]+' : $pattern;
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
			'meta_name'    => isset( $input['meta_name'] ) ? sanitize_text_field( trim( (string) $input['meta_name'] ) ) : 'repejo-telemarketing-id',
			'source_param' => isset( $input['source_param'] ) ? sanitize_key( $input['source_param'] ) : 'rp_hrid',
			'id_pattern'   => isset( $input['id_pattern'] ) ? trim( (string) $input['id_pattern'] ) : '[A-Za-z0-9_-]+',
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
			<p>
				<?php esc_html_e( 'Detta tillägg renderar ingen checkout. Det gör bara att adresser av typen /sida/<id> fungerar och lägger id:t i sidans <head> så att er Repejo-komponent kan läsa det.', 'repejo-wp-plugin' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'repejo_wp_plugin' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="repejo_meta_name"><?php esc_html_e( 'Meta-namn (komponenten läser detta)', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[meta_name]"
								id="repejo_meta_name" type="text" class="regular-text code"
								value="<?php echo esc_attr( $values['meta_name'] ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: rendered meta tag example. */
									esc_html__( 'Renderas som %s i sidans head. Måste matcha det er komponent letar efter.', 'repejo-wp-plugin' ),
									'<code>&lt;meta name=&quot;' . esc_html( $values['meta_name'] ) . '&quot; content=&quot;&lt;id&gt;&quot;&gt;</code>'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="repejo_source_param"><?php esc_html_e( 'Äldre frågeparameter (bakåtkompatibilitet)', 'repejo-wp-plugin' ); ?></label>
						</th>
						<td>
							<input name="<?php echo esc_attr( self::OPTION ); ?>[source_param]"
								id="repejo_source_param" type="text" class="regular-text code"
								value="<?php echo esc_attr( $values['source_param'] ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Om någon besöker den gamla ?rp_hrid=...-länken läses id:t även därifrån. Standard: rp_hrid.', 'repejo-wp-plugin' ); ?>
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
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
