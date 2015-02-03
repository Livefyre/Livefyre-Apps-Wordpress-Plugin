<?php
if($data['display_template']) {
    echo '<div id="'. esc_attr($data['livefyre_element']).'"></div>';
}
?>
<script type="text/javascript">
    var networkConfig = {
        network: "<?php echo esc_js($data['network']->getName()); ?>"
    };
    var convConfigChat<?php echo esc_js($data['articleId']); ?> = {
        siteId: "<?php echo esc_js($data['siteId']); ?>",
        articleId: "<?php echo esc_js($data['articleId']); ?>",
        el: "<?php echo esc_js($data['livefyre_element']); ?>",
        collectionMeta: "<?php echo esc_js($data['collectionMetaToken']); ?>",
        checksum: "<?php echo esc_js($data['checksum']); ?>"
    };
    
    if(typeof(liveChatConfig) !== 'undefined') {
        convConfigChat<?php echo esc_js($data['articleId']); ?> = lf_extend(liveChatConfig, convConfigChat<?php echo esc_js($data['articleId']); ?>);
    }

    Livefyre.require(['<?php echo Livefyre_Apps::get_package_reference('fyre.conv'); ?>'], function(ConvChat) {
        load_livefyre_auth();
        new ConvChat(networkConfig, [convConfigChat<?php echo esc_js($data['articleId']); ?>], function(chatWidget) {
        }());
    });
</script>