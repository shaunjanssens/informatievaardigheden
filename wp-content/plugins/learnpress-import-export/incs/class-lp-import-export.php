<?php
/**
 * Class LP_Import_Export
 */
class LP_Import_Export {

	/**
	 * @var object
	 */
	private static $_instance = false;

	/**
	 * @var string
	 */
	private $_plugin_url = '';

	/**
	 * @var string
	 */
	private $_plugin_path = '';

	/**
	 * @var string
	 */
	protected $_export = null;

	public $learnpress_version = null;
	public $learnpress_db_version = null;

	/**
	 * Constructor
	 */
	function __construct() {

		!session_id() && session_start();


		$this->_plugin_path = LP_EXPORT_IMPORT_PATH;
		$this->_plugin_url  = untrailingslashit( plugins_url( '/', LP_EXPORT_IMPORT_FILE ) );

		// includes required files
		$this->_includes();
		$this->_init_hooks();
		$this->get_learnpress_version();

	}

	private function _init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'get_learnpress_version' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		add_action( 'all_admin_notices', array( $this, 'import_upload_form' ) );
		add_action( 'load-edit.php', array( $this, 'do_bulk_actions' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'course_bulk_actions' ), 2 );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_filter( 'learn_press_row_action_links', array( $this, 'course_row_actions' ) );
		add_action( 'admin_init', array( $this, 'do_export' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'js_settings' ) );
		add_action( 'admin_init', array( $this, 'do_action' ) );

		add_filter( 'lpie_export_provider_class', array( $this, 'export_class' ), 5, 2 );

		do_action( 'learn_press_import_export_init_hooks', $this );
	}

	function do_action() {
		$this->_do_action();

	}

	function js_settings() {
		?>
		<script type="text/javascript">
			var LearnPress_Import_Export_Settings = {
				ajax: '<?php echo admin_url( 'admin-ajax.php' );?>'
			}
		</script>
		<?php
	}

	function get_learnpress_version( $ver = 'core' ) {
		if ( !defined( 'LPIE_PLUGIN_VERSION' ) ) {

			if ( !function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			/*
			 * Delete cache so hook for extra plugin headers works
			 * Do not know why it worked before but now is not
			 */
			wp_cache_delete( 'plugins', 'plugins' );

			$all_plugins = get_plugins();
			if ( !empty( $all_plugins['learnpress/learnpress.php'] ) ) {
				define( 'LPIE_PLUGIN_VERSION', $all_plugins['learnpress/learnpress.php']['Version'] );
				define( 'LPIE_PLUGIN_DB_VERSION', defined( 'LEARNPRESS_DB_VERSION' ) ? LEARNPRESS_DB_VERSION : null );
			}
		}
		return $ver == 'core' ? $this->learnpress_version : ( $ver == 'db' ? $this->learnpress_db_version : array( 'db' => $this->learnpress_db_version, 'core' => $this->learnpress_version ) );
	}

	private function _do_action() {
		// hide notice import sample data if we are in import/export screen
		if ( !empty( $_REQUEST['page'] ) && 'learnpress-import-export' == $_REQUEST['page'] ) {
			remove_action( 'admin_notices', 'learn_press_one_click_install_sample_data_notice' );
		}
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
			return;
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
			return;
		}

		// download file
		if ( !empty( $_REQUEST['download-export'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-download-export-file' ) ) {
			$file = learn_press_get_request( 'download-export' );
			lpie_export_header( $file );
			echo lpie_get_contents( 'learnpress/export/' . $file );
			die();
		}
		if ( !empty( $_REQUEST['download-import'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-download-import-file' ) ) {
			$file = learn_press_get_request( 'download-import' );
			lpie_export_header( $file );
			echo lpie_get_contents( 'learnpress/import/' . $file );
			die();
		}

		// export
		if ( !empty( $_REQUEST['export_nonce'] ) && wp_verify_nonce( $_REQUEST['export_nonce'], 'learnpress-import-export-export' ) ) {
			if ( empty( $_REQUEST['exporter'] ) ) {
				die( __( '<p>Please select a source to export</p>', 'learnpress-import-export' ) );
			} else {
				if ( !is_object( $provider = lpie_get_exporter( $_REQUEST['exporter'] ) ) ) {
					die( sprintf( __( '<p>Provider %s does not exists, please select another one</p>', 'learnpress-import-export' ), $provider ) );
				} else {
					add_action( 'wp_ajax_learn_press_export', array( $provider, 'do_export' ) );
				}
			}
			return;
		}

		// import from upload
		if ( !empty( $_REQUEST['import_nonce'] ) && wp_verify_nonce( $_REQUEST['import_nonce'], 'learnpress-import-export-import' ) ) {
			include_once LP_EXPORT_IMPORT_PATH . '/incs/lp-import-functions.php';
			/**if( empty( $_REQUEST['exporter'] ) ) {
			 * die( __( '<p>Please select a source to export</p>', 'learnpress-import-export' ) );
			 * }else{
			 * if( !is_object( $provider = lpie_get_exporter( $_REQUEST['exporter'] ) ) ){
			 * die( sprintf( __( '<p>Provider %s does not exists, please select another one</p>', 'learnpress-import-export' ), $provider ) );
			 * }else{
			 * add_action( 'wp_ajax_learn_press_export', array( $provider, 'do_export' ) );
			 * }
			 * }*/
			$file = lp_import_handle_upload( $_FILES['import'], array( 'mimes' => array( 'xml' => 'text/xml' ) ) );
			if ( !empty( $file['file'] ) && file_exists( $file['file'] ) ) {
				$file_name = basename( $file['file'] );
				$redirect  = admin_url( 'admin.php?page=learnpress-import-export&tab=import-course&import-file=import/' . $file_name . '&nonce=' . wp_create_nonce( 'lpie-import-file' ) );
				wp_redirect( $redirect );
				die();

				exit();
			}
			return;
		}

		// import from file on server

		if ( !empty( $_REQUEST['import-file'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'lpie-import-file' ) ) {

			include_once LP_EXPORT_IMPORT_PATH . '/incs/lp-import-functions.php';
			$file        = learn_press_get_request( 'import-file' );
			$source_file = lpie_root_path() . '/learnpress/' . $file;
			if ( $exporter = $this->get_exporter_from_file( $source_file ) ) {
				add_action( 'wp_ajax_learn_press_import', array( $exporter, 'do_import' ) );
				//$exporter->do_import( $source_file );
			}
		}
	}

	function get_exporter_from_file( $file ) {
		$exporter = 'learnpress';
		if ( $fs = lpie_filesystem() ) {
			$content = $fs->get_contents( $file );
			if ( preg_match( '!<wp:plugin_name>(.*)<\/wp:plugin_name>!is', $content, $matches ) ) {
				$exporter = $matches[1];
			}
		}
		if ( !is_object( $exporter = lpie_get_exporter( $exporter ) ) ) {
			die( sprintf( __( '<p>Provider %s does not exists, please select another one</p>', 'learnpress-import-export' ), $exporter ) );
		}
		return $exporter;
	}

	function export_class( $class, $name ) {
		switch ( strtolower( $name ) ) {
			case 'learnpress':
				include $this->get_plugin_path( 'incs/export/providers/class-lp-export-learnpress.php' );
				$class = 'LPIE_Export_LearnPress';
				break;
		}
		return $class;
	}

	function do_export() {
		$lms = !empty( $_POST['lsm_export'] ) ? $_POST['lsm_export'] : false;
		if ( !$lms ) return;
		$export = LPR_Export::instance();
		foreach ( $lms as $l ) {
			$provider = LPR_Export::get_provider( $l );
			if ( $provider ) {
				$data = $provider->export_courses();
			}
		}
		die();
	}


	function get_exporter() {
		return $this->_export;
	}

	/**
	 * Includes required files
	 *
	 * @access private
	 */
	private function _includes() {
		require_once( $this->_plugin_path . '/incs/class-lpr-export-base.php' );
		require_once( $this->_plugin_path . '/incs/class-lpr-export.php' );
		require_once( $this->_plugin_path . '/incs/functions.php' );
	}

	function admin_menu() {
		add_submenu_page(
			'learn_press',
			__( 'Import/Export', 'learnpress-import-export' ),
			__( 'Import/Export', 'learnpress-import-export' ),
			'manage_options',
			'learnpress-import-export',
			array( $this, 'admin_page' )
		);
	}

	function admin_page() {
		require_once( LP_EXPORT_IMPORT_PATH . '/views/admin-page.php' );
	}

	function export() {
		require_once( LP_EXPORT_IMPORT_PATH . '/views/export-form.php' );
	}

	function do_bulk_actions() {
		///

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();
		if ( $action != 'export' ) {
			if ( !empty( $_REQUEST['export'] ) && $action = $_REQUEST['export'] ) {

			}
		}
		if ( $action == 'export' ) {

			switch ( $action ) {
				case 'export':
					$post_ids = isset( $_REQUEST['post'] ) ? (array) $_REQUEST['post'] : array();
					require_once( $this->_plugin_path . '/incs/lpr-export-functions.php' );
					require_once( $this->_plugin_path . '/incs/lpr-export.php' );
					die();
				//wp_redirect( admin_url('edit.php?post_type=lpr_course') );

			}
		} elseif ( isset( $_REQUEST['reset'] ) ) {

		} else {
			$import_file = isset( $_FILES['lpr_import'] ) ? $_FILES['lpr_import'] : false;

			if ( !$import_file ) return;
			$message = 0;
			require_once( $this->_plugin_path . '/incs/lpr-import-functions.php' );
			require_once( $this->_plugin_path . '/incs/lpr-import.php' );

			$lpr_import = new LPR_Import();
			$message    = $lpr_import->dispatch();
			if ( $message >= 1 ) {
				$duplication_ids = $lpr_import->get_duplication_course();
				$message .= '&post=' . join( ',', $duplication_ids );
				wp_redirect( admin_url( 'edit.php?post_type=lpr_course&course-imported=1&message=' . $message ) );
			} else {
				wp_redirect( admin_url( 'edit.php?post_type=lpr_course&course-imported=error&message=' . $message ) );
			}
			die();
		}


		//echo admin_url('edit.php?post_type=lpr_course');

	}

	function course_bulk_actions() {
		global $post_type;
		if ( 'lpr_course' == $post_type ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('<option>').val('export').text('<?php _e('Export Courses', 'learnpress-import-export' )?>').appendTo("select[name='action']");
					$('<option>').val('export').text('<?php _e('Export Courses', 'learnpress-import-export' )?>').appendTo("select[name='action2']");
				});
			</script>
			<?php
		}
	}

	function course_row_actions( $actions ) {
		global $post;
		if ( 'lpr_course' == $post->post_type ) {
			//$actions['lpr-export'] = sprintf('<a href="%s">%s</a>', admin_url('edit.php?post_type=lpr_course&action=export&post=' . $post->ID ) , __('Export Course') );
			$actions[] = array(
				'link'  => admin_url( 'edit.php?post_type=lpr_course&action=export&post=' . $post->ID ),
				'title' => __( 'Export this course', 'learnpress_import_export' ),
				'class' => 'lpr-export'
			);
		}
		return $actions;
	}

	function admin_scripts() {
		wp_enqueue_script( 'learn-press-export-script', $this->_plugin_url . '/assets/js/export.js', array( 'jquery', 'backbone', 'wp-util' ) );
		wp_enqueue_script( 'learn-press-import-script', $this->_plugin_url . '/assets/js/import.js', array( 'jquery', 'backbone', 'wp-util' ) );
	}

	function admin_styles() {
		global $pagenow, $post_type;
		//if ( 'lp_course' != $post_type || $pagenow != 'edit.php' ) return;
		wp_enqueue_style( 'learn-press-import-export-style', $this->_plugin_url . '/assets/css/export-import.css' );
	}

	function import_upload_form() {
		global $pagenow, $post_type;
		if ( 'lpr_course' != $post_type || $pagenow != 'edit.php' ) return;
		?>
		<div id="lpr-import-upload-form">
			<a href="" class="">&times;</a>

			<form method="post" enctype="multipart/form-data">
				<input type="file" name="lpr_import" />
				<br />
				<button class="button"><?php _e( 'Import', 'learnpress-import-export'  ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * displays the message in admin
	 */
	function admin_notice() {
		global $post_type;
		if ( 'lpr_course' != $post_type ) return;

		if ( empty( $_REQUEST['course-imported'] ) ) return;

		$message = isset( $_REQUEST['message'] ) ? intval( $_REQUEST['message'] ) : 0;
		if ( !$message ) {
			$type         = "error";
			$message_text = get_transient( 'lpr_import_error_message' );
			delete_transient( 'lpr_import_error_message' );
		} else {

			$type         = "";
			$message_text = null;
			switch ( $message ) {

				case 1: // import success with out any duplicate course
					$type         = "updated";
					$message_text = __( 'Imports all courses successfully', 'learnpress_import_export' );
					break;
				case 2: // import success with some of duplicate course
					$type         = "error";
					$message_text = __( 'Some courses are duplicate, please select it in the list to duplicate if you want', 'learnpress_import_export' );
					break;

				default: // no course imported
					$type         = "error";
					$message_text = __( 'No course is imported. Please try again', 'learnpress_import_export' );
					break;

			}
		}

		if ( !$type ) return;
		if ( !empty( $_REQUEST['post'] ) ) {
			$posts = get_posts( array( 'include' => $_REQUEST['post'], 'post_type' => 'lpr_course' ) );
			$message_text .= '<p>The following courses are duplicated:</p>';
			foreach ( $posts as $post ) {
				$message_text .= sprintf( '<p><a href="%s">%s</a></p>', get_edit_post_link( $post->ID ), $post->post_title );
			}
		}
		if ( empty( $message_text ) ) return;
		?>
		<div class="<?php echo $type; ?>">
			<p><?php echo $message_text; ?></p>
		</div>
		<?php
	}

	/**
	 * Get the url of this plugin
	 *
	 * @var     $sub    string  Optional - The sub-path to append to url of plugin
	 * @return  string
	 */
	function get_plugin_url( $sub = '' ) {
		return $this->_plugin_url . ( $sub ? '/' . $sub : '' );
	}

	/**
	 * Get the path of this plugin
	 *
	 * @var     $sub    string  Optional - The sub-path to append to path of plugin
	 * @return  string
	 */
	function get_plugin_path( $sub = '' ) {
		return $this->_plugin_path . ( $sub ? '/' . $sub : '' );
	}

	/**
	 * Get an instance of main class, create a new one if it's not loaded
	 *
	 * @return bool|LP_Import_Export
	 */
	static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Load text domain for import export addon
	 *
	 * @static
	 */
	static function load_text_domain() {
		$textdomain    = 'learnpress_import_export';
		$locale        = apply_filters( "plugin_locale", get_locale(), $textdomain );
		$lang_dir      = dirname( __FILE__ ) . '/lang/';
		$mofile        = sprintf( '%s.mo', $locale );
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		if ( file_exists( $mofile_global ) ) {
			load_textdomain( $textdomain, $mofile_global );
		} else {
			load_textdomain( $textdomain, $mofile_local );
		}
	}

	/**
	 * Init import export addon
	 *
	 * @static
	 */
	static function init() {
		//add_action( 'plugins_loaded', array( __CLASS__, 'load_text_domain' ) );
		//add_action( 'learn_press_loaded', array( __CLASS__, 'instance' ) );
		self::load_text_domain();
		self::instance();
	}
}