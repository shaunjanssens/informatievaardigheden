<?php
/*
Plugin Name: LearnPress Question Fill In Blank
Plugin URI: http://thimpress.com/learnpress
Description: Supports type of question Fill In Blank lets user fill out the text into one ( or more than one ) space
Author: ThimPress
Version: 2.0
Author URI: http://thimpress.com
Tags: learnpress
Text Domain: learnpress-fill-in-blank
Domain Path: /languages/
*/
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !defined( 'LP_QUESTION_FILL_IN_BLANK_PATH' ) ) {
	define( 'LP_QUESTION_FILL_IN_BLANK_FILE', __FILE__ );
	define( 'LP_QUESTION_FILL_IN_BLANK_PATH', dirname( __FILE__ ) );
	define( 'LP_QUESTION_FILL_IN_BLANK_VER','2.0' );
	define( 'LP_QUESTION_FILL_IN_BLANK_REQUIRE_VER', '2.0');
}

/**
 * Class LP_Addon_Question_Fill_In_Blank
 */
class LP_Addon_Question_Fill_In_Blank {

	/**
	 * Initialize
	 */
	static function init() {
		add_action('learn_press_ready', array(__CLASS__, 'check_version'));
	}

	public static function check_version(){
		if ( !defined( 'LEARNPRESS_VERSION' ) || ( version_compare( LEARNPRESS_VERSION, LP_QUESTION_FILL_IN_BLANK_REQUIRE_VER, '<' ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			return false;
		}
		add_action( 'learn_press_loaded', array( __CLASS__, 'ready' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_text_domain' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'learn_press_save_default_question_types', array( __CLASS__, 'default_types' ) );
		add_filter( 'learn_press_question_meta_box_args', array( __CLASS__, 'admin_options' ) );
		add_filter( 'learn_press_question_types', array( __CLASS__, 'register_question' ) );
	}

	public static function admin_notice() {
		?>
		<div class="error">
			<p><?php printf( __( '<strong>Fill In Blank</strong> addon version %s requires LearnPress version %s or higher', 'learnpress-fill-in-blank' ), LP_QUESTION_FILL_IN_BLANK_VER, LP_QUESTION_FILL_IN_BLANK_REQUIRE_VER ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add new options to question
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	static function admin_options( $args ) {
		$post_id = !empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : 0;
		if ( get_post_meta( $post_id, '_lp_type', true ) == 'fill_in_blank' ) {
			$args['fields'][] = array(
				'name'    => __( 'Mark result', 'learnpress-fill-in-blank' ),
				'id'      => "_lpr_fill_in_blank_mark_result",
				'type'    => 'radio',
				'desc'    => __( 'Mark result for this question', 'learnpress-fill-in-blank' ),
				'options' => array(
					'correct_all'    => __( 'Requires correct all blanks', 'learnpress-fill-in-blank' ),
					'correct_blanks' => __( 'Mark is calculated by total of correct blanks', 'learnpress-fill-in-blank' )
				),
				'std'     => 'correct_all',
				'class'   => 'fill-in-blank-meta'
			);
		}
		return $args;
	}

	static function default_types( $types ) {
		$types[] = 'fill_in_blank';
		return $types;
	}

	/**
	 * @return mixed|void
	 */
	static function admin_js_template() {
		ob_start();
		?>
		<tr class="lp-list-option lp-list-option-new lp-list-option-empty <# if(data.value){ #>lp-list-option-{{data.value}}<# } #>" data-id="{{data.value}}">
			<td>
				<input class="lp-answer-text no-submit key-nav" type="text" name="learn_press_question[{{data.question_id}}][answer][text][]" value="{{data.text}}" />
				<input type="hidden" name="learn_press_question[{{data.question_id}}][answer][value][]" value="{{data.value}}" />
			</td>
			<td class="display-position display-position-{{data.question_id}} display-position-{{data.value}}">
						<span class="lp-question-sorting-choice-display-position lp-question-sorting-choice-display-position-{{data.question_id}} lp-question-sorting-choice-display-position-{{data.value}}">
							<input type="hidden" name="learn_press_question[{{data.question_id}}][answer][position][]" value="{{data.value}}" />
							<span>{{data.text}}</span>
						</span>
			</td>
			<td class="lp-list-option-actions lp-remove-list-option">
				<i class="dashicons dashicons-trash"></i>
			</td>
			<td class="lp-list-option-actions lp-move-list-option open-hand">
				<i class="dashicons dashicons-sort"></i>
			</td>
		</tr>
		<?php
		return apply_filters( 'learn_press_question_sorting_choice_answer_option_template', ob_get_clean(), __CLASS__ );
	}

	/**
	 * Enqueues assets
	 */
	static function enqueue_assets() {
		wp_enqueue_script( 'question-fill-in-blank-js', plugins_url( '/', LP_QUESTION_FILL_IN_BLANK_FILE ) . 'assets/script.js', array( 'jquery' ) );
		wp_enqueue_style( 'question-fill-in-blank-css', plugins_url( '/', LP_QUESTION_FILL_IN_BLANK_FILE ) . 'assets/style.css' );
	}

	/**
	 *
	 */
	static function ready() {
		require_once LP_QUESTION_FILL_IN_BLANK_PATH . '/inc/class-lp-question-fill-in-blank.php';
	}

	/**
	 * Register Fill In Blank question type
	 *
	 * @param $types
	 *
	 * @return mixed
	 */
	static function register_question( $types ) {
		$types['fill_in_blank'] = __( 'Fill In Blank', 'learnpress-fill-in-blank' );
		return $types;
	}

	/**
	 * Textdomain
	 */
	static function load_text_domain() {
		if ( function_exists( 'learn_press_load_plugin_text_domain' ) ) {
			learn_press_load_plugin_text_domain( LP_QUESTION_FILL_IN_BLANK_PATH );
		}
	}

	/**
	 * @param      $name
	 * @param null $args
	 */
	static function get_template( $name, $args = null ) {
		learn_press_get_template( $name, $args, get_template_directory() . '/addons/fill-in-blank/', LP_QUESTION_FILL_IN_BLANK_PATH . '/templates/' );
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	static function locate_template( $name ) {
		return learn_press_locate_template( $name, get_template_directory() . '/addons/fill-in-blank/', LP_QUESTION_FILL_IN_BLANK_PATH . '/templates/' );
	}
}

// Sparky, run now!
LP_Addon_Question_Fill_In_Blank::init();
