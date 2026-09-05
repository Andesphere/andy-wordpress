/**
 * Settings → Andy Chat: "Check access" button.
 *
 * Runs in the administrator's browser so the request carries this site's real origin, exactly as a
 * visitor's browser would. It only reads the Agent's public configuration; no chat message is sent.
 *
 * Outcomes, in the words the endpoint can actually prove:
 * - success: HTTP 200 with a chatbot object. Andy let this origin read the Agent's configuration.
 * - not found: readable HTTP 404 (a deleted Agent). Andy answered, so origins were not the problem.
 * - blocked: the browser could not read the answer (CORS) but Andy is reachable. Andy returns the
 *   same opaque 403 for an origin outside Allowed Origins and for an unknown embed id, so the copy
 *   names both causes instead of asserting a proven origin rejection.
 * - network: nothing reached Andy at all. Says nothing about Allowed Origins.
 * - error: any other readable HTTP status.
 */
( function () {
	'use strict';

	var config = window.andyChatAccess;
	var input = document.getElementById( 'andy_chat_embed_id' );
	var button = document.getElementById( 'andy-chat-check-access' );
	var output = document.getElementById( 'andy-chat-access-result' );
	if ( ! config || ! input || ! button || ! output ) {
		return;
	}

	var pattern = new RegExp( config.pattern );
	var origin = window.location.origin;
	// Every click and every keystroke in the id field bumps this; a response whose run number is
	// no longer current is dropped, so a slow answer for an old id can never overwrite a newer one.
	var run = 0;

	// Minimal sprintf: %s in order, or %1$s / %2$s so a translation can reorder the values.
	function format( template, values ) {
		var next = 0;
		return template.replace( /%(\d+\$)?s/g, function ( match, position ) {
			return values[ position ? parseInt( position, 10 ) - 1 : next++ ];
		} );
	}

	function show( kind, text ) {
		output.className = 'notice notice-' + kind + ' inline';
		output.textContent = '';
		var p = document.createElement( 'p' );
		p.textContent = text;
		output.appendChild( p );
		output.hidden = false;
	}

	function clear() {
		run++;
		output.hidden = true;
		output.className = '';
		output.textContent = '';
	}

	function originGuidance() {
		var text = format( config.text.addOrigin, [ origin ] );
		if ( config.siteOrigin && config.siteOrigin !== origin ) {
			text += ' ' + format( config.text.siteOriginDiffers, [ config.siteOrigin ] );
		}
		return text;
	}

	function check() {
		var id = input.value.trim();
		clear();
		if ( ! pattern.test( id ) ) {
			show( 'error', config.text.invalidId );
			return;
		}

		var current = run;
		var url = config.endpoint + encodeURIComponent( id );
		var isCurrent = function () {
			return current === run && input.value.trim() === id;
		};

		button.disabled = true;
		output.setAttribute( 'aria-busy', 'true' );
		show( 'info', format( config.text.checking, [ id, origin ] ) );

		fetch( url, { mode: 'cors', credentials: 'omit', cache: 'no-store', headers: { Accept: 'application/json' } } )
			.then( function ( response ) {
				return response.json().catch( function () {
					return {};
				} ).then( function ( data ) {
					if ( ! isCurrent() ) {
						return;
					}
					var chatbot = data && data.chatbot;
					if ( response.ok && chatbot && typeof chatbot.id === 'string' && chatbot.id ) {
						show( 'success', format( config.text.success, [ chatbot.name || id, origin ] ) );
					} else if ( 404 === response.status ) {
						show( 'error', format( config.text.notFound, [ id ] ) );
					} else {
						show( 'error', format( config.text.unexpected, [ String( response.status ) ] ) );
					}
				} );
			} )
			.catch( function () {
				// Opaque failure. Ask again without CORS: this resolves whenever Andy answers at all, and
				// rejects only when nothing reaches Andy (offline, DNS, a content blocker).
				return fetch( url, { mode: 'no-cors', credentials: 'omit', cache: 'no-store' } ).then(
					function () {
						if ( isCurrent() ) {
							show( 'warning', config.text.blocked + ' ' + originGuidance() );
						}
					},
					function () {
						if ( isCurrent() ) {
							show( 'error', config.text.network );
						}
					}
				);
			} )
			.then( function () {
				if ( current === run ) {
					button.disabled = false;
					output.removeAttribute( 'aria-busy' );
				}
			} );
	}

	button.addEventListener( 'click', check );
	input.addEventListener( 'input', function () {
		clear();
		button.disabled = false;
		output.removeAttribute( 'aria-busy' );
	} );
} )();
