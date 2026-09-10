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
	var BASE_MIN = cfg.baseMin || 12;
	var BASE_MAX = cfg.baseMax || 24;
	var DEFAULTS = cfg.defaults || { ratio: '1.25', base: 16, round: true };
	var STEPS = [ 'xxxl', 'xxl', 'xl', 'l', 'm', 's', 'xs', 'xxs' ];

	// Rounded / fluid mode — mirrors the theme's Customizer "round" branch.
	var ROUND_CSS =
		':root{' +
		'--st-ratio-min:calc((1 + var(--st-ratio)) / 2);' +
		'--st-l-max:calc(var(--st-text-m) * var(--st-ratio));' +
		'--st-xl-max:calc(var(--st-l-max) * var(--st-ratio));' +
		'--st-xxl-max:calc(var(--st-xl-max) * var(--st-ratio));' +
		'--st-xxxl-max:calc(var(--st-xxl-max) * var(--st-ratio));' +
		'--st-l-min:calc(var(--st-text-m) * var(--st-ratio-min));' +
		'--st-xl-min:calc(var(--st-l-min) * var(--st-ratio-min));' +
		'--st-xxl-min:calc(var(--st-xl-min) * var(--st-ratio-min));' +
		'--st-xxxl-min:calc(var(--st-xxl-min) * var(--st-ratio-min));' +
		'--st-text-l:round(nearest, clamp(var(--st-l-min), calc(var(--st-l-min) + 0.3vw), var(--st-l-max)), 2px);' +
		'--st-text-xl:round(nearest, clamp(var(--st-xl-min), calc(var(--st-xl-min) + 0.7vw), var(--st-xl-max)), 2px);' +
		'--st-text-xxl:round(nearest, clamp(var(--st-xxl-min), calc(var(--st-xxl-min) + 1.1vw), var(--st-xxl-max)), 2px);' +
		'--st-text-xxxl:round(nearest, clamp(var(--st-xxxl-min), calc(var(--st-xxxl-min) + 1.6vw), var(--st-xxxl-max)), 2px);' +
		'}';

	// Raw modular chain — what tokens.css declares when rounding is off.
	var RAW_CSS =
		':root{' +
		'--st-text-l:calc(var(--st-text-m) * var(--st-ratio));' +
		'--st-text-xl:calc(var(--st-text-l) * var(--st-ratio));' +
		'--st-text-xxl:calc(var(--st-text-xl) * var(--st-ratio));' +
		'--st-text-xxxl:calc(var(--st-text-xxl) * var(--st-ratio));' +
		'}';

	/* ---------- state ---------- */

	function sanitize( raw ) {
		if ( ! raw || typeof raw !== 'object' ) {
			return null;
		}
		var ratio = String( raw.ratio );
		var base = parseInt( raw.base, 10 );
		if ( RATIOS.indexOf( ratio ) === -1 ) {
			ratio = DEFAULTS.ratio;
		}
		if ( isNaN( base ) || base < BASE_MIN || base > BASE_MAX ) {
			base = DEFAULTS.base;
		}
		return { ratio: ratio, base: base, round: !! raw.round };
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
			state.base === parseInt( DEFAULTS.base, 10 ) &&
			state.round === !! DEFAULTS.round
		);
	}

	/* ---------- CSS override ---------- */

	function buildCss( state ) {
		return (
			':root{--st-ratio:' + state.ratio + ';--st-text-m:' + state.base + 'px;}' +
			( state.round ? ROUND_CSS : RAW_CSS )
		);
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
		var baseEl = dialog.querySelector( '[data-sds-base]' );
		var baseOut = dialog.querySelector( '[data-sds-base-out]' );
		var roundEl = dialog.querySelector( '[data-sds-round]' );
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
				base: baseEl.value,
				round: roundEl.checked,
			} );
		}

		function writeControls( state ) {
			ratioEl.value = state.ratio;
			baseEl.value = state.base;
			baseOut.value = state.base + 'px';
			roundEl.checked = state.round;
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
		baseEl.addEventListener( 'input', onChange );
		roundEl.addEventListener( 'change', onChange );

		resetBtn.addEventListener( 'click', function () {
			writeControls( sanitize( DEFAULTS ) );
			onChange();
		} );

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
