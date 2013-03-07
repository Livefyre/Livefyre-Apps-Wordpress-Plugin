<?php

class Logger {
    
    function __construct() {

    }

    function add( $message, $database_flag = false ) {
        if ( WP_DEBUG !== true ) {
            return;
        }
        if ( is_array( $message ) || is_object( $message ) ) {
            $message = print_r( $message, true );
        }
        if ( $database_flag ) {
            global $wpdb;
            $wpdb->query("insert into livefyre_debug_blobs (text) values ('". $message . "');");
        }
        error_log ( $message );
    }
}

?>