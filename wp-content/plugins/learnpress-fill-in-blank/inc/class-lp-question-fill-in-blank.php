<?php
if ( class_exists( 'LP_Abstract_Question' ) ) {
	/**
	 * Class LP_Question_Sorting_Choice
	 *
	 * @extend LP_Question_Abstract
	 */
	class LP_Question_Fill_In_Blank extends LP_Abstract_Question {

		/**
		 * @var string
		 */
		protected $_shortcode_pattern = '!\[fib(.*)fill=["|\'](.*)["|\']!iSU';

		/**
		 * LP_Question_Fill_In_Blank constructor.
		 *
		 * @param null $the_question
		 * @param null $args
		 */
		function __construct( $the_question, $args ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );
			add_shortcode( 'fib', array( $this, 'shortcode' ) );

			parent::__construct( $the_question, $args );
			$this->checked = $this->get_answers();
			add_action( 'learn_press_save_user_question_answer', array( $this, '_get_checked' ), 5, 5 );
			add_filter( 'learn_press_check_question_answers', array( $this, '_check_answer' ), 5, 4 );

			learn_press_add_question_type_support( $this->type, array( 'check-answer', 'hint' ) );
		}

		/**
		 * @param $question_answer
		 * @param $save_id
		 * @param $quiz_id
		 * @param $user_id
		 * @param $a
		 */
		function _get_checked( $question_answer, $save_id, $quiz_id, $user_id, $a ) {
			$this->user_answered = $question_answer;
		}

		/**
		 * @param $checked
		 * @param $question_id
		 * @param $quiz_id
		 * @param $user_id
		 *
		 * @return string
		 */
		function _check_answer( $checked, $question_id, $quiz_id, $user_id ) {
			//
			if ( $question_id == $this->id ) {
				settype( $checked, 'array' );
				$content = reset( $checked );
				$content = stripcslashes( current( $content ) );

				$pattern = $this->_shortcode_pattern;

				$content = preg_replace_callback( $pattern, array( $this, '_replace_callback' ), $content );

				$checked = '<div class="question-passage">' . do_shortcode( $content ) . '</div>';
			}
			return $checked;
		}

		/**
		 * @param $a
		 *
		 * @return string
		 */
		function _replace_callback( $a ) {
			$user_fill = '';

			if ( !empty( $this->user_answered ) ) {
				settype( $this->user_answered, 'array' );
				$input_name = $this->get_input_name( $a[2] );
				if ( !empty( $this->user_answered[$input_name] ) ) {
					settype( $this->user_answered[$input_name], 'array' );
					$user_fill = array_shift( $this->user_answered[$input_name] );
				}
			}
			return $a[0] . ' correct_fill="' . $a[2] . '" user_fill="' . $user_fill . '"';
		}

		/**
		 * @param $fill
		 *
		 * @return string
		 */
		function get_input_name( $fill ) {
			return '_' . md5( wp_create_nonce( $fill ) );
		}

		private function _format_text( $text ) {
			return trim( preg_replace( '!\s+!', ' ', $text ) );
		}

		/**
		 * @param null $atts
		 *
		 * @return string
		 */
		function shortcode( $atts = null ) {
			$atts         = shortcode_atts(
				array(
					'fill'         => '',
					'user_fill'    => '',
					'correct_fill' => ''
				), $atts
			);
			$input_name   = $this->get_input_name( $atts['fill'] );
			$correct_fill = $this->_format_text( $atts['correct_fill'] );
			$user_fill    = $this->_format_text( $atts['user_fill'] );
			$correct      = strcasecmp( $user_fill, $correct_fill ) == 0;
			return sprintf(
				'<input type="text" name="%s" data-fill="%s" value="%s" %s class="%s" />%s',
				'learn-press-question-' . $this->id . '[' . $input_name . '][]',
				esc_attr( $input_name ),
				$correct_fill ? $correct_fill : '',
				$correct_fill ? ' disabled="disabled"' : '',
				$correct ? 'blank-fill-correct' : '',
				$correct_fill ? sprintf( '<span class="check-label %s">%s</span>', $correct ? 'correct' : 'wrong', $user_fill ) : ''
			);
		}

		/**
		 * Assets
		 */
		function load_assets() {
			wp_enqueue_style( 'fill-in-blank', plugins_url( 'assets/style.css', LP_QUESTION_FILL_IN_BLANK_FILE ) );
		}

		/**
		 * Magic __get helper
		 *
		 * @param $key
		 *
		 * @return mixed|null|string
		 */
		function __get( $key ) {
			if ( $key == 'passage' || $key == 'passage_checked' ) {
				$answers = (array) $this->get_answers();
				$passage = reset( $answers );
				$passage = !empty( $passage[0] ) ? stripcslashes( $passage[0] ) : null;
				if ( $key == 'passage_checked' ) {
					if ( $passage ) {
						$pattern = $this->_shortcode_pattern;
						$passage = preg_replace_callback( $pattern, array( $this, '_replace_callback' ), $passage );
					}
				}

				return $passage;
			}
			return parent::__get( $key );
		}

		/**
		 * Admin interface
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		function admin_interface( $args = array() ) {
			ob_start();

			$view = learn_press_get_admin_view( 'admin-fill-in-blank-options', LP_QUESTION_FILL_IN_BLANK_FILE );
			include $view;
			$output = ob_get_clean();

			if ( !isset( $args['echo'] ) || ( isset( $args['echo'] ) && $args['echo'] === true ) ) {
				echo $output;
			}
			return $output;
		}

		/**
		 * Question content
		 *
		 * @param null $args
		 */
		function render( $args = null ) {

			$args     = wp_parse_args(
				$args,
				array(
					'answered'   => null,
					'history_id' => 0,
					'quiz_id'    => 0,
					'course_id'  => 0
				)
			);
			$answered = !empty( $args['answered'] ) ? $args['answered'] : null;
			if ( null === $answered ) {
				$answered = $this->get_user_answered( $args );
			}
			$view = LP_Addon_Question_Fill_In_Blank::locate_template( 'answer-options.php' );
			include $view;

		}

		/**
		 * Check result of question
		 *
		 * @param null $args
		 *
		 * @return mixed
		 */
		function check( $args = null ) {
			$key           = wp_create_nonce( maybe_serialize( $args ) );
			$check_results = $this->check_results;
			if ( empty( $check_results ) ) {
				$check_results = array();
			}
			if ( empty( $check_results[$key] ) ) {

				$return = array(
					'correct' => true
				);

				$passage = $this->passage;
				if ( preg_match_all( $this->_shortcode_pattern, $passage, $matches ) ) {
					settype( $args, 'array' );
					$input_pos = array();
					foreach ( $matches[0] as $k => $v ) {
						$input_name = $this->get_input_name( $matches[2][$k] );
						$user_fill  = '';
						if ( !empty( $args[$input_name] ) ) {
							$pos = !empty( $input_pos[$input_name] ) ? $input_pos[$input_name] : 1;
							if ( !empty( $args[$input_name][$pos - 1] ) ) {
								$user_fill = $args[$input_name][$pos - 1];
							}
							$input_pos[$input_name] = $pos + 1;
						}
						$user_fill    = $this->_format_text( $user_fill );
						$correct_fill = $this->_format_text( $matches[2][$k] );
						if ( strcasecmp( $user_fill, $correct_fill ) != 0 ) {
							$return['correct'] = false;
						}
					}
					$return['mark'] = $return['correct'] ? $this->mark : 0;
				}
				$check_results[$key] = $return;
				$this->check_results = $check_results;
			}
			return $check_results[$key];
		}

		/**
		 * @param null $post_data
		 */
		function save( $post_data = null ) {

			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'learnpress_question_answers', array( 'question_id' => $this->id ), array( '%d' ) );
			$wpdb->insert(
				$wpdb->prefix . 'learnpress_question_answers',
				array(
					'question_id'  => $this->id,
					'answer_data'  => maybe_serialize( $post_data ),
					'answer_order' => 1
				),
				array( '%d', '%s', '%d' )
			);

		}
	}
}
