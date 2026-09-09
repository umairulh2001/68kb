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
 * Admin Viewers Controller
 *
 * Manages the front-end (public) viewer accounts used by the optional viewer
 * login. Mirrors the admin Users controller but targets the separate "viewers"
 * table; the login identifier is email and there is no permission level.
 *
 * @package		68kb
 * @subpackage	Admin_Controllers
 * @category	Admin_Controllers
 * @author		68kb Dev Team
 * @link		http://68kb.com/
 */
class Viewers extends Controller
{
	/**
	* Constructor
	*
	* Requires needed models and helpers.
	*
	* @access	public
	*/
	function __construct()
	{
		parent::__construct();
		$this->load->model('init_model');
		$this->load->model('viewers_model');
		$this->load->helper('cookie');
		$this->load->library('auth');
		$this->auth->restrict();

		// Idempotent create: make sure the viewers table exists on installs that
		// predate this feature (works on both MySQL and SQLite via dbforge).
		if ( ! $this->db->table_exists('viewers'))
		{
			$this->load->model('setup/install_model');
			$this->install_model->table_viewers(FALSE);
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Index Controller
	*
	* Redirects to this->grid
	*
	* @access	public
	*/
	function index()
	{
		$cookie = get_cookie('viewersgrid_orderby', TRUE);
		$cookie2 = get_cookie('viewersgrid_orderby_2', TRUE);
		if($cookie<>'' && $cookie2 <> '')
		{
			redirect('admin/viewers/grid/orderby/'.$cookie.'/'.$cookie2);
		}
		else
		{
			redirect('admin/viewers/grid/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Revert
	*
	* Show a message and redirect the user
	*
	* @access	public
	* @param	string -- The location to goto
	* @return	array
	*/
	function revert($goto)
	{
		$data['goto'] = $goto;
		$data['nav'] = 'viewers';
		$this->init_model->display_template('content', $data, 'admin');
	}

	// ------------------------------------------------------------------------

	/**
	* Grid
	*
	* Show a table of viewers
	*
	* @access	public
	* @return	array
	*/
	function grid()
	{
		$data['nav'] = 'viewers';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		#### settings ###
		$this->load->library("pagination");
		$this->load->helper("url");

		$config['per_page'] = $this->init_model->settings['max_search'];

		#### db init ###

		//total number of rows
		$config['total_rows'] = $this->db->count_all('viewers');

		//prepare active record for new query (with limit/offeset/orderby)
		$this->db->select('id, email, firstname, lastname, active');
		$this->db->from("viewers");

		#### sniff uri/orderby ###

		$segment_array = $this->uri->segment_array();
		$segment_count = $this->uri->total_segments();

		$allowed = array('id','email', 'firstname', 'lastname', 'active');

		//segments
		$do_orderby = array_search("orderby",$segment_array);

		//do orderby
		if ($do_orderby !== FALSE)
		{
			$orderby = $this->uri->segment($do_orderby+1);
			if( !in_array( trim ( $orderby ), $allowed ))
			{
				$orderby = 'id';
			}
			$this->db->order_by($orderby, $this->uri->segment($do_orderby+2));
		}
		else
		{
			$orderby = 'id';
		}

		$data['orderby'] = $orderby;
		$data['sort'] = $this->uri->segment($do_orderby+2);
		if($data['sort'] == 'asc')
		{
			$data['opp'] = 'desc';
		}
		else
		{
			$data['opp'] = 'asc';
		}

		//set cookie
		$cookie = array(
				'name'   => 'viewersgrid_orderby',
				'value'  => $this->uri->segment($do_orderby+1),
				'expire' => '86500'
			);
		$cookie2 = array(
				'name'   => 'viewersgrid_orderby_2',
				'value'  => $this->uri->segment($do_orderby+2),
				'expire' => '86500'
			);

		set_cookie($cookie);
		set_cookie($cookie2);

		#### pagination & data subset ###

		//remove last segment (assume it's the current offset)
		if (ctype_digit($segment_array[$segment_count]))
		{
			$this->db->limit($config['per_page'], $segment_array[$segment_count]);
			array_pop($segment_array);
		}
		else
		{
			$this->db->limit($config['per_page']);
		}
		$query = $this->db->get();
		$data['items'] =  $query->result_array();

		$config['base_url'] = site_url(join("/",$segment_array));
		$config['uri_segment'] = count($segment_array)+1;
		$this->pagination->initialize($config);
		$data["pagination"] = $this->pagination->create_links();

		$this->init_model->display_template('viewers/grid', $data, 'admin');
	}

	// ------------------------------------------------------------------------

	/**
	* Edit Viewer
	*
	* @access	public
	*/
	function edit()
	{
		$data['nav'] = 'viewers';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$this->load->helper(array('form', 'url', 'security'));
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

		$id = (int) $this->uri->segment(4, 0);

		$data['art'] = $this->viewers_model->get_viewer_by_id($id);
		$data['action'] = site_url('admin/viewers/edit/'.$id);

		$this->form_validation->set_rules('firstname', 'lang:kb_firstname', 'trim');
		$this->form_validation->set_rules('lastname', 'lang:kb_lastname', 'trim');
		$this->form_validation->set_rules('email', 'lang:kb_email', 'required|valid_email|callback_email_check');
		if($this->input->post('password') <> '')
		{
			$this->form_validation->set_rules('password', 'lang:kb_password', 'required|matches[passconf]');
			$this->form_validation->set_rules('passconf', 'lang:kb_confirmpassword', 'required');
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->init_model->display_template('viewers/form', $data, 'admin');
		}
		else
		{
			$id = (int) $this->input->post('id', TRUE);
			$update = array(
				'firstname' => $this->input->post('firstname', TRUE),
				'lastname' => $this->input->post('lastname', TRUE),
				'email' => $this->input->post('email', TRUE),
				'active' => ($this->input->post('active', TRUE) == 'Y') ? 1 : 0
			);
			if ($this->input->post('password'))
			{
				$update['password'] = $this->input->post('password', TRUE);
			}
			$this->viewers_model->edit_viewer($id, $update);
			$this->revert('admin/viewers/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Add Viewer
	*
	* @access	public
	*/
	function add()
	{
		$data['nav']='viewers';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$this->load->helper(array('form', 'url', 'security'));
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

		$data['action'] = site_url('admin/viewers/add/');

		/** Rules **/
		$this->form_validation->set_rules('firstname', 'lang:kb_firstname', 'trim');
		$this->form_validation->set_rules('lastname', 'lang:kb_lastname', 'trim');
		$this->form_validation->set_rules('email', 'lang:kb_email', 'required|valid_email|callback_email_check');
		$this->form_validation->set_rules('password', 'lang:kb_password', 'required|matches[passconf]');
		$this->form_validation->set_rules('passconf', 'lang:kb_confirmpassword', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->init_model->display_template('viewers/form', $data, 'admin');
		}
		else
		{
			$viewer = array(
				'firstname' => $this->input->post('firstname', TRUE),
				'lastname' => $this->input->post('lastname', TRUE),
				'email' => $this->input->post('email', TRUE),
				'password' => $this->input->post('password', TRUE),
				'active' => ($this->input->post('active', TRUE) == 'Y') ? 1 : 0,
				'joindate' => time()
			);
			if ($this->viewers_model->add_viewer($viewer))
			{
				$this->revert('admin/viewers/');
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Email check
	*
	* Checks to see if an email is already in use. Excludes the viewer currently
	* being edited (via the posted id) so an unchanged email doesn't fail.
	*
	* @access	public
	*/
	function email_check($str)
	{
		$exclude_id = (int) $this->input->post('id', TRUE);
		if ($this->viewers_model->email_exists($str, $exclude_id))
		{
			$this->form_validation->set_message('email_check', $this->lang->line('kb_email_inuse'));
			return FALSE;
		}
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	* Delete Viewer
	*
	* @access	public
	*/
	function delete()
	{
		$data['nav'] = 'viewers';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$id = (int) $this->uri->segment(4, 0);
		$this->viewers_model->delete_viewer($id);
		$this->revert('admin/viewers/');
	}

}

/* End of file viewers.php */
/* Location: ./upload/includes/application/controllers/admin/viewers.php */
