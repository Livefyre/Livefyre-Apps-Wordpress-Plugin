<div id="<?php echo esc_attr($data['livefyre_element']); ?>"></div>
<script type="text/javascript">
    var networkConfig = {
        network: "<?php echo esc_js($data['network']->getName()); ?>"<?php echo $data['strings'] !== null ? ', strings: ' . esc_js($data['strings']) : ''; ?>
    };
    var convConfigBlog<?php echo esc_js($articleId); ?> = {
        siteId: "<?php echo esc_js($data['siteId']); ?>",
        articleId: "<?php echo esc_js($data['articleId']); ?>",
        el: "<?php echo esc_js($data['livefyre_element']); ?>",
        collectionMeta: "<?php echo esc_js($data['collectionMetaToken']); ?>",
        checksum: "<?php echo esc_js($data['checksum']); ?>"
    };
    
    if(typeof(liveBlogConfig) !== 'undefined') {
        convConfigBlog<?php echo esc_js($data['articleId']); ?> = lf_extend(liveBlogConfig, convConfigBlog<?php echo esc_js($data['articleId']); ?>);
    }

    Livefyre.require(['<?php echo Livefyre_Apps::get_package_reference('fyre.conv'); ?>'], function(ConvBlog) {
        load_livefyre_auth();
        new ConvBlog(networkConfig, [convConfigBlog<?php echo esc_js($data['articleId']); ?>], function(blogWidget) {            
        }());
    });
</script>