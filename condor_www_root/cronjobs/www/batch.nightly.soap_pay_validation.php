<?php


define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');

require_once('mysql.4.php');
require_once('mysqli.1.php');
include_once('prpc/client.php');

$myObj = new SoapPayValidationEmail();
$myObj->runMe();

class SoapPayValidationEmail
{
	private $sql;
	private $server;
	private $debug;
	private $result_array;
	private $mode;

	function __construct()
	{
   		$this->debug = FALSE; // change this value when ready to go live;
		if($this->debug)
		{
			$this->mode = 'RC';
			$host = 'db101.clkonline.com:3309';
			$user = 'epointps';
			$pass = 'pwsb1tch';
		} 
		else
		{
			$this->mode = 'LIVE';
			$host = 'reader.statpro.ept.tss:3307';
			$user = 'owner';
			$pass = 'llama';
		}

		
		$this->sql =  new MySQL_4($host, $user, $pass,FALSE);
		$this->sql->Connect();
		$this->result_array = array();

   }

   private function getData()
   {
      $query = "
      SELECT promo_id, sum(total_count) my_count, site_name FROM 
      `stat` s1 inner join space s2 on  s1.space_key = s2.space_key  ";

      if($this->debug)
      {
         $query .= " WHERE event_type_key like '%bad%' and s1.date_occured = '2007-09-25' ";
      } else {
         $query .= " WHERE event_type_key = 'bad_twice_monthly' and s1.date_occured = date_sub(CURDATE() , INTERVAL 1 DAY)";
      }
      $query .= " 
      group by promo_id
      ";
      
      // Gather Data
		$results = $this->sql->Query('reportpro',$query);
		$result_array = array();
		
		// Loop over the results so 
		while($row = $this->sql->Fetch_Array_Row($results))
		{
			$result_array[] = $row;
		} // end while
      
      $this->result_array = $result_array;

   }

   public function runMe()
   {
      $this->getData();
      $this->sendData();
   }

	/*
	 * I send the data to the who we need to send it to
	 */
	private function sendData()
	{
		// Prepare Email
		$tx = new OlpTxMailClient(false,$this->mode);
		
		$recipients = array();
		
		if($this->debug)
		{
			$recipients[] = 
				array
				(
					"email_primary_name" => "Test Email", 
					"email_primary" => "adam.englander@sellingsource.com"
				);
		} else {
			$recipients[] = 
				array
				(
					"email_primary_name" => "August Malson", 
					"email_primary" => "august.malson@sellingsource.com"
				);
			$recipients[] = 
				array(
					"email_primary_name" => "Brian Feaver", 
					"email_primary" => "brian.feaver@sellingsource.com"
				);
		}
		
		// Manage Data
		
		// Loop over the results  
      $csv = "Promo ID|count|Site Associated with lead" . "\n";
      
		$rowcount = 0;
		foreach($this->result_array as $app)
		{	
				$csv .= "{$app['promo_id']}|{$app['my_count']}|{$app['site_name']}";
				$csv .= "\n"; // delimiter
				$rowcount++;
		} // end while
		
		$subject = "{$rowcount} Promo IDs that sent us bad pay date info";

		$header = array
		(
		"sender_name"           => "Selling Source <no-reply@sellingsource.com>",
		"subject" 	        	=> $subject,
		"site_name" 	        => "sellingsource.com",
		"message"				=> $subject
		);
		
		
		$attach = array(
			'method' => 'ATTACH',
			'filename' => 'bad_leads.txt',
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($csv),
			'file_data_size' => strlen($csv),
		);
		
		if($rowcount != 0 )
		{
			foreach($recipients as $r){
				$data = array_merge($r,$header);
				
				try
				{
					$result = $tx->sendMessage('live', 'PDDLEADS_CRON', $data['email_primary'], 'bad_leads', $data, array($attach));
				}
				catch(Exception $e)
				{
					$result = FALSE;
				}
				
				if($result)
				{
					print "\r\nEMAIL HAS BEEN SENT TO: ".$r['email_primary'].".\n";
				}
				else
				{
					print "\r\nERROR SENDING EMAIL TO: ".$r['email_primary'].".\n";
				}
			
			}
		} else {
			print "\r\nNo email sent -- no data to send" . ".\n";
		}
	}
	

}

?>
