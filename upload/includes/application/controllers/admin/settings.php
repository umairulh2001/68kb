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
 * Admin Settings Controller
 *
 * Handles all the settings
 *
 * @package		68kb
 * @subpackage	Admin_Controllers
 * @category	Admin_Controllers
 * @author		68kb Dev Team
 * @link		http://68kb.com/user_guide/overview/settings.html
 * @version 	$Id: settings.php 134 2009-12-02 01:29:40Z suzkaw68 $
 */
class Settings extends Controller
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
		$this->load->helper('form');
	}
	
	// ------------------------------------------------------------------------
	
	/**
	* Index Controller
	*
	* Redirects to this->main
	*
	* @access	public
	*/
	function index()
	{
		redirect('admin/settings/main/'); 
	}
	
	// ------------------------------------------------------------------------
	
	/**
	* Save Settings
	* 
	* @access	public
	*/
	function main()
	{
		$this->load->library('form_validation');
		$this->load->helper('form');
		$data['nav']='settings';
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin'); 
			return FALSE;
		}
		
		// Seed settings rows for installs that predate these features so they
		// always have a row to display/persist (works on MySQL and SQLite).
		$seed = array(
			'enable_viewer_login'   => array('Enable Viewer Login', 'N'),
			'session_timeout'       => array('Login Session Timeout', '120'),
			'login_max_attempts'    => array('Max Failed Login Attempts', '5'),
			'login_lockout_minutes' => array('Login Lockout Minutes', '15'),
			'force_https'           => array('Force HTTPS', 'N'),
		);
		foreach ($seed as $short_name => $row)
		{
			if ($this->db->where('short_name', $short_name)->count_all_results('settings') == 0)
			{
				$this->db->insert('settings', array(
					'short_name' => $short_name,
					'name'       => $row[0],
					'value'      => $row[1],
					'auto_load'  => 'yes'
				));
				// init_model cached the settings before this seed ran; add the
				// value so the form shows it on this first visit.
				$this->init_model->settings[$short_name] = $row[1];
			}
		}

		$this->form_validation->set_rules('site_name', 'lang:kb_site_title', 'required');
		$this->form_validation->set_rules('site_email', 'lang:kb_email', 'required|valid_email');
		$this->form_validation->set_rules('max_search', 'lang:kb_max_search', 'required|numeric');
		$this->form_validation->set_rules('cache_time', 'lang:kb_cache_time', 'required|numeric');
		$this->form_validation->set_rules('enable_viewer_login', 'lang:kb_enable_viewer_login', 'required');
		$this->form_validation->set_rules('session_timeout', 'lang:kb_session_timeout', 'required|is_natural_no_zero|callback__in_range[5,43200]');
		$this->form_validation->set_rules('login_max_attempts', 'lang:kb_login_max_attempts', 'required|is_natural_no_zero|callback__in_range[1,100]');
		$this->form_validation->set_rules('login_lockout_minutes', 'lang:kb_login_lockout_minutes', 'required|is_natural_no_zero|callback__in_range[1,1440]');
		$this->form_validation->set_rules('force_https', 'lang:kb_force_https', 'required|callback__force_https_check');
		$this->core_events->trigger('settings/validation');
		
		if ($this->form_validation->run() == FALSE)
		{
			$this->init_model->display_template('settings/main', $data, 'admin');
		}
		else
		{
			foreach ($_POST as $key => $value)
			{
				$data = array(
					'value' => $this->input->xss_clean($value)
				);
				$this->db->where('short_name', $key);
				$this->db->update('settings', $data);
			}
			// Purge any pages cached before a settings change (e.g. before the
			// viewer-login gate was turned on) so stale content isn't served.
			$this->load->helper('file');
			delete_files($this->config->item('cache_path'));
			$this->db->cache_delete_all();
			$this->revert('admin/settings/');
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	* Validation callback for force_https: Y/N, and Y only over https.
	*
	* @access	public
	* @param	string	submitted value
	* @return	bool
	*/
	function _force_https_check($str)
	{
		if ($str != 'Y' && $str != 'N')
		{
			$this->form_validation->set_message('_force_https_check', lang('kb_force_https_invalid'));
			return FALSE;
		}
		// Lockout guard: only allow turning it on from an https request, which
		// proves https works and is detected on this server (else redirect loop).
		if ($str == 'Y' && ! core_Config::is_https())
		{
			$this->form_validation->set_message('_force_https_check', lang('kb_force_https_need_https'));
			return FALSE;
		}
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	* Validation callback: whole number within "min,max" (inclusive).
	*
	* @access	public
	* @param	string	submitted value
	* @param	string	"min,max"
	* @return	bool
	*/
	function _in_range($str, $range)
	{
		list($min, $max) = array_map('intval', explode(',', $range));
		$str = trim((string) $str);
		if (ctype_digit($str) && (int) $str >= $min && (int) $str <= $max)
		{
			return TRUE;
		}
		$this->form_validation->set_message('_in_range', sprintf(lang('kb_in_range'), '%s', $min, $max));
		return FALSE;
	}

	// ------------------------------------------------------------------------
	
	/**
	* Template Controller
	*
	* Allow admin to select active template.
	*
	* @access	public
	*/
	function templates()
	{
		$this->load->model('theme_model');
		$settings['template'] = $this->init_model->get_setting('template');
		$data['nav'] = 'settings';
		
		if ( ! $this->auth->check_level(1))
		{
			$data['not_allowed'] = TRUE;
			$this->init_model->display_template('content', $data, 'admin'); 
			return FALSE;
		}
		
		$activate = $this->uri->segment(4, 0);
		
		if ($activate != '')
		{
			if (file_exists(KBPATH .'themes/front/'.$activate.'/config.php'))
			{
				$data = array( 
				'value' => $this->input->xss_clean($activate)
				);
				$this->db->where('short_name', 'template');
				$this->db->update('settings', $data);
				redirect('admin/settings/templates/');
			}
			else
			{
				echo $activate;
			}
		}
		
		$data['active'] = $this->theme_model->load_active_template($settings['template']);
		$data['available_themes'] = $this->theme_model->load_templates($settings['template']);
		$this->init_model->display_template('settings/template', $data, 'admin'); 
	}
	
	// ------------------------------------------------------------------------
		
	/**
	 * Load Active Template
	 *
	 * Load the config file for the active template.
	 *
	 * @access	private
	 * @param	string	the file
	 * @return	bool
	 */
	function _load_active_template($template)
	{
		if (file_exists(KBPATH .'themes/front/'.$template.'/config.php'))
		{
			require_once(KBPATH .'themes/front/'.$template.'/config.php');
			$preview = 'front/'.$template.'/preview.png';
			if ($this->_testexists($preview))
			{
				$data['template']['preview']=base_url().'templates/'.$preview;
			}
			else
			{
				$data['template']['preview']=base_url().'images/nopreview.gif';
			}
			return $data['template'];
		}
		else
		{
			return FALSE;
		}
	}
	
	// ------------------------------------------------------------------------
		
	/**
	 * Test if a file exists
	 *
	 * Test if a file exists.
	 *
	 * @access	private
	 * @param	string	the file
	 * @return	bool
	 */
	function _testexists($file)
	{
		$file = KBPATH.'themes/'.$file;
		//echo $file.'<BR>';
		if ( ! file_exists($file))
		{
			return false;
		}
		return true;
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
		$data['nav'] = 'settings';
		$data['goto'] = $goto;
		$this->init_model->display_template('content', $data, 'admin');
	}
	
}

/* End of file settings.php */
/* Location: ./upload/includes/application/controllers/admin/settings.php */ 