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

                $bad_status = $this->ext->get_network_option( 'livefyre_domain_name', '' ) == ''
                    || $this->ext->get_network_option( 'livefyre_domain_key', '' ) == '';
                $status = Array('All systems go!', 'green');
                if( $bad_status ) {
                    $status = Array('Settings blank', 'red');
                }
                echo '<h1><span class="statuscircle' .$status[1]. '"></span>Livefyre Status: <span>' .$status[0]. '</span></h1>';

                $total_errors = 1;
                if ( $bad_status ) {
                    echo '<h2> You must set your network name and key.</h2>';
                }
                ?>
            </div>
            <div id="fyrenetworksettingsmulti">
                <h1>Livefyre Network Settings</h1>
                <div id="settings_toggle_button" onclick="settings_toggle_less()" cursor="pointer">
                    <img id="settings_toggle" src= <?php echo '"' .plugins_url( '/livefyre-comments/images/more-info.png', 'livefyre-comments' ). '"' ?> rel="Info">
                    <div id='toggle_text'>Less Info</div>
                </div>
                <form method="post" action="edit.php?action=save_network_options">
                    <?php
                        settings_fields( 'livefyre_domain_options' );
                        do_settings_sections( 'livefyre_network' );
                    ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
                    </p>
                </form>
            </div>
            <div id="fyresidepanel">
                <div id="fyresidesettings">
                    <h1>Network Settings</h1>
                        <p class="lf_label">Network: </p>
                        <?php echo '<p class="lf_text">' .$this->ext->get_network_option( 'livefyre_domain_name', '' ). '</p>'; ?>
                        <br />
                        <p class="lf_label">Network Key: </p>
                        <?php echo '<p class="lf_text">' .$this->ext->get_network_option( 'livefyre_domain_key', '' ). '</p>'; ?>
                        <br />
                        <p class="lf_label">Auth Delegate: </p>
                        <?php echo '<p class="lf_text">' .$this->ext->get_network_option( 'livefyre_auth_delegate_name', '' ). '</p>'; ?>
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
