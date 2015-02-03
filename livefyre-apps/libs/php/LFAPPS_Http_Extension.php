<?php
if(function_exists('vip_safe_wp_remote_get'))
    require_once( LFAPPS__PLUGIN_PATH . '/../vip-init.php' );

/*
 * Extension to the WP http helper class
 */
class LFAPPS_Http_Extension {
    
    /* 
     * Map the Livefyre request signature to what WordPress expects.
     * This just means changing the name of the payload argument.
     *
     */
    public function request( $url, $args = array() ) {
        if(function_exists('vip_safe_wp_remote_get')) {
            if ( isset( $args[ 'data' ] ) ) {
                $args[ 'body' ] = $args[ 'data' ];
                unset( $args[ 'data' ] );
            }
            return vip_safe_wp_remote_get( $url, $args );
        } else {
            $http = new WP_Http;
            if ( isset( $args[ 'data' ] ) ) {
                $args[ 'body' ] = $args[ 'data' ];
                unset( $args[ 'data' ] );
            }
            return $http->request( $url, $args );
        }
    }
}
