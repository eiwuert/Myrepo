<?

/**
 * @desc A skeleton Vendor Post class which posts using an HTTP/1.1 POST request.
 *	This should be copied to a new file to create a concrete implentation class.
 */
class Vendor_Post_Impl_SKEL extends Abstract_Vendor_Post_Implementation
{
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://dump.ds32.tss',
				'test'     => 'default',
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				'test' => 'local',
				),
			'RC'      => Array(
				'test' => 'rc',
				),
			'LIVE'    => Array(
				'test' => 'live',
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'test'    => Array(
				'ALL'      => Array(
					'running_as' => 'test campaign',
					),
				'LOCAL'    => Array(
					'running_as' => 'test campaign, local development sandbox',
					),
				'RC'       => Array(
					'running_as' => 'test campaign, release candidate server',
					),
				'LIVE'     => Array(
					'running_as' => 'test campaign, live server',
					),
				),
			'test2'    => Array(
				'ALL'      => Array(
					'running_as' => 'test2 campaign',
					),
				'LOCAL'    => Array(
					'running_as' => 'test2 campaign, local development sandbox',
					),
				'RC'       => Array(
					'running_as' => 'test2 campaign, release candidate server',
					),
				'LIVE'     => Array(
					'running_as' => 'test2 campaign, live server',
					),
				),
		);
	
	protected $static_thankyou = TRUE;
	
	public static function Generate_Fields(&$lead_data, &$params)
	{
		$fields = Array(
			'First_Name' => $lead_data['data']['name_first'],
			'Last_Name' => $lead_data['data']['name_last'],
			'Test' => $params['test'],
			'RunningAs' => $params['running_as'],
		);

		return $fields;
	}
	
	/*
		Fields to be sent to Pre Qualify Vendor Post
	*/
	public static function Generate_Qualify_Fields(&$lead_data, &$params)
	{
		$fields = array (
			'ID' => $params['LeadGenId'],
			'EMAIL' => $lead_data['data']['email_primary'],
			'SSN' => $lead_data['data']['social_security_number'],
		);

		return $fields;
	}	
	public static function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (!strlen($data_received))
		{
			$result->Empty_Response();
		}
		elseif (strpos($data_received, 'Success') !== FALSE)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
		}

		return $result;
	}

//	Uncomment the next line to use HTTP GET instead of POST
//	public static function Get_Post_Type() {return Http_Client::HTTP_GET;}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [SKELETON]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		
		$content = parent::Generic_Thank_You_Page("http://www.sellingsource.com/");
		return($content);
		
	}
	
}
