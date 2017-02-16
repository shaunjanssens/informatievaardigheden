<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class LPR_Export_Courseware
 *
 * @extend LPR_Export_Base
 */
class LPR_Export_Coursepress extends LPR_Export_Base{
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
        $_courses = get_posts(
            array(
                'post_type' => 'course',
                'posts_per_page' => -1
            )
        );

        if( $_courses ){
            foreach( $_courses as $course ) {
                $course->meta = array();
                $sections = array();
                $_units = get_posts(
                    array(
                        'post_type' => 'unit',
                        'post_parent' => $course->ID,
                        'posts_per_page' => -1,
                        'order' => 'ASC',
                        'meta_key' => 'unit_order',
                        'orderby'   => 'meta_value_num',
                    )
                );
                if( $_units ) foreach( $_units as $unit ){
                    $section = array(
                        'name'  => $unit->post_title,
                        'lesson_quiz' => array()
                    );
                    $page_count = get_post_meta( $unit->ID, 'unit_page_count', true );

                    $page_count = max( $page_count, 1 );

                    for( $page_num = 1; $page_num <= $page_count; $page_num++ ) {
                        $args = array(
                            'post_type'      => 'module',
                            'post_status'    => 'any',
                            'posts_per_page' => - 1,
                            'post_parent'    => $unit->ID,
                            'meta_query'     => array(
                                array(
                                    'key'   => 'module_page',
                                    'value' => $page_num,
                                )
                            ),
                            //'meta_key'		 => 'module_page',
                            //'meta_value'	 => $unit_page,
                            'meta_key'       => 'module_order',
                            'orderby'        => 'meta_value_num',
                            'order'          => 'ASC',
                        );
//                    $args = array(
//                        'post_type' => 'module',
//                        'post_parent' => $unit->ID,
//                        'posts_per_page' => -1,
//                        'meta_key' => 'module_order',
//                        'order_by' => 'meta_value_num'
//                    );
                        $modules = get_posts(
                            $args
                        );

                        if ($modules) foreach ($modules as $module) {
                            $mod_type = get_post_meta($module->ID, 'module_type', true);
                            $mod_page = get_post_meta($module->ID, 'module_page', true);
                            //if( $mod_page != $page_num  ) continue;
                            switch ($mod_type) {
                                case 'text_input_module':
                                case 'textarea_input_module':
                                    break;
                                case 'checkbox_input_module':
                                    break;
                                case 'radio_input_module':
                                    break;
                                case 'section_break_module':
                                    break;
                                default:
                                    $section['lesson_quiz'][] = $module->ID;
                                    $lessons[] = $module;
                            }
                        }
                    }
                    $sections[] = $section;
                }
                $course->meta['_lpr_course_lesson_quiz'] = $sections;
                $courses[] = $course;
            }
        }

        return compact( 'authors', 'courses', 'lessons', 'quizzes' );
    }

}