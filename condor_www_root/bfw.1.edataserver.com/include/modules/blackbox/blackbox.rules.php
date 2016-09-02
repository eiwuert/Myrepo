<?php

	/**

		@desc A simple BlackBox rule type

	*/
	class BlackBox_Rule_OldSchool {

		private $field;
		private $type;
		private $param;
		private $event;
		private $config;
		private $modes;

		public function __construct($type = NULL, $field = NULL, $param = NULL, $event = NULL, $config = FALSE)
		{

			if ((!is_null($type)) && (!is_null($param)))
			{
				$this->type = $type;
				$this->param = $param;
				$this->field = $field;
				$this->event = $event;
				$this->config = $config;
			}

		}

		public function Type()
		{
			return($this->type);
		}

		public function Param()
		{
			return($this->param);
		}

		public function Field()
		{
			return($this->field);
		}

		public function Event()
		{
			return($this->event);
		}

		public function Config()
		{
			return($this->config);
		}

		public function Modes()
		{
			return($this->modes);
		}

		public function Sleep()
		{

			$rule = array();

			if ($this->field)	$rule['field'] = $this->field;
			if ($this->type) $rule['type'] = $this->type;
			if (!is_null($this->param)) $rule['param'] = $this->param;
			if ($this->event) $rule['event'] = $this->event;
			if ($this->config) $rule['config'] = $this->config;

			return($rule);

		}

		public function Run(&$blackbox, &$target, &$data)
		{

			$temp = NULL;

			// do they need data?
			if (!is_null($this->field))
			{
				$temp = $this->Get_Data($data, $this->field);
			}

			// assume we're going to run if our
			// current mode is in our list of modes
			$run = (is_null($this->modes) || in_array($blackbox->Mode(), $this->modes));

			// specific mode exceptions
			switch ($blackbox->Mode())
			{
				case MODE_ECASH_REACT:
					// Online Run Certain Rules for ECash React
					if($blackbox->Is_Impact($target->Name()))
					{
						// Mantis #4450 - Impact doesn't want minimum income checked on ecashapp reacts [BF]
						$run = ($this->type == 'Suppression_Lists'
							|| ($this->type == 'Not_In' && $this->field == 'home_state'));
					}
					else
					{
						// Added ability to run In rule for bank_account_type - GForge #10438 [DW]
						// Added ability to run Direct_Deposit rule for income_direct_desosit - GForge #10438 [DW]
						$run = ($this->type == 'Suppression_Lists'
							|| ($this->type == 'Not_In' && $this->field == 'home_state')
							|| ($this->type == 'More_Than_Equals' && $this->field == 'income_monthly_net')
							|| $this->type == 'Allow_Military'
							|| ($this->type == 'In' && $this->field == 'bank_account_type')
							|| $this->type == 'Direct_Deposit');
					}
					break;

				case MODE_ONLINE_CONFIRMATION:
					//Run the react rules on the confirmation page for Impact's reacts.
					if($blackbox->Is_Impact($target->Name()) && $blackbox->Is_React())
					{
						$run = ($this->type == 'Suppression_Lists'
							|| ($this->type == 'Not_In' && $this->field == 'home_state'));
						break;
					}
				//This is intended to fall through

				case MODE_CONFIRMATION:
					// in prequal and confirmation mode, we only
					// run the rules that we have valid data for
					$run = ((is_null($this->field) || (!is_null($temp)))
						&& ($this->type != 'Allow_Military'));
					break;
					
				case MODE_PREQUAL:
					// Moved to PREQUAL for military update GFORGE: 3936 [AuMa]
					// in prequal and confirmation mode, we only
					// run the rules that we have valid data for
					$run = ((is_null($this->field) || (!is_null($temp))));

					//Don't run the military check during prequal if we don't have any data for it.
					//Only midapp sites will have the military question during prequal.
					if(is_array($this->field) && in_array('military', $this->field)
						&& is_array($temp) && is_null($temp[1]))
					{
						$run = false;
					}
					break;
			}

			if ($run)
			{

				// build our parameters
				$params = array($this->param, $temp);
				if ($this->config) $params[] = &$blackbox;

				// run the rule
				$valid = call_user_func_array(array('BlackBox_Rules', $this->type), $params);
			}
			else
			{
				// null indicates that we didn't run the rule
				$valid = NULL;
			}

			return($valid);

		}

		/**
		 * Get Data
		 *
		 * Extract the required field from the data
		 * array, and do basic normalization (convert
		 * 'TRUE'/'FALSE' to booleans).
		 */
		protected function Get_Data(&$data, $field)
		{

			$temp = NULL;

			// an array indicates that we need to
			// "drill down" to data: for instance,
			// $data[paydate_model][income_frequency]
			if (is_array($field))
			{
               $is_nested = end($field);
               if (is_bool($is_nested)) array_pop($field); //remove last boolean element

               if ($is_nested !== false)
               {
					$temp = &$data;

					foreach ($field as $key)
					{
						if (is_array($temp) && isset($temp[$key]))
						{
							$temp = &$temp[$key];
						}
						else
						{
							unset($temp);
							$temp = NULL;
						}
					}

					// be extremely careful
					// with references
					$save = $temp;
					unset($temp);
					$temp = $save;
               }
               else
               { // not nested
                   $temp = array();
                   foreach ($field as $key)
                   {
                       $temp[] = $data[$key];
                   }
               }

			}
			elseif ($field && isset($data[$field]))
			{
				$temp = $data[$field];
			}

			// do a little conversion
			if ($temp)
			{

				if ($temp==='TRUE')
				{
					unset($temp);
					$temp = TRUE;
				}
				elseif ($temp==='FALSE')
				{
					unset($temp);
					$temp = FALSE;
				}

			}

			return($temp);

		}

		private function Valid($data)
		{

			$valid = is_array($data);

			if ($valid) $valid = (isset($data['type']));
			if ($valid) $valid = (isset($data['param']));

			return($valid);

		}

		public function Restore($data)
		{

			$new_rule = FALSE;
			
			if (BlackBox_Rule_OldSchool::Valid($data))
			{

				if (isset($this) && ($this instanceof BlackBox_Rule_OldSchool))
				{
					$new_rule = &$this;
				}
				else
				{
					$new_rule = new BlackBox_Rule_OldSchool();
				}

				$new_rule->type = $data['type'];
				$new_rule->param = $data['param'];

				if (isset($data['field']))
				{
					$new_rule->field = $data['field'];
				}

				if (isset($data['event']))
				{
					$new_rule->event = $data['event'];
				}

				if (isset($data['config']))
				{
					$new_rule->config = $data['config'];
				}

			}

			return($new_rule);

		}

	}

	/**
		@desc Simple rules
	*/
	class BlackBox_Rules {

		private static $target;

		public static function Target($target = NULL)
		{

			if (is_string($target))
			{
				self::$target = $target;
			}

			return(self::$target);

		}

        public static function Equals_No_Case($param, $data)
        {
        	return (strtoupper($param) == strtoupper($data)) ? true : false;
        }
		/***********************************************
		 * This function checks if the $data is not in $param
		 * @param array $param
		 * @param mixed $data
		 * @return boolean
		***********************************************/
		public static function Not_In($param, &$data)
		{

			if (is_array($param))
			{
				$valid = (in_array($data, $param)) ? FALSE : TRUE;
			}
			else
			{
				$valid = ($data == $param) ? FALSE : TRUE;
			}

			return($valid);

		}

		/***********************************************
		 * This function checks if the $data is in $param
		 * @param array $param
		 * @param mixed $data
		 * @return boolean
		***********************************************/
		public static function In($param, &$data)
		{

			if (is_array($param))
			{
				$valid = (in_array($data, $param)) ? TRUE : FALSE;
			}
			else
			{
				$valid = ($data == $param) ? TRUE : FALSE;
			}

			return($valid);
		}

		/***********************************************
		 * This function checks if the data is the same as the option in the database 
		 * for the function of Direct Deposit
		 * @param string $param
		 * @param string $data
		 * @return boolean
		***********************************************/
		public static function Direct_Deposit($param, &$data)
		{

			$valid = ($data === $param);
			return($valid);

		}

		public static function More_Than($param, &$data)
		{

			$valid = ($data > $param) ? TRUE : FALSE;
			return($valid);

		}

		/**
		 * Compares the data to the parameter and returns true if the data is greather than or
		 * equal to the param.
		 *
		 * @param int $param
		 * @param int $data
		 * @return bool
		 */
		public static function More_Than_Equals($param, &$data)
		{
			$valid = ($data >= $param);
			return $valid;
		}
		
		public static function Less_Than($param, &$data)
		{
			return !self::More_Than_Equals($param, $data);
		}
		
		public static function Less_Than_Equals($param, &$data)
		{
			return !self::More_Than($param, $data);
		}

		public static function Required($param, &$data)
		{
			$valid = ((!$param) || ($param && (!empty($data)))) ? TRUE: FALSE;
			return($valid);
		}

		public static function Not_Today($param, &$data)
		{

			$today = date('Y-m-d');
			$valid = BlackBox_Rules::Not_In($param, $today);

			return($valid);

		}

		public static function Allow_Weekends($param, $data)
		{

			$weekday = date('w');
			$valid = ((!$param) && ($weekday==6||$weekday==0)) ? FALSE : TRUE;

			return($valid);

		}

		public function Force_Promo($param, &$data)
		{
			return(TRUE);
		}

		public function Force_Site($param, &$data)
		{
			return(TRUE);
		}

		/**
		 * Checks the target's military lead rules against the application's email address and
		 * military question answer.
		 *
		 * @param string $param
		 * @param array $data
		 * @param BlackBox $blackbox
		 * @return bool
		 */
		public function Allow_Military($param, &$data, $blackbox = NULL)
		{
			
			
			//if military is not set, force a Valid response
			if($data[1] == 'n/a' || is_null($data[1]))
			{
				if ($param == 'ALLOW') // GForge [#3459] - Change military check to fail for 'n/a' [DY]
				{
					$valid = TRUE;
				}
				else
				{
					$valid = FALSE;
				}
			}
			else
			{
				if(!is_null($blackbox))
				{
					$target = BlackBox_Rules::Target();
				}
				$mil_email = preg_match('/.*@.*\.mil$/i',$data[0]);
				switch($param) {
					case 'DENY':
						$valid = (!($mil_email) && ($data[1] === "FALSE"));
						break;
					case 'ONLY':
						$valid = (($mil_email) || $data[1] === "TRUE");
						break;
					default:
						$valid = TRUE;
						break;
				}
				$result = ($valid) ? 'PASS' : 'FAIL';
				//$blackbox->Log_Event('MILITARY_' . $param . '_' . $target, $result, $target);
			}
			return $valid;
		}
		
		public function Minimum_Recur_FLE( $param, &$data, &$blackbox )
		{

			$config = &$blackbox->Config();

			// Date to use below
			$query_date = date('Ymd', strtotime("-$param days"));

			$query = "
				SELECT
					COUNT(*) as count
				FROM
					fle_dupes
				WHERE
					fle_dupes.date_created >= '{$query_date}'
					AND fle_dupes.email = '{$data}'
					AND fle_dupes.site = '{$config->config->site_name}'
					AND fle_dupe_id <> {$config->fle_dupe_id}";

			// run our second dupe check
			$mysql_result = $config->sql->Query( $config->database, $query );
			$result = $config->sql->Fetch_Column( $mysql_result, 'count' );
			$return = $result ? FALSE : TRUE;

			unset($config);
			return($return);

		}

		public function Minimum_Recur( $param, &$data, &$blackbox )
		{
			$config = &$blackbox->Config();
			$total = 0;
			if ($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION)
			{
				// Date to be used in the queries below
				$query_date = date('Y-m-d', strtotime("-$param days"));

				$key = 'MR:' . md5("{$data}:{$query_date}:".self::$target);
				$total = Memcache_Singleton::Get_Instance()->get($key);

				// We didn't find the result in the cache
				if(!$total)
				{
					if (is_numeric($data))
					{
						$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
						$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
						$data_encrypted = $crypt_object->encrypt($data);
						// Social Security Number
						// Removed the UNION per the DBA's (Ted O.). This is to help reduce the number of
						// error 127's showing up on the olp.personal table. [BF]
						$query_list[] = "
							SELECT
								COUNT(*) AS count
							FROM
								application USE INDEX (PRIMARY)
								INNER JOIN personal_encrypted USE INDEX (idx_ssn)
									ON personal_encrypted.application_id = application.application_id
								INNER JOIN target
									ON target.target_id = application.target_id
							WHERE
								application.modified_date >= '$query_date'
								AND personal_encrypted.social_security_number = '$data_encrypted'
								AND target.property_short = '".self::$target."'
								AND application.application_type != 'disagreed'
								AND application.application_type != 'confirmed_disagreed'";

						
						$query_list[] = "SELECT
							    COUNT(*) AS count
							FROM
								blackbox_post AS bp
								INNER JOIN personal_encrypted USE INDEX (idx_ssn)
									ON personal_encrypted.application_id = bp.application_id
							WHERE
								bp.date_created >= '$query_date'
								AND personal_encrypted.social_security_number = '$data_encrypted'
								AND bp.winner = '".self::$target."'";
/* GForge #6804 [BA]
						$query_list[] = "SELECT
								COUNT(*) AS count
							FROM
								personal_encrypted USE INDEX (idx_ssn)
								INNER JOIN blackbox_post AS bp
									ON personal_encrypted.application_id = bp.application_id
								INNER JOIN target
									ON bp.winner = target.property_short
								INNER JOIN rules
									ON target.target_id = rules.target_id
							WHERE
								bp.success = 'FALSE'
								AND rules.withheld_targets RLIKE '[[:<:]]".self::$target."[[:>:]]'
								AND target.status = 'ACTIVE'
								AND rules.status = 'ACTIVE'
								AND personal_encrypted.social_security_number = '$data_encrypted'
								AND bp.date_modified >= '$query_date'";
*/
					}
					else
					{

						// Email Address.
						$query_list[] = "SELECT
								COUNT(*) AS count
							FROM
								application USE INDEX (PRIMARY)
								INNER JOIN personal_encrypted USE INDEX (idx_email)
									ON personal_encrypted.application_id = application.application_id
								INNER JOIN target
									ON target.target_id = application.target_id
							WHERE
								application.modified_date >= '$query_date'
								AND personal_encrypted.email = '$data'
								AND target.property_short = '".self::$target."'
								AND application.application_type != 'disagreed'
								AND application.application_type != 'confirmed_disagreed'";

						$query_list[] = "SELECT
							    COUNT(*) AS count
							FROM
								blackbox_post AS bp
								INNER JOIN personal_encrypted USE INDEX (idx_email)
									ON personal_encrypted.application_id = bp.application_id
							WHERE
								bp.date_created >= '$query_date'
								AND personal_encrypted.email = '$data'
								AND bp.winner = '".self::$target."'";
/* GForge #6804 [BA]
						$query_list[] = "SELECT
								COUNT(*) AS count
							FROM
								personal_encrypted USE INDEX (idx_email)
								INNER JOIN blackbox_post AS bp
									ON personal_encrypted.application_id = bp.application_id
								INNER JOIN target
									ON bp.winner = target.property_short
								INNER JOIN rules
									ON target.target_id = rules.target_id
							WHERE
								bp.success = 'FALSE'
								AND rules.withheld_targets RLIKE '[[:<:]]".self::$target."[[:>:]]'
								AND target.status = 'ACTIVE'
								AND rules.status = 'ACTIVE'
								AND personal_encrypted.email = '$data'
								AND bp.date_modified >= '$query_date'";
*/
					}

					$total = 0;

					// Loop through the queries and total up the counts
					foreach($query_list as $query)
					{
						$mysql_result = $config->sql->Query( $config->database, $query );
						while ($row = $config->sql->Fetch_Array_Row($mysql_result))
						{
							$total += $row['count'];
						}
						$config->sql->Free_Result($mysql_result);
					}

					// Set in cache if the total is greater than 0
					if($total > 0)
					{
						$time = getdate();
						$expire = mktime(0, 0, 0, $time['mon'], $time['mday'] + 1, $time['year']);

						// Add to cache, expire at the end of the day
						Memcache_Singleton::Get_Instance()->add($key, $total, $expire);
					}
				}

				$return = ($total == 0) ? TRUE : FALSE;

			}
			else
			{
				// didn't run
				$return = NULL;
			}

			unset($config);
			return $return;

		}

		public function Direct_Deposit_Recur($param, $data, &$config)
		{
			$return = NULL;
			$crypt_config 	= Crypt_Config::Get_Config(BFW_MODE);
			$crypt_object		= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
			$data_encrypted =   $crypt_object->encrypt($data);
			
			if(intval($param) > 0 && strlen($data) == 9 && $config->Mode() !== MODE_CONFIRMATION && $config->Mode() !== MODE_ONLINE_CONFIRMATION)
			{
				$param = intval($param);

				$query = "SELECT COUNT(*) AS count
					FROM
						personal_encrypted
						INNER JOIN bank_info_encrypted USING (application_id)
					WHERE
						personal_encrypted.social_security_number = '{$data_encrypted}'
						AND personal_encrypted.modified_date > DATE_SUB(CURDATE(), INTERVAL {$param} DAY)
						AND bank_info_encrypted.direct_deposit != 'TRUE'";

				$result = $config->sql->Query($config->database, $query);

				if($result && $config->sql->Row_Count($result))
				{
					$total = $config->sql->Fetch_Column($result, 0);

					$return = (intval($total) === 0);
				}
			}

			return $return;
		}

		public function Operating_Hours($param, &$data)
		{
			$valid = true;

			$now_time = time();
			$string_day_of_week = date("l",$now_time);
			$string_date = date("m-d-Y",$now_time);
			$string_time = date("H:i:s",$now_time);

			// Uncomment to force a weekend
			//$string_day_of_week = "Saturday";
			//$string_day_of_week = "Sunday";

			//Figure out which operating hours schedule we need to use
			//$params[6..8] will hold special operating hours for specific days
			if($param[6] == $string_date){
				$start_param = $param[7];
				$stop_param  = $param[8];
			}
			elseif($string_day_of_week == "Saturday" && $param[2]){
				$start_param = $param[2];
				$stop_param =  $param[3];
			}
			elseif($string_day_of_week == "Sunday" && $param[4]){
				$start_param = $param[4];
				$stop_param = $param[5];
			}
			// Default for all days
			else{;
				$start_param = $param[0];
				$stop_param = $param[1];
			}


			//Check to see if time goes into tommrow
			$start = explode(":",$start_param);
			$stop = explode(":",$stop_param);

			$start[0] = ($start[0] != 12 && $start[2] == 'PM') ? $start[0] + 12  : $start[0];
			$start[0] = ($start[0] == 12 && $start[2] == 'AM') ? $start[0] = 0 : $start[0];

			$stop[0] = ($stop[0] != 12 && $stop[2] == 'PM') ? $stop[0] + 12 : $stop[0];
			$stop[0] = ($stop[0] == 12 && $stop[2] == 'AM') ? $stop[0] = 0 : $stop[0];

			if((mktime($start[0],$start[1],0) > mktime($stop[0],$stop[1],0)))
			{
				$start_time = mktime($start[0],$start[1],0);
				$stop_time 	= mktime($stop[0],$stop[1],0,date("m"),date("d")+1);
			}
			else
			{
				$start_time = mktime($start[0],$start[1],0);
				$stop_time 	= mktime($stop[0],$stop[1],0);
			}

			$now_time = time();

			$valid = (($start_time < $now_time) && ($stop_time > $now_time)) ? true : false;
			return($valid);
		}

		/**
		 * Check that the passed in date earlier
		 * than the date specified
		 *
		 * @param int Months requirement
		 * @param string date
		 * @return boolean TRUE if they meet the requirement
		 */
		public function Later_Than_Date_Months($param, $data)
		{
			if($param == 0 || $param == "") return TRUE;

			if(!is_numeric($param)) return FALSE;

			$check_date = strtotime('-' . $param . ' months');
			
			// This is the thing we are checking
			$mydate = strtotime($data);

			return ($mydate < $check_date) ? TRUE : FALSE;
		}

        /**
         * Check For Minimum Age Requirements
         *
         * @param int Years old requirement
         * @param string DOB in format m/d/y (ex 01/01/06)
         * @return boolean True if they meet age requirement
         */
        public function Minimum_Age($param, $data)
        {
            //Zero turns check off
            if($param == 0 || $param == "") return true;

            $matches = array();
            if(!preg_match("/([\d]{1,2})\/([\d]{1,2})\/(\d{4}|\d{2})/",$data,$matches)) return false;
            if(!is_numeric($param)) return false;

            $dob = mktime(0,0,0,$matches[1],$matches[2],$matches[3]);
            $check_date = strtotime("-" . $param . " years");

            return ($dob < $check_date) ? true : false;
        }

         /**
          * Check if home/business phone numbers are valid or not.
          * If home/business phone numbers are identical and system doesn't allow identical phone numbers,
          * the phone numbers are not valid. Otherwise, they are valid.
          * Here we won't reformat the phone numbers and won't check if phone number format is correct or not.
          *
          * @param array $param identical_phone_numbers value in table olp_bb_visitor. (boolean)
          * @param enum $data An array contains 1 home phone number and 1 business phone number.
          * @return boolean True if phone numbers are valid, otherwise false.
          */
       public static function Valid_Phone_Numbers($param, $data)
       {
           if ($param || !is_array($data))  // system allows idental #, or $data isn't an array
           {
               return true;
           }
           else
           {
               if ( (empty($data[0]) && empty($data[1])) // both phone # are empty
               	 || ($data[0] != $data[1]) // phone # are not identical
               )
               {
                   return true;
               }

               // phone # are identical
               return false;
           }
       }

       /**
		* No paydate with N days [DY]
		*
		* @link http://bugs.edataserver.com/view.php?id=8769 48-Modify Loan Due Date, Next Paydate within 4 days (On/Off)
		* @param int $param paydate_minimum value in webadmin settings.
		* @param NULL $data
		* @param object $blackbox see $config_map in function BlackBox_Target::Rules_From_Row().
		* @return boolean alwasy return TRUE. We will change paydate (if necessary) in abstract_vendor_post_impl....php later.
		*/
		public function Paydate_Minimum( $param, &$data, &$blackbox ) {
			$config = &$blackbox->Config(); // BlackBox_Config Object; $config->config is Base_Frame_Work::$config;

			if (!$config->config->paydate_minimum || !is_array($config->config->paydate_minimum)) {
				$config->config->paydate_minimum = array( BlackBox_Rules::$target => (int) $param );
			} else {
				$config->config->paydate_minimum[BlackBox_Rules::$target] = (int) $param ;
			}

			return TRUE;
		}
		

		public function Suppression_Lists($param, $data, &$blackbox)
		{

			$valid = NULL;

			$target = BlackBox_Rules::Target();
			$config = &$blackbox->Config();
			
			// If you know of a better way to get the target_id...
			$target_id = 0;
			$query = "
				SELECT
					target_id
				FROM
					target
				WHERE
					property_short = '" . $target . "'";
			
			$mysql_result = $config->sql->Query( $config->database, $query );
			if ($row = $config->sql->Fetch_Array_Row($mysql_result))
			{
				$target_id = $row['target_id'];
			}
			
			$target_stats = OLPStats_Spaces::getInstance(
				$config->mode,
				$target_id,
				$config->bb_mode,
				$config->config->page_id,
				$config->config->promo_id,
				$config->config->promo_sub_code
			);

			// lists in $param
			foreach ($param as $id=>$action)
			{
				//Parse mode and
				$mode = (is_array($action)) ? $action[1] : 'ALL';
				$action = (is_array($action)) ? $action[0] : $action;

				//Check mode
				if($mode != 'ALL' && $mode != $blackbox->Mode()) continue;

				//You may think this is retarded and you would be correct
				//Eventually, the lists will be removed and readded as Broker modes
				// if($mode == 'ALL' && $blackbox->Mode() == MODE_PREQUAL) continue; // Mantis #10351 [DY] Mantis #10609 [DY] Mantis #11514 [DY]

				// once we found a store we, don't need to go through more store lists
				if((strtoupper($action) == 'CATCH') &&
					$_SESSION['suppression_list_catch'][strtolower($target)]['caught'] === true)
				{
					continue; // Skip this suppression list
				}

				// load the suppression list
				$list = new Cache_Suppress_List($config->sql, $config->database);
				$list->Load($id);

				/*
					For ecashapp.com we only want to run certain lists. There currently
					doesn't exist a way to restrict what lists are run except by target
					(the default way this works). So here we defined an array of lists
					that we want to run and if it's in the array, we run it. If not,
					we skip it.
				*/
				if($blackbox->Mode() === MODE_ECASH_REACT && !in_array($list->Name(), array(
					'KS_GA_WV_zips',
					'TSS Employee SSN Suppression',
					'CLK Employee SSN Suppression',
					'CLK Military Suppression List',
				))) // end if
				{
					continue; // Skip this suppression list
				}

				//Check if we have the data before running
				$data = (isset($config->data[$list->Field()]) ? $config->data[$list->Field()] : NULL);

				if (!is_null($data))
				{
					/**
					 * Originally changed for Mantis #7510 to store suppression list validity.
					 * This was modified to store both the validity and the result, when it
					 * was discovered that Verify lists were coming back with PASS results.
					 * 
					 * This prevents us from having to run the lists over and over when we know
					 * they'll have the same result.
					 * 
					 * The $cache_key was added to account for different lists being used as VERIFY
					 * or EXCLUDE lists. It would use the result from whichever was first. For
					 * example, if we used the Employer Watch List as a VERIFY list for CA, but as
					 * an EXCLUDE list for UFC, and CA ran first, then the EXCLUDE list for UFC
					 * would show up with a result of VERIFY or VERIFIED because CA ran first. It
					 * should re-run the list or take the result from another target that also
					 * has it as an EXCLUDE list and have a result of PASS or FAIL.
					 */
					$cache_key = sprintf("%s-%u", $action, $id);
					if(isset($_SESSION['suppression_results'][$cache_key]))
					{
						list($valid, $result) = $_SESSION['suppression_results'][$cache_key];
					}
					else
					{
						switch (strtoupper($action))
						{
							case 'EXCLUDE':
								$valid = (!$list->Match($data));
								$result = ($valid ? EVENT_PASS : EVENT_FAIL);
								break;

							case 'RESTRICT':
								$valid = $list->Match($data);
								$result = ($valid ? EVENT_PASS : EVENT_FAIL);
								break;

							case 'VERIFY':
								$valid = TRUE; // We're still valid if it's a verify list
								$verify = $list->Match($data);
								$result = ($verify ? 'VERIFY' : 'VERIFIED');
								break;
							case 'CATCH':
								$verify = $list->Match($data);
								$result = ($verify ? 'CAUGHT' : 'MISS');
								if(($verify) && (preg_match("/(\w*)_(\w*)_(.*)/i",$list->Name(),$matching))) {
									$_SESSION['suppression_list_catch'][strtolower($matching[1])][strtolower($matching[2])]['ref'] = $matching[3];
									$_SESSION['suppression_list_catch'][strtolower($matching[1])][strtolower($matching[2])]['desc'] = $list->Description();
									$_SESSION['suppression_list_catch'][strtolower($target)]['caught'] = true;
								}
								break;
						}
						
						// See comment above where this is checked for changes.
						$_SESSION['suppression_results'][$cache_key] = array($valid, $result);
					}

					// checking if this is a VS store supression list and saving the store if it is and it matched
					// this is just here till the lists get transfered to a CATCH
					if ((strtoupper($action) == 'VERIFY') && ($verify) && (preg_match("/vs(\d*)/i",$list->Name(),$matching)))
					{
						$_SESSION['suppression_list_catch']['vs']['store']['ref'] = $matching[1];
					}
					if (isset($result) && $result != 'MISS')
					{
						// We need to store that UFC had a VERIFY result
						if (strcasecmp($action,'VERIFY') === 0 
							&& strcasecmp($target,'UFC') === 0 
							&& strcasecmp($result,'VERIFY') === 0)
						{
							$_SESSION['UFC_SUPPRESSION_VERIFY'] = TRUE;
						}
						$event = 'LIST_'.strtoupper($action).'_'.strtoupper($list->Field()).'_'.$id;
						$blackbox->Log_Event($event, $result, $target);
					}
				}

				// only fail one!
				if($valid === FALSE)
				{
					// Store so we can use it somewhere else like ECash REacts :p [RL]
					$_SESSION['SUPPRESSION_LIST_FAILURE'][strtoupper($list->Field())] = strtoupper($action);
					
					/// NOTE: Suppression List Failure for Decisioning Report goes here
					if ($target_stats) $target_stats->hitStat('suppression_' . $list->Field() . '_fail');
					
					break;
				}
			}


			unset($config);
			return $valid;

		}

		public function Reference_Count($param, $data, $config)
		{
			if($config->Mode() == MODE_DEFAULT)
			{
				for($i = 0; $i < intval($param * 3); $i++) { // added by [DY] (No Task # - Fix Reference_Count function.)
					if (empty($data[$i])) return false;
				}

				return true;
			}
			else
			{
				return NULL;
			}
		}
	}
	
	class BlackBox_Verify_Rules
	{

		public function Run(&$blackbox, BlackBox_Target_OldSchool &$target, &$data)
		{

			// run our rules
			//$this->Same_Work_And_Home($blackbox, $data); //Replaced by verifyWorkPhoneType - GForge #4249 [MJ]
			//$this->Work_Phone_Type($blackbox); //Replaced by verifyWorkPhoneType - GForge #4249 [MJ]
			$this->Minimum_Income($blackbox, $data);
			$this->Paydate_Proximity($blackbox, $data);

			// we always pass!
			return;

		}

		protected function Same_Work_And_Home(&$blackbox, &$data)
		{

			$home = @$data['phone_home'];
			$work = @$data['phone_work'];

			if (($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION) || ($home && $work))
			{

				// do the check -- simple enough, eh?
				$verify = (@$home == @$work);

				// log the result for digestion later
				$result = ($verify) ? 'VERIFY' : 'VERIFIED';
				$blackbox->Log_Event('VERIFY_SAME_WH', $result);

			}
			else
			{
				$verify = NULL;
			}

			return $verify;

		}

		/**
		 * Checks if work phone needs to be verified.
		 * 
		 * Checks for buckets returned in a datax perf call for work phone number
		 * and logs events for whether the information needs to be verified (verify)
		 * or has been verified (verified) and by the perf call for the conditions 
		 * that the buckets represent. GForge #4249 [MJ]
		 *
		 * @param  object $blackbox  Used to get application information and
		 *                            log events.
		 * @return bool   $verify  TRUE if not in confirmation mode and 
		 * 							work phone needs to be checked, otherwise
		 * 							it is NULL.
		 */
		protected function verifyWorkPhoneType(&$blackbox)
		{
			/*
			 * DataX performance does NOT get re-run in
			 * confirmation mode, so there's no point in
			 * repulling the records here
			 */
			if ($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION)
			{
				$verify = TRUE;
				$config = &$blackbox->Config();

				// pull our Performance packet
				$auth = new Authentication($config->sql, $config->database, $config->applog);
				$response = reset($auth->Get_Records($config->application_id, Authentication::DATAX_PERF));

				if ($response)
				{
					$response = $response['received_package'];
					$response = @simplexml_load_string($response);
				}
				
				//All are verified unless they appear in a bucket.
				$bucket_statuses = array('R1'=>'VERIFIED',
										'R2'=>'VERIFIED',
										'R3'=>'VERIFIED',
										'R4'=>'VERIFIED',
										'R5'=>'VERIFIED');

				if($response)
				{
					$buckets = $response->Response->Summary->DecisionBuckets->Bucket;
					foreach($buckets as $bucket)
					{
						$bucket_statuses["$bucket"] = 'VERIFY';
					}
				}
				else
				{
					$bucket_statuses = array('ALL' => 'ERROR');
				}

				foreach($bucket_statuses as $bucket => $bucket_status)
				{
					switch($bucket)
					{
						case 'R1':
							$blackbox->Log_Event('VERIFY_SAME_WH', $bucket_status);
							break;
						case 'R2':
							$blackbox->Log_Event('VERIFY_W_TOLL_FREE', $bucket_status);
							break;
						case 'R3':
							$blackbox->Log_Event('VERIFY_WH_AREA', $bucket_status);
							break;
						case 'R4':
							$blackbox->Log_Event('VERIFY_W_PHONE', $bucket_status);
							break;
						case 'R5':
							$blackbox->Log_Event('VERIFY_SAME_CR_W_PHONE', $bucket_status);
							break;
						case 'ALL':
							$blackbox->Log_Event('VERIFY_SAME_WH', $bucket_status);
							$blackbox->Log_Event('VERIFY_W_TOLL_FREE', $bucket_status);
							$blackbox->Log_Event('VERIFY_WH_AREA', $bucket_status);
							$blackbox->Log_Event('VERIFY_W_PHONE', $bucket_status);
							$blackbox->Log_Event('VERIFY_SAME_CR_W_PHONE', $bucket_status);
							break;
						default:
							break;
					}
				}
				unset($config);
			}
			else
			{//In confirmation mode
				$verify = NULL;
			}
			return $verify;
		}
		
		//Replaced by verifyWorkPhoneType - GForge #4249 [MJ]
		protected function Work_Phone_Type(&$blackbox)
		{

			// DataX performance does NOT get re-run in
			// confirmation mode, so there's no point in
			// repulling the records here
			if ($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION)
			{

				$verify = TRUE;
				$config = &$blackbox->Config();

				// pull our Performance packet
				$auth = new Authentication($config->sql, $config->database, $config->applog);
				$response = reset($auth->Get_Records($config->application_id, Authentication::DATAX_PERF));

				if ($response)
				{
					$response = $response['received_package'];
					$response = @simplexml_load_string($response);
				}

				if ($response)
				{
					// Commenting out 2nd part of the conditional as a possible fix to issue mantis 6693 [VT]
					if(($response->Response->Detail->REVERSEPHONE))// && (strlen(@(string)$response->Response->Detail->REVERSEPHONE->record_type) >= 1))
					{
						$record_type = $response ? @(string)$response->Response->Detail->REVERSEPHONE->record_type : '';
						$phone_type = $response ? @(string)$response->Response->Detail->REVERSEPHONE->phone_type : '';


						// check for a business record type (B)
						if ($fail = ($record_type != 'B')) $verify = TRUE;

						// log the outcome
						$result = ($fail ? 'VERIFY' : 'VERIFIED');
						$blackbox->Log_Event('VERIFY_WORK_BIZ', $result);

						// check for POTS (Plain Ol' Telephone Service)
						if ($fail = ((!is_numeric($phone_type)) || ($phone_type != '00'))) $verify = TRUE;

						// log the outcome
						$result = ($fail ? 'VERIFY' : 'VERIFIED');
						$blackbox->Log_Event('VERIFY_WORK_CELL', $result);
					}
					else
					{
						// log these separately
						$blackbox->Log_Event('VERIFY_WORK_BIZ', 'ERROR');
						$blackbox->Log_Event('VERIFY_WORK_CELL', 'ERROR');
					}
				}
				else
				{
					// log these separately
					$blackbox->Log_Event('VERIFY_WORK_BIZ', 'ERROR');
					$blackbox->Log_Event('VERIFY_WORK_CELL', 'ERROR');
				}

				unset($config);

			}
			else
			{
				$verify = NULL;
			}

			return $verify;

		}

		protected function Minimum_Income(&$blackbox, $data)
		{

			$income = (int)@$data['income_monthly_net'];

			if (($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION) || $income)
			{

				// do the check -- simple enough, eh?
				$verify = ($income < 1300);

				// log the result for digestion later
				$result = ($verify) ? 'VERIFY' : 'VERIFIED';
				$blackbox->Log_Event('VERIFY_MIN_INCOME', $result);

			}
			else
			{
				$verify = NULL;
			}

			return $verify;

		}

		protected function Paydate_Proximity(&$blackbox, $data)
		{

			$paydates = isset($_SESSION['data']['paydates']) ? $_SESSION['data']['paydates'] : FALSE;

			if (($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION) || ($paydates !== FALSE))
			{

				// assume we're fine
				$verify = TRUE;

				// get the paydate array OLP built (as timestamps for simplicity)
				$paydates = @array_map('strtotime', $_SESSION['data']['paydates']);

				if (is_array($paydates))
				{

					foreach ($paydates as $date)
					{

						$next = next($paydates);

						// paydates within 5 days of each other must be verified
						$verify = (($next !== FALSE) && ($next < strtotime('+5 days', $date)));
						if ($verify) break;

					}

				}

				// log the result for digestion later
				$result = ($verify) ? 'VERIFY' : 'VERIFIED';
				$blackbox->Log_Event('VERIFY_PAYDATES', $result);

			}
			else
			{
				$verify = NULL;
			}

			return $verify;
		}
		protected function Verify_Zip($blackbox, $data)
		{
			$zip = $data['home_zip'];
			$city = $data['home_city'];
			$state = $data['home_state'];
			
			$query = 'SELECT 
				count(*) as cnt
			FROM 
				zip_lookup
			WHERE
				zip_code = \''.mysql_real_escape_string($zip).'\'
			AND
				city =\''.mysql_real_escape_string($city).'\'
			AND
				state =\''.$state.'\'
			';
			$res = $blackbox->sql->Query($config->sql->db_info['db'],$query);
			if($row = $blackbox->sql->Fetch_Object_Row($res))
			{
				$cnt = $row->cnt;
			}
			if($cnt < 1)
			{
				$blackbox->Log_Event('zip_verify','VERIFY');
			}
			
		}
		
		protected function Run_Fraud($blackbox, BlackBox_Target_OldSchool $target, $data)
		{
			require_once(BFW_CODE_DIR.'OLP_Fraud.php');
			if($target->runFraud())
			{
				$fraud = new OLPFraud(BFW_MODE,$target->Name());
			
				$ecash_app = OLPFraud::buildECashAppFromArray($_SESSION['data'],$blackbox->application_id);
				
				$cnt = $fraud->runFraudRules($ecash_app);
				$status = ($cnt > 0) ? 'VERIFY' : 'PASS';
				$blackbox->Log_Event('FRAUD_CHECK',$status); 
			}
		}
		
	}
	class BlackBox_Verify_Rules_CLK extends BlackBox_Verify_Rules
	{
		public function Run(&$blackbox, BlackBox_Target_OldSchool &$target, &$data) 
		{
			parent::Run($blackbox, $target, $data);
			$this->verifyWorkPhoneType($blackbox);
			$this->Verify_Zip($blackbox, $data);
			$this->Run_Fraud($blackbox, $target, $data);
		}
	}

	/**
	 * Special Verify Rules for UFC - GForge #6696 [MJ]
	 */
	class BlackBox_Verify_Rules_UFC extends BlackBox_Verify_Rules
	{
		/**
		 * Run verify rules for UFC
		 *
		 * @param BlackBox_Config $blackbox
		 * @param BlackBox_Target $target
		 * @param array $data
		 */
		public function Run(&$blackbox, BlackBox_Target_OldSchool &$target, &$data) 
		{
			$valid = $this->sameWorkAndHomePhone($blackbox, $target, $data);
			
			// After further analysis, they want all BENEFITS income types to pass
			$benefits = $this->hasBenefitsIncome($blackbox, $data);
			
			// If we're still valid, check the DataX packet
			if ($valid && !$benefits)
			{
				$valid = $this->checkDataXReferrals($blackbox);
			}
			
			$this->Run_Fraud($blackbox, $target, $data);
			
			return $valid;
		}
		
		/**
		 * Fail if home and work are the same and income type is employment.
		 *
		 * @param BlackBox_Config $blackbox
		 * @param BlackBox_Target $target
		 * @param array $data
		 * @return bool valid
		 */
		protected function sameWorkAndHomePhone(&$blackbox, &$target, &$data)
		{
			$phone_home = isset($data['phone_home']) ? $data['phone_home'] : FALSE;
			$phone_work = isset($data['phone_work']) ? $data['phone_work'] : FALSE;
			$income_type = isset($data['income_type']) ? $data['income_type'] : FALSE;

			if (($blackbox->Mode() !== MODE_CONFIRMATION && $blackbox->Mode() !== MODE_ONLINE_CONFIRMATION) 
					&& ($phone_home && $phone_work && $income_type)
					&& (strcasecmp($phone_home, $phone_work) === 0)
					&& (strcasecmp($income_type, 'EMPLOYMENT') === 0))
			{
				$blackbox->Log_Event('VERIFY_SAME_WH',EVENT_FAIL);
				return FALSE;
			}
			else
			{
				$blackbox->Log_Event('VERIFY_SAME_WH',EVENT_PASS);
				return TRUE;
			}
		}
		
		/**
		 * Simply returns TRUE if the customer has an income type of benefits, FALSE otherwise.
		 *
		 * @param Blackbox_Config $blackbox the Blackbox config
		 * @param array           $data     the data to check
		 * @return bool
		 */
		protected function hasBenefitsIncome(BlackBox_Config_OldSchool $blackbox, $data)
		{
			if (isset($data['income_type']) && strcasecmp($data['income_type'], 'BENEFITS') == 0)
			{
				$blackbox->Log_Event('BENEFITS_CHECK', EVENT_PASS);
				return TRUE;
			}
			
			return FALSE;
		}
		
		/**
		 * Checks the DataX DecisionBuckets to see if we have any Referral Buckets.
		 * 
		 * For UFC, we need to check if they have any referral buckets to fail them. Returns TRUE by
		 * default to keep us valid. Returns FALSE if we find referral buckets.
		 *
		 * @param Blackbox $blackbox the Blackbox object
		 * @return bool
		 */
		protected function checkDataXReferrals(&$blackbox)
		{
			/**
			 * DataX is now going to return the same buckets they were before. So now we'll
			 * get a PASS with R# referrals, rather than a FAIL.
			 * 
			 * If we see one of these R# referrals, we'll need to do this check below. If it passes
			 * then we'll let them through, otherwise, we'll fail everything.
			 */
			if ($blackbox->Mode() == MODE_DEFAULT)
			{
				$config =& $blackbox->Config();
				
				$auth = new Authentication($config->sql, $config->database, $config->applog);
				$response = reset($auth->Get_Records($config->application_id, Authentication::DATAX_PERF));
				
				if ($response)
				{
					$response = $response['received_package'];
					$response = @simplexml_load_string($response);
					
					$buckets = $response->Response->Summary->DecisionBuckets->Bucket;
					
					foreach ($buckets as $bucket)
					{
						if (strstr($bucket, 'R'))
						{
							$blackbox->Log_Event('CHECK_DATAX_REFERRAL', EVENT_FAIL);
							return FALSE;
						}
					}
					
					$blackbox->Log_Event('CHECK_DATAX_REFERRAL', EVENT_PASS);
					return TRUE;
				}
			}
			
			return TRUE;
		}
	}
	
	/**
	 * Impact Verify Rules 
	 */
	class BlackBox_Verify_Rules_Impact extends BlackBox_Verify_Rules
	{
		public function Run(&$blackbox, BlackBox_Target_OldSchool &$target, &$data)
		{
			parent::Run($blackbox, $target, $data);
			$this->Same_Work_And_Home($blackbox, $data); //Impact still uses the old WH gforge #4703 [TP]
		}
	}
	
	
	class BlackBox_Verify_Rules_Agean extends BlackBox_Verify_Rules
	{

		public function Run(&$config, BlackBox_Target_OldSchool &$target, &$data)
		{

			// run our rules
			$this->Same_Work_And_Home($config, $data);
			//$this->Work_Phone_Type($config);
			//$this->Minimum_Income($config, $data);
			$this->Paydate_Proximity($config, $data);
			$this->Verify_Income_Type($config, $data['income_type']);

			// we always pass!
			return;

		}
		
		protected function Verify_Income_Type($config, $income_type)
		{
			if(strcasecmp($income_type, 'BENEFITS') === 0)
			{
				//Agean sends out an email for people with benefits and adds a loan action.
				Agean_Triggers::Log_Trigger($config->log, $config->bb_mode, 13);
				Agean_Triggers::Log_Trigger($config->log, $config->bb_mode, 14);
			}
		}
	}
?>
