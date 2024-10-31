<?php

namespace Octify\Controller;

use Octify\Libs\OctifyApi;

class Compress {
	const IN_COMPRESS = 'octify_in_compress', TOTAL = 'octify_total_files',
		SENT_LIST = 'octify_sent_list', DONE_LIST = 'octify_done_list', LOCK = 'octify_lock';

	public static function bulkOctify() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		self::clear();
		//update status
		update_option( self::IN_COMPRESS, 1 );
		$uncompressed = \Octify\Libs\OctifyApi::findImages( 'uncompressed' );
		$error        = \Octify\Libs\OctifyApi::findImages( 'error' );
		$uncompressed = array_merge( $uncompressed, $error );
		//prepare the data
		foreach ( $uncompressed as $image ) {
			//update status to pending
			update_post_meta( $image->ID, OctifyApi::META_STATUS, 'pending' );
		}
		update_option( self::TOTAL, count( $uncompressed ) );
		wp_send_json_success();
	}

	public static function getOctifyStatus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

//		if ( get_option( self::IN_COMPRESS ) == false ) {
//			return;
//		}

		if ( self::isLocked() ) {
			return;
		}

		self::createLock();

		$sentList = get_option( self::SENT_LIST, array() );
		$doneList = get_option( self::DONE_LIST, array() );
		$pending  = OctifyApi::findImages( 'pending' );
		if ( empty( $pending ) ) {
			//process done
			self::clear();
			self::releaseLock();
		}

		if ( empty( $sentList ) ) {
			//send it
			$image = array_shift( $pending );
			//send envelope
			$res        = OctifyApi::sendEnvelope( array( $image->ID ) );
			$sentList[] = $image->ID;
			update_option( self::SENT_LIST, $sentList );
			self::processResponse( $res );
		} else {
			//verify sent list before process new image
			$id     = array_shift( $sentList );
			$log    = sprintf( __( "Compressing file <strong>%s</strong>", octify()->domain ), wp_get_attachment_image_url( $id ) );
			$status = get_post_meta( $id, OctifyApi::META_STATUS, true );
			if ( $status != 'pending' ) {
				//processed
				$doneList[] = $id;
				update_option( self::DONE_LIST, $doneList );
				//remove from sent list
				update_option( self::SENT_LIST, array() );
			} else {
				//get the time
				$meta = get_post_meta( $id, OctifyApi::META_NAME, true );
				$time = $meta['started'];
				if ( $meta['started'] == false ) {
					$meta = array(
						'started' => time(),
						'retry'   => 1
					);
					update_post_meta( $id, OctifyApi::META_NAME, $meta );
				}
				if ( strtotime( '+60 seconds', $time ) < time() ) {
					//we should retry it
					$retry = isset( $meta['retry'] ) ? $meta['retry'] + 1 : 1;
					if ( $retry > 3 ) {
						//error
						update_post_meta( $id, OctifyApi::META_STATUS, 'error' );
						update_post_meta( $id, OctifyApi::META_NAME, array(
							'error' => __( "Oops! Your image timed out, try again or contact your host to raise your timeout limit", octify()->domain )
						) );
					} else {
						$res = OctifyApi::sendEnvelope( array( $id ) );
						update_post_meta( $id, OctifyApi::META_NAME, array(
							'started' => time(),
							'retry'   => $retry
						) );
						self::processResponse( $res, $retry );
					}
				}
			}
			self::releaseLock();
			wp_send_json_success( array(
				'percent' => self::calPercent(),
				'log'     => $log,
				'waiting' => 1
			) );
		}
		self::releaseLock();
	}

	private static function processResponse( $result, $isRetry = 0 ) {
		$pending = OctifyApi::findImages( 'pending' );
		if ( is_wp_error( $result ) ) {
			//revert all the images
			foreach ( $pending as $image ) {
				delete_post_meta( $image->ID, OctifyApi::META_STATUS );
				delete_post_meta( $image->ID, OctifyApi::META_STATUS );
			}
			update_option( 'octifyErrorFlash', $result->get_error_message() );
			self::releaseLock();
			self::clear();
			wp_send_json_error( array(
				'error' => $result->get_error_message()
			) );
		} elseif ( count( $result['fail'] ) ) {
			//file sent fine but there is error
			$id = array_shift( $result['fail'] );
			if ( $id['code'] == 'expired' ) {
				$errorMessages = sprintf( __( "Whoops! Sorry you've used up your compression Quota. Want more? <a href='%s' target='_blank'>Buy Now</a>", octify()->domain ), 'https://octify.io/' );
			} else {
				$errorMessages = sprintf( __( "Image with ID %s - %s", octify()->domain ), $id['id'], $id['error'] );
			}
			$pending = OctifyApi::findImages( 'pending' );
			//revert all images
			foreach ( $pending as $image ) {
				delete_post_meta( $image->ID, OctifyApi::META_STATUS );
				delete_post_meta( $image->ID, OctifyApi::META_NAME );
			}
			update_option( 'octifyErrorFlash', $errorMessages );
			self::releaseLock();
			self::clear();
			wp_send_json_error();
		} elseif ( count( $result['success'] ) ) {
			//file sent ok
			$id = array_shift( $result['success'] );
			//update the time for retry
			update_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME, array(
				'started' => time()
			) );
			//send out progress
			$log = sprintf( __( "Submitted file <strong>%s</strong>", octify()->domain ), wp_get_attachment_image_url( $id ) );
			if ( $isRetry ) {
				$log = sprintf( __( "Submitted file <strong>%s</strong> - <strong>Retry %d</strong>", octify()->domain ), wp_get_attachment_image_url( $id ), $isRetry );
			}
			self::releaseLock();
			wp_send_json_success( array(
				'percent' => self::calPercent(),
				'log'     => $log
			) );
		}
	}

	private static function clear() {
		delete_option( self::SENT_LIST );
		delete_option( self::DONE_LIST );
		delete_option( self::IN_COMPRESS );
	}

	public static function calPercent() {
		$total    = get_option( self::TOTAL );
		$doneList = get_option( self::DONE_LIST, array() );
		if ( $total == 0 ) {
			return 0;
		}

		$percent = ( count( $doneList ) / $total ) * 100;

		return round( $percent, 2 );
	}

	private static function isLocked() {
		$lock = get_option( self::LOCK );
		if ( $lock == false ) {
			return false;
		}

		if ( strtotime( '+90 seconds', $lock ) < time() ) {
			//locked too long
			return false;
		}

		return true;
	}

	private static function releaseLock() {
		delete_option( self::LOCK );
	}

	private static function createLock() {
		update_option( self::LOCK, time() );
	}

	public function cancelBulkOctify() {
		$pending = OctifyApi::findImages( 'pending' );
		foreach ( $pending as $image ) {
			delete_post_meta( $image->ID, OctifyApi::META_STATUS );
			delete_post_meta( $image->ID, OctifyApi::META_NAME );
		}
		wp_send_json_success();
	}
}