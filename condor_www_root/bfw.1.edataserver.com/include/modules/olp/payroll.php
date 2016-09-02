<?php
	
	/**
		@publicsection
		@public
		@brief	Translates form data to a paydate model.
		
		These classes handle the translation of raw form data to
		a paydate model understood by the paydate calculators.
		They also provide a unified interface to both the old and
		new method of calculating paydates, and handle validation
		of form data.
		
		@version
			0.1.0 2005-04-05 - Andrew Minerd
	
	*/
	
	class Paydate_Model {
		
		private $model;
		private $model_data;
		private $frequency;
		private $ref_dates;
		private $user_generated;
		
		/**
			@publicsection
			@public
			@fn return array Build_From_Data($data)
			@brief
				
				Build the paydate model from raw form data. Returns
				TRUE if completed successfully, otherwise, returns
				an array of errors.
				
			@param $data array Raw form data
		*/
		public function Build_From_Data($data)
		{
			
			// hold data errors
			$errors = array();
			$ref_dates = array();

			$frequency = $this->Get_Data('frequency', $data);

			if ($frequency!==FALSE)
			{
				
				switch (strtoupper($frequency))
				{
					
					case 'WEEKLY':
						
						$week_day = $this->Get_Data('weekly_day', $data);
						
						if ($week_day)
						{
							$model = 'DW';
							$model_data = $this->Build_DW($week_day);
						}
						else
						{
							if (!$week_day) $errors[] = 'weekly_day';
						}
						
						break;
						
					case 'BI_WEEKLY':
						
						$week_day = $this->Get_Data('biweekly_day', $data);
						if($week_day == NULL)
						{
							$errors[] = 'biweekly_day';
						}
						$pay_date_1 = $this->Neuter_Date($this->Get_Data('biweekly_date', $data));
						
						$date_array = getdate($pay_date_1);
						
						/*
							Sanity check to see that the customer's last paydate is the same day they
							say they get paid on. So if they say they get paid on Friday every two weeks,
							the date they give us has to be a Friday. [BF]
						*/
						if($this->Week_Day($week_day) != $date_array['wday'])
						{
							/*
								The error generated here is correct if a little inaccurate, but
								if they already borked it by having Javascript off, they'll still
								have to put the right date anyway. For someone with Javascript off,
								the paydate widget is already going to be a pain to them, what's
								another error?
							*/
							$errors[] = 'biweekly_date';
						}
						else
						{
							if ($week_day && $pay_date_1)
							{
								$model = 'DWPD';
								$model_data = $this->Build_DW($week_day);
								$ref_dates = array($pay_date_1);
							}
							else
							{
								if (!$week_day) $errors[] = 'biweekly_day';
								if (!$pay_date_1) $errors[] = 'biweekly_date';
							}
						}
						
						break;
						
					case 'TWICE_MONTHLY':
						
						$type = $this->Get_Data('twicemonthly_type', $data);
						
						switch (strtoupper($type))
						{
							
							case 'DATE':
								
								$day_1 = $this->Get_Data('twicemonthly_date1', $data);
								$day_2 = $this->Get_Data('twicemonthly_date2', $data);
							
								//If day_1 is the 31st and day_2 is 'last day of the month', it will
								//pass an empty paydates array and not throw an error, so we need
								//to stop it here. [CB] 2006-02-01
								if ($day_1 >= $day_2 || ($day_1 == 31 && $day_2 == 32))
								{
									$errors[] = 'twicemonthly_order';
								}								
								else if ($day_1 && $day_2)
								{
									$model = 'DMDM';
									$model_data = $this->Build_DMDM($day_1, $day_2);
								}
								else
								{
									if (!$day_1) $errors[] = 'twicemonthly_date1';
									if (!$day_2) $errors[] = 'twicemonthly_date2';
								}
								
								break;
								
							case 'WEEK':
								
								list($week_1, $week_2) = explode('-', $this->Get_Data('twicemonthly_week', $data));
								$week_day = $this->Get_Data('twicemonthly_day', $data);
								
								if ($week_1 && $week_2 && $week_day)
								{
									$model = 'WWDW';
									$model_data = $this->Build_WWDW($week_1, $week_2, $week_day);
								}
								else
								{
									if ((!$week_1) || (!$week_2)) $errors[] = 'twicemonthly_week';
									if (!$week_day) $errors[] = 'twicemonthly_day';
								}
								
								break;
							default:
								$errors[] = 'twicemonthly_type';
								break;
						}
						
						break;
						
					case 'MONTHLY':
						
						$type = $this->Get_Data('monthly_type', $data);

						switch(strtoupper($type))
						{
							
							case 'DATE':
								
								$day_1 = $this->Get_Data('monthly_date', $data);
								
								if ($day_1)
								{
									$model = 'DM';
									$model_data = $this->Build_DM($day_1);
								}
								else
								{
									if (!$day_1) $errors[] = 'monthly_date';
								}
								
								break;
								
							case 'DAY':
								
								$week_1 = $this->Get_Data('monthly_week', $data);
								$week_day = $this->Get_Data('monthly_day', $data);
								
								if ($week_1 && $week_day)
								{
									$model = 'WDW';
									$model_data = $this->Build_WDW($week_1, $week_day);
								}
								else
								{
									if (!$week_1) $errors[] = 'monthly_week';
									if (!$week_day) $errors[] = 'monthly_day';
								}
								
								break;
								
							case 'AFTER':
								
								$day_1 = $this->Get_Data('monthly_after_date', $data);
								$week_day = $this->Get_Data('monthly_after_day', $data);
								
								if ($day_1 && $week_day)
								{
									$model = 'DWDM';
									$model_data = $this->Build_DWDM($day_1, $week_day);
								}
								else
								{
									if (!$day_1) $errors[] = 'monthly_after_date';
									if (!$week_day) $errors[] = 'monthly_after_day';
								}
								
								break;
							default:
								$errors[] = 'monthly_type';
								
						}
						
						break;
						
					default:
						$errors[] = 'frequency';
						break;
						
				}
				
			} else {
				
				if (!$frequency) $errors[] = 'frequency';
				
			}
			
			if ($model && $model_data)
			{
				$this->model = $model;
				$this->model_data = $model_data;
				$this->ref_dates = $ref_dates;
				$this->frequency = $frequency;
				$this->user_generated = TRUE;
			}
			
			return ($model) ? TRUE : $errors;
			
		}
		
		public function Import_From_Record($row, $prefix = '', $map = NULL)
		{
			
			if (!is_array($map))
			{
				
				$map = array
				(
					'DW'=>array('day_string_one'=>'day_of_week'),
					'DWPD'=>array('day_string_one'=>'day_of_week', 'next_pay_date'=>'next_paydate'),
					'DMDM'=>array('day_int_one'=>'day_of_month_1', 'day_int_two'=>'day_of_month_2'),
					'WWDW'=>array('week_one'=>'week_1', 'week_two'=>'week_2', 'day_string_one'=>'day_of_week'),
					'DM'=>array('day_int_one'=>'day_of_month_1'),
					'WDW'=>array('week_one'=>'week_1', 'day_string_one'=>'day_of_week'),
					'DWDM'=>array('day_string_one'=>'day_of_week', 'day_int_one'=>'day_of_month_1')
				);
				
			}
			
			// will hold information
			$model_data = array();
			$ref_dates = array();
			
			// get our model name
			$model = strtoupper($row['paydate_model']);
			
			if (isset($map[$model]))
			{
				
				// get our transformation array
				$temp = $map[$model];
				
				foreach ($temp as $data=>$field)
				{
					
					if ($prefix) $field = $prefix.$field;
					
					if (isset($row[$field]))
					{
						
						// transform fields
						if ($field == "{$prefix}day_of_week")
						{
							$model_data[$data] =  $row[$field];
							$model_data['day_of_week'] = $this->Week_Day($row[$field]);
						}
						elseif ($field == "{$prefix}next_paydate")
						{
							$ref_dates[] = strtotime($row[$field]);
						}
						else
						{
							$model_data[$data] = $row[$field];
						}
						
					}
					
				}
				
			}
			
			$this->model = $model;
			$this->model_data = $model_data;
			$this->ref_dates = $ref_dates;
			
			return $model_data;
			
		}
		
		public function Import($model_data)
		{
			
			$model_name = $model_data['model_name'];
			
			$fields = array('day_string_one', 'day_int_one', 'day_int_two', 'week_one', 'week_two');
			
			$data = array();
			$ref_dates = array();
			
			if (isset($model_data['next_pay_date']))
			{
				$ref_dates[] = strtotime($model_data['next_pay_date']);
			}
			
			foreach ($fields as $name)
			{
				
				if (isset($model_data[$name]))
				{
					$data[$name] = $model_data[$name];
				}
				
			}
			
			//set local copies
			$this->model = $model_name;
			$this->frequency = $model_data['frequency'];
			$this->model_data = $data;
			$this->ref_dates = $ref_dates;
			$this->user_generated = FALSE;
			
			return($model_name);
			
		}
		
		/**
			@publicsection
			@public
			@fn return array Model_Data()
			@brief
				
				Return the model in a fashion understood by
				the paydate calculator.
		*/
		public function Model_Data()
		{
			
			if ($this->model_data)
			{
				
				// translate to the old names
				$export = $this->model_data;
				$ref_dates = $this->ref_dates;
				
				$export['model_name'] = $this->model;
				$export['user_generated'] = $this->user_generated;
				$export['income_frequency'] = $this->frequency;
				
				// import pay dates
				if (count($ref_dates))
				{
					$export['next_pay_date'] = date('Y-m-d', reset($ref_dates));
				}
				
			}
			else
			{
				$export = FALSE;
			}
			
			return($export);
			
		}
		
		public function Export()
		{
			
			$model_data = $this->model_data;
			$data = array();
			
			$data['frequency'] = $this->frequency;
			
			switch(strtoupper($this->model))
			{
				
				case "DW":
					$data['weekly_day'] = $this->Week_Day($model_data['day_string_one'], TRUE);
					break;
					
				case "DWPD":
					
					$next_pay_date = array_shift($this->ref_dates);
					
					$data['biweekly_day'] = $this->Week_Day($model_data['day_string_one'], TRUE);
					$data['biweekly_date'] = date('m/d/Y', $next_pay_date);
					break;
					
				case 'DMDM':
					$data['twicemonthly_type'] = 'date';
					$data['twicemonthly_date1'] = $model_data['day_int_one'];
					$data['twicemonthly_date1'] = $model_data['day_int_two'];
					break;
					
				case 'WWDW':
					$data['twicemonthly_type'] = 'week';
					$data['twicemonthly_week'] = $model_data['week_one'].'-'.$model_data['week_two'];
					$data['twicemonthly_day'] = $this->Week_Day($model_data['day_string_one'], TRUE);
					break;
					
				case 'DM':
					$data['monthly_type'] = 'date';
					$data['monthly_date'] = $model_data['day_int_one'];
					break;
					
				case 'WDW':
					$data['monthly_type'] = 'day';
					$data['monthly_week'] = $model_data['week_one'];
					$data['monthly_day'] = $this->Week_Day($model_data['day_string_one'], TRUE);
					break;
					
				case 'DWDM':
					$data['monthly_type'] = 'after';
					$data['monthly_after_day'] = $this->Week_Day($model_data['day_string_one'], TRUE);
					$data['monthly_after_date'] = $model_data['day_int_one'];
					break;
				
			}
			
			return($data);
			
		}
		
		/**
			@publicsection
			@public
			@fn return array Pay_Dates()
			@brief
				
				Return an array of future pay dates
				
			@param $holidays array Array of holidays to avoid
			@param $count int Number of pay dates to generate
		*/
		public function Pay_Dates($holidays, $count = 4)
		{
			
			// calculate paydates
			$calc = new Pay_Date_Calc_1($holidays);
			$pay_dates = $calc->Calculate_Payday($this->Model(), date('y-m-d'), $this->Model_Data(), $count);
			
			return ($pay_dates);
			
		}
		
		/**
			@publicsection
			@public
			@fn return string Model_Data()
			@brief
				
				Return the model name
		*/
		public function Model()
		{
			
			return($this->model);
			
		}
		
		/**
			@privatesection
			@private
			@fn return string Get_Data($data)
			@brief
				
				Provide "friendly" access to the data array.
				
			@param $key string Field to return
			@param $data array Raw form data
		*/
		private function Get_Data($key, $data)
		{
			
			$val = (key_exists($key, $data)) ? $data[$key] : FALSE;
			return($val);
			
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_DW($pay_date_1)
			@brief
				
				Build the model data for DW (weekly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 string Day of the week
		*/
		private function Build_DW($pay_date_1)
		{
			
			// accept it as a date, or by name
			if (!is_string($pay_date_1)) $week_day = date('l', $pay_date_1);
			else $week_day = $pay_date_1;
			
			$model_data = array();
			$model_data['day_string_one'] = $week_day;
			$model_data['day_of_week'] = $this->Week_Day($week_day);
			
			return($model_data);
			
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_DWPD($pay_date_1)
			@brief
				
				Build the model data for DWPD (bi-weekly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 string Day of the week
		*/
		private function Build_DWPD($pay_date_1)
		{
			
			// accept it as a date, or by name
			if (!is_string($week_day)) $week_day = date('l', $pay_date_1);
			
			$model_data = array();
			$model_data['day_string_one'] = date('l', $week_day);
			
			return($model_data);
			
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_DMDM($pay_date_1, $pay_date_2)
			@brief
				
				Build the model data for DMDM (twice monthly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 int Day of the month
			@param $pay_date_2 timestamp Reference pay date
			@param $pay_date_2 int Day of the month
		*/
		private function Build_DMDM($pay_date_1, $pay_date_2)
		{
			
			// get the days of the week
			$day_1 = ($pay_date_1 < 32) ? $pay_date_1 : date('j', $pay_date_1);
			$day_2 = ($pay_date_2 < 32) ? $pay_date_2 : date('j', $pay_date_2);
			
			$model_data = array();
			$model_data['day_int_one'] = ($day_1 < $day_2) ? $day_1 : $day_2;
			$model_data['day_int_two'] = ($day_2 > $day_1) ? $day_2 : $day_1;
			
			return($model_data);
			
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_WWDW($pay_date_1, $pay_date_2, $week_day)
			@brief
				
				Build the model data for WWDW (twice monthly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 int Week of the month
			@param $pay_date_2 timestamp Reference pay date
			@param $pay_date_2 int Week of the month
			@param optional $week_day string Day of the week
		*/
		private function Build_WWDW($pay_date_1, $pay_date_2, $week_day = '')
		{
			
			// get the week numbers
			$week_1 = ($pay_date_1 < 7) ? $pay_date_1 : ceil(date('j', $pay_date_1) / 7);
			$week_2 = ($pay_date_2 < 7) ? $pay_date_2 : ceil(date('j', $pay_date_2) / 7);
			
			// get the week day
			if (!$week_day) $week_day = date('l', $pay_date_1);
			
			$model_data = array();
			$model_data['week_one'] = ($week_1 < $week_2) ? $week_1 : $week_2;
			$model_data['week_two'] = ($week_2 > $week_1) ? $week_2 : $week_1;
			$model_data['day_string_one'] = $week_day;
			
			return($model_data);
			
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_DM($pay_date_1)
			@brief
				
				Build the model data for DM (monthly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 int Day of the month
		*/
		private function Build_DM($pay_date_1)
		{
			$day = ($pay_date_1 <= 32) ? $pay_date_1 : date('n', $pay_date_1);
			
			$model_data = array();
			$model_data['day_int_one'] = $day;
			
			return($model_data);
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_WDW($pay_date_1, $week_day)
			@brief
				
				Build the model data for WDW (monthly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_1 int Week of the month
			@param optional $week_day string Week day
		*/
		private function Build_WDW($pay_date_1, $week_day = '')
		{
			if ($week_day) $week = $pay_date_1;
			else { $week = $this->Week($pay_date_1); $week_day = date('l', $pay_date_1); }
			
			$model_data = array();
			$model_data['week_one'] = $week;
			$model_data['day_string_one'] = $week_day;
			
			return($model_data);
		}
		
		/**
			@privatesection
			@private
			@fn return array Build_DWDM($pay_date_1, $pay_date_2)
			@brief
				
				Build the model data for DWDM (monthly)
				
			@param $pay_date_1 timestamp Reference pay date
			@param $pay_date_2 timestamp Reference pay date
			@param $pay_date_1 string Week day
			@param $pay_date_2 int Day of the month the paydates occur AFTER
		*/
		private function Build_DWDM($pay_date_1, $pay_date_2)
		{
			
			if (is_string($pay_date_1))
			{
				$day = $pay_date_1;
				$week_day = $pay_date_2;
			}
			else
			{
				
				$day = date('n', $pay_date_1);
				$week_day = date('l', $pay_date_1);
				
				while (($day > date('n', $pay_date_1)) && ($day > date('n', $pay_date_2))) $day--;
			}
			
			$model_data = array();
			$model_data['day_string_one'] = $week_day;
			$model_data['day_int_one'] = $day;
			
			return($model_data);
		}
		
		// Convert between a string and integer
		// representation of a week day
		private function Week_Day($weekday, $abbreviation = FALSE) {

			$days = array('SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY');
			$abbrev = array('SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT');
			
			if (is_numeric($weekday)) {
				
				// cast to integer, just in case
				$weekday = (integer)$weekday;
				
				if (($weekday >= 0) && ($weekday <= 5))
				{
					
					if ($abbreviation)
					{
						$day = $abbrev[$weekday];
					}
					else
					{
						$day = $days[$weekday];
					}
					
				}
				
			} elseif (is_string($weekday)) {

				if ($abbreviation)
				{
					
					// get the abbreviation
					$key = array_search(strtoupper($weekday), $days);
					if ($key!==FALSE) $day = $abbrev[$key];
					
				}
				else
				{
					
					switch (strtoupper($weekday)) {
						case 'SUNDAY':
						case 'SUN': { $day = 0; break; }
						case 'MONDAY':
						case 'MON': { $day = 1; break; }
						case 'TUESDAY':
						case 'TUE': { $day = 2; break; }
						case 'WEDNESDAY':
						case 'WED': { $day = 3; break; }
						case 'THURSDAY':
						case 'THU': { $day = 4; break; }
						case 'FRIDAY':
						case 'FRI': { $day = 5; break; }
						case 'SATURDAY':
						case 'SAT': { $day = 6; break; }
					}
					
				}
				
			}
			
			if (!$day) { $day = FALSE; }
			return($day);
			
		}
		
		/**
			@privatesection
			@private
			@fn return timestamp Neuter_Date($date = NULL)
			@brief
				
				Strip the time from a timestamp, and return it. If
				no date is given, the current date is used.
				
			@param optional $date timestamp Date to neuter
		*/
		private function Neuter_Date($date = NULL)
		{
			if($date === false) return false;
			
			if (is_null($date)) $date = time();
			else $date = strtotime($date);
			
			if ($date && ($date!='-1')) $date = strtotime(date('y-m-d', $date));
			else $date = FALSE;
			
			return($date);
		}
		
	}
	
	class Old_Paydate_Model
	{
		
		private $model = 'OLD';
		private $frequency;
		private $pay_date_1;
		private $pay_date_2;
		private $pay_date_3;
		private $pay_date_4;
		private $ent_date_3;
		private $ent_date_4;
		private $ent = FALSE;
		
		/**
			@publicsection
			@public
			@fn return array Build_From_Data($data)
			@brief
				
				Build the paydate model from raw form data. Returns
				TRUE if completed successfully, otherwise, returns
				an array of errors.
				
			@param $data array Raw form data
		*/
		public function Build_From_Data($data)
		{
			
			$ref_dates = array();
			
			// get up to four paydates
			$pay_date_1 = strtotime($this->Get_Data('pay_date1', $data));
			$pay_date_2 = strtotime($this->Get_Data('pay_date2', $data));
			$pay_date_3 = strtotime($this->Get_Data('pay_date3', $data));
			$pay_date_4 = strtotime($this->Get_Data('pay_date4', $data));
			
			// enterprise pay dates
			$ent_date_3 = strtotime($this->Get_Data('ent_pay_date3', $data));
			$ent_date_4 = strtotime($this->Get_Data('ent_pay_date4', $data));
			
			if ($ent_date_3 && $ent_date_4)
			{
				
				$this->ent = TRUE;
				
				if ($ent_date_3>$pay_date_2) $errors[] = 'pay_date3_before_date2';
				if ($ent_date_3>$pay_date_2) $errors[] = 'pay_date4_before_date3';
				
				if (!count($errors))
				{
					// store locally
					$this->pay_date_1 = $pay_date_1;
					$this->pay_date_2 = $pay_date_2;
					$this->pay_date_3 = $ent_date_3;
					$this->pay_date_4 = $ent_date_4;
				}
				
			}
			else
			{
				// store locally
				$this->pay_date_1 = $pay_date_1;
				$this->pay_date_2 = $pay_date_2;
				$this->pay_date_3 = $pay_date_3;
				$this->pay_date_4 = $pay_date_4;
			}
			
			$this->frequency = $this->Get_Data('income_frequency');
			
			return (count($errors)) ? $errors : TRUE;
			
		}
		
		/**
			@privatesection
			@private
			@fn return string Get_Data($data)
			@brief
				
				Provide "friendly" access to the data array.
				
			@param $key string Field to return
			@param $data array Raw form data
		*/
		private function Get_Data($key, $data)
		{
			
			$val = (key_exists($key, $data)) ? $data[$key] : FALSE;
			return($val);
			
		}
		
		/**
			@publicsection
			@public
			@fn return array Model_Data()
			@brief
				
				Return the model in a fashion understood by
				the paydate calculator.
		*/
		public function Model_Data()
		{
			
			$export = array();
			
			$export['pay_date1'] = date('Y-m-d', $this->pay_date_1);
			$export['pay_date2'] = date('Y-m-d', $this->pay_date_2);
						
			if ($this->ent)
			{
				$export['pay_date3'] = date('Y-m-d', $this->ent_date_3);
				$export['pay_date4'] = date('Y-m-d', $this->ent_date_4);
			}
			elseif ($this->pay_date_3 && $this->pay_date_4)
			{
				$export['pay_date3'] = date('Y-m-d', $this->pay_date_3);
				$export['pay_date4'] = date('Y-m-d', $this->pay_date_4);
			}
			
			$export['income_frequency'] = $this->frequency;
			
			return($export);
			
		}
		
		/**
			@publicsection
			@public
			@fn return array Pay_Dates()
			@brief
				
				Return an array of future pay dates
				
			@param $holidays array Array of holidays to avoid
			@param $count int Number of pay dates to generate
		*/
		public function Pay_Dates($holidays, $count = 0)
		{
			
			$errors = array();
			
			$validation = new Pay_Date_Validation($this->Model_Data(), $holidays);
			$result = $validation->Validate_Paydates();
			
			if ($result)
			{
				
				$pay_dates = $result['pay_dates'];
				
				if ($ent)
				{
					
					$generated_pd3 = strtotime($pay_dates['pay_date3']);
					$generated_pd4 = strtotime($pay_dates['pay_date4']);
					
					if ($generated_pd3 != $this->ent_date_3)
					{
						$_SESSION['data']['invalid_paydates'] = TRUE;
						$_SESSION['data']['updates']->paydate_3->new = date('Y-m-d', $this->ent_date_3);
						$_SESSION['data']['updates']->paydate_3->old = date('Y-m-d', strtotime($pay_dates['pay_date3']));
						$pay_dates['pay_date3'] = date('Y-m-d', $this->ent_date_3);
					}
					
					if ($generated_pd4 != $this->ent_date_4)
					{
						$_SESSION['data']['invalid_paydates'] = TRUE;
						$_SESSION['data']['updates']->paydate_4->new = date('Y-m-d', $this->ent_date_4);
						$_SESSION['data']['updates']->paydate_4->old = date('Y-m-d', strtotime($pay_dates['pay_date4']));
						$pay_dates['pay_date4'] = date('Y-m-d', $this->ent_date_4);
					}
					
				}
				
			}
			else
			{
				$errors = $result['errors'];
			}
			
			return (count($errors)) ? array('errors'=>$errors) : $pay_dates;
			
		}
		
		private function Correct_Day_Of_Week()
		{
			
		}
		
	}
	
?>
