<?php
/**
 * OLPBlackbox_Enterprise_Agean_Rule_DataX class file.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc Agean Specific Rules can go in here.
 * 
 */
class OLPBlackbox_Enterprise_Agean_Rule_DataX extends OLPBlackbox_Rule_DataX
{
	const TYPE_AGEAN_PERF	= 'agean-perf';
	const TYPE_AGEAN_TITLE	= 'agean-title';
	
	/**
	 * Array of call_types
	 * 
	 * @var Array $call_type_list
	 */
	protected $call_type_list = Array(
		self::TYPE_AGEAN_PERF,
		self::TYPE_AGEAN_TITLE
		);

	/**
	 * Flag for whether an Adverse Action needs to take place.
	 *
	 * @var bool $aa_hit
	 */
	protected $aa_hit = FALSE;
	
	/**
	 * Array of triggers hit
	 *
	 * @var Array $trigger_hit
	 */
	protected $trigger_hit = array();
	
	/**
	 * Array of readable responses to IDV Errors
	 *
	 * @var Array $response_map
	 */
	protected $response_map = array(
		'idv' => array(
			//Hard fails
			'D1' => 'SSN is invalid',
			'D2' => 'SSN is deceased',
			'D3' => 'SSN not open for issue or issued',
			'D4' => 'OFAC hit',
			'D5' => 'SSN name/address/dob matches failed',
			'D6' => 'SSN issue before DOB',
			'D7' => 'Not 18 years old according to Experian',
			
			//Soft Pass
			'R1' => 'DOB >= 1991 and SSN Issuance > (DOB + 2 years)',
			'R2' => 'DOB < 1991 and SSN Issuance > (DOB + 18 years)',
			'R3' => 'Phone invalid and type not cellular, mobile or PCS',
			'R4' => 'Work phone type fail.'
		),
		
		'bav' => array(
			'D1' => 'ABA not valid or ACH return code is 401 or 101',
			'R1' => 'Bank type is savings or credit union',
		),
		
		'dpb' => array(
			'D1' => 'Score less than 480',
			'D2' => 'Multiple SSNs associated with individual',
			'D3' => 'SSN mismatch with DDA name and address information',
			'D4' => 'Reported as applying for loans with different SSNs',
			'D5' => 'Consumer\'s SSN may have been used by another individual',
			'D6' => 'Bank account associated with fraud',
			'D7' => 'Unpaid, defaulted loans originated in the last 180 days',
			'D8' => 'ABA has a high negative loan default',
			'D9' => '2 or more bank accounts associated with payday loan applications',
			'D10' => 'Open loans > 0',//Agean CRA/DPB-(Changed Description)[MJ]
			'D11' => 'Number of inquiries in the last 60 days > 19',
			'D12' => 'MICR > 2',//Agean CRA/DPB-(Added)[MJ]
			
			'R1' => 'Last name from result does not match last name from input',
			'R2' => 'Number of inquiries in last 60 days between 1 and 19',
			'R3' => 'Number of open loans between 1 and 2',
			'R4' => 'Number of chargeoffs older than 180 days > 0',
			'R5' => 'Number of open loans is null',//Agean CRA/DPB-(Added)[MJ]
			'R6' => 'Work phone not valid'//Agean CRA/DPB-(Added)[MJ]
		),
		
		'cra' => array(//Agean CRA/DPB-(Added)[MJ]
			'D10' => '(SSN Match = Current Tradeline) >= 3',
			'D11' => 'Daily inquiries in the last 60 days > 20',
			'D12' => 'Charge offs in the last 180 days found',
			'D13' => 'Daily inquiries in the last 7 days >= 4',
			'D14' => 'ACH (last) = Returned',
			'D15' => 'ACH returns in the last 60 days >= 3',
			
			'R2' => '(SSN Match = Tradeline) >=1 <=2',
			'R3' => 'Daily Inquiries in the last 60 days >=1 <=19',
			'R4' => 'Charge offs older than 180 days found',
		),
			
		'tt' => array(
			'R1' => 'Customer has recent inquiries.',
			'R2' => 'Customer has recent chargeoffs.',
			'R3' => 'Customer has open loans.',
		)
	);
	
	/**
	 * Array of triggers 
	 *
	 * @var Array $triggers
	 */
	protected $triggers = array(
		'idv' => array(
			'R1' => array(5, 13),
			'R2' => array(6, 13),
			'R3' => array(2),
			'R4' => array(3)
		),

		'bav' => array(
			'R1' => array(8)
		),

		'dpb' => array(
			'R1' => array(15, 13),
			'R2' => array(10),
			'R3' => array(9),
			'R4' => array(11),
			'R5' => array(21)//Agean CRA/DPB-(Added)[MJ]
		),
		
		'tt' => array(
			'R1' => array(18),
			'R2' => array(19),
			'R3' => array(17)
		),
	);
	
	/**
	 * Build the query for DataX call.
	 *
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * 
	 * @return mixed
	 */
	protected function buildQuery(Blackbox_Data $blackbox_data)
	{
		$query = parent::buildQuery($blackbox_data);
		$query['bankaccttype'] = (strcasecmp($blackbox_data->bank_account_type, 'savings') === 0) ? 'savings' : 'checking';
		
		return $query;
	}

	/**
	 *  Gets the Source ID from the current call_type
	 *
	 * @return integer
	 */
	protected function getSourceID()
	{
		$source_id = NULL;
		
		switch ($this->call_type)
		{
			case  self::TYPE_AGEAN_PERF: 	$source_id = 9; break;
			case  self::TYPE_AGEAN_TITLE: 	$source_id = 10; break;
		}
		
		return $source_id;
	}
	
	/**
	 * This will figure out where to pull the decision from based on the call type.
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data State data
	 *
	 * @return void
	 */
	protected function findDecision(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		parent::findDecision($blackbox_data, $state_data);
		
		switch ($this->call_type)
		{
			case self::TYPE_AGEAN_PERF:
				$this->findPerfDecision($blackbox_data, $state_data);
				break;
			case self::TYPE_AGEAN_TITLE:
				$this->findTitleDecision($blackbox_data, $state_data);
				break;
		}
	}
	
	/**
	 * Finds a decision for the TYPE_AGEAN_PERF call
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data State data
	 *
	 * @return void
	 */
	private function findPerfDecision(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$reasons = array();
		$seg_name = '';
		
		$buckets = explode(',', $this->reason);
		
		foreach ($buckets AS $bucket)
		{
			list($seg_name, $seg_bucket) = explode('-', $bucket);
			
			if (isset($this->response_map[$seg_name][$seg_bucket]))
			{
				if ($seg_bucket[0] == 'D')
				{
					switch ($seg_name)
					{
						case 'idv': $aa_stat = 'datax'; break;
						case 'bav': $aa_stat = 'creditbureau'; break;
						case 'dpb': $aa_stat = 'clverify'; break;
						case 'cra': $aa_stat = 'cra'; break;//Agean CRA/DPB-(Added)[MJ]
						default: $aa_stat = 'unknown_' . $seg_name; break;
					}
					
					$this->adverseAction("aa_mail_{$aa_stat}", $blackbox_data, $state_data);
					$this->decision = 'N';
				}
				elseif ($seg_bucket[0] == 'R' && !empty($this->triggers[$seg_name][$seg_bucket]))
				{
					foreach ($this->triggers[$seg_name][$seg_bucket] as $trigger)
					{
						$this->trigger($trigger);
					}
				}
			}
		}
		
		if ($this->decision == 'N')
		{
			// Sadly, fraud report is set to split on pluses not commas
			$this->reason = str_replace(',', '+', $this->reason);
		}
		else
		{
			$this->hitTriggers();
		}
	}
	
	/**
	 * Finds a decision for the TYPE_AGEAN_TITLE call
	 * 
	 * @param Blackbox_Data $blackbox_data The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data State data
	 *
	 * @return void
	 */
	private function findTitleDecision(Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		$reasons = array();
		$seg_name = '';
		
		$buckets = explode(',', $this->reason);
		
		foreach ($buckets AS $bucket)
		{
			list($seg_name, $seg_bucket) = explode('-', $bucket);
			$seg_name = 'tt';
			
			if ($this->decision == 'Y' && $seg_bucket[0] == 'R' && !empty($this->triggers[$seg_name][$seg_bucket]))
			{
				foreach ($this->triggers[$seg_name][$seg_bucket] as $trigger)
				{
					$this->trigger($trigger);
				}
			}
		}
		
		if ($this->decision == 'N')
		{
			// Sadly, fraud report is set to split on pluses not commas
			$this->reason = str_replace(',', '+', $this->reason);
			$this->adverseAction('aa_mail_veritrac', $blackbox_data, $state_data);
		}
		else
		{
			$this->hitTriggers();
		}
	}
	
	/**
	 * Sets the trigger
	 *
	 * @param string $trigger The trigger we are going to hit?
	 * 
	 * @return void
	 */
	protected function trigger($trigger)
	{
		if (!empty($trigger))
		{
			if (empty($this->trigger_hit[$trigger]))
			{
				$this->trigger_hit[$trigger] = TRUE;
			}
		}
	}
	
	/**
	 * This will hit a trigger
	 *
	 * @return void
	 */
	protected function hitTriggers()
	{
		foreach ($this->trigger_hit as $trigger => $true)
		{
			OLPBlackbox_Enterprise_Agean_Triggers::logTrigger($this->config_data->blackbox_mode, $trigger);
		}
	}
	
	/**
	 * Will hit the stats appropriately
	 *
	 * @param bool $valid 					Was the call successfull?
	 * @param Blackbox_Data $blackbox_data 	The data used for the DataX Validation.
	 * @param Blackbox_IStateData $state_data 	State data
	 * 
	 * @return void
	 */
	protected function hitCustomStats($valid, Blackbox_Data $blackbox_data, Blackbox_IStateData $state_data)
	{
		parent::hitCustomStats($valid, $blackbox_data, $state_data);
		
		$stats = array();
		
		$stats[] = ($valid) ? 'agean_perf_pass' : 'agean_perf_fail';

		// Don't hit stats if we're in mode_online_confirmation because we're using the enterprise config and these are bb stats
		if (!empty($stats) && $this->config_data->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
		{
			// Hit the stat
			foreach ($stats as $stat)
			{
				$this->hitSiteStat($stat, $blackbox_data, $state_data);
			}
		}
	}
}
?>
