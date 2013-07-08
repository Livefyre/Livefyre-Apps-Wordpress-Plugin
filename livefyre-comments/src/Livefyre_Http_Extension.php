<?php
/*
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/

if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

class Livefyre_Http_Extension {
    // Map the Livefyre request signature to what WordPress expects.
    // This just means changing the name of the payload argument.
    public function request( $url, $args = array() ) {
        $http = new WP_Http;
        if ( isset( $args[ 'data' ] ) ) {
            $args[ 'body' ] = $args[ 'data' ];
            unset( $args[ 'data' ] );
        }
        return $http->request( $url, $args );
    }
}
