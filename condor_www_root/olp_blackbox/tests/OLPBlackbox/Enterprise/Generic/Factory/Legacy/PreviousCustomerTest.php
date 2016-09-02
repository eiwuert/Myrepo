<?php

require_once 'OLPBlackboxTestSetup.php';

/**
 * Unit test for the generic previous customer factory
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var OLPBlackbox_Config
	 */
	protected $config;

	/**
	 * @var OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer
	 */
	protected $factory;

	/**
	 * Array of config items that were modified
	 *
	 * @var array
	 */
	protected $config_modified = array();

	/**
	 * Does test setup type stuffs
	 * @return void
	 */
	public function setUp()
	{
		if (!$this->includeFiles())
		{
			$this->markTestSkipped('Required files missing');
		}

		$this->config = OLPBlackbox_Config::getInstance();
		$this->factory = new OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer('test', $this->config);

		$db_info = TEST_GET_DB_INFO();
		$db = new MySQL_Wrapper(TEST_DB_MYSQL4());

		$this->setConfig('debug', new OLPBlackbox_DebugConf());
		$this->setConfig('olp_db', $db);
		$this->config->olp_db->db_info = array('db' => $db_info->name);
	}

	/**
	 * Include the necessary files
	 *
	 * @return bool
	 */
	protected function includeFiles()
	{
		if (!include_once(BFW_DIR.'/include/code/setup_db.php'))
		{
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Does tear down type stuffs
	 * @return void
	 */
	public function tearDown()
	{
		// clean out the debug options
		$this->resetConfig();

		// need to put this back for other tests...
		$this->setConfig('debug', new OLPBlackbox_DebugConf());
	}

	/**
	 * Ensures that when the previous customer debug option is OFF, we get a skip rule
	 * @group previousCustomer
	 * @return void
	 */
	public function testSkipsWhenDebugOptionIsSet()
	{
		$this->config->debug->setFlag(OLPBlackbox_DebugConf::PREV_CUSTOMER, FALSE);
		$rule = $this->factory->getPreviousCustomerRule();

		$this->assertType('OLPBlackbox_DebugRule', $rule);
	}

	/**
	 * Ensure that the collection is set to expire apps in ECash_React mode
	 * @group previousCustomer
	 * @return void
	 */
	public function testExpireAppsInECashReactMode()
	{
		$this->setConfig('blackbox_mode', OLPBlackbox_Config::MODE_ECASH_REACT);
		$rule = $this->factory->getPreviousCustomerRule();

		$this->assertTrue($rule->getExpireApplications());
	}

	/**
	 * Attempt to ensure that a react is run for a single company
	 * @group previousCustomer
	 * @return void
	 */
	public function testReactRunsForSingleCompany()
	{
		$this->setConfig('blackbox_mode', OLPBlackbox_Config::MODE_ECASH_REACT);
		$this->setConfig('is_enterprise', TRUE);

		$this->factory = $this->getMock(
			'OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer',
			array('getCompanies'),
			array('test', $this->config)
		);
		$this->factory->expects($this->never())
			->method('getCompanies');

		$rule = $this->factory->getPreviousCustomerRule();
	}

	/**
	 * Provides a list of companies and the facotries they should get
	 *
	 * @return array
	 */
	public static function provideTargetFactories()
	{
		return array(
			array(
				array('CLK', 'UFC', 'UCL', 'PCL', 'CA', 'D1'),
				'OLPBlackbox_Enterprise_CLK_Factory_Legacy_PreviousCustomer'
			),
			array(
				array('IC', 'IFS', 'ICF', 'IPDL'),
				'OLPBlackbox_Enterprise_Impact_Factory_Legacy_PreviousCustomer'
			),
			array(
				array('CBNK', 'JIFFY'),
				'OLPBlackbox_Enterprise_Agean_Factory_Legacy_PreviousCustomer',
			),
		);
	}

	/**
	 * Test that each company in the list above receives the correct factory
	 *
	 * @param array $companies
	 * @param string $factory_class
	 * @dataProvider provideTargetFactories
	 */
	public function testCompanyGetsCorrectFactory(array $companies, $factory_class)
	{
		foreach ($companies as $name)
		{
			$f = OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer::getInstance($name, $this->config);
			$this->assertType($factory_class, $f);
		}
	}

	/**
	 * Tracks changes to the config
	 *
	 * @param string $option
	 * @param mixed $value
	 * @return void
	 */
	protected function setConfig($option, $value)
	{
		$this->config->{$option} = $value;
		$this->config_modified[] = $option;
	}

	/**
	 * Unsets everything that has been modified
	 * @return void
	 */
	protected function resetConfig()
	{
		foreach ($this->config_modified as $name)
		{
			unset($this->config->{$name});
		}
		$this->config_modified = array();
	}
}

?>