<?php

namespace Octify\Libs;

class OctifyApi {
	const META_NAME = 'octifyImageData', META_STATUS = 'octifyImageStatus', LICENSE_NAME = 'octifyActivateCode';
	const API_ENDPOINT = 'http://octify1.wpcodely.com/';

	/**
	 * @param $type
	 *
	 * @return array
	 */
	public static function findImages( $type ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array(
				'image/jpeg',
				'image/png',
				'image/gif'
			),
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
		);
		switch ( $type ) {
			case 'pending':
				$args['meta_key']   = self::META_STATUS;
				$args['meta_value'] = 'pending';
				break;
			case 'compressed':
				$args['meta_key']   = self::META_STATUS;
				$args['meta_value'] = 'compressed';
				break;
			case 'error':
				$args['meta_key']   = self::META_STATUS;
				$args['meta_value'] = 'error';
				break;
			case 'uncompressed':
				$args['meta_key']     = self::META_STATUS;
				$args['meta_compare'] = 'NOT EXISTS';
				break;
		}
		$query = new \WP_Query( $args );
		$posts = $query->get_posts();

		//validate if the link is still good
		foreach ( $posts as $key => $post ) {
			if ( ! is_file( get_attached_file( $post->ID ) ) ) {
				unset( $posts[ $key ] );
			}
		}

		return $posts;
	}

	/**
	 * @return array
	 */
	public static function getStats() {
		$images     = self::findImages( 'compressed' );
		$total      = 0;
		$after      = 0;
		$jpg        = 0;
		$jpgBefore  = 0;
		$jpgAfter   = 0;
		$jpgPending = 0;
		$png        = 0;
		$pngBefore  = 0;
		$pngAfter   = 0;
		$pngPending = 0;
		$gif        = 0;
		$gifBefore  = 0;
		$gifAfter   = 0;
		$gifPending = 0;
		foreach ( $images as $image ) {
			$detail   = get_post_meta( $image->ID, self::META_NAME, true );
			$total    += $detail['before'];
			$after    += $detail['after'];
			$mimeType = get_post_mime_type( $image->ID );
			switch ( $mimeType ) {
				case 'image/jpeg':
					$jpg ++;
					$jpgBefore += $detail['before'];
					$jpgAfter  += $detail['after'];
					break;
				case 'image/png':
					$png ++;
					$pngBefore += $detail['before'];
					$pngAfter  += $detail['after'];
					break;
				case 'image/gif':
					$gif ++;
					$gifBefore += $detail['before'];
					$gifAfter  += $detail['after'];
					break;
			}
		}

		$pending = self::findImages( 'uncompressed' );
		foreach ( $pending as $image ) {
			$mimeType = get_post_mime_type( $image->ID );
			switch ( $mimeType ) {
				case 'image/jpeg':
					$jpgPending ++;
					break;
				case 'image/png':
					$pngPending ++;
					break;
				case 'image/gif':
					$gifPending ++;
					break;
			}
		}

		return array(
			'total'   => $total,
			'after'   => $after,
			'saved'   => $total - $after,
			'saved_p' => $total > 0 ? ( ( $total - $after ) / $total ) * 100 : 0,
			'jpg'     => array(
				'total'   => $jpg,
				'before'  => $jpgBefore,
				'after'   => $jpgAfter,
				'saved_p' => $jpgBefore > 0 ? ( ( $jpgBefore - $jpgAfter ) / $jpgBefore ) * 100 : 0,
				'saved'   => $jpgBefore - $jpgAfter,
				'pending' => $jpgPending
			),
			'png'     => array(
				'total'   => $png,
				'before'  => $pngBefore,
				'after'   => $pngAfter,
				'saved_p' => $pngBefore > 0 ? ( ( $pngBefore - $pngAfter ) / $pngBefore ) * 100 : 0,
				'saved'   => $pngBefore - $pngAfter,
				'pending' => $pngPending
			),
			'gif'     => array(
				'total'   => $gif,
				'before'  => $gifBefore,
				'after'   => $gifAfter,
				'saved_p' => $gifBefore > 0 ? ( ( $gifBefore - $gifAfter ) / $gifBefore ) * 100 : 0,
				'saved'   => $gifBefore - $gifAfter,
				'pending' => $gifPending
			)
		);
	}

	/**
	 * @param $email
	 * @param $code
	 *
	 * @return \WP_Error
	 */
	public static function activateCheck( $email, $code ) {
		if ( empty( $email ) || empty( $code ) ) {
			return new \WP_Error( 'validate', __( 'Shoot! That license is incorrect. Double check
your code and try again. Still won\'t activate? Contact support' ) );
		}

		$request  = wp_remote_post( self::API_ENDPOINT . 'v1/site/activate', array(
			'body' => array(
				'email'  => $email,
				'code'   => $code,
				'domain' => get_site_url()
			)
		) );
		$httpCode = wp_remote_retrieve_response_code( $request );
		if ( $httpCode == 200 ) {
			update_option( self::LICENSE_NAME, array(
				'code'      => $code,
				'status'    => 1,
				'isExpired' => 0
			) );

			return true;
		}

		return new \WP_Error( 'validate', __( 'Shoot! That license is incorrect. Double check
your code and try again. Still won\'t activate? Contact support' ) );
	}

	public static function getSubDetail() {
		$request = wp_remote_post( self::API_ENDPOINT . 'v1/customer/subscription_detail', array(
			'body' => array(
				'code' => self::getLicense()
			)
		) );
		$body    = wp_remote_retrieve_body( $request );
		$body    = json_decode( $body, true );

		return $body;
	}

	/**
	 * @param $ids
	 *
	 * @return array|bool|\WP_Error
	 */
	public static function sendEnvelope( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_filter( $ids );
		if ( empty( $ids ) ) {
			return false;
		}
		$uploadDirs     = wp_upload_dir();
		$imageToProcess = array();
		$settings       = octify()->getSettings();
		foreach ( $ids as $id ) {
			if ( ! filter_var( $id, FILTER_VALIDATE_INT ) && $id instanceof \WP_Post ) {
				$id = $id->ID;
			} elseif ( ! filter_var( $id, FILTER_VALIDATE_INT ) ) {
				continue;
			}

			if ( ! wp_attachment_is_image( $id ) ) {
				continue;
			}

			$imageMeta             = wp_get_attachment_metadata( $id );
			$imageUrl              = wp_get_attachment_image_url( $id, 'full' );
			$imagePath             = $uploadDirs['basedir'] . '/' . $imageMeta['file'];
			$imageToProcess[ $id ] = array(
				'site_url'      => site_url(),
				'meta'          => $id,
				'image_url'     => $imageUrl,
				'method'        => $settings['mode'],
				'keep_exif'     => $settings['keep_exif'],
				'resize'        => $settings['resize'],
				'resize_width'  => $settings['resize_width'],
				'resize_height' => $settings['resize_height'],
				'postback'      => '/wp-admin/admin-ajax.php?action=octify_postback',
				'secret'        => self::getLicense(),
				'api_key'       => self::getLicense(),
				'filesize'      => filesize( $imagePath )
			);
		}

		$imageToProcess = array_filter( $imageToProcess );
		if ( count( $imageToProcess ) ) {
			$chunks = array_chunk( $imageToProcess, 50 );
			$data   = array(
				'success' => array(),
				'fail'    => array()
			);
			foreach ( $chunks as $chunk ) {
				$request = wp_remote_post( self::API_ENDPOINT . 'v1/image/bulk_compress2', array(
					'body'    => array(
						'images' => json_encode( $chunk )
					),
					'timeout' => 60
				) );

				if ( is_wp_error( $request ) ) {
					return $request;
				}
				$body = wp_remote_retrieve_body( $request );
				//var_dump($body);die;
				$body = json_decode( $body, true );
				if ( ! is_array( $body ) ) {
					return new \WP_Error( 'invalid_response', 'Please try again' );
				}
				if ( $body['status'] == 1 ) {
					$data['success'] = array_merge( $data['success'], array_keys( $imageToProcess ) );
				} else {
					//get the error and success
					$success = array_keys( $imageToProcess );
					$fail    = array();
					foreach ( $body['error'] as $error ) {
						unset( $success[ $error['meta'] ] );
						$fail[] = array(
							'id'    => $error['meta'],
							'error' => $error['error'],
							'code'  => $error['errorCode']
						);
						if ( $error['errorCode'] == 'expired' ) {
							$license              = get_option( self::LICENSE_NAME, false );
							$license['isExpired'] = true;
							update_option( self::LICENSE_NAME, $license );
						}
					}
					$data['success'] = array_merge( $data['success'], $success );
					$data['fail']    = array_merge( $data['fail'], $fail );
				}
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * @param $email
	 *
	 * @return array|mixed|object|string|\WP_Error
	 */
	public static function getAPIKey( $email ) {
		$apiURL  = self::API_ENDPOINT . '/v1/customer/generate_free_code';
		$request = wp_remote_post( $apiURL, array(
			'body' => array(
				'email' => $email
			)
		) );

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );
		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'invalid_response', 'Please try again' );
		}

		return $body;
	}

	/**
	 * @param $data
	 */
	public static function processAPIPostback( $data ) {
		if ( ! isset( $data['hmac'] ) ) {
			return;
		}
		$comparedHmac = $data['hmac'];
		unset( $data['hmac'] );
		$string  = implode( '|', $data );
		$license = self::getLicense();
		$hmac    = hash_hmac( 'sha256', $string, $license );
		if ( $hmac != $comparedHmac ) {
			return;
		}

		$settings = octify()->getSettings();

		if ( self::removeProtocol( $data['site_url'] ) == self::removeProtocol( network_site_url() ) ) {
			$attachmentId = $data['meta'];

			if ( ! wp_attachment_is_image( $attachmentId ) ) {
				return;
			}

//			if ( $data['status'] == 'error' ) {
//				update_post_meta( $attachmentId, self::META_STATUS, 'error' );
//
//				return;
//			}
			$file = get_attached_file( $attachmentId );
			if ( ! function_exists( 'download_url' ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$uploadsDir = wp_upload_dir();
			$tmpPath    = $uploadsDir['basedir'] . '/octify/tmp/';
			if ( ! is_dir( $tmpPath ) ) {
				wp_mkdir_p( $tmpPath );
			}
			$tmp = download_url( $data['image_url'] );
			if ( is_wp_error( $tmp ) ) {
				update_post_meta( $attachmentId, self::META_STATUS, 'error' );
				update_post_meta( $attachmentId, self::META_NAME, array(
					'error' => $tmp->get_error_message()
				) );

				return;
			}
			if ( ! copy( $tmp, $tmpPath . pathinfo( $file, PATHINFO_BASENAME ) ) ) {
				update_post_meta( $attachmentId, self::META_STATUS, 'error' );
				update_post_meta( $attachmentId, self::META_NAME, array(
					'error' => 'Can\'t download image cause of permission issue'
				) );

				return;
			}
			@unlink( $tmp );
			$backupPath = $uploadsDir['basedir'] . '/octify/backup/';
			$backupFile = null;
			if ( ! is_dir( $backupPath ) ) {
				wp_mkdir_p( $backupPath );
			}
			if ( $settings['backup_original'] ) {
				if ( ! copy( $file, $backupPath . pathinfo( $file, PATHINFO_BASENAME ) ) ) {
					update_post_meta( $attachmentId, self::META_STATUS, 'error' );
					update_post_meta( $attachmentId, self::META_NAME, array(
						'error' => 'Can\'t create backup'
					) );

					return;
				}
				$backupFile = $backupPath . pathinfo( $file, PATHINFO_BASENAME );
			}
			$beforeSize = filesize( $file );
			//override it
			@unlink( $file );
			copy( $tmpPath . pathinfo( $file, PATHINFO_BASENAME ), $file );
			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				include( ABSPATH . 'wp-admin/includes/image.php' );
			}
			if ( $settings['optimise_different_size'] ) {
				wp_generate_attachment_metadata( $attachmentId, $file );
			}
			$afterSize = filesize( $file );
			update_post_meta( $attachmentId, self::META_STATUS, 'compressed' );
			update_post_meta( $attachmentId, self::META_NAME, array(
				'status'  => 'success',
				'before'  => $beforeSize,
				'after'   => filesize( $file ),
				'saved'   => $beforeSize - $afterSize,
				'saved_p' => round( ( ( $beforeSize - $afterSize ) * 100 ) / $beforeSize, 2 ),
				'backup'  => $backupFile,
				'file'    => $file
			) );
		}
	}

	/**
	 * @param $size
	 * @param int $precision
	 *
	 * @return string
	 */
	public static function humanFilesize( $size, $precision = 2 ) {
		$units = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		$step  = 1024;
		$i     = 0;
		while ( ( $size / $step ) > 0.9 ) {
			$size = $size / $step;
			$i ++;
		}

		return round( $size, $precision ) . $units[ $i ];
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	public static function removeProtocol( $url ) {
		$parts = parse_url( $url );

		$host = $parts['host'] . ( isset( $parts['path'] ) ? $parts['path'] : null );
		$host = rtrim( $host, '/' );

		return $host;
	}

	/**
	 * @return string
	 */
	public static function getLicense() {
		$license = get_option( self::LICENSE_NAME, false );
		if ( is_array( $license ) ) {
			$code   = $license['code'];
			$status = $license['status'];
			if ( $status == 1 ) {
				return $code;
			}
		}

		return false;
	}

	public static function isLicenseExpired() {
		$license = get_option( self::LICENSE_NAME, false );
		if ( ( is_array( $license ) && isset( $license['isExpired'] ) && $license['isExpired'] == 1 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return float|int
	 */
	public static function calculateProgress() {
		$cached = get_site_transient( 'octifyUncompressCached' );
		if ( ! is_array( $cached ) ) {
			return 0;
		}

		$total = count( $cached );
		if ( $total == 0 ) {
			return 0;
		}
		$processed = 0;
		foreach ( $cached as $id ) {
			$status = get_post_meta( $id, self::META_STATUS, true );
			if ( $status == 'compressed' ) {
				$processed ++;
			} else {
				$detail = get_post_meta( $id, self::META_NAME, true );
				if ( isset( $detail['status'] ) && $detail['status'] == 'success' ) {
					$processed ++;
					update_post_meta( $id, self::META_STATUS, 'compressed' );
				}
			}
		}
		$percent = round( ( $processed / $total ) * 100, 2 );
		if ( $percent > 100 ) {
			$percent = 100;
		}

		return $percent;
	}
}