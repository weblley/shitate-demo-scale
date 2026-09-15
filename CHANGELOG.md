# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.3.2] - 2026-09-16

### Added
- `gtag('set', { site_name })` printed at wp_head priority 1, ahead of any gtag('config') — so when another plugin (Rank Math) loads GA4, its events including page_view carry the site name too. Needs no measurement ID; follows the editor-tracking switch; `sds_site_name_snippet_enabled` filter.

## [0.3.1] - 2026-09-15

### Fixed
- gtag.js now really loads async (the config snippet is printed before the loader; an "after" snippet made WordPress drop the async attribute).

## [0.3.0] - 2026-09-15

### Added
- Google Analytics 4 (Settings → shitate demo scale → Analytics): measurement ID, `site_name` parameter (defaults to the install's path segment, e.g. `demo-pro1`), and a switch to include logged-in editors (off by default). Loads gtag.js with `anonymize_ip`, independent of the active theme. `sds_analytics_enabled` filter.
- Modal events for GA4: `demo_scale_open`, `demo_scale_change` (ratio, ratio_mobile, base_size, rounding, fixed_small) and `demo_scale_save`, each with `site_name`.

## [0.2.1] - 2026-09-15

### Fixed
- Works on shitate 0.4.0–0.4.2 too: the override now restates `--st-r` (fallback to `--st-ratio` when the theme has no fluid ratio), so text steps no longer collapse on themes older than 0.4.3.

## [0.2.0] - 2026-09-15

### Added
- "Scale ratio on small screens" select (Auto / the same 8 ratios), mirroring the theme's `shitate_ratio_mobile` setting: the low end of the fluid ratio at 375px.
- "Save to theme settings": logged-in users with `edit_theme_options` get a button that writes the modal's values into the theme's Customizer settings (`POST sds/v1/theme-scale`, nonce + capability checked). Visitors without that right never see it.

### Changed
- Override CSS follows theme 0.4.3: every step is the base times a power of the fluid ratio `--st-r`; "Apply rounding" now only snaps to 2px. Verified identical to the theme's output for all 8 ratio-mobile × rounding × fixed-small combinations.

## [0.1.0] - 2026-09-12

### Added
- Settings → shitate demo scale: "Show the button only to logged-in users" (default off = every visitor). "Settings" link on the Plugins row; option removed on uninstall.
- "Fixed sizes for small text" switch, mirroring the theme's Customizer option: when on, Small / X-Small / XX-Small are pinned to 0.95 / 0.8 / 0.75rem; when off they divide by the ratio (2px-snapped in rounding mode), exactly as the theme does.
- Floating "Aa" launcher and a `<dialog>` modal that lets any visitor (logged in or not) change the shitate theme's Typography Scale: ratio (8 musical intervals), base size (12–24px) and "Apply rounding to font sizes".
- Live table of the resulting pixel size for every step (Display → XX-Small) at the current viewport, with an "Aa" sample per step.
- Settings persist in the visitor's `localStorage` only and are re-applied before first paint; "Reset to theme settings" restores the site's Customizer values. Nothing is written on the server.
- Renders only on the front end and only while the shitate theme is active; `sds_enabled` filter to switch it off.
- GitHub-based updates (`Update URI` + `update_plugins_github.com`), so every demo site updates from this repo's latest Release.
- Japanese translation.
