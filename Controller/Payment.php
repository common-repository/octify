<?php

namespace Octify\Controller;

class Payment {
	public static function activate() {
		$wpNonce = isset( $_POST['_octifyNonce'] ) ? $_POST['_octifyNonce'] : null;
		if ( is_null( $wpNonce ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $wpNonce, 'octifyActivator' ) ) {
			return;
		}

		$email = sanitize_email( $_POST['email'] );
		$code  = sanitize_key( $_POST['code'] );

		$res = \Octify\Libs\OctifyApi::activateCheck( $email, $code );
		if ( is_wp_error( $res ) ) {
			update_option( 'octifyErrorFlash', $res->get_error_message() );
		} else {
			update_option( 'octifyFlash', __( "WooHoo! You've just made an awesome decision
thanks for choosing Octify!", octify()->domain ) );
		}

		wp_redirect( admin_url( 'upload.php?page=octify' ) );
		exit;
	}

	public static function getFreeKey() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$nonce = isset( $_POST['_octifyNonce'] ) ? $_POST['_octifyNonce'] : null;
		if ( ! wp_verify_nonce( $nonce, 'octify_getFreeKey' ) ) {
			return;
		}

		$email = isset( $_POST['email'] ) ? $_POST['email'] : null;
		if ( ! $email ) {
			//show error
			update_option( 'octifyErrorFlash', __( "Email can't be blank", octify()->domain ) );
		}

		$code = \Octify\Libs\OctifyApi::getAPIKey( $email );
		$res  = \Octify\Libs\OctifyApi::activateCheck( $email, $code['code'] );
		if ( is_wp_error( $res ) ) {
			update_option( 'octifyErrorFlash', $res->get_error_message() );
		} else {
			update_option( 'octifyFlash', __( "Welcome to Octify!", octify()->domain ) );
		}

		wp_redirect( admin_url( 'upload.php?page=octify#' ) );
	}
}