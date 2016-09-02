<?php
/**
 * Vendor Implementation for Valued Services
 * 
 * This class implements the vendor post for Valued Services
 * 
 * This class must contain the zips for the stores AS WELL AS
 * the restriction list in webadmin2. I thought it would make
 * it easier to also have it restrict by state so you can add the
 * state to the restriction list under webadmin and perhaps it 
 * will be faster than scanning the zip list everytime. Also,
 * I added the address in case they want to put a link to 
 * Google Maps there eventually. 
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 */
class Vendor_Post_Impl_VS extends Abstract_Vendor_Post_Implementation
{
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 6;
	
	public static function Get_Post_Type() {
		return Http_Client::HTTP_GET;
	}
	
	protected $store_id = null;
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
              'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/VS',
              ),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				'post_url' => 'https://fahllc.com/inbound/datafeed.asp'
				),
			'vs' => array(),
			'vs2' => array(
				'LOCAL' => array(),
				'RC' => array(),
				'LIVE' => array(
					'post_url' => 'https://fahllc.com/inbound/datafeed.asp',
					)
			)	
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
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
		
		
		
		$fields = array (
			"st2"   => urlencode($lead_data['data']['name_first']),
			"st3"   => urlencode($lead_data['data']['name_last']),      
            "st4"   => urlencode($lead_data['data']['name_middle']),
			"st16"  => urlencode($lead_data['data']['home_street']),
			"st17"  => urlencode($lead_data['data']['home_city']),
			"st18"  => urlencode($lead_data['data']['home_state']),
			"st19"  => urlencode($lead_data['data']['home_zip']),
			"st14"  => urlencode($lead_data['data']['email_primary']),
			"st8"   => urlencode($lead_data['data']['phone_home']),
			"st22"  => urlencode($lead_data['data']['income_monthly_net']),
			"st23"  => $freq,
			"st13"  => $lead_data['data']['best_call_time'],
			"st5"   => $lead_data['data']['ssn_part_1']. 
			           $lead_data['data']['ssn_part_2']. 
			           $lead_data['data']['ssn_part_3'],
		    "st7" 	=> $lead_data['data']['date_dob_y'] . "-" . 
		    		   $lead_data['data']['date_dob_m'] . "-" .
		    		   $lead_data['data']['date_dob_d'],
		    //Paydates
        	"st24"  => $lead_data['data']['paydates'][0],
        	"st25"  => $lead_data['data']['paydates'][1],
        	"trk"   => $_SESSION['application_id']
        );
        
        $fields["st1"] = null;
		
		//Find correct store
		$state = strtoupper($lead_data['data']['home_state']);
		$zip = $lead_data['data']['home_zip'];
		
		//checking if we grabbed the storeid from a supression list, and 
		//if we did use that instead of parseing the arrays for store id
		if(isset($_SESSION['suppression_list_catch']['vs']['store']['ref']))
		{
			$fields["st1"] = $_SESSION['suppression_list_catch']['vs']['store']['ref'];
		} 
		//Bank Info
        if(isset($lead_data['data']['bank_aba']) && $lead_data['data']['bank_aba'] != "")
        {
        	$fields["st27"] = $lead_data['data']['bank_aba'];
            $fields["st28"] = $lead_data['data']['bank_account'];
            $fields["st26"] = urlencode($lead_data['data']['bank_name']);
        }
        //Bank Account Type
        if(isset($lead_data['data']['bank_account_type']) && $lead_data['data']['bank_account_type'] != "")
        {
            $fields["st29"] = $lead_data['data']['bank_account_type'];
        }
                
        //Work Phone
        if(isset($lead_data['data']['phone_work']) && $lead_data['data']['phone_work'] != "")
        {
            $fields["st9"] = $lead_data['data']['phone_work'];
        }
        //Work Phone Ext
        if(isset($lead_data['data']['ext_work']) && $lead_data['data']['ext_work'] != "")
        {
        	$fields["st12"] = $lead_data['data']['ext_work'];
        }
        //Cell Phone
        if(isset($lead_data['data']['phone_cell']) && $lead_data['data']['phone_cell'] != "")
        {
        	$fields["st11"] = $lead_data['data']['phone_cell'];
        }
        //Fax
        if(isset($lead_data['data']['phone_fax']) && $lead_data['data']['phone_fax'] != "")
        {
        	$fields["st12"] = $lead_data['data']['phone_fax'];
        }
		
		//Residence Type
        if(isset($lead_data['data']['residence_type']) && $lead_data['data']['residence_type'] != "")
        {
        	$fields["st15"] = $lead_data['data']['residence_type'];
        }
		
        //Income Type
        if(isset($lead_data['data']['income_type']) && $lead_data['data']['income_type'] != "")
        {
        	$fields["st21"] = $lead_data['data']['income_type'];
        }
            
        //Employer Name
        if(isset($lead_data['data']['employer_name']) && $lead_data['data']['employer_name'] != "")
        {
            $fields["st20"] = urlencode($lead_data['data']['employer_name']);
        }

        //State-issued ID
        if(isset($lead_data['data']['state_id_number']) && $lead_data['data']['state_id_number'] != "")
        {
            $fields["st6"] = urlencode($lead_data['data']['state_id_number']);
        }
        
        //References
        if(isset($lead_data['data']['ref_01_name_full']) && $lead_data['data']['ref_01_name_full'] != "")
        {
            $fields["st30"] = urlencode($lead_data['data']['ref_01_name_full']);
            $fields["st31"] = urlencode($lead_data['data']['ref_01_phone_home']);
            $fields["st32"] = urlencode($lead_data['data']['ref_01_relationship']);
        }
        if(isset($lead_data['data']['ref_02_name_full']) && $lead_data['data']['ref_02_name_full'] != "")
        {    
            $fields["st33"] = urlencode($lead_data['data']['ref_02_name_full']);
            $fields["st34"] = urlencode($lead_data['data']['ref_02_phone_home']);
            $fields["st35"] = urlencode($lead_data['data']['ref_02_relationship']);
        }        
        
        //Source 
        $fields["st36"] = urlencode("The Selling Source");
        
        return $fields;
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/yes/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
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

	public function __toString()
	{
		return "Vendor Post Implementation [VS]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		//Redirect to mychoicefinancial.com - GFORGE #3481 [MJ]
		switch(BFW_MODE)
		{
			case 'LIVE':
				$url = 'http://mychoicefinancial.com/thankyou.asp';
				//$url = 'https://easycashcrew.com';
				break;
			case 'RC':
				$url = 'http://mychoicefinancial.com/thankyou.asp';
				//$url = 'http://rc.easycashcrew.com';
				break;
			case 'LOCAL':
				$url = 'http://mychoicefinancial.com/thankyou.asp';
				//$url = 'http://pcl.3.easycashcrew.com.ds70.tss';
		}
		$promo_id=SiteConfig::getInstance()->promo_id;
		$site_name =SiteConfig::getInstance()->site_name;
		return parent::Generic_Thank_You_Page($url . '?control='.$promo_id.'&site='.$site_name,self::REDIRECT);
		}
}
?>
