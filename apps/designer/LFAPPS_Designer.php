<?php
/*
Sub Plugin Name: Livefyre Designer Apps
Plugin URI: http://www.livefyre.com/
Description: Implements Designer embeds
Version: 1.3
Author: Livefyre, Inc.
Author URI: http://www.livefyre.com/
 */

//Disallow direct access to this file
if(!defined('LFAPPS__PLUGIN_PATH')) 
    die('Bye');

use Livefyre\Livefyre;

require_once LFAPPS__PLUGIN_PATH . 'libs/php/LFAPPS_View.php';

if ( ! class_exists( 'LFAPPS_Designer' ) ) {
    class LFAPPS_Designer {
        public static $default_package_version = '0.6.6';
        private static $initiated = false;
        
        public static function init() {
            if ( ! self::$initiated ) {
                self::$initiated = true;
                self::init_hooks();    
                self::set_default_options();
            }
        }

        /**
         * Initialise WP hooks
         */
        private static function init_hooks() {
            if(self::designer_active())
                add_shortcode('livefyre_designer', array('LFAPPS_Designer', 'init_shortcode'));
        }
        
        public static function set_default_options() {
            if(get_option('livefyre_apps-livefyre_designer_version', '') === '') {
                update_option('livefyre_apps-livefyre_designer_version', 'latest');
            }            
        }
        
        public static function init_shortcode($atts=array()) {
            if(!self::show_designer()) {
                return;
            }
            $designerAppId = $atts['app_id'];
            return LFAPPS_View::render_partial('script', compact('designerAppId'), 'designer', true);
        }
                
        /**
         * Check if comments are active and there are no issues stopping them from loading
         * @return boolean
         */
        public static function designer_active() {
            return ( Livefyre_Apps::active());
        }
        
        /**
         * Get the Livefyre.require package reference name and version
         * @return string
         */
        public static function get_package_reference() {
            $option_version = get_option('livefyre_apps-livefyre_designer_version');
            $available_versions = Livefyre_Apps::get_available_package_versions('app-embed'); 
            if(empty($available_versions)) {
                $available_versions = array(LFAPPS_Desginer::$default_package_version);
            }
            $required_version = Livefyre_Apps::get_package_reference();
            if(is_null($required_version)) {
                if($option_version == 'latest') {
                    //get latest version
                    $latest_version = array_pop($available_versions);
                    if(strpos($latest_version, '.') !== false) {
                        $required_version = substr($latest_version, 0, strpos($latest_version, '.'));
                    } else {
                        $required_version = $latest_version;
                    }
                } else {
                    $required_version = $option_version;
                }
            }
            
            return 'app-embed#'.$required_version;
        }
        
        /*
         * Handles the toggles on the settings page that decide which post types should be shown.
         * Also prevents comments from appearing on non single items and previews.
         *
         */

        public static function show_designer() {

            global $post;
            /* Is this a post and is the settings checkbox on? */
            $display_posts = ( is_single() && get_option('livefyre_apps-livefyre_designer_display_post'));
            /* Is this a page and is the settings checkbox on? */
            $display_pages = ( is_page() && get_option('livefyre_apps-livefyre_designer_display_page'));
 
            $display = $display_posts || $display_pages;
            $post_type = get_post_type();
            if ($post_type != 'post' && $post_type != 'page') {

                $post_type_name = 'livefyre_designer_display_' . $post_type;
                $display = ( get_option('livefyre_apps-'.$post_type_name, 'true') == 'true' );
                if($post_type === false) {
                    $display = true;
                }
            }
            return $display
                && Livefyre_Apps::is_app_enabled('designer')
                && !is_preview();
        }
    }
}
?>
