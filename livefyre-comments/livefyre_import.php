<?php

global $livefyre_comment_filter_enabled;
global $wpdb;

class Livefyre_Import {
    
    function __construct( $lf_core ) {

        $this->lf_core = $lf_core;
        $this->ext = $lf_core->ext;
        $this->ext->setup_import( $this );

    }

    static function skip_trackback_filter($c) {
        if ($c->comment_type == 'trackback' || $c->comment_type == 'pingback') { return false; }
        return true;
    }
    
    function admin_import_notice() {
        return; //todo: re-enable this
        if (!is_admin() || $_GET["page"] != "livefyre" ||
            $this->ext->get_option('livefyre_import_status', '') != '' ||
            $this->ext->get_option('livefyre_site_id', '') == '') {
            return;
        }
        echo "<div id='livefyre-import-notice' class='updated fade'><p><a href='?page=livefyre&livefyre_import_begin=true'>Click here</a> to import your comments.</p></div>";
    }

    function run_begin() {
        try {
            $this->begin();
        }
        catch (Exception $e) {
            try {
                $this->lf_core->Logger->add('Livefyre Import: Exception occured in begin - ' . $e->getMessage());
                $this->lf_core->Raven->captureException($e);
            }
            catch (Exception $f) {}
            throw $e;
        }
    }

    function begin() {

        $this->lf_core->Logger->add( "Livefyre: Beginning an import process." );

        if (!isset($_GET['page']) || $_GET['page'] != 'livefyre' || !isset($_GET['livefyre_import_begin'])) {
            return;
        }
        $siteId = $this->ext->get_option('livefyre_site_id', '');
        if ($siteId == '') {
            return;
        }
        $url = $this->lf_core->quill_url . '/import/wordpress/' . $siteId . '/start';
        $http = $this->lf_core->lf_domain_object->http;
        $resp = $http->request($url, array('method' => 'POST'));

        if (is_wp_error($resp)) {
            $status = 'error';
            $message = $resp->get_error_message();
        } else {
            $json = json_decode($response);
            $status = $json->status;
            $message = $json->message;
        }

        if ($status == 'error') {
            $this->ext->update_option('livefyre_import_status', 'error');
            $this->ext->update_option('livefyre_import_message', $message);
            $this->ext->delete_option('livefyre_v3_notify_installed');
        } else {
            $this->ext->update_option('livefyre_import_status', 'started');
            $this->ext->delete_option('livefyre_v3_notify_installed');
        }
        
    }

    function run_check_activity_map_import() {
        try {
            $this->check_activity_map_import();
        }
        catch (Exception $e) {
            try {
                $this->lf_core->Logger->add('Livefyre Import : Exception occured in check_activity_map_import - ' . $e->getMessage());
                $this->lf_core->Raven->captureException($e);
            }
            catch (Exception $f) {}
            throw $e;
        }
    }

    function check_activity_map_import() {

        if (!isset($_POST['activity_map'])) {
            return;
        }

        global $wpdb;
        $activity_map = $_POST['activity_map'];
        $rows = explode("\n", $activity_map);
        $i = 0;

        foreach ($rows as $row) {
            $rowparts = explode(",", $row);
            $this->lf_core->Logger->add( "comment import req received from livefyre, inserting: $rowparts[0], $rowparts[1], $rowparts[2]", true );
            $this->ext->activity_log( $rowparts[0], $rowparts[1], $rowparts[2] );
            $i++;
        }
        $this->ext->update_option('livefyre_activity_id', $rowparts[0]);
        $this->ext->update_option('livefyre_import_status', 'csv_uploaded');
        $date_formatted = 'Completed on ' . date('d/m/Y') . ' at ' . date('h:i a');
        $this->ext->update_option('livefyre_import_message', $date_formatted);
        $this->ext->delete_option('livefyre_v3_notify_installed');
        echo "ok";
        exit;
    }

    function run_check_import() {
        try {
            $this->check_import();
        }
        catch (Exception $e) {
            try {
                $this->lf_core->Logger->add('Livefyre Import: Exception occured in check_import - ' . $e->getMessage());
                $this->lf_core->Raven->captureException($e);
            }
            catch (Exception $f) {}
            throw $e;
        }
    }

    function check_import() {

        $this->lf_core->Logger->add( "Livefyre: Checking on an import." );
        // Make sure we're allowed to import comments
        if (!isset($_GET['livefyre_comment_import']) || !isset($_GET['offset'])) {
            return;
        }
        // Get the decoded sig values from the $_POST object
        $sig = $_POST['sig'];
        $sig_created = urldecode($_POST['sig_created']);
        // Check the signature
        $this->lf_core->Logger->add( 'comment import req received from livefyre', true );
        $key = $this->ext->get_option('livefyre_site_key');
        $string = 'import|' . $_GET['offset'] . '|' . $sig_created;
        $this->lf_core->Logger->add( ' -comment import req sig inputs: ' . $string . ' input sig:' . $sig, true );
        if (getHmacsha1Signature(base64_decode($key), $string) != $sig || abs($sig_created-time()) > 259200) {
            $this->lf_core->Logger->add( ' -sig failed', true );
            echo 'sig-failure';
            exit;
        } else {
            $this->lf_core->Logger->add( ' -sig correct, rendering', true );
            $siteId = $this->ext->get_option('livefyre_site_id', '');
            if ($siteId != '') {
                $response = $this->extract_xml($siteId, intval($_GET['offset']));
                echo $response;
                exit;
            } else {
                $this->lf_core->Logger->add( ' -tried to render, but no blogid', true );
                echo 'missing-blog-id';
                exit;
            }
        }
    }

    function check_utf_conversion() {
        global $livefyre_comment_filter_enabled;

        if (!isset($livefyre_comment_filter_enabled)) {
            $test_string = 'Testing 1 2 3!! ?/#@';
            $converted = $this->comment_data_filter($test_string, true);
            if ($converted != $test_string) {
                $livefyre_comment_filter_enabled = false;
            } else {
                $livefyre_comment_filter_enabled = true;
            }
        }
        return $livefyre_comment_filter_enabled;
    }

    function comment_data_filter( $comment, $test=false ) {
        if ($test || $this->check_utf_conversion()) {
            $before = $comment;
            if (function_exists( 'iconv' )) {
                $unicode = array_filter($this->utf8_to_unicode_code($comment), array(&$this, 'filter_unicode_longs'));
                $comment = $this->unicode_code_to_utf8($unicode);
            }
            $after = $comment;
            if ($this->ext->get_option('livefyre_cleaned_data', 'no') == 'no' && $before != $after) {
                $this->ext->update_option('livefyre_cleaned_data', 'yes');
                $this->report_error("before and after are different when exporting content, this means we saw bad data and cleaned it up\nbefore:\n$before\n\nafter:\n$after");
            }
        }
        $comment = preg_replace('/\&/', '&amp;', $comment);
        $comment = preg_replace('/\>/', '&gt;', $comment);
        $comment = preg_replace('/\</', '&lt;', $comment);
        return $comment;
    }

    function extract_xml( $siteId, $offset=0 ) {

        $this->lf_core->Logger->add( "Livefyre: Extracting XML." );
        $maxqueries = 50;
        $maxlength = 500000;
        $index = $offset;
        $next_chunk = false;
        $total_queries = 0;
        do {
            $total_queries++;
            if ($total_queries > $maxqueries) {
                $next_chunk = true;
                break;
            }
            $args = array(
                'post_type' => 'any',
                'numberposts' => 20,
                'offset' => $index
            );
            $myposts = get_posts($args);
            if (!isset($articles)) {
                $articles = '';
            }
            $inner_idx = 0;
            foreach ($myposts as $post) {
                if ($parent_id = wp_is_post_revision($post->ID)) {
                    $post_id = $parent_id;
                } else {
                    $post_id = $post->ID;
                }
                $newArticle = '<article id="' . $post_id . '"><title>' . $this->comment_data_filter($post->post_title) . '</title><source>' . get_permalink($post->ID) . '</source>';
                if ($post->post_date_gmt != null && !strstr($post->post_date_gmt, '0000-00-00')) {
                    $newArticle .= '<created>' . preg_replace('/\s/', 'T', $post->post_date_gmt) . 'Z</created>';
                }
                $comment_array = get_approved_comments($post->ID);
                $comment_array = array_filter($comment_array, array(Livefyre_Import, 'skip_trackback_filter'));
                foreach ($comment_array as $comment) {
                    $comment_content = $this->comment_data_filter($comment->comment_content);
                    if ($comment_content == "") {
                        continue; #don't sync blank
                    }
                    $commentParent = ($comment->comment_parent ? " parent-id=\"$comment->comment_parent\"" : '');
                    $commentXML = "<comment id=\"$comment->comment_ID\"$commentParent>";
                    $commentXML .= '<author format="html">' . $this->comment_data_filter($comment->comment_author) . '</author>';
                    $commentXML .= '<author-email format="html">' . $this->comment_data_filter( $comment->comment_author_email ) . '</author-email>';
                    $commentXML .= '<author-url format="html">' . $this->comment_data_filter( $comment->comment_author_url) . '</author-url>';
                    $commentXML .= '<body format="wphtml">' . $comment_content . '</body>';
                    $use_date = $comment->comment_date_gmt;
                    if ($use_date == '0000-00-00 00:00:00Z') {
                        $use_date = $comment->comment_date;
                    }
                    if ($use_date != null && !strstr($use_date, '0000-00-00')) {
                        $commentXML .= '<created>' . preg_replace('/\s/', 'T', $use_date) . 'Z</created>';
                    } else {
                        // We need to supply a datetime so the XML parser does not fail
                        $now = new DateTime;
                        $commentXML .= '<created>' . $now->format('Y-m-d\TH:i:s\Z') . '</created>';
                    }
                    $commentXML .= '</comment>';
                    $newArticle .= $commentXML;
                }
                $newArticle .= '</article>';
                if (strlen($newArticle) + strlen($articles) > $maxlength && strlen($articles)) {
                    $next_chunk = true;
                    break;
                } else {
                    $inner_idx += 1;
                    $articles .= $newArticle;
                }
                unset($newArticle);
            }
        } while (count($myposts) != 0 && !$next_chunk && ($index = $index + 10));
        if (strlen($articles) == 0) {
            return 'no-data';
        } else {
            return 'to-offset:' . ($inner_idx + $index) . "\n" . $this->wrap_xml($articles);
        }
    }

    function filter_unicode_longs( $long ) {
        return ($long == 0x9 || $long == 0xa || $long == 0xd || ($long >= 0x20 && $long <= 0xd7ff) || ($long >= 0xe000 && $long <= 0xfffd) || ($long >= 0x10000 && $long <= 0x10ffff));
    }

    function report_error( $message ) { 
        $args = array('data' => array('message' => $message, 'method' => 'POST'));
        $url = $this->lf_core->http_url . '/site/' . $this->ext->get_option('livefyre_site_id');
        $this->lf_core->lf_domain_object->http->request($url . '/error', $args);
    }

    function unicode_code_to_utf8( $unicode_list ) {
        $result = "";
        foreach ($unicode_list as $key => $value) {
            $one_character = pack("L", $value);
            $result .= iconv("UTF-32", "UTF-8", $one_character);
        }
        return $result;
    }

    function utf8_to_unicode_code( $utf8_string ) {
        $expanded = iconv("UTF-8", "UTF-32", $utf8_string);
        return unpack("L*", $expanded);
    }

    function wrap_xml( &$articles ) {
        return '<?xml version="1.0" encoding="UTF-8"?><site xmlns="http://livefyre.com/protocol" type="wordpress">' . $articles . '</site>';
    }

}



?>