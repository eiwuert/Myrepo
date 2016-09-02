<?php

class Populate
{
	private static $first_names = array(
		'Brian',
		'Jason',
		'Chris',
		'Christopher',
		'Stephan',
		'Joe',
		'Josef',
		'Raymond',
		'Merlin',
		'Gloria',
		'Jennifer',
		'Jessica',
		'Lynn',
		'Betty',
		'Laura',
		'Jack',
		'John',
		'Jon',
		'William'
	);

	private static $last_names = array(
		'Smith',
		'Lopez',
		'Gonzales',
		'Underhill',
		'Simpson',
		'Lee',
		'Baggins',
		'Jackson',
		'Johnson',
		'Bradford',
		'Wallace',
		'Williams'
	);

	private static $addresses = array(
		'123 Test Way',
		'456 Testing Ave',
		'987 Not Real Road',
		'12335 Something Fake Ct',
		'8741 San Juan Fako'
	);
	
	/**
	  * Where did I get these values? Check file /virtualhosts/tss.2.shared.code/smt/master.build.tokens.php [DY]
	  */
	private static $relationships = array(
		'parent',
		'sibling',
		'friend',
		'Co-Worker',
		'extended_family',
	);
	

	public static function Get_Random_Record( $sql, $applog, $mode )
	{
		// Needed to decrypt data since we pull from slave.
		$olp_enc_api = "prpc://callcenter:4w#8_G@bfw.1.edataserver.com/olp_encryption_prpc.php";
		$crypt = new Prpc_Client($olp_enc_api, FALSE, 32);
		
		// don't work on live
		if( $mode == 'LIVE' )
			return array();

		try
		{

			//if( $mode == "LOCAL" )
			//{

				$db = Server::Get_Server('SLAVE', 'blackbox' );
				if(isset($db['port']) && strpos($db['host'],':') === false)
				{
					$db['host'] = $db['host'].':'.$db['port'];
				}
				// Build the sql object
				$mysql = new MySQL_4 ($db['host'], $db['user'], $db['password']);
				$result = $mysql->Connect();

			//}
			//else
			//{
			//	$mysql = $sql;
			//}

			// pick a random offset for the query
			$random = rand(1, 100);

			$query = "
					SELECT personal_encrypted.*, bank_info_encrypted.*, employment.*, income.*, residence.*, personal_contact.*
					FROM application join personal_encrypted on (application.application_id = personal_encrypted.application_id)
					            join bank_info_encrypted on (personal_encrypted.application_id = bank_info_encrypted.application_id)
					            join employment on (personal_encrypted.application_id = employment.application_id)
					            join income on (personal_encrypted.application_id = income.application_id)
					            join residence on (personal_encrypted.application_id = residence.application_id)
					            join personal_contact on (personal_encrypted.application_id = personal_contact.application_id)
					WHERE application.created_date >= DATE_SUB(NOW(), INTERVAL 5 HOUR)
					LIMIT {$random}, 1";
			
			$result = $mysql->Query('olp', $query);
			$rec = $mysql->Fetch_Array_Row($result);

		}

		catch( MySQL_Exception $e )
		{
			// don't do anything sorry every one :(
			DB_Exception_Handler::Def($applog, $e, 'Could not run populate function for session_id ' . session_id());
			return array();
		}


		if( $rec )
		{
			
			if($rec['phone'] == '0000000000') $rec['phone'] = '7028519587';

			$data['name_first'] = Populate::$first_names[array_rand(Populate::$first_names)] . '_TSSTEST';
			$data['name_last'] = Populate::$last_names[array_rand(Populate::$last_names)] . '_TSSTEST';
			$data['email_primary'] = mt_rand(1000,1000000000).'@tssmasterd.com';
			$data['phone_home'] = mt_rand(200, 999) . '555' . mt_rand(1000, 9999);
			$data['phone_work'] = mt_rand(200, 999) . '555' . mt_rand(1000, 9999);
			$data['phone_cell'] = mt_rand(200, 999) . '555' . mt_rand(1000, 9999);
			$data['ext_work'] = mt_rand(0, 1) == 1 ? '' : mt_rand(1000, 9999);
			$data['best_call_time'] = $rec['best_call_time'];
			// Random number between 80 years ago and 20 years ago
			$data['date_dob_y'] = mt_rand(date('Y') - 80, date('Y') - 20);
			$data['date_dob_m'] = mt_rand(1, 12);
			$data['date_dob_d'] = mt_rand(1, 28);
			$data['ssn_part_1'] = '8' . mt_rand(10, 99);
			$data['ssn_part_2'] = mt_rand(10, 99);
			$data['ssn_part_3'] = mt_rand(1000, 9999);
			$data['home_street'] = Populate::$addresses[array_rand(Populate::$addresses)];
			$data['home_city'] = $rec['city'];
			$data['home_state'] = $rec['state'];
			$data['home_zip'] = $rec['zip'];
			$data['employer_name'] = $rec['employer'];
			$data['state_id_number'] = mt_rand(10000000, 99999999);
			$data['state_issued_id'] = $rec['state'];
			$data['income_direct_deposit'] = $rec['direct_deposit'];
			$data['income_type'] = $rec['income_type'];
			$data['income_frequency'] = $rec['pay_frequency'];
			$data['bank_name'] = $rec['bank_name'];
			


			$rec['routing_number'] = $crypt->decrypt($rec['routing_number']);

			
			$data['bank_aba'] = $rec['routing_number'];
			$data['bank_account'] = mt_rand();
			$data['ref_01_name_full'] = $rec['full_name'];
			$data['ref_01_phone_home'] = $rec['phone'];
			$data['ref_01_relationship'] = $rec['relationship'];
			$data['ref_02_name_full'] = $rec['full_name'].' 2';
			$data['ref_02_phone_home'] = $rec['phone'];
			$data['ref_02_relationship'] = $rec['relationship'].' 2';
			$data['legal_notice_1'] = 'TRUE';
			$data['offers'] = 'FALSE';
			$data['mh_offer'] = 'FALSE';
			$data['paydate']['frequency'] = 'WEEKLY';
			$data['paydate']['weekly_day'] = 'MON';
			$data['bank_account_type'] = 'CHECKING';
			$data['legal_approve_docs_1'] = 'checked';
			$data['legal_approve_docs_2'] = 'checked';
			$data['legal_approve_docs_3'] = 'checked';
			$data['legal_approve_docs_4'] = 'checked';
			$data['military'] = 'FALSE';

			$rand_keys = array_rand(self::$relationships, 2); // GForge #3755 [DY]
			$data['ref_01_relationship'] = self::$relationships[$rand_keys[0]];
			$data['ref_02_relationship'] = self::$relationships[$rand_keys[1]];
			
			if( $rec['state'] == 'CA')
			{
				$data['cali_agree'] = 'agree';
			}

			$data['income_monthly_net'] = 6999;
		}

		// return the data
		return $data;
	}
}
?>
