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
 * Auth Libarary
 *
 * Handles the article pages
 *
 * @package		68kb
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Bramme.net
 * @link		http://www.bramme.net/2008/07/auth-library-for-codeigniter-tutorial/
 * @version 	$Id: Auth.php 130 2009-12-01 18:04:30Z suzkaw68 $
 */
class Auth {

	public $CI = NULL;
	
	/**
	 * User data of person attempting login or session/cookie owner
	 *
	 * @var array
	 * @access private
	 */
	public $_user_data = array();

	function __construct()
	{
		$this->CI =& get_instance();
		
		// Load additional libraries, helpers, etc.
		$this->CI->load->library('session');
		$this->CI->load->database();
		$this->CI->load->helper('url');
	}

	// ------------------------------------------------------------------------
	
	/**
	 *
	 * Process the data from the login form
	 *
	 * @access	public
	 * @param	array	array with 2 values, username and password (in that order)
	 * @return	boolean
	 */	
	function process_login($login = NULL)
	{
		// A few safety checks
		// Our array has to be set
		if( ! isset($login))
		{
			return FALSE;
		}
			
		//Our array has to have 2 values
		//No more, no less!
		if(count($login) != 2)
		{
			return FALSE;
		}
			
		$username = $login[0];
		$password = md5($login[1]);
		
		$this->CI->db->where('username', $username);
		$this->CI->db->where('password', $password);
		$query = $this->CI->db->get('users');
		
		if ($query->num_rows() == 1)
		{
			$row = $query->row(); 
			// Our user exists, update db
			$data = array(
				'cookie' => md5(md5($row->id . $username)), 
				'session' => $this->CI->session->userdata('session_id'),
				'custip' => $this->CI->input->ip_address()
			);
			$this->CI->db->where('id', $row->id);
			$this->CI->db->update('users', $data);

			// Our user exists, set session.
			$this->CI->session->set_userdata('logged_user', $username);
			$this->CI->session->set_userdata('userid', $row->id);
			$this->CI->session->set_userdata('username', $username);
			$this->CI->session->set_userdata('cookie', md5(md5($row->id.$username)));
			$this->CI->session->set_userdata('level', $row->level);
			return TRUE;
		}
		else 
		{
			// No existing user.
			return FALSE;
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * This function redirects users after logging in
	 *
	 * @access	public
	 * @return	void
	 */	
	function redirect()
	{
		if ($this->CI->session->userdata('redirected_from') == FALSE)
		{
			redirect('/admin');
		} 
		else 
		{
			redirect($this->CI->session->userdata('redirected_from'));
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * This function restricts users from certain pages.
	 * use restrict(TRUE) if a user can't access a page when logged in
	 *
	 * @access	public
	 * @param	boolean	wether the page is viewable when logged in
	 * @return	void
	 */	
	function restrict($logged_out = FALSE)
	{
		// If the user is logged in and he's trying to access a page
		// he's not allowed to see when logged in,
		// redirect him to the index!
		if ($logged_out && $this->logged_in())
		{
			redirect('admin');
		}
		
		// If the user isn' logged in and he's trying to access a page
		// he's not allowed to see when logged out,
		// redirect him to the login page!
		if ( ! $this->_check_session() || ! $logged_out && ! $this->logged_in()) 
		{
			$this->CI->session->set_userdata('redirected_from', $this->CI->uri->uri_string()); // We'll use this in our redirect method.
			redirect('admin/kb/login');
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * Check the session against the db
	 *
	 * @access	public
	 * @param	boolean	wether the page is viewable when logged in
	 * @return	void
	 */
	private function _check_session() 
	{
		$this->CI->db->select('id,username,level')->from('users')->where('username', $this->CI->session->userdata('username'))->where('cookie', $this->CI->session->userdata('cookie'));
		$query = $this->CI->db->get();
		//echo $this->CI->db->last_query();
		
		if ($query->num_rows() <> 1)
		{
			return false;
		}
		$this->_user_data = $query->result_array();
		return true;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * Check the user level
	 *
	 * @access	public
	 * @param	boolean	wether the page is viewable when logged in
	 * @return	void
	 */
	function check_level($level) 
	{
		$this->CI->db->select('id,username,level')->from('users')
			->where('level <= ', $level)
			->where('username', $this->CI->session->userdata('username'))
			->where('cookie', $this->CI->session->userdata('cookie'));
		$query = $this->CI->db->get();

		if ($query->num_rows() == 1)
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * Checks if a user is logged in
	 *
	 * @access	public
	 * @return	boolean
	 */	
	function logged_in()
	{
		if ($this->CI->session->userdata('logged_user') == FALSE)
		{
			return FALSE;
		}
		else 
		{
			return TRUE;
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 *
	 * Logs the admin user out.
	 *
	 * Clears only the admin session keys (NOT sess_destroy) so a co-existing
	 * viewer session in the same browser is preserved (mirrors viewer_logout).
	 * unset_userdata() iterates $key => $val, so the keys must be array KEYS.
	 *
	 * @access	public
	 * @return	TRUE
	 */
	function logout()
	{
		$this->CI->session->unset_userdata(array(
			'logged_user'     => '',
			'userid'          => '',
			'username'        => '',
			'cookie'          => '',
			'level'           => '',
			'redirected_from' => ''
		));
		return TRUE;
	}

	// ========================================================================
	// Viewer (front-end) authentication
	//
	// Parallel to the admin methods above but scoped to the separate "viewers"
	// table and its own session keys (viewer_logged_in, viewer_id, viewer_email,
	// viewer_cookie). Keeping the keys distinct lets an admin and a viewer be
	// logged in simultaneously in the same browser without clobbering each other.
	// ========================================================================

	/**
	 *
	 * Process a viewer login.
	 *
	 * @access	public
	 * @param	string	email
	 * @param	string	password (plain text)
	 * @return	boolean
	 */
	function viewer_login($email, $password)
	{
		$email = strtolower(trim($email));

		$this->CI->db->where('email', $email);
		$this->CI->db->where('password', md5($password));
		$this->CI->db->where('active', 1);
		$query = $this->CI->db->get('viewers');

		if ($query->num_rows() == 1)
		{
			$row = $query->row();

			// Update the viewer record with session/verification data.
			$data = array(
				'cookie' => md5(md5($row->id . $email)),
				'session' => $this->CI->session->userdata('session_id'),
				'custip' => $this->CI->input->ip_address(),
				'lastlogin' => time()
			);
			$this->CI->db->where('id', $row->id);
			$this->CI->db->update('viewers', $data);

			// Set the viewer session.
			$this->CI->session->set_userdata('viewer_logged_in', TRUE);
			$this->CI->session->set_userdata('viewer_id', $row->id);
			$this->CI->session->set_userdata('viewer_email', $email);
			$this->CI->session->set_userdata('viewer_cookie', md5(md5($row->id . $email)));
			return TRUE;
		}
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 * Checks if a viewer is logged in (session flag only).
	 *
	 * @access	public
	 * @return	boolean
	 */
	function viewer_logged_in()
	{
		return ($this->CI->session->userdata('viewer_logged_in') == TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 * Logs a viewer out.
	 *
	 * Clears only the viewer session keys (NOT sess_destroy) so any co-existing
	 * admin session in the same browser is preserved.
	 *
	 * @access	public
	 * @return	void
	 */
	function viewer_logout()
	{
		$this->CI->session->unset_userdata('viewer_logged_in');
		$this->CI->session->unset_userdata('viewer_id');
		$this->CI->session->unset_userdata('viewer_email');
		$this->CI->session->unset_userdata('viewer_cookie');
	}

	// ------------------------------------------------------------------------

	/**
	 *
	 * The front-end site gate.
	 *
	 * No-op when the "enable_viewer_login" setting is off (site stays public).
	 * When on, requires a valid viewer session and re-validates it against the
	 * db (viewer still exists and is active) so a deleted/deactivated viewer is
	 * forced back to the login page rather than lingering until session expiry.
	 *
	 * @access	public
	 * @return	void
	 */
	function viewer_restrict()
	{
		$this->CI->load->model('init_model');

		// Setting off? Public site, nothing to do.
		if ($this->CI->init_model->get_setting('enable_viewer_login') != 'Y')
		{
			return;
		}

		// Not logged in -> send to login, remembering where they were headed.
		if ( ! $this->viewer_logged_in())
		{
			$this->CI->session->set_userdata('viewer_redirected_from', $this->CI->uri->uri_string());
			redirect('kb');
		}

		// Logged in: re-validate against the db (exists + active + cookie match).
		$this->CI->db->select('id')->from('viewers')
			->where('id', $this->CI->session->userdata('viewer_id'))
			->where('cookie', $this->CI->session->userdata('viewer_cookie'))
			->where('active', 1);
		$query = $this->CI->db->get();

		if ($query->num_rows() != 1)
		{
			$this->viewer_logout();
			$this->CI->session->set_userdata('viewer_redirected_from', $this->CI->uri->uri_string());
			redirect('kb');
		}
	}
}

/* End of file: Auth.php */
/* Location: ./system/application/libraries/Auth.php */