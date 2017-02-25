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
<div <?php learn_press_question_class( $this, $args ); ?> data-id="<?php echo $this->id; ?>" data-type="fill-in-blank">

	<?php do_action( 'learn_press_before_question_wrap', $this ); ?>

	<h4 class="learn-press-question-title"><?php echo get_the_title( $this->id ); ?></h4>

	<?php do_action( 'learn_press_before_question_options', $this ); ?>



	<?php do_action( 'learn_press_after_question_wrap', $this, $quiz ); ?>

</div>