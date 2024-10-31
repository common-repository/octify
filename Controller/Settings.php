<?php

namespace Octify\Controller;
class Settings {
	public static function saveSettings() {
		$wpNonce = isset( $_POST['_octifyNonce'] ) ? $_POST['_octifyNonce'] : null;
		if ( is_null( $wpNonce ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $wpNonce, 'octify_settings' ) ) {
			return;
		}

		$settings = octify()->getSettings();
		foreach ( $settings as $key => $val ) {
			if ( isset( $_POST[ $key ] ) ) {
				$settings[ $key ] = $_POST[ $key ];
			}
		}
		update_option( 'octify', $settings );
		update_option( 'octifyFlash', __( "Settings saved successfully", octify()->domain ) );
		wp_redirect( admin_url( 'upload.php?page=octify' ) );
		exit;
	}
}