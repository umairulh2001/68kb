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
 * PDO SQLite Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_sqlite_result extends CI_DB_result {

	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_rows()
	{
		// PDO's rowCount() is unreliable for SELECT queries in SQLite
		// (always returns 0). We cache the row count in result_id->row_count
		// after the first fetch operation.
		if (is_object($this->result_id) && isset($this->result_id->row_count))
		{
			return $this->result_id->row_count;
		}

		// If result_array hasn't been populated yet, fetch it now
		// This ensures num_rows() works even when called before result_array()
		if (count($this->result_array) == 0 && $this->result_id !== FALSE)
		{
			$this->result_array();
			return is_object($this->result_id) ? $this->result_id->row_count : count($this->result_array);
		}

		return count($this->result_array);
	}

	// --------------------------------------------------------------------

	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_fields()
	{
		return is_object($this->result_id) ? $this->result_id->columnCount() : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Field Names
	 *
	 * Generates an array of column names
	 *
	 * @access	public
	 * @return	array
	 */
	function list_fields()
	{
		if ($this->db->db_debug)
		{
			return $this->db->display_error('db_unsuported_feature');
		}
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Field data
	 *
	 * Generates an array of objects containing field meta-data
	 *
	 * @access	public
	 * @return	array
	 */
	function field_data()
	{
		if ($this->db->db_debug)
		{
			return $this->db->display_error('db_unsuported_feature');
		}
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Free the result
	 *
	 * @return	null
	 */
	function free_result()
	{
		if (is_object($this->result_id))
		{
			$this->result_id = FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Data Seek
	 *
	 * Moves the internal pointer to the desired offset.  We call
	 * this internally before fetching results to make sure the
	 * result set starts at zero
	 *
	 * @access	private
	 * @return	array
	 */
	function _data_seek($n = 0)
	{
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Query result.  Acts as a wrapper function for the following functions.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	mixed
	 */
	function result_array()
	{
		if (count($this->result_array) > 0)
		{
			return $this->result_array;
		}

		if ($this->result_id === FALSE)
		{
			return array();
		}

		$this->_data_seek(0);
		while ($row = $this->_fetch_assoc())
		{
			$this->result_array[] = $row;
		}

		// Cache the row count on the result_id object for num_rows()
		if (is_object($this->result_id))
		{
			$this->result_id->row_count = count($this->result_array);
		}

		return $this->result_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Query result.  "object" version.
	 *
	 * @access	public
	 * @return	array
	 */
	function result_object()
	{
		if (count($this->result_object) > 0)
		{
			return $this->result_object;
		}

		// In the event that query caching is on the result_id variable
		// will return FALSE since there isn't a valid SQL resource so
		// we'll simply return an empty array.
		if ($this->result_id === FALSE)
		{
			return array();
		}

		// Ensure result_array is populated first (which sets row_count)
		// Then convert arrays to objects
		$array_result = $this->result_array();
		foreach ($array_result as $row)
		{
			$this->result_object[] = (object) $row;
		}

		return $this->result_object;
	}

	// --------------------------------------------------------------------

	/**
	 * Result - associative array
	 *
	 * Returns the result set as an array
	 *
	 * @access	private
	 * @return	array
	 */
	function _fetch_assoc()
	{
		return $this->result_id->fetch(PDO::FETCH_ASSOC);
	}

	// --------------------------------------------------------------------

	/**
	 * Result - object
	 *
	 * Returns the result set as an object
	 *
	 * @access	private
	 * @return	object
	 */
	function _fetch_object()
	{
		return $this->result_id->fetch(PDO::FETCH_OBJ);
	}

}


/* End of file pdo_sqlite_result.php */
/* Location: ./system/database/drivers/pdo_sqlite/pdo_sqlite_result.php */
