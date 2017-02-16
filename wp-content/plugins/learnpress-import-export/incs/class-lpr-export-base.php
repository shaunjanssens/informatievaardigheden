<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Class LPR_Export_Base
 */
abstract class LPR_Export_Base{
    /**
     * Constructor
     */
    function __construct(){

    }

    /**
     * Export data
     * Require to define in extended class
     *
     * @return mixed
     */
    abstract function do_export();

    /**
     * Export and return the course data
     *
     * @return mixed
     */
    abstract function export_courses();
}