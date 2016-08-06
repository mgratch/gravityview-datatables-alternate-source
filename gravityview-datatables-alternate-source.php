<?php
/**
 * Plugin Name: GravityView DataTables Alternative Source (BETA)
 * Plugin URI:  https://gravityview.co/extensions/gvdt-alt-src
 * Description: All an alternative source to be set for Gravity View DataTables Extension
 * Version:     1.0.1
 * Text Domain: gravityview-datatables-alternate-source
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:    /languages
 * Author:      Katz Web Services, Inc.
 * Author URI:  https://gravityview.co
 */

add_action( 'plugins_loaded', 'gvdt_alt_src_load', 10 );

/**
 * Wrapper function to make sure GravityView_Extension has loaded
 * @return void
 */
function gvdt_alt_src_load() {

	if ( ! class_exists( 'GravityView_Extension' ) ) {

		if ( class_exists( 'GravityView_Plugin' ) && is_callable( array(
				'GravityView_Plugin',
				'include_extension_framework'
			) )
		) {
			GravityView_Plugin::include_extension_framework();
		} else {
			return;
		}
	}

	class GravityView_DataTables_Alt extends GravityView_Extension {

		protected $_title = 'DataTable Alt Src';

		protected $_version = '1.0.1';

		protected $_min_gravityview_version = '1.12';

		protected $_min_php_version = '5.4.0';

		protected $_text_domain = 'gravityview-datatables-alternate-source';

		protected $_path = __FILE__;

		/**
		 * @var GravityView_DataTables_Alt
		 */
		public static $instance;

		/**
		 * @var GravityView_DataTables_Alt_DataSrc
		 */
		public $dataSrc;

		function __construct() {

			parent::__construct();

			// Make sure it's able to check for PHP version and
			if ( ! is_callable( array( $this, 'is_extension_supported' ) ) || false === self::$is_compatible ) {
				return;
			}

			/**
			 * Full path to the DataTable Alt Src file
			 * @define "GVDT_ALT_SRC_FILE" "./gravityview-datatables-alternate-source.php"
			 */
			define( 'GVDT_ALT_SRC_FILE', __FILE__ );

			/** @define "GVDT_ALT_SRC_DIR" "./" The absolute path to the plugin directory */
			if ( ! defined( 'GVDT_ALT_SRC_DIR' ) ) {
				define( 'GVDT_ALT_SRC_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'GVDT_ALT_SRC_URL' ) ) {
				define( 'GVDT_ALT_SRC_URL', plugin_dir_url( __FILE__ ) );
			}

			require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-alt-data-src.php';
			require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-index-db.php';
			require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-index-db.php';
			require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-background-processing.php';

			global $wp_queue;
			$wp_queue->release_time = 0;

			$WP_GVDT_Index_Job = new WP_GVDT_Index_Job();
			$WP_GVDT_Index_Job->release();
			$wp_queue->restart_failed_jobs();
			$job_count = $wp_queue->available_jobs();

			for ( $i = 0; $i < $job_count; $i ++ ) {

				$job = $wp_queue->get_next_job();

				if ( isset($job->job) && false !== strpos( $job->job, "WP_GVDT_Index_Job" ) ) {
					$wp_queue->delete( $job );
					if ( $i === $job_count - 1 ) {
						if ( 0 !== $wp_queue->available_jobs() ) {
							$job_count = $wp_queue->available_jobs();
						}
						$i = - 1;
					}
				}

			}
			$this->dataSrc = GravityView_DataTables_Alt_DataSrc::get_instance();
		}

	}

	new GravityView_DataTables_Alt();
}

/**
 * Creates the queue tables if they don't exist yet.
 *
 * @subcommand create-tables
 */
function create_tables() {

	/** @define "GVDT_ALT_SRC_DIR" "./" The absolute path to the plugin directory */
	if ( ! defined( 'GVDT_ALT_SRC_DIR' ) ) {
		define( 'GVDT_ALT_SRC_DIR', plugin_dir_path( __FILE__ ) );
	}

	require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-index-db.php';
	require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-index-db.php';

	global $wpdb;

	$queue_table       = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . 'queue' ) );
	$failed_jobs_table = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . 'failed_jobs' ) );

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


	$wpdb->hide_errors();

	$charset_collate = $wpdb->get_charset_collate();

	if ( ! $queue_table ):

		$sql = "CREATE TABLE {$wpdb->prefix}queue (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                attempts tinyint(1) NOT NULL DEFAULT 0,
                locked tinyint(1) NOT NULL DEFAULT 0,
                locked_at datetime DEFAULT NULL,
                available_at datetime NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

	endif;
	if ( ! $failed_jobs_table ):

		$sql = "CREATE TABLE {$wpdb->prefix}failed_jobs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                failed_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );
	endif;

	if ( $failed_jobs_table ) {
		require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-alt-data-src.php';
		require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-index-db.php';
		require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-index-db.php';
		require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-background-processing.php';

		$GravityView_DataTables_Alt_DataSrc = GravityView_DataTables_Alt_DataSrc::get_instance();
		$GravityView_DataTables_Alt_DataSrc->create_tables();
	}
}

register_activation_hook( __FILE__, 'create_tables' );