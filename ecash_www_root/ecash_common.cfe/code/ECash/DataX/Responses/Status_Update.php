<?php

/**
 * DataX Status Update Response
 */
class ECash_DataX_Responses_Status_Update extends TSS_DataX_Response
{
	public function isValid()
	{
		return $this->getDecision() == 'Success';
	}

	public function getDecision()
	{
		return ($this->findNode('/DataxResponse/Response/Data/Complete') == TRUE ? 'Success' : 'Fail');
	}
}

?>