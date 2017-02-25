<?php
/**
 * Template for displaying the content of multi-choice question
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz = LP()->global['course-item'];
$user = learn_press_get_current_user();

$completed   = $user->get_quiz_status( $quiz->id ) == 'completed';
$show_result = $quiz->show_result == 'yes';
$checked     = $user->has_checked_answer( $this->id, $quiz->id );

$args = array();
if ( $checked || ( $show_result && $completed ) ) {
	$args['classes'] = 'checked';
}

if ( $checked || ( $show_result && $completed ) ) {
	if ( empty( $this->user_answered ) ) {
		$result              = $user->get_quiz_results( $quiz->id );
		$this->user_answered = !empty( $result->question_answers[$this->id] ) ? $result->question_answers[$this->id] : array();
	}
	$passage = $this->passage_checked;
} else {
	$passage = $this->passage;
}

?>

<div class="question-passage learn-press-question-options" data-type="fill-in-blank">
	<?php echo nl2br( do_shortcode( $passage ) ); ?>
</div>