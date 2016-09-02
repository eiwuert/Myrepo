<?php

/**
 * Abstract adverse action observer
 *
 * This serves as the base for most of the adverse action observers,
 * except CLK (due to them existing at multiple price points). Upon
 * a failed DataX call, this immediately hits the adverse action stat.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
abstract class VendorAPI_Blackbox_DataX_AdverseActionObserver implements VendorAPI_Blackbox_DataX_ICallObserver
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
		if (!$result->isValid())
		{
			$stat = $this->getAdverseAction($result);
			if ($stat !== NULL)
			{
				$this->client->hitStat($stat);
			}
		}
	}

	/**
	 * Determine the correct adverse action stat for the given (invalid) response
	 * @param $response
	 * @return string|null
	 */
	abstract protected function getAdverseAction(TSS_DataX_Result $result);

	/**
	 * Finds the first bucket with a failed ('D#') result
	 * @param TSS_DataX_IPerformanceResponse $response
	 * @return string|null
	 */
	protected function findFirstFailedBucket(TSS_DataX_IPerformanceResponse $response)
	{
		foreach ($response->getDecisionBuckets() as $bucket=>$result)
		{
			if ($result{0} === 'D')
			{
				return $bucket;
			}
		}
		return NULL;
	}
}

?>
