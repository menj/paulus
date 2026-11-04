/* Paulus Theme Options: accessible tabs (arrow keys, Home and End, URL-hash
 * memory), live slider values, the core colour picker, and a live preview of
 * the colour scheme on the screen itself. No globals. */
( function ( $ ) {
	'use strict';

	var tabs = Array.prototype.slice.call( document.querySelectorAll( '.paulus-tab' ) );
	var save = document.querySelector( '.paulus-save' );
	var referer = document.querySelector( '.paulus-form input[name="_wp_http_referer"]' );
	if ( ! tabs.length ) {
		return;
	}

	function activate( tab, focus ) {
		tabs.forEach( function ( t ) {
			var panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
			var on = t === tab;
			t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			t.tabIndex = on ? 0 : -1;
			if ( panel ) {
				panel.hidden = ! on;
			}
		} );
		if ( save ) {
			save.hidden = tab.id === 'tab-content';
		}
		if ( focus ) {
			tab.focus();
		}
		if ( tab.scrollIntoView ) {
			tab.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
		}
		history.replaceState( null, '', '#' + tab.id.replace( 'tab-', '' ) );
		// The fragment is not sent with the save, so name the tab in the
		// address WordPress returns to.
		if ( referer && window.URL ) {
			var back = new URL( referer.value, window.location.href );
			back.searchParams.set( 'tab', tab.id.replace( 'tab-', '' ) );
			referer.value = back.pathname + back.search;
		}
		loadLazy( document.getElementById( tab.getAttribute( 'aria-controls' ) ) );
	}

	// A heavy panel (Content) is built only when the screen opens on its
	// ?tab=; otherwise fetch it the first time its tab is shown. The
	// placeholder link opens that ?tab= if the request fails.
	function loadLazy( panel ) {
		var box = panel ? panel.querySelector( '[data-paulus-lazy]' ) : null;
		if ( ! box || box.getAttribute( 'data-loading' ) || ! window.fetch || ! window.ajaxurl ) {
			return;
		}
		box.setAttribute( 'data-loading', '1' );
		box.setAttribute( 'aria-busy', 'true' );
		var body = new URLSearchParams();
		body.append( 'action', box.getAttribute( 'data-paulus-lazy' ) );
		body.append( 'nonce', box.getAttribute( 'data-nonce' ) );
		window.fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					throw new Error( 'failed' );
				}
				box.outerHTML = res.data.html;
			} )
			.catch( function () {
				box.removeAttribute( 'data-loading' );
				box.removeAttribute( 'aria-busy' );
			} );
	}

	tabs.forEach( function ( tab, i ) {
		tab.addEventListener( 'click', function () {
			activate( tab, false );
		} );
		tab.addEventListener( 'keydown', function ( e ) {
			var next = null;
			if ( e.key === 'ArrowRight' ) {
				next = tabs[ ( i + 1 ) % tabs.length ];
			} else if ( e.key === 'ArrowLeft' ) {
				next = tabs[ ( i - 1 + tabs.length ) % tabs.length ];
			} else if ( e.key === 'Home' ) {
				next = tabs[ 0 ];
			} else if ( e.key === 'End' ) {
				next = tabs[ tabs.length - 1 ];
			}
			if ( next ) {
				e.preventDefault();
				activate( next, true );
			}
		} );
	} );

	var start = document.getElementById( 'tab-' + window.location.hash.slice( 1 ) );
	if ( ! start && window.URLSearchParams ) {
		start = document.getElementById( 'tab-' + ( new URLSearchParams( window.location.search ).get( 'tab' ) || '' ) );
	}
	if ( ! start ) {
		start = tabs.filter( function ( t ) {
			return t.getAttribute( 'aria-selected' ) === 'true';
		} )[ 0 ];
	}
	activate( start || tabs[ 0 ], false );

	// Each slider shows its value as it moves.
	document.querySelectorAll( '.paulus-range input[type="range"]' ).forEach( function ( input ) {
		var out = document.getElementById( input.id + '-value' );
		input.addEventListener( 'input', function () {
			if ( out ) {
				out.textContent = input.value;
			}
		} );
	} );

	// The core colour picker; an empty field still means "follow the scheme".
	if ( $ && $.fn && $.fn.wpColorPicker ) {
		$( '.paulus-color' ).wpColorPicker();
	}

	// The screen follows the colour scheme as soon as one is picked, before
	// saving, so the choice can be judged in place.
	var wrap = document.querySelector( '.paulus-wrap' );
	document.querySelectorAll( '.paulus-swatch input' ).forEach( function ( input ) {
		input.addEventListener( 'change', function () {
			if ( ! wrap || ! input.checked ) {
				return;
			}
			wrap.className = wrap.className.replace( /\bpaulus-wrap--[a-z]+\b/, '' ).trim() + ' paulus-wrap--' + input.value;
		} );
	} );
}( window.jQuery ) );
