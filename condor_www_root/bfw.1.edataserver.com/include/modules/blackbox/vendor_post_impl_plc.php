<?php

/**
 * @Author: vinh.trinh@sellingsource.com
 * 
 * @desc Paydayloancashnow (PLC) campaign implementation
 * This vendor accepts the first 30 leads that pass through the business rules. Leads are not
 * sent to a post server. Leads are accumulated at the end of a day through a cronjob
 * (batch.nightly.paydayloancashnow.php) and are emailed to the recipients.
 */
class Vendor_Post_Impl_PLC extends Abstract_Vendor_Post_Implementation
{
	public function Generate_Fields(&$lead_data, &$params)
	{
		return array();
	}
	
	protected $static_thankyou = TRUE;
	
	public function HTTP_Post_Process($fields, $qualify = FALSE) 
	{
		$t = array();
		$r = TRUE;
		$result = $this->Generate_Result($r, $t);
		$result->Set_Data_Sent(serialize($fields));
		$result->Set_Data_Received(" ");
		$result->Set_Thank_You_Content($this->Thank_You_Content());
			
		
		return $result;
	}
	
	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		$result->Set_Message("Accepted");
		$result->Set_Success(TRUE);
		$result->Set_Thank_You_Content( self::Thank_You_Content($data_received) );
		$result->Set_Vendor_Decision('ACCEPTED');


		return $result;
	}
	
	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [PLC]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
		$content = '<div style="padding: 40px 0px 40px 0px; text-align: center">Your application has been accepted by Payday Loan Cash Now. 
					They will be contacting you shortly via email or phone to complete the loan process.</div>';
					
		if(!$this->Is_SOAP_Type())
		{
			return $content;
		}
		else
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
					$url = 'http://pcl.3.easycashcrew.com.ds70.tss';
			}
			
			$_SESSION['config']->bb_static_thanks = $content;
			return parent::Generic_Thank_You_Page($url . '/?page=bb_static_thanks');
		}	
	}
}
