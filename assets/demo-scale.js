/**
 * shitate demo scale — front-end behaviour.
 *
 * Re-implements the theme's shitate_scale_inline_css() in the browser so any
 * visitor can try ratio / base size / rounding without logging in. The
 * override is a <style> appended to <head> (after the theme's own inline
 * scale CSS, so it wins on equal :root specificity) and is remembered in
 * localStorage for this browser only. Nothing is sent to the server.
 *
 * Keep the two CSS blocks below in sync with the theme's functions.php
 * (shitate_scale_inline_css) and assets/css/tokens.css.
 */
( function () {
	var cfg = window.sdsConfig || {};
	var STORAGE_KEY = 'shitate-demo-scale';
	var STYLE_ID = 'sds-override';
	var RATIOS = cfg.ratios || [ '1.067', '1.125', '1.2', '1.25', '1.333', '1.414', '1.5', '1.618' ];
	var RATIOS_MOBILE = cfg.ratiosMobile || [ 'auto' ].concat( RATIOS );
	var I18N = window.sdsI18n || {};
	var BASE_MIN = cfg.baseMin || 12;
	var BASE_MAX = cfg.baseMax || 24;
	var DEFAULTS = cfg.defaults || { ratio: '1.25', ratioMobile: 'auto', base: 16, round: true, fixedSmall: false };
	var STEPS = [ 'xxxl', 'xxl', 'xl', 'l', 'm', 's', 'xs', 'xxs' ];

	// Rounded mode — mirrors the theme's Customizer "round" branch. Since theme
	// 0.4.3 fluidity lives in the ratio itself (tokens.css derives --st-r from
	// --st-ratio / --st-ratio-min), so every step is simply the base times a
	// power of --st-r, snapped to 2px.
	// Down-scale (only when small text is not pinned).
	var ROUND_SMALL =
		'--st-text-s:round(nearest, calc(var(--st-text-m) / var(--st-r)), 2px);' +
		'--st-text-xs:round(nearest, calc(var(--st-text-m) / var(--st-r) / var(--st-r)), 2px);' +
		'--st-text-xxs:round(nearest, calc(var(--st-text-m) / var(--st-r) / var(--st-r) / var(--st-r)), 2px);';
	var ROUND_UP =
		'--st-text-l:round(nearest, calc(var(--st-text-m) * var(--st-r)), 2px);' +
		'--st-text-xl:round(nearest, calc(var(--st-text-m) * var(--st-r) * var(--st-r)), 2px);' +
		'--st-text-xxl:round(nearest, calc(var(--st-text-m) * var(--st-r) * var(--st-r) * var(--st-r)), 2px);' +
		'--st-text-xxxl:round(nearest, calc(var(--st-text-m) * var(--st-r) * var(--st-r) * var(--st-r) * var(--st-r)), 2px);';

	// Raw modular chain — what tokens.css declares when rounding is off.
	var RAW_CSS =
		':root{' +
		'--st-text-s:calc(var(--st-text-m) / var(--st-r));' +
		'--st-text-xs:calc(var(--st-text-m) / var(--st-r) / var(--st-r));' +
		'--st-text-xxs:calc(var(--st-text-m) / var(--st-r) / var(--st-r) / var(--st-r));' +
		'--st-text-l:calc(var(--st-text-m) * var(--st-r));' +
		'--st-text-xl:calc(var(--st-text-m) * var(--st-r) * var(--st-r));' +
		'--st-text-xxl:calc(var(--st-text-m) * var(--st-r) * var(--st-r) * var(--st-r));' +
		'--st-text-xxxl:calc(var(--st-text-m) * var(--st-r) * var(--st-r) * var(--st-r) * var(--st-r));' +
		'}';

	// "Fixed sizes for small text" — emitted last so it wins over either chain.
	var FIXED_SMALL_CSS = ':root{--st-text-s:0.95rem;--st-text-xs:0.8rem;--st-text-xxs:0.75rem;}';

	// Small-screen ratio "auto" = the tokens.css default; restated because the
	// theme's inline CSS may have pinned --st-ratio-min to a fixed value.
	var RATIO_MIN_AUTO = '--st-ratio-min:calc((1 + var(--st-ratio, 1.25)) / 2);';

	// The working ratio every step is computed from. Theme 0.4.3+ defines
	// --st-r as the fluid ratio (--st-ratio-fluid); older themes have neither,
	// so restating it with a fallback to --st-ratio keeps the override valid
	// there (static ratio instead of fluid). Identical on 0.4.3+.
	var ST_R = '--st-r:var(--st-ratio-fluid, var(--st-ratio));';

	/* ---------- state ---------- */

	function sanitize( raw ) {
		if ( ! raw || typeof raw !== 'object' ) {
			return null;
		}
		var ratio = String( raw.ratio );
		var ratioMobile = String( raw.ratioMobile || 'auto' );
		var base = parseInt( raw.base, 10 );
		if ( RATIOS.indexOf( ratio ) === -1 ) {
			ratio = DEFAULTS.ratio;
		}
		if ( RATIOS_MOBILE.indexOf( ratioMobile ) === -1 ) {
			ratioMobile = DEFAULTS.ratioMobile || 'auto';
		}
		if ( isNaN( base ) || base < BASE_MIN || base > BASE_MAX ) {
			base = DEFAULTS.base;
		}
		return { ratio: ratio, ratioMobile: ratioMobile, base: base, round: !! raw.round, fixedSmall: !! raw.fixedSmall };
	}

	function load() {
		try {
			return sanitize( JSON.parse( window.localStorage.getItem( STORAGE_KEY ) ) );
		} catch ( e ) {
			return null;
		}
	}

	function save( state ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
		} catch ( e ) {}
	}

	function clear() {
		try {
			window.localStorage.removeItem( STORAGE_KEY );
		} catch ( e ) {}
	}

	function isDefault( state ) {
		return (
			state.ratio === String( DEFAULTS.ratio ) &&
			state.ratioMobile === String( DEFAULTS.ratioMobile || 'auto' ) &&
			state.base === parseInt( DEFAULTS.base, 10 ) &&
			state.round === !! DEFAULTS.round &&
			state.fixedSmall === !! DEFAULTS.fixedSmall
		);
	}

	/* ---------- CSS override ---------- */

	function buildCss( state ) {
		var css =
			':root{--st-ratio:' + state.ratio + ';--st-text-m:' + state.base + 'px;' +
			( state.ratioMobile === 'auto' ? RATIO_MIN_AUTO : '--st-ratio-min:' + state.ratioMobile + ';' ) +
			ST_R +
			'}';
		if ( state.round ) {
			css += ':root{' + ( state.fixedSmall ? '' : ROUND_SMALL ) + ROUND_UP + '}';
		} else {
			css += RAW_CSS;
		}
		if ( state.fixedSmall ) {
			css += FIXED_SMALL_CSS;
		}
		return css;
	}

	function apply( state ) {
		var el = document.getElementById( STYLE_ID );
		if ( ! el ) {
			el = document.createElement( 'style' );
			el.id = STYLE_ID;
		}
		el.textContent = buildCss( state );
		// Always last in <head> so it beats the theme's inline scale CSS.
		document.head.appendChild( el );
	}

	function remove() {
		var el = document.getElementById( STYLE_ID );
		if ( el && el.parentNode ) {
			el.parentNode.removeChild( el );
		}
	}

	// Re-apply before first paint so there is no flash of the theme scale.
	var current = load();
	if ( current && ! isDefault( current ) ) {
		apply( current );
	} else {
		current = null;
	}

	/* ---------- UI ---------- */

	function initUi() {
		var dialog = document.getElementById( 'sds-dialog' );
		var fab = document.querySelector( '[data-sds-open]' );
		if ( ! dialog || ! fab ) {
			return;
		}

		var ratioEl = dialog.querySelector( '[data-sds-ratio]' );
		var ratioMobileEl = dialog.querySelector( '[data-sds-ratio-mobile]' );
		var baseEl = dialog.querySelector( '[data-sds-base]' );
		var baseOut = dialog.querySelector( '[data-sds-base-out]' );
		var roundEl = dialog.querySelector( '[data-sds-round]' );
		var fixedSmallEl = dialog.querySelector( '[data-sds-fixed-small]' );
		var resetBtn = dialog.querySelector( '[data-sds-reset]' );
		var stepOuts = {};
		var samples = {};
		STEPS.forEach( function ( slug ) {
			stepOuts[ slug ] = dialog.querySelector( '[data-sds-step="' + slug + '"]' );
			samples[ slug ] = dialog.querySelector( '[data-sds-sample="' + slug + '"]' );
		} );

		// Hidden probes: one span per step, sized with the live token, so the
		// table shows the real computed pixel value at this viewport.
		var probe = document.createElement( 'div' );
		probe.className = 'sds-probe';
		probe.setAttribute( 'aria-hidden', 'true' );
		var probes = {};
		STEPS.forEach( function ( slug ) {
			var span = document.createElement( 'span' );
			span.style.fontSize = 'var(--st-text-' + slug + ')';
			span.textContent = 'A';
			probe.appendChild( span );
			probes[ slug ] = span;
		} );
		document.body.appendChild( probe );

		function readState() {
			return sanitize( {
				ratio: ratioEl.value,
				ratioMobile: ratioMobileEl.value,
				base: baseEl.value,
				round: roundEl.checked,
				fixedSmall: fixedSmallEl.checked,
			} );
		}

		function writeControls( state ) {
			ratioEl.value = state.ratio;
			ratioMobileEl.value = state.ratioMobile;
			baseEl.value = state.base;
			baseOut.value = state.base + 'px';
			roundEl.checked = state.round;
			fixedSmallEl.checked = state.fixedSmall;
		}

		function formatPx( px ) {
			var n = parseFloat( px );
			if ( isNaN( n ) ) {
				return '–';
			}
			// Number → string already drops trailing zeros (30 → "30", 25.89 → "25.89").
			return String( Math.round( n * 100 ) / 100 ) + 'px';
		}

		function refreshTable() {
			STEPS.forEach( function ( slug ) {
				var size = window.getComputedStyle( probes[ slug ] ).fontSize;
				if ( stepOuts[ slug ] ) {
					stepOuts[ slug ].value = formatPx( size );
				}
				if ( samples[ slug ] ) {
					// Preview glyph: real size, capped so the modal stays compact.
					samples[ slug ].style.fontSize = Math.min( parseFloat( size ) || 16, 40 ) + 'px';
				}
			} );
		}

		function markFab( active ) {
			fab.classList.toggle( 'is-active', !! active );
		}

		function onChange() {
			var state = readState();
			baseOut.value = state.base + 'px';
			if ( isDefault( state ) ) {
				remove();
				clear();
				current = null;
			} else {
				apply( state );
				save( state );
				current = state;
			}
			markFab( current );
			refreshTable();
		}

		ratioEl.addEventListener( 'change', onChange );
		ratioMobileEl.addEventListener( 'change', onChange );
		baseEl.addEventListener( 'input', onChange );
		roundEl.addEventListener( 'change', onChange );
		fixedSmallEl.addEventListener( 'change', onChange );

		resetBtn.addEventListener( 'click', function () {
			writeControls( sanitize( DEFAULTS ) );
			onChange();
		} );

		// "Save to theme settings" (logged-in users with edit_theme_options).
		var saveBtn = dialog.querySelector( '[data-sds-save]' );
		var saveStatus = dialog.querySelector( '[data-sds-save-status]' );
		if ( saveBtn && cfg.canSave && cfg.saveUrl && window.fetch ) {
			saveBtn.addEventListener( 'click', function () {
				var state = readState();
				saveBtn.disabled = true;
				saveStatus.textContent = I18N.saving || '…';
				window
					.fetch( cfg.saveUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': cfg.nonce,
						},
						body: JSON.stringify( state ),
					} )
					.then( function ( res ) {
						if ( ! res.ok ) {
							throw new Error( res.status );
						}
						return res.json();
					} )
					.then( function ( data ) {
						// The theme now serves these values, so the browser override
						// is no longer a deviation: forget it. The injected CSS stays
						// until the next page load, where the theme takes over.
						DEFAULTS = sanitize( data.defaults || state );
						clear();
						current = null;
						markFab( false );
						saveStatus.textContent = I18N.saved || 'OK';
					} )
					.catch( function () {
						saveStatus.textContent = I18N.failed || 'Error';
					} )
					.then( function () {
						saveBtn.disabled = false;
					} );
			} );
		}

		function open() {
			if ( typeof dialog.showModal === 'function' ) {
				dialog.showModal();
			} else {
				dialog.setAttribute( 'open', '' );
			}
			refreshTable();
		}

		fab.addEventListener( 'click', open );
		dialog.querySelectorAll( '[data-sds-close]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				dialog.close();
			} );
		} );
		// Backdrop click = the dialog element itself.
		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );
		dialog.addEventListener( 'close', function () {
			fab.focus();
		} );

		// Fluid steps change with the viewport; keep the table honest.
		var raf = 0;
		window.addEventListener( 'resize', function () {
			if ( ! dialog.open ) {
				return;
			}
			window.cancelAnimationFrame( raf );
			raf = window.requestAnimationFrame( refreshTable );
		} );

		writeControls( current || sanitize( DEFAULTS ) );
		markFab( current );
		refreshTable();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initUi );
	} else {
		initUi();
	}
} )();
