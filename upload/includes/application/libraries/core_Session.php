<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 68KB
 *
 * An open source knowledge base script
 *
 * @package		68kb
 * @author		68kb Dev Team
 * @copyright	Copyright (c) 2009, 68 Designs, LLC
 * @license		http://68kb.com/user_guide/license.html
 * @link		http://68kb.com
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * 68KB Core Session
 *
 * Extends CI_Session (autoloaded on every controller request) to:
 *  - control the session lifetime from the admin "Login session timeout"
 *    setting (settings.session_timeout, minutes) instead of the
 *    server-protected config file (sess_expiration);
 *  - enforce the admin "Force HTTPS" setting (settings.force_https): plain
 *    http GET/HEAD requests are redirected to https before anything else runs;
 *  - write the session cookie HttpOnly always, and Secure on https requests.
 *
 * Falls back to config/defaults whenever the settings table or a row is
 * missing (e.g. during setup) or holds an invalid value.
 *
 * @package		68kb
 * @subpackage	Libraries
 * @category	Libraries
 */
class core_Session extends CI_Session {

	// Accepted range for the session_timeout setting (minutes).
	const TIMEOUT_MIN = 5;
	const TIMEOUT_MAX = 43200;

	function __construct($params = array())
	{
		if ( ! is_array($params))
		{
			$params = array();
		}

		$settings = $this->_read_settings(array('session_timeout', 'force_https'));

		if (isset($settings['force_https']) && $settings['force_https'] == 'Y')
		{
			$this->_enforce_https();
		}

		$minutes = $this->_valid_timeout(isset($settings['session_timeout']) ? $settings['session_timeout'] : NULL);
		if ($minutes !== FALSE)
		{
			$params['sess_expiration'] = $minutes * 60;
			// Refresh last_activity at least twice per lifetime so an active
			// user is never expired by a short timeout (default refresh 300s).
			$params['sess_time_to_update'] = (int) min(300, floor($params['sess_expiration'] / 2));
		}

		parent::__construct($params);
	}

	// ------------------------------------------------------------------------

	/**
	 * Read settings rows straight from the db.
	 *
	 * init_model is not loaded yet when the autoloaded session starts, so
	 * this queries directly. db_debug is on, so only query once the settings
	 * table is known to exist.
	 *
	 * @access	private
	 * @param	array	short_names
	 * @return	array	short_name => value (missing rows are absent)
	 */
	function _read_settings($names)
	{
		$CI =& get_instance();

		if ( ! isset($CI->db) OR ! is_object($CI->db) OR ! $CI->db->table_exists('settings'))
		{
			return array();
		}

		$query = $CI->db->select('short_name, value')->from('settings')->where_in('short_name', $names)->get();
		$out = array();
		if ($query)
		{
			foreach ($query->result() as $row)
			{
				$out[$row->short_name] = trim((string) $row->value);
			}
		}
		return $out;
	}

	// ------------------------------------------------------------------------

	/**
	 * Validate a session_timeout value.
	 *
	 * @access	private
	 * @return	mixed	int minutes, or FALSE to keep the config default
	 */
	function _valid_timeout($value)
	{
		if ($value === NULL OR ! ctype_digit((string) $value))
		{
			return FALSE;
		}
		$value = (int) $value;
		return ($value < self::TIMEOUT_MIN OR $value > self::TIMEOUT_MAX) ? FALSE : $value;
	}

	// ------------------------------------------------------------------------

	/**
	 * Redirect a plain-http request to https (Force HTTPS setting).
	 *
	 * - Only GET/HEAD: redirecting a POST would drop its body.
	 * - 302, not 301, so turning the setting off takes effect immediately
	 *   (browsers cache 301s).
	 * - Never on localhost, so a production db copy can't break local dev.
	 *
	 * @access	private
	 * @return	void
	 */
	function _enforce_https()
	{
		if (core_Config::is_https())
		{
			return;
		}

		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		if (($method != 'GET' && $method != 'HEAD') OR $host == '')
		{
			return;
		}

		$hostname = strtolower(preg_replace('#:\d+$#', '', $host));
		if (in_array($hostname, array('localhost', '127.0.0.1', '[::1]')))
		{
			return;
		}

		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		header('Location: https://'.$hostname.$uri, TRUE, 302);
		exit;
	}

	// ------------------------------------------------------------------------

	/**
	 * Write the session cookie (CI_Session::_set_cookie with hardened flags).
	 *
	 * @access	public
	 * @return	void
	 */
	function _set_cookie($cookie_data = NULL)
	{
		if (is_null($cookie_data))
		{
			$cookie_data = $this->userdata;
		}

		// Serialize the userdata for the cookie
		$cookie_data = $this->_serialize($cookie_data);

		if ($this->sess_encrypt_cookie == TRUE)
		{
			$cookie_data = $this->CI->encrypt->encode($cookie_data);
		}
		else
		{
			// if encryption is not used, we provide an md5 hash to prevent userside tampering
			$cookie_data = $cookie_data.md5($cookie_data.$this->encryption_key);
		}

		setcookie(
					$this->sess_cookie_name,
					$cookie_data,
					$this->sess_expiration + time(),
					$this->cookie_path,
					$this->cookie_domain,
					core_Config::is_https(),
					TRUE
				);
	}

	// ------------------------------------------------------------------------

	/**
	 * Destroy the session (CI_Session::sess_destroy with matching flags).
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_destroy()
	{
		if ($this->sess_use_database === TRUE AND isset($this->userdata['session_id']))
		{
			$this->CI->db->where('session_id', $this->userdata['session_id']);
			$this->CI->db->delete($this->sess_table_name);
		}

		setcookie(
					$this->sess_cookie_name,
					addslashes(serialize(array())),
					($this->now - 31500000),
					$this->cookie_path,
					$this->cookie_domain,
					core_Config::is_https(),
					TRUE
				);
	}
}

/* End of file core_Session.php */
/* Location: ./upload/includes/application/libraries/core_Session.php */
