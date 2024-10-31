jQuery( function( $ ) {

    // Update license
    $( document.body ).on( 'keypress', '.wp-updater-license-row input', function( e ) {

        if ( e.which !== 13 ) {
            return;
        }
        e.preventDefault();

        var $this = $( this );
        var data = {
            action: 'updater_update_license',
            plugin: $this.attr( 'data-plugin' ),
            license: $this.val(),
            nonce: wpu.nonce
        };

        // Show loading
        $this.parents( 'tr' ).first().find( '.spinner' ).addClass( 'is-active' );

        $.post( ajaxurl, data, function( response ) {

            // Replace updater row
            $this.parents( 'tr.wp-updater-license-row' ).replaceWith( response.html );

            add_notice( response.message, response.message_type );

        });

    });

    // Deactivate license
    $( document.body ).on( 'click', '.wp-updater-license-row .deactivate', function( e ) {

        var $this = $( this );
        var data = {
            action: 'updater_deactivate_license',
            plugin: $this.attr( 'data-plugin' ),
            nonce: wpu.nonce
        };

        // Show loading
        $this.parents( 'tr' ).first().find( '.spinner' ).addClass( 'is-active' );

        $.post( ajaxurl, data, function( response ) {

            // Replace updater row
            $this.parents( 'tr.wp-updater-license-row' ).replaceWith( response.html );

            add_notice( response.message, response.message_type );

        });

    });

    // Add a notice
    function add_notice(message, type) {

        if (undefined == type) {
            type = 'updated';
        } else if ('success' == type) {
            type = 'updated';
        }

        $('.pt-license-message').html('<div id="message" class=" \' + type + \' notice">' +
            '<p>' + message + '</p>' +
            '</div>');
    }

});
