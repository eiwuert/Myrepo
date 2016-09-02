<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/Number.1.php';
require_once 'libolution/Validation/String.1.php';

class ECash_Validation_CampaignInfo extends Validation_ObjectValidator_1
{
	public function __construct()
	{
		$this->addValidator('promo_id', new Validation_Number_1(0, 99999));
		$this->addValidator('promo_id', new Validation_String_1(5, 5));
	}
}

?>