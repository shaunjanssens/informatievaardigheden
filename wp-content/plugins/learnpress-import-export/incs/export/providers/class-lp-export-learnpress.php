<?php

/**
 * Class LPIE_Export_LearnPress
 */
if ( !defined( 'LPIE_EXPORT_OPTIONS_SESSION_KEY' ) ) {
	define( 'LPIE_EXPORT_OPTIONS_SESSION_KEY', 'learn_press_export_options' );
}

/**
 * Class LPIE_Export_LearnPress
 */
class LPIE_Export_LearnPress {
	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var array
	 */
	protected $_exported_data = array();

	/**
	 * LPIE_Export_LearnPress constructor.
	 */
	function __construct() {
		// No add hooks anymore
		if ( did_action( 'lpie_export_learnpress_init' ) ) {
			return;
		}
		add_action( 'lpie_export_view_step_1', array( $this, 'export_step_1' ) );
		add_action( 'lpie_export_view_step_2', array( $this, 'export_step_2' ) );
		add_action( 'lpie_export_view_step_3', array( $this, 'export_step_3' ) );

		add_action( 'lpie_export_learnpress', array( $this, 'do_export' ) );
		add_action( 'lpie_do_export_item_meta', array( $this, 'do_export_item' ) );


		do_action( 'lpie_export_learnpress_init' );

	}

	/**
	 * Step 1 view
	 */
	function import_step_1() {
		include_once LP_IMPORT_EXPORT_PATH . 'views/LearnPress/import-step-1.php';
	}

	/**
	 * Step 2 view
	 */
	function import_step_2() {
		include_once LP_IMPORT_EXPORT_PATH . 'views/LearnPress/import-step-2.php';
	}

	function do_import() {
		global $wpdb;
		require_once LP_IMPORT_EXPORT_PATH . '/incs/class-lp-import.php';
		$file        = learn_press_get_request( 'import-file' );
		$source_file = lpie_root_path() . '/learnpress/' . $file;
		$import      = new LP_Import( $source_file );
		// start a transaction so we can rollback all data if an error trigger
		$wpdb->query( "START TRANSACTION;" );
		try {
		} catch ( Exception $ex ) {
			// rollback
			$wpdb->query( "ROLLBACK;" );
			wp_die( $ex->getMessage() );
		}
		echo "DONE";
		// Wow, commit all
		$wpdb->query( "COMMIT;" );
	}

	function do_export( $step ) {
		if ( $step != 3 ) {
			return;
		}
		global $wpdb, $post;

		require_once dirname( dirname( __FILE__ ) ) . "/lp-export-functions.php";

		$all_courses          = $_REQUEST['courses'];
		$this->_exported_data = array_merge( $_REQUEST, $this->_exported_data );
		$course_post_type     = defined( 'LEARNPRESS_VERSION' ) && version_compare( LEARNPRESS_VERSION, '2.0', '>' ) ? 'lp_course' : 'lpr_course';

		$courses = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT *
				FROM {$wpdb->posts}
				WHERE ID IN(" . join( ",", $all_courses ) . ")
				AND post_type = %s
			", $course_post_type )
		);
		ob_start();

		$export_options = $this->_exported_data;
		foreach ( $courses as $post ) {
			setup_postdata( $post );
			require LP_IMPORT_EXPORT_PATH . "/incs/export/lp-export-item" . ( $course_post_type == 'lp_course' ? '' : '-backward' ) . '.php';

			/*
			 * Import course's items
			 */
			$course_items = $wpdb->get_results(
				$wpdb->prepare( "
					SELECT c.ID, p.*
					FROM {$wpdb->prefix}learnpress_sections s
					INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.section_id = s.section_id
					INNER JOIN {$wpdb->prefix}posts p ON si.item_id = p.ID
					INNER JOIN {$wpdb->prefix}posts c ON c.ID = s.section_course_id
					WHERE c.ID = %d
				", $post->ID )
			);
			if ( $course_items ) {
				foreach ( $course_items as $item ) {
					$this->_get_item( $item, $course_post_type );
				}
			}
		}
		$html_courses = ob_get_clean();
		$this->generate_exported_file( $html_courses, $course_post_type );
	}

	/**
	 * @param WP_Post $item
	 */
	public function do_export_item( $item ) {
		$export_options = $this->_exported_data;
		global $post;
		$old_post = $post;
		$post     = $item;
		setup_postdata( $post );
		if ( $item->post_type == 'lp_course' ) {
			require LP_IMPORT_EXPORT_PATH . '/incs/export/providers/LearnPress/lp-export-item-lp_course.php';
		} elseif ( $item->post_type == 'lp_lesson' ) {
			require LP_IMPORT_EXPORT_PATH . '/incs/export/providers/LearnPress/lp-export-item-lp_question.php';
		} elseif ( $item->post_type == 'lp_quiz' ) {
			require LP_IMPORT_EXPORT_PATH . '/incs/export/providers/LearnPress/lp-export-item-lp_quiz.php';
		}
		$post = $old_post;
		setup_postdata( $post );
	}

	private function _get_item( $item, $course_post_type ) {
		global $wpdb, $post;
		$old_post = $post;
		$post     = $item;
		setup_postdata( $post );

		require LP_IMPORT_EXPORT_PATH . "/incs/export/lp-export-item" . ( $course_post_type == 'lp_course' ? '' : '-backward' ) . '.php';

		if ( $post->post_type == 'lp_quiz' ) {
			$query     = $wpdb->prepare( "
				SELECT q.*, qq.*
				FROM {$wpdb->posts} q
				INNER JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON q.ID = qq.question_id
				WHERE quiz_id = %d
			", $post->ID );
			$questions = $wpdb->get_results( $query );
			if ( $questions ) foreach ( $questions as $question ) {
				$this->_get_item( $question, $course_post_type );
			}
		}

		$post = $old_post;
		setup_postdata( $post );
	}

	function get_export_file_name( $name = '', $type = 'xml' ) {
		$file_name = !empty( $name ) ? $name : 'export-courses-learnpress-' . date( 'Ymdhis' );
		$segs      = explode( '.', $file_name );
		$ext       = '';
		if ( sizeof( $segs ) > 1 ) {
			$ext = end( $segs );
		}
		if ( $ext != $type ) {
			$file_name .= ".{$type}";
		}
		return sanitize_file_name( $file_name );
	}

	public function get_export_file_name_without_ext( $name = '' ) {
		$name = $this->get_export_file_name( $name );
		$segs = explode( '.', $name );
		array_pop( $segs );
		return join( '/', $segs );
	}

	function get_download_export_file_name( $type = 'xml' ) {
		$file_name = md5( date( 'Ymdhis' ) );
		$segs      = explode( '.', $file_name );
		$ext       = '';
		if ( sizeof( $segs ) > 1 ) {
			$ext = end( $segs );
		}
		if ( $ext != $type ) {
			$file_name .= ".{$type}";
		}
		return sanitize_file_name( $file_name );
	}

	function generate_exported_file( $html_courses, $course_post_type ) {

		$export_options = $this->_exported_data;
		ob_start();
		require_once LP_IMPORT_EXPORT_PATH . "/incs/export/lp-export" . ( $course_post_type == 'lp_course' ? "" : '-backward' ) . '.php';
		$content      = ob_get_clean();
		$xml_filename = $this->get_export_file_name( $this->_exported_data['learn-press-export-file-name'] );
		if ( $this->_exported_data['save_export'] ) {
			$xml_filename = 'learnpress/export/' . $xml_filename;
			lpie_put_contents( $xml_filename, $content );
		}
		if ( $this->_exported_data['download_export'] ) {
			$download_filename = $this->get_download_export_file_name( $this->_exported_data['learn-press-export-file-name'] );
			$download_filename = 'learnpress/tmp/' . $download_filename;
			lpie_put_contents( $download_filename, $content );
			/*header( 'Content-disposition: attachment; filename="' . $xml_filename . '"' );
			$content_type = 'text/xml';
			header( 'Content-type: "' . $content_type . '"; charset="utf8"' );
			echo $content;
			exit();*/
			$this->_exported_data['download_url']   = $download_filename;
			$this->_exported_data['download_alias'] = basename( $xml_filename );
		}
	}


	function export_step_1() {
		include_once LP_IMPORT_EXPORT_PATH . '/views/LearnPress/export-step-1.php';
	}

	function export_step_2() {
		include_once LP_IMPORT_EXPORT_PATH . '/views/LearnPress/export-step-2.php';
	}

	function export_step_3() {
		include_once LP_IMPORT_EXPORT_PATH . '/views/LearnPress/export-step-3.php';
	}

	function get_courses() {
		global $wpdb;
		$user         = wp_get_current_user();
		$roles        = $user->roles;
		$teacher_role = 'lp_teacher';
		$post_type    = 'lp_course';
		$query        = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_type = %s
		", $post_type );
		$is_teacher   = false;
		if ( in_array( 'administrator', $roles ) || ( $is_teacher = in_array( $teacher_role, $roles ) ) ) {
			if ( $is_teacher ) {
				$query .= $wpdb->prepare( " AND post_author = %d", $user->ID );
			}
		}
		return $wpdb->get_results( $query );
	}

	public
	static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

return LPIE_Export_LearnPress::instance();