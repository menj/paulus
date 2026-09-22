/**
 * Paulus: send every image in the text that links to its own file through
 * Lightbox2, grouped with the theme's figures, captioned from its figcaption.
 * The theme's [paulus_figure] images carry the attributes already.
 */
( function () {
	'use strict';
	var sel = [ 'jpg', 'jpeg', 'png', 'webp', 'gif' ].map( function ( x ) {
		return '.paulus-prose a[href$=".' + x + '"]';
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
} )();
