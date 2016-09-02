<?php
/**
 * @desc A concrete implementation class for posting to Imagine Card
 */
class Vendor_Post_Impl_IMC extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			'ALL'     => Array(),
			'LOCAL'   => Array(),
			'RC'      => Array(),
			'LIVE'    => Array()
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		return array($lead_data['data']);
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();

		if($data_received)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}
	
	/**
	 * HTTP Post Process
	 * 
	 * This is an override for the post process
	 * @param array Field List
	 * @param boolean Qualify (not used)
	 * @return object vendor post obj
	 */
	public function HTTP_Post_Process($fields, $qualify = FALSE) 
	{
		$t = array();
		$r = TRUE;
		$result = $this->Generate_Result($r, $t);
		$result->Set_Data_Sent(serialize($fields));
		
		$fields = $fields[0];
		//Build URL
		$annual_income = $fields['income_monthly_net'] * 12;
		$pay_freq_map = Array(
			'WEEKLY' => 4,
			'BI_WEEKLY' => 2,
			'TWICE_MONTHLY' => 3,
			'MONTHLY' => 1
		);
		
		$map = Array(
			'fn' => 'name_first',        'ln' => 'name_last',
			'a1' => 'home_street',       'ct' => 'home_city',
			'st' => 'home_state',	     'zp' => 'home_zip',
			'em' => 'email_primary',     'ssn1' => 'ssn_part_1',
			'ssn2' => 'ssn_part_2', 	 'ssn3' => 'ssn_part_3',
			'dobm' => 'date_dob_m',   	 'doby' => 'date_dob_y',
			'dobd' => 'date_dob_d',      'rsal' => 'income_direct_deposit',
			'rtn'  => 'bank_aba', 		 'can'  => 'bank_account'
		);
		
		$pmap = Array('ph' => 'phone_home', 'wph' => 'phone_work');

		foreach($map as $key => $val)
		{
			$url[] ="{$key}=". urlencode($fields[$val]);
		}
		
	  	foreach($pmap as $key => $val)
		{
			$phone = sscanf($fields[$val],'%3d%3d%4d');
			
			if(is_array($phone))
			{
				$url[] = $key . '1=' . urlencode($phone[0]);
				$url[] = $key . '2=' . urlencode($phone[1]);
				$url[] = $key . '3=' . urlencode($phone[2]);
			}
			else
			{
				$url[] = $key . '1=';
				$url[] = $key . '2=';
				$url[] = $key . '3=';
			}
		}
		for($i = 0;$i < 2;$i ++)
		{
			$date = split('-', $fields['paydates'][(3 - $i)]);
			$url[] = 'pd' . ($i+1) . 'd=' . urlencode($date[2]);
			$url[] = 'pd' . ($i+1) . 'm=' . urlencode($date[1]);
			$url[] = 'pd' . ($i+1) . 'y=' . urlencode($date[0]);
		}
		$url[] = 'ofsal=' . urlencode($pay_freq_map[$fields['paydate']['frequency']]);
		$url[] = 'sal=' . urlencode($annual_income);
		$url[] = 'mnm=';
		$url[] = 'mar=';
		
		$data = implode('&', $url);
		
		$result->Set_Data_Received($data); //To stop blanks from appearing on bb page
		$result->Set_Thank_You_Content( $this->Thank_You_Content( $data ) );
			
		return $result;
	}

	public function Thank_You_Content($data_received)
	{
		switch(BFW_MODE)
		{
			case 'LIVE':
				$url = 'https://easycashcrew.com';
				break;
			case 'RC':
				$url = 'http://rc.easycashcrew.com';
				break;
			case 'LOCAL':
				$url = 'http://pcl.3.easycashcrew.com.ds59.tss';
		}
		
		//The sub code for when it sells to bb_imc needs to be bb_imc.
		$_SESSION['data']['bb_winner'] = 'bb_imc';

		return parent::Generic_Thank_You_Page($url . '/?page=imagine_card');
	}
}
?>
