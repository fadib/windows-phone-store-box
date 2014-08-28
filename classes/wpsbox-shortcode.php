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
		protected $cache_duration, $view_folder;
		protected static $readable_properties  = array( 'cache_duration', 'view_folder' );
		protected static $writeable_properties = array( 'cache_duration' );
		
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
			// add_action( 'wp_ajax_'.        WPSBox::PREFIX . 'render_box', 	array( $this, 'render_box' ) );
			// add_action( 'wp_ajax_nopriv_'. WPSBox::PREFIX . 'render_box',	array( $this, 'render_box' ) );
			add_shortcode( self::SHORTCODE_NAME,							array( $this, 'shortcode_wpsbox' ) );
		}

		/**
		 * Initializes variables
		 * @mvc Controller
		 */
		public function init() {
			$this->cache_duration = apply_filters( WPSBox::PREFIX . 'cache_duration', 0 );
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
				$items = $this->get_wpsbox( $attributes['url'] );
			}

			ob_start();
			require_once( $this->view_folder . '/shortcode.php' );
			return apply_filters( WPSBox::PREFIX . 'shortcode_output', ob_get_clean() );
		}

		/**
		 * Gets all of the attributes from the source url
		 * @mvc Model
		 *
		 * @param string $hashtag
		 * @return array
		 */
		protected function get_wpsbox( $url ) {
			$items = get_posts( array(
				'posts_per_page' => 1,
				'post_type' => WPSBoxSettings::POST_TYPE_SLUG,
				'meta_query' => array(
					array(
						'key' => 'app_url',
						'value' => $url,
					)
				)
			) );

			if ( ( $items && post_cache_expired( $items ) ) || empty( $items ) ) {
				$items = $this->fetch_app_info( $url );
			}
			
			return $items;
		}

		/**
		 * Returns HTML markup for the box
		 * This is an AJAX handler
		 * @mvc Controller
		 */
		public function render_box() {
			$url = sanitize_text_field( $_REQUEST['url'] );

			$this->send_ajax_headers( 'text/html' );

			if ( empty( $url ) ) {
				wp_die( -1 );
			}
			
			$this->fetch_app( $url );
			$items_markup = $this->get_items_markup( $url );

			wp_die( json_encode( $items_markup ? $items_markup : 0 ) );
		}

		/**
		 * Fetch app information from the site
		 * @mvc Controller
		 * 
		 * @param string $url
		 */
		protected function fetch_app_info( $url ) {
			$items = $this->fetch_app( $url );
			if ( $items )
				return $this->import_new_posts( $this->convert_items_to_posts( $items ) );
			
			return false;
		}
		
		/**
		 * Retrieves app attributes the given url
		 * @mvc Model
		 *
		 * @param string $url
		 * @return mixed array|false
		 */
		protected function fetch_app( $url ) {
			$response = wp_remote_get( $url, array(
				'httpversion' => '1.1',
				'user-agent'  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30',
			    'headers'     => array(),
			    'sslverify'   => true,
			) );
			
			if ( is_wp_error( $response ) ) {
				return false;
			}
			
			$body = $response['body'];
			
			$dom_document = new DOMDocument();
			$dom_document->loadHTML( $body );
			$dom_xpath = new DOMXpath( $dom_document );
			
			$item = array();
			$item['app_url']			= $url;
			$item['app_title'] 			= $this->extract_value( $dom_xpath->query("//h1[@itemprop='name']") );
			$item['app_logo'] 			= $this->extract_value( $dom_xpath->query("//img[contains(@class, 'appImage')]"), 'src' );
			$item['app_description'] 	= $this->extract_value( $dom_xpath->query("//pre[@itemprop='description']") );
			$item['app_cost'] 			= $this->extract_value( $dom_xpath->query("//span[@itemprop='price']") );
			$item['app_rating_value'] 	= $this->extract_value( $dom_xpath->query("//div[@id='rating']/meta[@itemprop='ratingValue']"), 'content' );
			$item['app_rating_count'] 	= $this->extract_value( $dom_xpath->query("//div[@id='rating']/meta[@itemprop='ratingCount']"), 'content' );
			$item['app_publisher_text'] = $this->extract_value( $dom_xpath->query("//div[@id='publisher']/h4[@class='noteText']") );
			$item['app_publisher'] 		= $this->extract_value( $dom_xpath->query("//div[@id='publisher']/span[@itemprop='publisher']") );
			$item['app_filesize_text'] 	= $this->extract_value( $dom_xpath->query("//div[@id='packageSize']/h4[@class='noteText']") );
			$item['app_filesize'] 		= $this->extract_value( $dom_xpath->query("//div[@id='packageSize']/span") );
			$item['app_update_text'] 	= $this->extract_value( $dom_xpath->query("//div[@id='releaseDate']/h4[@class='noteText']") );
			$item['app_date_published'] = $this->extract_value( $dom_xpath->query("//div[@id='releaseDate']/meta[@itemprop='datePublished']"), 'content' );
			$item['app_date_updated'] 	= $this->extract_value( $dom_xpath->query("//div[@id='releaseDate']/span") );
			$item['app_version_text'] 	= $this->extract_value( $dom_xpath->query("//div[@id='version']/h4[@class='noteText']") );
			$item['app_version'] 		= $this->extract_value( $dom_xpath->query("//div[@id='version']/span") );
			$item['app_categories'] 	= $this->extract_value( $dom_xpath->query("//strong[@itemprop='applicationCategory']") );
						
			return array( $item );
		}
		
		/**
		 * Converts data from external source into a post/postmeta format so it can be saved in the database
		 * @mvc Model
		 *
		 * @param array $items
		 * @return array
		 */
		public function convert_items_to_posts( $items ) {
			$posts = array();

			if ( $items ) {
				foreach ( $items as $item ) {
					$post_timestamp_gmt   = time();
					$post_timestamp_local = self::convert_gmt_timestamp_to_local( $post_timestamp_gmt );
					
					$post = array(
						'post_author'   => get_current_user_id(),
						'post_content'  => wp_kses( $item['app_description'], wp_kses_allowed_html( 'data' ), array( 'http', 'https', 'mailto' ) ),
						'post_date'     => date( 'Y-m-d H:i:s', $post_timestamp_local ),
						'post_date_gmt' => date( 'Y-m-d H:i:s', $post_timestamp_gmt ),
						'post_status'   => 'publish',
						'post_title'    => sanitize_text_field( $item['app_title'] ),
						'post_type'     => WPSBoxSettings::POST_TYPE_SLUG,
					);

					$post_meta = array(
						'app_url' 				=> sanitize_text_field( $item['app_url'] ),
						'app_cost' 				=> sanitize_text_field( $item['app_cost'] ),
						'app_rating_value' 		=> sanitize_text_field( $item['app_rating_value'] ),
						'app_rating_count' 		=> sanitize_text_field( $item['app_rating_count'] ),
						'app_publisher_text' 	=> sanitize_text_field( $item['app_publisher_text'] ),
						'app_publisher' 		=> sanitize_text_field( $item['app_publisher'] ),
						'app_filesize_text' 	=> sanitize_text_field( $item['app_filesize_text'] ),
						'app_filesize' 			=> sanitize_text_field( $item['app_filesize'] ),
						'app_update_text' 		=> sanitize_text_field( $item['app_update_text'] ),
						'app_date_published' 	=> sanitize_text_field( date( 'Y-m-d', strtotime( $item['app_date_published'] ) ) ),
						'app_date_updated'		=> sanitize_text_field( date( 'Y-m-d', strtotime( $item['app_date_updated'] ) ) ),
						'app_version_text' 		=> sanitize_text_field( $item['app_version_text'] ),
						'app_version' 			=> sanitize_text_field( $item['app_version'] ),
						'app_categories' 		=> sanitize_text_field( $item['app_categories'] ),
						'media'            		=> array(
							array(
								'small_url' => isset( $item['app_logo'] ) ? esc_url_raw( str_replace( 'ws_icon_large', 'ws_icon_small', $item['app_logo'] ) ) : false,
								'large_url' => isset( $item['app_logo'] ) ? esc_url_raw( $item['app_logo'] ) : false,
								'type'      => 'image',
							),
						),
					);

					$posts[] = array(
						'post'       => $post,
						'post_meta'  => $post_meta,
					);
				}
			}

			return $posts;
		}
		
		/**
		 * Imports items from external source into the local database as posts
		 * @mvc Controller
		 *
		 * @param array $posts
		 */
		protected function import_new_posts( $posts ) {
			global $wpdb;
		
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$post_id = wp_insert_post( $post['post'] );
					if ( $post_id ) {
						foreach ( $post['post_meta'] as $key => $value ) {
							update_post_meta( $post_id, $key, $value );
						}
						
						return array( get_post( $post_id ) );
					}
				}
			}
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
		
		private function extract_value( $elements, $attribute = '' ) {
			if ( !is_null( $elements ) ) {
				foreach ( $elements as $element ) {
					if ( $attribute && $element->attributes ) {
						foreach ( $element->attributes as $attr ) {
							if ( $attr->name == $attribute ) {
								return $attr->value;
							}
						}
					}

					return $element->nodeValue;
				}
			}
			
			return '';
		}
		
	} // end WPSBoxShortcode
}