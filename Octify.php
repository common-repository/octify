<?php
/**
 * Plugin Name: Octify
 * Plugin URI: https://octify.io
 * Description: Octify Image Compression for WordPress.
 * Version: 1.3
 * Author: Octify
 * Author URI: https://octify.io
 * Tested up to: 4.9
 */

class Octify {
	/**
	 * Singleton instance
	 * @var
	 */
	static $instance;
	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';
	/**
	 * @var string
	 */
	public $domain = '';

	public function __construct() {
		$this->init();
		//load domain
		add_action( 'plugins_loaded', array( &$this, 'localization' ) );
		//autoload
		spl_autoload_register( array( &$this, 'autoload' ) );
		//scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'adminScripts' ) );
		add_action( 'admin_menu', array( &$this, 'adminMenu' ) );
		add_action( 'wp_loaded', array( '\Octify\Controller\Settings', 'saveSettings' ) );
		add_action( 'wp_loaded', array( '\Octify\Controller\Payment', 'activate' ) );
		add_action( 'wp_loaded', array( '\Octify\Controller\Payment', 'getFreeKey' ) );
		//inject octify to media page
		add_filter( 'manage_media_columns', array( &$this, 'columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'customColumn' ), 10, 2 );
		add_filter( 'manage_upload_sortable_columns', array( $this, 'sortableColumn' ) );
		add_filter( 'attachment_fields_to_edit', array( &$this, 'imageStatsEditAttachment' ), 10, 2 );
		add_action( 'add_meta_boxes', array( &$this, 'imageStatsMetabox' ) );
		//
		add_action( 'wp_ajax_nopriv_octify_postback', array( &$this, 'postback' ) );
		///
		add_action( 'wp_ajax_octify_compress', array( &$this, 'ajaxCompressImage' ) );
		add_action( 'wp_ajax_octify_img_stats', array( &$this, 'ajaxImageStatus' ) );
		add_action( 'wp_ajax_octify_revert', array( &$this, 'revert' ) );
		//
		add_action( 'wp_ajax_start_bulk_octify', array( '\Octify\Controller\Compress', 'bulkOctify' ) );
		add_action( 'wp_ajax_cancel_octify_compress', array( '\Octify\Controller\Compress', 'cancelBulkOctify' ) );
		add_action( 'wp_ajax_get_bulk_octify_status', array( '\Octify\Controller\Compress', 'getOctifyStatus' ) );
		//
		add_action( 'wp_shutdown', function () {
			delete_option( 'octifyFlash' );
			delete_option( 'octifyErrorFlash' );
		} );

		$revertAll = isset( $_GET['octify_revert'] ) ? 1 : 0;
		if ( $revertAll ) {
			add_action( 'wp_loaded', array( &$this, 'revertAll' ) );
		}
	}

	public function revertAll() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$args  = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
		);
		$query = new \WP_Query( $args );
		foreach ( $query->get_posts() as $post ) {
			$status = get_post_meta( $post->ID, \Octify\Libs\OctifyApi::META_STATUS, true );
			$id     = $post->ID;
			if ( strlen( $status ) ) {
				$meta = get_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME, true );
				if ( $meta['status'] == 'success' ) {
					$backupPath = $meta['backup'];
					if ( ! is_file( $backupPath ) ) {
						continue;
					}
					$backupPath = $meta['backup'];
					$file       = get_attached_file( $id );
					copy( $backupPath, $file );
					if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
						include( ABSPATH . 'wp-admin/includes/image.php' );
					}
					wp_generate_attachment_metadata( $id, $file );
					delete_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME );
					delete_post_meta( $id, \Octify\Libs\OctifyApi::META_STATUS );
				} else {
					delete_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME );
					delete_post_meta( $id, \Octify\Libs\OctifyApi::META_STATUS );
				}
			}
		}
	}

	public function revert() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$id   = intval( $_POST['id'] );
		$meta = get_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME, true );
		if ( ! is_array( $meta ) ) {
			wp_send_json( array(
				'status' => 0,
				'error'  => __( "Invalid attachment", octify()->domain )
			) );
		}

		$backupPath = $meta['backup'];
		if ( ! is_file( $backupPath ) ) {
			wp_send_json( array(
				'status' => 0,
				'error'  => __( "Backup file was deleted!", octify()->domain )
			) );
		}
		$file = get_attached_file( $id );
		//unlink( $file );
		copy( $backupPath, $file );
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}
		delete_post_meta( $id, \Octify\Libs\OctifyApi::META_NAME );
		delete_post_meta( $id, \Octify\Libs\OctifyApi::META_STATUS );
		//wp_generate_attachment_metadata( $id, $file );
		wp_send_json( array(
			'status' => 1,
			'html'   => $this->showImageStatus( $id )
		) );
	}

	public function ajaxImageStatus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$id = intval( $_POST['id'] );
		if ( ! $id ) {
			return;
		}
		$meta = get_post_meta( $id, \Octify\Libs\OctifyApi::META_STATUS, true );
		if ( $meta == 'compressed' ) {
			wp_send_json( array(
				'status' => 1,
				'text'   => $this->showImageStatus( $id )
			) );
		} else {
			wp_send_json( array(
				'status' => 0
			) );
		}
	}

	public function postback() {
		$data = $_POST;
		\Octify\Libs\OctifyApi::processAPIPostback( $data );
	}

	/**
	 *
	 */
	public function ajaxCompressImage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$id = intval( $_POST['id'] );
		if ( ! $id ) {
			return;
		}
		$res = \Octify\Libs\OctifyApi::sendEnvelope( $id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array(
				'error' => $res->get_error_message()
			) );
		}
		//generic update the status
		foreach ( $res['success'] as $id ) {
			update_post_meta( $id, \Octify\Libs\OctifyApi::META_STATUS, 'pending' );
		}
		foreach ( $res['fail'] as $id ) {
			update_post_meta( $id['id'], \Octify\Libs\OctifyApi::META_STATUS, 'error' );
			update_post_meta( $id['id'], \Octify\Libs\OctifyApi::META_NAME, array(
				'error' => $id['error']
			) );
		}

		if ( count( $res['fail'] ) ) {
			//we have error
			$err = $res['fail'][0]['error'];
			if ( $res['fail'][0]['code'] == 'expired' ) {
				$err = sprintf( __( "Whoops! Sorry you've used up your compression Quota. Want more? <a target='_blank' href='%s'>Buy Now</a>", octify()->domain ), 'https://octify.io/' );
			}
			wp_send_json( array(
				'error' => $err
			) );
		} else {
			wp_send_json( array(
				'status' => 1,
				'button' => __( "Compressing...", $this->domain )
			) );
		}
	}

	/**
	 * @param $form_fields
	 * @param $post
	 *
	 * @return mixed
	 */
	public function imageStatsEditAttachment( $form_fields, $post ) {
		if ( ! wp_attachment_is_image( $post ) ) {
			return $form_fields;
		}
		$mime = get_post_mime_type( $post->ID );
		$mime = strtolower( $mime );
		if ( ! in_array( $mime, array(
			'image/jpeg',
			'image/png',
			'image/gif'
		) ) ) {
			return $form_fields;
		}
		$form_fields['location'] = array(
			'label' => __( 'Image Stats', $this->domain ),
			'input' => 'html',
			'html'  => $this->showImageStatus( $post->ID )
		);

		return $form_fields;
	}

	/**
	 *
	 */
	public function imageStatsMetabox() {
		add_meta_box( 'image-stats-box', __( 'Compress Status', $this->domain ), array(
			$this,
			'imageStatsMetaboxCallback'
		), 'attachment', 'side' );
	}

	/**
	 * @param $post
	 */
	public function imageStatsMetaboxCallback( $post ) {
		if ( wp_attachment_is_image( $post ) ) {
			$mime = get_post_mime_type( $post->ID );
			$mime = strtolower( $mime );
			if ( in_array( $mime, array(
				'image/jpeg',
				'image/png',
				'image/gif'
			) ) ) {
				echo $this->showImageStatus( $post->ID );
			}
		} else {
			__( "N/A", $this->domain );
		}
	}

	/**
	 * @param $defaults
	 *
	 * @return mixed
	 */
	public function columns( $defaults ) {
		$defaults['octify'] = __( "Octify", $this->domain );

		return $defaults;
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	function sortableColumn( $columns ) {
		$columns['octify'] = 'octify';

		return $columns;
	}

	/**
	 * @param $columnName
	 * @param $attachmentId
	 */
	public function customColumn( $columnName, $attachmentId ) {
		if ( 'octify' == $columnName ) {
			$mime = get_post_mime_type( $attachmentId );
			$mime = strtolower( $mime );
			if ( in_array( $mime, array(
				'image/jpeg',
				'image/png',
				'image/gif'
			) ) ) {
				echo $this->showImageStatus( $attachmentId );
			}
		}
	}

	/**
	 * @param $attachmentId
	 *
	 * @return string
	 */
	private function showImageStatus( $attachmentId ) {
		if ( ! wp_attachment_is_image( $attachmentId ) ) {
			return 'N/A';
		}

		$status = get_post_meta( $attachmentId, \Octify\Libs\OctifyApi::META_STATUS, true );
		if ( ! $status ) {
			ob_start();
			?>
            <button data-id="<?php echo esc_attr( $attachmentId ) ?>" class="button octify-compress" type="button">
				<?php _e( "Compress Now", $this->domain ) ?>
            </button>
			<?php
			return ob_get_clean();
		} else {
			$data = get_post_meta( $attachmentId, \Octify\Libs\OctifyApi::META_NAME, true );
			if ( $status == 'error' ) {
				ob_start();
				?>
                <p><?php
					//_e( "An error has happened, please try to compress again", $this->domain )
					echo $data['error'];
					?></p>
                <button data-id="<?php echo esc_attr( $attachmentId ) ?>" class="button octify-compress" type="button">
					<?php _e( "Try again!", $this->domain ) ?>
                </button>
				<?php
				return ob_get_clean();
			} elseif ( $status == 'pending' ) {
				ob_start();
				?>
                <button data-id="<?php echo esc_attr( $attachmentId ) ?>" class="button" disabled="disabled"
                        type="button">
					<?php _e( "Compressing...", $this->domain ) ?>
                </button>
				<?php
				return ob_get_clean();
			} elseif ( $status == 'compressed' ) {
				ob_start();
				?>
                <div>
                    <p><?php echo sprintf( __( "Reduced by %s ( %s )", $this->domain ), \Octify\Libs\OctifyApi::humanFilesize( $data['saved'] ), $data['saved_p'] . '%' ) ?></p>
                    <p><?php echo sprintf( __( "Original Size: %s", $this->domain ), \Octify\Libs\OctifyApi::humanFilesize( $data['before'] ) ) ?></p>
					<?php if ( $data['backup'] != null ): ?>
                        <button type="button" data-id="<?php echo esc_attr( $attachmentId ) ?>"
                                class="button octify-revert">
							<?php _e( "Revert", $this->domain ) ?>
                        </button>
					<?php endif; ?>
                </div>
				<?php
				return ob_get_clean();
			}
		}
	}

	public function adminMenu() {
		$svg = file_get_contents( $this->plugin_path . 'assets/octify-character-dashicon.svg' );
		$svg = 'data:image/svg+xml;base64,' . base64_encode( $svg );

		if ( \Octify\Libs\OctifyApi::getLicense() == false ) {
			add_menu_page( __( "Octify", $this->domain ), __( "Octify", $this->domain ), 'manage_options', 'octify', array(
				&$this,
				'activator'
			), $svg );
		} else {
			add_menu_page( __( "Octify", $this->domain ), __( "Octify", $this->domain ), 'manage_options', 'octify', array(
				&$this,
				'showDashboard'
			), $svg );
		}

		if ( is_plugin_active( 'ocean-extra/ocean-extra.php' ) ) {
			global $submenu;
			$permalink = 'https://octify.io/?ref=nicolaslecocq';
			add_submenu_page( 'octify', __( "Go Pro", octify()->domain ), __( "Go Pro", octify()->domain ), 'manage_options', 'octify-pro', array(
				&$this,
				'goPro'
			) );
			$submenu['octify'][] = array( 'Go Pro', 'manage_options', $permalink );
			unset( $submenu['octify'][1] );
		}

	}

	public function goPro() {
		wp_redirect( 'https://octify.io/?ref=nicolaslecocq' );
		exit;
	}

	public function showDashboard() {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'octify-chart', $this->plugin_url . 'assets/chart.bundle.min.js' );
		$this->render( 'dashboard2' );
	}

	public function activator() {
		$step = isset( $_GET['step'] ) ? $_GET['step'] : 1;
		switch ( $step ) {
			case 2:
				$this->render( 'activator2' );
				break;
			case 1:
			default:
				$this->render( 'activator' );
				break;
		}
	}

	/**
	 * PSR4 autload
	 *
	 * @param $className
	 */
	function autoload( $className ) {
		$className = ltrim( $className, '\\' );
		$parts     = explode( '\\', $className );
		if ( $parts[0] != 'Octify' ) {
			return;
		}
		unset( $parts[0] );
		$className = implode( '\\', $parts );
		$fileName  = '';
		$namespace = '';
		if ( $lastNsPos = strrpos( $className, '\\' ) ) {
			$namespace = substr( $className, 0, $lastNsPos );
			$className = substr( $className, $lastNsPos + 1 );
			$fileName  = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace( '_', DIRECTORY_SEPARATOR, $className ) . '.php';
		require __DIR__ . DIRECTORY_SEPARATOR . $fileName;
	}

	/**
	 * Init
	 */
	public function init() {
		//some defined
		$this->plugin_url  = plugins_url( '/', __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->domain      = 'ocitfy';
	}

	public function localization() {

	}

	/**
	 * Get settings
	 * @return array
	 */
	public function getSettings() {
		$defaults = array(
			'mode'                    => 'normal',
			'upload_listener'         => true,
			'backup_original'         => true,
			'resize'                  => false,
			'resize_width'            => 1280,
			'resize_height'           => 0,
			'keep_exif'               => false,
			'optimise_different_size' => true
		);

		$options = get_option( 'octify', array() );

		return wp_parse_args( $options, $defaults );
	}

	public function adminScripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'octify' ) {
			wp_enqueue_style( 'octify', $this->plugin_url . 'assets/styles.css' );
		}
		wp_enqueue_script( 'octify', $this->plugin_url . 'assets/script.js' );
	}

	/**
	 * @param $view
	 * @param array $args
	 * @param bool $echo
	 *
	 * @return null|string
	 */
	public function render( $view, $args = array(), $echo = true ) {
		$viewPath = __DIR__ . '/View/' . $view . '.php';
		if ( is_file( $viewPath ) ) {
			extract( $args );
			ob_start();
			include $viewPath;
			$contents = ob_get_clean();

			if ( $echo == true ) {
				echo $contents;
			} else {
				return $contents;
			}
		}

		return null;
	}
}

/**
 * @return Octify
 */
function octify() {
	if ( ! is_object( Octify::$instance ) ) {
		Octify::$instance = new Octify();
	}

	return Octify::$instance;
}

octify();
