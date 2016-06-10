<?php

/**
 * GravityView DataTables Alt Data Source class
 *
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
class GravityView_DataTables_Alt_DataSrc {

	/**
	 * @var GravityView_DataTables_Alt_DataSrc
	 * @since 1.0
	 */
	public static $instance = null;

	/**
	 * GravityView_Math_Report constructor.
	 */
	private function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {

		remove_all_actions( 'wp_ajax_gv_datatables_data', 10 );
		remove_all_actions( 'wp_ajax_nopriv_gv_datatables_data', 10 );
		//add_filter( 'gravityview_use_cache', '__return_false' );
		add_filter( 'gravityview_field_entry_value', array( $this, 'format_entry_value_array' ), 10, 4 );
		add_filter( 'gravityview_datatables_js_options', array(
			$this,
			'change_gravityview_datatables_source'
		), 9999, 3 );
		add_action( 'save_post', array( $this, 'generate_index_table' ), 20 );
		add_action( 'save_post', array( $this, 'handle_all' ), 30 );
	}

	/**
	 * @return GravityView_DataTables_Alt_DataSrc
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function change_gravityview_datatables_source( $dt_config, $view_id, $post ) {

		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityforms_view_entries' ) ) {
			return false;
		}

		//store original options
		$return_config = $dt_config;

		$returned_data = $this->get_view_data( $view_id );

		if ( $returned_data === false ) {
			return false;
		}

		unset( $return_config['ajax'] );
		$return_config['serverSide'] = false;
		$return_config['data']       = $returned_data['data'];

		return $return_config;

	}

	private function get_view_data( $view_id ) {
		global $gravityview_view;

		$view_data = GravityView_View_Data::getInstance()->get_view( $view_id );

		// Prevent error output
		ob_start();

		// Prevent emails from being encrypted
		add_filter( 'gravityview_email_prevent_encrypt', '__return_true' );

		// include some frontend logic
		if ( class_exists( 'GravityView_Plugin' ) && ! class_exists( 'GravityView_View' ) ) {
			GravityView_Plugin::getInstance()->frontend_actions( true );
		}

		$view_data['atts']['id'] = $view_id;

		$atts = $view_data['atts'];

		// prepare to get entries
		$atts = wp_parse_args( $atts, GravityView_View_Data::get_default_args() );

		$view_data['atts'] = $atts;

		$gravityview_view = new GravityView_View( $view_data );

		if ( class_exists( 'GravityView_Cache' ) ) {

			// We need to fetch the search criteria and pass it to the Cache so that the search is used when generating the cache transient key.
			$search_criteria = GravityView_frontend::get_search_criteria( $atts, $view_data['form_id'] );

			// make sure to allow late filter ( used on Advanced Filter extension )
			$criteria = apply_filters( 'gravityview_search_criteria', array( 'search_criteria' => $search_criteria ), $view_data['form_id'], $view_id );

			$atts['search_criteria'] = $criteria['search_criteria'];

			// Cache key should also depend on the View assigned fields
			$atts['directory_table-columns'] = ! empty( $view_data['fields']['directory_table-columns'] ) ? $view_data['fields']['directory_table-columns'] : array();

			// cache depends on user session
			if ( ! is_user_logged_in() ) {
				return '';
			}

			/**
			 * @see wp_get_session_token()
			 */
			$cookie = wp_parse_auth_cookie( '', 'logged_in' );
			$token  = ! empty( $cookie['token'] ) ? $cookie['token'] : '';

			$user_session = get_current_user_id() . '_' . $token;

			$atts['user_session'] = $user_session;

			$Cache = new GravityView_Cache( $view_data['form_id'], $atts );

			if ( $output = $Cache->get() ) {

				do_action( 'gravityview_log_debug', '[DataTables] Cached output found; using cache with key ' . $Cache->get_key() );

			}
		}

		if ( ! isset( $output ) || empty( $output ) ) {

			/**
			 * @todo remove this when ajax is enabled
			 */
			$atts['page_size'] = '-1';

			$view_entries = GravityView_frontend::get_view_entries( $atts, $view_data['form_id'] );

			// build output data
			$data            = array();
			$data['form_id'] = $view_data['form_id'];

			if ( isset( $view_entries['count'] ) && $view_entries['count'] !== '0' && $view_entries['count'] !== 0 ) {

				// For each entry
				foreach ( $view_entries['entries'] as $entry ) {

					$temp = array();

					// Loop through each column and set the value of the column to the field value
					if ( ! empty( $view_data['fields']['directory_table-columns'] ) ) {
						foreach ( $view_data['fields']['directory_table-columns'] as $field_settings ) {
							$temp = array_merge( $temp, GravityView_API::field_value( $entry, $field_settings ) );
						}
					}

					// Then add the item to the output dataset
					$data['data'][] = $temp;

				}

			} else {
				return false;
			}

			do_action( 'gravityview_log_debug', '[DataTables] Ajax request answer', $data );

			//$json = json_encode( $data );

			if ( class_exists( 'GravityView_Cache' ) ) {

				do_action( 'gravityview_log_debug', '[DataTables] Setting cache' );

				// Cache results
				$Cache->set( $data, 'datatables_output' );

			}

			// End prevent error output
			ob_end_clean();

			$output = $data;

		}

		return $output;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @internal param post $post The post object.
	 * @internal param bool $update Whether this is an existing post being updated or not.
	 */
	public function generate_index_table( $post_id ) {

		$post = get_post( $post_id );

		if ( 'gravityview' != $post->post_type ) {
			return;
		}

		$gravityview_view_DT = new GravityView_DataTables_Index_DB( $post_id );
		$gravityview_view_DT->create_table();
	}

	/**
	 * Process handler
	 */
	public function process_handler() {
		if ( ! isset( $_GET['process'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'process' ) ) {
			return;
		}
		if ( 'single' === $_GET['process'] ) {
			$this->handle_single();
		}
		if ( 'all' === $_GET['process'] ) {
			$this->handle_all();
		}
	}

	/**
	 * Handle single
	 */
	protected function handle_single() {
		$names = $this->get_names();
		$rand  = array_rand( $names, 1 );
		$name  = $names[ $rand ];
		wp_queue( new WP_Example_Job( $name ), MINUTE_IN_SECONDS );
	}

	/**
	 * Handle all
	 *
	 * @param $view_id
	 */
	public function handle_all( $view_id ) {
		$entries = $this->get_view_data( $view_id );
		$entries = $entries['data'];
		foreach ( $entries as $entry ) {
			wp_queue( new WP_Example_Job( $entry, $view_id ) );
		}
	}

	public function format_entry_value_array( $output, $entry, $field_settings, $current_field ) {
		$key = is_numeric( $current_field['field_id'] ) ? 'field_' . $current_field['field_id'] : $current_field['field_id'];

		if ("date_created" === $key){
			$output = $entry[$key];
		}

		return array( $key => $output );
	}


}