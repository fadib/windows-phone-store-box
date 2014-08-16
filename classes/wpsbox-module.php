<?php

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

if ( !function_exists('get_called_class') ) {
    function get_called_class() {
        $bt = debug_backtrace();
        $lines = file( $bt[1]['file'] );
        preg_match(
            '/([a-zA-Z0-9\_]+)::' . $bt[1]['function'] . '/',
            $lines[ $bt[1]['line'] - 1 ],
            $matches
        );
        return $matches[1];
    }
}

if ( ! class_exists( 'WPSBoxModule' ) ) {
	/**
	 * Abstract class to define/implement base methods for all module classes
	 * @package WPSBox
	 */
	abstract class WPSBoxModule {
		private static $instances = array();
		
		/**
		 * Constructor
		 * @mvc Controller
		 */
		abstract protected function __construct();

		/**
		 * Provides access to a single instance of a module using the singleton pattern
		 * @mvc Controller
		 * @return object
		 */
		static function get_instance() {
			$module = get_called_class();

			if ( ! isset( self::$instances[ $module ] ) ) {
				self::$instances[ $module ] = new $module();
			}

			return self::$instances[ $module ];
		}

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		abstract public function activate( $network_wide );

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 */
		abstract public function deactivate();

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 */
		abstract public function register_hook_callbacks();

		/**
		 * Initializes variables
		 * @mvc Controller
		 */
		abstract public function init();
		
		/**
		 * Determines if a setting is intended to be public/visible or not
		 * @mvc Controller
		 *
		 * @param string $setting_name
		 * @return bool
		 */
		public function is_public_setting( $setting_name ) {
			return '_' != substr( $setting_name, 0, 1 ) ? true : false;
		}
				
	} // end WPSBoxModule
}