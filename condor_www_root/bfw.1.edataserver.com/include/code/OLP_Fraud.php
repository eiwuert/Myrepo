<?php
require_once('ecash_common/Fraud/FraudCheck.php');

/**
 * This naming convention kind of sucks.
 *
 */
class OLPFraud
{
	protected $mode;
	protected $property_short;
	
	
	public function __construct($mode, $property_short)
	{
		$this->mode = $mode;
		$this->property_short = $property_short;
	}

	/**
	 * Return an event log object
	 *
	 * @param unknown_type $app_id
	 */
	protected function getEventLog($application_id)
	{
		return Event_Log_Singleton::Get_Instance($this->mode, $application_id);
	}
	
	/**
	 * Returns an ECashApplication with all the 
	 * data in the data array passed to it
	 *
	 * @param array $data
	 * @param int $application_id
	 * @return ECashApplication
	 */
	public static function buildECashAppFromArray($data, $application_id)
	{
		if(!is_array($data) && !$data instanceof ArrayAccess)
		{
			throw new Exception('Invalid data input to '.__CLASS__."::".__METHOD__.'.');
		}
		//Map the field
		//The key is the property in the ECashApplication object
		//The value is the name inside the data array
		static $field_map = array(
			'name_first'             => 'name_first',
			'name_middle'            => 'name_middle',
			'name_last'              => 'name_last',
			'bank_account'           => 'bank_account',
			'bank_aba'               => 'bank_aba',
			'bank_name'              => 'bank_name',
			'bank_account_type'      => 'bank_account_type',
			'phone_home'             => 'phone_home',
			'phone_work'             => 'phone_work',
			'phone_cell'             => 'phone_cell',
			'street'                 => 'home_street',
			'zip_code'               => 'home_zip',
			'city'                   => 'home_city',
			'state'                  => 'home_state',
			'unit'                   => 'home_state',
			'employer_name'          => 'employer_name',
			'income_monthly'         => 'income_monthly_net',
			'income_direct_deposit'  => 'income_direct_depost',
			'legal_id_number'        => 'state_id_number', 
		);
		
		$app = new ECashApplication();
		$app->application_id = $application_id;
		foreach($field_map as $app_key => $data_key)
		{
			if(isset($data[$data_key]))
			{
				$app->$app_key = $data[$data_key];
			}
			elseif(isset($data[$app_key]))
			{
				$app->$app_key = $data[$app_key];
			}
		}
		return $app;
	}
	
	/**
	 * Run fraud rules on an application. Returns the number
	 * of "violations"
	 *
	 * @param ECashApplication $application
	 * @return int
	 */
	public function runFraudRules(ECashApplication $application)
	{
		$ldb = Setup_DB::Get_PDO_Instance('FRAUD', $this->mode, $this->property_short);
		$return = 0; //The number of violations
		$fraud = new FraudCheck($ldb);
		$result = $fraud->processApplication($application);
		
		if(is_array($result) && count($result) > 0)
		{
			//Loop through each rule_type
			//and then loop through their rules and insert them
			foreach($result as $t => $rules)
			{
				if(is_array($rules) && count($rules) > 0)
				{
					foreach($rules as $rule)
					{
						$return++;
						$this->recordFraudEvent($application, $rule);
						$this->insertFraudRule($application, $rule);
					}
				}
			}
		}
		return $return;
	}
	
	/**
	 * Records an event in the event log saying it failed a rule
	 *
	 * @param ECashApplication $application
	 * @param FraudRule $rule
	 * @param string $status
	 */
	protected function recordFraudEvent(ECashApplication $application, FraudRule $rule, $status='VERIFY')
	{
		$event_log = $this->getEventLog($application->application_id);
		$event_name = sprintf('FRAUD_%s_%s_%d',
			$rule->getRuleType(),
			$rule->getName(),
			$rule->getFraudRuleID());
		$event_name = strtoupper($event_name);
		//
		$event_log->Log_Event($event_name, $status);
	}
	
	/**
	 * Insert the rule into the OLP database so that
	 * we can send it out later
	 *
	 * @param ECashApplication $application
	 * @param FraudRule $rule
	 * @return unknown
	 */
	protected function insertFraudRule(ECashApplication $application, FraudRule $rule)
	{
		$app_id = $application->application_id;
		$rul_id = $rule->getFraudRuleID(); 
		$query = "
			INSERT IGNORE INTO
				fraud_application
			(
				fraud_rule_id,
				application_id
			)
			VALUES
			(
				$rul_id,
				$app_id
			)";
		try 
		{
			$db = Setup_DB::Get_Instance('BLACKBOX',$this->mode);
			$db->Query($db->db_info['db'],$query);
		}
		catch (Exception $e)
		{
			
		}
	}
}

?>
