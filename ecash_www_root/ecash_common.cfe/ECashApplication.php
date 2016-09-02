<?php

require_once(LIBOLUTION_DIR . "Object.1.php");

class ECashApplication extends Object_1
{
	public $application_id;
	public $company_id;
	public $display_short;
	public $company_short;
	public $status_long;
	public $status;
	public $level1;
	
	public $name_first;
	public $name_middle;
	public $name_last;
	public $phone_home;
	public $phone_cell;
	public $phone_fax;
	public $street;
	public $unit;
	public $city;
	public $state;
	public $county;
	public $zip_code;
	public $ssn;
	public $legal_id_number;
	public $email;
	public $bank_name;
	public $bank_account;
	public $bank_account_type;
	public $bank_aba;
	public $employer_name;
	public $phone_work;
	public $phone_work_ext;
	public $job_title;
	public $income_monthly;
	public $income_direct_deposit;

	/**
	 * use this to load data from * OLP's application array all at
	 * once.
	 */
	/*
	public function loadOLP()
	{
	}
	*/
}

?>
