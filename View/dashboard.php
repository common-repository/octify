<div class="wrap">
    <div class="octify">
        <!--        <h2 class="title is-2">--><?php //_e( "Octify: Image Compressor", octify()->domain ) ?><!--</h2>-->
        <h2 class="title"><img src="<?php echo octify()->plugin_url . 'assets/octify_title_welcome.png' ?>"></h2>
		<?php if ( get_option( 'octifyFlash' ) != false ): ?>
            <div class="notification is-primary">
				<?php echo get_option( 'octifyFlash' );
				delete_option( 'octifyFlash' );
				?>
            </div>
		<?php endif; ?>
		<?php if ( get_option( 'octifyErrorFlash' ) != false ): ?>
            <div class="notification is-warning">
				<?php echo get_option( 'octifyErrorFlash' );
				delete_option( 'octifyErrorFlash' );
				?>
            </div>
		<?php endif; ?>
        <div class="columns is-multiline">
            <div class="column is-half">
				<?php
				if ( \Octify\Libs\OctifyApi::isLicenseExpired() ) {
					//send a alert here
					?>
                    <div class="notification is-info">
                        <p><?php _e( "Your license is expired" ) ?></p>
                        <form method="post">
                            <div class="field">
                                <label class="label">Activate Code</label>
                                <div class="control">
                                    <input class="input" name="code" type="text"/>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">Email</label>
                                <div class="control">
                                    <input class="input" name="email" type="text"/>
                                </div>
                            </div>
                            <br/>
							<?php wp_nonce_field( 'extendLicense', '_octifyNonce' ) ?>
                            <button class="button is-primary" type="submit">Submit</button>
                        </form>
                    </div>
					<?php
				} else {
					?>
                    <div id="octify-bulk-optimization" class="box">
                        <h1 class="title"><?php _e( "BULK OPTIMIZATION", octify()->domain ) ?></h1>
						<?php
						$pendingImages = count( \Octify\Libs\OctifyApi::findImages( 'pending' ) );
						if ( $pendingImages > 0 ) {
							$process = \Octify\Libs\OctifyApi::calculateProgress();
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
					<?php
				}
				?>
            </div>
            <div class="column is-3">
                <div id="octify-statistics" class="box">
                    <h1 class="title is-5"><?php _e( "Statistics", octify()->domain ) ?></h1>
                    <p><strong>Total
                            Optimizes</strong>: <?php echo count( \Octify\Libs\OctifyApi::findImages( 'compressed' ) ) ?>
                    </p>
                    <p><strong>Total Savings</strong>: <?php
						$stats = \Octify\Libs\OctifyApi::getStats();
						echo \Octify\Libs\OctifyApi::humanFilesize( $stats['saved'] ) . '/' . round( $stats['saved_p'], 2 ) . '%'
						?></p>
					<?php
					$detail = \Octify\Libs\OctifyApi::getSubDetail();
					?>
                </div>
            </div>
            <div class="column is-3">
                <div class="box">
                    <h1 class="title is-5"><?php _e( "Subscription Info", octify()->domain ) ?></h1>
                    <p><strong>Name</strong>: <?php echo $detail['data']['plan_name'] ?></p>
					<?php if ( $detail['data']['type'] == 'subscription' ): ?>
                        <p><strong>Next Bill Date</strong>: <?php echo $detail['data']['next_billing_date'] ?></p>
					<?php else: ?>

                        <p>
                            <strong>Quota</strong>: <?php echo \Octify\Libs\OctifyApi::humanFilesize( $detail['data']['quota'] ) ?>
                        </p>
                        <p>
                            <strong>Used</strong>: <?php echo \Octify\Libs\OctifyApi::humanFilesize( $detail['data']['used'] ) ?>
                        </p>
					<?php endif; ?>
                </div>
            </div>
        </div>
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
                    </div>
                    <div class="control">
                        <label for="upload_listener" class="radio">
                            <input type="hidden" name="upload_listener" value="0">
                            <input id="upload_listener" type="checkbox" value="1"
								<?php checked( 1, $settings['upload_listener'] ) ?>
                                   name="upload_listener">
							<?php _e( "Automatically optimize every image you upload to WordPress.", octify()->domain ) ?>
                        </label>
                    </div>
                </div>
                <div class="control is-horizontal">
                    <div class="control-label">
                        <label><?php _e( "Backup original images", octify()->domain ) ?></label>
                    </div>
                    <div class="control">
                        <label for="backup_original" class="radio">
                            <input type="hidden" name="backup_original" value="0">
                            <input id="backup_original" type="checkbox" value="1"
								<?php checked( 1, $settings['backup_original'] ) ?>
                                   name="backup_original">
							<?php _e( "Keep your original images in a separate folder before optimization process.", octify()->domain ) ?>
                        </label>
                    </div>
                </div>
                <div class="control is-horizontal">
                    <div class="control-label">
                        <label><?php _e( "EXIF Data", octify()->domain ) ?></label>
                    </div>
                    <div class="control">
                        <label for="keep_exif" class="radio">
                            <input type="hidden" name="keep_exif" value="0">
                            <input id="keep_exif" type="checkbox" value="1"
								<?php checked( 1, $settings['keep_exif'] ) ?>
                                   name="keep_exif">
							<?php _e( "Keep all EXIF data from your images. EXIF data is information stored in your pictures like shutter speed, exposure compensation, ISO, etc...", octify()->domain ) ?>
                        </label>
                    </div>
                </div>
                <div class="control is-horizontal">
                    <div class="control-label">
                        <label><?php _e( "Files optimization", octify()->domain ) ?></label>
                    </div>
                    <div class="control">
                        <label for="optimise_different_size " class="radio">
                            <input type="hidden" name="optimise_different_size" value="0">
                            <input id="optimise_different_size " type="checkbox" value="1"
								<?php checked( 1, $settings['optimise_different_size'] ) ?>
                                   name="optimise_different_size">
							<?php _e( "Optimise image sizes created by WordPress", octify()->domain ) ?>
                        </label>
                    </div>
                </div>
                <div class="control is-horizontal">
                    <div class="control-label">
                        <label><?php _e( "Resize", octify()->domain ) ?></label>
                    </div>
                    <div class="control">
                        <label for="resize " class="radio">
                            <input type="hidden" name="resize" value="0">
                            <input id="resize " type="checkbox" value="1"
					            <?php checked( 1, $settings['resize'] ) ?>
                                   name="resize">
				            <?php _e( "Resize", octify()->domain ) ?>
                        </label>
                    </div>
                </div>
				<?php wp_nonce_field( 'octify_settings', '_octifyNonce' ) ?>
                <br/>

                <button type="submit" class="button is-pulled-right is-medium is-primary">
					<?php _e( "Save Changes", octify()->domain ) ?></button>
                <div class="is-clearfix"></div>
            </form>
        </div>
    </div>
</div>