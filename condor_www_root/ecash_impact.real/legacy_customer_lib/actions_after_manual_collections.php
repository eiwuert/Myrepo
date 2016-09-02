<?php
/*Customer_Actions_After_Manual_Collections
 * Author: Richard Bunce
 * Purpose: This class is used to override behavior after a manual payment is made on an application 
 * in collections
 *  
 */
class Customer_Actions_After_Manual_Collections
{
	protected $server;
	
	public function __construct(Server $server)
	{
		$this->server = $server;
	}
	
	public function run($status, $application_id)
	{
		//check if current
	
		if($status->past_due_balance <= 0)
		{
			$flags = new Application_Flags($this->server, $application_id);
			//check if has ACH Fatal
			if($flags->Get_Flag_State('has_fatal_ach_failure'))
			{
				//has fatal do popup to change bank info set to collections general collections contact

				$data->has_js ="<script>alert('Bank info Must be updated in order for further ACH transactions'); </script>";
				$this->server->transport->Set_Data($data);
			}
		}
		
		Update_Status($this->server, $application_id, array("queued", "contact", "collections", "customer", "*root"));
		return false;
		
	}
}




?>