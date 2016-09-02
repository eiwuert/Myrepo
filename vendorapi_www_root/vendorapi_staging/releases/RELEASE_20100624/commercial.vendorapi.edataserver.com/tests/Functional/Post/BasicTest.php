<?php
class Functional_Post_BasicTest extends FunctionalTestCase
{
	protected $_enterprise;
	protected $_company;
	protected $_user;
	protected $_pass;
	protected $_api;

	public function setUp()
	{
		$this->_enterprise = $GLOBALS['enterprise'];
		$this->_company    = $GLOBALS['company'];
		$this->_user       = $GLOBALS['api_user'];
		$this->_pass       = $GLOBALS['api_pass'];

		parent::setUp();

		$this->_api = TestAPI::getInstance(
			$this->_enterprise,
			$this->_company,
			'DEV',
			$this->_user,
			$this->_pass
		);
	}

	public function tearDown()
	{
		$this->_api = NULL;
	}

	public function testOutcomeIsSuccess()
	{
		$data = unserialize(file_get_contents(dirname(__FILE__).'/_fixtures/application_array/'.$this->_company.'_basic.inc'));
		$args = array(
			$data
		);
	
		$r = $this->_api->executeAction('post', $args, FALSE);
		$this->assertEquals(1, $r['outcome']);
	}
	
	
	
	public function xtestScrubsApplication()
	{
		$data = unserialize(file_get_contents(dirname(__FILE__).'/_fixtures/application_array/'.$this->_company.'_basic.inc'));
		$args = array(
			$data
		);
	
		$r = $this->_api->executeAction('post', $args, TRUE);
		$this->assertEquals(1, $r['outcome']);
		
	}

	public static function stateDataScenarioProvider()
	{
		return array(
			array('rework', 'Rework', 1),
			array('qualifyexception', 'Exception in Qualify', 0),
			array('pass', 'Passed Application', 1),
		);
	}

	/**
	 * @dataProvider stateDataScenarioProvider
	 */
	public function testStateDataScenario($check_name, $description, $expected_outcome)
	{
		$data = unserialize(file_get_contents(dirname(__FILE__).'/_fixtures/application_array/'.$this->_company.'_' . $check_name . '.inc'));
		$expected_state = file_get_contents(dirname(__FILE__).'/_expectation/state_object/'.$this->_company.'_'. $check_name . '.inc');
		
		$args = array(
			$data
		);

		$r = $this->_api->executeAction('post', $args, FALSE);
		
		$this->assertEquals($expected_outcome, $r['outcome'], "{$description}: Call Failed");
		$this->compareStrippedStateTableData(
			unserialize($expected_state), 
			unserialize($r['state_object']),
			array(
				'eventlog' => array('date_created'),
				'bureau_inquiry_failed' => array('received_package', 'date_created', 'trace_info', 'timer'),
				'application' => array('date_fund_estimated', 'date_first_payment', 'apr'),
			),
			"{$description}: State Objects Not Matching"
		);
	}

	public function testECashReactPass()
	{
		$file = dirname(__FILE__).'/_fixtures/application_array/'.$this->_company.'_ecashreact.inc';
		if (!file_exists($file))
		{
			$this->markTestSkipped("No ecashreact fixture.");
		}
		else
		{
			$data = unserialize(file_get_contents($file));
			$this->setupReactApp($data);
			$args = array(
				$data
			);
			$r = $this->_api->executeAction('post', $args, FALSE);
			$this->assertEquals(1, $r['outcome'], "React Fail");
			$this->assertEquals(TRUE, $r['result']['qualified']);
			$expected_state = unserialize(file_get_contents(dirname(__FILE__).'/_expectation/state_object/'.$this->_company.'_ecashreact.inc'));

			$this->compareStrippedStateTableData(
				$expected_state, 
				unserialize($r['state_object']),
				array(
					'eventlog' => array('date_created'),
					'bureau_inquiry_failed' => array('received_package', 'date_created', 'trace_info', 'timer'),
					'application' => array('date_fund_estimated', 'date_first_payment', 'apr', 'rule_set_id', 'enterprise_site_id'),
				),
				"ECashReact: State Objects Not Matching"
			);

		}
	}
	
	public function setupReactApp($data)
	{
		$db = getTestPDODatabase();
		$db->exec("USE ".$GLOBALS['db_name']);

		$stmt = $db->prepare("SELECT application_status_id FROM application_status_flat WHERE level0=? AND level0_name=? and active_status=?");
		
		$stmt->execute(array('paid', 'Inactive (Paid)', 'active'));
		if ($row = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$status_id = $row->application_status_id;
		}
		$stmt = $db->prepare('SELECT company_id FROM company WHERE name_short = ?');
		$stmt->execute(array($this->_company));
		if ($row = $stmt->fetch(PDO::FETCH_OBJ))
		{
			$company_id = $row->company_id;
		}
		$app_model = new ECash_Models_Application(new FakeDb($db));
		$cols = $app_model->getColumns();
		foreach ($data as $col => $val)
		{
			if (in_array($col, $cols))
			{
				$app_model->$col = $val;
			}
		}
		$app_model->application_id = $data['react_application_id'];
		$app_model->application_status_id = $status_id;
		$app_model->company_id = "(SELECT company_id FROM company WHERE name_short = '{$this->_company}')";
		$app_model->olp_process = 'email_confirmation';
		$app_model->fund_actual = 200;
		$app_model->date_fund_actual = "2009-04-24";
		$app_model->dob = date('Y-m-d', strtotime($app_model->dob));
		$app_model->company_id = $company_id;

		$app_model->save();
	}

	/**
	 * Will strip columns out of the state data and then compare it
	 *
	 * The $columns_to_strip array should be keyed by table name with the value 
	 * being an array of column names you want removed.
	 *
	 * @param VendorAPI_StateObject $state1
	 * @param VendorAPI_StateObject $state2
	 * @param array $columns_to_strip
	 * @param string $message
	 */
	protected function compareStrippedStateTableData(VendorAPI_StateObject $state1, VendorAPI_StateObject $state2, array $columns_to_strip, $message)
	{
		$this->assertEquals(
			$this->stripColumnsFromTables($state1->getTableDataSince(), $columns_to_strip), 
			$this->stripColumnsFromTables($state2->getTableDataSince(), $columns_to_strip),
			$message
		);
	}

	/**
	 * Strips a list of given columns from an array of state table data.
	 *
	 * The $columns_to_strip array should be keyed by table name with the value 
	 * being an array of column names you want removed.
	 *
	 * The $state_data array can be retreived using the getTableDataSince() method
	 * on the VendorAPI_StateObject class.
	 *
	 * @param array $state_data
	 * @param array $columns_to_strip
	 * @return array
	 */
	protected function stripColumnsFromTables(array $state_data, array $columns_to_strip)
	{
		foreach ($columns_to_strip as $table => $columns)
		{
			if (!empty($state_data[$table]))
			{
				if (is_int(reset(array_keys($state_data[$table]))))
				{
					foreach ($state_data[$table] as $i => $rows)
					{
						$state_data[$table][$i] = array_diff_key($state_data[$table][$i], array_flip($columns));
					}
				}
				else
				{
					$state_data[$table] = array_diff_key($state_data[$table], array_flip($columns));
				}
			}
		}

		return $state_data;
	}

	public function getDataset()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array());
		//return $this->getFixture('generic_post');
	}
}

class FakeDb extends DB_Database_1
{
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}	
	public function setPDO(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
}
