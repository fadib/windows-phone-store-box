<?php

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	die( 'Access denied.' );

if ( ! class_exists( 'WPSBoxShortcode' ) ) {
	/**
	 * Handles the [wpsbox] shortcode
	 *
	 * @package wpsbox
	 */
	class WPSBoxShortcode extends WPSBoxModule {
		protected $refresh_interval, $view_folder;
		protected static $readable_properties  = array( 'refresh_interval', 'view_folder' );
		protected static $writeable_properties = array( 'refresh_interval' );
		
		const SHORTCODE_NAME = 'wpsbox';

		/**
		 * Constructor
		 * @mvc Controller
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
			$this->view_folder = dirname( __DIR__ ) . '/views/'. str_replace( '.php', '', basename( __FILE__ ) );
		}

		/**
		 * Prepares site to use the plugin during activation
		 * @mvc Controller
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
			$this->init();
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 * @mvc Controller
		 */
		public function deactivate() {}

		/**
		 * Register callbacks for actions and filters
		 * @mvc Controller
		 */
		public function register_hook_callbacks() {
			add_action( 'init',												array( $this, 'init' ) );
			add_action( 'wp_ajax_'.        WPSBox::PREFIX . 'render_box', 	array( $this, 'render_box' ) );
			add_action( 'wp_ajax_nopriv_'. WPSBox::PREFIX . 'render_box',	array( $this, 'render_box' ) );
			add_shortcode( self::SHORTCODE_NAME,							array( $this, 'shortcode_wpsbox' ) );
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 */
		public function init() {
			$this->refresh_interval = apply_filters( WPSBox::PREFIX . 'refresh_interval', 30 );
		}

		/**
		 * Controller for the [wpsbox] shortcode
		 * @mvc Controller
		 *
		 * @return string
		 */
		public function shortcode_wpsbox( $attributes ) {
			$attributes = shortcode_atts( array( 'url' => '' ), $attributes );
			$items = array();

			if ( $attributes['url'] ) {
				$items = self::get_wpsbox( $attributes['url'] );
			}

			ob_start();
			require_once( $this->view_folder . '/shortcode.php' );
			return apply_filters( WPSBox::PREFIX . 'shortcode_output', ob_get_clean() );
		}

		/**
		 * Gets all of the items from all of the media sources that are assigned to the given hashtag
		 * @mvc Model
		 *
		 * @param string $hashtag
		 * @return array
		 */
		protected function get_wpsbox( $url ) {
			$items = array();
			return $items;
			
			$term = get_term_by( 'name', $hashtag, TGGRMediaSource::TAXONOMY_HASHTAG_SLUG );
			if ( isset ( $term->slug ) ) {
				$items = get_posts( array(
					'posts_per_page'   => apply_filters( WPSBox::PREFIX . 'media_items_per_page', 30 ),
					'post_type'        => $post_types,
					'tax_query'        => array(
						array(
							'taxonomy' => TGGRMediaSource::TAXONOMY_HASHTAG_SLUG,
							'field'    => 'slug',
							'terms'    => $term->slug,
						),
					),
				) );
			}

			return $items;
		}

		/**
		 * Returns HTML markup for the box
		 * This is an AJAX handler
		 * @mvc Controller
		 */
		public function render_box() {
			$hashtag = sanitize_text_field( $_REQUEST['url'] );

			$this->send_ajax_headers( 'text/html' );

			if ( empty( $hashtag ) ) {
				wp_die( -1 );
			}

			$this->import_new_items( $hashtag );
			$items = $this->get_media_items( $hashtag );
			$new_items_markup = $this->get_new_items_markup( $items, $existing_item_ids );

			wp_die( json_encode( $new_items_markup ? $new_items_markup : 0 ) );
		}

		/**
		 * Imports the latest items from media sources
		 * @mvc Controller
		 * 
		 * @param string $hashtag
		 * @param string $rate_limit 'respect' to enforce the rate limit, or 'ignore' to ignore it
		 */
		protected function import_new_items( $hashtag, $rate_limit = 'respect' ) {
			$last_fetch = get_transient( WPSBox::PREFIX . 'last_media_fetch', 0 );

			if ( 'ignore' == $rate_limit || self::refresh_interval_elapsed( $last_fetch, $this->refresh_interval ) ) {
				set_transient( WPSBox::PREFIX . 'last_media_fetch', microtime( true ) );	// do this right away to minimize the chance of race conditions
				
				foreach ( WPSBox::get_instance()->media_sources as $source ) {
					$source->import_new_items( $hashtag );
				}
			}
		}

		/**
		 * Determines if the enough time has passed since the previous media fetch
		 *
		 * @param int $last_fetch The number of seconds between the Unix epoch and the last time the data was fetched, as a float (i.e., the recorded output of microtime( true ) during the last fetch).
		 * @param int $refresh_interval The minimum number of seconds that should elapse between refreshes
		 * @return bool
		 */
		protected static function refresh_interval_elapsed( $last_fetch, $refresh_interval ) {
			$current_time = microtime( true );
			$elapsed_time = $current_time - $last_fetch;

			return $elapsed_time > $refresh_interval;
		}

		/**
		 * Outputs the appropriate headers for responses to AJAX requests
		 * In some cases, sending these are necessary to avoid browser quirks, especially the 200 header
		 *
		 * @param string $content_type The desired content type. e.g., 'text/html', 'application/json', etc
		 */
		protected static function send_ajax_headers( $content_type = 'text/html' ) {
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
			header( 'Content-Type: '. $content_type .'; charset=utf8' );
			header( 'Content-Type: '. $content_type );
			header( $_SERVER['SERVER_PROTOCOL'] . ' 200 OK' );
		}
		
	} // end WPSBoxShortcode
}