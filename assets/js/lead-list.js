/* Lead and list: pointing at (or tabbing to) an entry in the list brings its
 * picture, date and title forward in the large panel; leaving puts the lead
 * back. Without script the lead simply stays. */
( function () {
	'use strict';
	document.querySelectorAll( '.paulus-lead-list' ).forEach( function ( box ) {
		var panels = box.querySelectorAll( '.paulus-lead-list__lead' );
		var items = box.querySelectorAll( '.paulus-lead-list__item[data-lead]' );
		if ( panels.length < 2 || ! items.length ) {
			return;
		}
		var home = panels[ 0 ].getAttribute( 'data-lead' );
		function show( id ) {
			panels.forEach( function ( p ) {
				p.hidden = p.getAttribute( 'data-lead' ) !== id;
			} );
		}
		items.forEach( function ( li ) {
			var id = li.getAttribute( 'data-lead' );
			li.addEventListener( 'mouseenter', function () { show( id ); } );
			li.addEventListener( 'focusin', function () { show( id ); } );
		} );
		box.querySelector( '.paulus-lead-list__list' ).addEventListener( 'mouseleave', function () { show( home ); } );
		box.addEventListener( 'focusout', function ( e ) {
			if ( ! box.contains( e.relatedTarget ) ) {
				show( home );
			}
		} );
	} );
}() );
