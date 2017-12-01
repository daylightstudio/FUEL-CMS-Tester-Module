<?php
require_once(FUEL_PATH.'/libraries/Fuel_base_controller.php');
require_once(MODULES_PATH.TESTER_FOLDER.'/libraries/Tester_base.php');

class Tester extends Fuel_base_controller {
	
	public $module_uri = 'tester';
	public $nav_selected = 'tools/tester';
	
	function __construct()
	{
		$validate = (php_sapi_name() == 'cli' or defined('STDIN')) ? FALSE : TRUE;
		parent::__construct($validate);
		
		// must load first
		$this->load->library('unit_test');
		
		if ($validate)
		{
			$this->_validate_user('tools/tester');
		}
	}
	
	function index()
	{
		
		$test_list = $this->fuel->tester->get_tests();
		$vars['test_list'] = $test_list;
		$vars['form_action'] = 'tools/tester/run';
		
		$fields['tests'] = array('type' => 'multi', 'options' => $test_list, $this->input->post('tests'));

		$this->load->library('form_builder');
		$this->form_builder->load_custom_fields(APPPATH.'config/custom_fields.php');
		
		$this->form_builder->question_keys = array();
		$this->form_builder->submit_value = null;
		$this->form_builder->use_form_tag = FALSE;
		$this->form_builder->set_fields($fields);
		$this->form_builder->display_errors = FALSE;
		$form = $this->form_builder->render();
		$vars['form'] = $form;
		
		$crumbs = array('tools' => lang('section_tools'), lang('module_tester'));
		$this->fuel->admin->set_titlebar($crumbs, 'ico_tools_tester');
		$this->fuel->admin->render('_admin/tester', $vars, Fuel_admin::DISPLAY_NO_ACTION);
	}
	
	function run()
	{
		$is_cli = $this->fuel->tester->is_cli();

		if ($is_cli)
		{
			if (empty($_SERVER['argv'][3]))
			{
				$module = 'application';
				// $this->output->set_output(lang('tester_no_cli_arguments'));
				// return;
			}
			else
			{
				$module = $_SERVER['argv'][2];
			}
			
			$folders = array();
			
			if (isset($_SERVER['argv'][3]))
			{
				// now loop through the argv arguments to get the folders/tests
				for ($i = 3; $i < count($_SERVER['argv']); $i++)
				{
					if (!empty($_SERVER['argv'][$i]))
					{
						$folders[] = $_SERVER['argv'][$i];
					}
				}
			}

			$tests = $this->fuel->tester->get_tests($module, $folders, TRUE);
		}
		else
		{
			// Check if the user came from the main 'Tester' tool page
			$tests = $this->input->post('tests');
			if (empty($tests))
			{
				// Check if tests are being re-run from the test results page
				$serialized = $this->input->post('tests_serialized');
				if (!empty($serialized))
				{
					$tests = unserialize(base64_decode($serialized));
				}
			}
			if (empty($tests))
			{
				redirect(fuel_url('tools/tester'));
			}

			// Only get valid tests, and eliminate potentially injected filenames
			$tests = array_intersect($tests, array_keys($this->fuel->tester->get_tests()));
		}
		
		$vars = array();
		$vars['results'] = $this->fuel->tester->run($tests);
		
		if ($is_cli)
		{
			$this->load->module_view(TESTER_FOLDER, '_admin/tester_results_cli', $vars);
		}
		else
		{
			$vars['tests_serialized'] = base64_encode(serialize($tests));

			$crumbs = array('tools' => lang('section_tools'), 'tools/tester' => lang('module_tester'), lang('tester_results'));
			$this->fuel->admin->set_titlebar($crumbs, 'ico_tools_tester');
			$this->fuel->admin->render('_admin/tester_results', $vars, Fuel_admin::DISPLAY_NO_ACTION);
		}
	}

}

/* End of file tester.php */
/* Location: ./fuel/modules/tester/controllers/tester.php */
