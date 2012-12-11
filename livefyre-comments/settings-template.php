<?php
if (function_exists( 'home_url' )) {
    $home_url=home_url();
} else {
    $home_url=get_option('home');
}

$site_id=get_option('livefyre_site_id',''); 

if (isset($_GET['status'])) {
    update_option('livefyre_import_status', $_GET['status']);
    if (isset($_GET['message'])) {
        update_option('livefyre_import_message', urldecode($_GET['message']));
    }
} elseif (isset($_GET['livefyre_reset_v3_notes'])) {
    delete_option('livefyre_v3_notify_installed');
    delete_option('livefyre_v3_notify_upgraded');
}
?>

<script type="text/javascript">
//Lightweight JSONP fetcher - www.nonobtrusive.com
var JSONP=(function(){var a=0,c,f,b,d=this;function e(j){var i=document.createElement("script"),h=false;i.src=j;i.async=true;i.onload=i.onreadystatechange=function(){if(!h&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){h=true;i.onload=i.onreadystatechange=null;if(i&&i.parentNode){i.parentNode.removeChild(i)}}};if(!c){c=document.getElementsByTagName("head")[0]}c.appendChild(i)}function g(h,j,k){f="?";j=j||{};for(b in j){if(j.hasOwnProperty(b)){f+=b+"="+j[b]+"&"}}var i="json"+(++a);d[i]=function(l){k(l);d[i]=null;try{delete d[i]}catch(m){}};e(h+f+"callback="+i);return i}return{get:g}}());
var livefyre_wp_plugin_polled = false;

function updateProgressBarLF(conv_count, convs_processed) {
    var percentage_complete = 0;
    if (typeof(conv_count) !== 'number') {
        conv_count = 0;
    }
    if (typeof(convs_processed) !== 'number') {
        convs_processed = 0;
    }
    document.getElementById('fyre-progress-title').innerHTML = convs_processed + '/' + conv_count + ' Conversations';
    percentage_complete = (100 * convs_processed / conv_count);
    if (!isNaN(percentage_complete)) {
        var prog = document.getElementById('fyre-progress-bar');
        prog.style.width = percentage_complete + '%';
        prog.style.border = '1px solid #58891c';
    }
}

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
                updateProgressBarLF(data['conversation_count'], data['conversations_processed_count']);
        }
        if (status === 'complete') {
            // 'complete' in this response is equivalent to the 'csv_uploaded' status internally
            // we'll wait a moment to let the progress bar look pretty
            setTimeout(function(){
                // reload the page, showing completed status.
                window.location.href = window.location.href.split('?')[0] + '?page=livefyre';
            }, 2500);
        }
        livefyre_wp_plugin_polled=true;
    });
}

function livefyre_start_ajax() {
    window.checkStatusInterval=setInterval(
        checkStatusLF, 
        5000
    );
    checkStatusLF();
}
</script>


<?php
$status = get_option('livefyre_import_status','');
if (!in_array($status, array('', 'error', 'csv_uploaded'))) {
    //only report status of the import
    ?>
    <script type="text/javascript">
        livefyre_start_ajax();
    </script>
    <?php
}
?>


<div class="fyre-settings">
    <div class="fyre-container-base">
        <div class="fyre-container">
            <ul class="fyre-list">
                <li class="fyre-list">Livefyre Site ID: <strong><?php echo get_option('livefyre_site_id') ?></strong></li>
                <li class="fyre-list">Livefyre Site Key: <strong><?php echo get_option('livefyre_site_key') ?></strong></li>
                <li class="fyre-list">Livefyre Admin URL: <a href="http://livefyre.com/admin">Admin Panel</a></li>
            </ul>
        </div>
    </div>

    <?php
    $import_status = get_option('livefyre_import_status','');
    if (get_option('livefyre_site_id','') == '' || get_option( 'livefyre_v3_installed', null) != 0) {
        // Don't allow the status sections if there isn't a site
        // The second condition hides the button to start an import, if this was an upgrade from V2
    } else if ($import_status == 'error') {
        ?>
        <div class="fyre-container-base" id="fyre-failure">
            <div class="fyre-container">
                <div class="fyre-header">
                    <div class="fyre-status red"></div>
                    <span class="fyre-title">Initialization Error</span>
                    <span class="fyre-subtext">
                        <?php echo "Message: " . get_option('livefyre_import_message','') ?>
                        <p>Please reattempt the import process by hitting the green button below. If that proceeds to fail, please 
                            e-mail Livefyre at <a href="support@livefyre.com">support@livefyre.com</a> with the following:</p>
                        <ul>
                            <li>An .XML file of your Wordpress Blog:</li>
                                <ol>
                                    <li>Open your WP-Admin panel</li>
                                    <li>Click "Tools" on the left-hand side</li>
                                    <li>Click "Export"</li>
                                    <li>Choose "All Content"</li>
                                    <li>Click "Download Export file"</li>
                                </ol>
                            <li>The base site url you are trying to import for (yourblog.com).
                                <strong>Note:</strong> If you have multiple sites on your Wordpress that you would like to import comments for, please make note of that
                                in the email
                                <br />
                            </li>
                        </ul>
                    </span>
                    <a href="?page=livefyre&livefyre_import_begin=1" class="green fyre-button">Re-attempt comment import</a>
                </div>
            </div>
        </div>
        <?php
    } else if ($import_status == 'csv_uploaded') {
        ?>
        <div class="fyre-container-base" id='fyre-success'>
            <div class="fyre-container">
                <div class="fyre-header">
                    <div class="fyre-status green"></div>
                    <span class="fyre-title">Successfully Imported</span>
                    <span class="fyre-subtext">
                        <?php echo get_option('livefyre_import_message','') ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    } else if ($import_status == '') {
        ?>
        <div class="fyre-container-base" id="fyre-start">
            <div class="fyre-container">
                <div class="fyre-header">
                    <div class="fyre-status slate"></div>
                    <span id="fyre-progress-title" class="fyre-title"></span>

                    <span class="fyre-subtext">
                        Import your existing WordPress comments so that they show up in Livefyre Comments and in the Livefyre Admin.  As your comments are being imported the status will be displayed bere.   If Livefyre is unable to import data, you can still use the plugin, but your existing comments will not be displayed by Livefyre.
                    </span>
                    <a href="?page=livefyre&livefyre_import_begin=1" class="green fyre-button">Import comments</a>
                </div>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="fyre-container-base" id="fyre-start">
            <div class="fyre-container">
                <div class="fyre-header">
                    <div class="fyre-status"></div>
                    <span id="fyre-progress-title" class="fyre-title"></span>

                    <span class="fyre-subtext">
                        Import your existing WordPress comments so that they show up in Livefyre Comments and in the Livefyre Admin.  As your comments are being imported the status will be displayed bere.   If Livefyre is unable to import data, you can still use the plugin, but your existing comments will not be displayed by Livefyre.
                    </span>
                    <div class="fyre-progress-bar-container">
                        <div id="fyre-progress-bar" class="fyre-progress-bar"></div>
                    </div>
                </div>
            </div>
        </div><?php
         
    } 
    ?>
</div>
<style>
    <?php echo file_get_contents( dirname( __FILE__ ) . '/settings-template.css' )  ?>
</style>
