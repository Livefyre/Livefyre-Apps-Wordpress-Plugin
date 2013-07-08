<?php
/*
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/

class Livefyre_Activation {

    function __construct( $lf_core ) {
    
        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        $this->ext->setup_activation( $this );

    }

    function deactivate() {

        $this->reset_caches();
        $this->ext->update_option( 'livefyre_deactivated', 'Deactivated: ' . time() );

    }

    function activate() {
        $this->lf_core->Livefyre_Logger->add( "Livefyre: Activated." );
        $existing_blogname = $this->ext->get_option( 'livefyre_blogname', false );
        if ( $existing_blogname ) {
            $site_id = $existing_blogname;
            $existing_key = $this->ext->get_option( 'livefyre_secret', false );
            $this->ext->update_option( 'livefyre_site_id', $site_id );
            $this->ext->delete_option( 'livefyre_blogname' );
            $this->ext->update_option( 'livefyre_site_key', $existing_key );
            $this->ext->delete_option( 'livefyre_secret' );
        } else {
            $site_id = $this->ext->get_option( 'livefyre_site_id', false );
        }
        if ( !$this->ext->get_network_option( 'livefyre_domain_name', false ) ) {
            // Initialize default profile domain i.e. livefyre.com
            $this->ext->update_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
        }
        if ( !$this->ext->get_option( 'livefyre_v3_installed', false ) ) {
            // Set a flag to show the 'hey you just upgraded' (or installed) flash message
            // Set the timestamp so we know which posts use V2 vs V3
            if ( $site_id ) {
                $this->ext->update_option( 'livefyre_v3_installed', current_time('timestamp') );
                $this->ext->update_option( 'livefyre_v3_notify_upgraded', 1 );
                $this->run_backfill( $site_id ); //only run backfill on existing blogs
            } else {
                // !IMPORTANT
                // livefyre_v3_installed == 0 is used elsewhere to determine if this
                // installation was derived from a former V2 installation
                $this->ext->update_option( 'livefyre_v3_installed', 0 );
                $this->ext->update_option( 'livefyre_v3_notify_installed', 1 );
                $this->ext->update_option( 'livefyre_backend_upgrade', 'skipped' );
            }
        }
    }

    function run_backfill( $site_id ) {
        $backend_upgrade = $this->ext->get_option('livefyre_backend_upgrade', 'not_started' );
        $this->lf_core->Livefyre_Logger->add( "backend_upgrade is set to: " . $backend_upgrade );
        if ( $backend_upgrade == 'not_started' ) {
            # Need to upgrade the backend for this plugin. It's never been done for this site.
            # Since this only happens once, notify the user and then run it.
            $url = $this->lf_core->quill_url . '/import/wordpress/' . $site_id . '/upgrade';
            $http = $this->lf_core->lf_domain_object->http;

            $resp = $http->request( $url, array( 'timeout' => 10 ) );
            if ( is_wp_error( $resp ) ) {
                $this->lf_core->Raven->captureMessage( "Backfill error for site " . $site_id . ": " . $resp->get_error_message() );
                $this->lf_core->Livefyre_Logger->add( "Livefyre: Backend upgrade error: " . $resp->get_error_message() );
                update_option( 'livefyre_backend_upgrade', 'error' );
                update_option( 'livefyre_backend_msg', $resp->get_error_message() );
                return;
            }

            $resp_code = $resp['response']['code'];
            $resp_message = $resp['response']['message'];
            $this->lf_core->Livefyre_Logger->add( "Livefyre: Backfill Request: Code: " . $resp_code . " Message: " . $resp_message . "." );

            if ( $resp_code != '200' ) {
                $this->lf_core->Livefyre_Logger->add( "Livefyre: Request returned an non successful value. " . $resp );
                update_option( 'livefyre_backend_upgrade', 'error' );
                $this->lf_core->Raven->captureMessage( "Backfill error for site " . $site_id . ": " . $resp->get_error_message() );
                $this->lf_core->Livefyre_Logger->add( "Livefyre: Backend upgrade error: " . $resp->get_error_message() );
                return;
            }

            $json_data = json_decode( $resp['body'] );
            $backfill_status = $json_data->status;
            $backfill_msg = $json_data->msg;

            $this->lf_core->Livefyre_Logger->add( "Livefyre: Backend Response: Status: " . $backfill_status . " Message: " . $backfill_msg . "." );
            if ( $backfill_status == 'success' ) {
                $backfill_msg = 'Request for Comments 2 upgrade has been sent';
            }
            update_option( 'livefyre_backend_upgrade', $backfill_status );
            update_option( 'livefyre_backend_msg', $backfill_msg );
        }
    }

    function reset_caches() {
    
        $this->ext->reset_caches();
        
    }

}
