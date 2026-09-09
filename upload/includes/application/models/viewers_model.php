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
 * Viewers Model
 *
 * Handles the front-end (public) viewer accounts used by the optional viewer
 * login. Kept separate from the admin "users" table. Login identifier is email.
 *
 * @package		68kb
 * @subpackage	Models
 * @category	Models
 * @author		68kb Dev Team
 * @link		http://68kb.com/
 */
class Viewers_model extends model
{
	function __construct()
	{
		parent::__construct();
		$this->obj =& get_instance();
	}

	// ------------------------------------------------------------------------

	/**
	 * Normalize an email address.
	 *
	 * Trim + lowercase so case/space variants can't create duplicate accounts
	 * or block a valid login.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function normalize_email($email)
	{
		return strtolower(trim($email));
	}

	// ------------------------------------------------------------------------

	/**
	 * Get all viewers (query object, for the admin grid).
	 *
	 * @access	public
	 * @return	object
	 */
	function get_viewers()
	{
		$this->db->from('viewers');
		$query = $this->db->get();
		return $query;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get a single viewer by id.
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_viewer_by_id($id)
	{
		$this->db->from('viewers')->where('id', (int)$id);
		$query = $this->db->get();
		$data = $query->row();
		$query->free_result();
		return $data;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get a single viewer by email.
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	function get_viewer_by_email($email)
	{
		$this->db->from('viewers')->where('email', $this->normalize_email($email));
		$query = $this->db->get();
		$data = $query->row();
		$query->free_result();
		return $data;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check whether an email is already in use.
	 *
	 * @access	public
	 * @param	string	the email to check
	 * @param	int		a viewer id to exclude (for the edit case)
	 * @return	bool	TRUE if the email already exists
	 */
	function email_exists($email, $exclude_id = 0)
	{
		$this->db->select('id')->from('viewers')->where('email', $this->normalize_email($email));
		if ($exclude_id > 0)
		{
			$this->db->where('id !=', (int)$exclude_id);
		}
		$query = $this->db->get();
		return ($query->num_rows() > 0);
	}

	// ------------------------------------------------------------------------

	/**
	 * Add a viewer.
	 *
	 * Hashes the password (MD5, matching the admin side) and normalizes email.
	 *
	 * @access	public
	 * @param	array
	 * @return	int|bool	insert id on success, FALSE on failure
	 */
	function add_viewer($data)
	{
		if (isset($data['email']))
		{
			$data['email'] = $this->normalize_email($data['email']);
		}
		if (isset($data['password']))
		{
			$data['password'] = md5($data['password']);
		}
		if ( ! isset($data['joindate']))
		{
			$data['joindate'] = time();
		}
		if ($this->db->insert('viewers', $data))
		{
			$this->db->cache_delete_all();
			return $this->db->insert_id();
		}
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Edit a viewer.
	 *
	 * Only re-hashes the password when a non-empty one is supplied.
	 *
	 * @access	public
	 * @param	int
	 * @param	array
	 * @return	bool
	 */
	function edit_viewer($viewer_id, $data)
	{
		if (isset($data['email']))
		{
			$data['email'] = $this->normalize_email($data['email']);
		}
		if (isset($data['password']) && $data['password'] != '')
		{
			$data['password'] = md5($data['password']);
		}
		else
		{
			unset($data['password']);
		}
		$viewer_id = (int)$viewer_id;
		$this->db->where('id', $viewer_id);
		$this->db->update('viewers', $data);

		if ($this->db->affected_rows() > 0)
		{
			$this->db->cache_delete_all();
			return TRUE;
		}
		log_message('info', 'Could not edit the viewer id '. $viewer_id);
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete a viewer.
	 *
	 * @access	public
	 * @param	int
	 * @return	void
	 */
	function delete_viewer($id)
	{
		$this->db->cache_delete_all();
		$this->db->delete('viewers', array('id' => (int)$id));
	}
}

/* End of file viewers_model.php */
/* Location: ./upload/includes/application/models/viewers_model.php */
