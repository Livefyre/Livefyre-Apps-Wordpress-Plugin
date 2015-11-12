<div id="lfapps-general-metabox-holder" class="metabox-holder clearfix">
    <?php
    wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
    wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
    ?>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
            if (typeof postboxes !== 'undefined')
                postboxes.add_postbox_toggles('plugins_page_livefyre_comments');
        });
    </script>

    <div class="postbox-container postbox-large">
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="referrers" class="postbox ">
                <div class="handlediv" title="Click to toggle"><br></div>
                <h3 class="hndle"><span><?php esc_html_e('Notifications Shortcode', 'lfapps-notifications'); ?></span></h3>
                <div class='inside'>
                    <p>To activate Notifications, you must add a shortcode to your content.</p>
                    <p>The shortcode usage is pretty simple. Let's say we wish to generate a Notifications inside post content. We could enter something like this
                        inside the content editor:</p>
                    <p class='code'>[livefyre_notifications]</p>
                    <p>This code can be entered in your theme as well. We recommend implementing the stream in a shadow box or other visual space.</p>
                </div> 
            </div>
        </div>
    </div>
</div>