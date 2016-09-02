<?php
/**
 * Sets up validators and runs validation for the Post action.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Actions_Validators_Post extends VendorAPI_Actions_Validators_Base implements VendorAPI_IValidator
{
	/**
	 * Initializes the Post action's required data validator.
	 *
	 * @return void
	 */
	public function init()
	{
		// Normalization information here
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_PhoneNumber('phone_home'));
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_PhoneNumber('phone_work'));
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_PhoneNumber('phone_cell'));
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_Ampersand('employer_name'));

		$this->addFilter(new VendorAPI_Actions_Validators_Filter_UpperCase('bank_account_type'));
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_BankAccount('bank_account'));

		// =======================================================================================================
		// Application info
		$this->addValidator('external_id', new Validation_Optional_1(new Validation_Number_1()));
		$this->addValidator('track_id', new Validation_String_1(24, 32));
		$this->addValidator('page_id', new Validation_Number_1());
		$this->addValidator('promo_id', new Validation_Number_1());
		$this->addValidator('promo_sub_code', new Validation_Optional_1(new Validation_String_1(0, 100)));

		// =======================================================================================================
		// Name
		$this->addValidators('name_first', array(new Validation_String_1(1, 50)));
		$this->addValidators('name_last', array(new Validation_String_1(1, 50)));
		$this->addValidators('name_middle', array(
			new Validation_Optional_1(new Validation_String_1(1, 50))
		));

		// =======================================================================================================
		// Address
		$this->addValidator('street', new Validation_NotEmpty_1());

		$this->addValidators('unit', array(
			new Validation_Optional_1(new Validation_String_1(1, 10))
		));
		$this->addValidators('state', array(
			new Validation_String_1(2, 2),
			new Validation_Alpha_1()
		));
		$this->addValidators('zip_code', array(
			new Validation_String_1(5, 9),
			new Validation_Number_1()
		));

		// =======================================================================================================
		// Phone
		$this->addValidators('phone_home', array(
			new Validation_Number_1(),
			new Validation_String_1(7, 10)
		));
		$this->addValidators('phone_work', array(
			new Validation_Number_1(),
			new Validation_String_1(7, 10)
		));
		$this->addValidators('phone_fax', array(
			new Validation_Optional_1(new Validation_Number_1()),
			new Validation_Optional_1(new Validation_String_1(7, 10))
		));

		// =======================================================================================================
		// Work

		$this->addValidator('employer_name', new Validation_NotEmpty_1());
		// =======================================================================================================
		// Personal info
		$this->addValidator('email', new Validation_EmailAddress_1());
		$this->addValidators('ssn', array(
			new Validation_String_1(9, 9),
			new Validation_Number_1()
		));
		$this->addValidator('dob', new Validation_Date_1());

		$this->addValidator('legal_id_type', new Validation_Alpha_1());


		$this->addValidators('legal_id_state', array(
			new Validation_String_1(2, 2),
			new Validation_Alpha_1()
		));

		// =======================================================================================================
		// Personal references

		$pr_validator = new Validation_SubArray_1('personal_reference');

		// First things first, add the normalization filters
		$pr_validator->addFilter(new VendorAPI_Actions_Validators_Filter_PhoneNumber('phone_home'));
		$pr_validator->addFilter(new VendorAPI_Actions_Validators_Filter_NoFilter('name_full'));
		$pr_validator->addFilter(new VendorAPI_Actions_Validators_Filter_NoFilter('relationship'));

		/*
		 *  get rid of the validator
		//Now validate the newly normalized values
		$pr_validator->addValidator('name_full', new Validation_NotEmpty_1());
		$pr_validator->addValidators('phone_home', array(
			new Validation_Optional_1(new Validation_Number_1()),
		));
		$pr_validator->addValidator('relationship', new Validation_NotEmpty_1());
		*/
		$this->addFilter(new VendorAPI_Actions_Validators_Filter_Optional('personal_reference', $pr_validator));


		// =======================================================================================================
		// Campaign Info
		$ci_validator = new Validation_SubArray_1('campaign_info');
		$ci_validator->addValidator('promo_id', new Validation_Number_1());
		$ci_validator->addValidator('promo_sub_code', new Validation_Optional_1(new Validation_String_1(0, 100)));
		// @todo Find out what the actual length of the license key is
		$ci_validator->addValidator('license_key', new Validation_String_1(32, 64));
		$ci_validator->addValidator('name', new Validation_NotEmpty_1());
		$this->addFilter($ci_validator);

		$this->addValidators('bank_name', array(
			new Validation_String_1(1, 255),
			new Validation_NotEmpty_1()
		));


		$this->addValidators('bank_aba', array(
			new Validation_Number_1(),
			new Validation_String_1(9, 9)
		));
		$this->addValidators('bank_account', array(
			new Validation_Number_1(),
			new Validation_String_1(2, 17)
		));
		$this->addValidator('bank_account_type', new Validation_Set_1(array('CHECKING', 'SAVINGS')));

		// =======================================================================================================
		// Income
		$this->addValidators('income_monthly', array(
			new Validation_String_1(1, 5),
			new Validation_Number_1()
		));
	}
}
