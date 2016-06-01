<?php
/**
 * Plugin Name: GravityView DataTables Alternative Source (BETA)
 * Plugin URI:  https://gravityview.co/extensions/gvdt-alt-src
 * Description: All an alternative source to be set for Gravity View DataTables Extension
 * Version:     1.0-beta
 * Text Domain: gravityview-datatables-alternate-source
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:	/languages
 * Author:      Katz Web Services, Inc.
 * Author URI:  https://gravityview.co
 */

add_action( 'init', 'gvdt_alt_src_load', 20 );

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

		protected $_version = '1.0.0';

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
			define( 'GVDT_ALT_SRC_DIR', plugin_dir_path( __FILE__ ) );

			require_once GVDT_ALT_SRC_DIR . 'includes/class-gravityview-datatables-alt-data-src.php';

			$this->dataSrc = GravityView_DataTables_Alt_DataSrc::get_instance();
		}

		/**
		 * @return GravityView_DataTables_Alt
		 */
		public static function get_instance() {

			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

	}

	GravityView_DataTables_Alt::get_instance();
}