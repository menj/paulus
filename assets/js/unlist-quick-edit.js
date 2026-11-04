/**
 * Quick Edit: tick "Unlist this item" when the row is unlisted.
 * The row's Unlisted column carries data-unlisted (inc/unlist.php).
 */
( function ( $ ) {
	'use strict';
	if ( typeof window.inlineEditPost === 'undefined' ) {
		return;
	}
	var original = window.inlineEditPost.edit;
	window.inlineEditPost.edit = function ( id ) {
		original.apply( this, arguments );
		var postId = parseInt( typeof id === 'object' ? this.getId( id ) : id, 10 );
		if ( ! postId ) {
			return;
		}
		var unlisted = $( '#post-' + postId ).find( '.paulus-unlist-status' ).data( 'unlisted' );
		$( '#edit-' + postId ).find( 'input[name="paulus_unlisted"]' ).prop( 'checked', String( unlisted ) === '1' );
	};
}( jQuery ) );
