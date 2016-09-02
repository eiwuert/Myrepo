<?php

require_once 'libolution/Validation/ObjectValidator.1.php';
require_once 'libolution/Validation/Number.1.php';
require_once 'libolution/Validation/String.1.php';
require_once 'libolution/Validation/Set.1.php';
require_once 'libolution/Validation/Regex.1.php';
require_once 'libolution/Validation/Date.1.php';

class ECash_Validation_CFCApplication extends Validation_ObjectValidator_1
{
	public function __construct()
	{		
		$this->addValidator('name_first', new Validation_String_1(1, 25));
		$this->addValidator('name_last', new Validation_String_1(1, 25));
	
		$this->addValidator('street', new Validation_String_1(1, 25));

		$this->addValidator('city', new Validation_String_1(1, 25));

		//don't allow New York or Wisconsin
		//$this->addValidator('state', new Validation_Set_1(array('ny', 'wi'), FALSE, FALSE));
	
		$this->addValidator('zip_code', new Validation_Number_1(0, 99999));
		$this->addValidator('zip_code', new Validation_String_1(5, 5));
		
		$this->addValidator('dob', new Validation_Date_1());

		$this->addValidator('ssn', new Validation_Number_1(0, 999999999));
		$this->addValidator('ssn', new Validation_String_1(9, 9));

		$this->addValidator('email', new Validation_Regex_1('/[^@]+@[^@]+\.[^@]+/', 'must be a valid email address'));		
		
		$this->addValidator('phone_home', new Validation_String_1(10, 10));
		//work phone not required

		$this->addValidator('loan_type', new Validation_Set_1(array('classic', 'gold'), TRUE, FALSE));

		$this->addValidator('application_status', new Validation_Set_1(array(
			   'queued::verification::applicant::*root', //pending
			   'approved::servicing::customer::*root', //approved
			   'denied::applicant::*root' //declined
			   															), TRUE, FALSE));
		
		//possibly problematic fields
		//is_react (set to 'no')
		//bank name (set to 0 length string)
		//is_watched (set to 'no')
		//schedule_model_id (set to 0)		
		
	}
}

?>
