<?
	
	/**
		Desc:
			Queue SMS messages by inserting into the sms database
		
		Auth:
			N. Rodrigo
		
		1/14/2005
			A. Minerd
			Updated for new SMS system
		
	*/
	
	require_once('mysql.4.php');
	require_once('statpro_client.php');
	require_once('prpc/client.php');
	
	class Send_SMS
	{
		
		// default messages for each event -- no longer used,
		// as default messages are stored in the sms database
		private $default_message = array(
			'due_date' => "default due_date message",
			'agreed' => "default agreed message",
			'fund_date' => "default fund_date messaage",
		);
		
		// when in testing mode, only messages
		// to these numbers will be sent
		private $testing_numbers = array(
			'6032641231', // Andrew's number
			'7025809495', // Norbinn's cell
			'8184484748', // Don's cell
			'6613191881', // Brian F's cell
			'6613042865', // Ray L's all night party line
		);
		
		// Used for db connection to check for removed numbers
		private $sql_remove;
		private $sql_remove_db;
		private $sms_prpc;
		
		public $ent_sites = array(
			'ca'	=> array (
				'site'		=> 'Ameriloan.Com',
				'phone'		=> '1-800-362-9090',
				'support'	=> '1-800-756-3122',
			),
			'd1'	=> array (
				'site'		=> '500FastCash.Com',
				'phone'		=> '1-888-919-6669',
				'support'	=> '1-800-756-3126',
			),
			'pcl'	=> array (
				'site'		=> 'OneClickCash.Com',
				'phone'		=> '1-800-230-3266',
				'support'	=> '1-800-756-3118',
			),
			'ucl'	=> array (
				'site'		=> 'UnitedCashloans.Com',
				'phone'		=> '1-800-279-8511',
				'support'	=> '1-800-756-3117',
			),
			'ufc'	=> array (
				'site'		=> 'USFastCash.Com',
				'phone'		=> '1-800-640-1295',
				'support'	=> '1-800-756-3115',
			),
			'ic'	=> array (
				'site'		=> 'ImpactCashUSA.Com',
				'phone'		=> '1-800-707-0102',
				'support'	=> '1-801-295-2548',
			),
		);
		
		public $valid = array(
			'cell_phone'	=> 'null',
			'is_valid'		=> 'null',
		);
		
		public function Send_Message($cell_phone, $event, $property_short, $space_key, $track_key, $mode = 'test', $data = NULL)
		{
			
			// assume we fail
			$sent = FALSE;
			
			switch(strtoupper($mode))
			{
				case 'LIVE':
					$url = 'prpc://sms.edataserver.com/sms_prpc.php';
					break;
				default:
					$url = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
					break;
			}
			
			$this->sms_prpc = new PRPC_Client($url);
			
			// Check for removed cell phone numbers
			if ($this->Valid_Cell_Number($cell_phone, $mode))
			{
				
				// use the DB's default message
				$message = NULL;
				
				// You can send a message directly when invoking the Send_Message
				// function by storing the message in $data['msg_override']
				if (is_array($data) && isset($data['msg_override']))
				{
					$message = $data['msg_override'];
				}
				
				// can't send from testing mode unless
				// our number is in the priveleged list
				if ((strtoupper($mode) === 'LIVE') || in_array($cell_phone, $this->testing_numbers))
				{
					
					// if possible, hit an sms_queued stat
					$statpro_info = $this->StatPro_Info($property_short);
					if ($statpro = $this->Get_StatPro($statpro_info['key'], $statpro_info['pass'], $mode))
					{
						
						// set it up
						$statpro->Track_Key($track_key);
						$statpro->Space_Key($space_key);
						
						// hit the stat
						$statpro->Record_Event('sms_queued');
						
						// all done!
						unset($statpro);
						
					}
					
					// send the message via the PRPC-interface
					$sent = $this->sms_prpc->Send_SMS($cell_phone, $message, $event, NULL, NULL, $property_short, $track_key, $space_key);
					
					// convert to a boolean
					$sent = ($sent !== FALSE);
					
				}
				
			}
			
			return $sent;
	
		}
		
		// This event is triggered when a user agrees.
		// The rest of the triggers are
		// cron-based and can be found in:
		// /virtualhosts/cronjobs/www/send_sms.php
		/**
		 * Enter description here...
		 *
		 * @param object $sql
		 * @param unknown_type $license
		 * @param unknown_type $promo_id
		 * @param unknown_type $promo_sub_code
		 * @param unknown_type $cell_phone
		 * @param unknown_type $mode
		 * @param unknown_type $property_short
		 * @return unknown
		 */
		public function SMS_Agreed(&$sql, $license, $promo_id, $promo_sub_code, $cell_phone, $mode='test', $property_short=NULL)
		{
			
			// assume we fail
			$sent = FALSE;
			
			// default to the property short in our session
			if (is_null($property_short))
			{
				$property_short = $_SESSION['blackbox']['winner'];
			}
			
			// we'll want this lowercased
			$property_short = strtolower($property_short);
			$statpro_info = $this->StatPro_Info($property_short);
			
			// get our page ID
			$page_id = $this->Find_Page_ID($sql, $license);
			
			if ($page_id !== FALSE)
			{
				
				// get statpro information
				$statpro2 = $this->Get_StatPro($statpro_info['key'], $statpro_info['pass'], $mode);
				$space_key = $statpro2->Get_Space_Key($page_id, $promo_id, $promo_sub_code);
				$track_key = $statpro2->Create_Track();
				
				if (isset($this->ent_sites[$property_short]) && $space_key && $track_key)
				{
					
					// get our site name
					$site = $this->ent_sites[strtolower($property_short)]['site'];
					
					// set up our message
					$data = array(
						'msg_override' => "Check your email to confirm your loan application with ".
							"{$site}. Cash may be sent to your bank tonight.",
					);
					
					// send the message
					$sent = $this->Send_Message($cell_phone, 'agreed', $property_short, $space_key, $track_key, $mode, $data);
					
				}
				
			}
			
			return $sent;
			
		}
		
		protected function Find_Page_ID(&$sql, $license)
		{
			
			$page_id = FALSE;
			
			// get our page ID
			$query = "SELECT page_id FROM license_map WHERE license='".$license."'";
			$result = $sql->Query('management', $query);
			
			if ($rec = $sql->Fetch_Array_Row($result))
			{
				$page_id = $rec['page_id'];
			}
			
			return $page_id;
			
		}
		
		protected function StatPro_Info($property_short)
		{
			
			$statpro_info = array( 'key' => '', 'pass' => '', );
		
			switch (strtolower($property_short))
			{
				case 'ic':
					$statpro_info['key'] = 'imp';
					$statpro_info['pass'] = 'h0l3iny0urp4nts';
					break;
				case 'ca':
				case 'd1':
				case 'pcl':
				case 'ucl':
				case 'ufc':
				default:
					$statpro_info['key'] = 'clk';
					$statpro_info['pass'] = 'dfbb7d578d6ca1c136304c845';
					break;
			}
			return $statpro_info;
		}
		
		protected function &Get_StatPro($key, $password, $mode)
		{
			
			// not sure we need this, but just in case
			$mode = (strtoupper($mode) !== 'LIVE') ? $mode = 'test' : 'live';
			
			// create statpro object
			$bin = '/opt/statpro/bin/spc_'.$key.'_'.$mode;
			$statpro = new StatPro_Client($bin, NULL, $key, $password);
			
			return $statpro;
			
		}
		
		/**
		* @desc This function takes a cell phone number and checks to see if it is
		* in the removed list.
		* @param $cell_number string Cell phone number to be checked
		* @param $mode string The release mode being used
		* @return boolean True if not in the removed list, false otherwise
		*/
		private function Valid_Cell_Number($cell_number, $mode = 'test')
		{
			if(!is_object($this->sms_prpc))
			{
				switch(strtoupper($mode))
				{
					case 'LIVE':
						$url = 'prpc://sms.edataserver.com/sms_prpc.php';
						break;
					default:
						$url = 'prpc://rc.sms.edataserver.com/sms_prpc.php';
						break;
				}
			
				$this->sms_prpc = new PRPC_Client($url);
			}
			
			// Is the number in the blacklist?
			if($this->sms_prpc->Check_Blacklist($cell_number))
			{
				// The cell number is in the blacklist, not a valid number
				$valid_number = FALSE;
			}
			else
			{
				// The cell number is NOT in the blacklist, it is valid
				$valid_number = TRUE;
			}
			
			return $valid_number;
		}
	
	}
	
?>
