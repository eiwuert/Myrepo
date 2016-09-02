<?php
/**
 * OLPBlackbox_Data class which is an extension of Blackbox_Data used for holding state.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * State object for Blackbox applications.
 *
 * ArrayAccess was added to this Config object mainly to easily interface
 * with the {@link OLP_Fraud} class. When we no longer need that class for
 * Blackbox, the ArrayAccess interface won't be needed. (Though, personally,
 * I see no harm in keeping it. Whatevs) [DO]
 *
 * @todo remove ArrayAccess from this when legacy code no longer requires it.
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Data extends Blackbox_Data implements ArrayAccess
{
	/**
	 * Required for ArrayAccess.
	 *
	 * See class comment.
	 *
	 * @param string $offset Check whether a property exists.
	 *
	 * @return bool Property exists, TRUE. Otherwise FALSE.
	 */
	public function offsetExists($offset)
	{
		$this->__isset($offset);
	}

	/**
	 * Required for ArrayAccess.
	 *
	 * See class comment.
	 *
	 * @param string $offset The property to retrive.
	 *
	 * @return mixed Value of the property asked for.
	 */
	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	/**
	 * Set a property value.
	 *
	 * See class comment.
	 *
	 * @param string $offset Name of the property to set.
	 * @param mixed $value Value to set for the property.
	 *
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	/**
	 * Unset a class property.
	 *
	 * See class comment.
	 *
	 * @param string $offset Name of the property to unset.
	 *
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		$this->__unset($offset);
	}

	/**
	 * OLPBlackbox_Data constructor.
	 */
	public function __construct()
	{
		$this->data['application_id'] = NULL;
		$this->data['session_id'] = NULL;

		$this->data['name_first'] = NULL;
		$this->data['name_middle'] = NULL;
		$this->data['name_last'] = NULL;

		$this->data['home_street'] = NULL;
		$this->data['home_unit'] = NULL;
		$this->data['home_city'] = NULL;
		$this->data['home_state'] = NULL;
		$this->data['home_zip'] = NULL;

		$this->data['phone_home'] =NULL;
		$this->data['phone_cell'] = NULL;
		$this->data['phone_work'] = NULL;
		$this->data['ext_work'] = NULL;

		$this->data['email_primary'] = NULL;

		$this->data['state_issued_id'] = NULL;
		$this->data['state_id_number'] = NULL;
		$this->data['state_id_number_encrypted'] = NULL;

		$this->data['date_dob_y'] = NULL;
		$this->data['date_dob_m'] = NULL;
		$this->data['date_dob_d'] = NULL;
		$this->data['dob'] = NULL;
		$this->data['dob_encrypted'] = NULL;

		$this->data['ssn_part_1'] = NULL;
		$this->data['ssn_part_2'] = NULL;
		$this->data['ssn_part_3'] = NULL;

		$this->data['bank_name'] = NULL;
		$this->data['bank_aba'] = NULL;
		$this->data['bank_aba_encrypted'] = NULL;
		$this->data['bank_account'] = NULL;
		$this->data['bank_account_encrypted'] = NULL;
		$this->data['permutated_bank_account'] = array();
		$this->data['permutated_bank_account_encrypted'] = array();
		$this->data['bank_account_type'] = NULL;

		$this->data['employer_name'] = NULL;
		$this->data['income_monthly_net'] = NULL;
		$this->data['income_direct_deposit'] = NULL;
		$this->data['social_security_number'] = NULL;
		$this->data['social_security_number_encrypted'] = NULL;

		$this->data['date_of_hire'] = NULL;
		$this->data['loan_amount_desired'] = NULL;
		$this->data['residence_start_date'] = NULL;
		$this->data['residence_type'] = NULL;

		$this->data['client_ip_address'] = NULL;

		$this->data['datax_event_type'] = NULL;
		$this->data['account'] = NULL;
		$this->data['source'] = NULL;

		$this->data['allow_datax_rework'] = NULL;
		$this->data['do_datax_rework'] = NULL;

		$this->data['paydates'] = NULL;
		$this->data['income_type'] = NULL;

		// Paydate model flattened out.
		$this->data['income_frequency'] = NULL;
		$this->data['model_name'] = NULL;
		$this->data['next_pay_date'] = NULL;
		$this->data['last_pay_date'] = NULL;
		$this->data['day_int_one'] = NULL;
		$this->data['day_int_two'] = NULL;
		$this->data['week_one'] = NULL;
		$this->data['week_two'] = NULL;
		$this->data['day_string_one'] = NULL;
		$this->data['day_of_week'] = NULL;

		$this->data['military'] = NULL;

		// used for Agean title loans, esp in OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount
		$this->data['vehicle_vin'] = NULL;
		$this->data['vehicle_make'] = NULL;
		$this->data['vehicle_year'] = NULL;
		$this->data['vehicle_type'] = NULL;
		$this->data['vehicle_model'] = NULL;
		$this->data['vehicle_style'] = NULL;
		$this->data['vehicle_series'] = NULL;
		$this->data['vehicle_mileage'] = NULL;
		$this->data['vehicle_license_plate'] = NULL;
		$this->data['vehicle_color'] = NULL;
		$this->data['vehicle_value'] = NULL;
		$this->data['vehicle_title_state'] = NULL;
		

		/*
		 * @todo personal reference data should not be stored as flat if it 
		 * can be prevented...
		 */
		// This is used for references :)
		$this->data['ref_01_name_full'] = NULL;
		$this->data['ref_01_phone_home'] = NULL;
		$this->data['ref_01_relationship'] = NULL;
		
		$this->data['ref_02_name_full'] = NULL;
		$this->data['ref_02_phone_home'] = NULL;
		$this->data['ref_02_relationship'] = NULL;

		$this->data['ref_03_name_full'] = NULL;
		$this->data['ref_03_phone_home'] = NULL;
		$this->data['ref_03_relationship'] = NULL;

		$this->data['ref_04_name_full'] = NULL;
		$this->data['ref_04_phone_home'] = NULL;
		$this->data['ref_04_relationship'] = NULL;

		$this->data['react_app_id'] = NULL;

		// UK fields
		$this->data['nin'] = NULL;
		$this->data['supervisor_phone'] = NULL;
		$this->data['best_call_time'] = NULL;

		//*********************************************
		// Added for suppression list checks
		//*********************************************
		$this->data['promo_id'] = NULL;
		$this->data['site_name'] = NULL;
		$this->data['promo_sub_code'] = NULL;
		$this->data['promo_and_sub_code'] = NULL;

		// Added for non-CFE loan type determination
		$this->data['card_loan'] = NULL;

		$this->data['track_key'] = NULL;
		
		/*
		 * Added so the data could be sent to vendor api
		 */
		$this->data['work_title'] = NULL;
		$this->data['phone_fax'] = NULL;
		$this->data['pwadvid'] = NULL;
		$this->data['banking_start_date'] = NULL;

		// Added for deny_soap rule GForge [#27004] 
		$this->data['is_soap'] = NULL;
		$this->data['customer_id'] = NULL;
		
		$this->data['test_app'] = NULL;

		parent::__construct();
	}

	/**
	 * Returns the an array of the data in the object that can be used for the eCash Vendor API.
	 *
	 * @return array
	 */
	public function toECashArray()
	{
		return OLPECash_VendorAPI::toECashArray($this->data);
	}
}
?>
