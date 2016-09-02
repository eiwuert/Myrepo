<?php

/** 
 * Static class to populate common OLP data values.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate
{
	/** 
	 * Populate common OLP data values.
	 *
	 * @return array
	 */
	public static function getRandomOLPData()
	{
		$number = new OLP_Populate_Number();
		$name = new OLP_Populate_Name(dirname(__FILE__) . '/Populate/names.txt');
		$bank = new OLP_Populate_Bank(dirname(__FILE__) . '/Populate/bank.txt');
		$employer = new OLP_Populate_Employer(dirname(__FILE__) . '/Populate/employer.txt');
		$address = new OLP_Populate_Address();
		$phone = new OLP_Populate_PhoneNumber();
		$extension = new OLP_Populate_Sometimes(new OLP_Populate_Number(4), 0.333, '');
		$email = new OLP_Populate_Email();
		$relationship = new OLP_Populate_Array(array('parent', 'sibling', 'friend', 'Co-Worker', 'extended_family')); // Source: tss.2.shared.code/smt/master.build.tokens.php
		$aba = new OLP_Populate_ABA();
		$call_time = new OLP_Populate_Array(array('MORNING', 'AFTERNOON', 'EVENING'));
		$date = new OLP_Populate_Date();
		$ssn = new OLP_Populate_SSN();
		$boolean = new OLP_Populate_Array(array('TRUE', 'FALSE'));
		$incometype = new OLP_Populate_Array(array('BENEFITS', 'EMPLOYMENT'));
		$payfrequency = new OLP_Populate_Array(array('WEEKLY', 'BI_WEEKLY', 'TWICE_MONTHLY', 'MONTHLY'));
		$employer_length = new OLP_Populate_Array(array('1', '2', '3', '4', '5', '6', '7', '8', 'FALSE'));
		
		// Randomize the values that get split
		$date->getRandomItem('-20 year', '-60 year');
		$ssn->getRandomItem();
		$address->getRandomItem(array(
			// No checks doesn't disable these states
			'WV', 'VA',
			'GA', 'CT',
			
			// Impact doesn't sell to Utah, so might as well exclude them as well
			'UT',
		));
		
		// Fill in our array
		$data = array(
			'name_first' => $name->getRandomItem() . '_TSSTEST',
			'name_last' => $name->getRandomItem() . '_TSSTEST',
			'email_primary' => $email->getRandomItem(),
			'phone_home' => $phone->getRandomItem(),
			'phone_work' => $phone->getRandomItem(),
			'phone_cell' => $phone->getRandomItem(),
			'ext_work' => $extension->getRandomItem(0, 9999),
			'best_call_time' => $call_time->getRandomItem(),
			'date_dob_y' => $date->year,
			'date_dob_m' => $date->month,
			'date_dob_d' => $date->day,
			'ssn_part_1' => $ssn->ssn_1,
			'ssn_part_2' => $ssn->ssn_2,
			'ssn_part_3' => $ssn->ssn_3,
			'home_street' => $address->street,
			'home_city' => $address->city,
			'home_state' => $address->state_abbr,
			'home_zip' => $address->zip_code,
			'employer_name' => $employer->getRandomItem(3,50),				
			'state_id_number' => $number->getRandomItem(10000000, 99999999),
			'state_issued_id' => $address->state_abbr,
			'income_direct_deposit' => $boolean->getRandomItem(),
			'income_type' => $incometype->getRandomItem(),
			'income_frequency' => $payfrequency->getRandomItem(),
			'income_monthly_net' => $number->getRandomItem(4000, 4999),
			'bank_name' => $bank->getRandomItem(5,40),
			'bank_aba' => $aba->getRandomItem(TRUE),
			'bank_account' => $number->getRandomItem(1000, 99999999),
			'ref_01_name_full' => $name->getRandomItem() . '_TSSTEST ' . $name->getRandomItem() . '_TSSTEST',
			'ref_01_phone_home' => $phone->getRandomItem(),
			'ref_01_relationship' => $relationship->getRandomItem(),
			'ref_02_name_full' => $name->getRandomItem() . '_TSSTEST ' . $name->getRandomItem() . '_TSSTEST',
			'ref_02_phone_home' => $phone->getRandomItem(),
			'ref_02_relationship' => $relationship->getRandomItem(),
			'legal_notice_1' => 'TRUE',
			'offers' => 'FALSE',
			'mh_offer' => 'FALSE',
			'paydate' => array(
				'frequency' => 'WEEKLY',
				'weekly_day' => 'MON',
			),
			'bank_account_type' => 'CHECKING',
			'legal_approve_docs_1' => 'checked',
			'legal_approve_docs_2' => 'checked',
			'legal_approve_docs_3' => 'checked',
			'legal_approve_docs_4' => 'checked',
			'military' => 'FALSE',
			'cali_agree' => $address->state_abbr == 'CA' ? 'agree' : '',
			'employer_length_select' => $employer_length->getRandomItem(),
		);
		
		return $data;
	}
}


?>
