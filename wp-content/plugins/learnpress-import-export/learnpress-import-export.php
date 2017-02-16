<?php
/*
Plugin Name: LearnPress - Export/Import Courses
Plugin URI: http://thimpress.com/learnpress
Description: Export and Import your courses with all lesson and quiz in easiest way
Author: ThimPress
Version: 2.0
Author URI: http://thimpress.com
Text Domain: learnpress-import-export
Tags: learnpress
*/
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'LP_IMPORT_EXPORT_FILE', __FILE__ );
define( 'LP_IMPORT_EXPORT_PATH', dirname( __FILE__ ) );
define( 'LP_IMPORT_EXPORT_VER', '2.0' );
define( 'LP_IMPORT_EXPORT_URL', plugins_url( '/', LP_IMPORT_EXPORT_FILE ) );
define( 'LP_IMPORT_EXPORT_REQUIRE_VER', '2.0' );

/**
 * Class LP_Addon_Import_Export_Courses
 */
class LP_Addon_Import_Export_Courses {
	/**
	 * @var LP_Addon_Import_Export_Courses|null
	 */
	protected static $_instance = null;

	/**
	 * @var LP_Import
	 */
	private $importer = null;

	/**
	 * LP_Addon_Import_Export_Courses constructor.
	 */
	public function __construct() {
		if ( !$this->check_version() ) {
			return;
		}
		$this->init();
	}

	/**
	 * Init hooks and include needed files or do anything...
	 */
	public function init() {
		// include files
		require_once LP_IMPORT_EXPORT_PATH . '/incs/functions.php';
		require_once LP_IMPORT_EXPORT_PATH . '/incs/export/providers/class-lp-export-learnpress.php';

		$this->importer = include_once( LP_IMPORT_EXPORT_PATH . '/incs/class-lp-import.php' );


		// hooks
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'do_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

	}

	/**
	 * Do action when request is submitted
	 */
	public function do_action() {
		$this->_do_action();
	}

	/**
	 * Do action when request is submitted
	 */
	private function _do_action() {
		// hide notice import sample data if we are in import/export screen
		if ( !empty( $_REQUEST['page'] ) && 'learnpress-import-export' == $_REQUEST['page'] ) {
			remove_action( 'admin_notices', 'learn_press_one_click_install_sample_data_notice' );
		}
		do_action( 'learn_press_import_export_actions' );
		// delete files
		$this->_delete_files();
		// download file
		$this->_download_file();
		// dispatch export
		$this->_dispatch_export();
		// dispatch import
		$this->_dispatch_import();

		$this->_delete_tmp();
	}

	/**
	 * Delete files are imported/exported
	 */
	private function _delete_files() {
		// delete file
		if ( !empty( $_REQUEST['delete-export'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-delete-export-file' ) ) {
			$file = learn_press_get_request( 'delete-export' );
			if ( $file ) {
				$file = explode( ',', $file );
				foreach ( $file as $f ) {
					lpie_delete_file( 'learnpress/export/' . $f );
				}
			}
			wp_redirect( admin_url( 'admin.php?page=learnpress-import-export&tab=export-course' ) );
			exit();
		}
		if ( !empty( $_REQUEST['delete-import'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-delete-import-file' ) ) {
			$file = learn_press_get_request( 'delete-import' );
			if ( $file ) {
				$file = explode( ',', $file );
				foreach ( $file as $f ) {
					lpie_delete_file( 'learnpress/import/' . $f );
				}
			}
			wp_redirect( admin_url( 'admin.php?page=learnpress-import-export&tab=import-course' ) );
			exit();
		}
	}

	private function _delete_tmp() {
		if ( $filesystem = lpie_filesystem() ) {
			$path = lpie_root_path() . '/learnpress/tmp';
			$list = $filesystem->dirlist( $path );
			if ( $list ) foreach ( $list as $file ) {
				if ( time() - $file['lastmodunix'] > HOUR_IN_SECONDS ) {
					@unlink( $path . '/' . $file['name'] );
				}
			}
		}
	}

	/**
	 * Download file was imported/exported
	 */
	private function _download_file() {
		// download file was exported
		if ( !empty( $_REQUEST['download-export'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-download-export-file' ) ) {
			$file = learn_press_get_request( 'download-export' );
			lpie_export_header( $file );
			echo lpie_get_contents( 'learnpress/export/' . $file );
			die();
		}
		// download file was imported
		if ( !empty( $_REQUEST['download-import'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-download-import-file' ) ) {
			$file = learn_press_get_request( 'download-import' );
			lpie_export_header( $file );
			echo lpie_get_contents( 'learnpress/import/' . $file );
			die();
		}
		// download file was imported
		if ( !empty( $_REQUEST['download-file'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-download-file' ) ) {
			$file = learn_press_get_request( 'download-file' );
			lpie_export_header( !empty( $_REQUEST['alias'] ) ? $_REQUEST['alias'] : basename( $file ) );
			echo lpie_get_contents( $file );
			die();
		}
	}

	/**
	 * Get export provider from a name in request
	 * and call do_export method
	 */
	private function _dispatch_export() {
		if ( !empty( $_REQUEST['export-nonce'] ) && wp_verify_nonce( $_REQUEST['export-nonce'], 'learnpress-import-export-export' ) ) {
			if ( empty( $_REQUEST['exporter'] ) ) {
				learn_press_add_notice( __( '<p>Please select a source to export.</p>', 'learnpress-import-export' ), 'error' );
			} else {
				do_action( 'lpie_export_' . $_REQUEST['exporter'], !empty( $_REQUEST['step'] ) ? $_REQUEST['step'] : 0 );
			}
		}
	}

	/**
	 * Import form upload file or files was exported
	 */
	private function _dispatch_import() {
		// import from upload
		if ( !empty( $_REQUEST['import-nonce'] ) && wp_verify_nonce( $_REQUEST['import-nonce'], 'learnpress-import-export-import' ) ) {

			if ( empty( $_REQUEST['import-file'] ) ) {
				include_once LP_IMPORT_EXPORT_PATH . '/incs/lp-import-functions.php';
				if ( !empty( $_REQUEST['step'] ) ) {
					do_action( 'lpie_import_step_' . $_REQUEST['step'] );
				}
			} else {
				// import from file on server
				if ( !empty( $_REQUEST['import-file'] ) ) {
					include_once LP_IMPORT_EXPORT_PATH . '/incs/lp-import-functions.php';
					$file = learn_press_get_request( 'import-file' );
					$this->importer->do_import( $file );
					if ( !empty( $_REQUEST['save_import'] ) ) {
						copy( lpie_root_path() . '/learnpress/' . $file, lpie_import_path() . '/' . basename( $file ) );

					}
					//@unlink( lpie_root_path() . '/learnpress/' . $file );
				}
			}
			/*$file = lp_import_handle_upload( $_FILES['import'], array( 'mimes' => array( 'xml' => 'text/xml' ) ) );
			if ( !empty( $file['file'] ) && file_exists( $file['file'] ) ) {
				$file_name = basename( $file['file'] );
				$redirect  = admin_url( 'admin.php?page=learnpress-import-export&tab=import-course&import-file=import/' . $file_name . '&nonce=' . wp_create_nonce( 'lpie-import-file' ) );
				wp_redirect( $redirect );
				die();
			}*/
			return;
		}


	}

	/**
	 * Check LearnPress version is supported
	 *
	 * @return bool
	 */
	public function check_version() {
		if ( !defined( 'LEARNPRESS_VERSION' ) || ( version_compare( LEARNPRESS_VERSION, LP_IMPORT_EXPORT_REQUIRE_VER, '<' ) ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );

			return false;
		}
		return true;
	}

	/**
	 * Show notice in admin site if LearnPress is not activated
	 * or current version is not supported.
	 */
	public function admin_notice() {
		?>
		<div class="error">
			<p><?php printf( __( '<strong>Import/Export Courses</strong> addon version %s requires LearnPress version %s or higher', 'learnpress-import-export' ), LP_IMPORT_EXPORT_VER, LP_IMPORT_EXPORT_REQUIRE_VER ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add menu item to LearnPress menu
	 */
	public function admin_menu() {
		add_submenu_page(
			'learn_press',
			__( 'Import/Export', 'learnpress-import-export' ),
			__( 'Import/Export', 'learnpress-import-export' ),
			'manage_options',
			'learnpress-import-export',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Show page for our main menu
	 */
	public function admin_page() {
		lpie_admin_view( 'admin-page' );
	}

	/**
	 * Load text domain
	 */
	public function load_text_domain() {
		if ( function_exists( 'learn_press_load_plugin_text_domain' ) ) {
			learn_press_load_plugin_text_domain( LP_IMPORT_EXPORT_PATH, true );
		}
	}

	public function admin_scripts() {
		wp_enqueue_script( 'learn-press-export-script', LP_IMPORT_EXPORT_URL . '/assets/js/export.js', array( 'jquery', 'backbone', 'wp-util' ) );
		wp_enqueue_script( 'learn-press-import-script', LP_IMPORT_EXPORT_URL . '/assets/js/import.js', array( 'jquery', 'backbone', 'wp-util' ) );
	}

	public function admin_styles() {
		wp_enqueue_style( 'learn-press-import-export-style', LP_IMPORT_EXPORT_URL . '/assets/css/export-import.css' );
	}

	/**
	 * @return LP_Addon_Import_Export_Courses|null
	 */
	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

add_action( 'learn_press_ready', array( 'LP_Addon_Import_Export_Courses', 'instance' ) );

function learn_press_import_export_load() {
	if ( defined( 'LEARNPRESS_VERSION' ) && version_compare( LEARNPRESS_VERSION, '2.0', '<' ) ) {
		require_once "compatible/learnpress-import-export.php";
	} else {
		define( 'learn_press_import_export', 'learn_press_import_export' );
		require_once "incs/class-lp-import-export.php";
		LP_Import_Export::init();
	}
}

//add_action( 'learn_press_ready', 'learn_press_import_export_load' );

