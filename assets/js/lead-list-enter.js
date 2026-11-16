/* Lead and list: a staggered fade-up when the block scrolls into view
   (inc/lead-list.php). Without this file, or with reduced motion, the block
   is drawn at rest and nothing moves. */
( function () {
	'use strict';
	var blocks = document.querySelectorAll( '.paulus-lead-list' );
	if ( ! blocks.length || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var calm = function ( el ) {
		return el.classList.contains( 'paulus-lead-list--calm' );
	};
	if ( reduce ) {
		return;
	}
	var io = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			if ( entry.isIntersecting ) {
				entry.target.classList.add( 'is-in' );
				io.unobserve( entry.target );
			}
		} );
	}, { threshold: 0.12 } );
	blocks.forEach( function ( el ) {
		if ( calm( el ) ) {
			return;
		}
		el.classList.add( 'is-armed' );
		io.observe( el );
	} );
}() );
