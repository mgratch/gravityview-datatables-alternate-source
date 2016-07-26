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

		if ( class_exists( 'GravityView_Plugin' ) && ! class_exists( 'GravityView_View' ) ) {
			GravityView_Plugin::getInstance()->frontend_actions( true );
		}

		remove_action( 'wp_ajax_gv_datatables_data', array(
			"GV_Extension_DataTables_Data",
			'get_datatables_data'
		), 10 );
		remove_action( 'wp_ajax_nopriv_gv_datatables_data', array(
			"GV_Extension_DataTables_Data",
			'get_datatables_data'
		), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 11 );
		add_action( 'gravityview_default_args', array( $this, 'add_hidden_field' ), 10 );

		add_filter( 'gravityview_use_cache', '__return_false' );
		add_filter( 'gravityview/metaboxes/default', array( $this, 'remove_metabox_tab' ) );
		add_filter( 'gravityview_field_entry_value', array( $this, 'format_entry_value_array' ), 10, 4 );
		add_filter( 'gv_index_custom_content', array( $this, 'index_custom_content_values' ), 11, 2 );
		add_filter( 'gravityview_datatables_js_options', array(
			$this,
			'change_gravityview_datatables_source'
		), 9999, 3 );
		add_action( 'wp_ajax_gv_alt_datatables_data', array( $this, 'get_alt_datatables_data' ), 10 );
		add_action( 'wp_ajax_nopriv_gv_alt_datatables_data', array( $this, 'get_alt_datatables_data' ), 10 );

		add_action( 'gravityview_view_saved', array( $this, 'create_table' ), 10, 2 );
		add_action( 'gv_duplicate_view', array( $this, 'create_table' ), 10, 2 );
		add_action( 'gform_entry_created', array( $this, 'insert_entry' ), 10, 2 );
		add_action( 'gform_after_update_entry', array( $this, 'update_entry' ), 10, 2 );
		add_action( 'gform_delete_lead', array( $this, 'delete_entry' ) );
		add_action( 'gform_update_status', array( $this, 'delete_entry' ), 10, 3 );
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

	public function enqueue_admin_scripts() {
		global $pagenow, $typenow, $post;

		if ( "gravityview" !== $typenow ) {
			return;
		}

		if ( "post.php" !== $pagenow ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		wp_register_script( 'gaddon_repeater', GFAddOn::get_gfaddon_base_url() . "/js/repeater{$min}.js", array( 'jquery' ), "1.0" );
		wp_enqueue_script( 'gaddon_repeater' );
		$this->register_noconflict_script( 'gaddon_repeater' );

		wp_register_script( 'gvdt_fieldmap_js', GVDT_ALT_SRC_URL . "/includes/assets/js/gaddon_fieldmap{$min}.js", array(
			'jquery',
			'gaddon_repeater'
		), "1.0" );
		wp_enqueue_script( 'gvdt_fieldmap_js' );
		$this->register_noconflict_script( 'gvdt_fieldmap_js' );

		$index_custom_data = apply_filters( 'gv_index_custom_content', $answer = false, $post->ID );
		$index_custom_data = array( "index_custom_content" => (int) $index_custom_data );

		wp_register_script( 'sort-filter-selectbox', GVDT_ALT_SRC_URL . "/includes/assets/js/sort-filter-selectbox{$min}.js", array( 'gvdt_fieldmap_js' ), "1.0", true );
		wp_localize_script( 'sort-filter-selectbox', 'gvDTIndex', $index_custom_data );
		wp_enqueue_script( 'sort-filter-selectbox' );
		$this->register_noconflict_script( 'gaddon_repeater' );

	}

	public function register_noconflict_script( $script_name ) {
		add_filter( 'gform_noconflict_scripts', create_function( '$scripts', '$scripts[] = "' . $script_name . '"; return $scripts;' ) );
	}

	public function change_gravityview_datatables_source( $dt_config, $view_id, $post ) {

		if ( ! GravityView_Roles_Capabilities::has_cap( 'gravityforms_view_entries' ) ) {
			return false;
		}

		//store original options
		$return_config = $dt_config;

		$view_data = get_post_meta( $view_id, '_gravityview_template_settings', true );

		if ( isset( $view_data['multiple_sort_field'] ) ) {

			$sort_fields                      = json_decode( $view_data['multiple_sort_field'] );
			$view_data['multiple_sort_field'] = array();
			$columns                          = $return_config['columns'];
			$return_config['order']           = array();

			for ( $i = 0, $k = 0; $i < count( $columns ); $i ++ ) {

				$col = str_replace( "gv_", "", $columns[ $i ]['name'] );

				if ( "custom" === $col ) {
					$col = $col . "_" . $k;
					$k ++;
				}

				for ( $j = 0; $j < count( $sort_fields ); $j ++ ) {
					$sort_field = get_object_vars( $sort_fields[ $j ] );
					if ( $col == $sort_field['key'] ) {
						$return_config['order'][] = array( $i, $sort_field['value'] );
						$index_custom_data        = apply_filters( 'gv_index_custom_content', $answer = false, $view_id );
						if ( $index_custom_data ) {
							$return_config['columns'][ $i ]['orderable'] = true;
						}
					}
				}
			}
		}

		$return_config['ajax']['data']['action'] = 'gv_alt_datatables_data';
		$return_config['ajax']['url']            = admin_url( 'admin-ajax.php' );
		$return_config['stateSave']              = false;

		return $return_config;

	}

	/**
	 * @param array $atts
	 * @param $view_id
	 *
	 * @return array|bool|mixed|string
	 */
	public function get_view_data( $atts = array(), $view_id ) {
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

		$atts = wp_parse_args( $atts, $view_data['atts'] );

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
			if ( is_user_logged_in() ) {

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
			} else {
				$output = '';
			}
		}

		if ( ! isset( $output ) || empty( $output ) ) {

			/**
			 * @todo Use Delicious Brain method to detect bottleneck and process at that point
			 */
			$atts['page_size'] = '250';
			$atts['offset']    = isset( $atts['offset'] ) ? intval( $atts['offset'] ) : 0;

			$view_entries = GravityView_frontend::get_view_entries( $atts, $view_data['form_id'] );

			// build output data
			$data            = array();
			$data['form_id'] = $view_data['form_id'];

			if ( isset( $view_entries['count'] ) && $view_entries['count'] !== '0' && $view_entries['count'] !== 0 ) {

				// For each entry
				foreach ( $view_entries['entries'] as $entry ) {

					$temp = array();

					//Remove anonymizing field keys to prepare for `for` loop
					$fields = array_values( $view_data['fields']['directory_table-columns'] );

					// Loop through each column and set the value of the column to the field value
					if ( ! empty( $fields ) ) {
						for ( $i = 0, $c = 0; $i < count( $fields ); $i ++ ) {

							/**
							 * Entry ID is required as the second DB column
							 * @todo this is probably unnecessary as arrays are always sorted numerically or alphabetically
							 */
							if ( 'id' === $fields[ $i ]['id'] ) {
								$include_id = true;
								$temp       = $temp + array( 'id' => $entry['id'] );
							} else {
								$include_id = false;

								//try not to store html
								$fields[ $i ]['show_as_link'] = 0;
								if ( isset( $fields[ $i ]['content'] ) ) {

									$index_custom_data = apply_filters( 'gv_index_custom_content', $answer = false, $view_id );

									if ( $index_custom_data ) {
										$custom_data = GravityView_API::field_value( $entry, $fields[ $i ] );
										$custom_data = array( "custom" => $custom_data['custom'] );
									} else {
										$custom_data = array( "custom" => esc_html( $fields[ $i ]['content'] ) );
									}

									$temp = array_merge( $temp, $custom_data );
								} else {
									$temp = array_merge( $temp, GravityView_API::field_value( $entry, $fields[ $i ] ) );
								}
								if ( key_exists( 'custom', $temp ) ) {
									$temp = array_merge( $temp, array( 'custom_' . $c => $temp['custom'] ) );
									unset( $temp['custom'] );
									$c ++;
								}
							}

							if ( count( $fields ) - 1 === $i && ! $include_id ) {
								$temp = $temp + array( 'id' => $entry['id'] );
							}

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
	 * @return bool
	 */
	public function generate_index_table( $post_id ) {
		$gravityview_view_DT = new GravityView_DataTables_Index_DB( $post_id );
		$result              = $gravityview_view_DT->create_table();

		return $result;
	}

	/**
	 * @param $output
	 * @param $entry
	 * @param $field_settings
	 * @param $current_field
	 *
	 * @return array
	 */
	public function format_entry_value_array( $output, $entry, $field_settings, $current_field ) {

		if ( isset( $_GET['action'] ) && 'http_worker' === $_GET['action'] ) {
			$key = is_numeric( $current_field['field_id'] ) ? 'field_' . $current_field['field_id'] : $current_field['field_id'];

			if ( "date_created" === $key ) {
				$output = $entry[ $key ];
			} else {
				$key = strtolower( $key );
				$key = str_replace( ' ', '_', $key );
				$key = preg_replace( '/[^a-z0-9_\.\-]/', '', $key );
				$key = preg_replace( '/[.-]/', '_', $key );
			}

			return array( $key => $output );
		} else {
			return $output;
		}

	}

	/**
	 * @param $view_id
	 * @param $entry
	 *
	 * @return array
	 */
	public function prepare_entry( $view_id, $entry ) {

		$view_data = GravityView_View_Data::getInstance()->get_view( $view_id );

		if ( class_exists( 'GravityView_Plugin' ) && ! class_exists( 'GravityView_View' ) ) {
			GravityView_Plugin::getInstance()->frontend_actions( true );
		}

		$gravityview_view = new GravityView_View( $view_data );

		$view_entry = array();

		// Loop through each column and set the value of the column to the field value
		if ( ! empty( $view_data['fields']['directory_table-columns'] ) ) {
			foreach ( $view_data['fields']['directory_table-columns'] as $field_settings ) {
				$view_entry = array_merge( $view_entry, GravityView_API::field_value( $entry, $field_settings ) );
			}
		}

		return $view_entry;
	}

	/**
	 * @param $post_id
	 * @param $atts
	 *
	 * @internal param $post
	 *
	 * @internal param $update
	 *
	 * @internal param $update
	 *
	 * @internal param $ID
	 *
	 * @internal param $post
	 *
	 * @internal param $new_status
	 * @internal param $old_status
	 */
	public function create_table( $post_id, $atts ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$gravityview_directory_template = get_post_meta( $post_id, '_gravityview_directory_template', true );

		if ( 'datatables_table' === $gravityview_directory_template ) {
			$this->generate_index_table( $post_id );
		}
	}

	/**
	 * @param $entry
	 * @param $form
	 */
	public function insert_entry( $entry, $form ) {
		remove_action( 'gform_entry_created', array( $this, 'insert_entry' ), 10 );
		$views    = gravityview_get_connected_views( $form['id'] );
		$entry_id = $entry['id'];

		foreach ( $views as $view ) {
			$gravityview_directory_template = get_post_meta( $view->ID, '_gravityview_directory_template', true );

			if ( 'datatables_table' === $gravityview_directory_template ) {
				$gravityview_view_DT = new GravityView_DataTables_Index_DB( $view->ID );
				$entry               = GFAPI::get_entry( $entry_id );
				$entry               = $this->prepare_entry( $view->ID, $entry );
				$gravityview_view_DT->insert( $entry );
			}

		}
		add_action( 'gform_entry_created', array( $this, 'insert_entry' ), 10, 2 );
	}

	/**
	 * @param $form
	 * @param $entry_id
	 */
	public function update_entry( $form, $entry_id ) {
		$views = gravityview_get_connected_views( $form['id'] );

		foreach ( $views as $view ) {
			$gravityview_directory_template = get_post_meta( $view->ID, '_gravityview_directory_template', true );

			if ( 'datatables_table' === $gravityview_directory_template ) {
				$gravityview_view_DT = new GravityView_DataTables_Index_DB( $view->ID );
				$entry               = GFAPI::get_entry( $entry_id );
				$entry               = $this->prepare_entry( $view->ID, $entry );
				$gravityview_view_DT->update( $entry_id, $entry );
			}

		}
	}

	/**
	 * @param $entry_id
	 * @param string $property_value
	 * @param string $previous_value
	 */
	public function delete_entry( $entry_id, $property_value = '', $previous_value = '' ) {
		if ( $property_value == 'trash' ) {
			$form  = gravityview_get_form_from_entry_id( $entry_id );
			$views = gravityview_get_connected_views( $form['id'] );
			foreach ( $views as $view ) {

				$gravityview_directory_template = get_post_meta( $view->ID, '_gravityview_directory_template', true );

				if ( 'datatables_table' === $gravityview_directory_template ) {
					$gravityview_view_DT = new GravityView_DataTables_Index_DB( $view->ID );
					$gravityview_view_DT->delete( $entry_id );
				}

			}
		}
	}

	/**
	 * main AJAX logic to retrieve DataTables data
	 */
	function get_alt_datatables_data() {
		global $gravityview_view;

		if ( empty( $_POST ) ) {
			return;
		}

		// Prevent error output
		ob_start();

		// Send correct headers
		GV_Extension_DataTables_Data::do_ajax_headers( 'application/javascript' );

		$GV_Extension_DataTables_Data = new GV_Extension_DataTables_Data;

		$GV_Extension_DataTables_Data->check_ajax_nonce();

		if ( empty( $_POST['view_id'] ) ) {
			do_action( 'gravityview_log_debug', '[DataTables] AJAX request - View ID check failed' );
			exit( false );
		}

		/**
		 * @filter `gravityview/datatables/json/header/content_length` Enable or disable the Content-Length header on the AJAX JSON response
		 *
		 * @param boolean $has_content_length true by default
		 */
		$has_content_length = apply_filters( 'gravityview/datatables/json/header/content_length', true );

		// Prevent emails from being encrypted
		add_filter( 'gravityview_email_prevent_encrypt', '__return_true' );

		do_action( 'gravityview_log_debug', '[DataTables] AJAX Request ($_POST)', $_POST );

		// include some frontend logic
		if ( class_exists( 'GravityView_Plugin' ) && ! class_exists( 'GravityView_View' ) ) {
			GravityView_Plugin::getInstance()->frontend_actions();
		}

		// Pass $_GET variables to the View functions, since they're relied on heavily
		// for searching and filtering, for example the A-Z widget
		$_GET = json_decode( stripslashes( $_POST['getData'] ), true );

		$view_id = intval( $_POST['view_id'] );
		$post_id = intval( $_POST['post_id'] );

		// create the view object based on the post_id
		$GravityView_View_Data = GravityView_View_Data::getInstance( $post_id );

		// get the view data
		$view_data               = $GravityView_View_Data->get_view( $view_id );
		$view_data['atts']['id'] = $view_id;

		$atts = $view_data['atts'];

		// check for order/sorting
		if ( isset( $_POST['order'][0]['column'] ) ) {
			$order_index = $_POST['order'][0]['column'];
			if ( ! empty( $_POST['columns'][ $order_index ]['name'] ) ) {
				// remove prefix 'gv_'
				$atts['sort_field']     = substr( $_POST['columns'][ $order_index ]['name'], 3 );
				$atts['sort_direction'] = ! empty( $_POST['order'][0]['dir'] ) ? strtoupper( $_POST['order'][0]['dir'] ) : 'ASC';
			}
		}

		// check for search
		if ( ! empty( $_POST['search']['value'] ) ) {
			// inject DT search
			add_filter( 'gravityview_fe_search_criteria', array( $this, 'add_global_search' ), 5, 1 );
		}

		// Paging/offset
		$atts['page_size'] = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : '';
		$atts['offset']    = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 0;

		// prepare to get entries
		$atts = wp_parse_args( $atts, GravityView_View_Data::get_default_args() );

		// check if someone requested the full filtered data (eg. TableTools print button)
		if ( $atts['page_size'] == '-1' ) {
			$mode              = 'all';
			$atts['page_size'] = PHP_INT_MAX;
		} else {
			// regular mode - get view entries
			$mode = 'page';
		}

		$view_data['atts'] = $atts;

		$gravityview_view = new GravityView_View( $view_data );

		// TODO: Placeholder to get Ratings & Reviews links working. May not all be necessary.
		global $post;
		$post = get_post( $post_id );
		$fe   = GravityView_frontend::getInstance();
		$fe->parse_content();
		$fe->set_context_view_id( $view_id );
		$fe->setPostId( $post_id );
		$fe->setGvOutputData( $GravityView_View_Data );

		if ( class_exists( 'GravityView_Cache' ) ) {

			// We need to fetch the search criteria and pass it to the Cache so that the search is used when generating the cache transient key.
			$search_criteria = GravityView_frontend::get_search_criteria( $atts, $view_data['form_id'] );

			// make sure to allow late filter ( used on Advanced Filter extension )
			$criteria = apply_filters( 'gravityview_search_criteria', array( 'search_criteria' => $search_criteria ), $view_data['form_id'], $_POST['view_id'] );

			$atts['search_criteria'] = $criteria['search_criteria'];

			// Cache key should also depend on the View assigned fields
			$atts['directory_table-columns'] = ! empty( $view_data['fields']['directory_table-columns'] ) ? $view_data['fields']['directory_table-columns'] : array();

			// cache depends on user session
			$atts['user_session'] = $GV_Extension_DataTables_Data->get_user_session();

			$Cache = new GravityView_Cache( $view_data['form_id'], $atts );

			if ( $output = $Cache->get() ) {

				do_action( 'gravityview_log_debug', '[DataTables] Cached output found; using cache with key ' . $Cache->get_key() );

				// update DRAW (mr DataTables is very sensitive!)
				$temp         = json_decode( $output, true );
				$temp['draw'] = intval( $_POST['draw'] );
				$output       = function_exists( 'wp_json_encode' ) ? wp_json_encode( $temp ) : json_encode( $temp );

				if ( $has_content_length ) {
					// Fix strange characters before JSON response because of "Transfer-Encoding: chunked" header
					@header( 'Content-Length: ' . strlen( $output ) );
				}

				$pretty_print = defined( 'JSON_PRETTY_PRINT' ) && defined( 'JSON_UNESCAPED_SLASHES' ) ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : true;
				do_action( 'gravityview_log_error', json_encode( $GLOBALS['wp_filter']['wp_ajax_gv_datatables_data'][10], $pretty_print ) );
				do_action( 'gravityview_log_error', json_encode( $GLOBALS['wp_filter']['wp_ajax_nopriv_gv_datatables_data'][10], $pretty_print ) );

				exit( $output );
			}
		}

		//$view_entries = GravityView_frontend::get_view_entries( $atts, $view_data['form_id'] );
		$gravityview_view_DT = new GravityView_DataTables_Index_DB( $view_id );

		$table = $gravityview_view_DT->table_name;

		$primaryKey = $gravityview_view_DT->primary_key;

		$columns = $gravityview_view_DT->get_columns();

		$new_columns = array();
		$i           = 0;

		foreach ( $columns as $column => $val ) {
			$new_columns[] = array( 'db' => $column, 'dt' => $i );
			$i ++;
		}

		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * If you just want to use the basic configuration for DataTables with PHP
		 * server-side, there is no need to edit below this line.
		 */

		$sql_details = array(
			'user' => DB_USER,
			'pass' => DB_PASSWORD,
			'db'   => DB_NAME,
			'host' => DB_HOST
		);

		require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-ssp.php';

		$output = GravityView_DataTables_SSP::simple( $_POST, $sql_details, $table, $primaryKey, $new_columns );

		//$view_entries = $gravityview_view_DT->get_entries( $atts, false );

		//$data = $GV_Extension_DataTables_Data->get_output_data( $view_entries, $view_data );

//		// wrap all
//		$output = array(
//			'draw'            => intval( $_POST['draw'] ),
//			'recordsTotal'    => intval( $view_entries['count'] ),
//			'recordsFiltered' => intval( $view_entries['count'] ),
//			'data'            => $data,
//		);

		do_action( 'gravityview_log_debug', '[DataTables] Ajax request answer', $output );

		$json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $output ) : json_encode( $output );

		if ( class_exists( 'GravityView_Cache' ) ) {

			do_action( 'gravityview_log_debug', '[DataTables] Setting cache', $json );

			// Cache results
			//$Cache->set( $json, 'datatables_output' );

		}

		// End prevent error output
		$errors = ob_get_clean();

		if ( ! empty( $errors ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' Errors generated during DataTables response', $errors );
		}

		if ( $has_content_length ) {
			// Fix strange characters before JSON response because of "Transfer-Encoding: chunked" header
			@header( 'Content-Length: ' . strlen( $json ) );
		}

		$pretty_print = defined( 'JSON_PRETTY_PRINT' ) && defined( 'JSON_UNESCAPED_SLASHES' ) ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : true;
		do_action( 'gravityview_log_error', json_encode( $GLOBALS['wp_filter']['wp_ajax_gv_datatables_data'][10], $pretty_print ) );
		do_action( 'gravityview_log_error', json_encode( $GLOBALS['wp_filter']['wp_ajax_nopriv_gv_datatables_data'][10], $pretty_print ) );

		exit( $json );
	}

	/**
	 * @param $output
	 * @param $view_id
	 *
	 * @return array
	 * @internal param $entry
	 * @internal param $field_settings
	 */
	public function index_custom_content_values( $output, $view_id ) {
		return $output;
	}

	/**
	 * @param $metaboxes
	 *
	 * @return mixed
	 */
	public function remove_metabox_tab( $metaboxes ) {

		global $post;
		$gravityview_directory_template = get_post_meta( $post->ID, '_gravityview_directory_template', true );

		if ( 'datatables_table' === $gravityview_directory_template ) {
			for ( $i = 0, $count = count( $metaboxes ); $i < $count; $i ++ ) {
				if ( 'sort_filter' === $metaboxes[ $i ]['id'] ) {
					$metaboxes[ $i ]['file'] = GVDT_ALT_SRC_DIR . 'includes/template/sort-filter.php';
				}
			}
		}

		return $metaboxes;
	}

	/**
	 * @param $default_args
	 *
	 * @return mixed
	 */
	public function add_hidden_field( $default_args ) {
		$default_args['multiple_sort_field'] =
			array(
				'label'             => __( '', 'gravityview' ),
				'type'              => 'hidden',
				'value'             => '',
				'group'             => 'sort',
				'show_in_shortcode' => false
			);

		return $default_args;
	}


}