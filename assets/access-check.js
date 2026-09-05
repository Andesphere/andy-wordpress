/**
 * Settings → Andy Chat: "Check access" button.
 *
 * Runs in the administrator's browser so the request carries this page's real origin, exactly as a
 * visitor's browser would from the public site. It only reads the Agent's public configuration; no
 * chat message is sent.
 *
 * Outcomes, in the words the endpoint can actually prove:
 * - success: HTTP 200 with a chatbot object. Andy let this page's origin read the configuration. When
 *   the public site uses another origin the copy says that one was not tested.
 * - not found: readable HTTP 404 (a deleted Agent). Andy answered, so origins were not the problem.
 * - blocked: the CORS request itself failed but a no-cors probe reached Andy. Andy returns the same
 *   opaque 403 for an origin outside Allowed Origins and for an unknown embed id, so the copy names
 *   both causes instead of asserting a proven origin rejection.
 * - network: nothing reached Andy at all. Says nothing about Allowed Origins.
 * - timeout: the whole check, body read and probe included, took longer than 15 seconds.
 * - error: any other readable HTTP status, or a failure inside this script.
 */
( function () {
	'use strict';

	var TIMEOUT_MS = 15000;

	var config = window.andyChatAccess;
	var input = document.getElementById( 'andy_chat_embed_id' );
	var button = document.getElementById( 'andy-chat-check-access' );
	var output = document.getElementById( 'andy-chat-access-result' );
	if ( ! config || ! input || ! button || ! output || 'function' !== typeof window.AbortController ) {
		return;
	}

	var pattern = new RegExp( config.pattern );
	var origin = window.location.origin;
	// Each click gets a run number; the run in flight owns one AbortController and one timer. A click, a
	// keystroke, a timeout or a settled result ends the run, so a late answer can never overwrite a
	// newer result, and no request or timer outlives the run that started it.
	var run = 0;
	var active = null;

	// Minimal sprintf: %s in order, or %1$s / %2$s so a translation can reorder the values.
	function format( template, values ) {
		var next = 0;
		return template.replace( /%(\d+\$)?s/g, function ( match, position ) {
			return values[ position ? parseInt( position, 10 ) - 1 : next++ ];
		} );
	}

	// The wrapper is a permanent, visible live region; only the notice inside it comes and goes.
	function show( kind, text ) {
		var box = document.createElement( 'div' );
		var p = document.createElement( 'p' );
		box.className = 'notice notice-' + kind + ' inline';
		p.textContent = text;
		box.appendChild( p );
		output.textContent = '';
		output.appendChild( box );
	}

	function endRun() {
		run++;
		if ( active ) {
			window.clearTimeout( active.timer );
			active.controller.abort();
			active = null;
		}
		button.disabled = false;
	}

	function otherOrigin( template ) {
		return config.siteOrigin && config.siteOrigin !== origin ? ' ' + format( template, [ config.siteOrigin ] ) : '';
	}

	function check() {
		var id = input.value.trim();
		endRun();
		output.textContent = '';
		if ( ! pattern.test( id ) ) {
			show( 'error', config.text.invalidId );
			return;
		}

		var current = run;
		var controller = new window.AbortController();
		var url = config.endpoint + encodeURIComponent( id );
		var options = { mode: 'cors', credentials: 'omit', cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json' } };

		// Renders the one outcome of this click and ends the run. Anything arriving later is dropped.
		function settle( kind, text ) {
			if ( current !== run ) {
				return;
			}
			endRun();
			show( kind, text );
		}

		function render( response, data ) {
			var chatbot = data && data.chatbot;
			if ( response.ok && chatbot && typeof chatbot.id === 'string' && chatbot.id ) {
				settle( 'success', format( config.text.success, [ chatbot.name || id, origin ] ) + otherOrigin( config.text.successOtherOrigin ) );
			} else if ( 404 === response.status ) {
				settle( 'error', format( config.text.notFound, [ id ] ) );
			} else {
				settle( 'error', format( config.text.unexpected, [ String( response.status ) ] ) );
			}
		}

		active = {
			controller: controller,
			timer: window.setTimeout( function () {
				settle( 'error', format( config.text.timeout, [ String( TIMEOUT_MS / 1000 ) ] ) );
			}, TIMEOUT_MS )
		};
		button.disabled = true;
		show( 'info', format( config.text.checking, [ id, origin ] ) );

		fetch( url, options ).then(
			function ( response ) {
				// A body that cannot be read (cut connection, not JSON, or our own abort) is judged by its status.
				return response.json().then(
					function ( data ) {
						render( response, data );
					},
					function () {
						render( response, {} );
					}
				);
			},
			function () {
				// Only a rejected fetch gets here: opaque CORS failure, network failure, or our own abort.
				if ( controller.signal.aborted ) {
					return;
				}
				// Ask again without CORS: this resolves whenever Andy answers at all, and rejects only when
				// nothing reaches Andy (offline, DNS, a content blocker).
				return fetch( url, { mode: 'no-cors', credentials: 'omit', cache: 'no-store', signal: controller.signal } ).then(
					function () {
						settle( 'warning', config.text.blocked + ' ' + format( config.text.addOrigin, [ origin ] ) + otherOrigin( config.text.siteOriginDiffers ) );
					},
					function () {
						if ( ! controller.signal.aborted ) {
							settle( 'error', config.text.network );
						}
					}
				);
			}
		).then( null, function ( error ) {
			// A bug in this script, not an answer from Andy. Never route it through the no-cors probe.
			settle( 'error', config.text.internal );
			if ( window.console && window.console.error ) {
				window.console.error( error );
			}
		} );
	}

	button.addEventListener( 'click', check );
	input.addEventListener( 'input', function () {
		endRun();
		output.textContent = '';
	} );
} )();
