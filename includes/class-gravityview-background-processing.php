<?php

if (! class_exists("WP_Job")){
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
	 * WP_Example_Job constructor.
	 *
	 * @param $entry
	 * @param $view_id
	 */
	public function __construct( $entry, $view_id ) {
		$this->entry    = $entry;
		$this->view_id = $view_id;
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

		$processor = new GravityView_DataTables_Index_DB($this->view_id);

		

		
		$processor->insert($this->entry);

		$message = $this->get_message( $this->entry );

		$this->really_long_running_task();
		$this->log( $message );
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