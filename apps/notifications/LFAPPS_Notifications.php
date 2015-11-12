<?php
/*
Sub Plugin Name: Notifications
Plugin URI: http://www.livefyre.com/
Description: Implements Notifications
Version: 0.1
Author: Livefyre, Inc.
Author URI: http://www.livefyre.com/
 */

//Disallow direct access to this file
if(!defined('LFAPPS__PLUGIN_PATH')) 
    die('Bye');

use Livefyre\Livefyre;

require_once LFAPPS__PLUGIN_PATH . 'libs/php/LFAPPS_View.php';

if ( ! class_exists( 'LFAPPS_Notifications' ) ) {
    class LFAPPS_Notifications {
        public static $default_package_version = '3.0.0';
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
            if(self::notifications_active())
                add_shortcode('livefyre_notifications', array('LFAPPS_Notifications', 'init_shortcode'));
        }
        
        public static function set_default_options() {
            if(get_option('livefyre_apps-livefyre_notifications_version', '') === '') {
                update_option('livefyre_apps-livefyre_notifications_version', 'latest');
            }            
        }
        
        public static function init_shortcode($atts=array()) {
            return LFAPPS_View::render_partial('script', 
                    compact(), 
                    'notifications', true);
        }
                
        /**
         * Check if comments are active and there are no issues stopping them from loading
         * @return boolean
         */
        public static function notifications_active() {
            return ( Livefyre_Apps::active());
        }
        
        /*
         * Handles the toggles on the settings page that decide which post types should be shown.
         * Also prevents comments from appearing on non single items and previews.
         *
         */
        public static function show_notifications() {
            return Livefyre_Apps::is_app_enabled('notifications');
        }
    }
}
?>
