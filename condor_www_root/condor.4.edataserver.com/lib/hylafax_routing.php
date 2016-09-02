<?php
	require_once('hylafax_db.php');	
	class HylaFax_Routing
	{
		
		protected $mode;
		protected $db;
		

		
		const DIRECTION_INCOMING = 0;
		const DIRECTION_OUTGOING = 1;
		
		const STATUS_INACTIVE = 0;
		const STATUS_ACTIVE = 1;
		
		public function __construct($mode)
		{
			
			$this->mode = $mode;
			$this->db = HylaFax_DB::Get_DB($this->mode);
		}
		
		/**
		 * Find the incoming url based on a DID number
		 * Returns the url or false if it couldn't find 
		 * it.
		 *
		 * @param int $did
		 * @return mixed
		 */
		public function Find_Incoming_By_DID($did)
		{
			$url = FALSE;
			$number = FALSE;
			$query = "
				SELECT
					incoming_url,
					fax_number
				FROM
					did_routing
				LEFT JOIN 
					user 
				ON
					(did_routing.user = user.login)
				WHERE
					did_routing.did_number = '{$did}';
				";
			$rec = $this->db->arrayQuery($query);
			if(is_array($rec) && count($rec))
			{
				$rec = reset($rec);
				$url = $rec['incoming_url'];
				$number = $rec['fax_number'];
			}
			return array($url,$number);
		}
		
		
		/**
		 * Legacy junk for the old analog fax 
		 * servers. New ones use JobControl which
		 * makes this obsolete
		 *
		 * @param unknown_type $number
		 * @return unknown
		 */
		public function Find_Outgoing_Modem($number)
		{
			
			$modem = FALSE;
			
			// select an outgoing modem for this phone number
			$query = "
				SELECT
					modem
				FROM
					number_routing
				WHERE
					number = '{$number}' AND
					direction = ".self::DIRECTION_OUTGOING." AND
					status = ".self::STATUS_ACTIVE."
				ORDER BY
					priority ASC
				LIMIT 1
			";
			$rec = $this->db->arrayQuery($query);
			
			if (is_array($rec) && count($rec))
			{
				
				// get the first record
				$rec = reset($rec);
				$modem = $rec['modem'];
				
			}
			
			return $modem;
			
		}
		
		/**
		 * More Legacy stuff for the old Fax servers.
		 * The new ones route using Find_Incoming_By_DID
		 *
		 * @param unknown_type $modem
		 * @return unknown
		 */
		public function Find_Incoming_Number($modem)
		{
			
			$number = FALSE;
			
			$query = "
				SELECT
					number
				FROM
					number_routing
				WHERE
					number_routing.direction = ".self::DIRECTION_INCOMING." AND
					number_routing.status = ".self::STATUS_ACTIVE." AND
					number_routing.modem = '{$modem}'
				LIMIT 1
			";
			$rec = $this->db->arrayQuery($query);
			
			if (is_array($rec) && count($rec))
			{
				
				$rec = reset($rec);
				$number = $rec['number'];
				
			}
			
			return $number;
			
		}
		
		/**
		 * Legacy stuff for old fax server. New ones use
		 * Find_Incoming_By_DID
		 *
		 * @param unknown_type $number
		 * @return unknown
		 */
		public function Find_Incoming_URL($number)
		{
			
			$url = FALSE;
			
			$query = "
				SELECT
					incoming_url
				FROM
					user_routing,
					user
				WHERE
					user_routing.number = '{$number}' AND
					user_routing.direction = ".self::DIRECTION_INCOMING." AND
					user.login = user_routing.login
				LIMIT 1
			";
			$rec = $this->db->arrayQuery($query);
			
			if (is_array($rec) && count($rec))
			{
				
				$rec = reset($rec);
				$url = $rec['incoming_url'];
				
			}
			return $url;
		}
		
		
	}
	
?>
