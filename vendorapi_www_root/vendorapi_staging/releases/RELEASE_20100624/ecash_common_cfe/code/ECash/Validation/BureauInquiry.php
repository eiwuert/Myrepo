<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/String.1.php';

class ECash_Validation_BureauInquiry extends Validation_ObjectValidator_1
{
	public function __construct()
	{
		$this->addValidator('inquiry_type', new Validation_String_1(1, 20));
	}
}

?>