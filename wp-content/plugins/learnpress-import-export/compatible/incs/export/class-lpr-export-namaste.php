<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class LPR_Export_Namaste
 *
 * @extend LPR_Export_Base
 */
class LPR_Export_Namaste extends LPR_Export_Base{
    function do_export(){

    }

    function export_courses(){
        return '12212';
    }
}