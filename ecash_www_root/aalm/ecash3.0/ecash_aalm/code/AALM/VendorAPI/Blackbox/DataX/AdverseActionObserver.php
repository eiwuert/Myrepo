<?php

/**
 * AALM adverse action observer
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class AALM_VendorAPI_Blackbox_DataX_AdverseActionObserver extends VendorAPI_Blackbox_DataX_AdverseActionObserver
{
	/**
	 * (non-PHPdoc)
	 * @see code/VendorAPI/Blackbox/DataX/VendorAPI_Blackbox_DataX_AdverseActionObserver#getAdverseAction()
	 */
	protected function getAdverseAction(TSS_DataX_Result $result)
	{
		$failed = $this->findFirstFailedBucket($result->getResponse());

		switch ($failed)
		{
			case 'CRA':
				return 'aa_aalm_cra_denial';
			case 'TLT':
				return 'aa_aalm_teletrack_denial';
		}
		return 'aa_denial_datax_entgen';
	}

	/**
	 * Finds the first failed bucket
	 *
	 * A failed bucket should have a value starting with 'D' and
	 * additionally have a result of N in its corresponding segment
	 *
	 * @param $response
	 * @return string|null
	 */
	protected function findFirstFailedBucket(TSS_DataX_IResponse $response)
	{
		/* @var $response TSS_DataX_Responses_AALMPerf */
		foreach ($response->getDecisionBuckets() as $bucket=>$value)
		{
			if ($value{0} === 'D'
					&& $response->getSegmentDecision($bucket) === 'N')
			{
				return $bucket;
			}
		}
		return NULL;
	}
}

?>