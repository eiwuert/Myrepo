<?

/**
 * sms.iface.php
 * 
 * @desc
 * 		SMS Web Service Interface
 * @version 
 * 		1.0
 * @author 
 * 		don.adriano@sellingsource.com
 * 		andrew.minerd@sellingsource.com
 * @todo 
 *		comment methods
 * 
 */
 
interface SMS_IFace
{
	/**
	 * 
	 */
	public function Send_SMS($phone_number, $message, $campaign, $state, $delivery_date, $company_short, $track_key=false, $space_key=false, $time_zone_chk=false);
	
	/**
	 *
	 */
	public function SMS_Reply($phone_number, $message, $modem_id);
	
	/**
	 *
	 */
	public function Scheduled_SMS();
	
	/**
	 *
	 */
	public function Check_Blacklist($phone_number);
	
	/**
	 *
	 */
	public function Add_To_Blacklist($phone_number);
	
	/**
	 *
	 */
	public function Remove_From_Blacklist($phone_number);
}