<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class LPR_Export_Courseware
 *
 * @extend LPR_Export_Base
 */
class LPR_Export_Courseware extends LPR_Export_Base{
    function do_export(){

    }

    function export_courses(){
        global $wpdb;
        $authors = array();
        $courses = array();
        $lessons = array();
        $quizzes = array();
        $sections = array();

        // get courses
        $query = "
            SELECT course_id as ID, course_title as post_title, course_desc as post_content
            FROM {$wpdb->prefix}wpcw_courses
        ";
        $results = $wpdb->get_results( $query );
        if( $results ){
            foreach( $results as $course ) {
                $course->meta = array();
                // get modules
                $query = $wpdb->prepare("
                    SELECT *
                    FROM {$wpdb->prefix}wpcw_modules
                    WHERE parent_course_id=%d
                ", $course->ID );

                // get all modules as our sections
                if ($modules = $wpdb->get_results($query)) {
                    foreach ($modules as $mod) {
                        $section = array(
                            'name' => $mod->module_title,
                            'lesson_quiz' => array()
                        );

                        // get all units as our lessons
                        $units = get_posts(
                            array(
                                'post_type' => 'course_unit',
                                'meta_query' => array(
                                    array(
                                        'key' => 'wpcw_associated_module',
                                        'value' => $mod->module_id
                                    )
                                ),
                                'posts_per_page' => -1
                            )
                        );
                        if ($units) foreach ($units as $unit) {
                            $section['lesson_quiz'][] = $unit->ID;
                            $lessons[] = $unit;
                            $query = $wpdb->prepare("
                                SELECT *
                                FROM {$wpdb->prefix}wpcw_quizzes
                                WHERE parent_unit_id = %d
                            ", $unit->ID );
                            if ( $_quizzes = $wpdb->get_results( $query ) ) {
                                foreach ( $_quizzes as $quiz ) {
                                    $section['lesson_quiz'][] = $quiz->quiz_id;
                                    $quizzes[] = array(
                                        'ID'            => $quiz->quiz_id,
                                        'post_title'    => $quiz->quiz_title,
                                        'post_content'  => $quiz->quiz_desc,
                                    );
                                }
                            }
                        }
                        $sections[] = $section;
                    }
                }
                $course->meta['_lpr_course_lesson_quiz'] = ( $sections );

                $courses[] = $course;
            }
        }

        return compact( 'authors', 'courses', 'lessons', 'quizzes' );
    }

}