<?php

/**
 * @desc A concrete implementation class for posting to ezm
 */
class Vendor_Post_Impl_EZM extends Abstract_Vendor_Post_Implementation
{
	
	protected $rpc_params  = Array
	(
		// Params which will be passed regardless of $this->mode
		'ALL'     => Array(
		    'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/EZMPAN',
			'tcode' => 1352,
			'tcode_twb' => 1354,
			'bb_rcpt_name' => 'Cash Advance Now',
			'bb_rcpt_company' => 'CashAdvanceNow.com',
			'bb_rcpt_fax' => '1-866-653-0374',
			'bb_rcpt_cs_phone' => '1-877-645-2274',
			'bb_url_docs' => 'http://www.cashadvancenow.com/dlfiles/form2-3.doc',
			'bb_doc_name' => 'Cash Advance Forms 2 and 3',
			'bb_rcpt_cs_email' => 'cashadvance@cashadvanceusa.com',
			),
		// Specific cases varying with $this->mode, having higher priority than ALL.
		'LOCAL'   => Array(
			),
		'RC'      => Array(
			),
		'LIVE'    => Array(
			'post_url' => 'http://americacashadvance.com/applyonline.php',
			),
		// The next entries are params specific to property shorts.
		// They have higher priority than all of the previous entries
		'ezm4'    => Array(
			'ALL'      => Array(
				'stat_col' => 'bb_ezm4',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				),
			),
		'ezmcr' => Array(
			'ALL'	=> Array(
				'tcode' => 1394,
				'stat_col' => 'bb_ezmcr',
				'bb_rcpt_cs_email' => 'info@cashadvancenow.com',
				'bb_rcpt_name' => 'Cash Advance Now',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-866-653-0374',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvancenow.com/dlfiles/form2-3.doc',
				'bb_doc_name' => 'Cash Advance Forms 2 and 3',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				'post_url' => 'http://www.cashadvancenow.com/applyonline.php',
				),
			),
		'ezmpan' => Array(
			'ALL'	=> Array(
				'tcode' => 1395,
				'stat_col' => 'bb_ezmpan',
				'bb_rcpt_name' => 'Cash Advance USA',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-877-520-4308',
				'bb_rcpt_cs_email' => 'cashadvance@cashadvanceusa.com',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvanceusa.com/dlfiles/form2-3.doc',
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				'post_url' => 'http://www.cashadvanceusa.com/applyonline.php',
				),
			),
		'ezmcr40' => Array(
			'ALL'	=> Array(
				'tcode' => 1398,
				'bb_rcpt_cs_email' => 'info@cashadvancenow.com',
				'bb_rcpt_name' => 'Cash Advance Now',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-866-653-0374',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvancenow.com/dlfiles/form2-3.doc',
				'bb_doc_name' => 'Cash Advance Forms 2 and 3'
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				'post_url' => 'http://www.cashadvancenow.com/applyonline.php',
				),
			),
		'ezmpan40' => Array(
			'ALL'	=> Array(
				'tcode' => 1399,
				'bb_rcpt_name' => 'Cash Advance USA',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-877-520-4308',
				'bb_rcpt_cs_email' => 'cashadvance@cashadvanceusa.com',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvanceusa.com/dlfiles/form2-3.doc'
				),
			'LOCAL'    => Array(
				),
			'RC'       => Array(
				),
			'LIVE'     => Array(
				'post_url' => 'http://www.cashadvanceusa.com/applyonline.php',
				),
			),
	);
					
	protected $static_thankyou = TRUE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		// set up tcode : ezm trackcode
		$tcode = $lead_data['config']->promo_override ? $params['tcode_twb'] : $params['tcode'];

		// set up field for first page hit
		$fields = array (
			'track' => $tcode,
		);
		
		// set up post vars
		$fields = array (
			'ref' => $tcode,
			'track' => $tcode,
			'xfirst' => $lead_data['data']['name_first'],
			'xfirst' => $lead_data['data']['name_first'],
			'xemail' => $lead_data['data']['email_primary'],
			'xhomephone' => $lead_data['data']['phone_home'],
			'xworkphone' => $lead_data['data']['phone_work'],
			'preq1' => 'yes',	// at least $200 take home pay / week
			'preq2' => 'yes',	// open and active checking account?
			'preq3' => 'yes',	// us resident?
			'preq4' => 'yes',	// do not have 4 or more NSFs
			'step' => '3',
			'name' => $lead_data['data']['name_first'],
			'name2' => $lead_data['data']['name_last'],
			'email' => $lead_data['data']['email_primary'],
			'address' => $lead_data['data']['home_street'],
			'address2' => $lead_data['data']['home_unit'],
			'city' => $lead_data['data']['home_city'],
			'zip' => $lead_data['data']['home_zip'],
			'state' => $lead_data['data']['home_state'],
			'socialsecurity' => $lead_data['data']['social_security_number'],
			'homephone' => $lead_data['data']['phone_home'],
			'mm' => $lead_data['data']['date_dob_m'],
			'dd' => $lead_data['data']['date_dob_d'],
			'yy' => substr ($lead_data['data']['date_dob_y'], 2, 2),
			'employer' => $lead_data['data']['employer_name'],
			'workphone' => $lead_data['data']['phone_work'],
			'directdeposit' => $lead_data['data']['income_direct_deposit'],
			'dateofnextpayday' => date("m/d/Y", strtotime($lead_data['data']['paydates'][0])),
			'payperiod' => $lead_data['data']['paydate']['frequency'],
			'relative1' => $lead_data['data']['ref_01_name_full'],
			'relativephone1' => $lead_data['data']['ref_01_phone_home'],
			'friend' => $lead_data['data']['ref_02_name_full'],
			'friendsphone1' => $lead_data['data']['ref_02_phone_home'],
			'bankname' => $lead_data['data']['bank_name'],
			'checkingaccountnumber' => $lead_data['data']['bank_account'],
			'todaysdate' => date ('m-d-Y'),
			'signature' => $lead_data['data']['ezm_signature'],
			'routingnumber' => $lead_data['data']['bank_aba'],
			'accountnumber' => $lead_data['data']['bank_account'],
			'howdidyouhearaboutus' => 'bb',
			'referredby' => 'bb',
			'initials' => $lead_data['data']['ezm_signature']
		);
		
		return $fields;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if (preg_match('/Approved/i', $data_received))
		{
			$_SESSION['data']['imagine_card'] = TRUE;
			$_SESSION['data']['bb_winner'] = strtoupper($this->property_short);
			$result->Set_Message('Accepted');
			$result->Set_Success(TRUE);
			$result->Set_Next_Page( 'imagine_card' );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			/*if (preg_match ('/Declined - Invalid Track/', $data_received))
			{
				//TODO: Set reject decision
			}*/
			//Other Errors:
			//-Declined
			//-This record already exists
			$result->Set_Message('Rejected');
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}

	//Uncomment the next line to use HTTP GET instead of POST
	public static function Get_Post_Type() {
		return Http_Client::HTTP_GET;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [EZM]";
	}
	
	public static function Thank_You_Content(&$data_received)
	{
		return TRUE;
	}
	
	public static function Set_Session_Data($target)
	{
		$params = array(
			'ezmcr' => Array(
				'bb_rcpt_cs_email' => 'info@cashadvancenow.com',
				'bb_rcpt_name' => 'Cash Advance Now',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-866-653-0374',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvancenow.com/dlfiles/form2-3.doc',
				'bb_doc_name' => 'Cash Advance Forms 2 and 3',
			),
			'ezmcr40' => Array(
				'bb_rcpt_cs_email' => 'info@cashadvancenow.com',
				'bb_rcpt_name' => 'Cash Advance Now',
				'bb_rcpt_company' => 'CashAdvanceNow.com',
				'bb_rcpt_fax' => '1-866-653-0374',
				'bb_rcpt_cs_phone' => '1-877-645-2274',
				'bb_url_docs' => 'http://www.cashadvancenow.com/dlfiles/form2-3.doc',
				'bb_doc_name' => 'Cash Advance Forms 2 and 3',
			),
			'ezmpan' => Array(
				'bb_rcpt_cs_email' => 'cashadvance@cashadvanceusa.com',
				'bb_rcpt_name' => 'Cash Advance USA',
				'bb_rcpt_company' => 'CashAdvanceUsa.com',
				'bb_rcpt_fax' => '1-877-520-4308',
				'bb_rcpt_cs_phone' => '1-866-840-0440',
				'bb_url_docs' => 'http://www.cashadvanceusa.com/dlfiles/form2-3.doc',				
				'bb_doc_name' => 'Cash Advance Forms 2 and 3',
			),
			'ezmpan40' => Array(
				'bb_rcpt_cs_email' => 'cashadvance@cashadvanceusa.com',
				'bb_rcpt_name' => 'Cash Advance USA',
				'bb_rcpt_company' => 'CashAdvanceUsa.com',
				'bb_rcpt_fax' => '1-877-520-4308',
				'bb_rcpt_cs_phone' => '1-866-840-0440',
				'bb_url_docs' => 'http://www.cashadvanceusa.com/dlfiles/form2-3.doc',				
				'bb_doc_name' => 'Cash Advance Forms 2 and 3',
			),
		);	

		$tokens = array( 'bb_rcpt_name', 'bb_rcpt_company', 'bb_rcpt_fax', 'bb_rcpt_cs_email', 'bb_rcpt_cs_phone', 'bb_url_docs', 'bb_doc_name',);
		foreach( $tokens as $token )
		{
			$_SESSION['data'][$token] = $params[strtolower( $target )][$token];
		}
		
	}
	
}