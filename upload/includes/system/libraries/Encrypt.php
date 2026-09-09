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
 * CodeIgniter Encryption Class
 *
 * Provides two-way keyed encoding using XOR Hashing and Mcrypt
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Encrypt {

	public $CI;
	public $encryption_key	= '';
	public $_hash_type	= 'sha1';
	public $_openssl_exists = FALSE;
	public $_openssl_cipher;
	public $_openssl_mode;

	/**
	 * Constructor
	 *
	 * Simply determines whether the openssl library exists.
	 *
	 */
	function __construct()
	{
		$this->CI =& get_instance();
		$this->_openssl_exists = ( ! function_exists('openssl_encrypt')) ? FALSE : TRUE;
		log_message('debug', "Encrypt Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the encryption key
	 *
	 * Returns it as MD5 in order to have an exact-length 128 bit key.
	 * Mcrypt is sensitive to keys that are not the correct length
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function get_key($key = '')
	{
		if ($key == '')
		{
			if ($this->encryption_key != '')
			{
				return $this->encryption_key;
			}

			$CI =& get_instance();
			$key = $CI->config->item('encryption_key');

			if ($key === FALSE)
			{
				show_error('In order to use the encryption class requires that you set an encryption key in your config file.');
			}
		}

		return md5($key);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the encryption key
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_key($key = '')
	{
		$this->encryption_key = $key;
	}

	// --------------------------------------------------------------------

	/**
	 * Encode
	 *
	 * Encodes the message string using bitwise XOR encoding.
	 * The key is combined with a random hash, and then it
	 * too gets converted using XOR. The whole thing is then run
	 * through mcrypt (if supported) using the randomized key.
	 * The end result is a double-encrypted message string
	 * that is randomized with each call to this function,
	 * even if the supplied message and key are the same.
	 *
	 * @access	public
	 * @param	string	the string to encode
	 * @param	string	the key
	 * @return	string
	 */
	function encode($string, $key = '')
	{
		$key = $this->get_key($key);
		$enc = $this->_xor_encode($string, $key);
		
		if ($this->_openssl_exists === TRUE)
		{
			$enc = $this->openssl_encode($enc, $key);
		}
		return base64_encode($enc);
	}

	// --------------------------------------------------------------------

	/**
	 * Decode
	 *
	 * Reverses the above process
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function decode($string, $key = '')
	{
		$key = $this->get_key($key);
		
		if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string))
		{
			return FALSE;
		}

		$dec = base64_decode($string);

		if ($this->_openssl_exists === TRUE)
		{
			if (($dec = $this->openssl_decode($dec, $key)) === FALSE)
			{
				return FALSE;
			}
		}

		return $this->_xor_decode($dec, $key);
	}

	// --------------------------------------------------------------------

	/**
	 * XOR Encode
	 *
	 * Takes a plain-text string and key as input and generates an
	 * encoded bit-string using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _xor_encode($string, $key)
	{
		$rand = '';
		while (strlen($rand) < 32)
		{
			$rand .= mt_rand(0, mt_getrandmax());
		}

		$rand = $this->hash($rand);

		$enc = '';
		for ($i = 0; $i < strlen($string); $i++)
		{			
			$enc .= substr($rand, ($i % strlen($rand)), 1).(substr($rand, ($i % strlen($rand)), 1) ^ substr($string, $i, 1));
		}

		return $this->_xor_merge($enc, $key);
	}

	// --------------------------------------------------------------------

	/**
	 * XOR Decode
	 *
	 * Takes an encoded string and key as input and generates the
	 * plain-text original message
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _xor_decode($string, $key)
	{
		$string = $this->_xor_merge($string, $key);

		$dec = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
		}

		return $dec;
	}

	// --------------------------------------------------------------------

	/**
	 * XOR key + string Combiner
	 *
	 * Takes a string and key as input and computes the difference using XOR
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _xor_merge($string, $key)
	{
		$hash = $this->hash($key);
		$str = '';
		for ($i = 0; $i < strlen($string); $i++)
		{
			$str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Encrypt using OpenSSL
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function openssl_encode($data, $key)
	{
		$method = $this->_get_cipher();
		$ivlen = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
		return $this->_add_cipher_noise($iv . $encrypted, $key);
	}

	// --------------------------------------------------------------------

	/**
	 * Decrypt using OpenSSL
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function openssl_decode($data, $key)
	{
		$data = $this->_remove_cipher_noise($data, $key);
		$method = $this->_get_cipher();
		$ivlen = openssl_cipher_iv_length($method);

		if ($ivlen > strlen($data))
		{
			return FALSE;
		}

		$iv = substr($data, 0, $ivlen);
		$data = substr($data, $ivlen);
		return rtrim(openssl_decrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv), "\0");
	}

	// --------------------------------------------------------------------

	/**
	 * Adds permuted noise to the IV + encrypted data to protect
	 * against Man-in-the-middle attacks on CBC mode ciphers
	 * http://www.ciphersbyritter.com/GLOSSARY.HTM#IV
	 *
	 * Function description
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _add_cipher_noise($data, $key)
	{
		$keyhash = $this->hash($key);
		$keylen = strlen($keyhash);
		$str = '';

		for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j)
		{
			if ($j >= $keylen)
			{
				$j = 0;
			}

			$str .= chr((ord($data[$i]) + ord($keyhash[$j])) % 256);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Removes permuted noise from the IV + encrypted data, reversing
	 * _add_cipher_noise()
	 *
	 * Function description
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _remove_cipher_noise($data, $key)
	{
		$keyhash = $this->hash($key);
		$keylen = strlen($keyhash);
		$str = '';

		for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j)
		{
			if ($j >= $keylen)
			{
				$j = 0;
			}

			$temp = ord($data[$i]) - ord($keyhash[$j]);

			if ($temp < 0)
			{
				$temp = $temp + 256;
			}
			
			$str .= chr($temp);
		}

		return $str;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set the OpenSSL Cipher
	 *
	 * @access	public
	 * @param	constant
	 * @return	string
	 */
	function set_cipher($cipher)
	{
		$this->_openssl_cipher = $cipher;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the OpenSSL Mode
	 *
	 * @access	public
	 * @param	constant
	 * @return	string
	 */
	function set_mode($mode)
	{
		$this->_openssl_mode = $mode;
	}

	// --------------------------------------------------------------------

	/**
	 * Get OpenSSL cipher Value
	 *
	 * @access	private
	 * @return	string
	 */
	function _get_cipher()
	{
		if ($this->_openssl_cipher == '')
		{
			$this->_openssl_cipher = 'AES-256-CBC';
		}

		return $this->_openssl_cipher;
	}

	// --------------------------------------------------------------------

	/**
	 * Get OpenSSL Mode Value
	 *
	 * @access	private
	 * @return	string
	 */
	function _get_mode()
	{
		if ($this->_openssl_mode == '')
		{
			$this->_openssl_mode = 'CBC';
		}
		
		return $this->_openssl_mode;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the Hash type
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function set_hash($type = 'sha1')
	{
		$this->_hash_type = ($type != 'sha1' AND $type != 'md5') ? 'sha1' : $type;
	}

	// --------------------------------------------------------------------

	/**
	 * Hash encode a string
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function hash($str)
	{
		return ($this->_hash_type == 'sha1') ? $this->sha1($str) : md5($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Generate an SHA1 Hash
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function sha1($str)
	{
		if ( ! function_exists('sha1'))
		{
			if ( ! function_exists('mhash'))
			{
				require_once(BASEPATH.'libraries/Sha1'.EXT);
				$SH = new CI_SHA;
				return $SH->generate($str);
			}
			else
			{
				return bin2hex(mhash(MHASH_SHA1, $str));
			}
		}
		else
		{
			return sha1($str);
		}
	}

}

// END CI_Encrypt class

/* End of file Encrypt.php */
/* Location: ./system/libraries/Encrypt.php */