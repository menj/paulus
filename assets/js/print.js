/**
 * Paulus print and PDF: the Print button opens the browser's print dialog,
 * which uses assets/css/print.css; the PDF button loads html2pdf.js on first
 * use and renders the article's print sheet to a file.
 */
( function () {
	'use strict';
	var printBtn = document.querySelector( '[data-paulus-print]' );
	var pdfBtn   = document.querySelector( '[data-paulus-pdf]' );
	if ( printBtn ) {
		printBtn.addEventListener( 'click', function () { window.print(); } );
	}
	if ( ! pdfBtn ) {
		return;
	}
	var busy = false;
	function reset() {
		busy = false;
		pdfBtn.removeAttribute( 'aria-busy' );
		pdfBtn.disabled = false;
	}
	function sheet() {
		var body  = document.querySelector( '.paulus-hero-panel__body' );
		var prose = document.querySelector( '.paulus-prose' );
		if ( ! body || ! prose ) {
			return null;
		}
		var wrap = document.createElement( 'div' );
		wrap.className = 'paulus-printsheet';
		var head = body.cloneNode( true );
		head.querySelectorAll( '.paulus-share, .paulus-hero-panel__series' ).forEach( function ( n ) { n.remove(); } );
		var main = prose.cloneNode( true );
		main.querySelectorAll( '.paulus-alpha' ).forEach( function ( n ) { n.remove(); } );
		var foot = document.createElement( 'p' );
		foot.className = 'paulus-printsheet__foot';
		foot.textContent = document.title + ' \u2014 ' + window.location.href;
		wrap.appendChild( head ); wrap.appendChild( main ); wrap.appendChild( foot );
		return wrap;
	}
	function render() {
		var el = sheet();
		if ( ! el ) {
			reset();
			return;
		}
		var wrap = document.createElement( 'div' );
		wrap.className = 'paulus-printsheet-wrap';
		wrap.appendChild( el );
		document.body.appendChild( wrap );
		window.html2pdf().set( {
			margin: [ 16, 16, 18, 16 ],
			filename: pdfBtn.getAttribute( 'data-filename' ) || 'article.pdf',
			image: { type: 'jpeg', quality: 0.95 },
			html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
			jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
			pagebreak: { mode: [ 'css', 'legacy' ], avoid: [ 'blockquote', 'h2', 'tr', 'li' ] }
		} ).from( el ).save().then( function () {
			wrap.remove(); reset();
		} ).catch( function () {
			wrap.remove(); reset(); window.print();
		} );
	}
	pdfBtn.addEventListener( 'click', function () {
		// aria-busy is set here, before any async work starts, and
		// doubles as the actual re-entrancy guard: a second click (or a
		// click while the vendor script is still loading) is a no-op
		// until an export finishes or fails, instead of starting a
		// concurrent html2pdf() run.
		if ( busy ) {
			return;
		}
		busy = true;
		pdfBtn.setAttribute( 'aria-busy', 'true' );
		pdfBtn.disabled = true;
		if ( window.html2pdf ) {
			render(); return;
		}
		var s = document.createElement( 'script' );
		s.src = pdfBtn.getAttribute( 'data-src' );
		s.onload = render;
		s.onerror = function () { reset(); window.print(); };
		document.head.appendChild( s );
	} );
} )();
