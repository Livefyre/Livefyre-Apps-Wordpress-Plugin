<?php
/*
Author: Livefyre, Inc.
Version: 4.0.7
Author URI: http://livefyre.com/
*/

class Livefyre_Settings {

    function update_posts ( $id, $post_type ) {
        global $wpdb;
        $db_prefix = $wpdb->base_prefix;
        if( $id ) {
            $query = "
                UPDATE $wpdb->posts SET comment_status = 'open'
                WHERE ID = " .$id. "
                    AND comment_status = 'closed' 
                    AND post_type IN ('page','post')
                    AND post_status = 'publish'
                ";
        }
        else {
            $query = "
                UPDATE $wpdb->posts SET comment_status = 'open'
                WHERE comment_status = 'closed'
                    AND post_type = '" .$post_type. "'
                    AND post_status = 'publish'
                ";
        }
        return $wpdb->get_results( $query );
    }

    function select_posts ( $post_type ) {
        global $wpdb;
        $query = "
            SELECT ID, post_title
            FROM $wpdb->posts
            WHERE comment_status = 'closed' 
                AND post_type = '" .$post_type. "'
                AND post_status = 'publish'
            ORDER BY DATE(`post_date`) DESC
            LIMIT 50
            ";
        return $wpdb->get_results( $query );
    }

    function display_no_allows ( $post_type, $list ) {

        ?>
        <div id="fyreallowheader">
            <?php
            if ( $post_type == 'post' ) {
            ?>
                <h1>Post:</h1>
                <a href="?page=livefyre&allow_comments_id=all_posts" text-decoration:"none">Enable All</a>
            <?php
            }
            else {
            ?>
                <h1>Page:</h1>
                <a href="?page=livefyre&allow_comments_id=all_pages" text-decoration:"none">Enable All</a>
            <?php
            }
            ?>
        </div>
        <ul>
            <?php
            foreach ( $list as $ncpost ) {
                echo '<li>ID: <span>' .$ncpost->ID. "</span>  Title:</span> <span><a href=" .get_permalink($ncpost->ID). ">" .$ncpost->post_title. "</a></span>";
                echo '<a href="?page=livefyre&allow_comments_id=' .$ncpost->ID. '" class="fyreallowbutton">Enable</a></li>';
            }
        ?>
        <ul>
        <?php
    }

    function get_fyre_status ( $plugins_count, $disabled_posts_count, $disabled_pages_count, $import_status ) {
    
        if ( $this->get_total_errors( $plugins_count, $disabled_posts_count, $disabled_pages_count, $import_status ) == 0 ) {
            return Array('All systems go!', 'green');
        }
        if ( $plugins_count >= 1 ) {
            return Array('Error, conflicting plugins', 'red');
        }
        return Array('Warning, potential issues', 'yellow');

    }

    function get_total_errors( $plugins_count, $disabled_posts_count, $disabled_pages_count, $import_status ) {

        return ( $plugins_count + $disabled_pages_count + $disabled_posts_count + ( $import_status != 'complete' ? 1 : 0) );

    }

}
