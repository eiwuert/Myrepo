<?php

/**
 * AALM auto fund observer
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
class AALM_VendorAPI_Blackbox_DataX_AutoFundObserver implements VendorAPI_Blackbox_DataX_ICallObserver
{
	/**
	 * @var string
	 */
	protected $campaign;

	/**
	 * @var VendorAPI_StatProClient
	 */
	protected $client;

	/**
	 *
	 * @param string $campaign_name
	 * @param VendorAPI_StatProClient $client
	 */
	public function __construct($campaign_name, VendorAPI_StatProClient $client)
	{
		$this->campaign = $campaign_name;
		$this->client = $client;
	}

	/**
	 * Fired when a complete call has been made
	 *
	 * @param VendorAPI_Blackbox_Rule_DataX $caller
	 * @param TSS_DataX_Result $request
	 * @param Blackbox_IStateData $state
	 * @param VendorAPI_Blackbox_Data $data
	 * @return void
	 */
	public function onCall(VendorAPI_Blackbox_Rule_DataX $caller, TSS_DataX_Result $result, $state, VendorAPI_Blackbox_Data $data)
	{
		$stat = 'af_aalm_auto_fund';
		if (isset($state->auto_fund) && $state->auto_fund)
		{
			if ($stat !== NULL)
			{
				$this->client->hitStat($stat);
			}
		}
	}



}

?>
