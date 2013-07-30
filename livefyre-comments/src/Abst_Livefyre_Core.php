<?php
/*
Livefyre Realtime Comments Core Module

This library is shared between all Livefyre plugins.

Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

define( 'LF_DEFAULT_PROFILE_DOMAIN', 'livefyre.com' );
define( 'LF_DEFAULT_TLD', 'livefyre.com' );

abstract class Abst_Livefyre_Core {

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

    }

    function require_subclasses() {

    }

} //  Livefyre_core
