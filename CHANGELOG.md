# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.1.0] - 2026-09-12

### Added
- "Fixed sizes for small text" switch, mirroring the theme's Customizer option: when on, Small / X-Small / XX-Small are pinned to 0.95 / 0.8 / 0.75rem; when off they divide by the ratio (2px-snapped in rounding mode), exactly as the theme does.
- Floating "Aa" launcher and a `<dialog>` modal that lets any visitor (logged in or not) change the shitate theme's Typography Scale: ratio (8 musical intervals), base size (12–24px) and "Apply rounding to font sizes".
- Live table of the resulting pixel size for every step (Display → XX-Small) at the current viewport, with an "Aa" sample per step.
- Settings persist in the visitor's `localStorage` only and are re-applied before first paint; "Reset to theme settings" restores the site's Customizer values. Nothing is written on the server.
- Renders only on the front end and only while the shitate theme is active; `sds_enabled` filter to switch it off.
- GitHub-based updates (`Update URI` + `update_plugins_github.com`), so every demo site updates from this repo's latest Release.
- Japanese translation.
