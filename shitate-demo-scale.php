<?php
/**
 * Plugin Name:       shitate demo scale
 * Description:       Demo-site only. Lets any visitor (logged in or not) try the shitate theme's Typography Scale — ratio, base size and rounding — from a modal. Changes live in the visitor's browser; nothing is saved on the site.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            weblley inc
 * Author URI:        https://weblley.co.jp/
 * Text Domain:       shitate-demo-scale
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/weblley/shitate-demo-scale
 *
 * Naming: sds = shitate demo scale. Constants SDS_*, functions sds_*, CSS --sds-* / .sds-*.
 *
 * @package ShitateDemoScale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SDS_VERSION', '0.1.0' );
define( 'SDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SDS_URL', plugin_dir_url( __FILE__ ) );
define( 'SDS_BASENAME', plugin_basename( __FILE__ ) );

// GitHub distribution: every demo site pulls updates from this repo's latest Release.
define( 'SDS_GITHUB_REPO', 'weblley/shitate-demo-scale' );
define( 'SDS_GITHUB_ASSET', 'shitate-demo-scale.zip' );

require_once SDS_DIR . 'includes/github-updater.php';

/**
 * Load translations shipped with the plugin.
 */
function sds_load_textdomain() {
	load_plugin_textdomain( 'shitate-demo-scale', false, dirname( SDS_BASENAME ) . '/languages' );
}
add_action( 'init', 'sds_load_textdomain', 1 );

/**
 * Whether the demo UI should render on this request.
 *
 * Front end only, and only while the shitate theme (or a child of it) is
 * active — the modal drives the theme's --st-* tokens and is meaningless
 * elsewhere. Use the `sds_enabled` filter to switch it off (e.g. per host).
 *
 * @return bool
 */
function sds_is_enabled() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( 'shitate' !== get_template() ) {
		return false;
	}
	return (bool) apply_filters( 'sds_enabled', true );
}

/**
 * Ratio choices — identical to the theme's Customizer select.
 *
 * @return array<string,string> value => label
 */
function sds_ratio_choices() {
	return array(
		'1.067' => __( 'Minor Second — 1.067', 'shitate-demo-scale' ),
		'1.125' => __( 'Major Second — 1.125', 'shitate-demo-scale' ),
		'1.2'   => __( 'Minor Third — 1.2', 'shitate-demo-scale' ),
		'1.25'  => __( 'Major Third — 1.25', 'shitate-demo-scale' ),
		'1.333' => __( 'Perfect Fourth — 1.333', 'shitate-demo-scale' ),
		'1.414' => __( 'Augmented Fourth — 1.414', 'shitate-demo-scale' ),
		'1.5'   => __( 'Perfect Fifth — 1.5', 'shitate-demo-scale' ),
		'1.618' => __( 'Golden Ratio — 1.618', 'shitate-demo-scale' ),
	);
}

/**
 * The theme's currently saved Typography Scale values (what "Reset" returns to).
 *
 * @return array{ratio:string,base:int,round:bool}
 */
function sds_theme_defaults() {
	$ratio = (string) get_theme_mod( 'shitate_ratio', '1.25' );
	if ( ! array_key_exists( $ratio, sds_ratio_choices() ) ) {
		$ratio = '1.25';
	}
	$base = absint( get_theme_mod( 'shitate_text_m', 16 ) );
	if ( $base < 12 || $base > 24 ) {
		$base = 16;
	}
	return array(
		'ratio' => $ratio,
		'base'  => $base,
		'round' => (bool) get_theme_mod( 'shitate_round_scale', true ),
	);
}

/**
 * Enqueue the modal's assets.
 *
 * The script is printed in <head> on purpose: it re-applies the visitor's
 * saved settings before first paint so there is no flash of the theme scale.
 */
function sds_enqueue_assets() {
	if ( ! sds_is_enabled() ) {
		return;
	}

	wp_enqueue_style(
		'sds-demo-scale',
		SDS_URL . 'assets/demo-scale.css',
		array(),
		SDS_VERSION
	);

	wp_enqueue_script(
		'sds-demo-scale',
		SDS_URL . 'assets/demo-scale.js',
		array(),
		SDS_VERSION,
		false
	);
	wp_add_inline_script(
		'sds-demo-scale',
		'window.sdsConfig = ' . wp_json_encode(
			array(
				'defaults' => sds_theme_defaults(),
				'ratios'   => array_keys( sds_ratio_choices() ),
				'baseMin'  => 12,
				'baseMax'  => 24,
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'sds_enqueue_assets' );

/**
 * Print the launcher button and the <dialog> just before </body>.
 */
function sds_render_modal() {
	if ( ! sds_is_enabled() ) {
		return;
	}
	$defaults = sds_theme_defaults();
	$steps    = array(
		'xxxl' => __( 'Display / H1', 'shitate-demo-scale' ),
		'xxl'  => __( 'Huge / H2', 'shitate-demo-scale' ),
		'xl'   => __( 'Extra Large / H3', 'shitate-demo-scale' ),
		'l'    => __( 'Large / H4', 'shitate-demo-scale' ),
		'm'    => __( 'Medium / Body', 'shitate-demo-scale' ),
		's'    => __( 'Small / H6', 'shitate-demo-scale' ),
		'xs'   => __( 'X-Small', 'shitate-demo-scale' ),
		'xxs'  => __( 'XX-Small', 'shitate-demo-scale' ),
	);
	?>
	<button type="button" class="sds-fab" data-sds-open aria-haspopup="dialog" aria-controls="sds-dialog" aria-label="<?php esc_attr_e( 'Try the type scale', 'shitate-demo-scale' ); ?>" title="<?php esc_attr_e( 'Try the type scale', 'shitate-demo-scale' ); ?>">
		<span class="sds-fab__glyph" aria-hidden="true">Aa</span>
	</button>

	<dialog id="sds-dialog" class="sds-dialog" aria-labelledby="sds-dialog-title">
		<div class="sds-dialog__inner">
			<header class="sds-dialog__header">
				<h2 id="sds-dialog-title" class="sds-dialog__title"><?php esc_html_e( 'Typography Scale', 'shitate-demo-scale' ); ?></h2>
				<button type="button" class="sds-dialog__close" data-sds-close aria-label="<?php esc_attr_e( 'Close', 'shitate-demo-scale' ); ?>">&times;</button>
			</header>

			<p class="sds-dialog__lead"><?php esc_html_e( 'Changes apply to this page instantly and are remembered in your browser only. Nothing is saved on the site.', 'shitate-demo-scale' ); ?></p>

			<div class="sds-field">
				<label class="sds-field__label" for="sds-ratio"><?php esc_html_e( 'Scale ratio', 'shitate-demo-scale' ); ?></label>
				<select id="sds-ratio" class="sds-field__select" data-sds-ratio>
					<?php foreach ( sds_ratio_choices() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $defaults['ratio'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="sds-field__help"><?php esc_html_e( 'Bigger ratio = more contrast between headings (like typescale.com).', 'shitate-demo-scale' ); ?></p>
			</div>

			<div class="sds-field">
				<label class="sds-field__label" for="sds-base">
					<?php esc_html_e( 'Base size', 'shitate-demo-scale' ); ?>
					<output class="sds-field__value" data-sds-base-out for="sds-base"><?php echo esc_html( $defaults['base'] ); ?>px</output>
				</label>
				<input id="sds-base" class="sds-field__range" type="range" min="12" max="24" step="1" value="<?php echo esc_attr( $defaults['base'] ); ?>" data-sds-base>
				<p class="sds-field__help"><?php esc_html_e( 'Body text size. The whole scale is built from this.', 'shitate-demo-scale' ); ?></p>
			</div>

			<div class="sds-field">
				<label class="sds-switch">
					<input type="checkbox" data-sds-round<?php checked( $defaults['round'] ); ?>>
					<span class="sds-switch__track" aria-hidden="true"></span>
					<span class="sds-switch__text"><?php esc_html_e( 'Apply rounding to font sizes', 'shitate-demo-scale' ); ?></span>
				</label>
				<p class="sds-field__help"><?php esc_html_e( 'Snaps every step to even pixels (2px) and makes headings fluid between a derived mobile ratio and the chosen ratio.', 'shitate-demo-scale' ); ?></p>
			</div>

			<table class="sds-steps" aria-live="polite">
				<caption class="sds-steps__caption"><?php esc_html_e( 'Resulting sizes at this viewport', 'shitate-demo-scale' ); ?></caption>
				<tbody>
					<?php foreach ( $steps as $slug => $label ) : ?>
						<tr class="sds-steps__row">
							<th scope="row" class="sds-steps__name"><?php echo esc_html( $label ); ?></th>
							<td class="sds-steps__sample"><span class="sds-steps__aa" data-sds-sample="<?php echo esc_attr( $slug ); ?>" aria-hidden="true">Aa</span></td>
							<td class="sds-steps__px"><output data-sds-step="<?php echo esc_attr( $slug ); ?>">–</output></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<footer class="sds-dialog__footer">
				<button type="button" class="sds-btn sds-btn--ghost" data-sds-reset><?php esc_html_e( 'Reset to theme settings', 'shitate-demo-scale' ); ?></button>
				<button type="button" class="sds-btn" data-sds-close><?php esc_html_e( 'Done', 'shitate-demo-scale' ); ?></button>
			</footer>
		</div>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'sds_render_modal', 20 );
