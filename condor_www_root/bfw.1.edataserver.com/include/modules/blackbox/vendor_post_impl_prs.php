<?php
/**
 * @desc A concrete implementation class for posting to Imagine Card
 */
class Vendor_Post_Impl_PRS extends Abstract_Vendor_Post_Implementation
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
		$r = ($fields[0]['military'] == 'TRUE') ? TRUE : FALSE;
		$t = array();

		$result = $this->Generate_Result($r, $t);
		
		$result->Set_Data_Sent(serialize($fields));		
		$result->Set_Data_Received($data); //To stop blanks from appearing on bb page
		$result->Set_Thank_You_Content( $this->Thank_You_Content( $data ) );
			
		return $result;
	}

	public function Thank_You_Content($data_received)
	{
		switch(BFW_MODE)
		{
			case 'LIVE':
			case 'RC':
			case 'LOCAL':
				$url = 'http://landing.pioneermilitaryloans.com/index.jsp';
		}

		$url_params = array(
			'partner' => '156642',
			'tpcustom1' => SiteConfig::getInstance()->promo_id,
		); 
		$url .= '?' . http_build_query($url_params);
		
		return parent::Generic_Thank_You_Page($url);
	}
}