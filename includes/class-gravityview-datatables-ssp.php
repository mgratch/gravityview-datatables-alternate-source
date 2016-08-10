<?php

/**
 * Created by PhpStorm.
 * User: Marc
 * Date: 6/24/2016
 * Time: 11:35 AM
 */
class GravityView_DataTables_SSP {

	private static $view_id;

	/**
	 * Create the data output array for the DataTables rows
	 *
	 * @param  array $columns Column information array
	 * @param  array $data Data from the SQL get
	 *
	 * @return array Formatted data in a row based format
	 *
	 * @internal param $table
	 *
	 * @todo Process GV Data i.e. links and custom content as well
	 */
	static function data_output( $columns, $data ) {

		$view_id = self::$view_id;

		//get the view_data without relying on GV internal methods
		$gravityview_directory_fields = get_post_meta( $view_id, '_gravityview_directory_fields', true );

		/**
		 * Remove directory field keys
		 * @todo currently this plugin only works for DataTables Views but let's leave options open
		 * @todo remove array index magic number `0`
		 */
		$gravityview_directory_fields = array_values( $gravityview_directory_fields );
		$gravityview_directory_fields = $gravityview_directory_fields[0];

		//remove anonymizing field keys
		$gravityview_directory_fields = array_values( $gravityview_directory_fields );
		$index_custom_data            = apply_filters( 'gv_index_custom_content', $answer = false, $view_id );

		$out = array();

		for ( $i = 0, $ien = count( $data ); $i < $ien; $i ++ ) {
			$row = array();

			for ( $j = 0, $jen = count( $columns ); $j < $jen; $j ++ ) {
				$column = $columns[ $j ];


				/**
				 * Process Custom Content
				 * @todo do not run if processed custom content is already stored
				 * @todo find a better way to handle links
				 */


				//field indexes in the `$gravityview_directory_fields` array should match column index `$j`
				if ( isset( $gravityview_directory_fields[ $j ] ) ) {

					$field_setting = $gravityview_directory_fields[ $j ];

					if ( ( ! isset( $gravityview_directory_fields[ $j ]['show_as_link'] ) &&
					       'edit_link' == $gravityview_directory_fields[ $j ]['id'] ||
					       'delete_link' == $gravityview_directory_fields[ $j ]['id'] ||
					       'date_created' === $gravityview_directory_fields[ $j ]['id'] ) ||
					     ( isset( $gravityview_directory_fields[ $j ]['show_as_link'] ) &&
					       '0' !== $gravityview_directory_fields[ $j ]['show_as_link'] ) ||
					     ( false !== strpos( $column['db'], 'custom_' ) && ! $index_custom_data )
					) {

						//Get the processed field value
						$entry = GFAPI::get_entry( $data[ $i ]['id'] );

						//If an ID exists in the index but not the lead table skip it and clean up during health checks
						$entry       = is_array( $entry ) ? $entry : null;
						$field_value = GravityView_API::field_value( $entry, $field_setting );

						//a single string is expected
						if ( is_array( $field_value ) ) {
							$field_value                 = array_values( $field_value );
							$data[ $i ][ $column['db'] ] = $field_value[0];
						} else {
							$data[ $i ][ $column['db'] ] = $field_value;
						}
					}
				}

				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
					$row[ $column['dt'] ] =  $column['formatter']( $data[ $i ][ $column['db'] ], $data[ $i ] );
				} else {
					$row[ $column['dt'] ] =  $data[ $i ][ $columns[ $j ]['db'] ];
				}
			}

			$out[] = $row;
		}

		return $out;
	}


	/**
	 * Database connection
	 *
	 * Obtain an PHP PDO connection from a connection details array
	 *
	 * @param  array $conn SQL connection details. The array should have
	 *    the following properties
	 *     * host - host name
	 *     * db   - database name
	 *     * user - user name
	 *     * pass - user password
	 *
	 * @return resource PDO connection
	 */
	static function db( $conn ) {
		if ( is_array( $conn ) ) {
			return self::sql_connect( $conn );
		}

		return $conn;
	}


	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 * @param  array $request Data sent to server by DataTables
	 * @param  array $columns Column information array
	 *
	 * @return string SQL limit clause
	 */
	static function limit( $request, $columns ) {
		$limit = '';

		if ( isset( $request['start'] ) && $request['length'] != - 1 ) {
			$limit = "LIMIT " . intval( $request['start'] ) . ", " . intval( $request['length'] );
		}

		return $limit;
	}


	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 * @param  array $request Data sent to server by DataTables
	 * @param  array $columns Column information array
	 *
	 * @return string SQL order by clause
	 */
	static function order( $request, $columns ) {
		$order = '';

		if ( isset( $request['order'] ) && count( $request['order'] ) ) {
			$orderBy   = array();
			$dtColumns = self::pluck( $columns, 'dt' );

			for ( $i = 0, $ien = count( $request['order'] ); $i < $ien; $i ++ ) {
				// Convert the column index into the column data property
				$columnIdx     = intval( $request['order'][ $i ]['column'] );
				$requestColumn = $request['columns'][ $columnIdx ];

				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column    = $columns[ $columnIdx ];

				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $request['order'][ $i ]['dir'] === 'asc' ?
						'ASC' :
						'DESC';

					$orderBy[] = '`' . $column['db'] . '` ' . $dir;
				}
			}

			$order = 'ORDER BY ' . implode( ', ', $orderBy );
		}

		return $order;
	}


	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 * @param  array $request Data sent to server by DataTables
	 * @param  array $columns Column information array
	 * @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *
	 * @return string SQL where clause
	 */
	static function filter( $request, $columns, &$bindings ) {
		$globalSearch = array();
		$columnSearch = array();
		$dtColumns    = self::pluck( $columns, 'dt' );

		if ( isset( $request['search'] ) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			for ( $i = 0, $ien = count( $request['columns'] ); $i < $ien; $i ++ ) {
				$requestColumn = $request['columns'][ $i ];
				$columnIdx     = array_search( $requestColumn['data'], $dtColumns );
				$column        = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {
					$binding        = self::bind( $bindings, '%' . $str . '%', PDO::PARAM_STR );
					$globalSearch[] = "`" . $column['db'] . "` LIKE " . $binding;
				}
			}
		}

		// Individual column filtering
		if ( isset( $request['columns'] ) ) {
			for ( $i = 0, $ien = count( $request['columns'] ); $i < $ien; $i ++ ) {
				$requestColumn = $request['columns'][ $i ];
				$columnIdx     = array_search( $requestColumn['data'], $dtColumns );
				$column        = $columns[ $columnIdx ];

				$str = $requestColumn['search']['value'];

				if ( $requestColumn['searchable'] == 'true' &&
				     $str != ''
				) {
					$binding        = self::bind( $bindings, '%' . $str . '%', PDO::PARAM_STR );
					$columnSearch[] = "`" . $column['db'] . "` LIKE " . $binding;
				}
			}
		}

		// Combine the filters into a single string
		$where = '';

		if ( count( $globalSearch ) ) {
			$where = '(' . implode( ' OR ', $globalSearch ) . ')';
		}

		if ( count( $columnSearch ) ) {
			$where = $where === '' ?
				implode( ' AND ', $columnSearch ) :
				$where . ' AND ' . implode( ' AND ', $columnSearch );
		}

		if ( $where !== '' ) {
			$where = 'WHERE ' . $where;
		}

		return $where;
	}


	/**
	 * Perform the SQL queries needed for an server-side processing requested,
	 * utilising the helper functions of this class, limit(), order() and
	 * filter() among others. The returned array is ready to be encoded as JSON
	 * in response to an SSP request, or can be modified if needed before
	 * sending back to the client.
	 *
	 * @param  array $request Data sent to server by DataTables
	 * @param  array|PDO $conn PDO connection resource or connection parameters array
	 * @param  string $table SQL table to query
	 * @param  string $primaryKey Primary key of the table
	 * @param  array $columns Column information array
	 *
	 * @return array          Server-side processing response array
	 */
	static function simple( $request, $conn, $table, $primaryKey, $columns ) {

		self::$view_id = $request['view_id'];

		$bindings = array();
		$db       = self::db( $conn );

		// Build the SQL query string from the request
		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$where = self::filter( $request, $columns, $bindings );

		// Main query to actually get the data
		$data = self::sql_exec( $db, $bindings,
			"SELECT `" . implode( "`, `", self::pluck( $columns, 'db' ) ) . "`
			 FROM `$table`
			 $where
			 $order
			 $limit"
		);

		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db, $bindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`"
		);
		$recordsTotal   = $resTotalLength[0][0];

		/*
		 * Output
		 */

		return array(
			"draw"            => isset ( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			"recordsTotal"    => intval( $recordsTotal ),
			"recordsFiltered" => intval( $recordsFiltered ),
			"data"            => self::data_output( $columns, $data, $table )
		);
	}


	/**
	 * The difference between this method and the `simple` one, is that you can
	 * apply additional `where` conditions to the SQL queries. These can be in
	 * one of two forms:
	 *
	 * * 'Result condition' - This is applied to the result set, but not the
	 *   overall paging information query - i.e. it will not effect the number
	 *   of records that a user sees they can have access to. This should be
	 *   used when you want apply a filtering condition that the user has sent.
	 * * 'All condition' - This is applied to all queries that are made and
	 *   reduces the number of records that the user can access. This should be
	 *   used in conditions where you don't want the user to ever have access to
	 *   particular records (for example, restricting by a login id).
	 *
	 * @param  array $request Data sent to server by DataTables
	 * @param  array|PDO $conn PDO connection resource or connection parameters array
	 * @param  string $table SQL table to query
	 * @param  string $primaryKey Primary key of the table
	 * @param  array $columns Column information array
	 * @param  string $whereResult WHERE condition to apply to the result set
	 * @param  string $whereAll WHERE condition to apply to all queries
	 *
	 * @return array          Server-side processing response array
	 */
	static function complex( $request, $conn, $table, $primaryKey, $columns, $whereResult = null, $whereAll = null ) {
		$bindings         = array();
		$db               = self::db( $conn );
		$localWhereResult = array();
		$localWhereAll    = array();
		$whereAllSql      = '';

		self::$view_id = $request['view_id'];

		// Build the SQL query string from the request
		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$where = self::filter( $request, $columns, $bindings );

		$whereResult = self::_flatten( $whereResult );
		$whereAll    = self::_flatten( $whereAll );

		if ( $whereResult ) {
			$where = $where ?
				$where . ' AND ' . $whereResult :
				'WHERE ' . $whereResult;
		}

		if ( $whereAll ) {
			$where = $where ?
				$where . ' AND ' . $whereAll :
				'WHERE ' . $whereAll;

			$whereAllSql = 'WHERE ' . $whereAll;
		}

		/**
		 * Main query to actually get the data
		 */
		$data = self::sql_exec( $db, $bindings,
			"SELECT `" . implode( "`, `", self::pluck( $columns, 'db' ) ) . "`
			 FROM `$table`
			 $where
			 $order
			 $limit"
		);

		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db, $bindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db, $bindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table` " .
			$whereAllSql
		);
		$recordsTotal   = $resTotalLength[0][0];

		/*
		 * Output
		 */

		return array(
			"draw"            => isset ( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			"recordsTotal"    => intval( $recordsTotal ),
			"recordsFiltered" => intval( $recordsFiltered ),
			"data"            => self::data_output( $columns, $data )
		);
	}


	/**
	 * Connect to the database
	 *
	 * @param  array $sql_details SQL server connection details array, with the
	 *   properties:
	 *     * host - host name
	 *     * db   - database name
	 *     * user - user name
	 *     * pass - user password
	 *
	 * @return resource Database connection handle
	 */
	static function sql_connect( $sql_details ) {
		global $wpdb;

		try {
			$db = @new PDO(
				"mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset={$sql_details['charset']}",
				$sql_details['user'],
				$sql_details['pass'],
				array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
			);
		} catch ( PDOException $e ) {
			self::fatal(
				"An error occurred while connecting to the database. " .
				"The error reported by the server was: " . $e->getMessage()
			);
		}

		return $db;
	}


	/**
	 * Execute an SQL query on the database
	 *
	 * @param  resource $db Database handler
	 * @param  array $bindings Array of PDO binding values from bind() to be
	 *   used for safely escaping strings. Note that this can be given as the
	 *   SQL query string if no bindings are required.
	 * @param  string $sql SQL query to execute.
	 *
	 * @return array         Result from the query (all rows)
	 */
	static function sql_exec( $db, $bindings, $sql = null ) {

		// Argument shifting
		if ( $sql === null ) {
			$sql = $bindings;
		}

		$stmt = $db->prepare( $sql );

		//echo $sql;

		// Bind parameters
		if ( is_array( $bindings ) ) {
			for ( $i = 0, $ien = count( $bindings ); $i < $ien; $i ++ ) {
				$binding = $bindings[ $i ];
				$stmt->bindValue( $binding['key'], $binding['val'], $binding['type'] );
			}
		}

		// Execute
		try {
			$stmt->execute();
		} catch ( PDOException $e ) {
			self::fatal( "An SQL error occurred: " . $e->getMessage() );
		}

		// Return all
		return $stmt->fetchAll( PDO::FETCH_BOTH );
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */

	/**
	 * Throw a fatal error.
	 *
	 * This writes out an error message in a JSON string which DataTables will
	 * see and show to the user in the browser.
	 *
	 * @param  string $msg Message to send to the client
	 */
	static function fatal( $msg ) {
		echo json_encode( array(
			"error" => $msg
		) );

		exit( 0 );
	}

	/**
	 * Create a PDO binding key which can be used for escaping variables safely
	 * when executing a query with sql_exec()
	 *
	 * @param  array &$a Array of bindings
	 * @param  *      $val  Value to bind
	 * @param  int $type PDO field type
	 *
	 * @return string       Bound key to be used in the SQL where this parameter
	 *   would be used.
	 */
	static function bind( &$a, $val, $type ) {
		$key = ':binding_' . count( $a );

		$a[] = array(
			'key'  => $key,
			'val'  => $val,
			'type' => $type
		);

		return $key;
	}


	/**
	 * Pull a particular property from each assoc. array in a numeric array,
	 * returning and array of the property values from each item.
	 *
	 * @param  array $a Array to get data from
	 * @param  string $prop Property to read
	 *
	 * @return array        Array of property values
	 */
	static function pluck( $a, $prop ) {
		$out = array();

		for ( $i = 0, $len = count( $a ); $i < $len; $i ++ ) {
			$out[] = $a[ $i ][ $prop ];
		}

		return $out;
	}


	/**
	 * Return a string from an array or a string
	 *
	 * @param  array|string $a Array to join
	 * @param  string $join Glue for the concatenation
	 *
	 * @return string Joined string
	 */
	static function _flatten( $a, $join = ' AND ' ) {
		if ( ! $a ) {
			return '';
		} else if ( $a && is_array( $a ) ) {
			return implode( $join, $a );
		}

		return $a;
	}

}