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
 */

// ------------------------------------------------------------------------

/**
 * 68KB Core Config
 *
 * Extends CI_Config so every generated URL (site_url(), base_url(),
 * redirect(), form actions, asset links) follows the scheme of the current
 * request. The protected config file builds base_url as "http://".HTTP_HOST,
 * which used to send https visitors back to http on every redirect and caused
 * mixed-content blocking. Deployable, so the server-protected config files
 * don't need editing.
 *
 * Also provides core_Config::is_https(), the single https-detection routine
 * used by core_Session (Secure cookie, Force HTTPS) and the settings screen.
 *
 * @package		68kb
 * @subpackage	Libraries
 * @category	Libraries
 */
class core_Config extends CI_Config {

	function __construct()
	{
		parent::__construct();

		// $this->config is a reference to the shared get_config() array, so
		// config_item('base_url') sees the change too. Runs before the output
		// cache lookup, so http and https pages are cached separately.
		if (self::is_https() && ! empty($this->config['base_url']))
		{
			$this->config['base_url'] = preg_replace('#^http://#i', 'https://', $this->config['base_url']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Is the current request served over https?
	 *
	 * Checks the standard server flags plus X-Forwarded-Proto for hosts behind
	 * a TLS-terminating proxy. A client spoofing X-Forwarded-Proto on plain
	 * http only affects its own response, so trusting it is harmless here.
	 *
	 * @access	public
	 * @return	bool
	 */
	static function is_https()
	{
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
		{
			return TRUE;
		}
		if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
		{
			return TRUE;
		}
		if ( ! empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https')
		{
			return TRUE;
		}
		if ( ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
		{
			$proto = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']);
			if (strtolower(trim($proto[0])) === 'https')
			{
				return TRUE;
			}
		}
		return FALSE;
	}
}

/* End of file core_Config.php */
/* Location: ./upload/includes/application/libraries/core_Config.php */
