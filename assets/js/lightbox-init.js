/**
 * Paulus: send every image in the text that links to its own file through
 * Lightbox2, grouped with the theme's figures, captioned from its figcaption.
 * The theme's [paulus_figure] images carry the attributes already.
 */
( function () {
	'use strict';
	// The theme's Lightbox2 settings (label, wrap-around, timings), from
	// functions.php. Both scripts are deferred and run in order, so
	// window.lightbox exists here.
	var cfg = window.paulusLightbox || {};
	if ( cfg.options && window.lightbox && window.lightbox.option ) {
		window.lightbox.option( cfg.options );
	}
	var sel = [ 'jpg', 'jpeg', 'png', 'webp', 'avif', 'gif' ].map( function ( x ) {
		return '.paulus-prose a[href$=".' + x + '" i]';
	} ).join( ', ' );
	document.querySelectorAll( sel ).forEach( function ( a ) {
		var img = a.querySelector( 'img' );
		if ( ! img || a.hasAttribute( 'data-lightbox' ) ) {
			return;
		}
		var fig = a.closest( 'figure' );
		var cap = fig && fig.querySelector( 'figcaption' );
		a.setAttribute( 'data-lightbox', 'paulus-figures' );
		a.setAttribute( 'data-title', cap ? cap.textContent.trim() : ( img.alt || '' ) );
		a.removeAttribute( 'target' );
	} );
	// The large image takes the thumbnail's alt text (Lightbox2 reads data-alt).
	document.querySelectorAll( 'a[data-lightbox]' ).forEach( function ( a ) {
		var img = a.querySelector( 'img' );
		if ( img && img.alt && ! a.hasAttribute( 'data-alt' ) ) {
			a.setAttribute( 'data-alt', img.alt );
		}
	} );
} )();

/*
 * Accessibility for the viewer (WCAG 2.4.3, 4.1.2). Lightbox2 names no close
 * control, and after each image loads it puts focus on its overlay, which
 * sits outside the dialog. Name the close control. When the viewer opens,
 * move that focus to Close; when Previous, Next or a key changes the image,
 * give focus back to the control the reader was on. Lightbox2 already traps
 * Tab, closes on Escape and returns focus to the link that opened it.
 */
( function () {
	'use strict';
	var l10n = window.paulusLightbox || {};
	var last = null;
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest && e.target.closest( 'a[data-lightbox]' ) ) {
			last = null; // A fresh opening.
		}
	}, true );
	// Previous and Next are links with no address, so Enter and Space did
	// nothing on them (WCAG 2.1.1). Pass those keys on as a click.
	document.addEventListener( 'keydown', function ( e ) {
		var t = e.target;
		if ( ( 'Enter' === e.key || ' ' === e.key ) && t.classList && t.closest && t.closest( '#lightbox' ) && ( t.classList.contains( 'lb-prev' ) || t.classList.contains( 'lb-next' ) ) ) {
			e.preventDefault();
			t.click();
		}
	} );
	document.addEventListener( 'focusin', function ( e ) {
		var t = e.target;
		if ( ! t.classList ) {
			return;
		}
		if ( t.closest && t.closest( '#lightbox' ) && ( t.classList.contains( 'lb-prev' ) || t.classList.contains( 'lb-next' ) || t.classList.contains( 'lb-close' ) ) ) {
			last = t;
			return;
		}
		if ( ! t.classList.contains( 'lightboxOverlay' ) ) {
			return;
		}
		var close = document.querySelector( '#lightbox .lb-close' );
		if ( ! close ) {
			return;
		}
		close.setAttribute( 'aria-label', l10n.close || 'Close' );
		var target = last || close;
		// The controls fade in just after the overlay takes focus; wait
		// until the chosen one can be focused, unless the reader moved on.
		var tries = 0;
		( function move() {
			if ( document.activeElement !== t ) {
				return;
			}
			if ( target.getClientRects().length ) {
				target.focus( { preventScroll: true } );
			} else if ( tries++ < 40 ) {
				setTimeout( move, 25 );
			}
		} )();
	} );
} )();
