<?php

/**
 * Adding AALM override to not run for reacts
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 */
class AALM_VendorAPI_Blackbox_Rule_UwRecur extends VendorAPI_Blackbox_Rule_UwRecur 
{
	protected $company;
	
	public function __construct(
		VendorAPI_Blackbox_EventLog $log,
		ECash_WebService_InquiryClient $inquiry_client,
		$company)
	{
		parent::__construct($log, $inquiry_client, NULL, NULL, NULL);
		$this->company = $company;
	}
	
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$state_data->loan_amount_decision = NULL;

		if (!is_bool($this->is_valid))
		{
			$react_id = $state_data->customer_history->getReactID($this->company);
			if($react_id)
			{
				$state_data->loan_amount_decision = $this->getLoanAmountIncrease($react_id);
			}
			$this->is_valid = TRUE;
		}
		return $this->is_valid;
	}
	
	protected function getLoanAmountIncrease($react_application_id)
	{
		$inquiries = $this->inquiry_client->findLastNonReactInquiries($react_application_id);
		
		foreach ($inquiries as $inquiry)
		{
            // This will have to be expaneded to include the new uw sources
            $call_type = strtolower($inquiry->inquiry_type);
			if((strpos($call_type, 'perf') !== FALSE))
			{
				$uwResponse = new ECash_DataX_Responses_Perf();
				$uwResponse->parseXML($inquiry->receive_package);
				return $uwResponse->getLoanAmountDecision();				
			}
		}

		return FALSE;
	}
	
}

?>
