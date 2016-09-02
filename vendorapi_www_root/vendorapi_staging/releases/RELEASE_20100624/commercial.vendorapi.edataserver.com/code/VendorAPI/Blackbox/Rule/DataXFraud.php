<?php

/**
 * DataX Fraud Check Rule
 *
 * @author Jim Wu <jim.wu@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_DataXFraud extends VendorAPI_Blackbox_Rule
{
	/**
	 * @var TSS_DataX_Call
	 */
	protected $call;

	/**
	 * @var TSS_DataX_IResponse
	 */
	protected $response;

	/**
	 * @param VendorAPI_Blackbox_EventLog $log
	 * @param TSS_DataX_Call $call
	 * @return void
	 */
	public function __construct(VendorAPI_Blackbox_EventLog $log, TSS_DataX_Call $call)
	{
		parent::__construct($log);
		$this->call = $call;
	}

	/**
	 * Sets the name used for event logging
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#getEventName()
	 */
	protected function getEventName()
	{
		return 'DATAX_FRAUD';
	}

	/**
	 * Sets the failure reason stored in the state object when invalid
	 * @see code/VendorAPI/Blackbox/VendorAPI_Blackbox_Rule#getFailureReason()
	 */
	protected function getFailureReason()
	{
		return new VendorAPI_Blackbox_FailureReason("DATAX_FRAUD", "DATAX FRAUD CHECK FAILED!");
	}

	/**
	 * Checks whether the rule has enough data to run
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#canRun()
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}

	/**
	 * Runs the rule
	 * @see lib/blackbox/Blackbox/Blackbox_Rule#runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		if (empty($data->ip_address))
		{
			return FALSE;
		}

		if ($data->application_id > 0)
		{
			$request['track_id'] = $data->application_id;
		}
		elseif  ($data->external_id > 0)
		{
			$request['track_id'] = $data->external_id;
		}
		else
		{
			$request['track_id'] = 0;
		}
		$request['track_key'] = $data->track_id;
		$request['ip_address'] = $data->ip_address;
		$request['ioBlackBox'] = $data->ioBlackBox;

		$result = $this->call->execute($request);

		$log_file = fopen('/var/log/vendor_api/datax_fraud.log', 'a');
		$log = new Log_StreamLogger_1($log_file);
		$log->write(print_r($result, 1), Log_ILog_1::LOG_DEBUG);

		return $result->isValid();
	}
}

?>
