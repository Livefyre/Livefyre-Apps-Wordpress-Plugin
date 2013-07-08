<?php
/*
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/

define( 'LF_SITE_SETTINGS_PAGE', '/settings-template.php' );
define( 'LF_MULTI_SETTINGS_PAGE', '/multisite-settings.php' );

class Livefyre_Admin {
    
    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        
        add_action( 'admin_menu', array( &$this, 'register_admin_page' ) );
        add_action( 'admin_init', array( &$this, 'site_options_init' ) );
        add_action( 'network_admin_menu', array(&$this, 'register_network_admin_page' ) );
        add_action( 'admin_init', array( &$this, 'network_options_init' ) );
        add_action( 'network_admin_edit_save_network_options', array($this, 'do_save_network_options'), 10, 0);

    }
    
    function plugin_upgrade() {
    
        // We have to way to hook into an action that happens on auto-upgrade.
        // This is the work-around for that.
        if ( get_option( 'livefyre_v3_installed', false ) === false ) {
           $this->lf_core->Activation->activate();
        } else if ( get_option( 'livefyre_blogname', false ) !== false ) {
           $this->lf_core->Activation->activate();
        }
    
    }

    function settings_callback() {}
    
    private function allow_domain_settings() {
    
        # Should we collect domain (Livefyre profile domain) settings at
        # the blog level or multisite-wide?
        return is_multisite() && !defined( 'LF_WP_VIP' );
    
    }

    function register_admin_page() {
        
        add_submenu_page( 'options-general.php', 'Livefyre Settings', 'Livefyre', 'manage_options', 'livefyre', array( &$this, 'site_options_page' ) );
    }

    function register_network_admin_page() {
    
        add_submenu_page( 'settings.php', 'Livefyre Network Settings', 'Livefyre', 'manage_options', 'livefyre_network', array( &$this, 'network_options_page' ) );
    
    }

    function network_options_init( $settings_section = 'livefyre_domain_options' ) {
    
        $settings_section = 'livefyre_domain_options';

        register_setting( $settings_section, 'livefyre_domain_name' );
        register_setting( $settings_section, 'livefyre_domain_key' );
        register_setting( $settings_section, 'livefyre_auth_delegate_name' );

        add_settings_section('lf_domain_settings',
            'Livefyre Network Settings',
            array( &$this, 'settings_callback' ),
            'livefyre_network' 
        );
        
        add_settings_field('livefyre_domain_name',
            'Livefyre Network Name',
            array( &$this, 'domain_name_callback' ),
            'livefyre_network',
            'lf_domain_settings' 
        );
        
        add_settings_field('livefyre_domain_key',
            'Livefyre Network Key',
            array( &$this, 'domain_key_callback' ),
            'livefyre_network',
            'lf_domain_settings' 
        );
        
        add_settings_field('livefyre_auth_delegate_name',
            'Livefyre AuthDelagate Name',
            array( &$this, 'auth_delegate_callback' ),
            'livefyre_network',
            'lf_domain_settings' 
        );
        
    }
    
    function site_options_init() {
    
        $name = 'livefyre';
        $section_name = 'lf_site_settings';
        $settings_section = 'livefyre_site_options';
        register_setting( $settings_section, 'livefyre_site_id' );
        register_setting( $settings_section, 'livefyre_site_key' );
        register_setting( $settings_section, 'livefyre_domain_name' );
        register_setting( $settings_section, 'livefyre_domain_key' );
        register_setting( $settings_section, 'livefyre_auth_delegate_name' );
        register_setting( $settings_section, 'livefyre_environment' );
        
        if( $this->returned_from_setup() ) {
            $this->ext->update_option( "livefyre_site_id", $_GET["site_id"] );
            $this->ext->update_option( "livefyre_site_key", $_GET["secretkey"] );
        }
        
        add_settings_section('lf_site_settings',
            '',
            array( &$this, 'settings_callback' ),
            $name
        );
        
        add_settings_field('livefyre_site_id',
            'Livefyre Site ID',
            array( &$this, 'site_id_callback' ),
            $name,
            $section_name
        );
        
        add_settings_field('livefyre_site_key',
            'Livefyre Site Key',
            array( &$this, 'site_key_callback' ),
            $name,
            $section_name
        );

        add_settings_field('livefyre_domain_name',
            'Livefyre Network Name',
            array( &$this, 'domain_name_callback' ),
            $name,
            $section_name
        );
        
        add_settings_field('livefyre_domain_key',
            'Livefyre Network Key',
            array( &$this, 'domain_key_callback' ),
            $name,
            $section_name
        );
        
        add_settings_field('livefyre_auth_delegate_name',
            'Livefyre AuthDelagate Name',
            array( &$this, 'auth_delegate_callback' ),
            $name,
            $section_name
        );

        add_settings_field('livefyre_environment',  
            'Production Credentials',  
            array( &$this, 'environment_callback' ),
            $name,
            $section_name
        ); 
        
    }

    function site_options_page() {

        /* Should we display the Enterprise or Regular version of the settings?
         * Needs to be decided by the build process
         * The file gets set in the bash script that builds this.
         * The default is community
        */
        include( dirname(__FILE__) . LF_SITE_SETTINGS_PAGE);
    
    }

    function environment_callback() {
       
        $html = '<input type="checkbox" id="livefyre_environment" name="livefyre_environment" 
          value="1"' . checked( 1, get_option( 'livefyre_environment' ), false ) . '/>';
        $html .= '<label for="livefyre_environment">    Check this if you are using Production Credentials</label>';  
          
        echo $html;

    }

    function site_id_callback() {

        echo "<input name='livefyre_site_id' value='" . get_option( 'livefyre_site_id' ) . "' />";

    }
    
    function site_key_callback() { 

        echo "<input name='livefyre_site_key' value='" . get_option( 'livefyre_site_key' ) . "' />";

    }

    function do_save_network_options() {

        $this->ext->update_network_option( 'livefyre_domain_name', $_POST[ 'livefyre_domain_name' ] );
        $this->ext->update_network_option( 'livefyre_domain_key', $_POST[ 'livefyre_domain_key' ] );
        $this->ext->update_network_option( 'livefyre_auth_delegate_name', $_POST[ 'livefyre_auth_delegate_name' ] );

        wp_redirect( add_query_arg( array( 'page' => 'livefyre_network', 'updated' => 'true' ), network_admin_url( 'settings.php' ) ) );
        exit();
        
    }

    function network_options_page() {
        
        include( dirname(__FILE__) . LF_MULTI_SETTINGS_PAGE);
        
    }

    function auth_delegate_callback() {

        echo "<input name='livefyre_auth_delegate_name' value='". $this->ext->get_network_option( 'livefyre_auth_delegate_name', '' ) ."' />";

    }
    
    function domain_name_callback() {

        echo "<input name='livefyre_domain_name' value='". $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN ) ."' />";
    
    }
    
    function domain_key_callback() { 
    
        echo "<input name='livefyre_domain_key' value='". $this->ext->get_network_option( 'livefyre_domain_key' ) ."' />";
        
    }
    
    function use_backplane_callback() {
    
        echo "<input name='livefyre_use_backplane' type='checkbox' value='1' " . ( $this->ext->get_network_option('livefyre_use_backplane', false) ? 'checked ' : '' ) . "/>";
    
    }
    
    function get_app_comment_id( $lf_comment_id ) {

        return $this->ext->get_app_comment_id( $lf_comment_id );

    }
    
    static function lf_warning_display( $message ) {
        echo '<div id="livefyre-warning" class="updated fade"><p>' . $message . '</p></div>';
    }
    
    function lf_install_warning() {
        $livefyre_http_url = $this->lf_core->http_url;
        $livefyre_site_domain = "rooms." . LF_DEFAULT_PROFILE_DOMAIN;
    
        if (function_exists( 'home_url' )) {
            $home_url= $this->ext->home_url();
        } else {
            $home_url=$this->ext->get_option( 'home' );
        }
        
        if ( is_admin() )
        {
            $site_settings = $this->ext->get_option( 'livefyre_site_id', false );
            $message = false;
            if ( $site_settings ) {
                if ( $this->is_settings_page() ) {
                    return;
                }
                if ( $this->ext->get_option( 'livefyre_v3_notify_installed', false ) ) {
                    $message = "Thanks for installing the new Livefyre plugin featuring Livefyre Comments 3! Visit your <a href=\"./options-general.php?page=livefyre\">Livefyre settings</a> to import your old comments.";
                } elseif ( $this->ext->get_option( 'livefyre_v3_notify_upgraded', false ) ) {
                    $message = "Thanks for upgrading to the latest Livefyre plugin. Your posts should now be running Comments 3.";
                }
                if ( $message ) {
                    $message = $message . ' <a href="./options-general.php?page=livefyre&livefyre_reset_v3_notes=1">Got it, thanks!</a>';
                }
            } elseif ( !$this->returned_from_setup() ) {
                $message = '<strong>' . __( 'Livefyre is almost ready.' ) . '</strong> ' . 'You must <a href="'.$livefyre_http_url.'/installation/logout?site_url='.urlencode($home_url).'&domain='.$livefyre_site_domain.'&version='.LF_PLUGIN_VERSION.'&type=wordpress&lfversion=3&postback_hook='.urlencode($home_url.'/?lf_wp_comment_postback_request=1').'&transport=http">confirm your blog configuration with livefyre.com</a> for it to work.';
            }
            if ( $message ) {
                echo Livefyre_Admin::lf_warning_display( $message );
            }
        }
    }
    
    function returned_from_setup() {
        return ( isset($_GET['lf_login_complete']) && $_GET['lf_login_complete']=='1' );
    }
    
    function is_settings_page() {
        return ( isset($_GET['page']) && $_GET['page'] == 'livefyre' );
    }

}
