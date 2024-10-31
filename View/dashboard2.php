<div class="wrap">
    <div class="octify">
        <!--        <h2 class="title is-2">--><?php //_e( "Octify: Image Compressor", octify()->domain ) ?><!--</h2>-->
        <div class="columns">
            <div class="column is-10">
                <div class="box">
                    <h2 class="title has-text-centered"><img
                                src="<?php echo octify()->plugin_url . 'assets/octify_title_welcome.png' ?>"></h2>
					<?php
					$home = "https://octify.io/";
					if ( is_plugin_active( 'ocean-extra/ocean-extra.php' ) ) {
						$home = "https://octify.io/?ref=nicolaslecocq";
					}
					?>
                    <div class="octify-links-widget">
                        <ul>
                            <li>
                                <a href="<?php echo $home ?>"><?php _e( "Octify.io" ) ?></a>
                            </li>
                            <li>
                                <a href="https://wordpress.org/support/plugin/octify"><?php _e( "Support Forum" ) ?></a>
                            </li>
                            <li>
                                <a href="<?php echo $home ?>#section08faq"><?php _e( "Help" ) ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
        <div class="tabs is-centered is-medium">
            <ul>
                <li class="is-active">
                    <a href="#octify-stats">
                        <span class="dashicons dashicons-chart-pie"></span>&nbsp;
						<?php _e( "Stats", octify()->domain ) ?>
                    </a>
                </li>
                <li>
                    <a href="#octify-settings">
                        <span class="dashicons dashicons-admin-generic"></span>&nbsp;
						<?php _e( "Settings", octify()->domain ) ?>
                    </a>
                </li>
                <li>
                    <a href="#octify-subscription">
                        <span class="dashicons dashicons-clipboard"></span>&nbsp;
						<?php _e( "Subscriptions", octify()->domain ) ?>
                    </a>
                </li>
            </ul>
        </div>
		<?php if ( get_option( 'octifyFlash' ) != false ): ?>
            <div class="notification is-primary">
				<?php echo get_option( 'octifyFlash' );
				delete_option( 'octifyFlash' );
				?>
            </div>
		<?php endif; ?>
		<?php if ( get_option( 'octifyErrorFlash' ) != false ): ?>
            <br/>
            <div class="notification is-warning">
				<?php echo get_option( 'octifyErrorFlash' );
				delete_option( 'octifyErrorFlash' );
				?>
            </div>
		<?php endif; ?>
        <div class="octify-content" id="octify-stats">
            <div class="columns">
                <div class="column is-half">
                    <div id="octify-bulk-optimization" class="box">
                        <h1 class="title"><?php _e( "BULK OPTIMIZATION", octify()->domain ) ?></h1>
						<?php
						$pendingImages = count( \Octify\Libs\OctifyApi::findImages( 'pending' ) );
						if ( $pendingImages > 0 ) {
							$process = \Octify\Controller\Compress::calPercent();
							?>
                            <p>
                                We’re currently processing your images, you can
                                close this page and come back later to check on the
                                progress.
                            </p>
                            <div class="has-text-centered">
                                <progress class="progress is-large is-primary octify-progress"
                                          value="<?php echo $process ?>" max="100"><?php echo $process ?>%
                                </progress>
                                <span class="octify-log"></span>
                                <form method="post" id="cancel-frm">
                                    <input type="hidden" name="action" value="cancel_octify_compress"/>
                                    <button type="submit" class="button"><?php _e( "Cancel", octify()->domain ) ?></button>
                                </form>
                            </div>
							<?php
						} else {
							$uncompressed = count( \Octify\Libs\OctifyApi::findImages( 'uncompressed' ) );
							$errors       = count( \Octify\Libs\OctifyApi::findImages( 'error' ) );
							if ( $uncompressed + $errors == 0 ) {
								$total      = count( \Octify\Libs\OctifyApi::findImages( 'all' ) );
								$compressed = count( \Octify\Libs\OctifyApi::findImages( 'compressed' ) );
								if ( $total == $compressed && $compressed > 0 ) {
									?>
                                    <div class="notification is-primary">
										<?php _e( "Congratulations, all of your images have been optimized.", octify()->domain ) ?>
                                    </div>
									<?php
								} else {
									?>
                                    <div class="notification">
										<?php _e( "There are no images within your media library", octify()->domain ) ?>
                                    </div>
									<?php
								}
							} else {
								?>
                                <div>
                                    <div class="notification is-warning">
										<?php printf( _n( "You have <strong>%s image</strong>  waiting for optimize", "You have <strong>%s images</strong>  waiting for optimize", octify()->domain ), $uncompressed + $errors ) ?>
                                    </div>
                                    <div class="notification is-danger is-hidden"></div>
                                    <hr/>
                                    <button class="button bulk-octify"><?php _e( "Optimize All", octify()->domain ) ?></button>
                                </div>
								<?php
							}
						}
						?>
                    </div>
                </div>
                <div class="column is-half">
                    <div class="box">
                        <h1 class="title"><?php _e( "IMAGE STATS", octify()->domain ) ?></h1>
                        <p>
                            <strong><?php _e( "Total Optimizes", octify()->domain ) ?></strong>: <?php echo count( \Octify\Libs\OctifyApi::findImages( 'compressed' ) ) ?>
                        </p>
                        <p><strong><?php _e( "Total Savings", octify()->domain ) ?></strong>: <?php
							$stats = \Octify\Libs\OctifyApi::getStats();
							echo \Octify\Libs\OctifyApi::humanFilesize( $stats['saved'] ) . '/' . round( $stats['saved_p'], 2 ) . '%'
							?>
                        </p>
                        <table class="table">
                            <thead>
                            <tr>
                                <th><?php _e( "File type", octify()->domain ) ?></th>
                                <th><?php _e( "Compressed", octify()->domain ) ?></th>
                                <th><?php _e( "Pending", octify()->domain ) ?></th>
                                <th><?php _e( "Savings", octify()->domain ) ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>JPG</td>
                                <td><?php echo $stats['jpg']['total'] ?></td>
                                <td><?php echo $stats['jpg']['pending'] ?></td>
                                <td><?php echo round( $stats['jpg']['saved_p'], 2 ) ?>% saved</td>
                            </tr>
                            <tr>
                                <td>PNG</td>
                                <td><?php echo $stats['png']['total'] ?></td>
                                <td><?php echo $stats['png']['pending'] ?></td>
                                <td><?php echo round( $stats['png']['saved_p'], 2 ) ?>% saved</td>
                            </tr>
                            <tr>
                                <td>GIF</td>
                                <td><?php echo $stats['gif']['total'] ?></td>
                                <td><?php echo $stats['gif']['pending'] ?></td>
                                <td><?php echo round( $stats['gif']['saved_p'], 2 ) ?>% saved</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="octify-content" id="octify-settings">
            <div class="columns">
                <div class="column is-10">
                    <div class="box">
                        <h1 class="title is-5"><?php _e( "Configuration", octify()->domain ) ?></h1>
                        <br/>
						<?php
						$settings = octify()->getSettings();
						?>
                        <form method="post">
                            <div class="control is-horizontal">
                                <div class="control-label">
                                    <label><?php _e( "Optimization Level", octify()->domain ) ?></label>
                                </div>
                                <div class="control is-grouped">
                       <span class="select">
                            <select name="mode">
                                <option
	                                <?php selected( 'best', $settings['mode'] ) ?>
                                        value="best"><?php _e( "Best Quality", octify()->domain ) ?></option>
                                <option
	                                <?php selected( 'normal', $settings['mode'] ) ?>
                                        value="normal"><?php _e( "Balance", octify()->domain ) ?></option>
                                <option
	                                <?php selected( 'smallest', $settings['mode'] ) ?>
                                        value="smallest"><?php _e( "Smallest Size", octify()->domain ) ?></option>
                            </select>
                        </span>
                                </div>
                            </div>
                            <div class="control is-horizontal">
                                <div class="control-label">
                                    <label><?php _e( "Auto-Optimize images on upload", octify()->domain ) ?></label>
                                    <span class="sub">
                                <?php _e( "Automatically optimize every image you upload to WordPress.", octify()->domain ) ?>
                            </span>
                                </div>
                                <div class="control">
                                    <label for="upload_listener" class="radio">
                                        <input type="hidden" name="upload_listener" value="0">
                                        <input class="tgl tgl-light"
                                               id="upload_listener" <?php checked( 1, $settings['upload_listener'] ) ?>
                                               value="1" name="upload_listener" type="checkbox"/>
                                        <label class="tgl-btn" for="upload_listener"></label>
                                    </label>
                                </div>
                            </div>
                            <div class="control is-horizontal">
                                <div class="control-label">
                                    <label><?php _e( "Backup original images", octify()->domain ) ?></label>
                                    <span class="sub">
                                <?php _e( "Keep your original images in a separate folder before optimization process.", octify()->domain ) ?>
                            </span>
                                </div>
                                <div class="control">
                                    <label for="backup_original" class="radio">
                                        <input type="hidden" name="backup_original" value="0">
                                        <input class="tgl tgl-light"
                                               name="backup_original" <?php checked( 1, $settings['backup_original'] ) ?>
                                               value="1" id="backup_original" type="checkbox"/>
                                        <label class="tgl-btn" for="backup_original"></label>
                                    </label>
                                </div>
                            </div>
                            <div class="control is-horizontal">
                                <div class="control-label">
                                    <label><?php _e( "EXIF Data", octify()->domain ) ?></label>
                                    <span class="sub">
                                <?php _e( "Keep all EXIF data from your images. EXIF data is information stored in your pictures like shutter speed, exposure compensation, ISO, etc...", octify()->domain ) ?>
                            </span>
                                </div>
                                <div class="control">
                                    <label for="keep_exif" class="radio">
                                        <input type="hidden" name="keep_exif" value="0">
                                        <input class="tgl tgl-light"
                                               name="keep_exif" <?php checked( 1, $settings['keep_exif'] ) ?> value="1"
                                               id="keep_exif" type="checkbox"/>
                                        <label class="tgl-btn" for="keep_exif"></label>
                                    </label>
                                </div>
                            </div>
                            <div class="control is-horizontal">
                                <div class="control-label">
                                    <label><?php _e( "Files optimization", octify()->domain ) ?></label>
                                    <span class="sub">
                                <?php _e( "Optimise image sizes created by WordPress", octify()->domain ) ?>
                            </span>
                                </div>
                                <div class="control">
                                    <label for="optimise_different_size " class="radio">
                                        <input type="hidden" name="optimise_different_size" value="0">
                                        <input class="tgl tgl-light"
                                               name="optimise_different_size" <?php checked( 1, $settings['optimise_different_size'] ) ?>
                                               value="1" id="optimise_different_size" type="checkbox"/>
                                        <label class="tgl-btn" for="optimise_different_size"></label>
                                    </label>
                                </div>
                            </div>
<!--                            <div class="control is-horizontal">-->
<!--                                <div class="control-label">-->
<!--                                    <label>--><?php //_e( "Resize", octify()->domain ) ?><!--</label>-->
<!--                                    <span class="sub">-->
<!--                                --><?php //_e( "Optimise image sizes created by WordPress", octify()->domain ) ?>
<!--                            </span>-->
<!--                                </div>-->
<!--                                <div class="control">-->
<!--                                    <label for="resize" class="radio">-->
<!--                                        <input type="hidden" name="resize" value="0">-->
<!--                                        <input class="tgl tgl-light"-->
<!--                                               name="resize" --><?php //checked( 1, $settings['resize'] ) ?>
<!--                                               value="1" id="resize" type="checkbox"/>-->
<!--                                        <label class="tgl-btn" for="resize"></label>-->
<!--                                    </label>-->
<!--                                </div>-->
<!--                            </div>-->
<!--                            <div class="control is-horizontal">-->
<!--                                <div class="control-label">-->
<!--                                    <label>--><?php //_e( "Resize Size", octify()->domain ) ?><!--</label>-->
<!--                                    <span class="sub">-->
<!--                                --><?php //_e( "Optimise image sizes created by WordPress", octify()->domain ) ?>
<!--                            </span>-->
<!--                                </div>-->
<!--                                <div class="control">-->
<!--                                    <input type="text" name="resize_width" placeholder="Width"-->
<!--                                           value="--><?php //echo $settings['resize_width'] ?><!--"/>-->
<!--                                    <input type="text" name="resize_height" placeholder="Height"-->
<!--                                           value="--><?php //echo $settings['resize_height'] ?><!--"/>-->
<!--                                </div>-->
<!--                            </div>-->
							<?php wp_nonce_field( 'octify_settings', '_octifyNonce' ) ?>
                            <br/>
                            <button type="submit" class="button is-pulled-right is-medium is-primary">
								<?php _e( "Save Changes", octify()->domain ) ?></button>
                            <div class="is-clearfix"></div>
                        </form>
                    </div>
                </div>
                <div class="column is-2">
                    <a href="https://oceanwp.org/?ref=69" title="OceanWP - a free Multi-Purpose WordPress theme"><img
                                src="<?php echo octify()->plugin_url . 'assets/ocean.png' ?>"
                                alt="OceanWP - a free Multi-Purpose WordPress theme"/></a>
                </div>
            </div>
        </div>
        <div class="octify-content" id="octify-subscription">
			<?php
			$detail = \Octify\Libs\OctifyApi::getSubDetail();
			?>
            <div class="columns">
                <div class="column is-half">
                    <div class="box">
                        <form method="post">
                            <div class="field">
                                <label class="label">Email</label>
                                <div class="control">
                                    <input class="input" name="email"
                                           value="<?php echo isset( $detail['data']['email'] ) ? $detail['data']['email'] : '' ?>"
                                           type="text"/>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Activate Code</label>
                                <div class="control">
                                    <input class="input" name="code" type="text"/>
                                </div>
                            </div>
                            <br/>
							<?php wp_nonce_field( 'octifyActivator', '_octifyNonce' ) ?>
                            <button class="button is-primary" type="submit">Submit</button>
                        </form>
                    </div>
                </div>
                <div class="column is-half">
                    <div class="box">
                        <h1 class="title is-5"><?php _e( "Subscription Info", octify()->domain ) ?></h1>
                        <p><strong>Name</strong>: <?php echo $detail['data']['plan_name'] ?></p>
						<?php if ( $detail['data']['type'] == 'subscription' ): ?>
                            <p><strong>Next Bill Date</strong>: <?php echo $detail['data']['next_billing_date'] ?></p>
						<?php else: ?>

                            <p>
                                <strong>Quota</strong>: <?php echo $detail['data']['quota'] ?>
                            </p>
                            <p>
                                <strong>Used</strong>: <?php echo \Octify\Libs\OctifyApi::humanFilesize( $detail['data']['used'] ) ?>
                            </p>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>