/**
 * Paulus: the login page's LOG IN button takes the site's Greek swap.
 *
 * WordPress draws the button as an <input>, which holds text only, so this
 * replaces it with an equivalent <button> (the same id, name, value and
 * classes, so the form submits exactly as before) carrying the English and
 * its Koine Greek in one cell: on hover or focus the Greek replaces the
 * English in place. Screen readers hear the English. Without JavaScript the
 * page keeps WordPress's own button.
 */
( function () {
	'use strict';
	var data = window.paulusLogin || {};
	var form = document.getElementById( 'loginform' );
	var input = form ? form.querySelector( 'input#wp-submit[type="submit"]' ) : null;
	if ( ! input || ! data.greek ) {
		return;
	}
	var button = document.createElement( 'button' );
	button.type = 'submit';
	button.id = input.id;
	button.name = input.name;
	button.value = input.value;
	button.className = input.className;
	if ( data.title ) {
		button.title = data.title;
	}
	var cell = document.createElement( 'span' );
	cell.className = 'paulus-greekswap';
	var en = document.createElement( 'span' );
	en.className = 'paulus-greekswap__en';
	en.textContent = input.value;
	var gr = document.createElement( 'span' );
	gr.className = 'paulus-greekswap__gr';
	gr.lang = 'grc';
	gr.setAttribute( 'aria-hidden', 'true' );
	gr.textContent = data.greek;
	cell.appendChild( en );
	cell.appendChild( gr );
	button.appendChild( cell );
	input.parentNode.replaceChild( button, input );
} )();
