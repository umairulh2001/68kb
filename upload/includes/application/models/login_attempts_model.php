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
 * Login Attempts Model
 *
 * Failed-login limiting shared by the admin login, the viewer (front-end)
 * login and the XML-RPC API. Failures are counted per scope + identifier
 * (username/email) + IP. After "login_max_attempts" failures, that
 * combination is locked for "login_lockout_minutes". The count resets once
 * no failure has happened for the lockout window.
 *
 * Unknown identifiers are tracked the same way as real ones, so the messages
 * never reveal which accounts exist.
 *
 * The login_attempts table self-provisions on first use (MySQL and SQLite).
 *
 * @package		68kb
 * @subpackage	Models
 * @category	Models
 */
class Login_attempts_model extends model
{
	const DEFAULT_MAX_ATTEMPTS = 5;
	const DEFAULT_LOCKOUT_MINUTES = 15;

	public $obj;

	function __construct()
	{
		parent::__construct();
		$this->obj =& get_instance();
		$this->obj->load->model('init_model');

		// Idempotent create for installs that predate this feature.
		if ( ! $this->db->table_exists('login_attempts'))
		{
			$this->obj->load->model('setup/install_model');
			$this->obj->install_model->table_login_attempts(FALSE);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Max failed attempts before a lockout (setting, with a safe default).
	 *
	 * @return	int
	 */
	function max_attempts()
	{
		return $this->_int_setting('login_max_attempts', self::DEFAULT_MAX_ATTEMPTS, 1, 100);
	}

	/**
	 * Lockout duration in minutes (setting, with a safe default).
	 *
	 * @return	int
	 */
	function lockout_minutes()
	{
		return $this->_int_setting('login_lockout_minutes', self::DEFAULT_LOCKOUT_MINUTES, 1, 1440);
	}

	// ------------------------------------------------------------------------

	/**
	 * Seconds left on an active lock, or 0 when not locked.
	 *
	 * Uses the latest locked_until across matching rows so a duplicate row
	 * (from two concurrent first failures) can't bypass the lock.
	 *
	 * @param	string	admin|viewer
	 * @param	string	username or email
	 * @param	string	ip address
	 * @return	int
	 */
	function seconds_locked($scope, $identifier, $ip)
	{
		$this->db->select_max('locked_until');
		$this->_where($scope, $identifier, $ip);
		$query = $this->db->get('login_attempts');

		$until = ($query->num_rows() > 0) ? (int) $query->row()->locked_until : 0;
		$left = $until - time();
		return ($left > 0) ? $left : 0;
	}

	// ------------------------------------------------------------------------

	/**
	 * Record a failed login.
	 *
	 * @param	string	admin|viewer
	 * @param	string	username or email
	 * @param	string	ip address
	 * @return	array	array(seconds_locked, attempts_remaining)
	 */
	function record_failure($scope, $identifier, $ip)
	{
		$now = time();
		$max = $this->max_attempts();
		$window = $this->lockout_minutes() * 60;
		$identifier = $this->_normalize($identifier);

		$this->_prune($now);

		$this->_where($scope, $identifier, $ip);
		$query = $this->db->get('login_attempts');

		if ($query->num_rows() == 0)
		{
			$this->db->insert('login_attempts', array(
				'scope'        => $scope,
				'identifier'   => $identifier,
				'ip'           => $ip,
				'attempts'     => 1,
				'last_failure' => $now,
				'locked_until' => 0
			));
		}
		else
		{
			$row = $query->row();
			if (($now - (int) $row->last_failure) >= $window)
			{
				// Quiet for a full window: start counting again.
				$this->db->set('attempts', 1);
			}
			else
			{
				// Atomic increment so concurrent failures are not lost.
				$this->db->set('attempts', 'attempts+1', FALSE);
			}
			$this->db->set('last_failure', $now);
			$this->_where($scope, $identifier, $ip);
			$this->db->update('login_attempts');
		}

		// Read back the current count (all matching rows, in case of duplicates).
		$this->db->select_max('attempts');
		$this->_where($scope, $identifier, $ip);
		$attempts = (int) $this->db->get('login_attempts')->row()->attempts;

		if ($attempts >= $max)
		{
			$this->_where($scope, $identifier, $ip);
			$this->db->update('login_attempts', array('locked_until' => $now + $window));
			log_message('error', 'Login locked: scope='.$scope.' identifier='.$identifier.' ip='.$ip.' after '.$attempts.' failed attempts.');
			return array($window, 0);
		}

		return array(0, $max - $attempts);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clear the failure record after a successful login.
	 *
	 * @param	string	admin|viewer
	 * @param	string	username or email
	 * @param	string	ip address
	 * @return	void
	 */
	function clear($scope, $identifier, $ip)
	{
		$this->_where($scope, $identifier, $ip);
		$this->db->delete('login_attempts');
	}

	// ------------------------------------------------------------------------

	/**
	 * "Too many attempts" message for a lock with $seconds remaining.
	 *
	 * @param	int
	 * @return	string
	 */
	function lock_message($seconds)
	{
		$line = lang('kb_login_locked');
		if ( ! $line)
		{
			$line = 'Too many failed login attempts. Please try again in %d minute(s).';
		}
		return sprintf($line, max(1, (int) ceil($seconds / 60)));
	}

	/**
	 * Append the "N attempts remaining" warning to a failed-login message.
	 *
	 * @param	string	base error message
	 * @param	int		attempts remaining
	 * @return	string
	 */
	function remaining_message($base, $remaining)
	{
		$line = lang('kb_login_attempts_left');
		if ( ! $line)
		{
			$line = '%d attempt(s) remaining before a %d-minute lockout.';
		}
		$base = rtrim((string) $base);
		if ($base !== '' && ! in_array(substr($base, -1), array('.', '!', '?')))
		{
			$base .= '.';
		}
		return $base.' '.sprintf($line, (int) $remaining, $this->lockout_minutes());
	}

	// ------------------------------------------------------------------------

	/**
	 * Apply the scope/identifier/ip where clauses.
	 *
	 * @access	private
	 */
	function _where($scope, $identifier, $ip)
	{
		$this->db->where('scope', $scope);
		$this->db->where('identifier', $this->_normalize($identifier));
		$this->db->where('ip', $ip);
	}

	/**
	 * Lowercase/trim and cap to the column width (avoids strict-mode errors).
	 *
	 * @access	private
	 */
	function _normalize($identifier)
	{
		return substr(strtolower(trim((string) $identifier)), 0, 100);
	}

	/**
	 * Drop stale, unlocked rows so the table doesn't grow without bound.
	 *
	 * @access	private
	 */
	function _prune($now)
	{
		$this->db->where('last_failure <', $now - 86400);
		$this->db->where('locked_until <', $now);
		$this->db->delete('login_attempts');
	}

	/**
	 * Read an integer setting, falling back to $default when the row is
	 * missing or out of range.
	 *
	 * @access	private
	 */
	function _int_setting($name, $default, $min, $max)
	{
		$value = trim((string) $this->obj->init_model->get_setting($name));
		if ( ! ctype_digit($value))
		{
			return $default;
		}
		$value = (int) $value;
		return ($value < $min OR $value > $max) ? $default : $value;
	}
}

/* End of file login_attempts_model.php */
/* Location: ./upload/includes/application/models/login_attempts_model.php */
