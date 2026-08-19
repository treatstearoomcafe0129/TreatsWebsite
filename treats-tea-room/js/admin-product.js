/**
 * Product gallery picker.
 *
 * Wraps the media library so photographs are chosen visually rather than by
 * typing attachment IDs into a text box.
 */
( function ( $ ) {
	'use strict';

	var strings = window.treatsAdminProduct || {};

	/**
	 * Write the current thumbnails back into the hidden input.
	 *
	 * @param {jQuery} $wrap Gallery wrapper.
	 */
	function sync( $wrap ) {
		var ids = $wrap
			.find( '[data-treats-gallery-list] li' )
			.map( function () {
				return $( this ).data( 'id' );
			} )
			.get();

		$wrap.find( 'input[type="hidden"]' ).val( ids.join( ',' ) );
	}

	$( document ).on( 'click', '[data-treats-gallery-add]', function ( event ) {
		event.preventDefault();

		var $wrap = $( this ).closest( '[data-treats-gallery]' );

		var frame = wp.media( {
			title: strings.title || 'Choose photographs',
			button: { text: strings.button || 'Use these photographs' },
			library: { type: 'image' },
			multiple: true
		} );

		frame.on( 'select', function () {
			var $list = $wrap.find( '[data-treats-gallery-list]' );

			frame
				.state()
				.get( 'selection' )
				.each( function ( attachment ) {
					var data = attachment.toJSON();

					// Skip anything already in the list.
					if ( $list.find( 'li[data-id="' + data.id + '"]' ).length ) {
						return;
					}

					var src =
						data.sizes && data.sizes.thumbnail
							? data.sizes.thumbnail.url
							: data.url;

					$list.append(
						$( '<li></li>' )
							.attr( 'data-id', data.id )
							.append( $( '<img>' ).attr( { src: src, alt: '' } ) )
							.append(
								$( '<button type="button"></button>' )
									.addClass( 'button-link treats-gallery__remove' )
									.attr( {
										'data-treats-gallery-remove': '',
										'aria-label': strings.remove || 'Remove'
									} )
									.html( '&times;' )
							)
					);
				} );

			sync( $wrap );
		} );

		frame.open();
	} );

	/* ---------------------------------------------------------------------
	 * Options
	 * ------------------------------------------------------------------ */

	$( document ).on( 'click', '[data-treats-options-add]', function ( event ) {
		event.preventDefault();

		var name = $( this ).data( 'name' );
		var $list = $( this )
			.closest( '[data-treats-options]' )
			.find( '[data-treats-options-list]' );

		$list.append(
			$( '<li class="treats-options__row"></li>' )
				.append(
					$( '<input type="text">' ).attr( {
						name: name + '[label][]',
						placeholder: strings.optionLabel || 'Name, e.g. £20',
						'class': 'treats-options__label'
					} )
				)
				.append(
					$( '<input type="text" inputmode="decimal">' ).attr( {
						name: name + '[price][]',
						placeholder: strings.optionPrice || 'Price',
						'class': 'treats-options__price'
					} )
				)
				.append(
					$( '<button type="button"></button>' )
						.addClass( 'button-link treats-options__remove' )
						.attr( {
							'data-treats-options-remove': '',
							'aria-label': strings.remove || 'Remove'
						} )
						.html( '&times;' )
				)
		);

		$list.find( '.treats-options__label' ).last().trigger( 'focus' );
	} );

	$( document ).on( 'click', '[data-treats-options-remove]', function ( event ) {
		event.preventDefault();
		$( this ).closest( 'li' ).remove();
	} );

	$( document ).on( 'click', '[data-treats-gallery-remove]', function ( event ) {
		event.preventDefault();

		var $wrap = $( this ).closest( '[data-treats-gallery]' );

		$( this ).closest( 'li' ).remove();
		sync( $wrap );
	} );
} )( jQuery );
