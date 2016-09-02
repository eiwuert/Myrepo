<?php

/**
 * Clarity Status Update Response
 */
class ECash_Clarity_Responses_Status_Update extends Clarity_UW_Response
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