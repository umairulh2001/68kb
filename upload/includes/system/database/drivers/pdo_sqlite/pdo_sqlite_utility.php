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
 * PDO SQLite Utility Class
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_sqlite_utility extends CI_DB_utility {

	/**
	 * List databases
	 *
	 * Each SQLite database is a single file, so there is no server-side list.
	 *
	 * @access	private
	 * @return	bool
	 */
	function _list_databases()
	{
		if ($this->db->db_debug)
		{
			return $this->db->display_error('db_unsuported_feature');
		}
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Optimize table query
	 *
	 * SQLite has no per-table OPTIMIZE (VACUUM works on the whole database),
	 * so we report "not applicable" gracefully rather than erroring.
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	bool
	 */
	function _optimize_table($table)
	{
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Repair table query
	 *
	 * SQLite has no REPAIR TABLE equivalent.
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	bool
	 */
	function _repair_table($table)
	{
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * PDO SQLite Export / Backup
	 *
	 * The most reliable backup of a SQLite database is a byte-for-byte copy of
	 * the database file. Returning its contents lets the calling backup() flow
	 * wrap it in gzip/zip/txt and hand it to the download helper exactly like
	 * the MySQL SQL dump. Restore = decompress and use the resulting .sqlite.
	 *
	 * @access	private
	 * @param	array	Preferences
	 * @return	mixed
	 */
	function _backup($params = array())
	{
		$file = $this->db->database;

		if ($file == '' OR ! @is_file($file) OR ! @is_readable($file))
		{
			if ($this->db->db_debug)
			{
				return $this->db->display_error('db_unable_to_backup');
			}
			return FALSE;
		}

		// Checkpoint the WAL so the copied file contains all committed data.
		@$this->db->simple_query('PRAGMA wal_checkpoint(TRUNCATE);');

		$contents = @file_get_contents($file);

		return ($contents === FALSE) ? '' : $contents;
	}

}


/* End of file pdo_sqlite_utility.php */
/* Location: ./system/database/drivers/pdo_sqlite/pdo_sqlite_utility.php */
