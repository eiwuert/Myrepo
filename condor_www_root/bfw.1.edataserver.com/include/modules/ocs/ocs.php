<?php


	class OCS
	{
		protected $sql;
		
		protected $caller;
		protected $mode;
		protected $res5;
		
		protected $applog;
		
		public function __construct($caller = '', $mode = 'LIVE')
		{
			$this->caller = strtoupper($caller);
			$this->mode = strtoupper($mode);
			$this->res5 = null;

			$db = Server::Get_Server($mode, 'OCS');
			$this->sql = new MySQLi_1($db['host'], $db['user'], $db['password'], $db['db'], $db['port']);

			$this->applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'OCS', APPLOG_ROTATE, APPLOG_UMASK);
		}

		public function Get_Reservation($rnum, $zip = NULL)
		{
			$data = array();
			
			if(!empty($rnum))
			{
				$this->res5 = substr($rnum, 0, 5);
	
				try
				{
					//Check to make sure the table exists first
					$query = "SHOW TABLES LIKE 'reservation_{$this->res5}'";
					$result = $this->sql->Query($query);
					
					$table_exists = ($result->Row_Count() === 1); 
				
					if($table_exists)
					{
						
						//Use the other FROM line if we move to table_res5 format
						$query = "SELECT reservation_id, name_first, name_last, address_1, address_2, city, state, zip
							FROM reservation_{$this->res5}
							WHERE reservation_id = '{$rnum}'";
						
						$query .= (is_null($zip)) ? "" : " AND zip = '{$zip}'";
		
						$result = $this->sql->Query($query);
						
						if(!empty($result) && $result->Row_Count() > 0)
						{
							$data = $result->Fetch_Array_Row(MYSQLI_ASSOC);
							$data['promo_id'] = $this->Get_Promo();
							
							$data['address'] = (!empty($data['address_2'])) ? $data['address_2'] . ' ' . $data['address_1'] : $data['address_1'];
							unset($data['address_1'], $data['address_2']);
							
							$data['result'] = true;
						}
					}
				}
				catch(Exception $e)
				{
					$data = array();
				}
			}

			if(empty($data))
			{
				$data = array(
					'result' => false,
					'promo_id' => $this->Get_Fail_Promo()
				);
			}

			return $data;
		}
		
		protected function Get_Promo($fail = false)
		{
			$promo = 10000;

			if((!empty($this->res5) || $fail))
			{
				// Fail promos will have campaign_id 0
				if($fail)
				{
					$fail = 1;
					$this->res5 = 0;
				}
				else
				{
					$fail = 0;
				}

				if(!empty($this->caller))
				{
					$caller = "AND caller = '{$this->caller}'";
				}
				
				$query = "SELECT promo_id
					FROM campaign_promo
					WHERE campaign_id = '{$this->res5}'
						{$caller}
						AND fail = {$fail}";

				try
				{
					$result = $this->sql->Query($query);
	
					if(!empty($result) && $result->Row_Count() > 0)
					{
						$row = $result->Fetch_Object_Row();
						$promo = $row->promo_id;
					}
				}
				catch(Exception $e)
				{
					$promo = 10000;
				}
			}

			return $promo;
		}
		
		public function Get_Fail_Promo()
		{
			$this->res5 = 0;
			
			return $this->Get_Promo(true);
		}
		
		public function Insert_Application($application_id, $reservation_id, $ssn)
		{
			$ssn_last_4 = substr($ssn, -4);
			$reservation_id = strrev($reservation_id);
			
			$query = "INSERT INTO application
				(
					date_created,
					application_id,
					reservation_id,
					ssn,
					ssn_last_4
				)
				VALUES
				(
					NOW(),
					{$application_id},
					'{$reservation_id}',
					'{$ssn}',
					'{$ssn_last_4}'
				)";
				
			try
			{
				$this->sql->Query($query);
			}
			catch(Exception $e)
			{
				$this->applog->Write('[OCS] Failed to insert reservation affiliation: ' . $e->getMessage());
			}
		}
		
		public function Insert_Recording($app_id, $string)
		{
			$result = false;
			
			if(!empty($app_id))
			{
				$query = "INSERT INTO ivr_recording
					(
						date_created,
						application_id,
						ivr_string
					)
					VALUES
					(
						NOW(),
						{$app_id},
						'{$string}'
					)";
					
				try
				{
					$this->sql->Query($query);
					$result = true;
				}
				catch(Exception $e)
				{
					$this->applog->Write('[OCS] Failed to insert IVR Recording: ' . $e->getMessage());
				}
			}
			
			return $result;
		}
		
		public function Get_Applications_By_Reservation($res_id, $ssn_last_4)
		{
			$res_id = strrev((string)$res_id);
			$query = "SELECT application_id
				FROM application
				WHERE reservation_id = '{$res_id}'
					AND ssn_last_4 = '{$ssn_last_4}'";
			$result = $this->sql->Query($query);
			
			$apps = array();
			if(!empty($result) && $result->Row_Count() > 0)
			{
				while($row = $result->Fetch_Array_Row())
				{
					$apps[] = $row['application_id'];
				}
			}
			
			return $apps;
		}
		
		public function Get_Applications_By_SSN($ssn, $res_last_4)
		{
			$res_last_4 = strrev($res_last_4);
			$query = "SELECT application_id
				FROM application
				WHERE ssn = '{$ssn}'
					AND reservation_id LIKE '{$res_last_4}%'";
			$result = $this->sql->Query($query);
			
			$apps = array();
			if(!empty($result) && $result->Row_Count() > 0)
			{
				while($row = $result->Fetch_Array_Row())
				{
					$apps[] = $row['application_id'];
				}
			}
			
			return $apps;
		}

		/**
		 * Gets the promo_id for a reservation number
		 *
		 * @param string $res Either the first 5 numbers or an entire reservation_id
		 * @return string the promo id
		 */
		public function getPromoByReservation($reservation_id)
		{
			$this->res5 = substr($reservation_id, 0, 5);
			return $this->Get_Promo();
		}
	}

/*
CREATE TABLE reservation_12119 (
reservation_id bigint not null,
name_first varchar(20) not null default '',
name_last varchar(20) not null default '',
address_1 varchar(30) not null default '',
address_2 varchar(30) not null default '',
city varchar(20) not null default '',
state char(2) not null default '',
zip char(5) not null default '',

PRIMARY KEY (reservation_id)
) ENGINE=MyISAM;

CREATE TABLE campaign_promo (
campaign_id int unsigned not null default 0,
caller varchar(5) not null default '',
promo_id int unsigned not null default 0,
fail tinyint(1) unsigned not null default 0,

PRIMARY KEY (campaign_id, caller, fail)
);
 */

?>
