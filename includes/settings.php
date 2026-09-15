<?php
/**
 * Settings → shitate demo scale.
 *
 * One option array (`sds_settings`) managed through the Settings API:
 *   logged_in_only (bool) — show the launcher only to logged-in users.
 *   ga_id (string)        — GA4 measurement ID; empty = analytics off.
 *   ga_site_name (string) — value of the site_name parameter (default: path).
 *   ga_track_admins (bool) — also track logged-in editors.
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
		'logged_in_only'  => false,
		'ga_id'           => '',
		'ga_site_name'    => '',
		'ga_track_admins' => false,
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
		'logged_in_only'  => ! empty( $input['logged_in_only'] ),
		'ga_id'           => sds_sanitize_ga_id( isset( $input['ga_id'] ) ? $input['ga_id'] : '' ),
		'ga_site_name'    => isset( $input['ga_site_name'] ) ? sanitize_title( $input['ga_site_name'] ) : '',
		'ga_track_admins' => ! empty( $input['ga_track_admins'] ),
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

	add_settings_section(
		'sds_section_analytics',
		__( 'Analytics', 'shitate-demo-scale' ),
		'sds_section_analytics_text',
		'sds-settings'
	);
	add_settings_field(
		'sds_ga_id',
		__( 'GA4 measurement ID', 'shitate-demo-scale' ),
		'sds_field_ga_id',
		'sds-settings',
		'sds_section_analytics'
	);
	add_settings_field(
		'sds_ga_site_name',
		__( 'Site name', 'shitate-demo-scale' ),
		'sds_field_ga_site_name',
		'sds-settings',
		'sds_section_analytics'
	);
	add_settings_field(
		'sds_ga_track_admins',
		__( 'Logged-in users', 'shitate-demo-scale' ),
		'sds_field_ga_track_admins',
		'sds-settings',
		'sds_section_analytics'
	);
}

/**
 * Analytics section intro.
 */
function sds_section_analytics_text() {
	echo '<p>' . esc_html__( 'Send this site\'s traffic to the shared GA4 property. Every hit carries a site_name parameter; register it as an event-scoped custom dimension in GA4 to compare demo sites. The modal also sends demo_scale_open, demo_scale_change and demo_scale_save events.', 'shitate-demo-scale' ) . '</p>';
}

/**
 * Text: measurement ID.
 */
function sds_field_ga_id() {
	$settings = sds_get_settings();
	?>
	<input type="text" id="sds_ga_id" name="sds_settings[ga_id]" value="<?php echo esc_attr( $settings['ga_id'] ); ?>" class="regular-text code" placeholder="G-XXXXXXXXXX" pattern="G-[A-Za-z0-9]{4,16}">
	<p class="description"><?php esc_html_e( 'Leave empty to send nothing.', 'shitate-demo-scale' ); ?></p>
	<?php
}

/**
 * Text: site name.
 */
function sds_field_ga_site_name() {
	$settings = sds_get_settings();
	?>
	<input type="text" id="sds_ga_site_name" name="sds_settings[ga_site_name]" value="<?php echo esc_attr( $settings['ga_site_name'] ); ?>" class="regular-text code" placeholder="<?php echo esc_attr( sds_default_site_name() ); ?>">
	<p class="description">
		<?php
		printf(
			/* translators: %s: default site name derived from the URL */
			esc_html__( 'Value of the site_name parameter. Empty = %s (from the site URL).', 'shitate-demo-scale' ),
			'<code>' . esc_html( sds_default_site_name() ) . '</code>'
		);
		?>
	</p>
	<?php
}

/**
 * Checkbox: track admins.
 */
function sds_field_ga_track_admins() {
	$settings = sds_get_settings();
	?>
	<label for="sds_ga_track_admins">
		<input type="checkbox" id="sds_ga_track_admins" name="sds_settings[ga_track_admins]" value="1" <?php checked( $settings['ga_track_admins'] ); ?>>
		<?php esc_html_e( 'Also track logged-in users who can edit content', 'shitate-demo-scale' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Unchecked: your own editing sessions are left out of the numbers.', 'shitate-demo-scale' ); ?></p>
	<?php
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
