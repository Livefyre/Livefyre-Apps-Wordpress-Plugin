<?php
/*
Livefyre Realtime Comments Core Module

This library is shared between all Livefyre plugins.

Author: Livefyre, Inc. 
Author URI: http://livefyre.com/
*/
define( 'LF_DEFAULT_PROFILE_DOMAIN', 'livefyre.com' );
define( 'LF_DEFAULT_TLD', 'livefyre.com' );
define( 'LF_SYNC_LONG_TIMEOUT', 25200 );
define( 'LF_SYNC_SHORT_TIMEOUT', 3 );
define( 'LF_SYNC_MAX_INSERTS', 25 );
define( 'LF_SYNC_ACTIVITY', 'lf-activity' );
define( 'LF_SYNC_MORE', 'more-data' );
define( 'LF_SYNC_ERROR', 'error' );
define( 'LF_PLUGIN_VERSION', '4.0.1' );

global $livefyre;

class Livefyre_core {

    function __construct() { 

        $this->add_extension();
        $this->require_php_api();
        $this->require_Livefyre_Logger();
        $this->define_globals();
        $this->require_subclasses();
        $this->require_raven();
        $this->Livefyre_Logger->add( "Livefyre: Constructing a Livefyre_core." );
        
    }
    
    function define_globals() {
    
        $this->options = array( 
            'livefyre_site_id', // - name ( id ) of the livefyre record associated with this blog
            'livefyre_site_key' // - shared key used to sign requests to/from livefyre
        );

        $client_key = $this->ext->get_network_option( 'livefyre_domain_key', '' );
        $profile_domain = $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
        $dopts = array(
            'livefyre_tld' => LF_DEFAULT_TLD
        );
        $uses_default_tld = (strpos(LF_DEFAULT_TLD, 'livefyre.com') === 0);
        $this->lf_domain_object = new Livefyre_Domain( $profile_domain, $client_key, null, $dopts);
        $site_id = $this->ext->get_option( 'livefyre_site_id' );
        $this->site = $this->lf_domain_object->site( 
            $site_id, 
            trim( $this->ext->get_option( 'livefyre_site_key' ) )
        );
        $this->debug_mode = false;
        $this->top_domain = ( $profile_domain == LF_DEFAULT_PROFILE_DOMAIN ? LF_DEFAULT_TLD : $profile_domain );
        $this->http_url = ( $uses_default_tld ? "http://www." . LF_DEFAULT_TLD : "http://" . LF_DEFAULT_TLD );
        $this->api_url = "http://api.$this->top_domain";
        $this->quill_url = "http://quill.$this->top_domain";
        $this->admin_url = "http://admin.$this->top_domain";
        $this->assets_url = "http://zor." . LF_DEFAULT_TLD;
        $this->bootstrap_url = "http://bootstrap.$this->top_domain";
        
        // for non-production environments, we use a dev url and prefix the path with env name
        $bootstrap_domain = 'bootstrap-json-dev.s3.amazonaws.com';
        $environment = $dopts['livefyre_tld'] . '/';
        if ( $uses_default_tld ) {
            $bootstrap_domain = 'data.bootstrap.fyre.co';
            $environment = '';
        }

        $this->bootstrap_url_v3 = "http://$bootstrap_domain/$environment$profile_domain/$site_id";
        
        $this->home_url = $this->ext->home_url();
        $this->plugin_version = LF_PLUGIN_VERSION;

    }
    
    function require_php_api() {

        require_once(dirname(__FILE__) . "/livefyre-api/libs/php/Livefyre.php");

    }

    function require_Livefyre_Logger() {

        require_once(dirname(__FILE__) . "/libs/php/Logger/livefyre_logger.php");

    }

    function require_raven() {

        require_once(dirname(__FILE__) . "/libs/php/Raven/Autoloader.php");
        Raven_Autoloader::register();
        $this->Raven = new Raven_Client('http://0f5245e17ee1418a905268a6032ef829:3c1ef304db44449ab27988d6f0b4dfcf@sentry.livefyre.com:9000/3');
    }

    function add_extension() {

        if ( class_exists( 'Livefyre_Application' ) ) {
            $this->ext = new Livefyre_Application( $this );
        } else {
            die( "There is no Application Module ( WordPress, Joomla, or other )included with this plugin .  Error: Class Livefyre_Application not defined . " );
        }
    }

    function require_subclasses() {

        $this->Health_Check = new Livefyre_Health_Check( $this );
        $this->Activation = new Livefyre_Activation( $this );
        $this->Sync = new Livefyre_Sync( $this );
        $this->Import = new Livefyre_Import( $this );
        $this->Admin = new Livefyre_Admin( $this );
        $this->Display = new Livefyre_Display( $this );
        $this->Federation = new Livefyre_Federation( $this );
        $this->Livefyre_Logger = new Livefyre_Logger( );
    }

} //  Livefyre_core

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
            } else {
                // !IMPORTANT
                // livefyre_v3_installed == 0 is used elsewhere to determine if this
                // installation was derived from a former V2 installation
                $this->ext->update_option( 'livefyre_v3_installed', 0 );
                $this->ext->update_option( 'livefyre_v3_notify_installed', 1 );
            }
        }

        $backend_upgrade = $this->ext->get_option('livefyre_backend_upgrade', 'not_started');
        if ( $backend_upgrade == 'not_started' ) {
            # Need to upgrade the backend for this plugin. It's never been done for this site.
            # Since this only happens once, notify the user and then run it.
            $url = $this->lf_core->quill_url . '/import/wordpress/' . $site_id . '/upgrade';
            $http = $this->lf_core->lf_domain_object->http;

            $resp = $http->request( $url, array( 'timeout' => 10 ) );
            if ( is_wp_error( $resp ) ) {
                $this->lf_core->Raven->captureMessage( "Backfill error for site " . $site_id );
                $this->lf_core->Raven->captureMessage('Error message: ' . $resp->get_error_message() );
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
                $this->lf_core->Raven->captureMessage( "Backfill error for site " . $site_id );
                $this->lf_core->Raven->captureMessage('Error message: ' . $resp->get_error_message() );
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

/* START: This code not approved by automattic, yet */
class Livefyre_Federation {
    
    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        $this->ext->setup_federation( $this );

    }
    
    function token_request_handler() {
    
         // If the request signature matches what we expect, 
         // echo a token for the currently logged-in user and die()
         if ( !isset( $_GET[ 'livefyre_token_request' ] ) ) {
              return;
         }
         
         if ( isset( $_GET[ 'callback' ] ) ) {
              header( "Content-type: text/javascript" );
              echo $_GET[ 'callback' ] . '(' . $this->userauth_json() . ')';
         } else {
              header( "Content-type: application/json" );
              echo $this->userauth_json();
         }
         die();
         
    }
    
    function userauth_json() {

        $domain = $this->lf_core->lf_domain_object;
        $user_id = $this->ext->get_current_user_attr( "id" );
        $display_name = $this->ext->get_current_user_attr( "display_name" );
        if ( $user_id == null ) {
            // Someone asked for a token but they're not logged in,
            // respond empty since there is no token to render
            return null;
        }
        $user = $domain->user( $user_id, $display_name );
        return $user->auth_json();

    }
    
}
/* END: This code not approved by automattic, yet */

class Livefyre_Sync {
    
    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        $this->ext->setup_sync( $this );

    }

    function run_do_sync() {
        try {
            $this->do_sync();
        }
        catch (Exception $e) {
            try {
                $this->lf_core->Raven->captureException($e);
                $error_message = 'Livefyre: Exception occured during do_sync - ' . $e->getMessage();
                $this->lf_core->Livefyre_Logger->add($error_message);
            }
            catch (Exception $f) {}
            throw $e;
        }
    }


    function do_sync() {
        $this->lf_core->Livefyre_Logger->add( "Livefyre: Running a site sync." );
        /*
            Fetch comments from the livefyre server, providing last activity id we have.
            Schedule the next sync if we got >50 or the server says "more-data".
            If there are no more comments, schedule a sync for several hrs out.
        */
        $result = array(
            'status' => 'ok',
            'message' => 'The sync process completed successfully.',
            'last-message-type' => null,
            'activities-handled' => 0
        );

        $inserts_remaining = LF_SYNC_MAX_INSERTS;
        $max_activity = $this->ext->get_option( 'livefyre_activity_id', '0' );
        if ( $max_activity == '0' ) {
            $final_path_seg = '';
        } else {
            $final_path_seg = $max_activity . '/';
        }
        $url = $this->site_rest_url() . '/sync/' . $final_path_seg;
        $qstring = 'page_size=' . $inserts_remaining . '&sig_created=' . time();
        $key = $this->ext->get_option( 'livefyre_site_key' );
        $url .= '?' . $qstring . '&sig=' . urlencode( getHmacsha1Signature( base64_decode( $key ), $qstring ) );
        $http_result = $this->lf_core->lf_domain_object->http->request( $url, array('timeout' => 120) );
        if (is_array( $http_result ) && isset($http_result['response']) && $http_result['response']['code'] == 200) {
            $str_comments = $http_result['body'];
        } else {
            $str_comments = '';
        }
        $json_array = json_decode( $str_comments );
        if ( !is_array( $json_array ) ) {
            $this->schedule_sync( LF_SYNC_LONG_TIMEOUT );
            $error_message = 'Error during do_sync: Invalid response ( not a valid json array ) from sync request to url: ' . $url . ' it responded with: ' . $str_comments;
            $this->lf_core->Livefyre_Logger->add( "Livefyre: Invalid response ( not a valid json array) from sync request." );
            $this->livefyre_report_error( $error_message );
            return array_merge(
                $result,
                array( 'status' => 'error', 'message' => $error_message )
            );
        }
        $data = array();
        // What to record for the "latest" id we know about, when done inserting
        $last_activity_id = 0;
        // By default, we don't queue an other near-term sync unless we discover the need to
        $timeout = LF_SYNC_LONG_TIMEOUT;
        $first = true;
        foreach ( $json_array as $json ) {
            $mtype = $json->message_type;
            if ( $mtype == LF_SYNC_ERROR ) {
                // An error was encountered, don't schedule next sync for near-term
                $timeout = LF_SYNC_LONG_TIMEOUT;
                break;
            }
            if ( $mtype == LF_SYNC_MORE ) {
                // There is more data we need to sync, schedule next sync soon
                $timeout = LF_SYNC_SHORT_TIMEOUT;
                break;
            }
            if ( $mtype == LF_SYNC_ACTIVITY ) {
                $last_activity_id = $json->activity_id;
                $inserts_remaining--;
                $comment_date  = (int) $json->created;
                $comment_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $comment_date ) );
                $data = array( 
                    'lf_activity_id'  =>  $json->activity_id,
                    'lf_action_type'  => $json->activity_type,
                    'comment_post_ID'  => $json->article_identifier,
                    'comment_author'  => $json->author,
                    'comment_author_email'  => $json->author_email,
                    'comment_author_url'  => $json->author_url,
                    'comment_type'  => '', 
                    'lf_comment_parent'  => $json->lf_parent_comment_id,
                    'lf_comment_id'  => $json->lf_comment_id,
                    'user_id'  => null,
                    'comment_author_IP'  => $json->author_ip,
                    'comment_agent'  => 'Livefyre, Inc .  Comments Agent', 
                    'comment_date'  => $comment_date,
                    'lf_state'  => $json->state
                );
                if($first) {
                    $first_id_msg = 'Livefyre: Processing activity page starting with ' . $data['lf_activity_id'];
                    $this->lf_core->Livefyre_Logger->add($first_id_msg);
                    $first = false;
                }
                if ( isset( $json->body_text ) ) {
                    $data[ 'comment_content' ] = $json->body_text;
                }
                $this->livefyre_insert_activity( $data );
                if ( !$inserts_remaining ) {
                    $timeout = LF_SYNC_SHORT_TIMEOUT;
                    break;
                }
            }
        }
        $result[ 'last-message-type' ] = $mtype;
        $result[ 'activities-handled' ] = LF_SYNC_MAX_INSERTS - $inserts_remaining;
        $result[ 'last-activity-id' ] = $last_activity_id;
        if ( $last_activity_id ) {
            $this->ext->update_option( 'livefyre_activity_id', $last_activity_id );
            $last_id_msg = 'Livefyre: Set last activity ID processed to ' . $last_activity_id;
            $this->lf_core->Livefyre_Logger->add($last_id_msg);
        }
        $this->schedule_sync( $timeout );
        return $result;

    }

    function schedule_sync( $timeout ) {

        $this->ext->schedule_sync( $timeout );

    }
    
    function comment_update() {
        
        if (isset($_GET['lf_wp_comment_postback_request']) && $_GET['lf_wp_comment_postback_request']=='1') {
            $result = $this->do_sync();
            // Instruct the backend to use the site sync postback mechanism for future updates.
            $result[ 'plugin-version' ] = LF_PLUGIN_VERSION;
            echo json_encode( $result );
            exit;
        }
    
    }
    
    /* START: This code not approved by automattic, yet */
    function profile_update( $user_id ) {
        
        $systemuser = $this->lf_core->lf_domain_object->user( 'system' );
        $systemuser->push( $this->ext->profile_update_data( $user_id ) );

    }

    function check_profile_pull() {

        if ( ! $this->is_signed_profile_pull() )
            return;

        $domain = $this->lf_core->lf_domain_object;
        $server_token = base64_decode( $_GET[ 'server_token' ] );
        header( 'Content-type: application/json' );
        if ( $domain->validate_server_token( $server_token ) ) {
            // Everything looks good, we respond with the current user's details.
            $lf_user_info = $this->ext->profile_pull_data();
            echo json_encode($lf_user_info);
        } else {
            echo '{"error":"Invalid Signature using PSK."}';
        }
        exit();

    }
    /* END: This code not approved by automattic, yet */

    function save_post( $post_id ) {

        $this->ext->save_post( $post_id );
    
    }

    function post_param( $name, $plain_to_html = false, $default = null ) {

        $in = ( isset( $_POST[$name] ) ) ? trim( $_POST[$name] ) : $default;
        if ( $plain_to_html ) {
            $out = str_replace( "&", "&amp;", $in );
            $out = str_replace( "<", "&lt;", $out );
            $out = str_replace( ">", "&gt;", $out );
        } else {$out = $in;}
        return $out;

    }
    
    /* START: This code not approved by automattic, yet */
    function is_signed_profile_pull() {
    
        return ( 
            isset( $_GET[ 'lf_profile_pull_request' ] )
            && 
            ( $_GET[ 'lf_profile_pull_request' ] == '1' )
            &&
            isset( $_GET[ 'server_token' ] )
        );
    
    }
    /* END: This code not approved by automattic, yet */

    function site_rest_url() {

        return $this->lf_core->http_url . '/site/' . $this->ext->get_option( 'livefyre_site_id' );

    }

    function livefyre_report_error( $message ) { 

        $args = array( 'data' => array( 'message' => $message, 'method' => 'POST' ) );
        $this->lf_core->lf_domain_object->http->request( $this->site_rest_url() . '/error', $args );

    }

    function livefyre_insert_activity( $data ) {
        if ( isset( $data[ 'lf_comment_parent' ] ) && $data[ 'lf_comment_parent' ]!= null ) {
            $app_comment_parent = $this->ext->get_app_comment_id( $data[ 'lf_comment_parent' ] );
            if ( $app_comment_parent == null ) {
                //something is wrong.  might want to log this, essentially flattening because parent is not mapped
            }
        } else { 
            $app_comment_parent = null;
        }
        $app_comment_id = $this->ext->get_app_comment_id( $data[ 'lf_comment_id' ] );
        $at = $data[ 'lf_action_type' ];
        $data[ 'comment_approved' ] = ( ( isset( $data[ 'lf_state' ] ) && $data[ 'lf_state' ] == 'active' ) ? 1 : 0 );
        $data[ 'comment_parent' ] = $app_comment_parent;
        $action_types = array( 
            'comment-add', 
            'comment-moderate:mod-approve', 
            'comment-moderate:mod-hide',
            'comment-moderate:mod-unapprove',
            'comment-moderate:mod-mark-spam',
            'comment-moderate:mod-bozo',
            'comment-update',
            'comment-delete'
        );
        if ( $app_comment_id > '' && in_array( $at, $action_types ) ) {

            // update existing comment
            $data[ 'comment_ID' ] = $app_comment_id;
            $at_parts = explode( ':', $at );
            $action = $at_parts[ 0 ];
            $mod = count( $at_parts ) > 1 ? $at_parts[ 1 ] : '';
            if ( $action == 'comment-moderate' ) {
                if ( $mod == 'mod-approve' ) {
                    $this->ext->update_comment_status( $app_comment_id, 'approve' );
                } elseif ( $mod == 'mod-hide' && $data[ 'lf_state' ] == 'hidden' ) {
                    $this->ext->delete_comment( $app_comment_id );
                } elseif ( $mod == 'mod-unapprove') {
                    $this->ext->update_comment_status( $app_comment_id, 'hold' );
                } elseif ( $mod == 'mod-mark-spam' || $mod == 'mod-bozo') {
                    $this->ext->update_comment_status( $app_comment_id, 'spam' );
                }
            } elseif ( ($action == 'comment-update' || $action == 'comment-add') && isset( $data[ 'comment_content' ] ) && $data[ 'comment_content' ] != '' ) {
                // even if its supposed to be an "add", when we find the app comment ID, it must be an update
                $this->ext->update_comment( $data );
                if ( $data[ 'lf_state' ] == 'unapproved' ) {
                    $this->ext->update_comment_status( $app_comment_id, 'hold' );
                }
            } elseif ($action == 'comment-delete') {
                $this->ext->delete_comment( $app_comment_id );
            }
        } elseif ( in_array( $at, array( 'comment-add', 'comment-moderate:mod-approve' ) ) ) {
            // insert new comment
            if ( !isset( $data[ 'comment_content' ] ) ) {
                livefyre_report_error( 'comment_content missing for synched activity id:' . $data[ 'lf_activity_id' ] );
            }
            if ( $data[ 'lf_state' ] != 'deleted' && $data[ 'lf_state' ] != 'hidden' ) {
                $app_comment_id = $this->ext->insert_comment( $data );
                if ( $data[ 'lf_state' ] == 'unapproved' ) {
                    $this->ext->update_comment_status( $app_comment_id, 'unapproved' );
                }
            }
        } else {
            return false; //we do not know how to handle this condition
        }

        if ( !( $app_comment_id > 0 ) ) return false;
        $this->ext->activity_log( $app_comment_id, $data[ 'lf_comment_id' ], $data[ 'lf_activity_id' ] );
        return true;
    }
    
}

?>