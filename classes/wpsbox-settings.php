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
		protected $setting_names;
		
		const REQUIRED_CAPABILITY 		= 'manage_options';
		const MENU_SLUG           		= 'wpsbox';
		const SETTING_SLUG        		= 'wpsbox_settings';
		const SETTING_TITLE				= 'WPSBox';
		const SETTING_PREFIX			= 'wps_box_';
		
		const POST_TYPE_NAME_SINGULAR 	= 'Windows Phone App';
		const POST_TYPE_NAME_PLURAL   	= 'Windows Phone Apps';
		const POST_TYPE_SLUG          	= 'windows-phone';

		/**
		 * Constructor
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
			$this->setting_names = array( 'Cache Duration (days)' );
			foreach ( $this->setting_names as $key ) {
				self::$default_settings[ __CLASS__ ][ $this->slugify_key( $key ) ] = '';
			}
		}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'init',                                 array( $this, 'init' ) );
			add_action( 'admin_init',                           array( $this, 'register_settings' ) );
			add_action( 'admin_menu',                           __CLASS__ . '::register_settings_pages' );
			
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
			
			self::register_post_type( 
				self::POST_TYPE_SLUG, 
				$this->get_post_type_params( 
					self::POST_TYPE_SLUG, 
					self::POST_TYPE_NAME_SINGULAR, 
					self::POST_TYPE_NAME_PLURAL 
				) 
			);
		}

		/**
		 * Establishes initial values for all settings
		 * @mvc Model
		 * @return array
		 */
		protected static function get_default_settings() {
			return self::$default_settings;
		}

		/**
		 * Retrieves all of the settings from the database
		 * @mvc Model
		 * @return array
		 */
		protected static function get_settings() {
			$settings = shortcode_atts(
				self::$default_settings,
				get_option( WPSBox::PREFIX . 'settings' )
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
				$class = get_called_class();
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
			add_settings_section(
				self::SETTING_PREFIX . 'section',
				'',
				__CLASS__ . '::markup_settings_section_header',
				WPSBox::PREFIX . 'settings'
			);

			foreach ( self::get_instance()->setting_names as $setting ) {
				$slug = $this->slugify_key( $setting );

				if ( self::is_public_setting( $setting ) ) {
					add_settings_field(
						self::SETTING_PREFIX . $slug,
						$setting,
						array( $this, 'markup_settings_fields' ),
						WPSBox::PREFIX . 'settings',
						self::SETTING_PREFIX . 'section',
						array( 'label_for' => self::SETTING_PREFIX . $slug )
					);
				}
			}

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
			
			foreach ( $new_settings[ __CLASS__ ] as $key => $value ) {
				$new_settings[ __CLASS__ ][ $key ] = absint( $value ); 
			}
			return $new_settings;
		}
		
		/**
		 * Adds the section introduction text to the Settings page
		 * @mvc Controller
		 *
		 * @param array $section
		 */
		public static function markup_settings_section_header( $section ) {
			require_once( dirname( __DIR__ ) . '/views/wpsbox-settings/page-settings-section-header.php' );
		}
		
		/**
		 * Delivers the markup for settings fields
		 * @mvc Controller
		 *
		 * @param array $field
		 */
		public function markup_settings_fields( $field ) {
			$class = get_called_class();
			$setting = str_replace( $class::SETTING_PREFIX, '', $field['label_for'] );
			
			require_once( dirname( __DIR__ ) . '/views/wpsbox-settings/page-settings-fields.php' );
		}
		
		public function slugify_key( $key ) {
			return trim( strtolower( str_replace( array( ' ', ')', '(' ), '_', $key ) ), ' _' );
		}
		
	} // end WPSBoxSettings
}