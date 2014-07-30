<?php
/*
Livefyre Realtime Comments Core Module

This library is shared between all Livefyre plugins.

Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

require_once( dirname( __FILE__ ) . '/Abst_Livefyre_Core.php');

define( 'LF_PLUGIN_VERSION', '4.1.0' );

class Livefyre_WP_Core extends Abst_Livefyre_Core {

    function __construct() {

        Abst_Livefyre_Core::__construct();
        
    }
    
    function define_globals() {
    
        $this->options = array( 
            'livefyre_site_id', // - name ( id ) of the livefyre record associated with this blog
            'livefyre_site_key' // - shared key used to sign requests to/from livefyre
        );

        $client_key = $this->ext->get_network_option( 'livefyre_domain_key', '' );
        $profile_domain = $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
        $dopts = array(
            'livefyre_tld' => LF_DEFAULT_TLD,
            'protocol' => ($_SERVER['SERVER_PORT'] == 443) ? "https" : "http"
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
        $this->protocol = $dopts['protocol'];
        $this->http_url = ( $uses_default_tld ? "http://www." . LF_DEFAULT_TLD : "http://" . LF_DEFAULT_TLD );
        $this->api_url = "http://api.$this->top_domain";
        $this->quill_url = "http://quill.$this->top_domain";
        $this->admin_url = "http://admin.$this->top_domain";
        $this->assets_url = "$this->protocol://" . ($this->protocol === 'https' ? 'cdn' : 'zor') . "." . LF_DEFAULT_TLD;
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

        require_once(dirname(__FILE__) . "/../livefyre-api/libs/php/Livefyre.php");

    }

    function require_Livefyre_Logger() {

        require_once(dirname(__FILE__) . "/../libs/php/Logger/livefyre_logger.php");

    }

    function require_raven() {

        require_once(dirname(__FILE__) . "/../libs/php/Raven/Autoloader.php");
        Raven_Autoloader::register();
        $this->Raven = new Raven_Client('http://0f5245e17ee1418a905268a6032ef829:3c1ef304db44449ab27988d6f0b4dfcf@sentry.livefyre.com:9000/3');
    }

    function add_extension() {

        require_once( dirname( __FILE__ ) . '/Livefyre_Application.php' );
        $this->ext = new Livefyre_Application();
    }

    function require_subclasses() {

        require_once( dirname( __FILE__ ) . '/display/Livefyre_Display.php' );
        require_once( dirname( __FILE__ ) . '/import/Livefyre_Import_Impl.php' );
        require_once( dirname( __FILE__ ) . '/admin/Livefyre_Admin.php' );
        require_once( dirname( __FILE__ ) . '/Livefyre_Health_Check.php' );
        require_once( dirname( __FILE__ ) . '/Livefyre_Activation.php' );
        require_once( dirname( __FILE__ ) . '/Livefyre_Utility.php' );
        require_once( dirname( __FILE__ ) . '/sync/Livefyre_Sync_Impl.php' );

        $this->Health_Check = new Livefyre_Health_Check( $this );
        $this->Activation = new Livefyre_Activation( $this );
        $this->Sync = new Livefyre_Sync_Impl( $this );
        $this->Import = new Livefyre_Import_Impl( $this );
        $this->Admin = new Livefyre_Admin( $this );
        $this->Display = new Livefyre_Display( $this );
        $this->Livefyre_Utility = new Livefyre_Utility( $this );
        $this->Livefyre_Logger = new Livefyre_Logger();
    }

} //  Livefyre_core
