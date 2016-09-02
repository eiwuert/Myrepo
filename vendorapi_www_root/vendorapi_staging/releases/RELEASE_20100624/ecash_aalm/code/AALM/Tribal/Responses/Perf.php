<?php

class AALM_Tribal_Responses_Perf extends TSS_Tribal_Response implements TSS_Tribal_IPerformanceResponse
{
	public function isValid()
	{
		return $this->getDecision() == '1';
	}

	public function getDecision()
	{
		return $this->findNode('/response/decision');
	}

	public function getCode()
	{
		return $this->findNode('/response/code');
	}

	public function getMessage()
	{
		return $this->findNode('/response/message');
	}
}

?>
