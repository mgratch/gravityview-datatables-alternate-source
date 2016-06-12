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


		$this->view_data = GravityView_View_Data::getInstance()->get_view( $view_id );

		if ( $this->view_data ) {
			$columns = $this->view_data['fields']['directory_table-columns'];
			array_map( array( &$this, 'build_columns_array' ), $columns );
		}

		$this->table_name  = $wpdb->prefix . 'gv_index' . $table_suffix;
		$this->primary_key = 'index_id';
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
	 * @param $col
	 */
	private function build_columns_array( $col ) {

		$label = is_numeric( $col['id'] ) ? "field_" . $col['id'] : $col['id'];

		if ( isset( $this->columns[ $label ] ) ) {
			for ( $i = 0; $i < count( $this->columns ); $i ++ ) {
				if ( ! isset( $this->columns[ $label . "_{$i}" ] ) ) {
					$label = $label . "_{$i}";
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

		$defaults            = $this->get_columns();
		$defaults['orderby'] = 'index_id';
		$defaults['order']   = 'DESC';

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = '';

		foreach ( $args as $key => $value ) {
			if ( ! empty( $value ) && 'date_created' !== $key && 'order' !== $key && 'orderby' !== $key && 'count' !== $key ) {

				if ( is_array( $value ) ) {
					$values = implode( ',', $value );
				} else {
					$values = intval( $value );
				}

				$where .= "WHERE `$key` IN( {$values} ) ";

			} elseif ( ! empty( $value ) && 'date_created' == $key ) {

				if ( is_array( $value ) ) {

					if ( ! empty( $value['start'] ) ) {

						if ( false !== strpos( $value['start'], ':' ) ) {
							$format = 'Y-m-d H:i:s';
						} else {
							$format = 'Y-m-d 00:00:00';
						}

						$start = date( $format, strtotime( $value['start'] ) );

						if ( ! empty( $where ) ) {

							$where .= " AND `date` >= '{$start}'";

						} else {

							$where .= " WHERE `date` >= '{$start}'";

						}

					}

					if ( ! empty( $value['end'] ) ) {

						if ( false !== strpos( $value['end'], ':' ) ) {
							$format = 'Y-m-d H:i:s';
						} else {
							$format = 'Y-m-d 23:59:59';
						}

						$end = date( $format, strtotime( $value['end'] ) );

						if ( ! empty( $where ) ) {

							$where .= " AND `date` <= '{$end}'";

						} else {

							$where .= " WHERE `date` <= '{$end}'";

						}

					}

				} else {

					$year  = date( 'Y', strtotime( $value ) );
					$month = date( 'm', strtotime( $value ) );
					$day   = date( 'd', strtotime( $value ) );

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

				$results = absint( $wpdb->get_var( "SELECT COUNT({$this->primary_key}) FROM {$this->table_name} {$where};" ) );

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

		if ( ! isset( $columns['id'] ) ) {
			$columns['id'] = 0;
		}


		$table_columns = "index_id bigint(20) NOT NULL AUTO_INCREMENT,";

		$table_columns .= "\n\t";

		foreach ( $columns as $column_key => $value ) {
			$type    = $this->generate_column_schema( $column_key );
			$default = '' === $value ? '""' : $value;

			/**
			 * determine if we need a space
			 */
			$spacer = "None" !== $default ? ' ' : '';

			$default  = "None" === $default ? '' : "DEFAULT $default";
			$not_null = null === $default ? ',' : 'NOT NULL,';
			$table_columns .= $column_key . " " . $type . " " . $default . $spacer . $not_null . "\n\t";
		}

		$table_keys = "PRIMARY KEY  (index_id)";

		$sql = $this->format_table( $this->table_name, $table_columns, $table_keys );

		$existing_columns = $this->table_exists( $table_name ) ? $wpdb->get_col( "DESC {$table_name}", 0 ) : array();

		$new_columns = array_keys( $columns );

		//set index id to ensure it doesn't get dropped after the diff
		$new_columns[] = 'index_id';

		$columns_drop = array_diff( $existing_columns, $new_columns );

		/**
		 * if $existing_columns and $new_columns match do not update
		 */
		$dropped_columns = $columns_drop;

		if (! empty($dropped_columns)){
			foreach ( $dropped_columns as $dropped_column ) {
				unset( $existing_columns[ $dropped_column ] );
			}
		}

		$column_diff = array_diff( $new_columns, $existing_columns );

		if ( ! empty( $column_diff ) ) {
			dbDelta( $sql );
			update_option( $this->table_name . '_db_version', $this->version );
		} else {
			remove_action( 'save_post', array( GravityView_DataTables_Alt_DataSrc::get_instance(), 'handle_all' ), 30 );
		}

		/**
		 * Drop columns after updated the DB structure if needed
		 */

		if ( ! empty( $columns_drop ) ) {
			/**
			 * @todo look at efficient ways to drop columns
			 * @see http://stackoverflow.com/questions/23173789/mysql-drop-column-from-large-table#answer-23173871
			 */

			$tmp_table_name  = $table_name . "_tmp";
			$drop_table_name = $table_name . "_drop";

			//$tmp_col_copy = $table_name . ".";
			$tmp_col_copy = implode( ", ", $new_columns );

			$create_tmp_table = <<<SQL
			CREATE TABLE `$tmp_table_name` AS
				SELECT $tmp_col_copy
				FROM $table_name
SQL;
			$wpdb->query( $create_tmp_table );

			$rename_curr_table = <<<SQL
			ALTER TABLE `$table_name` RENAME `$drop_table_name`			
SQL;
			$wpdb->query( $rename_curr_table );

			$rename_new_table = <<<SQL
			ALTER TABLE `$tmp_table_name` RENAME `$table_name`
SQL;
			$wpdb->query( $rename_new_table );

			$drop_old_table = <<<SQL
			DROP TABLE `$drop_table_name`
SQL;
			$wpdb->query( $drop_old_table );
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

	private function format_table( $table_name, $table_columns, $table_keys = null, $charset_collate = null ) {
		global $wpdb;

		if ( $charset_collate == null ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$table_columns = strtolower( $table_columns );

		$table_structure = "( $table_columns $table_keys )";

		$search_array  = array();
		$replace_array = array();

		$search_array[]  = "`";
		$replace_array[] = "";

		$table_structure = str_replace( $search_array, $replace_array, $table_structure );

		$sql = "CREATE TABLE $table_name $table_structure $charset_collate;";

		// Rather than executing an SQL query directly, we'll use the dbDelta function in wp-admin/includes/upgrade.php (we'll have to load this file, as it is not loaded by default)
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// The dbDelta function examines the current table structure, compares it to the desired table structure, and either adds or modifies the table as necessary
		return $sql;
	}

	public function insert( $data, $type = '' ) {
		return parent::insert( $data, $type );
	}

}