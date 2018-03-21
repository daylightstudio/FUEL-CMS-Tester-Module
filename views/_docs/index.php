<h1>Tester Module Documentation</h1>
<p>This Tester documentation is for version <?=TESTER_VERSION?>.</p>

<h2>Overview</h2>
<p>The Tester module allows you to easily create tests and run them in the FUEL admin. 
To create a test, add a <dfn>test</dfn> folder within your applications folder. Tester will read that folder to create it's list of tests you are able to run.
It will even scan other modules for test directories to include in it's list of tests to run. 
You can also run your tests via the command line in a terminal prompt:<p>

<pre class="php:brush">
php index.php tester/run fuel Fuel_cache_test.php
</pre>

<p class="important">If you are on a Mac and having trouble where the script is outputting nothing, you may need to make sure 
you are calling the right php binary. For example, you may need to use something like /Applications/MAMP/bin/php/php5.3.6/bin/php.
Here is a thread that talks about it more:
<a href="http://codeigniter.com/forums/viewthread/130383/" target="_blank">http://codeigniter.com/forums/viewthread/130383/</a>
Hopefully it saves you some time too!
</p>

<p>Some other important features to point out:</p>
<ul>
	<li>If you have SQL that you want to include in your test, add it to a <dfn>tests/sql</dfn> folder and you can call it in your test class's setup method (see below)</li>
	<li>Test classes should always end with the suffix <dfn>_test</dfn> (e.g. my_app_test.php)</li>
	<li>Test class methods should always begin with the prefix <dfn>test_</dfn></li>
	<li>Test database information can be set in the <dfn>config/tester.php</dfn> file</li>
	<li>The constant <dfn>TESTING</dfn> is created when running a test so you can use this in your application for test specific code</li>
</ul>

<?=generate_config_info()?>


<p class="important">You must use a database user that has the ability to create databases since a separate database is created for testing.</p>

<h2>Example</h2>
<pre class="php:brush">
&lt;?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class My_site_test extends Tester_base {
	
	public function __construct()
	{
		parent::__construct();
	}

	public function setup()
	{
		$this->load_sql('test_generic_schema.sql');

		// load a basic MY_Model to test
		require_once('test_custom_records_model.php');
	}
	
	public function test_find_by_key()
	{
		$test_custom_records_model = new Test_custom_records_model();

		// test find_by_key
		$record = $test_custom_records_model->find_by_key(1);
		$test = $record->full_name;
		$expected = 'Darth Vader';
		$this->run($test, $expected, 'find_by_key custom record object property test');
	
		// test get_full_name() method version
		$test = $record->get_full_name();
		$this->run($test, $expected, 'find_one custom record object method test');
	}
	
	public function test_goto_page()
	{
		//http://code.google.com/p/phpquery/wiki/Manual
		$post['test']= 'test';
		$home = $this->load_page('home', $post);

		$test = pq("#content")->size();
		$expected = 1;
		$this->run($test, $expected, 'Test for content node');

		$test = pq("#logo")->size();
		$expected = 1;
		$this->run($test, $expected, 'Test for logo node');
	}
	

}


</pre>

<?=generate_toc('libraries', 'tester', array('phpQuery', 'MY_Unit_test'))?>