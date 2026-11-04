( function () {
	'use strict';
	var root = document.querySelector( '.cl-admin' );
	if ( ! root ) { return; }
	var tabs = Array.prototype.slice.call( root.querySelectorAll( '.cl-admin__tab' ) );
	var panels = root.querySelectorAll( '.cl-admin__panel' );
	function show( name, focus ) {
		tabs.forEach( function ( t ) {
			var on = t.dataset.tab === name;
			t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			t.tabIndex = on ? 0 : -1;
			if ( on && focus ) { t.focus(); }
		} );
		panels.forEach( function ( p ) { p.hidden = p.dataset.panel !== name; } );
		try { window.localStorage.setItem( 'clTab', name ); } catch ( e ) {}
	}
	tabs.forEach( function ( t, i ) {
		t.addEventListener( 'click', function () { show( t.dataset.tab, false ); } );
		t.addEventListener( 'keydown', function ( e ) {
			var n = { ArrowRight: i + 1, ArrowLeft: i - 1 + tabs.length, Home: 0, End: tabs.length - 1 }[ e.key ];
			if ( undefined === n ) { return; }
			e.preventDefault();
			show( tabs[ n % tabs.length ].dataset.tab, true );
		} );
	} );
	var saved = 'domains';
	try { saved = window.localStorage.getItem( 'clTab' ) || saved; } catch ( e ) {}
	show( root.querySelector( '[data-tab="' + saved + '"]' ) ? saved : 'domains', false );
}() );
