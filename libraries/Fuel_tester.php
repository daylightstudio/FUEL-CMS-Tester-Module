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
 * Fuel Tester object
 *
 * @package		FUEL CMS
 * @subpackage	Libraries
 * @category	Libraries
 * @author		David McReynolds @ Daylight Studio
 * @link		http://docs.getfuelcms.com/modules/tester
 */

// --------------------------------------------------------------------


class Fuel_tester extends Fuel_advanced_module {
	
	/**
	 * Constructor
	 *
	 * The constructor can be passed an array of config values
	 *
	 * @access	public
	 * @param	array	config preferences
	 * @return	void
	 */
	function __construct($params = array())
	{
		parent::__construct();

		$this->CI->load->helper('directory');
		$this->CI->load->helper('inflector');

		
		if (empty($params))
		{
			$params['name'] = 'tester';
		}
		$this->initialize($params);
	}

	// --------------------------------------------------------------------

	/**
	 * Runs the tests and returns an array of result information that can be passed to a view file for display
	 *
	 * @access	public
	 * @param	array	The tests to run
	 * @return	array
	 */
	function run($tests)
	{
		$template = ($this->is_cli())
			? $this->load_view('_admin/report_template_cli', array(), TRUE)
			: $this->load_view('_admin/report_template', array(), TRUE);

		$this->CI->unit->set_template($template);
		$results = array();
		
		foreach($tests as $test)
		{
			$this->CI->unit->reset();
			$test_arr = explode('/', $test);
			$test_class = str_replace(EXT, '', end($test_arr));
			if (preg_match('#'.preg_quote(MODULES_PATH).'#', $test))
			{
				$file_pieces = explode('/', str_replace(MODULES_PATH, '', $test));
				$module = $file_pieces[0];
			}
			else
			{
				$module = 'application';
			}

			require_once($test);

			$test_obj = new $test_class();
			$test_obj->setup();
			$methods = get_class_methods($test_obj);
			foreach($methods as $method)
			{
				if (preg_match('/^test_\w+/', $method))
				{
					$test_obj->$method();
				}
			}
			$test_obj->tear_down();
			
			$key = '<strong>'.$module.':</strong> '.humanize($test_class);
			$results[$key] = array();
			$results[$key]['report'] = $this->CI->unit->report(array(), !$this->is_cli());
			$results[$key]['raw'] = $this->CI->unit->result();
			$results[$key]['passed'] = 0; // initialize
			$results[$key]['failed'] = 0; // initialize
		}

		$results['total_passed'] = 0;
		$results['total_failed'] = 0;
		$lang_results = lang('ut_result');
		$lang_passed = lang('ut_passed');
		$lang_failed = lang('ut_failed');

		foreach($results as $key => $result)
		{
			if (is_array($result))
			{
				foreach($result['raw'] as $k => $v)
				{
					if (strtolower($v[$lang_results]) == strtolower($lang_passed))
					{
						$results['total_passed']++;
						$results[$key]['passed']++;
					}
					else
					{
						$results['total_failed']++;
						$results[$key]['failed']++;
					}
				}
			}
		}
		return $results;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Returns an array of test(s) across one or more modules
	 *
	 * @access	public
	 * @param	string	The module to retrieve the tests from (optional)
	 * @param	array	The folder name within the module. The default is 'tests'
	 * @param	boolean	If TRUE, will just return just the paths to the tests
	 * @return	array
	 */
	function get_tests($module = NULL, $folders = array(), $just_tests = FALSE)
	{
		if (!empty($module))
		{
			$test_list = $this->_get_tests($module, $folders);
		}
		else
		{
			// get tests from application folder
			$app_tests = $this->_get_tests();

			// get tests for modules
			$modules = $this->fuel->config('modules_allowed');
			
			// add the fuel folder in the list
			$modules[] = 'fuel';

			$test_list = array();
			foreach($modules as $module)
			{
				$module_tests_list = $this->_get_tests($module, $folders);

				// merge the arrays with a + to preserve keys
				if (!empty($module_tests_list))
				{
					$test_list = $test_list + $module_tests_list;
				}
			}

			// merge the arrays with a + to preserve keys
			if (!empty($app_tests))
			{
				$test_list = $test_list + $app_tests;
			}
			asort($test_list);
		}
		
		if ($just_tests)
		{
			return array_keys($test_list);
		}
		return $test_list;
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
		return Tester_base::is_cli();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns an array of test(s) across one or more modules
	 *
	 * @access	protected
	 * @param	string	The module to retrieve the tests from (optional)
	 * @param	array	The folder name within the module. The default is 'tests'
	 * @return	array
	 */
	protected function _get_tests($module = NULL, $folders = array())
	{
		// convert folders to an array if just a string
		if (empty($folders))
		{
			$folders = array('');
		}
		if (!is_array($folders))
		{
			$folders = array($folders);
		}
		
		$return = array();
	
		foreach($folders as $folder)
		{
			if (!empty($module) AND ($module != 'app' AND $module != 'application'))
			{
				$dir_path = MODULES_PATH.$module.'/tests/'.$folder;
			}
			else
			{
				$dir_path = APPPATH.'tests/'.$folder;
				$module = 'application';
			}

			// if a directory, grab all the tests in it
			if (is_dir($dir_path))
			{
				$tests = directory_to_array($dir_path);
				foreach($tests as $test)
				{
					$dir = $test;
					if (substr(strtoupper(PHP_OS), 0, 3) != 'WIN')
					{
						$dir = '/'.$dir;	
					}
					if (substr($test, -9) ==  '_test.php')
					{
						$test_arr = explode('/', $test);
						$val = str_replace(EXT, '', end($test_arr));
						$return[$dir] = $module.': '.humanize($val);
					}
				}
			}
			
			// if a file (without extension), add just that test
			else if (is_file($dir_path.EXT))
			{
				$dir_path_arr = explode('/', $dir_path);
				$val = end($dir_path_arr);
				$return[$dir_path.EXT] = $module.': '.humanize($val);
			}

			// if a file (with an extension), add just that test
			else if (substr($dir_path, -4) ==  EXT)
			{
				$dir_path_arr = explode('/', $dir_path);
				$val = str_replace(EXT, '', end($dir_path_arr));
				$return[$dir_path] = $module.': '.humanize($val);
			}
		}
		
		return $return;
	}
}

/* End of file Fuel_tester.php */
/* Location: ./modules/tester/libraries/Fuel_tester.php */