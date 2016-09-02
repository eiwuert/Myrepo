<?php

	/**
	 *Maintains the list_mgmt_buffer and list_mgmt_nosell tables in OLP.
	 *These tables are used to determine which applicants can or cannot be remarked (ie sent to epm_collect)
	 *Typically used at the very begining and very end of the application process.
	 * 
	 * @author Vinh Trinh, 4/13/2007
	 */

	class List_Mgmt_Collect
	{
		private $sql;
		private $database;
		
	 	public function __construct($sql,$database)
	 	{
	 		$this->sql = $sql;
	 		$this->database = $database;
	 	}
		
	 	/**
	 	 * Checks to see if an email address exists in the global no sell (list_mgmt_nosell table) listing.
	 	 * Rows in this table are deleted periodically in an external cronjob.
	 	 * This function is typically used at the begining of the application process to check if an applicant
	 	 * has been previously added to the global no sell list by a past vendor.
	 	 * This function is also used by Replace_list_mgmt_nosell to check if an email already exists.
	 	 *
	 	 * @param unknown_type $email
	 	 * @return true if email exists, false if not
	 	 * 
	 	 * @author Vinh Trinh 4/13/2007
	 	 */
	 	public function Check_List_Mgmt_Nosell($email)
	 	{
	 		$query = "
		 		SELECT
					email
		 		FROM
		 			list_mgmt_nosell
		 		WHERE
		 			email = '".mysql_escape_string($email)."'
		 		";
	 		
			try 
			{
				$result = $this->sql->Query($this->database,$query);
			}
			catch(Exception $e)
			{
				//Do nothing for now	
				//throw $e;
			}
	 		
			if($this->sql->Row_Count($result) > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
			
	 	}
	 	
	 	/**
	 	 * Inserts or Updates into the current global No sell table (list_mgmt_nosell).
	 	 * list_mgmt_nosell is scanned with an external cronjob and entries are deleted when they reach 
	 	 * a certain expiration date.
	 	 * All emails in this table unless removed are remarketed (sent to epm_collect)
	 	 * Typically used at the end of the application.
	 	 *
	 	 * @param  string $email
	 	 * @param string $target - property short
	 	 * 
	 	 * @author Vinh Trinh - 4/13/2007
	 	 */
		public function Replace_Into_List_Mgmt_Nosell($email,$target)
		{
			/* This query is not working as intended. Replaced with  check/update/insert queries.
			$query = "
			REPLACE INTO
				list_mgmt_nosell
					(email,
					target_id)
				VALUES
					('$email',
					$target_id)
			";
			*/
			
			$target_id = $this->Target_Id_From_Target($target);
			
			if($this->Check_List_Mgmt_Nosell($email) == true)
			{
				$query = "
					UPDATE 	
						list_mgmt_nosell
					SET
						target_id = ".mysql_escape_string($target_id)."
					WHERE
						email = '".mysql_escape_string($email)."'					
				";
			}
			else
			{
				$query = "
					INSERT INTO	
						list_mgmt_nosell
							(email,
							target_id)
						VALUES
							(
							'".mysql_escape_string($email)."',
							".mysql_escape_string($target_id).")
				";
			}
			
			try 
			{
				$result = $this->sql->Query($this->database,$query);
			}
			catch(Exception $e)
			{
				//throw $e;
				//Do Nothing For Now
			}
			
		}
		
		/**
		 * This function removes applicatants from the buffer preventing them to be sent to epm_collect.
		 * This function is typically called at the end of th application process after a lead is accepted by a vendor\
		 * If the vendor wishes its leads not be on any list managers than we will remove it from the buffer.
		 * List_mgmt_buffer is scanned by an external cronjob and rows are sent to epm collect.
		 * @param int $application_id
		 * 
		 * @author Vinh Trinh - 4/13/2007
		 */
		public function Remove_From_List_Mgmt_Buffer($application_id)
		{
			$query = "
				DELETE FROM
					list_mgmt_buffer
				WHERE
					application_id = ".mysql_escape_string($application_id)."
			";
			
			try 
			{
				$this->sql->Query($this->database,$query);
			}
			catch (Exception $e)
			{
				//throw $e;
				// Do Nothing for now
			}
		}
		
		
		
		/**
		 * Inserts an application into the list_mgmt_buffer table in olp. An external cronjob is 
		 * periodically run to move entries out into epm_collect.
		 * This function is typically used at the begining of the application process because by default, all
		 * applicants are sent to epm_collect unless sold to a vendor that states otherwise.
		 * 
		 * @param int application_id - applicaiton #
		 * @param string email
		 * @param string first_name
		 * @param string last_name
		 * @param int ole_site_id - Ole site Id as defeined in webadmin1
		 * @param int ole_list_id - ole list id as defeined in webadmin1
		 * @param int group_id - group id as defeine din webadmin1
		 * 
		 * @author Vinh Trinh 4/13/2007
		 */
		
		public function Insert_Into_List_Mgmt_Buffer(
			$application_id,
			$email, 
			$first_name, 
			$last_name,
			$ole_site_id, 
			$ole_list_id, 
			$group_id,
			$mode,
			$license_key,
			$address_1,
			$apartment,
			$city,
			$state,
			$zip,
			$url,
			$phone_home,
			$phone_cell,
			$date_of_birth,
			$promo_id,
			$bb_vendor_bypass,
			$tier
		)	
		{

			$query = "
				INSERT INTO 
					list_mgmt_buffer
						(application_id,
						email,
						first_name,
						last_name,
						ole_list_id,
						ole_site_id,
						group_id,
						mode,
						license_key,
						address_1,
						apartment,
						city,
						state,
						zip,
						url,
						phone_home,
						phone_cell,
						date_of_birth,
						promo_id,
						bb_vendor_bypass,
						tier)
					VALUES
						(
						'".mysql_escape_string($application_id)."',
						'".mysql_escape_string($email)."',
						'".mysql_escape_string($first_name)."',
						'".mysql_escape_string($last_name)."',
						{$ole_site_id},
						{$ole_list_id},
						{$group_id},
						'".mysql_escape_string($mode)."',
						'".mysql_escape_string($license_key)."',
						'".mysql_escape_string($address_1)."',
						'".mysql_escape_string($apartment)."',
						'".mysql_escape_string($city)."',
						'".mysql_escape_string($state)."',
						'".mysql_escape_string($zip)."',
						'".mysql_escape_string($url)."',
						'".mysql_escape_string($phone_home)."',
						'".mysql_escape_string($phone_cell)."',
						'".mysql_escape_string($date_of_birth)."',
						{$promo_id},
						{$bb_vendor_bypass},
						{$tier}
						)
			";
			
			try 
			{
				$this->sql->Query($this->database,$query);
			}
			catch(Exception $e)
			{
				throw($e);
				// Do Nothing For now
			}
		}
		
		/**
		 * Updates our list management buffer record
		 *
		 * @param integer $application_id
		 * @param string $email
		 * @param string $first_name
		 * @param string $last_name
		 * @param int $ole_site_id
		 * @param int $ole_list_id
		 * @param int $group_id
		 * @param string $mode
		 * @param string $license_key
		 * @param string $address_1
		 * @param string $apartment
		 * @param string $city
		 * @param string $state
		 * @param string $zip
		 * @param string $url
		 * @param string $phone_home
		 * @param string $phone_cell
		 * @param date $date_of_birth
		 * @param int $promo_id
		 * @param int $bb_vendor_bypass
		 * @param int $tier
		 */
		public function Replace_Into_List_Mgmt_Buffer(
			$application_id,
			$email, 
			$first_name, 
			$last_name,
			$ole_site_id, 
			$ole_list_id, 
			$group_id,
			$mode,
			$license_key,
			$address_1,
			$apartment,
			$city,
			$state,
			$zip,
			$url,
			$phone_home,
			$phone_cell,
			$date_of_birth,
			$promo_id,
			$bb_vendor_bypass,
			$tier
		)	
		{
			
			$query = "
				UPDATE 
					list_mgmt_buffer
				SET
					application_id = '".mysql_escape_string($application_id)."',
					email = '".mysql_escape_string($email)."',
					first_name = '".mysql_escape_string($first_name)."',
					last_name = '".mysql_escape_string($last_name)."',
					ole_list_id = {$ole_site_id},
					ole_site_id = {$ole_list_id},
					group_id = {$group_id},
					mode = '".mysql_escape_string($mode)."',
					license_key = '".mysql_escape_string($license_key)."',
					address_1 = '".mysql_escape_string($address_1)."',
					apartment = '".mysql_escape_string($apartment)."',
					city = '".mysql_escape_string($city)."',
					state = '".mysql_escape_string($state)."',
					zip = '".mysql_escape_string($zip)."',
					url = '".mysql_escape_string($url)."',
					phone_home = '".mysql_escape_string($phone_home)."',
					phone_cell = '".mysql_escape_string($phone_cell)."',
					date_of_birth = '".mysql_escape_string($date_of_birth)."',
					promo_id = {$promo_id},
					bb_vendor_bypass = {$bb_vendor_bypass},
					tier = {$tier}
				WHERE
					application_id = '".mysql_escape_string($application_id)."'";
			
			try 
			{
				$result = $this->sql->Query($this->database,$query);
			}
			catch(Exception $e)
			{
				//throw $e;
				//Do Nothing For Now
			}
			
		}
		
		
		//get the target id (int) from a property short.
		/**
		 * Private helper function to get the target_id from a property short
		 *
		 * @param string $target
		 * @return int target id, false if not found
		 * 
		 * @author Vinh Trinh 4/13/2007
		 */
		private function Target_Id_From_Target($target)
		{
			$query = "
	                                SELECT 
	                                        t.target_id
	                                FROM
	                                        target t
	                                WHERE
	                                        t.status = 'active' and
	                                        t.property_short = '".mysql_escape_string($target)."'
			";
			
			try 	
			{
				$result = $this->sql->Query($this->database,$query);
			}
			catch(Exception $e)
			{
				//throw $e;
				//Do Nothing for now
			}

			if($row_array = $this->sql->Fetch_Array_Row($result))
			{
				$target_id = $row_array['target_id'];
				return $target_id;
			}
			else
			{
				return false;
			}
			
		}

	}
?>
