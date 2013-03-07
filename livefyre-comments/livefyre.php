<?php
/*
Plugin Name: Livefyre Realtime Comments
Plugin URI: http://livefyre.com/wordpress#
Description: Implements livefyre realtime comments for WordPress
Author: Livefyre, Inc.
Version: 4.0.0
Author URI: http://livefyre.com/
*/


require_once( dirname( __FILE__ ) . "/livefyre_core.php" );
require_once( dirname( __FILE__ ) . "/livefyre_import.php" );

// Constants
define( 'LF_CMETA_PREFIX', 'livefyre_cmap_' );
define( 'LF_AMETA_PREFIX', 'livefyre_amap_' );
define( 'LF_DEFAULT_HTTP_LIBRARY', 'Livefyre_Http_Extension' );
define( 'LF_NOTIFY_SETTING_PREFIX', 'livefyre_notify_' );
define( 'LF_POST_META_KEY', 'livefyre_version' );
define( 'LF_POST_META_USE_V1', '1' );
define( 'LF_POST_META_USE_V3', '3' );

class Livefyre_Application {

    function __construct( $lf_core ) {
    
        $this->lf_core = $lf_core;
        add_action( 'publish_page', array( &$this, 'handle_publish' ) );
        add_action( 'publish_post', array( &$this, 'handle_publish' ) );

    }

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
    
    static function use_site_option( ) {
    
        return is_multisite() && !defined( 'LF_WP_VIP' );
    
    }

    function get_network_option( $optionName, $defaultValue = '' ) {
    
        if ($this->use_site_option()) {
            return get_site_option( $optionName, $defaultValue );
        } else {
            return get_option( $optionName, $defaultValue );
        }
    
    }
    
    function update_network_option( $optionName, $defaultValue = '' ) {
    
        if ($this->use_site_option()) {
            return update_site_option( $optionName, $defaultValue );
        } else {
            return update_option( $optionName, $defaultValue );
        }
        
    }

    function get_post_option( $postId, $optionName ) {
        return get_post_meta( $postId, $optionName, true );
    }

    function update_post_option( $postId, $optionName, $optionValue ) {
        update_post_meta( $postId, $optionName, $optionValue );
    }

    function get_post_version( $postId ) {
        return LF_POST_META_USE_V3;
    }
    
    function post_uses_v3($post_id) {
        $published = (int) get_the_time( 'U', $post_id );
        $installed = (int) $this->get_option( 'livefyre_v3_installed', 0 );
        return ( $installed < $published );
    }

    /**
     * Set an property on the post telling it which version of the Livefyre widget to load.
     * $postId: The ID of the post to set the property on.
     */
    function handle_publish( $post_id ) {
        if ( $parent_id = wp_is_post_revision( $post_id ) ) {
            $post_id = $parent_id;
        }
        if ( $this->post_uses_v3($post_id) ) {
            $this->update_post_option( $post_id, LF_POST_META_KEY, LF_POST_META_USE_V3 );
        }
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
        /*
         * Removing this for V2.0.1
         We don't need this at all, with conv_meta and collectionMeta
        add_filter( 'save_post' , array( &$obj, 'save_post' ) , 99, 1 );
        add_filter( 'edit_post' , array( &$obj, 'save_post' ) , 99, 1 );
        */
        /* START: Public Plugin Only */
        if ( $this->get_network_option( 'livefyre_profile_system', 'livefyre' ) == 'wordpress' ) {
            add_action( 'init', array( &$obj, 'check_profile_pull' ) );
            add_action( 'profile_update', array( &$obj, 'profile_update' ) );
            add_action( 'profile_update', array( &$this, 'profile_update' ) );
            add_action( 'set_auth_cookie', array( &$this, 'set_auth_cookie' ), 10, 5 );
            add_action( 'clear_auth_cookie', array( &$this, 'clear_auth_cookie' ) );
        }
        /* END: Public Plugin Only */
    
    }
    
    function setup_import( $obj ) {

        add_action('init', array(&$obj, 'run_check_import'));
        add_action('init', array(&$obj, 'run_check_activity_map_import'));
        add_action('init', array(&$obj, 'run_begin'));
    
    }
    
    /* START: Public Plugin Only */
    
    function profile_update( $user_id ) {
    
        $user_data = get_userdata( $user_id );
        $this->lf_core->lf_domain_object->set_display_name_cookie( $user_data->display_name, '/', COOKIE_DOMAIN );
    
    }
    
    function clear_auth_cookie() {
    
        $this->lf_core->lf_domain_object->clear_cookies( '/', COOKIE_DOMAIN );
    
    }
    
    function set_auth_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme ) {
        
        // Build a JSON string for login, store it in a cookie
        $user_data = get_userdata( $user_id );
        $display_name = $user_data->display_name;
        $lf_user = $this->lf_core->lf_domain_object->user( $user_id, $display_name );
        $token = $lf_user->token( $expiration - time() );
        $this->lf_core->lf_domain_object->set_token_cookie( $token, '/', COOKIE_DOMAIN, $expire, $scheme == 'secure_auth' );
        $this->lf_core->lf_domain_object->set_display_name_cookie( $display_name, '/', COOKIE_DOMAIN, $expire, $scheme == 'secure_auth' );
    
    }
    
    function setup_federation( $obj ) {
    
        add_action( 'init', array( &$obj, 'token_request_handler' ) );
        
    }
    /* END: Public Plugin Only */

    /* START: Public Plugin Only */
    
    function get_current_user_attr( $attr_name ) {
    
        global $current_user;
        // right now this only supports id, display_name, others need mapping
        if ( $current_user && in_array( $attr_name, array( 'id', 'display_name' ) ) ) {
            return $current_user->$attr_name;
        } else {
            return false;
        }
    
    }
    
    function get_livefyre_profile_dict( $user_info ) {

        $result = array(
            'id' => $user_info->ID,
            'display_name' => $user_info->display_name,
            'nickname' => $user_info->nickname,
            'name' => array(
                    "first"=> $user_info->user_firstname,
                    "last"=> $user_info->user_lastname
            ),
            'bio' => $user_info->description,
            'websites' => array( $user_info->user_url ),
            'settings_url' => admin_url( 'profile.php' ),
            'email' => $user_info->user_email
        );
        $notify_settings = array();
        $admin = $this->lf_core->Admin;
        foreach ( $admin->notify_types as $type => $desc ) {
            $meta_name = LF_NOTIFY_SETTING_PREFIX . $type;
            $current_setting = get_user_meta($user_info->ID, $meta_name, true);
            if ( !$current_setting ) {
                $current_setting = $admin->get_notify_default( $type );
            }
            $notify_settings[ $type ] = $current_setting;
        }
        $result[ 'email_notifications' ] = $notify_settings;
        $avatar = get_avatar( $user_info->ID, 512 );
        if ( ! empty( $avatar ) ) {
            // get_avatar presumes that I am a template, but I am not
            // we have to unfortunately deal with this by parsing out src
            $matches = array();
            $matched = preg_match("/src=[\'|\"]([^'|^\"]*)/", $avatar, $matches);
            if ( $matched ) {
                if ( substr( $matches[1], 0, 4) != 'http' ) {
                    // seems this is a relative path, add the root
                    $url_parts = explode( '//', get_option( 'siteurl' ) );
                    $host_uri = explode( '/', $url_parts[1] );
                    $host = $host_uri[0];
                    $matches[1] = $url_parts[0] . '//' . $host . $matches[1];
                }
                $image_url = str_replace( '&#038;', '&', $matches[1] );
                $image_url = str_replace( '&amp;', '&', $image_url );
                $result['image_url'] =  $image_url . '#' . $this->profile_image_checksum( $image_url );
            }
        }
        return $result;

    }
    
    function profile_image_checksum( $image_url ) {
    
        $http = $this->lf_core->lf_domain_object->http;
        $result = $http->request( $image_url );
        if ( is_array( $result ) && isset($result['response']) && $result['response']['code'] == 200 ) {
            return md5(base64_encode(substr($result['body'], -256)));
        } else {
            // fallback to random number, force the update
            return rand();
        }
    
    }

    function profile_update_data( $user_id ) {
    
        return $this->get_livefyre_profile_dict( get_userdata( $user_id ) );
        
    }

    function profile_pull_data() {
    
        // This delegate looks for a particular get param, uses it to look up a user from the db.
        // The get param is defined by the DomainHook record on Livefyre's side.
        return $this->get_livefyre_profile_dict( get_userdata( $_GET[ 'wp_user_id' ] ) );
        
    }
    /* END: Public Plugin Only */

    function save_post( $post_id, $update = true ) {
    
        $parent_id = wp_is_post_revision( $post_id );
        if ( $parent_id ) {
            $post_id = $parent_id;
        }
        
        $is_page = is_page( $post_id );
        if ( $is_page ) {
            $record = get_page( $post_id );
            if ( $parent_id ) {
                $parent = get_page( $parent_id );
            }
        } else {
            $record = get_post( $post_id );
            if ( $parent_id ) {
                $parent = get_post( $parent_id );
            }
        }
        
        if ( ( isset( $parent ) && $parent->post_status == 'publish' ) || $record->post_status == 'publish' )    {
            $tags = false;
            if ( !$is_page ) {
                $tags = get_the_tags( $post_id );
            }
            if ( $tags ) {
                $tagnames = array();
                foreach( $tags as $tag ) {
                    array_push( $tagnames, $tag->name );
                }
                $tagStr = implode( ', ', $tagnames );
            } else {
                $tagStr = '';
            }
            
            $url = $this->lf_core->quill_url . "/api/v1.1/private/management/site/".get_option('livefyre_site_id').'/conv/initialize/';
            $sig_created = time();
            $postdata = array(
                'article_identifier' => $post_id,
                'source_url' => get_permalink($post_id),
                'article_title' => $record->post_title,
                'sig_created' => $sig_created,
                'tags' => $tagStr,
                'sig' => getHmacsha1Signature(base64_decode(trim(get_option('livefyre_site_key'))), "sig_created=$sig_created")
            );
            $http = $this->lf_core->lf_domain_object->http;
            $http->request( $url, array( 'data' => $postdata, 'method' => 'POST' ) );
        }
    
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
        
        $this->lf_core->Logger->add( "Livefyre: Scheduling a Sync." );
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
    
        // Livefyre says unapproved, WordPress says hold.
        $wp_status = ( $status == 'unapproved' ? 'hold' : $status );
        $args = array( $app_comment_id, $wp_status );
        $this->without_wp_notifications( 'wp_set_comment_status', $args );
    
    }

} // Livefyre_Application

class Livefyre_Admin {
    
    public $notify_types = array( 
        'comments'                => 'Someone comments in a conversation you\'re following', 
        'moderator_comments'    => 'Someone comments in a conversation you\'re moderating',
        'moderator_flags'        => 'Someone flags a comment in a conversation you\'re moderating',
        'replies'                => 'Someone replies to one of your comments',
        'likes'                    => 'Someone likes one of your comments'
    );
    
    public $notify_defaults = array(
        'likes' => 'immediately',
        'replies' => 'immediately'
    );
    
    public $notify_options = array( 
        'often'            => 'hourly digest',
        'never'            => 'never',
        'immediately'    => 'immediately'
    );
    
    function __construct( $lf_core ) {
        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        
        add_action( 'admin_menu', array( &$this, 'register_admin_page' ) );
        /*
         * Removing this for V2.0.1
        add_action( 'network_admin_menu', array(&$this, 'register_network_admin_page' ) );
        */
        add_action( 'admin_notices', array( &$this, 'lf_install_warning') );
        add_action( 'admin_notices', array( &$this->lf_core->Import, 'admin_import_notice' ) );
        add_action( 'admin_init', array( &$this, 'site_options_init' ) );
        add_action( 'admin_init', array( &$this->lf_core->Admin, 'plugin_upgrade' ) );
        /*
         * Removing this for V2.0.1
        add_action( 'admin_init', array( &$this, 'network_options_init' ) );
        add_action( 'network_admin_edit_save_network_options', array($this, 'do_save_network_options'), 10, 0);
        */
        
        # self-serve version
        /*
         * Removing this for V2.0.1
        add_action( 'show_user_profile', array( &$this, 'show_user_profile' ) );
        add_action( 'personal_options_update', array( &$this, 'edit_user_profile_update' ) );
        
        # admin version
        add_action( 'edit_user_profile', array( &$this, 'edit_user_profile' ) );
        add_action( 'edit_user_profile_update', array( &$this, 'edit_user_profile_update' ) );
        */
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

    function get_notify_default( $notify_type ) {
    
        if ( isset( $this->notify_defaults[ $notify_type ] ) ) {
            return $this->notify_defaults[ $notify_type ];
        } else {
            return 'often';
        }
    
    }
    
    private function allow_domain_settings() {
    
        # Should we collect domain (Livefyre profile domain) settings at
        # the blog level or multisite-wide?
        return is_multisite() && !defined( 'LF_WP_VIP' );
    
    }

    function register_admin_page() {
        
        add_submenu_page( 'options-general.php', 'Livefyre Settings', 'Livefyre', 'manage_options', 'livefyre', array( &$this, 'site_options_page' ) );
    }

    function register_network_admin_page() {
    
        add_submenu_page( 'settings.php', 'Livefyre Network Settings', 'Livefyre',
        'manage_options', 'livefyre_network', array( &$this, 'network_options_page' ) );
    
    }

    function settings_callback() {}
    
    function edit_user_profile_update( $user_id ) {
    
        $this->email_settings_fields_save( $user_id );
    
    }
    
    function show_user_profile( ) {
    
        $user = wp_get_current_user();
        $this->edit_user_profile( $user );
    
    }
    
    function edit_user_profile( $user ) {
    
        ?>
        <div class="wrap">
            <h3>Livefyre Notification Settings</h3>
        <table class="form-table">
            <tr colspan="2">
                <td><strong>Send email when...</strong></td>
                <?php
                foreach ( $this->notify_options as $opt => $desc ) {
                    echo "<td><h3>$desc</h3></td>";
                }
                ?>
            </tr>
            <?php
                foreach ( $this->notify_types as $type => $type_desc ) {
                    ?>
                    <tr>
                        <th><strong><?php echo $type_desc; ?>:</strong></th>
                        <?php
                        $meta_name = LF_NOTIFY_SETTING_PREFIX . $type;
                        $current_setting = get_user_meta($user->ID, $meta_name, true);
                        if ( !$current_setting ) {
                            $current_setting = $this->get_notify_default( $type );
                        }
                        foreach ( $this->notify_options as $opt => $desc ) {
                            echo '<td><input type="radio" name="' . $meta_name . '" value="' . $opt . '" ' . ( $current_setting == $opt ? 'checked' : '') . '/></td>';
                        }
                         ?>
                        
                    </tr>
                <?php
                }
            ?>
        </table>
        <br/>
        <?php
    }
    
    function email_settings_fields_save( $user_id ) {
    
        if ( !current_user_can( 'edit_user', $user_id ) )
            return false;
        foreach ( $this->notify_types as $type => $desc ) {
            update_user_meta( $user_id, LF_NOTIFY_SETTING_PREFIX . $type, $_POST[ LF_NOTIFY_SETTING_PREFIX . $type ] );
        }
    
    }

    function network_options_init($settings_section = 'livefyre_domain_options') {
    
        register_setting($settings_section, 'livefyre_domain_name');
        register_setting($settings_section, 'livefyre_domain_key');
        register_setting($settings_section, 'livefyre_use_backplane');
        register_setting($settings_section, 'livefyre_profile_system');
        register_setting($settings_section, 'livefyre_wp_auth_hooks');
        register_setting($settings_section, 'livefyre_engage_appname');
        register_setting($settings_section, 'livefyre_lfsp_source_url');

        add_settings_section('lf_domain_settings',
            'Livefyre Network Settings',
            array( &$this, 'settings_callback' ),
            'livefyre_network');
        
        add_settings_field('livefyre_domain_name',
            'Livefyre Network Name',
            array( &$this, 'domain_name_callback' ),
            'livefyre_network',
            'lf_domain_settings');
        
        add_settings_field('livefyre_domain_key',
            'Livefyre Network Key',
            array( &$this, 'domain_key_callback' ),
            'livefyre_network',
            'lf_domain_settings');
        
        add_settings_field('livefyre_use_backplane',
            'Livefyre Backplane Integration',
            array( &$this, 'use_backplane_callback' ),
            'livefyre_network',
            'lf_domain_settings');
        
    }
    
    function site_options_init() {
    
        $name = 'livefyre';
        $section_name = 'lf_site_settings';
        $settings_section = 'livefyre_site_options';
        register_setting($settings_section, 'livefyre_site_id');
        register_setting($settings_section, 'livefyre_site_key');
        register_setting($settings_section, 'livefyre_admin_url');
        register_setting($settings_section, 'livefyre_support_url');
        
        if( $this->returned_from_setup() ) {
            $this->ext->update_network_option("livefyre_site_id", $_GET["site_id"] );
            $this->ext->update_network_option("livefyre_site_key", $_GET["secretkey"] );
        }
        
        add_settings_section('lf_site_settings',
            'Livefyre Site Settings',
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

        add_settings_field('livefyre_admin_url',
            'Livefyre Admin Url',
            array( &$this, 'site_admin_url_callback' ),
            $name,
            $section_name
        );

        add_settings_field('livefyre_support_url',
            'Livefyre Support Url',
            array( &$this, 'site_support_url_callback' ),
            $name,
            $section_name 
        );
        
        // is this a non-mu site? if so, call network_options_init()
        if ( !is_multisite() ) {
            $this->network_options_init($settings_section);
        }
        
    }

    function site_options_page() {

        ?>
            <div class="wrap">
                <h2>Livefyre Settings Page</h2>
                <form method="post" action="options.php">
                    <?php
                        // is this a non-mu site? if so, call network_options_init()
                        if ( !$this->allow_domain_settings() ) {
                            $this->network_options_page();
                        }
                        include( dirname(__FILE__) . '/settings-template.php');
                    ?>
                </form>
            </div>
        <?php
    
    }

    function site_id_callback() {
        echo "<input name='livefyre_site_id' value='" . get_option( 'livefyre_site_id' ) . "' />";
    }
    
    function site_key_callback() { 
        echo "<input name='livefyre_site_key' value='" . get_option( 'livefyre_site_key' ) . "' />";
    }

    function site_admin_url_callback() {
        echo "<input name='livefyre_admin_url' value=" . $this->lf_core->lf_domain_object.get_livefyre_tld() . "/>";
    }

    function site_support_url_callback() {
        echo "<input name='livefyre_support_url' value='www.support.livefyre.com'/>";
    }

    function do_save_network_options() {
    
        if ( !isset( $_POST[ 'livefyre_domain_name' ] ) || $_POST[ 'livefyre_domain_name' ] == '' ) {
            $_POST[ 'livefyre_domain_name' ] = 'livefyre.com';
        };
        // validate
        if ($_POST[ 'livefyre_domain_name' ] == LF_DEFAULT_PROFILE_DOMAIN) {
            $_POST[ 'livefyre_domain_key' ] = '';
        }
        $this->ext->update_network_option( 'livefyre_domain_name', $_POST[ 'livefyre_domain_name' ] );
        $this->ext->update_network_option( 'livefyre_domain_key', $_POST[ 'livefyre_domain_key' ] );
        $this->ext->update_network_option( 'livefyre_use_backplane', $_POST[ 'livefyre_use_backplane' ] );
        $this->ext->update_network_option( 'livefyre_engage_appname', $_POST[ 'livefyre_engage_appname' ] );
        $this->ext->update_network_option( 'livefyre_lfsp_source_url', $_POST[ 'livefyre_lfsp_source_url' ] );
        $this->ext->update_network_option( 'livefyre_wp_auth_hooks', $_POST[ 'livefyre_wp_auth_hooks' ] );
        $this->ext->update_network_option( 'livefyre_profile_system', $_POST[ 'livefyre_profile_system' ] );
        wp_redirect( add_query_arg( array( 'page' => 'livefyre_network', 'updated' => 'true' ), network_admin_url( 'settings.php' ) ) );
        exit();
        
    }
    function network_options_page() {
    
        ?>
            <div class="wrap">
                <h3 style="display:none;">Livefyre Network Settings</h3>
                <?php
                if ($this->allow_domain_settings()) {
                ?>
                <form method="post" action="edit.php?action=save_network_options">
                <?php
                }
                ?>
                    <script type="text/javascript">
                        var defaultNetwork = '<?php echo LF_DEFAULT_PROFILE_DOMAIN; ?>';
                        function setupLivefyreSettings($) {
                            var $useBackplane = $('input[name=livefyre_use_backplane]'),
                                $networkName = $('input[name=livefyre_domain_name]'),
                                $wpAuthHandlers = $('input[name=livefyre_wp_auth_hooks]'),
                                optionalSelector = 'input[name=livefyre_profile_system], input[name=livefyre_wp_auth_hooks], input[name=livefyre_use_backplane], input[name=livefyre_domain_key]',
                                domainKeySelector = '#domain_key_row';
                            function validateFields() {
                                var $profileSystem = $('input[name=livefyre_profile_system]:checked'),
                                    profileSystem = $profileSystem.val(),
                                    useBackplane = $useBackplane.val(),
                                    networkName = $networkName.val();
                                if (networkName == defaultNetwork) {
                                    // livefyre.com profiles - set defaults, disable custom profile options
                                    $('input[name=livefyre_profile_system][value=livefyre]')
                                        .attr('checked',true);
                                    $(optionalSelector).attr('disabled', 'disabled');
                                    $.map([$useBackplane, $wpAuthHandlers], function($elm) {
                                        $elm.removeAttr('checked');
                                    });
                                    $(domainKeySelector).hide();
                                } else if (networkName.indexOf('.fyre.co') == -1) {
                                    alert('Invalid network name!');
                                } else {
                                    $(optionalSelector).removeAttr('disabled');
                                    $(domainKeySelector).show();
                                    if (profileSystem == 'livefyre') {
                                        $('input[name=livefyre_profile_system][value=wordpress]').attr('checked',true);
                                    }
                                }
                            }
                            function profileSystemChange() {
                                if ($('input[name=livefyre_profile_system]:checked').val() == 'livefyre' && $networkName.val() != defaultNetwork ) {
                                    $networkName.val(defaultNetwork);
                                    validateFields();
                                }
                            }
                            $.map(['livefyre_profile_system'], function(name){
                                $('input[name='+name+']').live('change', profileSystemChange);
                            });
                            $.map(['livefyre_domain_name'], function(name){
                                $('input[name='+name+']').live('change', validateFields);
                            });
                            $(document).ready(validateFields);
                        }
                        if ( typeof(jQuery) == 'undefined') {
                            // grab jQuery if we need it, wait for it to load
                            var headID = document.getElementsByTagName("head")[0],
                                newScript = document.createElement('script');
                            
                            newScript.type = 'text/javascript';
                            newScript.src = 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js';
                            headID.appendChild(newScript);
                            function testReadiness() {
                                if (typeof(jQuery) == 'undefined') {
                                    setTimeout(testReadiness, 40);
                                } else {
                                    jQuery(function(){setupLivefyreSettings(jQuery)});
                                }
                            }
                            testReadiness();
                        } else {
                            jQuery(function(){setupLivefyreSettings(jQuery)});
                        }
                    </script>
                    <?php
                        // settings_fields( 'livefyre_domain_options' );
                        // do_settings_sections( 'livefyre_network' );
                        $profile_system = $this->ext->get_network_option('livefyre_profile_system','livefyre');
                        $disable_opt = ($profile_system == 'livefyre' ? 'disabled="disabled"' : '');
                    ?>
                    <table class="form-table" style="display:none;">
                        <tr valign="top"><th scope="row">Livefyre Network Name <br/>(either livefyre.com or your "Custom Network" name)</th><td><input name='livefyre_domain_name' value='<?php echo $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN ) ?>' /></td></tr>
                        <tr id="domain_key_row" valign="top"><th scope="row">Livefyre Network Key</th><td><input name='livefyre_domain_key' disabled='disabled' value='<?php echo $this->ext->get_network_option( 'livefyre_domain_key' ) ?>' /></td></tr>
                    </table>
                    <table class="form-table" style="display:none;">
                        <tr><td colspan="2"><input type="radio" name="livefyre_profile_system" value="livefyre" <?php echo $profile_system == 'livefyre' ? 'checked ' : '' ?>/> use Livefyre.com user profiles</td></tr>
                        <tr><td colspan="2"><input type="radio" <?php echo $disable_opt ?> name="livefyre_profile_system" value="wordpress" <?php echo $profile_system == 'wordpress' ? 'checked ' : '' ?>/> enable WordPress user profile integration (Livefyre Custom Network license REQUIRED.)  Your users in the WordPress database will interact with Livefyre via their WordPress login)</td></tr>
                        <tr><td></td><td><input name='livefyre_wp_auth_hooks' type='checkbox' value='1' <?php echo  $this->ext->get_network_option('livefyre_wp_auth_hooks', false) ? 'checked ' : '' ?>/>enable default auth handlers (uses redirect based login).  Leave this disabled if you have already implemented custom auth handlers</td></tr>
                        
                        <tr><td colspan="2"><input type="radio" <?php echo $disable_opt ?> name="livefyre_profile_system" value="3p" <?php echo $profile_system == '3p' ? 'checked ' : '' ?>/> use another 3rd party user profile system (extra setup is required, only select this when instructed to)</td></tr>
                        <tr><td></td><td><input name='livefyre_use_backplane' type='checkbox' value='1' <?php echo  $this->ext->get_network_option('livefyre_use_backplane', false) ? 'checked ' : '' ?>/> enable backplane user profile integration</td></tr>
                        
                        
                        <tr><td colspan="2"><input type="radio" <?php echo $disable_opt ?> name="livefyre_profile_system" value="lfsp" <?php echo $profile_system == 'lfsp' ? 'checked ' : '' ?>/> use the Livefyre Simple Profiles system (extra setup is required, only select this when instructed to)</td></tr>
                        <tr><td></td><td><input name='livefyre_engage_appname' type='text' value='<?php echo $this->ext->get_network_option('livefyre_engage_appname', '') ?>'/> Enter your Engage application name.</td></tr>
                        <tr><td></td><td><input name='livefyre_lfsp_source_url' type='text' value='<?php echo $this->ext->get_network_option('livefyre_lfsp_source_url', '') ?>'/>  Enter the source URL for your Livefyre Simple Profiles Javascript file.</td></tr>
                    </table>
                    <?php
                    if ($this->allow_domain_settings()) {
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
                    </p>
                    </form>
                    <?php
                    }
                    ?>
                
            </div>
        <?php
        
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
            $home_url=$this->ext->get_option('home');
        }
        
        if ( is_admin() )
        {
            $site_settings = $this->ext->get_option('livefyre_site_id', false);
            $message = false;
            if ( $site_settings ) {
                if ( $this->is_settings_page() ) {
                    return;
                }
                if ( $this->ext->get_option('livefyre_v3_notify_installed', false) ) {
                    $message = "Thanks for installing the new Livefyre plugin featuring Livefyre Comments 3! Visit your <a href=\"./options-general.php?page=livefyre\">Livefyre settings</a> to import your old comments.";
                } elseif ( $this->ext->get_option('livefyre_v3_notify_upgraded', false) ) {
                    $message = "Thanks for upgrading to the latest Livefyre plugin. Your posts should now be running Comments 3.";
                }
                if ($message) {
                    $message = $message . ' <a href="./options-general.php?page=livefyre&livefyre_reset_v3_notes=1">Got it, thanks!</a>';
                }
            } elseif (!$this->returned_from_setup()) {
                $message = '<strong>' . __( 'Livefyre is almost ready.' ) . '</strong> ' . 'You must <a href="'.$livefyre_http_url.'/installation/logout?site_url='.urlencode($home_url).'&domain='.$livefyre_site_domain.'&version='.LF_PLUGIN_VERSION.'&type=wordpress&lfversion=3&postback_hook='.urlencode($home_url.'/?lf_wp_comment_postback_request=1').'&transport=http">confirm your blog configuration with livefyre.com</a> for it to work.';
            }
            if ($message) {
                echo Livefyre_Admin::lf_warning_display($message);
            }
        }
    }
    
    function returned_from_setup() {
        return (isset($_GET['lf_login_complete']) && $_GET['lf_login_complete']=='1');
    }
    
    function is_settings_page() {
        return (isset($_GET['page']) && $_GET['page'] == 'livefyre');
    }

}


class Livefyre_Display {

    function __construct( $lf_core ) {
    
        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        
        if ( ! $this->livefyre_comments_off() ) {
            add_action( 'wp_head', array( &$this, 'lf_embed_head_script' ) );
            add_action( 'wp_footer', array( &$this, 'lf_init_script' ) );
            add_filter( 'comments_template', array( &$this, 'livefyre_comments' ) );
            add_filter( 'comments_number', array( &$this, 'livefyre_comments_number' ), 10, 2 );
        }
    
    }

    function livefyre_comments_off() {
    
        return ( $this->ext->get_option( 'livefyre_site_id', '' ) == '' );

    }
    
    function lf_embed_head_script() {
        global $wp_query;
        $profile_sys = $this->ext->get_network_option( 'livefyre_profile_system', 'livefyre' );
        if ($profile_sys == 'lfsp') {
                $lfsp_source_url = $this->ext->get_network_option( 'livefyre_lfsp_source_url', '' );
                echo '<script type="text/javascript" src="' . $lfsp_source_url . '"></script>';
        }
        if ( $this->ext->get_post_version( $wp_query->post->ID ) == '1' ) {
            echo $this->lf_core->lf_domain_object->source_js_v1();
        } else {
            echo $this->lf_core->lf_domain_object->source_js_v3();
        }
    }
    
    function lf_init_script() {

        global $post, $current_user, $wp_query;
        $network = $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
        if ( comments_open() && $this->livefyre_show_comments() ) {// is this a post page?
            if( $parent_id = wp_is_post_revision( $wp_query->post->ID ) ) {
                $original_id = $parent_id;
            } else {
                $original_id = $wp_query->post->ID;
            }
            $post_obj = get_post( $wp_query->post->ID );
            $tags = array();
            $posttags = get_the_tags( $wp_query->post->ID );
            if ($posttags) {
                foreach($posttags as $tag) {
                    array_push( $tags, $tag->name );
                }
            }
            $domain = $this->lf_core->lf_domain_object;
            $site = $this->lf_core->site;
            $article = $site->article( $original_id, get_permalink($original_id), get_the_title($original_id) );
            $conv = $article->conversation();
            $use_backplane = $this->ext->get_network_option( 'livefyre_use_backplane', false );
            $initcfg = array();
            $profile_sys = $this->ext->get_network_option( 'livefyre_profile_system', 'livefyre' );
            $initcfg['betaBanner'] = ($profile_sys == 'livefyre');
            if ( !$use_backplane && $network != LF_DEFAULT_PROFILE_DOMAIN) {
                if ( is_user_logged_in() && $profile_sys == 'wordpress' ) {
                    echo $domain->authenticate_js_v3( '?livefyre_token_request=1', '/' );
                    $initcfg['onload'] = 'doLivefyreAuth';
                }
            }
            if ($this->ext->get_network_option( 'livefyre_wp_auth_hooks', false )) {
                ?>
                <script type="text/javascript">
                    var authDelegate = {
                         login: function(){document.location.href="<?php echo wp_login_url( get_permalink() ); ?>";return true;},
                        logout: function(){document.location.href="<?php echo wp_logout_url( get_permalink() ); ?>";return true;}
                    };
                </script>
                <?php
                $initcfg['delegate'] = 'authDelegate';
            } elseif ($profile_sys == 'lfsp') {
                ?>
                <script type="text/javascript">
                    var authDelegate = new fyre.conv.SPAuthDelegate({engage: {app: "<?php echo $this->ext->get_network_option( 'livefyre_engage_appname', '' )  ?>"}});
                </script>
                <?php
                $initcfg['delegate'] = 'authDelegate';
            }
            if ( $this->ext->get_post_version( $wp_query->post->ID ) == '1' ) {
                //to_initjs( $user = null, $display_name = null, $backplane = false, $jquery_ready = false, $include_source = true )
                echo $conv->to_initjs( $backplane = $use_backplane );
            } else {
                echo $conv->to_initjs_v3('comments', $initcfg, $use_backplane);
            }
        } else if ( !is_single() ) {
            echo '<script type="text/javascript" data-lf-domain="' . $network . '" id="ncomments_js" src="'.$this->lf_core->assets_url.'/wjs/v1.0/javascripts/CommentCount.js"></script>';
        }

    }

    function livefyre_comments( $cmnts ) {

        return dirname( __FILE__ ) . '/comments-template.php';

    }

    function livefyre_show_comments(){

        return ( is_single() || is_page() ) && ! is_preview();

    }


    function livefyre_comments_number( $count ) {

        global $post;
        return '<span data-lf-article-id="' . $post->ID . '" data-lf-site-id="' . get_option('livefyre_site_id', '') . '" class="livefyre-commentcount">'.$count.'</span>';

    }
    
}

if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC. '/class-http.php' );

class Livefyre_Http_Extension {
    // Map the Livefyre request signature to what WordPress expects.
    // This just means changing the name of the payload argument.
    public function request( $url, $args = array() ) {
        $http = new WP_Http;
        if ( isset( $args[ 'data' ] ) ) {
            $args[ 'body' ] = $args[ 'data' ];
            unset( $args[ 'data' ] );
        }
        return $http->request( $url, $args );
    }
}

$livefyre = new Livefyre_core;

add_action( 'livefyre_check_for_sync', 'check_site_sync' );

function write_to_log( $msg ) {
    if ( WP_DEBUG === false ) {
        return;
    }
    error_log( $msg );
}
/*
* To alleviate site_syncs not firing, check to make sure they are set up
*/
function setup_sync_check() {
    $hook = 'livefyre_check_for_sync';
    if ( !wp_next_scheduled( $hook ) ) {
        write_to_log( 'Livefyre: Setting up sync_check.' );
        wp_schedule_event( time(), 'hourly', 'livefyre_check_for_sync' );
    }
}

/*
* Is there a site sync scheduled? (There should be...) If not schedule one for 7 hours down the road
*/

function check_site_sync() {
    $hook = 'livefyre_sync';
    $msg = '';
    $timeout = time();
    write_to_log( 'Livefyre: Checking for a site sync.' );
    if ( wp_next_scheduled( $hook ) > time() ) {
        return;
    }
    if ( !wp_next_scheduled( $hook ) ) {
        // Nothing scheduled for site sync
        $msg = 'Livefyre: Scheduling a site sync. Don\'t know why one is not scheduled.';
        $timeout += LF_SYNC_LONG_TIMEOUT;
    }
    elseif ( wp_next_scheduled( $hook ) < time() ) {
        // Sync was scheduled, but now timestamp is now expired
        $msg = "Livefyre: Site sync cron job expired. Scheduling sync on short timeout";
        $timeout += LF_SYNC_SHORT_TIMEOUT;
        wp_clear_scheduled_hook( $hook );
    }
    write_to_log( $msg );
    wp_schedule_single_event( $timeout, $hook );

}

setup_sync_check();

?>
