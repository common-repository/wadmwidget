jQuery( function( $ ) {
            $( '#the-list' ).on( 'click', 'a.editinline', function( e ) {
                e.preventDefault();
                var workcode = $(this).data( 'wadm-workcode' );
                $( '#workcode' ).val( workcode ? workcode : '' );
            });
        });
