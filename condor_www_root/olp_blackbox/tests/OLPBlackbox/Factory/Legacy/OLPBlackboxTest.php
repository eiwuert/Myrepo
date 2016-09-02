<?php
/**
 * OLPBlackbox_Factory_OLPBlackbox PHPUnit test file.
 * 
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLP blackbox factory class.
 *
 * @group olpbbx_factory_test
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_OLPBlackboxTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Config object.
	 *
	 * @var OLPBlackbox_Config
	 */
	protected $config;
	
	/**
	 * Setup function for each test.
	 * 
	 * @todo get bfw working in cruise control and then remove the hack below.
	 *
	 * @return void
	 */
	public function setUp()
	{
		if (!file_exists(BFW_CODE_DIR.'setup_db.php'))
		{
			// Hack remove me once cruise control works with bfw!
			$this->markTestSkipped("bfw isnt setup so the factory is broken");
		}
		include_once(BFW_CODE_DIR.'setup_db.php');
		$this->config = OLPBlackbox_Config::getInstance();
		$this->config->olp_db = Setup_DB::Get_Instance('blackbox', 'LOCAL');
		$this->config->debug = new OLPBlackbox_DebugConf();
		$this->config->event_log = new FakeEventLog();
		$this->config->session = new FakeSession();
	}
	
	/**
	 * Tests that the getBlackbox function in the OLPBlackbox_Factory_OLPBlackbox,
	 * to make sure a Blackbox object is returned.  This should actually hit
	 * the database and pull a blackbox object.
	 *
	 * @return void
	 */
	public function testGetBlackboxFromDB()
	{
		$blackbox_factory = new OLPBlackbox_Factory_Legacy_OLPBlackbox();
		$blackbox = $blackbox_factory->getBlackbox();
		
		//print_r($blackbox);
		//echo $blackbox;
		//exit();
		
		$this->assertType('Blackbox', $blackbox);
	}
	
	/**
	 * Mock everything so we have one target, and one rule to run, and 
	 * make sure the values in the rule and blackbox data are set so that
	 * the rule will always pass and a winner will be picked.
	 *
	 * @return void
	 */
	public function testGetBlackboxOneRuleOneTargetPass()
	{
		$minimum_income = 100;
		
		$mock_data = array(
			'tiers' => array(
				1=>array('tier_id'=>'1','name'=>'TEST','weight_type'=>'PERCENT')
			)
			,'targets' => array(
				1=>array(
					array(
						'target_name'=>'test'
						,'property_short'=>'test'
						,'target_id'=>'1'
						,'tier_id'=>'1'
						,'percentage'=>'100'
						,'minimum_income'=>$minimum_income
					)
				)
			)
		);
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = $minimum_income+100;
		
		$this->config->debug->setFlag(OLPBlackbox_DebugConf::ABA, FALSE);
		
		$blackbox_factory = $this->getMock('OLPBlackbox_Factory_Legacy_OLPBlackbox', array('getBlackboxData'));
		$blackbox_factory->expects($this->any())->method('getBlackboxData')->will($this->returnValue($mock_data));
		
		$blackbox = $blackbox_factory->getBlackbox();
		
		$winner = $blackbox->pickWinner($data);
		
		$this->assertType('Blackbox_Winner', $winner);
	}
	
	/**
	 * Mock everything so we have one target, and one rule to run, and 
	 * make sure the values in the rule and blackbox data are set so that
	 * the rule will always fail and result in no winner being picked.
	 *
	 * @return void
	 */
	public function testGetBlackboxOneRuleOneTargetFail()
	{
		$minimum_income = 100;
		
		$mock_data = array(
			'tiers' => array(
				1=>array('tier_id'=>'1','name'=>'TEST','weight_type'=>'PERCENT')
			), 
			'targets' => array(
				1=>array(
					array(
						'target_name'=>'test', 
						'property_short'=>'test', 
						'target_id'=>'1', 
						'tier_id'=>'1', 
						'percentage'=>'100', 
						'minimum_income'=>$minimum_income
					)
				)
			)
		);
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = $minimum_income-50;
		
		$this->config->debug->setFlag(OLPBlackbox_DebugConf::ABA, FALSE);
		
		$blackbox_factory = $this->getMock('OLPBlackbox_Factory_Legacy_OLPBlackbox', array('getBlackboxData'));
		$blackbox_factory->expects($this->any())->method('getBlackboxData')->will($this->returnValue($mock_data));
		
		$blackbox = $blackbox_factory->getBlackbox();
		
		$winner = $blackbox->pickWinner($data);
		
		$this->assertFalse($winner);
	}
	
	/**
	 * Data provider for the minimum income test cases we want to run..
	 *
	 * @return array
	 */
	public static function minimumIncomeDataProvider()
	{
		return array(
			array('test1', '100', '800', '500'),
			array('test2', '800', '100', '500'), 
			array('', '800', '1000', '250') 		// We dont expect a winner...
		);
	}
	
	/**
	 * Setup a scenario where there are 2 targets available, and a single rule
	 * where only one of the targets can possibly win, and make sure they
	 * are the winner.
	 *
	 * @param string $winner_name Who we expect to win based on the mocked array 
	 * @param string $test1_minimum The minimum income to win test1 target
	 * @param string $test2_minimum The minimum income to win test2 target
	 * @param string $income The customers income
	 *
	 * @return void
	 *
	 * @dataProvider minimumIncomeDataProvider
	 */
	public function testGetBlackboxMinimumIncome($winner_name, $test1_minimum, $test2_minimum, $income)
	{
		$mock_data = array(
			'tiers' => array(
				1=>array('tier_id'=>'1','name'=>'TEST','weight_type'=>'PERCENT')
			)
			,'targets' => array(
				1=>array(
					array(
						'target_name'=>'test1'
						,'property_short'=>'test1'
						,'target_id'=>'1'
						,'tier_id'=>'1'
						,'percentage'=>'50'
						,'minimum_income'=>$test1_minimum
					)
					,array(
						'target_name'=>'test2'
						,'property_short'=>'test2'
						,'target_id'=>'2'
						,'tier_id'=>'1'
						,'percentage'=>'50'
						,'minimum_income'=>$test2_minimum
					)
				)
			)
		);
		
		$data = new OLPBlackbox_Data();
		$data->income_monthly_net = $income;
		
		$this->config->debug->setFlag(OLPBlackbox_DebugConf::ABA, FALSE);
		
		$blackbox_factory = $this->getMock('OLPBlackbox_Factory_Legacy_OLPBlackbox', array('getBlackboxData'));
		$blackbox_factory->expects($this->any())->method('getBlackboxData')->will($this->returnValue($mock_data));
		
		$blackbox = $blackbox_factory->getBlackbox();
		
		$winner = $blackbox->pickWinner($data);
		
		// Make sure the winner is who we think it should be.
		if ($winner_name)
		{
			$this->assertEquals($winner_name, $winner->getCampaign()->getStateData()->campaign_name);
		}
		else
		{
			$this->assertFalse($winner);
		}
	}
	
	/**
	 * Data provider for the income frequency test cases we want to run..
	 *
	 * @return array
	 */
	public static function incomeFrequencyDataProvider()
	{
		return array(
			array('test1', 'MONTHLY')
			,array('test1', 'TWICE_MONTHLY')
			,array('test1', 'BI_WEEKLY')
			,array('test2', 'WEEKLY')
		);
	}
		
	/**
	 * Check to make sure the income frequency rule is running right.
	 *
	 * @param string $winner_name Who we expect to win based on the mocked array 
	 * @param string $frequency The frequency we want to test
	 *
	 * @return void
	 *
	 * @dataProvider incomeFrequencyDataProvider
	 */
	public function testGetBlackboxIncomeFrequency($winner_name, $frequency)
	{
		$minimum_income = 100;
		
		$mock_data = array(
			'tiers' => array(
				1=>array('tier_id'=>'1','name'=>'TEST','weight_type'=>'PERCENT')
			)
			,'targets' => array(
				1=>array(
					array(
						'target_name'=>'test1'
						,'property_short'=>'test1'
						,'target_id'=>'1'
						,'tier_id'=>'1'
						,'percentage'=>'50'
						,'income_frequency'=>serialize(array(
							'MONTHLY'
							,'TWICE_MONTHLY'
							,'BI_WEEKLY'
						))
					)
					,array(
						'target_name'=>'test2'
						,'property_short'=>'test2'
						,'target_id'=>'1'
						,'tier_id'=>'1'
						,'percentage'=>'50'
						,'income_frequency'=>serialize(array(
							'WEEKLY'
						))
					)
				)
			)
		);
		
		$data = new OLPBlackbox_Data();
		$data->income_frequency = $frequency;
		
		$blackbox_factory = $this->getMock('OLPBlackbox_Factory_Legacy_OLPBlackbox', array('getBlackboxData'));
		$blackbox_factory->expects($this->any())->method('getBlackboxData')->will($this->returnValue($mock_data));
		
		$blackbox = $blackbox_factory->getBlackbox();
		
		$winner = $blackbox->pickWinner($data);
		
		$this->assertEquals($winner_name, $winner->getCampaign()->getStateData()->campaign_name);
	}
}

/**
 * Fake EventLog class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class FakeEventLog
{
	/**
	 * Fakes the Log_Event function.
	 *
	 * @return void
	 */
	public function Log_Event()
	{
		// Do nothing!
	}
}

/**
 * Fake Session class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class FakeSession
{
	/**
	 * Fakes the Hit_Stat function.
	 *
	 * @return void
	 */
	public function Hit_Stat()
	{
		// Do nothing!
	}
}
?>
