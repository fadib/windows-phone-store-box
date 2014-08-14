<?php

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

if ( ! class_exists( 'WPSBoxSettings' ) ) {
	/**
	 * Handles plugin settings and user profile meta fields
	 *
	 * @package WPSBox
	 */
	class WPSBoxSettings extends WPSBoxModule {
		protected $settings;
		protected static $default_settings;
		protected static $readable_properties  = array( 'settings' );
		protected static $writeable_properties = array( 'settings' );
		
		const REQUIRED_CAPABILITY = 'manage_options';
		const MENU_SLUG           = 'wpsbox';
		const SETTING_SLUG        = 'wpsbox_settings';


		/**
		 * Constructor
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}

		/**
		 * Public setter for protected variables
		 * Updates settings outside of the Settings API or other subsystems
		 * @mvc Controller
		 *
		 * @param string $variable
		 * @param array  $value This will be merged with WPSBoxSettings->settings, so it should mimic the structure of the WPSBoxSettings::$default_settings. It only needs the contain the values that will change, though. See WPSBox->upgrade() for an example.
		 */
		public function __set( $variable, $value ) {
			// Note: wpsboxModule::__set() is automatically called before this

			if ( $variable != 'settings' ) {
				return;
			}

			$this->settings = self::validate_settings( $value );
			update_option( WPSBox::PREFIX . 'settings', $this->settings );
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'init',                                 array( $this, 'init' ) );
			add_action( 'admin_init',                           array( $this, 'register_settings' ) );
			add_action( 'admin_menu',                           __CLASS__ . '::register_settings_pages' );

			add_filter( 'shortcode_atts_' . self::SETTING_SLUG, array( $this, 'maintain_nested_settings' ), 10, 3 );
			add_filter(
				'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) ) . '/bootstrap.php',
				__CLASS__ . '::add_plugin_action_links'
			);
		}

		/**
		 * Prepares site to use the plugin during activation
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 */
		public function deactivate() {}

		/**
		 * Initializes variables
		 * @mvc Controller
		 */
		public function init() {
			self::$default_settings = self::get_default_settings();
			$this->settings         = self::get_settings();
		}

		/**
		 * Establishes initial values for all settings
		 * @mvc Model
		 * @return array
		 */
		protected static function get_default_settings() {
			return apply_filters( WPSBox::PREFIX . 'default_settings', array( 'url' => '' ) );
		}

		/**
		 * Retrieves all of the settings from the database
		 * @mvc Model
		 * @return array
		 */
		protected static function get_settings() {
			$settings = shortcode_atts(
				self::$default_settings,
				get_option( WPSBox::PREFIX . 'settings', array() )
			);

			return $settings;
		}

		/**
		 * Adds links to the plugin's action link section on the Plugins page
		 * @mvc Model
		 *
		 * @param array $links The links currently mapped to the plugin
		 * @return array
		 */
		public static function add_plugin_action_links( $links ) {
			array_unshift( $links, '<a href="http://wordpress.org/plugins/wpsbox/faq/">Help</a>' );
			array_unshift( $links, '<a href="admin.php?page=' . self::SETTING_SLUG .'">Settings</a>' );

			return $links;
		}

		/**
		 * Adds pages to the Admin Panel menu
		 * @mvc Controller
		 */
		public static function register_settings_pages() {
			add_menu_page(
				WPSBOX_NAME,
				WPSBOX_NAME,
				self::REQUIRED_CAPABILITY,
				self::MENU_SLUG,
				__CLASS__ . '::markup_settings_page'
			);
		}

		/**
		 * Creates the markup for the Settings page
		 * @mvc Controller
		 */
		public static function markup_settings_page() {
			if ( current_user_can( self::REQUIRED_CAPABILITY ) ) {
				require_once( dirname( __DIR__ ) . '/views/wpsbox-settings/page-settings.php' );
			} else {
				wp_die( 'Access denied.' );
			}
		}

		/**
		 * Registers settings sections, fields and settings
		 * @mvc Controller
		 */
		public function register_settings() {
			register_setting(
				self::SETTING_SLUG,
				self::SETTING_SLUG,
				array( $this, 'validate_settings' )
			);
		}

		/**
		 * Validates submitted setting values before they get saved to the database. Invalid data will be overwritten with defaults.
		 * @mvc Model
		 *
		 * @param array $new_settings
		 * @return array
		 */
		public function validate_settings( $new_settings ) {
			$new_settings = shortcode_atts( $this->settings, $new_settings, self::SETTING_SLUG );
			
			return $new_settings;
		}
	} // end WPSBoxSettings
}