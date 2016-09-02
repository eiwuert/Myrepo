<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/Number.1.php';
require_once 'libolution/Validation/String.1.php';
require_once 'libolution/Validation/Date.1.php';

class ECash_Validation_Application extends Validation_ObjectValidator_1
{
	public function __construct()
	{
		$this->addValidator('ssn', new Validation_Number_1(0, 999999999));
		$this->addValidator('ssn', new Validation_String_1(9, 9));		

		//must be a timestamp		
		$this->addValidator('dob', new Validation_Number_1(-PHP_INT_MAX, PHP_INT_MAX));

		$this->addValidator('phone_home', new Validation_String_1(10, 10));		
		$this->addValidator('phone_work', new Validation_String_1(10, 10));
		
	}
}

?>