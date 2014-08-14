<?php

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

if ( ! class_exists( 'WPSBox' ) ) {
	/**
	 * Main / front controller class
	 * @package WPSBox
	 */
	class WPSBox extends WPSBoxModule {
		protected static $__CLASS__ = __CLASS__;
		protected static $readable_properties  = array( 'modules' );
		protected static $writeable_properties = array( 'modules' );
		protected $modules;

		const VERSION    = '0.1';
		const PREFIX     = 'wpsbox_';
		const CSS_PREFIX = 'wpsbox-';
		const DEBUG_MODE = false;

		/**
		 * Constructor
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();

			$this->modules = apply_filters( self::PREFIX . 'modules', array(
				'WPSBoxSettings'	=> WPSBoxSettings::get_instance(),
				'WPSBoxShortcode'	=> WPSBoxShortcode::get_instance(),
			) );
		}

		/**
		 * Prepares sites to use the plugin during single or network-wide activation
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $network_wide ) {
					$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog );
						$this->single_activate( $network_wide );
					}

					restore_current_blog();
				} else {
					$this->single_activate( $network_wide );
				}
			} else {
				$this->single_activate( $network_wide );
			}
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 * @mvc Controller
		 *
		 * @param int $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		protected function single_activate( $network_wide ) {
			foreach ( $this->modules as $module ) {
				$module->activate( $network_wide );
			}

			flush_rewrite_rules();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 */
		public function deactivate() {
			foreach ( $this->modules as $module ) {
				$module->deactivate();
			}

			flush_rewrite_rules();
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'wpmu_new_blog',         array( $this, 'activate_new_site' ) );
			add_action( 'init',                  array( $this, 'init' ) );
			add_action( 'wp_enqueue_scripts',    __CLASS__ . '::load_resources' );
			add_action( 'admin_enqueue_scripts', __CLASS__ . '::load_resources' );
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 */
		public function init() {}

		/**
		 * Enqueues CSS, JavaScript, etc
		 * @mvc Controller
		 */
		public static function load_resources() {
			wp_register_script(
				self::PREFIX . 'front-end',
				plugins_url( 'javascript/front-end.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				self::VERSION,
				true
			);

			wp_register_style(
				self::PREFIX . 'front-end',
				plugins_url( 'css/front-end.css', dirname( __FILE__ ) ),
				array( 'font-awesome' ),
				self::VERSION,
				'all'
			);

			if ( ! is_admin() ) {
				wp_enqueue_script( self::PREFIX . 'front-end' );
				wp_enqueue_style( self::PREFIX . 'front-end' );
			}
		}
	} // end WPSBox
}