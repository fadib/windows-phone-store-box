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
		 * Registers the custom post type
		 * @mvc Controller
		 */
		protected static function register_post_type( $slug, $params ) {
			if ( ! post_type_exists( $slug ) ) {
				$post_type = register_post_type( $slug, $params );
			}
		}
		
		/**
		 * Defines the parameters for the custom post type
		 * @mvc Model
		 *
		 * @return array
		 */
		protected function get_post_type_params( $slug, $singular_name, $plural_name ) {
			$labels = array(
				'name'               => $plural_name,
				'singular_name'      => $singular_name,
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New ' . $singular_name,
				'edit'               => 'Edit',
				'edit_item'          => 'Edit ' .    $singular_name,
				'new_item'           => 'New ' .     $singular_name,
				'view'               => 'View ' .    $plural_name,
				'view_item'          => 'View ' .    $singular_name,
				'search_items'       => 'Search ' .  $plural_name,
				'not_found'          => 'No ' .      $plural_name . ' found',
				'not_found_in_trash' => 'No ' .      $plural_name . ' found in Trash',
				'parent'             => 'Parent ' .  $singular_name
			);

			$post_type_params = array(
				'labels'          => $labels,
				'singular_label'  => $singular_name,
				'public'          => true,
				'show_in_menu'    => WPSBoxSettings::MENU_SLUG,
				'hierarchical'    => true,
				'capability_type' => 'post',
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => $slug, 'with_front' => false ),
				'query_var'       => true,
				'supports'        => array( 'title', 'editor', 'thumbnail', )
			);

			return apply_filters( WPSBox::PREFIX . 'post-type-params', $post_type_params );
		}
		
		/**
		 * Determines if a setting is intended to be public/visible or not
		 * @mvc Controller
		 *
		 * @param string $setting_name
		 * @return bool
		 */
		protected function is_public_setting( $setting_name ) {
			return '_' != substr( $setting_name, 0, 1 ) ? true : false;
		}
		
		/**
		 * Converts a timestamp in GMT to the local timezone
		 * @mvc Model
		 *
		 * @param int $post_timestamp_gmt
		 * @return int
		 */
		public static function convert_gmt_timestamp_to_local( $post_timestamp_gmt ) {
			$post_timestamp_local = $post_timestamp_gmt + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

			return $post_timestamp_local;
		}
		
	} // end WPSBoxModule
}