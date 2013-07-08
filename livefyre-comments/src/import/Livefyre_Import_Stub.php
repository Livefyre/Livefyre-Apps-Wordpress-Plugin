<?php
/*
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/

require_once 'Livefyre_Import.php';

class Livefyre_Import_Stub implements Livefyre_Import {

    function admin_import_notice() {

        return;

    }

    static function skip_trackback_filter($c) {

        return;

    }

    function run_begin() {

        return;

    }

    function begin() {

        return;
        
    }

    function run_check_activity_map_import() {

        return;

    }

    function check_activity_map_import() {

        return;

    }

    function run_check_import() {

        return;

    }

    function check_import() {

        return;

    }

    function check_utf_conversion() {

        return;

    }

    function comment_data_filter( $comment, $test=false ) {

        return;

    }

    function extract_xml( $siteId, $offset=0 ) {

        return;

    }

    function filter_unicode_longs( $long ) {

        return;    
    
    }

    function report_error( $message ) { 

        return;

    }

    function unicode_code_to_utf8( $unicode_list ) {

        return;

    }

    function utf8_to_unicode_code( $utf8_string ) {

        return;

    }

    function wrap_xml( &$articles ) {

        return;

    }

}
