<?php

/**
 * Smile Funding (smf) campaign implementation.
 * 
 * This implementation is similiar to PLC's implementation.
 * 
 * @link https://webadmin2.sellingsource.com/index.php?m=tasks&a=view&task_id=12365 New Batch Campaign - Smile Funding (smf)
 * @link https://webadmin2.sellingsource.com/index.php?m=tasks&a=view&task_id=12327 New Batch Campaign - Payday Loan Cash Now (plc)
 * @author: Demin Yin (Demin.Yin@SellingSource.com
 */
class Vendor_Post_Impl_SMF extends Abstract_Vendor_Post_Implementation
{
	protected $static_thankyou = false;
	protected $rpc_params      = array('ALL' => array());
	
	function Generate_Fields(&$lead_data, &$params){
	}
	
	function HTTP_Post_Process($fields, $qualify = false) {
		$t = array();
		$r = true;
		$result = $this->Generate_Result($r, $t);
		$result->Set_Data_Sent(serialize($fields));
		$result->Set_Data_Received(" ");
		$result->Set_Thank_You_Content($this->Thank_You_Content());
		return $result;
	}
	
	function Generate_Result(&$data_received, &$cookies) {
		$result = new Vendor_Post_Result();
		$result->Set_Message("Accepted");
		$result->Set_Success(TRUE);
		$result->Set_Thank_You_Content( self::Thank_You_Content($data_received));
		$result->Set_Vendor_Decision('ACCEPTED');
		return $result;
	}
	
	public function __toString() {
		return "Vendor Post Implementation [SMF]";
	}
	
	public static function Thank_You_Content(&$data_received) {
		return <<<Thank_You_Content
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
		<center>
			Your application has been accepted by <b>Smile Funding</b>.<br>
			They will be contacting you shortly via email or phone to complete the loan process.
		</center>
		<br />
		<br />
		<br />
		<br />
		<br />
		<br />
Thank_You_Content;
	}
}
