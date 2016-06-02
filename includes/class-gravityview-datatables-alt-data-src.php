<?php

/**
 * Created by PhpStorm.
 * User: Marc
 * Date: 5/31/2016
 * Time: 11:58 PM
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
		add_filter( 'gravityview_datatables_js_options', array(
			$this,
			'change_gravityview_datatables_source'
		), 9999, 3 );
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

		$view_data        = gravityview_get_current_view_data( $view_id );
		$gravityview_view = new GravityView_View( $view_data );

		// Prevent error output
		ob_start();

		// Prevent emails from being encrypted
		add_filter( 'gravityview_email_prevent_encrypt', '__return_true' );

		// include some frontend logic
		if ( class_exists( 'GravityView_Plugin' ) && ! class_exists( 'GravityView_View' ) ) {
			GravityView_Plugin::getInstance()->frontend_actions();
		}

		// create the view object based on the post_id
		$GravityView_View_Data = GravityView_View_Data::getInstance( (int) $gravityview_view->post_id );

		// get the view data
		$view_data               = $GravityView_View_Data->get_view( $view_id );
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

		if (!isset($output) || empty($output)){

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
							$temp[] = GravityView_API::field_value( $entry, $field_settings );
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


}