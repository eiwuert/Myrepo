<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/String.1.php';
require_once 'libolution/Validation/Regex.1.php';

class ECash_Validation_Site extends Validation_ObjectValidator_1
{
	public function __construct()
	{
		$this->addValidator('name', new Validation_Regex_1('/.+\..+/', 'must be a valid domain name'));
				
		$this->addValidator('license_key', new Validation_String_1(21, 64));
	}
}

?>