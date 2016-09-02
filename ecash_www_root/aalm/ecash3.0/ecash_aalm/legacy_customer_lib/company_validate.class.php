<?php


class Company_Validate extends Validate
{

	public function Validate_Employment($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->employer_name = FALSE;
		$val_ruleset->phone_work = FALSE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->job_title = FALSE;
		$val_ruleset->income_monthly = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}
	
	public function Validate_General_Info($data)
	{
		$val_ruleset = (object) array();
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->phone_home = TRUE;
		$val_ruleset->phone_cell = FALSE;
		$val_ruleset->phone_work = FALSE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->income_monthly = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}
		
	public function Validate_Bank_Info($data)
	{
		$val_ruleset = (object) array();
		$val_ruleset->bank_aba = TRUE;
		$val_ruleset->bank_name = FALSE;
		$val_ruleset->bank_account = TRUE;

		if( !isset($data->bank_account_type) || !in_array($data->bank_account_type, array('checking','savings')) )
		$this->validation_errors['bank_account_type'] = "Invalid";

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}
	
	public function Validate_Personal($data)
	{
		$val_ruleset = (object) array();
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->ssn = TRUE;
		$val_ruleset->legal_id_number = FALSE;
		$val_ruleset->street = TRUE;
		$val_ruleset->unit = FALSE;
		$val_ruleset->city = TRUE;
		$val_ruleset->zip = TRUE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->state = TRUE;

		if( !checkdate($data->EditAppPersonalInfoCustDobmonth, $data-EditAppPersonalInfoCustDobday, $data->EditAppPersonalInfoCustDobyear) )
		{
			$this->validation_errors['dob'] = "Invalid";
		}

		$this->Validate_Data($val_ruleset, $data);
		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}

}

?>
