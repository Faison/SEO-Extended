jQuery(document).ready( function() {
    
    var existing_title_id   = "#seo-extended-existing-title-";
    var new_title_id        = "#seo-extended-new-title-";
    var new_title_class     = ".seo-extended-new-title";
    
    var existing_metadesc_id   = "#seo-extended-existing-metadesc-";
    var new_metadesc_id        = "#seo-extended-new-metadesc-";
    var new_metadesc_class     = ".seo-extended-new-metadesc";

	var handle_response = function( response, status ) {
		if( status != "success") { return; }

		var resp = jQuery.parseJSON( response ) || response;

		if( resp instanceof Array ) {
			jQuery.each( resp, function() {
				handle_response( this, status );
			} );
		} else {
			if( resp.status == 'success' ) {
				if( jQuery( '.seo_extended_bulk_titles' ).length ) {
					jQuery( existing_title_id + resp.post_id ).html( resp.new_title.replace(/\\(?!\\)/g, '') );
					jQuery( new_title_id + resp.post_id ).val( '' ).focus();
				} else if( jQuery( '.seo_extended_bulk_descriptions').length ) {
					jQuery( existing_metadesc_id + resp.post_id ).html( resp.new_metadesc.replace(/\\(?!\\)/g, '') );
					jQuery( new_metadesc_id + resp.post_id ).val( '' ).focus();
				}
			} else {
				alert( "Failure");
			}
		}

	};

	var handle_responses = function( responses ) {
		var resps = jQuery.parseJSON( responses );
		jQuery.each( resps, function() {
			handle_response( this );
		} );
	}

	var submit_new = function( id ) {
		if( jQuery( '.seo_extended_bulk_titles' ).length == 1 ) {
			submit_new_title( id );
		} else if( jQuery( '.seo_extended_bulk_descriptions').length == 1 ) {
			submit_new_metadesc( id );
		}
	}

	var submit_new_title = function( id ) {

		var data = {
			'action': 'seo_extended_save_title',
			'seo_extended_post_id': id,
			'new_title' : jQuery( new_title_id + id ).val(),
			'existing_title' : jQuery( existing_title_id + id ).html()
		};
        
        if( data.new_title == data.existing_title ) {
            
            jQuery( new_title_id + id ).val('').focus();
            
        } else {

        	if( ( data.new_title == '' ) && !confirm( "Are you sure you want to remove the existing Yoast SEO Title?" ) ) {
        		jQuery( new_title_id + id ).focus();
        		return;
        	}
            
            jQuery.post( ajax_object.ajax_url, data, handle_response );
            
        }

	};

	var submit_new_metadesc = function( id ) {

		var data = {
			'action': 'seo_extended_save_desc',
			'seo_extended_post_id': id,
			'new_metadesc' : jQuery( new_metadesc_id + id ).val(),
			'existing_metadesc' : jQuery( existing_metadesc_id + id ).html()
		};
        
        if( data.new_metadesc == data.existing_metadesc ) {
            
            jQuery( new_metadesc_id + id ).val('').focus();
            
        } else {

        	if( data.new_metadesc == '' && !confirm( "Are you sure you want to remove the existing Yoast SEO Description?" ) ) {
        		jQuery( data.new_metadesc + id ).focus();
        		return;
        	}
            
            jQuery.post( ajax_object.ajax_url, data, handle_response );
            
        }
	}

	jQuery.each( jQuery( new_title_class + ', ' + new_metadesc_class ), function() {
		jQuery(this).keypress( function(event) {
			if ( event.which == 13 ) {
				event.preventDefault();
				var id = jQuery(this).data('id');
				submit_new( id );
			}
		} );

	} );

	jQuery.each( jQuery('.seo-extended-save'), function() {
		jQuery(this).click( function() {
			var id = jQuery(this).data('id');
			submit_new( id );
		} );

	} );

	jQuery.each( jQuery('.seo-extended-save-all'), function() {
		var save_all_titles = function() {

			var data = {
				'action': 'seo_extended_save_all_titles'
			};
            
            data.send = false;

			data.titles = {};
			data.existing_titles = {};

			jQuery.each( jQuery( new_title_class ), function() {
				var id = jQuery(this).data('id');
				var value = jQuery(this).val();
				var existing_title = jQuery( existing_title_id + id ).html();

				if( value != '' ) {
					if( value == existing_title ) {

            			jQuery( new_title_id + id ).val('').focus();

					} else {

	                    data.send = true;
						data.titles[ id ] = value;
						data.existing_titles[ id ] = existing_title;

					}
				}

			} );

            if( data.send ) {
                
                jQuery.post( ajax_object.ajax_url, data, handle_response );
                
            }
			
		};

		var save_all_metadescs = function() {

			var data = {
				'action': 'seo_extended_save_all_descs'
			};
            
            data.send = false;

			data.metadescs = {};
			data.existing_metadescs = {};

			jQuery.each( jQuery( new_metadesc_class ), function() {
				var id = jQuery(this).data('id');
				var value = jQuery(this).val();
				var existing_metadesc = jQuery( existing_metadesc_id + id ).html();

				if( value != '' ) {
					if( value == existing_metadesc ) {

            			jQuery( new_metadesc_id + id ).val('').focus();

					} else {

	                    data.send = true;
						data.metadescs[ id ] = value;
						data.existing_metadescs[ id ] = existing_metadesc;

					}
				}

			} );

            if( data.send ) {
                
                jQuery.post( ajax_object.ajax_url, data, handle_response );
                
            }
			
		};

		if( jQuery( '.seo_extended_bulk_titles' ).length ) {
			jQuery(this).on( 'click', save_all_titles );
		} else if( jQuery( '.seo_extended_bulk_descriptions').length ) {
			jQuery(this).on( 'click', save_all_metadescs );
		}

	} );

} );