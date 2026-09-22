/**
 * Paulus rail bounds: the side rail follows the reader through the article
 * text and stops where the text ends, so it never runs on beside the author
 * card, the series list or the reading-order band that follow.
 */
( function () {
	'use strict';
	var rail   = document.querySelector( '.paulus-rail' );
	var prose  = document.querySelector( '.paulus-single .paulus-prose' );
	var single = rail && rail.offsetParent;
	if ( ! rail || ! prose || ! single ) {
		return;
	}
	function fit() {
		var gap = single.getBoundingClientRect().bottom - prose.getBoundingClientRect().bottom;
		rail.style.bottom = Math.max( 0, Math.round( gap ) ) + 'px';
	}
	fit();
	window.addEventListener( 'load', fit );
	window.addEventListener( 'resize', fit, { passive: true } );
	if ( 'ResizeObserver' in window ) {
		new ResizeObserver( fit ).observe( single );
	}
} )();

/**
 * Paulus reader script: marks the section on screen in the article rail.
 * Runs only where a rail with section links exists; does nothing else.
 */
( function () {
	'use strict';
	var links = document.querySelectorAll( '.paulus-rail__sections a[href^="#"]' );
	if ( ! links.length || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}
	var map = {};
	var headings = [];
	links.forEach( function ( a ) {
		var h = document.getElementById( a.getAttribute( 'href' ).slice( 1 ) );
		if ( h ) {
			map[ h.id ] = a;
			headings.push( h );
		}
	} );
	if ( ! headings.length ) {
		return;
	}
	var current = null;
	function mark( id ) {
		if ( current === id ) {
			return;
		}
		current = id;
		links.forEach( function ( a ) {
			a.parentNode.classList.toggle( 'is-current', a.getAttribute( 'href' ) === '#' + id );
		} );
	}
	function update() {
		var y = window.scrollY + window.innerHeight * 0.25;
		var active = headings[ 0 ].id;
		for ( var i = 0; i < headings.length; i++ ) {
			if ( headings[ i ].getBoundingClientRect().top + window.scrollY <= y ) {
				active = headings[ i ].id;
			}
		}
		mark( active );
	}
	var ticking = false;
	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			window.requestAnimationFrame( function () { update(); ticking = false; } );
			ticking = true;
		}
	}, { passive: true } );
	update();
} )();
