<?php
/*
Plugin Name: Livefyre Realtime Comments
Plugin URI: http://livefyre.com
Description: Implements Livefyre realtime comments for WordPress
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/


require_once( dirname( __FILE__ ) . "/src/Livefyre_WP_Core.php" );

// Constants
define( 'LF_CMETA_PREFIX', 'livefyre_cmap_' );
define( 'LF_AMETA_PREFIX', 'livefyre_amap_' );
define( 'LF_DEFAULT_HTTP_LIBRARY', 'Livefyre_Http_Extension' );
define( 'LF_NOTIFY_SETTING_PREFIX', 'livefyre_notify_' );

class Livefyre {

    function __construct() {
    
        $this->lf_core = new Livefyre_WP_Core();

    }

} // Livefyre

$livefyre = new Livefyre();
