<?php
/*
Author: Livefyre, Inc.
Version: 4.1.0
Author URI: http://livefyre.com/
*/

?>

<div id="fyresettings">
    <div id="fyreheader" style= <?php echo '"background-image: url(' .plugins_url( '/livefyre-comments/images/header-bg.png', 'livefyre-comments' ). ')"' ?> >
        <img src= <?php echo '"' .plugins_url( '/livefyre-comments/images/logo.png', 'livefyre-comments' ). '"' ?> rel="Livefyre" style="padding: 5px; padding-left: 15px;" />
    </div>
    <div id="fyrebody">
        <div id="fyrebodycontent">
            <div id="fyrestatus">
                <?php
                $status = Array('All systems go!', 'green');
                echo '<h1><span class="statuscircle' .$status[1]. '"></span>Livefyre Status: <span>' .$status[0]. '</span></h1>';
                echo '<p>Everything should be set at your local site level.</p>';
                ?>
            </div>

            <div id="fyresidepanel">
                <div id="fyresidesettings">
                    <h1>Network Settings</h1>
                        <p class="lf_label">Livefyre Network: </p>
                        <?php echo '<p class="lf_text">livefyre.com</p>'; ?>
                    <h1>Site Settings</h1>
                        <?php echo '<p class="lf_text">Specific to each site</p>'; ?>
                    <h1>Links</h1>
                        <a href="http://livefyre.com/admin" target="_blank">Livefyre Admin</a>
                        <br />
                        <a href="http://support.livefyre.com" target="_blank">Livefyre Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    <?php echo file_get_contents( dirname( __FILE__ ) . '/settings-template.css' )  ?>
</style>
