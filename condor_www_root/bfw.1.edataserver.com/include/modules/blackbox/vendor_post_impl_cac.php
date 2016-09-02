<?php

/**
 * @desc A concrete implementation class for posting to cac
 */
class Vendor_Post_Impl_CAC extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				//'post_url' => 'dump.ds80.tss/index.xml',
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/CAC',
			//	'post_url' => 'https://leads.cashadvance.com/soap_cashadvance.php',
				'username' => 'SellingSource',
				'password' => 'password',
				'SRC' => 'Test',
				'headers' => array(
						'Content-Type: text/xml; charset=utf-8',
						'SOAPAction: leads.cashadvance.com/soap_cashadvance.php#create_lead'
				),
			),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(

				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'cac'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://leads.cashadvance.com/soap_cashadvance.php',
					'SRC' => 'SellingSource'
					)
				),
			'cac2'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://leads.cashadvance.com/soap_cashadvance.php',
					'SRC' => 'SellingSource20'
					)
				),
			'cac3'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://leads.cashadvance.com/soap_cashadvance.php',
					'SRC' => 'SellingSource12'
					)
				),	
			'cac4'    => Array(
				'ALL'      => Array(
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://leads.cashadvance.com/soap_cashadvance.php',
					'SRC' => 'SellingSource50'
					)
				),	
			);
	
	protected $static_thankyou = FALSE;
	
	/**
	 * GForgeg 6672a
	 * Date Diff
	 * http://www.ilovejackdaniels.com/php/php-datediff-function/
	 * TODO:Cleaning up the code
	 *
	 */
	private function datediff($interval, $datefrom, $dateto, $using_timestamps = false) 
	{
		/*
		$interval can be:
		yyyy - Number of full years
		q - Number of full quarters
		m - Number of full months
		y - Difference between day numbers
		(eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
		d - Number of full days
		w - Number of full weekdays
		ww - Number of full weeks
		h - Number of full hours
		n - Number of full minutes
		s - Number of full seconds (default)
		*/

		if (!$using_timestamps)
		{
			$datefrom = strtotime($datefrom, 0);
			$dateto = strtotime($dateto, 0);
		}
		$difference = $dateto - $datefrom; // Difference in seconds

		switch($interval) 
		{

		case 'yyyy': // Number of full years

			$years_difference = floor($difference / 31536000);
			if (mktime(
				date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom), 
				date("j", $datefrom), 
				date("Y", $datefrom)+$years_difference) > $dateto) 
			{
				$years_difference--;
			}
			if (mktime(
				date("H", $dateto), 
				date("i", $dateto), 
				date("s", $dateto), 
				date("n", $dateto), 
				date("j", $dateto), 
				date("Y", $dateto)-($years_difference+1)) > $datefrom) 
			{
				$years_difference++;
			}
			$datediff = $years_difference;
		break;

		case "q": // Number of full quarters

			$quarters_difference = floor($difference / 8035200);
			while (mktime(date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom)+($quarters_difference*3), 
				date("j", $dateto), date("Y", $datefrom)) < $dateto) {
			$months_difference++;
			}
			$quarters_difference--;
			$datediff = $quarters_difference;
		break;

		case "m": // Number of full months

			$months_difference = floor($difference / 2678400);
			while (mktime(
				date("H", $datefrom), 
				date("i", $datefrom), 
				date("s", $datefrom), 
				date("n", $datefrom)+($months_difference), 
				date("j", $dateto), date("Y", $datefrom)) < $dateto) 
			{
			$months_difference++;
			}
			$months_difference--;
			$datediff = $months_difference;
		break;

		case 'y': // Difference between day numbers

			$datediff = date("z", $dateto) - date("z", $datefrom);
		break;

		case "d": // Number of full days

			$datediff = floor($difference / 86400);
		break;

		case "w": // Number of full weekdays

			$days_difference = floor($difference / 86400);
			$weeks_difference = floor($days_difference / 7); // Complete weeks
			$first_day = date("w", $datefrom);
			$days_remainder = floor($days_difference % 7);
			$odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
			if ($odd_days > 7) 
			{ // Sunday
				$days_remainder--;
			}
			if ($odd_days > 6) 
			{ // Saturday
				$days_remainder--;
			}
			$datediff = ($weeks_difference * 5) + $days_remainder;
		break;

		case "ww": // Number of full weeks

			$datediff = floor($difference / 604800);
		break;

		case "h": // Number of full hours

			$datediff = floor($difference / 3600);
		break;

		case "n": // Number of full minutes

			$datediff = floor($difference / 60);
		break;

		default: // Number of full seconds (default)

			$datediff = $difference;
		break;
		}

		return $datediff;

	}	

	/**
	 * GForge 6672a
	 * This function will compute the date for the
	 * field and convert it into # of months and years
	 * 
	 * @param date1 (ex: residence_start_date, date_of_hire
	 * @param date2 (default to now)
	 * 
	 * @return array (month, years)
	 */
	private function calculateDate($date1)
	{
		$ret_val = array();
		if(trim($date1) == '')
		{
			return $ret_val;
		}

		//********************************************* 
		// Implementation notes:
		// We're going to call the above datediff function
		// for months and then we'll use that number and
		// do a "/" to find out how many years are in that
		// months and a "%" to find out many months
		// are left over
		//********************************************* 
		$months = $this->datediff("m", $date1,  date('m/d/y')  ) + 1;
		//********************************************* 
		// for some reason the above is off by 1 month
		//********************************************* 
		
		$ret_val['months'] = $months % 12;
		$ret_val['years'] = floor($months / 12);

		return $ret_val;
	}

	public function Generate_Fields(&$lead_data, &$params)
	{
	// changed this if statement to test for 'TRUE' the string instead of boolean [AuMa]
		if($lead_data['data']['income_direct_deposit']=='TRUE') {
			$payment_type = 'directdeposit';
		}else {
			$payment_type = 'papercheck';
		}
        //Paydate Freq
        if(isset($lead_data['data']['paydate_model']) && 
           isset($lead_data['data']['paydate_model']['income_frequency']) &&
           $lead_data['data']['paydate_model']['income_frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate_model']['income_frequency'];
        }
        elseif(isset($lead_data['data']['income_frequency']) && 
           $lead_data['data']['income_frequency'] != "")
        {
            $freq = $lead_data['data']['income_frequency'];
        }
        elseif(isset($lead_data['data']['paydate']) && 
               isset($lead_data['data']['paydate']['frequency']) &&
               $lead_data['data']['paydate']['frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate']['frequency'];
        }
        
        if(isset($freq))
        {
            //convert income frequency to requested format
            switch($freq)
            {
                case 'WEEKLY':
                    $income_frequency = 'weekly';
                    break;
                case 'BIWEEKLY':
                case 'BI_WEEKLY':
                    $income_frequency = 'biweekly';
                    break;
                case 'TWICE_MONTHLY':
                    $income_frequency = 'semimonthly';
                    break;  
                case 'MONTHLY':
                    $income_frequency = 'monthly';
                    break;      
            }
			$pay_freq = $income_frequency;
        }
        
		//********************************************* 
		// GForge 6672a [AuMa]
		// We have to calculate the values for employer
		// and residence times
		//********************************************* 
		$res_time =  $this->calculateDate($lead_data['data']['residence_start_date']); 
		
		$emp_time =  $this->calculateDate($lead_data['data']['date_of_hire']); 
		//********************************************* 
		// End GForge 6672a
		//********************************************* 
		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];
		$military = ($lead_data['data']['military']=="TRUE"?"yes":"no");
		$homeunit = $lead_data['data']['home_unit'] ? $lead_data['data']['home_unit'] : '';
		//echo('<pre>'); print_r($lead_data); die();
		
		$dom=new DOMDocument('1.0','utf-8');
		$soap_envelope_element = $dom->createElement('soap:Envelope');
		$soap_envelope_element->setAttribute('SOAP-ENV:encodingStyle',"http://schemas.xmlsoap.org/soap/encoding/");
		$soap_envelope_element->setAttribute('xmlns:xsd','"http://www.w3.org/2001/XMLSchema"');
		$soap_envelope_element->setAttribute('xmlns:xsd',"http://www.w3.org/2001/XMLSchema");
		$soap_envelope_element->setAttribute('xmlns:soap',"http://schemas.xmlsoap.org/soap/envelope/");
		
		
		$dom->appendChild($soap_envelope_element);
		$soap_body_element = $dom->createElement('soap:Body');
		$soap_envelope_element->appendChild($soap_body_element);
		
		$create_lead_element = $dom->createElement('create_lead');
		$create_lead_element->setAttribute('xmlns','leads.cashadvance.com/soap_cashadvance.php');
		$soap_body_element->appendChild($create_lead_element);
		$create_lead_element->appendChild($dom->createElement('user_name', $params['username']));
		$create_lead_element->appendChild($dom->createElement('password', md5($params['password'])));
		
		
		$linfo = array (
				'Email' => $lead_data['data']['email_primary'],
				'First_Name' => $lead_data['data']['name_first'],
				'Last_Name' => $lead_data['data']['name_last'],				
				'Address_1' => $lead_data['data']['home_street'],
				'Address_2' => '',
				'Apartment_Number' => $homeunit,
				'City'  => $lead_data['data']['home_city'],
				'State' => $lead_data['data']['home_state'],
				'ZIP_Code' => $lead_data['data']['home_zip'],
				'Home_Phone' => $lead_data['data']['phone_home'],
				'Work_Phone'     => $lead_data['data']['phone_work'],
				'Cell_Phone' => $lead_data['data']['phone_cell'],
				'Military' => $military,
				'Occupation' => 'nananana',
				'Employer' => $lead_data['data']['employer_name'],
				'Monthly_Income' => $lead_data['data']['income_monthly_net'],
				'Income_Source' => ucwords (strtolower ($lead_data['data']['income_type'])),
				'Gender' => 'na',
				'SSN' => $lead_data['data']['social_security_number'],
				'DOB' => $lead_data['data']['date_dob_m'].'/'.$lead_data['data']['date_dob_d'].'/'.$lead_data['data']['date_dob_y'],
				'Payment_Method' => $payment_type,
				'Payment_Frequency' => $pay_freq,
				'Account_Number' => $lead_data['data']['bank_account'],
				'Routing_Number' => $lead_data['data']['bank_aba'],
				'Account_Type'=> $lead_data['data']['bank_account_type'],
				'Drivers_License_Number' => $lead_data['data']['state_id_number'],
				'Drivers_License_State' => $issued_state,
				'Bank_Name' => $lead_data['data']['bank_name'],
				'Bank_Phone' => '8886667777',
				'Outstanding_Loans' => 0,
				'Pay_Date_1' => date("m/d/Y", strtotime(reset($lead_data['data']['paydates']))),
				'Pay_Date_2' => date("m/d/Y", strtotime(next($lead_data['data']['paydates']))),
				'Reference_1_Name' => $lead_data['data']['ref_01_name_full'],
				'Reference_1_Phone' => $lead_data['data']['ref_01_phone_home'],
				'Reference_1_Relationship' => $lead_data['data']['ref_01_relationship'],
				'Reference_2_Name' => $lead_data['data']['ref_02_name_full'],
				'Reference_2_Phone' => $lead_data['data']['ref_02_phone_home'],
				'Reference_2_Relationship' => $lead_data['data']['ref_02_relationship'],
				'IP_Address' => $lead_data['data']['client_ip_address'],
				//********************************************* 
				// GForge 6672a [AuMa]
				// Adding form field information
				//********************************************* 
				'Best_Time_To_Call' => strtolower($lead_data['data']['best_call_time']),
				'Own_Rent' => strtolower($lead_data['data']['residence_type']),
				'US_Citizen' => $lead_data['data']['citizen'],
				'Years_At_Residence' => $res_time['years'],
				'Months_At_Residence' => $res_time['months'],
				'Years_Employed' => $emp_time['years'],
				'Months_Employed' => $emp_time['months'],
				'Supervisor_Name' => '',
				'Supervisor_Phone' => '8886667777',
				'Work_City' => '',
				// reusing home information
				'Work_State' => $lead_data['data']['home_state'], 
				'Work_Zip' => $lead_data['data']['home_zip'],
				'Years_Bank_Account' => '0',
				//********************************************* 
				// End GForge 6672a
				//********************************************* 
				'SRC' => $params['SRC']
				);
		$lead_info = $dom->createElement('lead_info');
		$create_lead_element->appendChild($lead_info);
		foreach($linfo as $element => $data) {
			$lead_info->appendChild($dom->createElement($element,$data));
		}			
		$fields = $dom->saveXML();
		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		} else {
			preg_match('/<status_id[^>]*>(.*)<\/status_id>/',$data_received,$status);
			if( $status[1] == 1) {
				$result->Set_Message("Accepted");
				$result->Set_Success(TRUE);
				$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
				$result->Set_Vendor_Decision('ACCEPTED');	
			} else {
				$result->Set_Message("Rejected");
				$result->Set_Success(FALSE);
				$result->Set_Vendor_Decision('REJECTED');
			}
		}
		
		return $result;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [CAC]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		$content = NULL;
		
		if(preg_match('/<delivery_url[^>]*>(.+)<\/delivery_url>/is', $data_received, $m))
		{
			$url = trim($m[1]);
			$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		}
		elseif(preg_match('/<delivery_message[^>]*>(.+)<\/delivery_message>/is', $data_received, $m))
		{
			$content = trim($m[1]);
		}
		
		return($content);
		
	}
	
}
