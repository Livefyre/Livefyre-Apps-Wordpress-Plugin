<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

require_once( dirname( __FILE__ ) . "/Livefyre_Settings.php" );
$livefyre_settings = new Livefyre_Settings();

global $wpdb;
if ( function_exists( 'home_url' ) ) {
    $home_url=home_url();
} else {
    $home_url=get_option( 'home' );
}

$site_id=get_option( 'livefyre_site_id', '' ); 

if ( isset( $_GET['status'] ) ) {
    update_option( 'livefyre_import_status', $_GET['status'] );
    if ( isset( $_GET['message'] ) ) {
        update_option( 'livefyre_import_message', urldecode( $_GET['message'] ) );
    }
} elseif ( isset( $_GET['livefyre_reset_v3_notes'] ) ) {
    delete_option( 'livefyre_v3_notify_installed' );
    delete_option( 'livefyre_v3_notify_upgraded' );
}
?>

<script type="text/javascript">

//Lightweight JSONP fetcher - www.nonobtrusive.com
var JSONP=(function(){var a=0,c,f,b,d=this;function e(j){var i=document.createElement("script"),h=false;i.src=j;i.async=true;i.onload=i.onreadystatechange=function(){if(!h&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){h=true;i.onload=i.onreadystatechange=null;if(i&&i.parentNode){i.parentNode.removeChild(i)}}};if(!c){c=document.getElementsByTagName("head")[0]}c.appendChild(i)}function g(h,j,k){f="?";j=j||{};for(b in j){if(j.hasOwnProperty(b)){f+=b+"="+j[b]+"&"}}var i="json"+(++a);d[i]=function(l){k(l);d[i]=null;try{delete d[i]}catch(m){}};e(h+f+"callback="+i);return i}return{get:g}}());

var secondsPassed = 0;
var stub = "Progress: ";

function checkStatusLF(){
    JSONP.get( '<?php echo $this->lf_core->quill_url ?>/import/wordpress/<?php echo get_option("livefyre_site_id") ?>/status', {param1:'none'}, function(data){
        console.log('REPSONSE:', data);
        var status = data['status'],
            loc = '?page=livefyre';

        switch(status) {
            case 'aborted':
            case 'failed':
                // Statuses that signal a stopping point in the process.
                loc += '&status=error';
                if (data['import_failure'] && data['import_failure']['message']) {
                    loc += '&message=' + data['import_failure']['message'];
                }
                window.location.href = loc;
                break;
            
            default:
                secondsPassed++;
                if(secondsPassed <= 20) {
                    message = "Warming up the engine...";
                }
                else if(secondsPassed >= 20 && secondsPassed < 60) {
                    message = "Starting the move...";
                }
                else if(secondsPassed >= 60 && secondsPassed < 30) {
                    message = "Hang tight, work in progress...";
                }
                else if(secondsPassed >= 300 && secondsPassed < 600) {
                    message = "We're still cranking away!";
                }
                else if(secondsPassed >= 600 && secondsPassed < 1800) {
                    message = "Maybe it's time for a candy bar.";
                }
                else if(secondsPassed >= 1800 && secondsPassed < 2700) {
                    message = 'In the meantime, check out our Facebook page at <a href="http://www.facebook.com/livefyre">facebook.com/livefyre</a>';
                }
                else if(secondsPassed >= 2700 && secondsPassed < 3600) {
                    message = "Boy, you have one popular website...";
                }
                else {
                    message = "Still working here. Thanks for your patience.";
                }
                document.getElementById("livefyre-import-text").innerHTML = stub + message;
        }
        if (status === 'complete') {
            window.location.href = window.location.href.split('?')[0] + '?page=livefyre';
        }
    });
}

function livefyre_start_ajax(iv) {
    window.checkStatusInterval=setInterval(
        checkStatusLF, 
        iv
    );
    checkStatusLF();
}

</script>

<?php

if (isset($_GET['hide_import_message'])) {
    $this->lf_core->Livefyre_Utility->update_import_status('complete');
    ?>
    <script type="text/javascript">
        window.location.href = window.location.pathname + '?page=livefyre';
    </script>
    <?php
    exit;
}

if (isset($_POST['textfield'])) {
    echo username();
    return;
}

$import_status = get_option('livefyre_import_status','uninitialized');

// Handle legacy values
if ( $import_status == 'csv_uploaded') {
    $import_status = 'complete';
}
elseif ( $import_status == 'started' ) {
    $import_status = 'pending';
}

// Start the animation only if the button was clicked
if ( $import_status == 'pending' ) {
    // Only report status of the import
    ?>
    <script type="text/javascript">
        livefyre_start_ajax(1000);
    </script>
    <?php
}

$deactivated_time = get_option( 'livefyre_deactivated', ': '.time() );
$deactivated_time = explode(': ', $deactivated_time);
if ( time() - $deactivated_time[1] >= 4838400 ) {
    $import_status = 'uninitialized';
    update_option( 'livefyre_deactivated', '' );
}

$upgrade_status = get_option( 'livefyre_backend_upgrade', false );
?>
<div id="fyresettings">
    <div id="fyreheader" style= <?php echo '"background-image: url(' .plugins_url( '/livefyre-comments/images/header-bg.png', 'livefyre-comments' ). ')"' ?> >
        <img src= <?php echo '"' .plugins_url( '/livefyre-comments/images/logo.png', 'livefyre-comments' ). '"' ?> rel="Livefyre" style="padding: 5px; padding-left: 15px;" />
    </div>
    <div id="fyrebody">
        <div id="fyrebodycontent">
            <?php
            $bad_plugins = Array();
            $all_bad_plugins = Array(
                    'disqus-comment-system/disqus.php' => 'Disqus: Commenting plugin.',
                    'cloudflare/cloudflare.php' => 'Cloudflare: May impact the look of the widget on the page. Be sure to turn off Rocket Loader in
                    your <a href="https://support.cloudflare.com/entries/22088538-How-do-I-access-my-CloudFlare-Performance-Settings-" target="_blank">CloudFlare settings</a>!',
                    'spam-free-wordpress/tl-spam-free-wordpress.php' => 'Spam Free: Disables 3rd party commenting widgets.',
            );
            $need_deactivation = false;
            foreach ( $all_bad_plugins as $key => $value ) {
                if ( is_plugin_active( $key ) ) {
                    array_push($bad_plugins, $value);
                }
            }
            if( isset($_GET['allow_comments_id']) ) {
                $allow_id = $_GET['allow_comments_id'];

                if ( $allow_id == 'all_posts' ) {
                    $livefyre_settings->update_posts( false, 'post' );
                }
                else if ( $allow_id == 'all_pages' ) {
                    $livefyre_settings->update_posts( false, 'page' );
                }
                else {
                    $livefyre_settings->update_posts( $allow_id, false );
                }
            }
            $db_prefix = $wpdb->base_prefix;
            // Get all the posts with comments disabled
            $comments_disabled_posts = $livefyre_settings->select_posts( 'post' );
            // Get all the pages with comments disabled
            $comments_disabled_pages = $livefyre_settings->select_posts( 'page' );
            ?>
            <div id="fyrestatus">
                <?php
                if ( get_option( 'livefyre_site_id', '' ) == '' ) {
                // Don't allow the status sections if there isn't a site
                // The second condition hides the button to start an import, if this was an upgrade from V2
                ?>
                    <h1>Livefyre Status: Needs Configuration</h1>
                    <h2>You must confirm your blog configuration with Livefyre.</h2>
                    <style>
                        <?php echo file_get_contents( dirname( __FILE__ ) . '/settings-template.css' )  ?>
                    </style>
                <?php
                    return;
                }
                // Count of all activated conflicting plugins
                $plugins_count = count($bad_plugins);
                // Count of all posts with comments disabled
                $disabled_posts_count = count($comments_disabled_posts);
                // Count of all pages with comments disabled
                $disabled_pages_count = count($comments_disabled_pages);

                $status = $livefyre_settings->get_fyre_status( $plugins_count, $disabled_posts_count, $disabled_pages_count, $import_status );
                echo '<h1><span class="statuscircle' .$status[1]. '"></span>Livefyre Status: <span>' .$status[0]. '</span></h1>';

                $total_errors = $livefyre_settings->get_total_errors( $plugins_count, $disabled_posts_count, $disabled_pages_count, $import_status );
                if ( $total_errors > 0 ) {
                    echo '<h2>' 
                    .$total_errors
                    .($total_errors == 1 ? ' issue requires' : ' issues require')
                    .' your attention, please see below</h2>';
                }
                ?>
            </div>

            <?php

            if ( $upgrade_status == 'success' ) {
                update_option( 'livefyre_backend_upgrade', 'sent' );
            ?>
                <div id="fyrestatus">
                    <h1>Livefyre Upgrade Status: <span>Sent</span></h1>
                    <p>It looks like your backend is in need of moving to the latest and greatest version of Livefyre! 
                        To implement the Comments 3 plugin, all your data pre July of 2012 will need to be updated. Depending on the number of blog posts and comments you have, 
                        this may take up to several hours and the comments will not show up on those conversations until the data upgrade is complete.
                    </p>
                </div>
            <?php
            }

            if( $import_status != 'complete' ) {
            ?>
                <div id="fyreimportstatus">
                    <?php
                    if ( $import_status == 'error' ) {
                    ?>
                        <h1>Livefyre Import Status: <span>Failed</span></h1>
                        <div id="import_toggle_button" cursor="pointer">
                            <img id="import_toggle" src= <?php echo '"' .plugins_url( '/livefyre-comments/images/more-info.png', 'livefyre-comments' ). '"' ?> rel="Info">
                            <div id="import_toggle_text">Less Info</div>
                        </div>
                        <div id="import_information">
                            <?php echo "<p>Message: " .get_option( 'livefyre_import_message', '' ). "</p>"?>
                            <p>Aw, man. It looks like your comment data gave our importer a hiccup and the import process was derailed. But have no fear, the Livefyre support team is here to help. 
                                If you wouldn’t mind following the instructions below, our support team would be more than happy to work with you to get this problem squared away before you know it!
                                E-mail Livefyre at <a href="mailto:support@livefyre.com">support@livefyre.com</a> with the following:</p>
                            <p>1. In your WP-Admin panel, click “Tools”<br />
                            2. Click “Export”<br />
                            3. Be sure that “All Content” is selected, and then click “Download Export File”<br />
                            4. Attach and e-mail the .XML file that WordPress created to support@livefyre.com along with the URL of your blog.<br /><br />
                            <strong>Note:</strong> If you have multiple sites on your WordPress that you would like to import comments for, please make note of that
                            in the email.</p>
                            <p>Livefyre will still be active and functional on your site, but your imported comments will not be displayed in the comment stream.</p>
                            <input id="fyrehideimport" class="fyrebutton" type="submit" value="Got it, thanks!" />
                        </div>
                    <?php
                    }
                    else if ( $import_status == 'uninitialized' ) {
                        if ( wp_count_comments()->total_comments > 100000 ) {
                        ?>
                            <h1>Livefyre Import Status: <span>Pending</span></h1>
                            <p>Oh snap, it looks like you're pretty popular! You've got a really large amount of comment data that will need some extra attention from our support team to make sure that all of your comments end up properly imported. If you wouldn't mind dropping a quick e-mail to <a href="mailto:support@livefyre.com">support@livefyre.com</a> 
                            with your site's URL, we'll get the ball rolling on completing your import and making sure that you're well taken care of.</p>
                        <?php
                        }
                        else {
                        ?>
                            <h1>Livefyre Import Status: <span>Uninitialized</span></h1>
                            <p>You’ve got some comment data that hasn’t been imported into Livefyre yet, please click the 'Import Comments' button below.
                            As your comments are being imported the status will be displayed here.
                            If Livefyre is unable to import your data, you can still use the plugin, but your existing comments will not be displayed in the Livefyre comment widget. 
                            Please e-mail <a href="mailto:support@livefyre.com">support@livefyre.com</a> with any issues as we’d be more than happy to help you resolve them.</p>
                            <span><a href="?page=livefyre&livefyre_import_begin=1" class="fyrebutton">Import Comments</a></span>
                        <?php
                        }
                    }
                    else {
                    ?>
                        <h1>Livefyre Import Status: <span>Running</span></h1>
                        <p>Depending on the amount of data imported, your comment data may not be immediately displayed after your import completes. If you have any questions,
                            please e-mail <a href="mailto:support@livefyre.com">support@livefyre.com.</a></p>
                        <div id="gears">
                            <img src=<?php echo '"' .plugins_url( '/livefyre-comments/images/gear1.png', 'livefyre-comments' ). '"';?> class="gear1" alt="" />
                            <img src=<?php echo '"' .plugins_url( '/livefyre-comments/images/gear2.png', 'livefyre-comments' ). '"';?> class="gear2" alt="" />
                            <img src=<?php echo '"' .plugins_url( '/livefyre-comments/images/gear3.png', 'livefyre-comments' ). '"';?> class="gear3" alt="" />
                        </div>
                        <p id="livefyre-import-text">Warming up the engine...</p>
                    <?php
                    }
                    ?>
                </div>
            <?php
            }
            ?>

            <div id="fyrecommunitysettings">
                <h1>Livefyre Settings</h1>
                <div id="settings_toggle_button" cursor="pointer">
                    <img id="settings_toggle" src= <?php echo '"' .plugins_url( '/livefyre-comments/images/more-info.png', 'livefyre-comments' ). '"' ?> rel="Info">
                    <div id="settings_toggle_text">More Info</div>
                </div>
                <div id="settings_information" class="hidden">
                    <div id="cache_toggle">
                        <h2>Caching</h2>
                        <p>By defaut, this plugin will automatically store the static HTML of each Livefyre commenting widget in the WordPress database as transient value.
                            If you would like to turn this off, you can do so here. However, without caching there will be a significant performance penalty causing the 
                            commenting widget to load on the page a few seconds slower than if caching was enabled.</p>
                        <?php
                        if( isset( $_GET['lf_caching']) ) {
                            update_option( 'livefyre_caching', $_GET['lf_caching'] );
                        }
                        ?>
                        <form id="fyrecacheform" action="options-general.php?page=livefyre">
                            <input type="hidden" name="page" value="livefyre" />
                            <select name="lf_caching">
                                <option value="on" <?php echo $livefyre_settings->checkSelected('livefyre_caching', 'on'); ?> >On</option>
                                <option value="off" <?php echo $livefyre_settings->checkSelected('livefyre_caching', 'off'); ?> >Off</option>
                            </select><br />
                            <input type="submit" class="fyrebutton" value="Submit" />
                        </form>
                    </div>
                    <div id="cache_delete">
                        <h2>Clear Cache</h2>
                        <p>By clicking the button below, you can delete all the transient values the plugin has stored in your options table.</p>
                        <form id="fyrecacheform" action="options-general.php?page=livefyre">
                            <input type="hidden" name="page" value="livefyre" />
                            <input type="hidden" name="lf_clear_cache" value="1" />
                            <input type="hidden" name="settings_page" value="1" />
                            <input type="submit" class="fyrebutton" value="Clear Cache" />
                        </form>
                    </div>
                </div>
            </div>

            <div id="fyrepotentials" class="clearfix">
                <div id="fyreconflictplugs">
                    <?php echo '<h1>Conflicting Plugins (' .$plugins_count. ')</h1>';
                    if ( $plugins_count ) {
                    ?>
                    <p>We found that the following plugins are active on your site, and unfortunately they will conflict with Livefyre Comments 3 and break our widget’s functionality. 
                        To be sure that Comments 3 is running without a hitch, it will be necessary to deactivate the following plugins:</p>
                    <ul>
                    <?php
                        foreach ( $bad_plugins as $plugin ) {
                            $plugin_data = explode( ':', $plugin, 2 );
                            echo '<li><div class="plugincirclered"></div>' .$plugin_data[0]. ": <span>" .$plugin_data[1];?></span></li><?php
                        }
                    ?>
                    </ul>
                    <?php
                    }
                    else {
                        echo '<p>There are no conflicting plugins</p>';
                    }
                ?>
                </div>

                <div id="fyreallowcomments">
                    <?php echo '<h1>Allow Comments Status (' .($disabled_posts_count + $disabled_pages_count). ')</h1>';
                    if ( $disabled_posts_count || $disabled_pages_count) {
                        ?>
                        <p>We've automagically found that you do not have the "Allow Comments" box in WordPress checked on the posts and pages listed below, which means that the Livefyre widget will not be present on them. 
                            To be sure that the Livefyre Comments 3 widget is visible on these posts or pages, simply click on the “enable” button next to each.</p>
                        <p>If you’d like to simply close commenting on any post or page with the Livefyre widget still present, you can do so from your Livefyre admin panel by clicking the "Livefyre Admin" link to the right, 
                            clicking “Conversations", and then clicking "Stream Settings."</p>
                        <?php
                        if ( $disabled_posts_count ) {
                            $livefyre_settings->display_no_allows( 'post', $comments_disabled_posts);
                        }
                        if ( $disabled_pages_count ) {
                            $livefyre_settings->display_no_allows( 'page', $comments_disabled_pages);
                        }
                    }
                    else {
                        echo '<p>There are no posts with comments not allowed</p>';
                    }
                    ?>
                </div>
            </div>
            <div id="fyresidepanel">
                <div id="fyresidesettings">
                    <h1>Site Settings</h1>
                        <p class="lf_label">Livefyre Site ID: </p>
                        <?php echo '<p class="lf_text">' .get_option('livefyre_site_id'). '</p>'; ?>
                        <br />
                        <p class="lf_label">Livefyre Site Key: </p>
                        <?php echo '<p class="lf_text">' .get_option('livefyre_site_key'). '</p>'; ?>
                    <h1>Links</h1>
                        <a href="http://livefyre.com/admin" target="_blank">Livefyre Admin</a>
                        <br />
                        <a href="http://support.livefyre.com" target="_blank">Livefyre Support</a>
                </div>
                <div id="fyredisplayinfo">
                    <h1>Display Comments</h1>
                    <p class="lf_text">I would like comments displayed on:</p>
                    <?php

                    $excludes = array( '_builtin' => false );
                    $post_types = get_post_types( $args = $excludes );

                    if( isset( $_GET['save_display_settings']) ) {
                        if ( isset( $_GET['display_posts'] ) ) {
                            update_option( 'livefyre_display_posts', $_GET['display_posts'] );
                        }
                        else {
                            update_option( 'livefyre_display_posts', 'false' );
                        }
                        if ( isset( $_GET['display_pages'] ) ) {
                            update_option( 'livefyre_display_pages', $_GET['display_pages'] );
                        }
                        else {
                            update_option( 'livefyre_display_pages', 'false' );
                        }

                        foreach ($post_types as $post_type ) {
                            $post_type_name = 'livefyre_display_' .$post_type;
                            if ( isset( $_GET[$post_type] ) ) {
                                update_option( $post_type_name, $_GET[$post_type] );
                            }
                            else {
                                update_option( $post_type_name, 'false' );
                            }
                        }
                    }

                    $posts_checkbox = "";
                    $pages_checkbox = "";
                    if ( get_option('livefyre_display_posts', 'true') == 'true' ) {
                        $posts_checkbox = 'checked="yes"';
                    }
                    if ( get_option('livefyre_display_pages', 'true') == 'true' ) {
                        $pages_checkbox = 'checked="yes"';
                    }
                    ?>
                    <form id="fyredisplayform" action="options-general.php?page=livefyre">
                        <input type="hidden" name="page" value="livefyre" />
                        <input type="checkbox" class="checkbox" name="display_posts" value="true" <?php echo $posts_checkbox;?> />Posts<br />
                        <input type="checkbox" class="checkbox" name="display_pages" value="true" <?php echo $pages_checkbox;?> />Pages<br />
                        <?php 
                        foreach ($post_types as $post_type ) {
                            $post_type_name = 'livefyre_display_' .$post_type;
                            if ( get_option($post_type_name, 'true') == 'true' ) {
                                $post_type_checkbox = 'checked="yes"';
                            }
                            ?>
                            <input type="checkbox" class="checkbox" name=<?php echo '"' .$post_type. '"';?> value="true" <?php echo $post_type_checkbox;?> /><?php echo $post_type; ?><br />
                            <?php
                        }
                        ?>
                        <input type="submit" class="fyrebutton" name="save_display_settings" value="Submit" />
                    </form>
                </div>
                <div id="fyrelanguages">
                    <?php
                    if( isset( $_GET['lf_language']) ) {
                        update_option( 'livefyre_language', $_GET['lf_language'] );
                    }
                    ?>
                    <h1>Languages</h1>
                    <p class="lf_text">I would like my language to be: </p>
                    <form id="fyrelanguagesform" action="options-general.php?page=livefyre">
                        <input type="hidden" name="page" value="livefyre" />
                        <select name="lf_language">
                            <option value="English" <?php echo $livefyre_settings->checkSelected('livefyre_language', 'English'); ?> >English</option>
                            <option value="Spanish" <?php echo $livefyre_settings->checkSelected('livefyre_language', 'Spanish'); ?> >Spanish</option>
                            <option value="French" <?php echo $livefyre_settings->checkSelected('livefyre_language', 'French'); ?> >French</option>
                            <option value="Portuguese" <?php echo $livefyre_settings->checkSelected('livefyre_language', 'Portuguese'); ?> >Portuguese</option>
                        </select><br />
                        <input type="submit" class="fyrebutton" name="save_languages" value="Submit" />
                    </form>
                </div>
                <?php
                if ( $import_status == 'complete' ) {
                ?>
                    <div id="fyreimportsuccess">
                        <h1>Import Success</h1>
                        <p class="lf_text">We’re all finished, your comment data is now fully imported. You are good to go!</p>
                    </div>
                <?php
                }
                if ( strpos( get_option( 'livefyre_deactivated' ), 'Deactivated' ) !== false ) {
                ?>
                    <div id="fyredeactivation">
                        <h1>Deactivation</h1>
                        <p class="lf_text">Welcome back! It looks like you’ve reactivated the Livefyre plugin recently. If you’ve had comments left on your site since deactivating Livefyre, 
                            please shoot a quick e-mail to <a href="mailto:support@livefyre.com">support@livefyre.com</a> and we’d be happy to help you get all of your comment data properly re-imported.
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    <?php echo file_get_contents( dirname( __FILE__ ) . '/settings-template.css' )  ?>
</style>


<script type="text/javascript">

function toggler(section) {
    var info = document.getElementById(section + 'information');
    var toggle_text = document.getElementById(section + 'toggle_text');
    if(info.className !== 'hidden') {
        info.className = 'hidden';
        toggle_text.innerHTML = 'More Info';
        return;
    }
    info.className = '';
    toggle_text.innerHTML = 'Less Info';
}

document.getElementById('settings_toggle_button').onclick = function() {
    toggler('settings_');
}

var import_button = document.getElementById('import_toggle_button');

if (import_button != null) {
    import_button.onclick = function() {
        toggler('import_');
    }
}

var hide_import_button = document.getElementById('fyrehideimport');

if (hide_import_button != null) {
    hide_import_button.onclick = function() {
        window.location.href = window.location.href + '&hide_import_message=1'
    }
}

</script>
