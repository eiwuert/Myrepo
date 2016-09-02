<?php

# ex: set ts=4:
# code to send personal data to bmg via the efastmedia website with an HTTP POST
# from OLP data

require_once("HTTP/Request.php"); # pear HTTP_Request, very handy

class Post_To_BMG
{

	function Post_To_BMG()
	{

	}

	function & Post($C, &$data, $live=false, $url='http://www.efastmedia.com/directpost.aspx')
	{
		
		# sorry, we can't have people posting incomplete data. the values can be empty, but the keys
		# must exist! assertions are a good thing, they catch bad code. don't turn them off!
		$assert = assert_options(ASSERT_BAIL);
		assert_options(ASSERT_BAIL, true); # none shall pass
			assert(is_numeric($C));
			assert(is_array($data));
				assert(isset($data['name_first']));
				assert(isset($data['name_last']));
				assert(isset($data['home_street']));
				assert(isset($data['home_unit']));
				assert(isset($data['home_city']));
				assert(isset($data['home_state']));
				assert(isset($data['home_zip']));
				assert(isset($data['email_primary']));
				assert(isset($data['offers']));
				assert(isset($data['phone_work']));
				#assert(isset($data['gender'])); # we don't collect gender
				assert(isset($data['date_of_birth']));
				assert(isset($data['employer_name']));
				assert(isset($data['income_direct_deposit']));
				assert(isset($data['income_monthly_net']));
				assert(isset($data['income_frequency']));
				assert(isset($data['pay_date_1']));
				assert(isset($data['pay_date_2']));
				assert(isset($data['bank_aba']));
				assert(isset($data['phone_home']));
				assert(isset($data['ip_address']));
				assert(isset($data['social_security_number']));
				assert(isset($data['bank_account']));
		assert_options(ASSERT_BAIL, $assert); # running away, eh? i'll bite your legs off!
	
		############### gratuitously ripped off from rodric's lead_batch.php script in /vh/cronjobs
	
		$epd_map = array (
			'WEEKLY' => 'Weekly',
			'MONTHLY' => 'Monthly',
			'BI_WEEKLY' => 'Bi-Weekly',
			'TWICE_MONTHLY' => 'Semi-Monthly',
		);
	
		$Employee_Days_Paid = $epd_map[$data['income_frequency']];
	
		$fields = array (
			'C' => $C,
			'First_Name' => $data['name_first'],
			'Last_Name' => $data['name_last'],
			'Address' => $data['home_street'] . (@$data['home_unit'] ? ' '.$data['home_unit'] : ''),
			'City'	=> $data['home_city'],
			'State' => $data['home_state'],
			'Zip' => $data['home_zip'],
			'Email' => $data['email_primary'],
			'Future_Offers' => $data['offers'] == "TRUE" ? 1 : 0,
			'Alt_Phone' => substr($data['phone_work'],0,3).'-'.substr($data['phone_work'],3,3).'-'.substr($data['phone_work'],6,4),
			'Gender' => isset($data['gender']) ? $data['gender'] : 'M',
			'DOB' =>  $data['date_of_birth'],
			'Employer' => $data['employer_name'],
			'Direct_Deposit' => $data['income_direct_deposit'] == 'TRUE' ? 'Y' : 'N',
			'Employee_Income' => $data['income_monthly_net'],
			'Employee_Days_Paid' => $Employee_Days_Paid,
			'Employee_Next_Pay_Day' => $data['pay_date_1'],
			'Employee_Next_Next_Pay_Day' => $data['pay_date_2'],
			'Bank_ABA' => $data['bank_aba'],
			'Phone' => substr($data['phone_home'],0,3).'-'.substr($data['phone_home'],3,3).'-'.substr($data['phone_home'],6,4),
			'IPAddress' => $data['ip_address'],
			'SSN' => substr($data['social_security_number'],0,3).'-'.substr($data['social_security_number'],3,2).'-'.substr($data['social_security_number'],5,4),
			'Account_Number' => $data['bank_account']
		);
	
		$net = new HTTP_Request($url);
		$net->setMethod(HTTP_REQUEST_METHOD_POST);
		reset($fields);
		while (list($k, $v) = each($fields))
			$net->addPostData($k, $v);

		# only actually send the request if this is "live", because we want coders to
		# be able to confirm that the right stuff is happening before we throw the
		# master switch
		if ($live)
		{
			$net->sendRequest();
		}
	
		return $net;
	
	}

}

?>
