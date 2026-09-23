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

/**
 * Paulus reading aids, each switched in Theme Options, Reading:
 * a progress bar, arrow keys for the reading order, a resume prompt,
 * a copy-link button in the share row, and a back-to-top button.
 * Settings arrive as window.paulusReader. Nothing is sent anywhere;
 * the resume position is kept in the reader's own browser.
 */
( function () {
	'use strict';
	var cfg = window.paulusReader || {};
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var root = document.documentElement;
	var prose = document.querySelector( '.paulus-prose' );

	function scrollToY( y ) {
		window.scrollTo( { top: y, behavior: reduce ? 'auto' : 'smooth' } );
	}
	// The stretch of the page that counts as the text: from the top of the
	// prose to its end, less one screen.
	function span() {
		if ( ! prose ) {
			return null;
		}
		var r = prose.getBoundingClientRect();
		var top = r.top + window.pageYOffset;
		return { top: top, end: top + r.height - window.innerHeight };
	}

	/* Progress bar (articles). */
	if ( cfg.progress && prose && document.body.classList.contains( 'single' ) ) {
		var bar = document.createElement( 'div' );
		bar.className = 'paulus-progress';
		bar.setAttribute( 'aria-hidden', 'true' );
		bar.innerHTML = '<span></span>';
		document.body.appendChild( bar );
		var fill = bar.firstChild;
		var ticking = false;
		var paint = function () {
			ticking = false;
			var s = span();
			var p = s && s.end > s.top ? ( window.pageYOffset - s.top ) / ( s.end - s.top ) : 0;
			fill.style.transform = 'scaleX(' + Math.max( 0, Math.min( 1, p ) ).toFixed( 4 ) + ')';
		};
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( paint );
			}
		}, { passive: true } );
		window.addEventListener( 'resize', paint, { passive: true } );
		paint();
	}

	/* Arrow keys: previous and next in the reading order (articles). */
	if ( cfg.keys ) {
		var prev = document.querySelector( '.paulus-readnav__prev' );
		var next = document.querySelector( '.paulus-readnav__next' );
		if ( prev || next ) {
			document.addEventListener( 'keydown', function ( e ) {
				if ( e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey ) {
					return;
				}
				var t = e.target;
				if ( t && ( t.isContentEditable || /^(INPUT|TEXTAREA|SELECT|BUTTON)$/.test( t.tagName ) ) ) {
					return;
				}
				// Leave the keys to the image viewer while it is open.
				var box = document.getElementById( 'lightbox' );
				if ( box && box.offsetParent !== null ) {
					return;
				}
				if ( 'ArrowLeft' === e.key && prev ) {
					window.location.href = prev.href;
				} else if ( 'ArrowRight' === e.key && next ) {
					window.location.href = next.href;
				}
			} );
			if ( prev ) {
				prev.setAttribute( 'aria-keyshortcuts', 'ArrowLeft' );
			}
			if ( next ) {
				next.setAttribute( 'aria-keyshortcuts', 'ArrowRight' );
			}
		}
	}

	/* Resume prompt: remembers how far the reader got in a long article or
	 * page and offers to return there on the next visit. It never moves the
	 * page by itself. */
	if ( cfg.memory && prose ) {
		var key = 'paulus-pos:' + window.location.pathname;
		var store = {
			get: function () {
				try {
					return JSON.parse( window.localStorage.getItem( key ) || 'null' );
				} catch ( e ) {
					return null;
				}
			},
			set: function ( v ) {
				try {
					window.localStorage.setItem( key, JSON.stringify( v ) );
				} catch ( e ) {}
			},
			clear: function () {
				try {
					window.localStorage.removeItem( key );
				} catch ( e ) {}
			}
		};
		var long = prose.getBoundingClientRect().height > window.innerHeight * 2.5;
		var saved = store.get();
		var fresh = saved && ( Date.now() - saved.t ) < 60 * 864e5;
		var s0 = span();
		if ( long && fresh && ! window.location.hash && s0 && saved.y > s0.top + window.innerHeight && saved.y < s0.end ) {
			// The section heading last passed before the saved position.
			var heading = '';
			document.querySelectorAll( '.paulus-prose h2' ).forEach( function ( h ) {
				if ( h.getBoundingClientRect().top + window.pageYOffset <= saved.y + 80 ) {
					heading = h.textContent.replace( /#\s*$/, '' ).trim();
				}
			} );
			var pill = document.createElement( 'div' );
			pill.className = 'paulus-resume';
			pill.setAttribute( 'role', 'status' );
			pill.innerHTML = '<button type="button" class="paulus-resume__go"></button><button type="button" class="paulus-resume__close"></button>';
			var go = pill.firstChild;
			var close = pill.lastChild;
			go.textContent = heading ? ( cfg.resumeAt || 'Resume reading at' ) + ' ' + heading : ( cfg.resume || 'Resume reading where you left off' );
			close.textContent = '×';
			close.setAttribute( 'aria-label', cfg.dismiss || 'Dismiss' );
			var hide = function () {
				pill.classList.remove( 'is-shown' );
				window.setTimeout( function () {
					pill.remove();
				}, 300 );
			};
			go.addEventListener( 'click', function () {
				scrollToY( saved.y );
				hide();
			} );
			close.addEventListener( 'click', function () {
				store.clear();
				hide();
			} );
			document.body.appendChild( pill );
			window.requestAnimationFrame( function () {
				pill.classList.add( 'is-shown' );
			} );
			// A reload or a Back navigation lets the browser restore the
			// position itself; once the reader is there, by any route, the
			// prompt has nothing left to offer and withdraws.
			var near = function () {
				if ( pill.isConnected && Math.abs( window.pageYOffset - saved.y ) < window.innerHeight / 2 ) {
					hide();
					window.removeEventListener( 'scroll', near );
				}
			};
			window.addEventListener( 'scroll', near, { passive: true } );
			window.addEventListener( 'load', near );
		}
		if ( long ) {
			var timer = null;
			window.addEventListener( 'scroll', function () {
				window.clearTimeout( timer );
				timer = window.setTimeout( function () {
					var s = span();
					var y = window.pageYOffset;
					if ( ! s ) {
						return;
					}
					if ( y >= s.end - 40 ) {
						store.clear(); // Finished: nothing to resume.
					} else if ( y > s.top + window.innerHeight ) {
						store.set( { y: Math.round( y ), t: Date.now() } );
					}
				}, 400 );
			}, { passive: true } );
		}
	}

	/* Copy link, in the share row. */
	if ( cfg.copy ) {
		document.querySelectorAll( '[data-paulus-copy]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var url = btn.getAttribute( 'data-url' ) || window.location.href;
				var done = function () {
					btn.classList.add( 'is-copied' );
					var live = btn.querySelector( '.paulus-share__live' );
					if ( live ) {
						live.textContent = cfg.copied || 'Link copied';
					}
					window.setTimeout( function () {
						btn.classList.remove( 'is-copied' );
						if ( live ) {
							live.textContent = '';
						}
					}, 1800 );
				};
				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( url ).then( done, function () {} );
				} else {
					var ta = document.createElement( 'textarea' );
					ta.value = url;
					ta.setAttribute( 'readonly', '' );
					ta.style.position = 'absolute';
					ta.style.left = '-9999px';
					document.body.appendChild( ta );
					ta.select();
					try {
						document.execCommand( 'copy' );
						done();
					} catch ( e ) {}
					ta.remove();
				}
			} );
		} );
	}

	/* Back to top. */
	if ( cfg.top && prose ) {
		var up = document.createElement( 'button' );
		up.type = 'button';
		up.className = 'paulus-top';
		up.setAttribute( 'aria-label', cfg.toTop || 'Back to top' );
		up.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-6 6 6-6 6 6"/></svg>';
		up.addEventListener( 'click', function () {
			scrollToY( 0 );
			var skip = document.querySelector( 'main, #wp--skip-link--target' );
			if ( skip ) {
				skip.setAttribute( 'tabindex', '-1' );
				skip.focus( { preventScroll: true } );
			}
		} );
		document.body.appendChild( up );
		var showUp = function () {
			up.classList.toggle( 'is-shown', window.pageYOffset > window.innerHeight * 1.5 );
		};
		window.addEventListener( 'scroll', showUp, { passive: true } );
		showUp();
	}
}() );
