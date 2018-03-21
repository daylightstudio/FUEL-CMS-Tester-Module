<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * FUEL CMS
 * http://www.getfuelcms.com
 *
 * An open source Content Management System based on the 
 * Codeigniter framework (http://codeigniter.com)
 *
 * @package		FUEL CMS
 * @author		David McReynolds @ Daylight Studio
 * @copyright	Copyright (c) 2013, Run for Daylight LLC.
 * @license		http://docs.getfuelcms.com/general/license
 * @link		http://www.getfuelcms.com
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * The base class Test classes should inherit from
 *
 * @package		Tester Module
 * @subpackage	Libraries
 * @category	Libraries
 * @author		David McReynolds @ Daylight Studio
 * @link		http://docs.getfuelcms.com/modules/tester/tester_base
 */
require_once(TESTER_PATH.'libraries/phpQuery.php');

abstract class Tester_base 
{
	protected $CI = NULL; // a reference to the CodeIgniter super object
	protected $fuel = NULL; // a reference to the FUEL object
	
	protected $loaded_page = NULL; // determines if a page is loaded
	
	private $_is_db_created = NULL;
	private $_orig_db = NULL;
	
	// --------------------------------------------------------------------

	/**
	 * Constructor sets up the CI instance
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->CI =& get_instance();
		
		if (isset($this->CI->fuel))
		{
			$this->fuel =& $this->CI->fuel;
		}
		
		// set testing constant if not already
		if (!defined('TESTING')) define('TESTING', TRUE);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Runs a test passing the value and the expected results. The name parameter will help identify it in the results page.
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @param	string
	 * @return	string
	 */
	public function run($test, $expected, $name = '', $notes = '', $format = NULL)
	{
		if (is_null($format))
		{
			$format = !$this->is_cli();
		}
		
		if ($format)
		{
			$name = $this->format_test_name($name, $test, $expected);
		}

		return $this->CI->unit->run($test, $expected, $name, $notes, $format);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns whether the test is being run via Command Line Interface or not
	 *
	 * @access	public
	 * @return	boolean
	 */
	static public function is_cli()
	{
		$is_cli = (php_sapi_name() == 'cli' or defined('STDIN')) ? TRUE : FALSE;
		return $is_cli;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Placeholder to be overwritten by child classes for test setup (like database table creation etc).
	 *
	 * @access	public
	 * @return	void
	 */
	public function setup()
	{
		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Is called at the end of the test and will remove any test database that has been created.
	 *
	 * @access	public
	 * @return	void
	 */
	public function tear_down()
	{
		if ($this->_is_db_created)
		{
			$this->remove_db();

			$sql = "USE `{$this->_orig_db}`;";
			$this->CI->db->query($sql);
		}
		
		// remove the cookie file
		if (file_exists($this->config_item('session_cookiejar_file')))
		{
			@unlink($this->config_item('session_cookiejar_file'));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Formats the test name to include the test and expected results
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed
	 * @param	mixed
	 * @return	string
	 */
	protected function format_test_name($name, $test, $expected)
	{
		$str = '<strong>'.$name.'</strong><br />';
		// $str .= '<strong>Test:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong> <pre>'.htmlentities($test).'</pre><br />';
		// $str .= '<strong>Expected:</strong> <pre>'.htmlentities($expected).'</pre>';
		return $str;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Return Tester specific configuration items
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	public function config_item($key)
	{
		$tester_config = $this->CI->config->item('tester');
		return $tester_config[$key];
	}

	// --------------------------------------------------------------------

	/**
	 *  Connects to the testing database
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function db_connect($dsn = '')
	{
		// check config if $dsn is empty
		if (empty($dsn))
		{
			$dsn = $this->config_item('dsn_group');
		}

		if (isset($this->CI->db->database))
		{
			$this->_orig_db = $this->CI->db->database;	
		}
		
		$this->CI->load->database($dsn);
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Checks to see if the database specified in the tester configuration exists or not
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function db_exists()
	{
		//$this->dbutil->database_exists(); // may be replaced with this later

		$result = $this->CI->db->query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "'.$this->config_item('db_name').'"');
		$table = $result->row_array();
		return (!empty($table['SCHEMA_NAME']) && strtoupper($table['SCHEMA_NAME']) == strtoupper($this->config_item('db_name')));
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Creates the database based on the tester configuration values.
	 *
	 * @access	public
	 * @return	void
	 */
	public function create_db()
	{
		// use the default database connection to make the new database
		$this->db_connect('default');
		
		$this->CI->load->dbforge();
		
		// create the database if it doesn't exist
		if (!$this->db_exists())
		{
			$this->CI->dbforge->create_database($this->config_item('db_name'));
		}

		// create database
		$this->_is_db_created = TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Removes the test database.
	 *
	 * @access	public
	 * @param	boolean
	 * @return	void
	 */
	public function remove_db()
	{
		$this->db_connect();
		
		$this->CI->load->dbforge();
		
		// drop the database if it exists
		if ($this->db_exists())
		{
			$this->CI->dbforge->drop_database($this->config_item('db_name'));
		}
		$this->_is_db_created = FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Loads the sql from a file in the {module}/test/sql folder. You can enter <dfn>NULL</dfn> or an empty string <dfn>''</dfn> if you are loading an SQL file from your application directory.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function load_sql($file = NULL, $module = '')
	{
		if (!$this->_is_db_created) $this->create_db();

		if (empty($module) OR $module == 'app' OR $module == 'application')
		{
			$sql_path = APPPATH.'tests/sql/'.$file;
		}
		else
		{
			$sql_path = MODULES_PATH.$module.'/tests/sql/'.$file;
		}
		
		// select the database
		$db_name = $this->config_item('db_name');
		$sql = "USE `$db_name`;";
		$this->CI->db->query($sql);

		if (file_exists($sql_path))
		{
			$sql = file_get_contents($sql_path);
			//$sql = str_replace('`', '', $sql);
			$sql = preg_replace('#^/\*(.+)\*/$#U', '', $sql);
			$sql = preg_replace('/^#(.+)$/U', '', $sql);
		}
		$sql_arr = explode(";\n", str_replace("\r\n", "\n", $sql));

		foreach($sql_arr as $s)
		{
			$s = trim($s);
			if (!empty($s))
			{
				$this->CI->db->query($s);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 *  Loads the results of call to a controller. Additionally creates pq function to query dom nodes
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	public function load_page($page, $post = array())
	{
		if (!is_array($post))
		{
			return FALSE;
		}
		
		$this->CI->load->library('user_agent');

		if (!function_exists('curl_init'))
		{
			show_error(lang('error_no_curl_lib'));
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, site_url($page));
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// set cookie jar for sessions and to tell system we are running tests
		$tester_config = $this->CI->config->item('tester');
		curl_setopt($ch, CURLOPT_COOKIEFILE, $tester_config['session_cookiejar_file']); 
		curl_setopt($ch, CURLOPT_COOKIEJAR, $tester_config['session_cookiejar_file']); 
		curl_setopt($ch, CURLOPT_COOKIE, 'tester_dsn='.$this->config_item('dsn_group')); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		if (!empty($post))
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, $this->CI->agent->agent_string());
		
		$output = curl_exec($ch);
		curl_close($ch); 
		
		//http://code.google.com/p/phpquery/wiki/Manual
		phpQuery::newDocumentHTML($output, strtolower($this->CI->config->item('charset')));
		$this->loaded_page = $output;
		return $output;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Convenience method to test if something exists on a page.
	 *
	 * @access	public
	 * @param	string	string to match
	 * @param	boolean	use jquery syntax to match a specific DOM node (TRUE), or to use regular expression (FALSE)
	 * @return	boolean
	 */
	public function page_contains($match, $use_jquery = TRUE)
	{
		if ($use_jquery)
		{
			return pq($match)->size();
		}
		else
		{
			return (preg_match('#'.$match.'#', $this->loaded_page));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 *  Convenience method to test if something exists in a string.
	 *
	 * @access	public
	 * @param	string	string to match
	 * @param	boolean	string to test
	 * @param	boolean	use jquery syntax to match a specific DOM node (TRUE), or to use regular expression (FALSE)
	 * @return	boolean
	 */
	public function str_contains($match, $str, $use_jquery = TRUE)
	{
		phpQuery::newDocument($str);
		if ($use_jquery)
		{
			return pq($match)->size();
		}
		else
		{
			return (preg_match('#'.$match.'#', $str));
		}
	}
	// --------------------------------------------------------------------

	/**
	 *  Magic method which will run the teardown method
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		$this->tear_down();
	}
}

/* End of file Tester_base.php */
/* Location: ./modules/tester/libraries/Tester_base.php */