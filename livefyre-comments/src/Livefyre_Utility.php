<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

class Livefyre_Utility {
    
    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;

        add_action( 'init', array( &$this, 'set_activity_id' ) );
        add_action( 'init', array( &$this, 'show_activity_id' ) );
        add_action( 'init', array( &$this, 'set_widget_priority' ) );
        add_action( 'init', array( &$this, 'show_widget_priority' ) );
        add_action( 'init', array( &$this, 'lf_clear_cache' ) );
    }
    
    function set_activity_id() {

        if ( !( isset($_GET['lf_set_activity_id']) ) ) {
            return;
        }
        $result = array(
            'status' => 'ok',
            'activity-id-set-to' => $_GET['lf_set_activity_id']
        );
        $status = $this->ext->update_option( "livefyre_activity_id", $_GET["lf_set_activity_id"] );
        if ( !$status ) {
            $result['status'] = 'error';
        }
        echo json_encode( $result );
        exit;
    }

    function show_activity_id() {

        if ( !( isset($_GET['lf_show_activity_id']) && $_GET['lf_show_activity_id'] == 1) ) {
            return;
        }
        $result = array(
            'activity-id' => $this->ext->get_option( 'livefyre_activity_id', 0 )
        );
        echo json_encode( $result );
        exit;
    }

    function set_widget_priority() {

        if ( !( isset($_GET['lf_set_widget_priority']) ) ) {
            return;
        }
        $result = array(
            'status' => 'ok',
            'widget-priority-set-to' => $_GET["lf_set_widget_priority"]
        );
        $status = $this->ext->update_option( "livefyre_widget_priority", $_GET["lf_set_widget_priority"] );
        if ( !$status ) {
            $result['status'] = 'error';
        }
        echo json_encode( $result );
        exit;
    }

    function show_widget_priority() {

        if ( !( isset($_GET['lf_show_widget_priority']) && $_GET['lf_show_widget_priority'] == 1 ) ) {
            return;
        }
        $result = array(
            'widget-priority' => $this->ext->get_option( 'livefyre_widget_priority', 99 )
        );
        echo json_encode( $result );
        exit;
    }

    function lf_clear_cache() {

        if ( !( isset($_GET['lf_clear_cache']) && $_GET['lf_clear_cache'] == 1 ) ) {
            return;
        }
        $result = array(
            'status' => 'ok'
        );
        $success = $this->run_clear_cache();
        if ( !$success ) {
            $result['status'] = 'error';
        }
        if ( !isset($_GET['settings_page']) ) {
            echo json_encode( $result );
            exit;
        }
    }

    function run_clear_cache() {
        
        global $wpdb;
        $query = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE ('_transient_livefyre%') OR `option_name` LIKE ('_transient_timeout_livefyre%')" );
        return $query;
    }

}
