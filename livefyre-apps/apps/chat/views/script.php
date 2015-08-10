<?php
if($display_template) {
    echo '<div id="'. esc_attr($livefyre_element).'"></div>';
}
?>
<script type="text/javascript">
    var networkConfigChat = {
        <?php echo isset( $strings ) ? 'strings: ' . json_encode($strings) . ',' : ''; ?>
        network: "<?php echo esc_js($network->getName()); ?>"
    };
    var convConfigChat<?php echo esc_js($articleId); ?> = {
        siteId: "<?php echo esc_js($siteId); ?>",
        articleId: "<?php echo esc_js($articleId); ?>",
        el: "<?php echo esc_js($livefyre_element); ?>",
        collectionMeta: "<?php echo esc_js($collectionMetaToken); ?>",
        checksum: "<?php echo esc_js($checksum); ?>"
    };
    
    if(typeof(liveChatConfig) !== 'undefined') {
        convConfigChat<?php echo esc_js($articleId); ?> = lf_extend(liveChatConfig, convConfigChat<?php echo esc_js($articleId); ?>);
    }

    Livefyre.require(['<?php echo LFAPPS_Chat::get_package_reference(); ?>'], function(ConvChat) {
        load_livefyre_auth();
        new ConvChat(networkConfigChat, [convConfigChat<?php echo esc_js($articleId); ?>], function(chatWidget) {
            if(typeof chatWidget !== "undefined") {
                var livechat_listeners = LFAPPS.get_app_jsevent_listeners('livechat');
                if(livechat_listeners.length > 0) {
                    for(var i in livechat_listeners) {
                        var livechat_listener = livechat_listeners[i];

                        chatWidget.on(livechat_listener.event_name, livechat_listener.callback);
                    }
                }
            }
        });
    });
</script>