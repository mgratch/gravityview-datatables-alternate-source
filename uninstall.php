<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   GravityView DataTables Alternative Source
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete GravityView content when GravityView is uninstalled, if the setting is set to "Delete on Uninstall"
 * @since 1.15
 */
class GravityView_DataTables_Alt_Uninstall {

	public function __construct() {

		error_log( "start uninstall" );

		$this->fire_everything();

	}

	/**
	 * Get the GravityView Alternate DataTables setting for whether to delete all View settings on uninstall
	 *
	 * @since 1.0
	 *
	 * @return string|null Returns NULL if not configured
	 */
	private function get_delete_setting() {

		$settings = get_option( $this->settings_name, array() );

		return isset( $settings['delete-on-uninstall'] ) ? $settings['delete-on-uninstall'] : null;
	}

	/**
	 * Delete GravityView Views, settings, roles, caps, etc.
	 * @see https://youtu.be/FXy_DO6IZOA?t=35s
	 * @since 1.15
	 * @return void
	 */
	private function fire_everything() {
		error_log( "fire everything" );
		$view_ids = $this->get_view_ids();
		$this->delete_backgorund_tasks( $view_ids );
		$this->delete_options( $view_ids );
		$this->drop_tables( $view_ids );
	}

	/**
	 * Delete GravityView Index Tables
	 * @since 1.0
	 *
	 * @param array $post_ids
	 */
	private function drop_tables( $post_ids = array() ) {
		global $wpdb;

		$index_tables = '';

		for ( $i = 0, $count = count( $post_ids ); $i < $count; $i ++ ) {
			if ( $i < 1 ) {
				$index_tables .= $wpdb->prefix . "gv_index_" . $post_ids[ $i ];
			} else {
				$index_tables .= ', ' . $wpdb->prefix . "gv_index_" . $post_ids[ $i ];
			}
		}

		$sql = "DROP TABLE IF EXISTS $index_tables";
		$sql = esc_sql( $sql );

		$result = $wpdb->query( $sql );

		error_log( "Table dropped: " . $result . " with the query: " . $sql );

	}

	/**
	 * Delete GravityView Alternate DataTables Source options
	 * @since 1.0
	 *
	 * @param array $post_ids
	 */
	private function delete_options( $post_ids = array() ) {

		foreach ( $post_ids as $id ) {
			$result = delete_transient( 'gv_index_' . $id );
			error_log( "transient deleted: " . $result );
		}

	}

	/**
	 * Get a list of DataTables View IDs
	 * @return array
	 */
	private function get_view_ids() {
		global $wpdb;

		$views    = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = %s", "_gravityview_datatables_settings" ), ARRAY_A );
		$view_ids = array();

		foreach ( $views as $view ) {
			$view_ids[] = $view['post_id'];
		}
		error_log( implode(", ", $view_ids ) );
		return $view_ids;

	}

	/**
	 * Find any remaining background tasks associated with GravityView DataTables ALt Sources
	 *
	 * @param $view_ids
	 *
	 */
	private function delete_backgorund_tasks( $view_ids ) {

		/** @define "GVDT_ALT_SRC_DIR" "./" The absolute path to the plugin directory */
		if ( ! defined( 'GVDT_ALT_SRC_DIR' ) ) {
			define( 'GVDT_ALT_SRC_DIR', plugin_dir_path( __FILE__ ) );
		}
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

					error_log( "Job Count Cleared: " . $job_count );

					if ( 0 !== $wp_queue->available_jobs() ) {
						$job_count = $wp_queue->available_jobs();
						error_log( "Job Count Restarted: " . $job_count );
					}
					$i = - 1;
				}
			}

		}


	}
}

new GravityView_DataTables_Alt_Uninstall;