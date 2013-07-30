<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

class Livefyre_Health_Check {

    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        $this->ext->setup_health_check( $this );

    }

    function livefyre_health_check() {

        $this->lf_core->Livefyre_Logger->add( "Livefyre: Making a health check." );

        if ( !isset( $_GET[ 'livefyre_ping_hash' ] ) )
            return;

        //check the signature
        if ( $_GET[ 'livefyre_ping_hash' ] != md5( $this->lf_core->home_url ) ) {
            echo "hash does not match! my url is: $this->lf_core->home_url";
            exit;
        } else {
            echo "\nhash matched for url: $this->lf_core->home_url\n";
            echo "site's server thinks the time is: " . gmdate( 'd/m/Y H:i:s', time() );
            $notset = '[NOT SET]';
            foreach ( $this->lf_core->options as $optname ) {
                echo "\n\nlivefyre option: $optname";
                $optval = $this->ext->get_option( $optname, $notset );
                #obscure the secret key ( first 2 chars only )
                $val = ( $optname == 'livefyre_secret' && $optval != $notset ) ? substr( $optval, 0, 2 ) : $optval;
                echo "\n          value: $val";
            }
            exit;
        }
    }
}
