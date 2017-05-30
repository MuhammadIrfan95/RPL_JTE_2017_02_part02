(function () {

	jQuery.fn.wpbrs_toggler = function ( fn, fn2 ) {
		// Don't mess with animation or css toggles
		if ( !jQuery.isFunction( fn ) || !jQuery.isFunction( fn2 ) ) {
			return oldToggle.apply( this, arguments );
		}
		// migrateWarn("jQuery.fn.toggle(handler, handler...) is deprecated");
		// Save reference to arguments for access in closure
		var args = arguments,
				guid = fn.guid || jQuery.guid++,
				i = 0,
				toggler = function ( event ) {
					// Figure out which function to execute
					var lastToggle = ( jQuery._data( this, "lastToggle" + fn.guid ) || 0 ) % i;
					jQuery._data( this, "lastToggle" + fn.guid, lastToggle + 1 );
					// Make sure that clicks stop
					event.preventDefault();
					// and execute the function
					return args[ lastToggle ].apply( this, arguments ) || false;
				};
		// link all the functions, so any of them can unbind this click handler
		toggler.guid = guid;
		while ( i < args.length ) {
			args[ i++ ].guid = guid;
		}
		return this.click( toggler );
	};

	tinymce.create( 'tinymce.plugins.WPB_Rich_Snippets', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init: function ( ed, url ) {
			ed.addButton( 'wpb_rich_snippets', {
				title: 'Shortcodes',
				cmd  : 'wpb_rich_snippets',
				image: url + '/../images/shortcode-editor-icon.png'
			} );

			ed.addCommand( 'wpb_rich_snippets', function () {
				jQuery( '#wpb_rich_snippets_window' ).fadeIn( 300 );
			} )
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl: function ( n, cm ) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo: function () {
			return {
				longname : 'Shortcodes',
				author   : 'WP-Buddy',
				authorurl: 'http://wp-buddy.com',
				infourl  : 'http://wp-buddy.com',
				version  : "1.4"
			};
		}
	} );

	// Register plugin
	tinymce.PluginManager.add( 'wpb_rich_snippets', tinymce.plugins.WPB_Rich_Snippets );

	jQuery( '#wpb_rich_snippets_window a.media-modal-close' ).click( function () {
		jQuery( '#wpb_rich_snippets_window' ).fadeOut( 300 );
	} );

	jQuery( '.rich-snippets-div-header a' ).wpbrs_toggler( function ( e ) {
		e.preventDefault();
		var attribute = jQuery( this ).parent().parent();
		attribute.addClass( 'active' );
		attribute.siblings().fadeOut( 300 );
		setTimeout( function () {
			attribute.find( '.rich-snippets-div-content' ).fadeIn( 300 );
			attribute.find( 'a' ).prepend( '<span>&laquo;&nbsp;</span>' );
		}, 350 );
		jQuery( '#wpb_rich_snippets_window .button' ).removeAttr( 'disabled' );

	}, function ( e ) {
		e.preventDefault();
		var attribute = jQuery( this ).parent().parent();
		attribute.removeClass( 'active' );
		attribute.find( '.rich-snippets-div-content' ).fadeOut( 300, function () {
			attribute.siblings().fadeIn( 300 );
			attribute.find( 'a span' ).remove();
			jQuery( '#wpb_rich_snippets_window .button' ).attr( 'disabled', true );
		} );
	} );

	jQuery( '#wpb_rich_snippets_window .button' ).click( function ( e ) {
		e.preventDefault;
		var active_snippet = jQuery( '#wpb_rich_snippets_window' ).find( '.active' );
		var shortcode_name = active_snippet.data( 'shortcode_name' );
		var shortcode_content = active_snippet.find( 'textarea' ).val();

		var shortcode = '[' + shortcode_name;
		active_snippet.find( 'input' ).each( function () {
			var attribute_name = jQuery( this ).data( 'attribute_name' );
			var value = jQuery( this ).val();
			if ( value != '' ) shortcode = shortcode + ' ' + attribute_name + '="' + value + '"';
		} );

		shortcode = shortcode + ']' + shortcode_content + '[/' + shortcode_name + ']';

		tinyMCE.activeEditor.execCommand( 'mceInsertContent', 0, shortcode + ' ' );

		/* Clear all fields */
		active_snippet.find( 'input, textarea' ).val( '' );
		/* Return to all snippet s*/
		active_snippet.find( 'a' ).trigger( 'click' );
		/* Fade window out */
		jQuery( '#wpb_rich_snippets_window' ).fadeOut( 300 );
	} );

})();