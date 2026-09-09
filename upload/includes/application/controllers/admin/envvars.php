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
 * Admin Environment Variables Controller
 *
 * Manages admin-defined name/value pairs that authors reference inside article
 * content as {{var_name}}. On the viewer side those placeholders are swapped for
 * their values by Article_model::replace_envvars(). Mirrors the glossary admin
 * CRUD (direct $this->db, no separate model) with viewers-style table auto-provision.
 *
 * @package		68kb
 * @subpackage	Admin_Controllers
 * @category	Admin_Controllers
 * @author		68kb Dev Team
 * @link		http://68kb.com/
 */
class Envvars extends Controller
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
		$this->load->helper('cookie');
		$this->load->library('auth');
		$this->auth->restrict();

		// Idempotent create: make sure the envvars table exists on installs that
		// predate this feature (works on both MySQL and SQLite via dbforge).
		if ( ! $this->db->table_exists('envvars'))
		{
			$this->load->model('setup/install_model');
			$this->install_model->table_envvars(FALSE);
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
		$cookie = get_cookie('envvarsgrid_orderby', TRUE);
		$cookie2 = get_cookie('envvarsgrid_orderby_2', TRUE);
		if($cookie<>'' && $cookie2 <> '')
		{
			redirect('admin/envvars/grid/orderby/'.$cookie.'/'.$cookie2);
		}
		else
		{
			redirect('admin/envvars/grid/');
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
		$data['nav'] = 'envvars';
		$this->init_model->display_template('content', $data, 'admin');
	}

	// ------------------------------------------------------------------------

	/**
	* Grid
	*
	* Show a table of environment variables
	*
	* @access	public
	* @return	array
	*/
	function grid()
	{
		$data['nav'] = 'envvars';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		#### settings ###
		$this->load->library("pagination");
		$this->load->helper("url");

		$config['per_page'] = 25;

		#### db init ###

		//total number of rows
		$config['total_rows'] = $this->db->count_all('envvars');

		//prepare active record for new query (with limit/offeset/orderby)
		$this->db->from("envvars");

		#### sniff uri/orderby ###

		$segment_array = $this->uri->segment_array();
		$segment_count = $this->uri->total_segments();

		$allowed = array('id','var_name');

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
				'name'   => 'envvarsgrid_orderby',
				'value'  => $this->uri->segment($do_orderby+1),
				'expire' => '86500'
			);
		$cookie2 = array(
				'name'   => 'envvarsgrid_orderby_2',
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

		$this->init_model->display_template('envvars/grid', $data, 'admin');
	}

	// ------------------------------------------------------------------------

	/**
	* Edit Environment Variable
	*
	* @access	public
	*/
	function edit()
	{
		$data['nav'] = 'envvars';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

		$id = (int) $this->uri->segment(4, 0);

		$this->db->from('envvars')->where('id', $id);
		$query = $this->db->get();
		$data['art'] = $query->row();
		$query->free_result();

		$data['action'] = site_url('admin/envvars/edit/'.$id);

		$this->form_validation->set_rules('var_name', 'lang:kb_var_name', 'trim|required|callback_name_check');
		$this->form_validation->set_rules('var_value', 'lang:kb_var_value', 'trim');
		$this->form_validation->set_rules('var_description', 'lang:kb_var_description', 'trim');

		if ($this->form_validation->run() == FALSE)
		{
			$this->init_model->display_template('envvars/form', $data, 'admin');
		}
		else
		{
			$id = (int) $this->input->post('id', TRUE);
			$update = array(
				'var_name' => $this->input->post('var_name', TRUE),
				'var_value' => $this->input->xss_clean($this->input->post('var_value')),
				'var_description' => $this->input->post('var_description', TRUE)
			);
			$this->db->where('id', $id);
			$this->db->update('envvars', $update);
			$this->_purge_cache();
			$this->revert('admin/envvars/');
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Add Environment Variable
	*
	* @access	public
	*/
	function add()
	{
		$data['nav']='envvars';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="error">', '</div>');

		$data['action'] = site_url('admin/envvars/add/');

		/** Rules **/
		$this->form_validation->set_rules('var_name', 'lang:kb_var_name', 'trim|required|callback_name_check');
		$this->form_validation->set_rules('var_value', 'lang:kb_var_value', 'trim');
		$this->form_validation->set_rules('var_description', 'lang:kb_var_description', 'trim');

		if ($this->form_validation->run() == FALSE)
		{
			$this->init_model->display_template('envvars/form', $data, 'admin');
		}
		else
		{
			$insert = array(
				'var_name' => $this->input->post('var_name', TRUE),
				'var_value' => $this->input->xss_clean($this->input->post('var_value')),
				'var_description' => $this->input->post('var_description', TRUE)
			);
			if ($this->db->insert('envvars', $insert))
			{
				$this->_purge_cache();
				$this->revert('admin/envvars/');
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Name check
	*
	* Validates the variable name format ([A-Za-z0-9_]) and enforces uniqueness
	* at the application level (there is no portable UNIQUE index in this dbforge).
	* Excludes the variable currently being edited via the posted id.
	*
	* @access	public
	*/
	function name_check($str)
	{
		if ( ! preg_match('/^[A-Za-z0-9_]+$/', $str))
		{
			$this->form_validation->set_message('name_check', $this->lang->line('kb_envvar_name_format'));
			return FALSE;
		}
		$exclude_id = (int) $this->input->post('id', TRUE);
		$this->db->select('id')->from('envvars')->where('var_name', $str);
		if ($exclude_id > 0)
		{
			$this->db->where('id !=', $exclude_id);
		}
		$query = $this->db->get();
		if ($query->num_rows() > 0)
		{
			$this->form_validation->set_message('name_check', $this->lang->line('kb_envvar_name_inuse'));
			return FALSE;
		}
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	* Delete Environment Variable
	*
	* @access	public
	*/
	function delete()
	{
		$data['nav'] = 'envvars';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin');
			return FALSE;
		}
		$id = (int) $this->uri->segment(4, 0);
		$this->db->delete('envvars', array('id' => $id));
		$this->_purge_cache();
		$this->revert('admin/envvars/');
	}

	// ------------------------------------------------------------------------

	/**
	* Purge cache
	*
	* Clears any pages cached before a variable change so stale values aren't
	* served. Mirrors the settings controller.
	*
	* @access	private
	*/
	function _purge_cache()
	{
		$this->load->helper('file');
		delete_files($this->config->item('cache_path'));
		$this->db->cache_delete_all();
	}

}

/* End of file envvars.php */
/* Location: ./upload/includes/application/controllers/admin/envvars.php */
