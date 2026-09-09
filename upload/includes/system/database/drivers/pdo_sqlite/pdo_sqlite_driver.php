<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * PDO SQLite Database Adapter Class
 *
 * A PHP 7 compatible SQLite adapter built on PDO. It mirrors the structure of
 * CodeIgniter 1.7.2's bundled sqlite driver (which relied on the removed
 * sqlite_* SQLite2 extension) but talks to SQLite 3 through PDO.
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_sqlite_driver extends CI_DB {

	public $dbdriver = 'pdo_sqlite';

	// The character used to escape with - not needed for SQLite
	public $_escape_char = '';

	// clause and character used for LIKE escape sequences
	public $_like_escape_str = " ESCAPE '%s' ";
	public $_like_escape_chr = '!';

	/**
	 * The syntax to count rows is slightly different across different
	 * database engines, so this string appears in each driver and is
	 * used for the count_all() and count_all_results() functions.
	 */
	public $_count_string = 'SELECT COUNT(*) AS ';
	public $_random_keyword = ' RANDOM()'; // database specific random keyword

	// How long (in seconds) a blocked writer waits for a lock before failing
	// with SQLITE_BUSY. Exposed so it can be tuned from the database config if
	// desired (e.g. $db['default']['busy_timeout'] = 10;).
	public $busy_timeout = 5;

	/**
	 * Non-persistent database connection
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	function db_connect()
	{
		return $this->_connect();
	}

	// --------------------------------------------------------------------

	/**
	 * Persistent database connection
	 *
	 * SQLite locks the whole database file on write. Persistent PDO handles
	 * keep that lock alive across requests and make "database is locked"
	 * contention dramatically worse, so we deliberately never persist for
	 * SQLite regardless of the pconnect config value.
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	function db_pconnect()
	{
		return $this->_connect();
	}

	// --------------------------------------------------------------------

	/**
	 * Connect to PDO SQLite
	 *
	 * Opens the SQLite file (creating it if missing and the directory is
	 * writable) and applies the PRAGMAs that make SQLite usable under
	 * concurrent access.
	 *
	 * @access	private
	 * @return	object
	 */
	function _connect()
	{
		if ( ! class_exists('PDO') OR ! in_array('sqlite', PDO::getAvailableDrivers(), TRUE))
		{
			log_message('error', 'The pdo_sqlite driver requires the PDO and pdo_sqlite PHP extensions.');

			if ($this->db_debug)
			{
				$this->display_error('The PDO SQLite extension is not available on this server.', '', TRUE);
			}
			return FALSE;
		}

		$dsn = 'sqlite:'.$this->database;

		$timeout = (int) $this->busy_timeout;
		if ($timeout < 1)
		{
			$timeout = 5;
		}

		try
		{
			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
				PDO::ATTR_TIMEOUT => $timeout,
			);

			$conn = new PDO($dsn, NULL, NULL, $options);

			// --- Concurrency / integrity PRAGMAs --------------------------
			// busy_timeout: wait (and retry) up to N ms for a lock instead of
			//   failing instantly with SQLITE_BUSY ("database is locked").
			$conn->exec('PRAGMA busy_timeout = '.($timeout * 1000).';');
			// WAL: let readers proceed while a single writer is active — a big
			//   win for a read-heavy knowledge base. Persisted in the file
			//   header; re-asserting on each connect is harmless.
			$conn->exec('PRAGMA journal_mode = WAL;');
			// NORMAL is safe and much faster than FULL when WAL is enabled.
			$conn->exec('PRAGMA synchronous = NORMAL;');
			// Enforce declared foreign keys (off by default in SQLite).
			$conn->exec('PRAGMA foreign_keys = ON;');

			return $conn;
		}
		catch (PDOException $e)
		{
			log_message('error', 'PDO SQLite connection failed: '.$e->getMessage());

			if ($this->db_debug)
			{
				$this->display_error($e->getMessage(), '', TRUE);
			}
			return FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Reconnect
	 *
	 * Keep / reestablish the db connection if no queries have been
	 * sent for a length of time exceeding the server's idle timeout
	 *
	 * @access	public
	 * @return	void
	 */
	function reconnect()
	{
		// PDO SQLite doesn't require reconnection
	}

	// --------------------------------------------------------------------

	/**
	 * Select the database
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	function db_select()
	{
		// SQLite database is selected at connection time
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Set client character set
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	resource
	 */
	function db_set_charset($charset, $collation)
	{
		// SQLite doesn't support setting charset per connection
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Version number query string
	 *
	 * @access	public
	 * @return	string
	 */
	function _version()
	{
		return "SELECT sqlite_version() AS ver";
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * @access	private called by the base class
	 * @param	string	an SQL query
	 * @return	resource
	 */
	function _execute($sql)
	{
		$sql = $this->_prep_query($sql);

		$result = $this->conn_id->query($sql);

		// Invalidate cached schema metadata after any DDL. CodeIgniter caches
		// list_tables()/list_fields() results for the whole request, so without
		// this a table/column created mid-request (install & upgrade both do
		// this) would still look absent to table_exists()/field_exists().
		if (preg_match('/^\s*(?:CREATE|ALTER|DROP|RENAME)\b/i', $sql))
		{
			unset($this->data_cache['field_names'], $this->data_cache['table_names']);
		}

		return ($result !== FALSE) ? $result : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep the query
	 *
	 * If needed, each database adapter can prep the query string
	 *
	 * @access	private called by execute()
	 * @param	string	an SQL query
	 * @return	string
	 */
	function _prep_query($sql)
	{
		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	function trans_begin($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		// Reset the transaction failure flag.
		// If the $test_mode flag is set to TRUE transactions will be rolled back
		// even if the queries produce a successful result.
		$this->_trans_failure = ($test_mode === TRUE) ? TRUE : FALSE;

		$this->simple_query('BEGIN TRANSACTION');
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	function trans_commit()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		$this->simple_query('COMMIT');
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	function trans_rollback()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		$this->simple_query('ROLLBACK');
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Escape String
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	whether or not the string will be used in a LIKE condition
	 * @return	string
	 */
	function escape_str($str, $like = FALSE)
	{
		if (is_array($str))
		{
			foreach($str as $key => $val)
	   		{
				$str[$key] = $this->escape_str($val, $like);
	   		}

	   		return $str;
	   	}

		$str = str_replace("'", "''", $str);

		// escape LIKE condition wildcards
		if ($like === TRUE)
		{
			$str = str_replace(	array('%', '_', $this->_like_escape_chr),
								array($this->_like_escape_chr.'%', $this->_like_escape_chr.'_', $this->_like_escape_chr.$this->_like_escape_chr),
								$str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @access	public
	 * @return	integer
	 */
	function affected_rows()
	{
		if ( ! is_object($this->result_id))
		{
			return 0;
		}
		return $this->result_id->rowCount();
	}

	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @access	public
	 * @return	integer
	 */
	function insert_id()
	{
		return $this->conn_id->lastInsertId();
	}

	// --------------------------------------------------------------------

	/**
	 * "Count All" query
	 *
	 * Generates a platform-specific query string that counts all records in
	 * the specified database
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function count_all($table = '')
	{
		if ($table == '')
		{
			return 0;
		}

		$query = $this->query($this->_count_string . $this->_protect_identifiers('numrows') . " FROM " . $this->_protect_identifiers($table, TRUE, NULL, FALSE));

		if ($query->num_rows() == 0)
		{
			return 0;
		}

		$row = $query->row();
		return (int) $row->numrows;
	}

	// --------------------------------------------------------------------

	/**
	 * List table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @access	private
	 * @param	boolean
	 * @return	string
	 */
	function _list_tables($prefix_limit = FALSE)
	{
		$sql = "SELECT name FROM sqlite_master WHERE type='table'";

		if ($prefix_limit !== FALSE AND $this->dbprefix != '')
		{
			$sql .= " AND name LIKE '" . $this->escape_like_str($this->dbprefix) . "%' ";
		}
		return $sql;
	}

	// --------------------------------------------------------------------

	/**
	 * Show column query
	 *
	 * Generates a platform-specific query string so that the column names can be fetched
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	string
	 */
	function _list_columns($table = '')
	{
		// Use pragma_table_info() as a table-valued function so the column can be
		// aliased to COLUMN_NAME, which is what CI's list_fields() looks for.
		// PRAGMA table_info returns rows whose first column is 'cid' (an integer),
		// which breaks list_fields() / field_exists() when they call current($row).
		// Requires SQLite 3.16.0+.
		$tbl = $this->_protect_identifiers($table, TRUE, NULL, FALSE);
		return "SELECT name AS COLUMN_NAME FROM pragma_table_info('"
			. str_replace("'", "''", $tbl) . "')";
	}

	// --------------------------------------------------------------------

	/**
	 * Field data query
	 *
	 * Generates a platform-specific query so that the column data can be retrieved
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	object
	 */
	function _field_data($table)
	{
		return "PRAGMA table_info(" . $this->_protect_identifiers($table, TRUE, NULL, FALSE) . ")";
	}

	// --------------------------------------------------------------------

	/**
	 * The error message string
	 *
	 * @access	private
	 * @return	string
	 */
	function _error_message()
	{
		if ( ! is_object($this->conn_id))
		{
			return '';
		}
		$error = $this->conn_id->errorInfo();
		return isset($error[2]) ? $error[2] : '';
	}

	// --------------------------------------------------------------------

	/**
	 * The error message number
	 *
	 * @access	private
	 * @return	integer
	 */
	function _error_number()
	{
		if ( ! is_object($this->conn_id))
		{
			return 0;
		}
		$error = $this->conn_id->errorInfo();
		return isset($error[1]) ? $error[1] : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Escape the SQL Identifiers
	 *
	 * This function escapes column and table names
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _escape_identifiers($item)
	{
		if ($this->_escape_char == '')
		{
			return $item;
		}

		foreach ($this->_reserved_identifiers as $id)
		{
			if (strpos($item, '.'.$id) !== FALSE)
			{
				$str = $this->_escape_char. str_replace('.', $this->_escape_char.'.', $item);

				// remove duplicates if the user already included the escape
				return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
			}
		}

		if (strpos($item, '.') !== FALSE)
		{
			$str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item).$this->_escape_char;
		}
		else
		{
			$str = $this->_escape_char.$item.$this->_escape_char;
		}

		// remove duplicates if the user already included the escape
		return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * From Tables
	 *
	 * This function implicitly groups FROM tables so there is no confusion
	 * about operator precedence in harmony with SQL standards
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _from_tables($tables)
	{
		if ( ! is_array($tables))
		{
			$tables = array($tables);
		}

		return '(' . implode(', ', $tables) . ')';
	}

	// --------------------------------------------------------------------

	/**
	 * Insert statement
	 *
	 * Generates a platform-specific insert string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the insert keys
	 * @param	array	the insert values
	 * @return	string
	 */
	function _insert($table, $keys, $values)
	{
		return "INSERT INTO " . $table . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
	}

	// --------------------------------------------------------------------

	/**
	 * Update statement
	 *
	 * Generates a platform-specific update string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the update data
	 * @param	array	the where clause
	 * @param	array	the orderby clause
	 * @param	array	the limit clause
	 * @return	string
	 */
	function _update($table, $values, $where, $orderby = array(), $limit = FALSE)
	{
		foreach($values as $key => $val)
		{
			$valstr[] = $key . " = " . $val;
		}

		$limit = ( ! $limit) ? '' : ' LIMIT ' . $limit;

		$orderby = (count($orderby) >= 1) ? ' ORDER BY ' . implode(", ", $orderby) : '';

		$sql = "UPDATE " . $table . " SET " . implode(', ', $valstr);

		$sql .= ($where != '' AND count($where) >=1) ? " WHERE " . implode(" ", $where) : '';

		$sql .= $orderby . $limit;

		return $sql;
	}


	// --------------------------------------------------------------------

	/**
	 * Truncate statement
	 *
	 * Generates a platform-specific truncate string from the supplied data
	 * If the database does not support the truncate() command
	 * This function maps to "DELETE FROM table"
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	string
	 */
	function _truncate($table)
	{
		return "DELETE FROM " . $table;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete statement
	 *
	 * Generates a platform-specific delete string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the where clause
	 * @param	string	the limit clause
	 * @return	string
	 */
	function _delete($table, $where = array(), $like = array(), $limit = FALSE)
	{
		$conditions = '';

		if (count($where) > 0 OR count($like) > 0)
		{
			$conditions = "\nWHERE ";
			$conditions .= implode("\n", $this->ar_where);

			if (count($where) > 0 && count($like) > 0)
			{
				$conditions .= " AND ";
			}
			$conditions .= implode("\n", $like);
		}

		$limit = ( ! $limit) ? '' : ' LIMIT ' . $limit;

		return "DELETE FROM " . $table . $conditions . $limit;
	}

	// --------------------------------------------------------------------

	/**
	 * Limit string
	 *
	 * Generates a platform-specific LIMIT clause
	 *
	 * @access	public
	 * @param	string	the sql query string
	 * @param	integer	the number of rows to limit the query to
	 * @param	integer	the offset value
	 * @return	string
	 */
	function _limit($sql, $limit, $offset)
	{
		if ($offset == 0)
		{
			$offset = '';
		}
		else
		{
			$offset .= ", ";
		}

		return $sql . "LIMIT " . $offset . $limit;
	}

	// --------------------------------------------------------------------

	/**
	 * Close DB Connection
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	function _close($conn_id)
	{
		$this->conn_id = NULL;
	}


}


/* End of file pdo_sqlite_driver.php */
/* Location: ./system/database/drivers/pdo_sqlite/pdo_sqlite_driver.php */
