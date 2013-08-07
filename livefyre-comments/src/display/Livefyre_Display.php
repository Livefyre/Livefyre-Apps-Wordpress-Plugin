<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

class Livefyre_Display {

    function __construct( $lf_core ) {
    
        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        
        if ( ! $this->livefyre_comments_off() ) {
            add_action( 'wp_enqueue_scripts', array( &$this, 'lf_embed_head_scripts' ) );
            add_action( 'wp_footer', array( &$this, 'lf_init_script' ) );
            add_action( 'wp_footer', array( &$this, 'lf_debug' ) );
            // Set comments_template filter to maximum value to always override the default commenting widget
            add_filter( 'comments_template', array( &$this, 'livefyre_comments' ), $this->lf_widget_priority() );
            add_filter( 'comments_number', array( &$this, 'livefyre_comments_number' ), 10, 2 );
        }
    
    }

    function livefyre_comments_off() {
    
        return ( $this->ext->get_option( 'livefyre_site_id', '' ) == '' );

    }

    function lf_widget_priority() {

        return intval( $this->ext->get_option( 'livefyre_widget_priority', 99 ) );

    }

    function lf_embed_head_scripts() {
        global $wp_query;
        $profile_sys = $this->ext->get_network_option( 'livefyre_profile_system', 'livefyre' );
        if ($profile_sys == 'lfsp') {
                $lfsp_source_url = $this->ext->get_network_option( 'livefyre_lfsp_source_url', '' );
                wp_enqueue_script('lfsp', $lfsp_source_url);
        }
        
        $zor_source_url = 'http://zor.'
        . ( 1 == get_option( 'livefyre_environment', '0' ) ?  "livefyre.com" : get_option( 'livefyre_domain_name' ) )
        . '/wjs/v3.0/javascripts/livefyre.js';
        
        wp_enqueue_script('zor', $zor_source_url, array(), null, false);

        if ( function_exists ( 'livefyre_strings_chooser') ) {
            $file_url = livefyre_strings_chooser();
            wp_enqueue_script('lf_custom_strings', $file_url, array(), null, false);
            return;
        }

        $language = get_option( 'livefyre_language', 'English' );
        if ( $language == 'English' ) {
            return;
        }
        wp_enqueue_script('lf_language', plugins_url('/languages/' . $language . '.js', dirname(dirname( __FILE__ ))), array(), null, false );

    }
    
    function lf_init_script() {
    /*  Reset the query data because theme code might have moved the $post gloabl to point 
        at different post rather than the current one, which causes our JS not to load properly. 
        We do this in the footer because the wp_footer() should be the last thing called on the page.
        We don't do it earlier, because it might interfere with what the theme code is trying to accomplish.  */
        wp_reset_query();

        global $post, $current_user, $wp_query;
        $network = $this->ext->get_network_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
        if ( comments_open() && $this->livefyre_show_comments() ) {   // is this a post page?
            if( $parent_id = wp_is_post_revision( $wp_query->post->ID ) ) {
                $original_id = $parent_id;
            } else {
                $original_id = $wp_query->post->ID;
            }
            $post_obj = get_post( $wp_query->post->ID );
            $tags = array();
            $posttags = get_the_tags( $wp_query->post->ID );
            if ( $posttags ) {
                foreach( $posttags as $tag ) {
                    array_push( $tags, $tag->name );
                }
            }
            $domain = $this->lf_core->lf_domain_object;
            $site = $this->lf_core->site;
            if ( empty( $tags ) ) {
                $article = $site->article( $original_id, get_permalink($original_id), get_the_title($original_id));
            }
            else {
                $article = $site->article( $original_id, get_permalink($original_id), get_the_title($original_id), $tags=$tags );
            }
            $conv = $article->conversation();
            $initcfg = array();

            if ( $network != LF_DEFAULT_PROFILE_DOMAIN ) {
                if ( function_exists( 'livefyre_onload_handler' ) ) {
                    $initcfg['onload'] = livefyre_onload_handler();
                }
                if ( function_exists( 'livefyre_delegate_name' ) ) {
                    $initcfg['delegate'] = livefyre_delegate_name();
                }
                if ( get_option( 'livefyre_auth_delegate_name', '' ) != '' ) {
                    $initcfg['delegate'] = get_option( 'livefyre_auth_delegate_name', '' );
                }
                if ( function_exists ( 'livefyre_strings_chooser') ) {
                    $filename = livefyre_strings_chooser();
                    $initcfg['strings'] = $this->load_strings( $filename );
                }
                else {
                    $language = get_option( 'livefyre_language', 'English' );
                    $initcfg['strings'] = $this->load_strings( $language );
                }
            }
            else {
                $language = get_option( 'livefyre_language', 'English' );
                $initcfg['strings'] = $this->load_strings( $language );
            }
            // Do we need to add in some things for Enterprise?
            echo $conv->to_initjs_v3( 'livefyre-comments', $initcfg );
        }

        if ( !is_single() ) {
            echo '<script type="text/javascript" data-lf-domain="' . $network . '" id="ncomments_js" src="'.$this->lf_core->assets_url.'/wjs/v1.0/javascripts/CommentCount.js"></script>';
        }

    }

    function lf_debug() {

        global $post;
        $post_type = get_post_type( $post );
        $article_id = $post->ID;
        $site_id = get_option( 'livefyre_site_id', '' );
        $display_posts = get_option( 'livefyre_display_posts', 'true' );
        $display_pages = get_option( 'livefyre_display_pages', 'true' );
        echo "\n";
        ?>
            <!-- LF DEBUG
            site-id: <?php echo $site_id . "\n"; ?>
            article-id: <?php echo $article_id . "\n"; ?>
            post-type: <?php echo $post_type . "\n"; ?>
            comments-open: <?php echo comments_open() ? "true\n" : "false\n"; ?>
            is-single: <?php echo is_single() ? "true\n" : "false\n"; ?>
            display-posts: <?php echo $display_posts . "\n"; ?>
            display-pages: <?php echo $display_pages . "\n"; ?>
            -->
        <?php
        
    }

    function livefyre_comments( $cmnts ) {

        return dirname( __FILE__ ) . '/comments-template.php';

    }

    function livefyre_show_comments() {
        
        global $post;
        /* Is this a post and is the settings checkbox on? */
        $display_posts = ( is_single() && get_option( 'livefyre_display_posts','true') == 'true' );
        /* Is this a page and is the settings checkbox on? */
        $display_pages = ( is_page() && get_option( 'livefyre_display_pages','true') == 'true' );
        /* Are comments open on this post/page? */
        $comments_open = ( $post->comment_status == 'open' );

        $display = $display_posts || $display_pages;
        $post_type = get_post_type();
        if ( $post_type != 'post' && $post_type != 'page' ) {
            
            $post_type_name = 'livefyre_display_' .$post_type;            
            $display = ( get_option( $post_type_name, 'true' ) == 'true' );
        }

        return $display
            && !is_preview()
            && $comments_open;

    }

    function livefyre_comments_number( $count ) {

        global $post;
        return '<span data-lf-article-id="' . $post->ID . '" data-lf-site-id="' . get_option( 'livefyre_site_id', '' ) . '" class="livefyre-commentcount">'.$count.'</span>';

    }

    function load_strings( $language ) {

        if ( $language == 'English' ) {
            return '';
        }
        return 'customStrings';
    }
    
}
