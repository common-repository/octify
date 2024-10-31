<div class="wrap">
    <div class="octify">
        <div class="box">
            <h2 class="title has-text-centered"><img
                        src="<?php echo octify()->plugin_url . 'assets/octify_title_welcome.png' ?>"></h2>
            <div class="octify-links-widget">
                <ul>
                    <li>
                        <a href=""><?php _e( "Octify.com" ) ?></a>
                    </li>
                    <li>
                        <a href=""><?php _e( "Support Forum" ) ?></a>
                    </li>
                    <li>
                        <a href=""><?php _e( "Help" ) ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="has-text-centered">
            <h3><?php _e( "Looks like this is your first time using Octify... Click below to get setup", octify()->domain ) ?></h3>
            <a href="<?php echo admin_url( 'upload.php?page=octify&step=2' ) ?>"
               class="button is-medium is-success"><?php _e( "Ok Let's go", octify()->domain ) ?></a>
        </div>
    </div>
</div>