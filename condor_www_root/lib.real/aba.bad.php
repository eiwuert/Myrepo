<?php

/**
 *
 * This returns true if the aba number is bad. Some ABA numbers are
 * only bad with account numbers. That is why the account numbers are
 * compared.
 *
 * params:
 *		$aba  This is the aba number
 *		$account  This is the account number.
 *
 * return:
 *		$result  This is true if the aba number is invalid. It returns false
 *             if the aba number is good.
 */
function Aba_Bad ($aba, $account)
{
	
	$bad_abas = array('114924742', '264171241', 
		'061192407', '022000046',
		'072413201', '071900948', '041200775',
		'073972181', '051000101', '072412927',
		'111906271', '113008465', '61091977',
		'271972572', '253177832',
		'63000021', '51000101',
		'124303065' //Galileo
	);
	
	$result = FALSE;
	
	if (in_array($aba, $bad_abas))
	{
		$result = TRUE;
	}

	if ($aba == '044000037' && ($account == '635858443' || $account == '635858442') )
	{
		$result = TRUE;
	}

	if ($aba == '221571473' && ($account == '441803384' || $account == '4450009443'))
	{
		$result = TRUE;
	}

	if ($aba == '075000022' && ($account == '754830073'))
	{
		$result = TRUE;
	}
	
	if ($aba == '042205038' && ($account == '130103009689'))
	{
		$result = TRUE;
	}

	return $result;
}


?>
