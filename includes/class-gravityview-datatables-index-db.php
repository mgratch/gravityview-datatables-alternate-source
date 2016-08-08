<?php

/**
 * GravityView DataTables Alt Src Index DB class
 *
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
class GravityView_DataTables_Index_DB extends GravityView_Index_DB {

	/**
	 * View ID
	 * @var null
	 */
	public $view_id = null;

	/**
	 * View Data
	 * @var array
	 */
	public $view_data = array();

	/**
	 * View Columns
	 * @var array
	 */
	public $columns = array();

	/**
	 * GravityForems/GravityView Meta Fields
	 * @var array
	 */
	public $meta_fields = array(
		"id",
		"date_created",
		"source_url",
		"ip",
		"created_by",
		"custom",
		"other_entries",
		"entry_link",
		"edit_link",
		"delete_link"
	);

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 *
	 * @param int $view_id
	 */
	public function __construct( $view_id = null ) {

		global $wpdb;

		$this->view_id = null == $view_id ? '' : $view_id;
		$table_suffix  = '' == $view_id ? '' : "_" . $view_id;

		$GravityView_Admin = new GravityView_Admin( $view_id );
		$GravityView_Admin->backend_actions();

		$this->view_data = GravityView_View_Data::getInstance( $view_id )->get_view( $view_id );

		if ( $this->view_data && isset( $this->view_data['fields']['directory_table-columns'] ) ) {
			$columns = $this->view_data['fields']['directory_table-columns'];
			$columns = array_values( $columns );
			$filters = get_post_meta( $this->view_id, '_gravityview_filters', true );

			if ( $filters ) {
				unset( $filters['mode'] );
				foreach ( $filters as $filter ) {
					$columns[] = array( 'id' => $filter['key'] );
				}
			}

			array_map( array( &$this, 'build_columns_array' ), $columns );

			//always make sure entry id is set
			if ( ! isset( $this->columns['id'] ) ) {
				$this->columns['id'] = "None";
			}

			//always make sure date_created is set
			if ( ! isset( $this->columns['date_created'] ) ) {
				$this->columns['date_created'] = "None";
			}

			//always make sure is_approved is set
			if ( ! isset( $this->columns['is_approved'] ) ) {
				$this->columns['is_approved'] = "";
			}

		} else {
			return;
		}

		$this->table_name  = $wpdb->prefix . 'gv_index' . $table_suffix;
		$this->primary_key = 'id';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_columns() {
		$columns = array_keys( $this->columns );
		$columns = array_combine( $columns, $columns );
		$columns = array_map( array( $this, 'get_field_type' ), $columns );

		return $columns;
	}

	/**
	 * Drop Index Table
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function drop_table() {

		global $wpdb;

		$drop_old_table = <<<SQL
			DROP TABLE `$this->table_name`
SQL;
		$drop_old_table = esc_sql( $drop_old_table );
		$result         = $wpdb->query( $drop_old_table );
	}

	/**
	 * @param $col
	 */
	private function build_columns_array( $col ) {

		$label = is_numeric( $col['id'] ) ? "field_" . $col['id'] : $col['id'];

		/**
		 * If the field is already being called lets create a new column
		 * @todo determine if this is useful
		 */
		if ( isset( $this->columns[ $label ] ) || 'custom' === $label ) {
			for ( $i = 0; $i < count( $this->columns ) + 1; $i ++ ) {
				$new_label = $label . "_{$i}";
				if ( ! isset( $this->columns[ $new_label ] ) ) {
					$label = $new_label;
					break;
				}
			}
		}

		$label = $this->sanitize_column_label( $label );

		$this->columns[ $label ] = $this->get_field_default( $col['id'] );
	}

	/**
	 * @param $key
	 *
	 * @return mixed|void
	 */
	private function sanitize_column_label( $key ) {
		$raw_key = $key;
		$key     = strtolower( $key );
		$key     = str_replace( ' ', '_', $key );
		$key     = preg_replace( '/[^a-z0-9_\.\-]/', '', $key );
		$key     = preg_replace( '/[.-]/', '_', $key );

		/**
		 * Filter a sanitized column name.
		 *
		 * @since 1.0
		 *
		 * @param string $key Sanitized key.
		 * @param string $raw_key The key prior to sanitization.
		 */
		return apply_filters( 'gv/gvdt/sanitize_column_name', $key, $raw_key );
	}


	/**
	 * Get default field values
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_column_defaults() {
		return $this->columns;
	}

	/**
	 * Retrieve entries from the database
	 *
	 * @access  public
	 * @since   1.0
	 *
	 * @param   array $args
	 * @param   bool $count Return only the total number of results found (optional)
	 *
	 * @return array|bool|int|mixed|null|object
	 */
	public function get_entries( $args = array(), $count = false ) {

		global $wpdb;

		if ( isset( $args['field_filters'] ) ) {
			$field_filter_array = array();
			$field_filters      = $args['field_filters'];
			foreach ( $field_filters as $field_filter => $val ) {
				if ( 'mode' !== $field_filter ) {
					if ( is_array( $val ) ) {
						$val = array_values( $val );
						list( $c, $v ) = $val;
						$field_filter_array[ $c ] = $v;
					}
				}
			}
			$args = $field_filter_array;
		}

		$defaults            = $this->get_column_defaults();
		$defaults['orderby'] = 'index_id';
		$defaults['order']   = 'DESC';

		$args = wp_parse_args( $args, $defaults );

		if ( isset( $args['number'] ) && $args['number'] < 1 ) {
			$args['number'] = PHP_INT_MAX;
		}

		$where = '';

		foreach ( $args as $col_name => $default_value ) {
			if ( ! empty( $default_value ) ) {
				if ( 'None' !== $default_value && 'date_created' !== $col_name && 'order' !== $col_name && 'orderby' !== $col_name && 'count' !== $col_name && 'status' !== $col_name ) {

					if ( is_array( $default_value ) ) {
						$default_value = implode( ',', $default_value );
					}

					$where .= $wpdb->prepare( "$col_name IN( %s ) ", $default_value );

				}
			} elseif ( ! empty( $default_value ) && 'date_created' == $col_name ) {

				if ( is_array( $default_value ) ) {

					if ( ! empty( $default_value['start'] ) ) {

						if ( false !== strpos( $default_value['start'], ':' ) ) {
							$format = 'Y-m-d H:i:s';
						} else {
							$format = 'Y-m-d 00:00:00';
						}

						$start = date( $format, strtotime( $default_value['start'] ) );

						if ( ! empty( $where ) ) {

							$where .= " AND `date` >= '{$start}'";

						} else {

							$where .= " WHERE `date` >= '{$start}'";

						}

					}

					if ( ! empty( $default_value['end'] ) ) {

						if ( false !== strpos( $default_value['end'], ':' ) ) {
							$format = 'Y-m-d H:i:s';
						} else {
							$format = 'Y-m-d 23:59:59';
						}

						$end = date( $format, strtotime( $default_value['end'] ) );

						if ( ! empty( $where ) ) {

							$where .= " AND `date` <= '{$end}'";

						} else {

							$where .= " WHERE `date` <= '{$end}'";

						}

					}

				} else {

					$year  = date( 'Y', strtotime( $default_value ) );
					$month = date( 'm', strtotime( $default_value ) );
					$day   = date( 'd', strtotime( $default_value ) );

					if ( empty( $where ) ) {
						$where .= " WHERE";
					} else {
						$where .= " AND";
					}

					$where .= " $year = YEAR ( date ) AND $month = MONTH ( date ) AND $day = DAY ( date )";
				}

			}
		}


		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? $this->primary_key : $args['orderby'];

		if ( 'total' === $args['orderby'] ) {
			$args['orderby'] = 'total+0';
		} else if ( 'subtotal' === $args['orderby'] ) {
			$args['orderby'] = 'subtotal+0';
		}

		$cache_key = ( true === $count ) ? md5( 'pw_entries_count' . serialize( $args ) ) : md5( 'pw_entries_' . serialize( $args ) );

		$results = wp_cache_get( $cache_key, 'entries' );

		if ( false === $results ) {

			if ( true === $count ) {

				if ( ! empty( $where ) ) {
					$where = "WHERE " . $where;
				}

				$sql     = <<<SQL
				SELECT COUNT($this->primary_key) FROM $this->table_name $where;
SQL;
				$results = absint(
					$wpdb->get_var( $sql ) );

			} else {

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$this->table_name} {$where} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d, %d;",
						absint( $args['offset'] ),
						absint( $args['number'] )
					)
				);

			}

			wp_cache_set( $cache_key, $results, 'entries', 3600 );

		}

		return $results;

	}

	/**
	 * Return the number of results found for a given query
	 *
	 * @param  array $args
	 *
	 * @see \GravityView_frontend::get_search_criteria
	 * @return int
	 */
	public function count( $args = array() ) {
		return $this->get_entries( $args, true );
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table_name = $this->table_name;

		$columns = $this->get_column_defaults();

		if ( empty( $columns ) ) {
			return;
		}

		//always make sure entry id is set
		if ( ! isset( $columns['id'] ) ) {
			$columns['id'] = "None";
		}

		//always make sure date_created is set
		if ( ! isset( $this->columns['date_created'] ) ) {
			$this->columns['date_created'] = "None";
		}

		//always make sure is_approved is set
		if ( ! isset( $this->columns['is_approved'] ) ) {
			$this->columns['is_approved'] = "";
		}

		$table_columns = $this->generate_table_column_string( $columns );

		$table_columns = implode(", ", $table_columns);

		$table_keys = ", PRIMARY KEY  (id),";
		$table_keys .= " KEY is_approved (is_approved),";
		$table_keys .= " KEY date_created (date_created),";
		$table_keys .= " KEY index_id (index_id)";

		$sql              = $this->format_table( $this->table_name, $table_columns, $table_keys );
		$existing_columns = $this->table_exists( $table_name ) ? $wpdb->get_col( "DESC {$table_name}", 0 ) : array();

		$new_columns = array_keys( $columns );

		//set index id to ensure it doesn't get dropped after the diff
		$new_columns[] = 'index_id';

		$dropped_columns = array_diff( $existing_columns, $new_columns );

		/**
		 * if $existing_columns and $new_columns match do not update
		 */
		if ( ! empty( $dropped_columns ) ) {
			foreach ( $dropped_columns as $dropped_column ) {
				unset( $existing_columns[ $dropped_column ] );
			}
		}

		$column_diff  = array_diff( $new_columns, $existing_columns );
		$index_id_key = array_search( 'index_id', $column_diff );
		unset( $column_diff[ $index_id_key ] );

		if ( ! empty( $column_diff ) ) {

			// Rather than executing an SQL query directly, we'll use the dbDelta function in wp-admin/includes/upgrade.php (we'll have to load this file, as it is not loaded by default)
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$result = dbDelta( $sql );

			if ( false !== $result ) {
				$this->handle_all( $this->view_id, $column_diff );
			}

			update_option( $this->table_name . '_db_version', $this->version );
		} else {

			$view_data = $this->view_data;
			$atts      = $view_data['atts'];
			$transient = get_transient( "gv_index_" . $this->view_id . "multisort" );

			if ( $transient ) {
				$this->handle_all( $this->view_id, $new_columns );
				delete_transient( "gv_index_" . $this->view_id . "multisort" );
			}

		}

		/**
		 * Drop columns after updated the DB structure if needed
		 */

		if ( ! empty( $dropped_columns ) ) {

			$tmp_table_name  = $table_name . "_tmp";
			$drop_table_name = $table_name . "_drop";

			//make sure index_id and id are at the front
			array_unshift( $new_columns, 'id' );
			array_unshift( $new_columns, 'index_id' );
			$new_columns = array_unique( $new_columns );

			//store new columns array as a comma separated string
			$tmp_col_copy = implode( ", ", $new_columns );

			$sql = $this->format_table( $tmp_table_name, $table_columns, $table_keys, false, true, false );

			//Copy table and applicable columns
			$create_tmp_table = "SELECT $tmp_col_copy FROM $table_name;";

			$result = $wpdb->query( $sql . $create_tmp_table );

			if ( false === $result ) {
				return;
			}

			//prep old table for drop
			$rename_curr_table = <<<SQL
			ALTER TABLE `$table_name` RENAME `$drop_table_name`			
SQL;
			$result            = $wpdb->query( $rename_curr_table );

			if ( false === $result ) {
				return;
			}
			//Make tmp table the new index table
			$rename_new_table = <<<SQL
			ALTER TABLE `$tmp_table_name` RENAME `$table_name`
SQL;
			$result           = $wpdb->query( $rename_new_table );

			if ( false === $result ) {
				return;
			}
			//Drop the old index table
			$drop_old_table = <<<SQL
			DROP TABLE `$drop_table_name`
SQL;
			$result         = $wpdb->query( $drop_old_table );

			if ( false === $result ) {
				return;
			}
		}


	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	private function get_field_default( $id ) {

		switch ( $id ):
			case 'post_id':
			case 'currency':
			case 'payment_status':
			case 'payment_date':
			case 'payment_amount':
			case 'payment_method':
			case 'transaction_id':
			case 'is_fulfilled':
			case 'created_by':
			case 'transaction_type':
				$field_type = null;
				break;
			case 'is_starred':
			case 'is_read':
				$field_type = 0;
				break;
			case 'id':
			case 'ip':
			case 'date_created':
				$field_type = 'None';
				break;
			case 'source_url':
			case 'user_agent':
				$field_type = '';
				break;
			case 'status':
				$field_type = 'active';
				break;
			default:
				$field_type = '';
		endswitch;

		return $field_type;
	}

	/**
	 * @param $key
	 *
	 * @return bool|int|string
	 * @internal param $value
	 *
	 */
	private function generate_column_schema( $key ) {
		switch ( $key ):
			case 'index_id':
			case 'post_id':
			case 'created_by':
				$field_default = 'bigint(20)';
				break;
			case 'is_starred':
			case 'is_read':
			case 'is_fulfilled':
			case 'transaction_type':
				$field_default = 'tinyint(1)';
				break;
			case 'id':
				$field_default = 'int(10)';
				break;
			case 'ip':
				$field_default = 'varchar(39)';
				break;
			case 'source_url':
				$field_default = 'varchar(200)';
				break;
			case 'user_agent':
				$field_default = 'varchar(250)';
				break;
			case 'currency':
				$field_default = 'varchar(5)';
				break;
			case 'payment_status':
				$field_default = 'varchar(15)';
				break;
			case 'payment_amount':
				$field_default = 'decimal(19.2)';
				break;
			case 'payment_method':
			case 'is_approved':
				$field_default = 'varchar(30)';
				break;
			case 'transaction_id':
				$field_default = 'varchar(50)';
				break;
			case 'status':
				$field_default = 'varchar(20)';
				break;
			case 'payment_date':
			case 'date_created':
				$field_default = 'datetime';
				break;
			default:
				$field_default = 'longtext';
		endswitch;

		return $field_default;
	}

	/**
	 * @param $key
	 *
	 * @return string
	 * @internal param $id
	 *
	 */
	private function get_field_type( $key ) {

		switch ( $key ):
			case 'post_id':
			case 'created_by':
			case 'is_starred':
			case 'is_read':
			case 'is_fulfilled':
			case 'transaction_type':
			case 'id':
				$field_default = '%d';
				break;
			case 'payment_amount':
				$field_default = '%f';
				break;
			case 'ip':
			case 'source_url':
			case 'user_agent':
			case 'currency':
			case 'payment_status':
			case 'payment_method':
			case 'transaction_id':
			case 'status':
			case 'payment_date':
			case 'date_created':
			default:
				$field_default = '%s';
		endswitch;

		return (string) $field_default;
	}

	private function format_table( $table_name, $table_columns, $table_keys = null, $charset_collate = null, $create = true, $end = true ) {
		global $wpdb;

		if ( null === $charset_collate ) {
			$charset_collate = $wpdb->get_charset_collate();
		} elseif ( $charset_collate === false ) {
			$charset_collate = "";
		}

		$table_columns = strtolower( $table_columns );

		$table_structure = "( $table_columns $table_keys )";

		$search_array  = array();
		$replace_array = array();

		$search_array[]  = "`";
		$replace_array[] = "";

		$table_structure = str_replace( $search_array, $replace_array, $table_structure );

		$create = true === $create ? "CREATE TABLE " : "";
		$end    = true === $end ? ";" : "";

		$sql = "$create";
		$sql .= "$table_name $table_structure $charset_collate";
		$sql .= "$end";

		// The dbDelta function examines the current table structure, compares it to the desired table structure, and either adds or modifies the table as necessary
		return $sql;
	}

	/**
	 * @param $data
	 * @param array $new_columns
	 *
	 * @return false|int
	 */
	public function update_index( $data, $new_columns = array() ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		//do_action( 'edd_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$table_name     = $this->table_name;
		$column_list    = implode( ", ", $data_keys );
		$column_formats = implode( ", ", $column_formats );
		$new_values     = array();

		if ( $data_keys !== $new_columns ) {

			$new_data = array_intersect_key( $data_keys, $new_columns );

			foreach ( $new_data as $key => $val ) {
				$value = '';
				if ( 'date_created' === $val ) {
					$value = strtotime( $data[ $val ] );
				}
				$new_values[] = "$val = $value";
			}

			$new_values = implode( ", ", $new_values );
		}

		if ( empty( $new_values ) ) {
			$new_values = "id=id";
		}

		$update_index_table = "REPLACE INTO `$table_name` ($column_list) VALUES ($column_formats)";

		$query = $wpdb->prepare( $update_index_table, $data );

		$result = $wpdb->query( $query );

		return $result;

		//error_log( "inserted: " . boolval($result) . "\n\t" . $wpdb->last_error );

		//do_action( 'edd_post_insert_' . $type, $wpdb->insert_id, $data );
	}


	/**
	 * Handle all
	 *
	 * @param $view_id
	 * @param array $new_columns
	 */
	public function handle_all( $view_id = null, $new_columns = array() ) {

		$post = get_post( $view_id );


		if ( 'gravityview' != $post->post_type ) {
			return;
		}

		delete_transient( "gv_index_" . $view_id );

		wp_queue( new WP_GVDT_Index_Job( null, $view_id, array(), 'sync-all', $new_columns ) );
	}

	private function generate_table_column_string( $columns ) {
		//index_id should always be the first column
		$table_columns   = array();
		$table_columns[] = "index_id bigint(20) NOT NULL AUTO_INCREMENT";

		//Entry ID should always be the second column
		$type = $this->generate_column_schema( 'id' );

		/**
		 * determine if we need a space
		 */
		$spacer = '';

		$default         = "";
		$not_null        = 'NOT NULL';
		$table_columns[] = "id " . $type . " " . $default . $spacer . $not_null;

		foreach ( $columns as $column_key => $value ) {

			if ( 'id' === $column_key ) {
				continue;
			}

			$type    = $this->generate_column_schema( $column_key );
			$default = '' === $value ? '""' : $value;

			/**
			 * determine if we need a space
			 */
			$spacer = "None" !== $default ? ' ' : '';

			$default         = "None" === $default || null === $default ? '' : "DEFAULT $default";
			$not_null        = null === $default ? '' : 'NOT NULL';
			$table_columns[] = $column_key . " " . $type . " " . $default . $spacer . $not_null;
		}


		return $table_columns;

	}


}