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
 * KB Controller
 *
 * Main Home Page Controller
 *
 * @package		68kb
 * @subpackage	Controllers
 * @category	Controllers
 * @author		68kb Dev Team
 * @link		http://68kb.com/
 * @version 	$Id: kb.php 89 2009-08-13 01:54:20Z suzkaw68 $
 */
class Kb extends Controller
{
	

	/**
	* Constructor
	*
	* @return 	void
	*/
	function __construct()
	{
		parent::__construct();
		
		$this->load->model('init_model');
		$this->load->library('session');
		$this->load->helper('cookie');
		$this->load->helper('form');
		$this->load->library('auth');
		
		log_message('debug', 'KB Controller Initialized');
		if(!$this->db->table_exists('articles'))
		{
			redirect('setup');
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	* Index Controller
	*
	* Show the home page
	*
	* @uses category_model::get_categories_by_parent
	* @uses article_model::get_most_popular
	* @uses article_model::get_latest
	* @access	public
	*/
	function index()
	{
		// Public site (toggle off) or already logged in -> straight to the home page.
		if ($this->init_model->get_setting('enable_viewer_login') != 'Y' OR $this->auth->viewer_logged_in())
		{
			redirect('home');
		}

		$this->_show_login();
	}

	// ------------------------------------------------------------------------

	/**
	* Process the viewer login form.
	*
	* @access	public
	*/
	function login()
	{
		// Don't process logins for a public site, or for an already-authed viewer.
		if ($this->init_model->get_setting('enable_viewer_login') != 'Y' OR $this->auth->viewer_logged_in())
		{
			redirect('home');
		}

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<p class="error">', '</p>');
		$this->form_validation->set_rules('email', 'lang:kb_email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'lang:kb_password', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->_show_login();
			return;
		}

		// Failed-login limiting (per email + IP). A locked combination is
		// refused without checking the password.
		$this->load->model('login_attempts_model');
		$email = $this->input->post('email');
		$ip = $this->input->ip_address();

		$locked = $this->login_attempts_model->seconds_locked('viewer', $email, $ip);
		if ($locked > 0)
		{
			$this->_show_login($this->login_attempts_model->lock_message($locked));
			return;
		}

		if ($this->auth->viewer_login($email, $this->input->post('password')))
		{
			$this->login_attempts_model->clear('viewer', $email, $ip);
			$goto = $this->session->userdata('viewer_redirected_from');
			$this->session->unset_userdata('viewer_redirected_from');
			redirect($goto ? $goto : 'home');
		}
		else
		{
			list($locked, $remaining) = $this->login_attempts_model->record_failure('viewer', $email, $ip);
			$this->_show_login($locked > 0
				? $this->login_attempts_model->lock_message($locked)
				: $this->login_attempts_model->remaining_message(lang('kb_login_failed'), $remaining));
		}
	}

	// ------------------------------------------------------------------------

	/**
	* Log the viewer out.
	*
	* @access	public
	*/
	function logout()
	{
		$this->auth->viewer_logout();
		redirect('kb');
	}

	// ------------------------------------------------------------------------

	/**
	* Render the standalone login view for the active front theme.
	*
	* Deliberately does NOT go through init_model::display_template so the
	* content-revealing theme layout/sidebar never renders for logged-out
	* visitors on a private knowledge base.
	*
	* @access	private
	* @param	string	optional error message to show
	*/
	function _show_login($error = '')
	{
		$this->load->helper('form');
		$data['error'] = $error;
		$data['settings'] = $this->init_model->settings;

		$template = $this->init_model->get_setting('template');
		if ($this->init_model->test_exists('front/'.$template.'/login_view.php'))
		{
			$this->load->view('front/'.$template.'/login_view', $data);
		}
		else
		{
			$this->load->view('front/default/login_view', $data);
		}
	}

}

     

/* End of file kb.php */
/* Location: ./upload/includes/application/controllers/kb.php */ 