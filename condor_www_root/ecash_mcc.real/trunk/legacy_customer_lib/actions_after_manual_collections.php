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
		//Do nothing!
		return false;
		
	}
}




?>