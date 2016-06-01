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

		include_once( GV_DT_DIR . 'includes/class-admin-datatables.php' );

		$class = new GV_Extension_DataTables_Data();
		
		remove_action( 'wp_ajax_gv_datatables_data', array(
			$class,
			'get_datatables_data'
		), 11 );
		remove_action( 'wp_ajax_nopriv_gv_datatables_data', array(
			$class,
			'get_datatables_data'
		), 11 );
		add_filter( 'gravityview_datatables_js_options', array(
			$this,
			'change_gravityview_datatables_source'
		), 9999, 3 );
		add_filter( 'gravityview_use_cache', '__return_false' );
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

		return $return_config;

	}


}