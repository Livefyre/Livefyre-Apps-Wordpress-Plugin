<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

require_once( dirname( __FILE__ ) . "/admin/Livefyre_Admin.php" );
require_once( dirname( __FILE__ ) . "/Livefyre_Http_Extension.php");

class Livefyre_Application {

    function home_url() {
    
        return $this->get_option( 'home' );
        
    }
    
    function delete_option( $optionName ) {
    
        return delete_option( $optionName );
        
    }
    
    function update_option( $optionName, $optionValue ) {
    
        return update_option( $optionName, $optionValue );
        
    }
    
    function get_option( $optionName, $defaultValue = '' ) {
    
        return get_option( $optionName, $defaultValue );
        
    }
    
    static function use_site_option() {
    
        return is_multisite() && !defined( 'LF_WP_VIP' );
    
    }

    function get_network_option( $optionName, $defaultValue = '' ) {
    
        if ( $this->use_site_option() ) {
            return get_site_option( $optionName, $defaultValue );
        }

        return get_option( $optionName, $defaultValue );
    
    }
    
    function update_network_option( $optionName, $defaultValue = '' ) {

        if ( $this->use_site_option() ) {
            return update_site_option( $optionName, $defaultValue );
        }
        
        return update_option( $optionName, $defaultValue );
    }
    
    function reset_caches() {
    
        global $cache_path, $file_prefix;
        if ( function_exists( 'prune_super_cache' ) ) {
            prune_super_cache( $cache_path, true );
        }
        if ( function_exists( 'wp_cache_clean_cache' ) ) {
            wp_cache_clean_cache( $file_prefix );
        }
    }

    function setup_activation( $Obj ) {

        register_activation_hook( __FILE__, array( &$Obj, 'activate' ) );
        register_deactivation_hook( __FILE__, array( &$Obj, 'deactivate' ) );

    }
    
    function setup_health_check( $Obj ) {

        add_action( 'init', array( &$Obj, 'livefyre_health_check' ) );

    }

    function setup_sync( $obj ) {

        add_action( 'livefyre_sync', array( &$obj, 'run_do_sync' ) );
        add_action( 'init', array( &$obj, 'comment_update' ) );
        /* START: Public Plugin Only */
        if ( $this->get_network_option( 'livefyre_profile_system', 'livefyre' ) == 'wordpress' ) {
            add_action( 'init', array( &$obj, 'check_profile_pull' ) );
            add_action( 'profile_update', array( &$obj, 'profile_update' ) );
            add_action( 'profile_update', array( &$this, 'profile_update' ) );
        }
        /* END: Public Plugin Only */
    
    }
    
    function setup_import( $obj ) {

        add_action( 'init', array( &$obj, 'run_check_import' ) );
        add_action( 'init', array( &$obj, 'run_check_activity_map_import' ) );
        add_action( 'init', array( &$obj, 'run_begin' ) );
    
    }
    
    function activity_log( $wp_comment_id = "", $lf_comment_id = "", $lf_activity_id = "" ) {
    
        // Use meta keys that will allow us to lookup by Livefyre comment i
        update_comment_meta( $wp_comment_id, LF_CMETA_PREFIX . $lf_comment_id, $lf_comment_id );
        update_comment_meta( $wp_comment_id, LF_AMETA_PREFIX . $lf_activity_id, $lf_activity_id );
        return false;

    }
    
    function get_app_comment_id( $lf_comment_id ) {
    
        global $wpdb;
        $wp_comment_id = wp_cache_get( $lf_comment_id, 'livefyre-comment-map' );
        if ( false === $wp_comment_id ) {
            $wp_comment_id = $wpdb->get_var( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = %s LIMIT 1", LF_CMETA_PREFIX . $lf_comment_id ) );
            if ( $wp_comment_id ) {
                wp_cache_set( $lf_comment_id, $wp_comment_id, 'livefyre-comment-map' );
            }
        }
        return $wp_comment_id;

    }
    
    function schedule_sync( $timeout ) {
        
        // $this->lf_core->Livefyre_Logger->add( "Livefyre: Scheduling a Sync." );
        $hook = 'livefyre_sync';

        // try to clear the hook, for race condition safety
        wp_clear_scheduled_hook( $hook );
        wp_schedule_single_event( time() + $timeout, $hook );
    
    }
    
    private static $comment_fields = array(
        "comment_author",
        "comment_author_email",
        "comment_author_url",
        "comment_author_IP",
        "comment_content",
        "comment_ID",
        "comment_post_ID",
        "comment_parent",
        "comment_approved",
        "comment_date"
    );
    
    function sanitize_inputs ( $data ) {
        
        // sanitize inputs
        $cleaned_data = array();
        foreach ( $data as $key => $value ) {
            // 1. do we care ? if so, add it
            if ( in_array( $key, self::$comment_fields ) ) {
                $cleaned_data[ $key ] = $value;
            }
        }
        return wp_filter_comment( $cleaned_data );
        
    }
    
    function delete_comment( $id ) {

        return wp_delete_comment( $id );

    }

    function insert_comment( $data ) {

        $sanitary_data = $this->sanitize_inputs( $data );
        return $this->without_wp_notifications( 'wp_insert_comment', array( $sanitary_data ) );

    }

    function update_comment( $data ) {

        $sanitary_data = $this->sanitize_inputs( $data );
        return $this->without_wp_notifications( 'wp_update_comment', array( $sanitary_data ) );

    }
    
    function without_wp_notifications( $func_name, $args ) {
    
        $old_notify_setting = get_option( 'comments_notify', false );
        if ( $old_notify_setting !== false ) {
            update_option( 'comments_notify', '' );
        }
        $ret_val = call_user_func_array( $func_name, $args );
        if ( $old_notify_setting !== false ) {
            update_option( 'comments_notify', $old_notify_setting );
        }
        return $ret_val;
    
    }
    
    function update_comment_status( $app_comment_id, $status ) {

        if ( get_comment( $app_commetn_id ) == NULL ) {
            return;
        }
    
        // Livefyre says unapproved, WordPress says hold.
        $wp_status = ( $status == 'unapproved' ? 'hold' : $status );
        $args = array( $app_comment_id, $wp_status );
        $this->without_wp_notifications( 'wp_set_comment_status', $args );
    
    }

    function detect_default_comment() {
        // Checks to see if the site only has the default WordPress comment
        // If the site has 0 comments or only has the default comment, we skip the import
        if ( wp_count_comments()->total_comments > 1) {
            // If the site has more than one comment, show import button like normal
            return False;
        }
        // We take all the comments from post id 1, because this post has the default comment if it was not deleted
        $comments = get_comments('post_id=1');
        if ( count( $comments ) == 0 || ( count( $comments ) == 1 && $comments[0]->comment_author == 'Mr WordPress' ) ) {
            // If there are 0 approved comments or if there is only the default WordPress comment, return True
            return True;
        }
        // If there is 1 comment but it is not the default comment, return False
        return False;
    }

} // Livefyre_Application
