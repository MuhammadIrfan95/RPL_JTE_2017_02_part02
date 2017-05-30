jQuery( document ).ready( function () {
	"use strict";

	function placeholders() {
		var placeholder_strs = '<span class="button">[content]</span> ';
		jQuery( '.rswp-attribute' ).each( function () {
			placeholder_strs = placeholder_strs + '<span class="button">[' + jQuery( this ).val() + ']</span> ';
		} );

		jQuery( '.rswp-placeholders' ).html( placeholder_strs );

		/* Update the usage code */
		usage_code( jQuery( '#rswp_shortcode' ).data( 'shortcodename' ) );

	}

	function usage_code( shortcode ) {
		var usage_code = '[' + shortcode + ' ';

		jQuery( '.rswp-attribute' ).each( function () {
			usage_code = usage_code + jQuery( this ).val() + '="" ';
		} );

		usage_code = usage_code + '][/' + shortcode + ']';

		jQuery( '#rswp_usage textarea' ).val( usage_code );
	}

	jQuery( '#rswp_usage textarea' ).focusin( function () {
		jQuery( this ).select();
	} );

	jQuery( '#title' ).focusout( function () {
		var nonce = jQuery( '#rswp_sanitize_ajax_nonce' ).val();
		var title = jQuery( this ).val();

		jQuery.getJSON( ajaxurl, {
			'do'      : 'sanitize_title',
			'title'   : title,
			'action'  : 'rswp_sanitize',
			'security': nonce
		}, function ( data ) {
			if ( data.data.action == 'sanitized' ) {

				/* Update the real shortcode-name */
				jQuery( '#rswp_shortcode' ).data( 'shortcodename', data.data.title );

				/* Update the usage code */
				usage_code( data.data.title );
			}
		}, 'json' );
	} );

	/* Add a new attribute input-field */
	jQuery( 'a.rswp-add-new' ).click( function ( e ) {
		/* Do not follow the href link */
		e.preventDefault();

		// Read the translation of the attribute value
		var placeholder = jQuery( '#rswp-new-attribute-value' ).data( 'placeholder' );

		/* Remove the css class from the old "new" input fields */
		jQuery( '.rswp-attribute' ).each( function () {
			jQuery( this ).removeClass( 'rswp-attribute-new' );
		} );

		/* Adding an input field */
		jQuery( '#rswp-attributes' ).append( '<a class="rswp-remove" href="#"><span class="rswp-icon rswp-icon-remove"></a><input type="text" class="rswp-attribute rswp-attribute-new" name="rswp_attributes[]" placeholder="' + placeholder + '" />' );

		/* Select the current new input field */
		jQuery( '.rswp-attribute-new' ).select();
	} );

	/* Fill the attributes input field value */
	jQuery( '#rswp-attributes' ).on( 'focusout', '.rswp-attribute', function () {

		var thisObj = jQuery( this );

		if ( thisObj.val() == '' ) thisObj.prev().trigger( 'click' );

		var attribute_name = thisObj.val();
		var nonce = jQuery( '#rswp_sanitize_ajax_nonce' ).val();

		jQuery.getJSON( ajaxurl, {
			'do'            : 'sanitize_attribute',
			'attribute_name': attribute_name,
			'action'        : 'rswp_sanitize',
			'security'      : nonce
		}, function ( data ) {
			if ( data.data.action == 'sanitized' ) {
				thisObj.val( data.data.attribute_name );
				placeholders();
			}
		}, 'json' );

	} );

	/* Remove an input field */
	//jQuery('a.rswp-remove').on('click', function (e) {
	jQuery( '#rswp-attributes' ).on( 'click', '.rswp-remove', function ( event ) {
		/* Do not follow the href link */
		event.preventDefault();

		jQuery( this ).next().remove();
		jQuery( this ).remove();
		placeholders();
	} );

	jQuery( '.rswp_column_shortcode' ).on( 'focusin', function () {
		jQuery( this ).select();
	} );

	jQuery( '.wpbuddy-cr-form a.button' ).click( function ( e ) {
		e.preventDefault();

		var name = jQuery( '#text1210658' ).val();
		var mail = jQuery( '#text1210692' ).val();

		jQuery( [
			'<form style="display:none;" action="https://10955.cleverreach.com/f/54067/wcs/" method="post" target="_blank">',
			'<input id="text1210692" name="email" value="' + mail + '" type="text"  />',
			'<input id="text1210658" name="209681" type="text" value="' + name + '"  />',
			'</form>'
		].join( '' ) ).appendTo( 'body' )[ 0 ].submit();

	} );

	jQuery( '.rswp-placeholders' ).on( 'click', 'span', function () {
		var placeholder = jQuery( this ).text();
		editor1.replaceSelection( placeholder );
	} );

} );


(function ( $, undefined ) {
	$.fn.getCursorPosition = function () {
		var el = $( this ).get( 0 );
		var pos = 0;
		if ( 'selectionStart' in el ) {
			pos = el.selectionStart;
		} else if ( 'selection' in document ) {
			el.focus();
			var Sel = document.selection.createRange();
			var SelLength = document.selection.createRange().text.length;
			Sel.moveStart( 'character', -el.value.length );
			pos = Sel.text.length - SelLength;
		}
		return pos;
	}
})( jQuery );