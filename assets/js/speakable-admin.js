(function ( $ ) {
	'use strict';

	$( document ).ready( function () {

		// ---- Tab Navigation ----
		function activateTab( $tab ) {
			var tab = $tab.data( 'tab' );

			// Update ARIA on all tabs.
			$( '.speakable-tab' ).removeClass( 'speakable-tab-active' )
				.attr( 'aria-selected', 'false' )
				.attr( 'tabindex', '-1' );
			$tab.addClass( 'speakable-tab-active' )
				.attr( 'aria-selected', 'true' )
				.removeAttr( 'tabindex' );

			// Update panels.
			$( '.speakable-tab-panel' ).removeClass( 'speakable-tab-panel-active' );
			$( '.speakable-tab-panel[data-panel="' + tab + '"]' ).addClass( 'speakable-tab-panel-active' );
		}

		$( '.speakable-tab' ).on( 'click', function () {
			activateTab( $( this ) );
		});

		// Arrow key navigation between tabs.
		$( '.speakable-tab' ).on( 'keydown', function ( e ) {
			var $tabs = $( '.speakable-tab' );
			var index = $tabs.index( this );
			var $next;

			if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				$next = $tabs.eq( ( index + 1 ) % $tabs.length );
			} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				$next = $tabs.eq( ( index - 1 + $tabs.length ) % $tabs.length );
			} else if ( e.key === 'Home' ) {
				e.preventDefault();
				$next = $tabs.first();
			} else if ( e.key === 'End' ) {
				e.preventDefault();
				$next = $tabs.last();
			}

			if ( $next && $next.length ) {
				activateTab( $next );
				$next.focus();
			}
		});

		// ---- Color Picker ----
		$( '.speakable-color-field' ).wpColorPicker( {
			change: function ( event, ui ) {
				// Update mockup live.
				var color = ui.color.toString();
				$( '.speakable-player-mockup' ).css( '--speakable-color', color );
			}
		});

		// ---- Range Sliders ----
		$( '#speakable-speech-rate' ).on( 'input', function () {
			var val = parseFloat( this.value ) + 'x';
			$( '#speakable-rate-val' ).text( val );
			this.setAttribute( 'aria-valuetext', val );
		});
		$( '#speakable-pitch' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#speakable-pitch-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});
		$( '#speakable-volume' ).on( 'input', function () {
			var val = parseFloat( this.value );
			$( '#speakable-volume-val' ).text( val );
			this.setAttribute( 'aria-valuetext', String( val ) );
		});

		// ---- Voice Dropdown ----
		var voiceSelect = document.getElementById( 'speakable-voice-name' );
		var savedVoice  = document.getElementById( 'speakable-voice-saved' );

		function populateVoices() {
			if ( ! voiceSelect || typeof speechSynthesis === 'undefined' ) {
				return;
			}
			var voices = speechSynthesis.getVoices();
			if ( ! voices.length ) {
				return;
			}

			while ( voiceSelect.options.length > 1 ) {
				voiceSelect.remove( 1 );
			}

			voices.forEach( function ( voice ) {
				var option         = document.createElement( 'option' );
				option.value       = voice.name;
				option.textContent = voice.name + ' (' + voice.lang + ')';
				if ( savedVoice && savedVoice.value === voice.name ) {
					option.selected = true;
				}
				voiceSelect.appendChild( option );
			});
		}

		populateVoices();
		if ( typeof speechSynthesis !== 'undefined' && speechSynthesis.onvoiceschanged !== undefined ) {
			speechSynthesis.onvoiceschanged = populateVoices;
		}

		// ---- REST API Toggle ----
		var $apiToggle   = $( '#speakable-rest-api' );
		var $apiEndpoints = $( '#speakable-api-endpoints' );

		function updateApiEndpoints() {
			if ( $apiToggle.is( ':checked' ) ) {
				$apiEndpoints.removeClass( 'speakable-api-disabled' );
			} else {
				$apiEndpoints.addClass( 'speakable-api-disabled' );
			}
		}

		if ( $apiToggle.length ) {
			updateApiEndpoints();
			$apiToggle.on( 'change', updateApiEndpoints );
		}

		// ---- Preview ----
		var isPreviewPlaying = false;
		var adminI18n = ( window.speakableAdmin && window.speakableAdmin.i18n ) ? window.speakableAdmin.i18n : {};

		$( '#speakable-preview-btn' ).on( 'click', function () {
			var text = $( '#speakable-preview-text' ).val();
			if ( ! text ) {
				return;
			}

			if ( isPreviewPlaying ) {
				speechSynthesis.cancel();
				stopPreviewUI();
				return;
			}

			var utter    = new SpeechSynthesisUtterance( text );
			utter.rate   = parseFloat( $( '#speakable-speech-rate' ).val() || 1 );
			utter.pitch  = parseFloat( $( '#speakable-pitch' ).val() || 1 );
			utter.volume = parseFloat( $( '#speakable-volume' ).val() || 1 );

			var voiceName = voiceSelect ? voiceSelect.value : '';
			if ( voiceName ) {
				var match = speechSynthesis.getVoices().find( function ( v ) {
					return v.name === voiceName;
				});
				if ( match ) {
					utter.voice = match;
				}
			}

			utter.onstart = function () {
				startPreviewUI();
			};
			utter.onend = function () {
				stopPreviewUI();
			};
			utter.onerror = function () {
				stopPreviewUI();
			};

			speechSynthesis.cancel();
			speechSynthesis.speak( utter );
		});

		$( '#speakable-stop-preview-btn' ).on( 'click', function () {
			speechSynthesis.cancel();
			stopPreviewUI();
		});

		function startPreviewUI() {
			isPreviewPlaying = true;
			$( '#speakable-preview-label' ).text( adminI18n.playing || 'Playing...' );
			$( '#speakable-preview-btn' ).css( 'background', 'linear-gradient(135deg, #d60017, #b50014)' );
			$( '#speakable-stop-preview-btn' ).show();
			$( '#speakable-wave' ).show();
		}

		function stopPreviewUI() {
			isPreviewPlaying = false;
			$( '#speakable-preview-label' ).text( adminI18n.playPreview || 'Play Preview' );
			$( '#speakable-preview-btn' ).css( 'background', '' );
			$( '#speakable-stop-preview-btn' ).hide();
			$( '#speakable-wave' ).hide();
		}

	});
})( jQuery );
