<?php

if ( ! class_exists( "WP_Job" ) ) {
	return;
}

class WP_Example_Job extends WP_Job {

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
	 * WP_Example_Job constructor.
	 *
	 * @param $entry
	 * @param $view_id
	 * @param array $args
	 * @param string $context
	 */
	public function __construct( $entry = null, $view_id = null, $args = array(), $context = '' ) {
		$this->entry   = $entry;
		$this->view_id = $view_id;
		$this->args = $args;
		$this->context = $context;
	}

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 * @return mixed
	 * @throws Exception
	 * @internal param mixed $item Queue item to iterate over
	 *
	 */
	public function handle() {

		$processor = new GravityView_DataTables_Index_DB( $this->view_id );

		if ( null !== $this->entry ) {
			$processor->insert( $this->entry );
		} elseif ( null !== $this->view_id && 'sync-all' === $this->context) {

			$form_id     = gravityview_get_form_id( $this->view_id );
			$entry_count = GFAPI::count_entries( $form_id );

			$page_count = intval( $entry_count ) / 250;
			$args       = array();

			$offset = get_transient( "gv_index_" . $this->view_id );
			$offset = false !== $offset ? $offset : 0;

			for ( $i = 0; $i < $page_count; $i ++ ) {
				$args['offset'] = max( $i * 250, $offset );
				wp_queue( new WP_Example_Job( null, $this->view_id, $args, 'sync-group' ) );
				set_transient( "gv_index_" . $this->view_id, $args['offset'] );
			}

		} elseif ( null !== $this->view_id && 'sync-group' === $this->context) {
			$src         = GravityView_DataTables_Alt_DataSrc::get_instance();
			$entries        = $src->get_view_data( $this->args, $this->view_id );

			$entries = $entries['data'];
			foreach ( $entries as $entry ) {
				$processor->insert( $entry );
				$this->log( "inserted: " . $entry['id'] );
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

}