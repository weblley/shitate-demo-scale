<?php
/**
 * GitHub-based plugin updates.
 *
 * The "Update URI" header in the main file routes core's update check to the
 * update_plugins_github.com filter below, which answers with the repo's
 * latest GitHub Release (asset: shitate-demo-scale.zip). Core compares
 * versions itself, so every demo site that has this plugin installed gets the
 * usual "update available" notice and one-click update under Plugins.
 *
 * Same mechanism the shitate theme used before WordPress.org distribution.
 *
 * @package ShitateDemoScale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch the latest GitHub release, cached in a site transient.
 *
 * @return array|null Array with version / url / package keys, or null.
 */
function sds_github_latest_release() {
	$cached = get_site_transient( 'sds_github_release' );
	if ( is_array( $cached ) ) {
		return empty( $cached ) ? null : $cached; // Empty array = cached failure.
	}

	$headers = array( 'Accept' => 'application/vnd.github+json' );
	if ( defined( 'SDS_GITHUB_TOKEN' ) && SDS_GITHUB_TOKEN ) {
		$headers['Authorization'] = 'Bearer ' . SDS_GITHUB_TOKEN; // Only needed if the repo goes private.
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/' . SDS_GITHUB_REPO . '/releases/latest',
		array(
			'timeout' => 10,
			'headers' => $headers,
		)
	);

	$release = null;
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$package = '';
		foreach ( (array) ( isset( $body['assets'] ) ? $body['assets'] : array() ) as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && SDS_GITHUB_ASSET === $asset['name'] ) {
				$package = $asset['browser_download_url'];
				break;
			}
		}

		if ( ! empty( $body['tag_name'] ) && $package ) {
			$release = array(
				'version' => ltrim( (string) $body['tag_name'], 'vV' ),
				'url'     => isset( $body['html_url'] ) ? (string) $body['html_url'] : 'https://github.com/' . SDS_GITHUB_REPO . '/releases',
				'package' => $package,
			);
		}
	}

	// Cache success for 6 hours; cache failures briefly so a flaky network
	// or rate limit does not hammer the API on every admin load.
	set_site_transient( 'sds_github_release', $release ? $release : array(), $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );

	return $release;
}

/**
 * Answer core's update check for plugins whose Update URI is on github.com.
 *
 * @param array|false $update      Update data so far (false = none).
 * @param array       $plugin_data Plugin headers.
 * @param string      $plugin_file Plugin basename being checked.
 * @param string[]    $locales     Installed locales (unused).
 * @return array|false
 */
function sds_github_plugin_update( $update, $plugin_data, $plugin_file, $locales ) {
	if ( SDS_BASENAME !== $plugin_file ) {
		return $update;
	}

	$release = sds_github_latest_release();
	if ( ! $release ) {
		return $update;
	}

	return array(
		'id'           => 'github.com/' . SDS_GITHUB_REPO,
		'slug'         => dirname( SDS_BASENAME ),
		'plugin'       => $plugin_file,
		'version'      => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires'     => isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '',
		'requires_php' => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
	);
}
add_filter( 'update_plugins_github.com', 'sds_github_plugin_update', 10, 4 );

/**
 * Drop the release cache right after this plugin is updated, so the Plugins
 * screen does not keep offering the version that was just installed.
 *
 * @param WP_Upgrader $upgrader Upgrader instance.
 * @param array       $options  Upgrade context.
 */
function sds_github_flush_cache( $upgrader, $options ) {
	if ( 'plugin' !== ( $options['type'] ?? '' ) ) {
		return;
	}
	$plugins = (array) ( $options['plugins'] ?? array() );
	if ( in_array( SDS_BASENAME, $plugins, true ) ) {
		delete_site_transient( 'sds_github_release' );
	}
}
add_action( 'upgrader_process_complete', 'sds_github_flush_cache', 10, 2 );
