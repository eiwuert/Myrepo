<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/Set.1.php';

class ECash_Validation_Demographics extends Validation_ObjectValidator_1
{
	public function __construct()
	{
		//yes or no
		//$this->addValidator('has_checking', new Validation_Set_1(array('yes', 'no'), TRUE, FALSE));
		//$this->addValidator('opt_in', new Validation_Set_1(array('yes', 'no'), TRUE, FALSE));
	}
}

?>