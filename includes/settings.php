<?php
/**
 * Settings → shitate demo scale.
 *
 * One option array (`sds_settings`) managed through the Settings API:
 *   logged_in_only (bool) — show the launcher only to logged-in users.
 *
 * @package ShitateDemoScale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default settings.
 *
 * @return array{logged_in_only:bool}
 */
function sds_default_settings() {
	return array(
		'logged_in_only' => false,
	);
}

/**
 * Current settings merged over the defaults.
 *
 * @return array{logged_in_only:bool}
 */
function sds_get_settings() {
	$saved = get_option( 'sds_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), sds_default_settings() );
}

/**
 * Sanitize the settings array on save.
 *
 * @param mixed $input Raw form input.
 * @return array{logged_in_only:bool}
 */
function sds_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	return array(
		'logged_in_only' => ! empty( $input['logged_in_only'] ),
	);
}

/**
 * Register the option, section and fields.
 */
function sds_register_settings() {
	register_setting(
		'sds_settings_group',
		'sds_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'sds_sanitize_settings',
			'default'           => sds_default_settings(),
		)
	);

	add_settings_section(
		'sds_section_display',
		__( 'Display', 'shitate-demo-scale' ),
		'sds_section_display_text',
		'sds-settings'
	);

	add_settings_field(
		'sds_logged_in_only',
		__( 'Launcher button', 'shitate-demo-scale' ),
		'sds_field_logged_in_only',
		'sds-settings',
		'sds_section_display'
	);
}
add_action( 'admin_init', 'sds_register_settings' );

/**
 * Section intro.
 */
function sds_section_display_text() {
	echo '<p>' . esc_html__( 'The "Aa" button and the Typography Scale modal appear on the front end while the shitate theme is active.', 'shitate-demo-scale' ) . '</p>';
}

/**
 * Checkbox: logged-in only.
 */
function sds_field_logged_in_only() {
	$settings = sds_get_settings();
	?>
	<label for="sds_logged_in_only">
		<input type="checkbox" id="sds_logged_in_only" name="sds_settings[logged_in_only]" value="1" <?php checked( $settings['logged_in_only'] ); ?>>
		<?php esc_html_e( 'Show the button only to logged-in users', 'shitate-demo-scale' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Unchecked: every visitor can open the modal (demo-site behaviour). Checked: only logged-in users see the button — handy while a site is public but not yet meant as a demo.', 'shitate-demo-scale' ); ?></p>
	<?php
}

/**
 * Add the page under Settings.
 */
function sds_add_settings_page() {
	add_options_page(
		__( 'shitate demo scale', 'shitate-demo-scale' ),
		__( 'shitate demo scale', 'shitate-demo-scale' ),
		'manage_options',
		'sds-settings',
		'sds_render_settings_page'
	);
}
add_action( 'admin_menu', 'sds_add_settings_page' );

/**
 * Render the page.
 */
function sds_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'shitate demo scale', 'shitate-demo-scale' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'sds_settings_group' );
			do_settings_sections( 'sds-settings' );
			submit_button();
			?>
		</form>
		<?php if ( 'shitate' !== get_template() ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The shitate theme is not active, so the button is currently hidden regardless of this setting.', 'shitate-demo-scale' ); ?></p></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * "Settings" link on the Plugins list row.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function sds_plugin_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=sds-settings' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'shitate-demo-scale' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . SDS_BASENAME, 'sds_plugin_action_links' );
