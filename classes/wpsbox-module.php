<?php

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

if ( ! class_exists( 'WPSBoxModule' ) ) {
	/**
	 * Abstract class to define/implement base methods for all module classes
	 * @package WPSBox
	 */
	abstract class WPSBoxModule {
		protected static $__CLASS__ = __CLASS__;
		private static $instances = array();
		
		/**
		 * get_called_class() replacement for PHP 5.2
		 */
		protected static function get_class_local() {
	        if ( self::$__CLASS__ == __CLASS__ ) {
	            die("You MUST provide a <code>protected static \$__CLASS__ = __CLASS__;</code> statement in your Singleton-class!");
	        }
    
	        return self::$__CLASS__;
	    }
		
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
		public static function get_instance() {
			$module = self::get_class_local();
			
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
	} // end WPSBoxModule
}