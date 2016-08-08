<?php

if ( ! class_exists( "WP_Job" ) ) {
	return;
}

class WP_GVDT_Index_Job extends WP_Job {

	/**
	 * @var string
	 */
	protected $entry;

	/**
	 * @var string
	 */
	protected $view_id;

	/**
	 * @var string
	 */
	protected $args;

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * @var array
	 */
	protected $new_columns;

	/**
	 * WP_Example_Job constructor.
	 *
	 * @param $entry
	 * @param $view_id
	 * @param array $args
	 * @param string $context
	 * @param array $new_columns
	 */
	public function __construct( $entry = null, $view_id = null, $args = array(), $context = '', $new_columns = array() ) {
		$this->entry       = $entry;
		$this->view_id     = $view_id;
		$this->args        = $args;
		$this->context     = $context;
		$this->new_columns = $new_columns;
	}

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * THIS IS CURRENTLY USING THE MAGIC NUMBER OF 150
	 *
	 * @todo identify largest packet size usable for the current environment
	 * @see \WPMDB_Base::get_bottleneck
	 *
	 * @return mixed
	 * @throws Exception
	 * @internal param mixed $item Queue item to iterate over
	 *
	 */
	public function handle() {

		//don't used cache results for background processing
		add_filter( 'gravityview_use_cache', '__return_false' );

		global $wpdb;

		$processor = new GravityView_DataTables_Index_DB( $this->view_id );

		if ( null !== $this->entry ) {
			$processor->insert( $this->entry );
		} elseif ( null !== $this->view_id && 'sync-all' === $this->context ) {

			$form_id = gravityview_get_form_id( $this->view_id );

			if ( empty( $form_id ) ) {
				return false;
			}

			$entry_count = GFAPI::count_entries( $form_id );

			if ( ! $entry_count ) {
				return false;
			}

			$page_count = ceil( intval( $entry_count ) / 150 );
			$args       = array();

			$offset = get_transient( "gv_index_" . $this->view_id );
			$offset = false !== $offset ? $offset : 0;

			for ( $i = 0; $i < $page_count; $i ++ ) {
				$args['offset'] = max( $i * 150, $offset );
				wp_queue( new WP_GVDT_Index_Job( null, $this->view_id, $args, 'sync-group', $this->new_columns ) );
				set_transient( "gv_index_" . $this->view_id, $args['offset'] );
			}

		} elseif ( null !== $this->view_id && 'sync-group' === $this->context ) {
			$src     = GravityView_DataTables_Alt_DataSrc::get_instance();
			$entries = $src->get_view_data( $this->args, $this->view_id );

			if ( ! isset( $entries['data'] ) || ! $entries['data'] || empty( $entries['data'] ) || is_null( $entries['data'] ) ) {
				return false;
			}

			$entries = $entries['data'];
			foreach ( $entries as $entry ) {
				$result = $processor->update_index( $entry, $this->new_columns );
				$this->log( "inserted: " . boolval( $result ) . "\n\t" . $wpdb->last_error );
			}

		}
	}

	/**
	 * Really long running process
	 *
	 * @return int
	 */
	protected function really_long_running_task() {
		return sleep( 5 );
	}

	/**
	 * Log
	 *
	 * @param string $message
	 */
	protected function log( $message ) {
		error_log( $message );
	}

	/**
	 * Get lorem
	 *
	 * @param string $entry
	 *
	 * @return string
	 */
	protected function get_message( $entry ) {
		return $entry;
	}


	/**
	 *Provide a method to unlock locked tables, for uninstall purposes only
	 *
	 * @param int $delay
	 */
	public function release( $delay = 0 ) {
		parent::release( $delay );
	}


}