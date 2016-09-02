<?

/**
 * sms_prpc.php
 * 
 * @desc 
 * 		PRPC wrapper for sms web service
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @version 
 * 		1.0
 * @todo
 * 		comments
 */


include_once 'prpc/server.php';
include_once 'prpc/client.php';
include_once 'sms.iface.php';
include_once 'sms.php';

class SMS_PRPC extends Prpc_Server implements SMS_IFace
{
	
	function __construct()
	{
		parent:: __construct();
	}
	
	
	public function Send_SMS( $phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key = false, $space_key = false, $time_zone_chk = false )
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Send_SMS( $phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key, $space_key, $time_zone_chk );
	}
	
	public function SMS_Reply($phone_number, $message, $modem_id)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->SMS_Reply($phone_number, $message, $modem_id);
	}
	
	public function Scheduled_SMS()
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Scheduled_SMS();
	}
	
	public function Check_Blacklist($phone_number)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Check_Blacklist($phone_number);
	}
	
	public function Add_To_Blacklist($phone_number)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Add_To_Blacklist($phone_number);
	}
	
	public function Remove_From_Blacklist($phone_number)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Remove_From_Blacklist($phone_number);
	}
	
	public function Get_Time_Zone($state)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Get_Time_Zone($state);
	}

	public function Check_Time_Zone($abbrev)
	{
		$this->sms_obj = new SMS();
		return $this->sms_obj->Check_Time_Zone($abbrev);
	}

}


$cm_prpc = new SMS_PRPC();
$cm_prpc->_Prpc_Strict = TRUE;
$cm_prpc->Prpc_Process();
