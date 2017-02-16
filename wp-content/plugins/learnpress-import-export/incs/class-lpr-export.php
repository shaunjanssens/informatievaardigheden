<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Class LPR_Export
 */
class LPR_Export{

    /**
     * @var array
     */
    protected $_plugins = null;

    /**
     * @var array
     */
    protected $_third_parties    =  array(
        'courseware'    => array(
            'plugin_file' => 'wp-courseware/wp-courseware.php'
        ),
        'namaste'       => array(
            'plugin_file' => 'namaste-lms/namaste.php'
        ),
        'educator'       => array(
            'plugin_file' => 'ibeducator/ibeducator.php'
        ),
        'coursepress'       => array(
            'plugin_file' => 'coursepress/coursepress.php'
        )
    );

    /**
     * @var object
     */
    public static $_instance = false;

    /**
     * Constructor
     *
     * @param array
     */
    function __construct( ){
    }

    /**
     * Get the plugins that installed and activated from predefined third party list
     *
     * @return array|void
     */
    function get_enable_plugins(){
        if( ! $this->_third_parties ) return;
        if( ! $this->_plugins ) {
            if (!function_exists('is_plugin_activate')) {
                require_once(ABSPATH . '/wp-includes/plugin.php');
            }
            $active = array();
            $inactive = array();
            foreach ($this->_third_parties as $class_name => $options) {
                $plugin_file = $options['plugin_file'];
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
                if( file_exists( $plugin_path ) ) {
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
                    $plugin_data['slug'] = $class_name;
                    if (is_plugin_active($plugin_file)) {
                        $active[ $plugin_file ] = $plugin_data;
                        $active[ $plugin_file ]['status'] = 'activated';
                    }else{
                        $inactive[ $plugin_file ] = $plugin_data;
                        $inactive[ $plugin_file ]['status'] = 'inactive';
                    }

                }
            }
            $this->_plugins = array_merge( $active, $inactive );
        }
        return $this->_plugins;
    }

    /**
     * Get instance object of third-party provider
     *
     * @param string
     * @return object
     */
    static function get_provider( $slug ){
        $class = 'LPR_Export_' . ucfirst( $slug );
        $provider = false;
        // find the location of class to include
        if( ! class_exists( $class ) ) {
            $file_class = LPR_EXPORT_IMPORT_PATH . '/incs/export/class-lpr-export-' . $slug . '.php';
            if (file_exists($file_class)) {
                require_once($file_class);
            }
        }
        if( class_exists( $class ) ){
            $provider = new $class();
        }

        return $provider;
    }

    static function instance(){
        if( ! self::$_instance ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
