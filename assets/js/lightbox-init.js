/**
 * Paulus: send every image in the text that links to its own file through
 * Lightbox2, grouped with the theme's figures, captioned from its figcaption.
 * The theme's [paulus_figure] images carry the attributes already.
 */
( function () {
	'use strict';
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
 * control, and after each image it puts focus on its overlay, which sits
 * outside the dialog. Name the close control, and send focus that lands on
 * the overlay to it, inside the dialog, once it can take focus. Lightbox2 already traps Tab, closes
 * on Escape and returns focus to the link that opened it.
 */
( function () {
	'use strict';
	document.addEventListener( 'focusin', function ( e ) {
		if ( ! e.target.classList || ! e.target.classList.contains( 'lightboxOverlay' ) ) {
			return;
		}
		var close = document.querySelector( '#lightbox .lb-close' );
		if ( ! close ) {
			return;
		}
		close.setAttribute( 'aria-label', 'Close' );
		// The caption bar, which holds the close control, fades in just
		// after the overlay takes focus; wait until it can be focused.
		var tries = 0;
		( function move() {
			if ( close.getClientRects().length && document.activeElement === e.target ) {
				close.focus( { preventScroll: true } );
			} else if ( document.activeElement === e.target && tries++ < 40 ) {
				setTimeout( move, 25 );
			}
		} )();
	} );
} )();
