<div class="wrap">
    <div class="octify">
        <div class="box">
            <h2 class="title has-text-centered"><img
                        src="<?php echo octify()->plugin_url . 'assets/octify_title_welcome.png' ?>"></h2>
            <div class="octify-links-widget">
                <ul>
                    <li>
                        <a href="https://octify.io"><?php _e( "Octify.com" ) ?></a>
                    </li>
                    <li>
                        <a href="https://wordpress.org/plugins/octify/support"><?php _e( "Support Forum" ) ?></a>
                    </li>
                    <li>
                        <a href="https://octify.io/contact"><?php _e( "Help" ) ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="columns">
            <div class="column is-8">
                <div class="box has-text-centered">
                    <h1 class="title"><?php _e( "Activation", octify()->domain ) ?></h1>
                    <form method="post" id="activator-frm">
	                    <?php
	                    $current_user = wp_get_current_user();
	                    ?>
                        <input placeholder="<?php _e( "Email", octify()->domain ) ?>" type="text"
                               class="is-large" name="email" value="<?php echo $current_user->user_email ?>">
						<?php wp_nonce_field( 'octify_getFreeKey', '_octifyNonce' ) ?>
                        <br/>
                        <button type="submit" class="button is-medium is-primary">
							<?php _e( "Get My 50MB Free", octify()->domain ) ?></button>
                        <div class="is-clearfix"></div>
                    </form>
                </div>
            </div>
            <div class="column is-4">

            </div>
        </div>
    </div>
</div>