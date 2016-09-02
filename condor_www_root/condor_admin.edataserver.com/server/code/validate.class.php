<?php

require_once(DIR_LIB . "data_validation.1.php");

class Validate
{
	private $validation_rules;
	private $validation_errors;
	private $dv_obj;
	private $last_normalized;

	function __construct($server)
	{
		//changed to include all of the time
		include("validation_rules.php");

		if( isset($_SESSION['holidays']) )
		{
			$holidays = $_SESSION['holidays'];
		}
		else
		{
			$holiday_obj = new Bank_Holidays();

			$holidays = $holiday_obj->Get_Holidays();
		}

		$this->validation_rules = $rules;
		$this->validation_errors = array();
		$this->last_normalized = array();
		$this->dv_obj = new Data_Validation($holidays);
	}

	public function Get_Last_Normalized()
	{
		return (object) $this->last_normalized;
	}

	public function Clear_Normalized()
	{
		$this->last_normalized = array();

		return TRUE;
	}

	private function Validate_Data($val_ruleset, $data)
	{
		(object) $data;

		$this->Clear_Normalized();

		foreach($val_ruleset as $field => $required)
		{
			if( isset($data->{$field}) && strlen(trim($data->{$field})) )
			{
				if( isset($this->validation_rules->normalize->{$field}) )
				{
					$normalized_data = $this->dv_obj->Normalize(trim($data->$field), $this->validation_rules->normalize->{$field});
					//echo "Normalized: {$normalized_data}<pre>" . print_r($this->validation_rules->normalize->{$field}); echo "</pre><br>";
				}
				else
				{
					$normalized_data = $data->{$field};
				}

				if( is_string($normalized_data) )
				$normalized_data = strtolower($normalized_data);

				$this->last_normalized[$field] = $normalized_data;

				$val_result = $this->dv_obj->Validate($normalized_data, $this->validation_rules->validation->{$field});

				if( !$val_result['status'] )
				{
					$this->validation_errors[$field] = "Invalid";
				}

			}
			elseif( $required )
			{
				$this->validation_errors[$field] = "Required";
			}
		}

		//echo "VAL ERRORS:<pre>"; print_r($this->validation_errors); echo "</pre><br><br>";

		return TRUE;
	}

	private function Consolidate_Name_Errors()
	{
		if( isset($this->validation_errors['name_last']) )
		{
			$this->validation_errors['name'] = "Last name is: " . $this->validation_errors['name_last'];
		}
		elseif( isset($this->validation_errors['name_first']) )
		{
			$this->validation_errors['name'] = "First name is: " . $this->validation_errors['name_first'];
		}
	}

	public function Validate_Personal($data)
	{
		$val_ruleset = (object) array();
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->ssn = TRUE;
		$val_ruleset->legal_id_number = TRUE;
		$val_ruleset->street = TRUE;
		$val_ruleset->unit = FALSE;
		$val_ruleset->city = TRUE;
		$val_ruleset->zip = TRUE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->state = TRUE;

		if( !checkdate($data->dob_month, $data->dob_day, $data->dob_year) )
		{
			$this->validation_errors['dob'] = "Invalid";
		}

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		//echo "Validation errors:<pre>" . print_r($this->validation_errors); echo "</pre>";

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
		$val_ruleset->income_monthly = TRUE;
		$val_ruleset->bank_aba = TRUE;
		$val_ruleset->bank_name = TRUE;
		$val_ruleset->bank_account = TRUE;

		if( !isset($data->bank_account_type) || !in_array($data->bank_account_type, array('checking','savings')) )
		$this->validation_errors['bank_account_type'] = "Invalid";

		$this->Validate_Data($val_ruleset, $data);

		$this->Consolidate_Name_Errors();

		return $this->validation_errors;
	}

	public function Validate_Application($data)
	{
		(int) $data->fund_amount;

		if( !isset($data->fund_amount) || !is_numeric($data->fund_amount) || $data->fund_amount > 500 || $data->fund_amount < 50 )
		$this->validation_errors['fund_amount'] = "Invalid";

		if( !isset($data->income_direct_deposit) || !in_array($data->income_direct_deposit, array('yes','no')) )
		$this->validation_errors['income_direct_deposit'] = "Invalid";

		if( !checkdate($data->date_fund_actual_month, $data->date_fund_actual_day, $data->date_fund_actual_year) )
		{
			$this->validation_errors['date_fund_actual'] = "Invalid";
		}

		if( !checkdate($data->date_first_payment_month, $data->date_first_payment_day, $data->date_first_payment_year) )
		{
			$this->validation_errors['date_first_payment'] = "Invalid";
		}
		elseif( $status = $this->dv_obj->Validate("{$data->date_first_payment_year}-{$data->date_first_payment_month}-{$data->date_first_payment_day}", array("type" => "weekend_holiday_check") ) )
		{
			if(!$status['status'])
			{
				$this->validation_errors['date_first_payment'] = "Date falls on a weekend or holiday";
			}
		}

		return $this->validation_errors;
	}

	public function Validate_Employment($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->employer_name = TRUE;
		$val_ruleset->phone_work = FALSE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->job_title = FALSE;
		$val_ruleset->income_monthly = TRUE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Campaign_Info($data)
	{
		$val_ruleset = (object) array();


		$val_ruleset->promo_sub_code = FALSE;
		$val_ruleset->url = FALSE;


		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Comment($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->comment = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Document($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->alt_xfer_date = FALSE;
		//$val_ruleset->verified_date = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Reference($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->full_name = FALSE;
		$val_ruleset->phone = FALSE;
		$val_ruleset->relationship = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}

	public function Validate_Cashline($data)
	{
		$val_ruleset = (object) array();

		$val_ruleset->employer_name = TRUE;
		$val_ruleset->job_title = FALSE;
		$val_ruleset->shift = FALSE;
		$val_ruleset->bank_name = TRUE;
		$val_ruleset->name_first = TRUE;
		$val_ruleset->name_middle = FALSE;
		$val_ruleset->name_last = TRUE;
		$val_ruleset->customer_email = TRUE;
		$val_ruleset->ssn = TRUE;
		$val_ruleset->legal_id_number = TRUE;
		$val_ruleset->street = TRUE;
		$val_ruleset->unit = FALSE;
		$val_ruleset->city = TRUE;

		$val_ruleset->phone_work = FALSE;
		$val_ruleset->phone_work_ext = FALSE;
		$val_ruleset->phone_home = TRUE;
		$val_ruleset->phone_cell = FALSE;
		$val_ruleset->phone_fax = FALSE;

		$val_ruleset->date_fund_estimated = FALSE;

		$this->Validate_Data($val_ruleset, $data);

		return $this->validation_errors;
	}


}

?>
