<?PHP

	class Billing_Profile_1
	{
		// Required vars
		var $sql;
		var $database_name;
		
		// Default vars
		var $min_cycle;
		var $max_cycle;
		var $holiday_object;
		
		/*
		$sql = "SELECT field FROM table WHERE why";
		$query = $this->sql->cluster1->Query($database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($query);
		*/


		/*!
		Name: Billing_Profile_1
		Return Value: TRUE
		Purpose: Class Constructor
		Passed Values:
			sql_object->instance of mysql3 class
			database_name->name of the database to use

		Comments:
			Sets constants, and build the holiday object
		*/

		function Billing_Profile_1($sql, $database_name)
		{
			$this->sql = $sql;
			$this->database_name = $database_name;
			
			$this->min_cycle = "25";
			$this->max_cycle = "60";

			$this->Get_Holidays();

			return TRUE;
		}
		
		/*!
		Name: Add_Days
		Return Value: $date
		Purpose: To add days to date
		Passed Values:
			$date: start date (Y-m-d)
			$days: days to add

		Comments:
			Will return a valid date
		*/

		function Add_Days($date, $days)
		{

			$date = explode("-", $date);
			$date = mktime (0,0,0,$date[1],$date[2],$date[0])  ;
			$date = strtotime("$days day", $date);

			return date('Y-m-d', $date);
		}

		/*!
		Name: Is_Date
		Return Value: Boolean
		Purpose:  To check date
		Passed Values:
			$date: (Y-m-d)

		Comments:
			Verify date validity
		*/

		function Is_Date($date)
		{
			$date = explode("-", $date);

			return  checkdate($date[1],$date[2],$date[0]);
		}

		/*!
		Name: Add_Direct_Months
		Return Value: $date
		Purpose: Move to next month
		Passed Values:
			$date: (Y-m-d)

		Comments:
			Return date may not be a valid date.

		*/

		function Add_Direct_Months($date)
		{
			$date = explode("-", $date);

			if ($date[1] == 12)
			{
				$date[1] = 1;  $date[0]++;
			}
			else
			{
			      $date[1]++;
			}

			$date = "$date[0]-$date[1]-$date[2]";

			return  $date;
		}

		/*!
		Name: Billing_Cycle
		Return Value: date
		Purpose: To add days to date
		Passed Values:
			$date: date
			$inc: days to add

		Comments:
			May not return a valid date

		*/

		function Add_Direct_Day($date,$inc)
		{
			$date = explode("-", $date);
			 $date[2] += $inc;
			 $date = "$date[0]-$date[1]-$date[2]";
			 
			 return  $date;
		}

		/*!
		Name: Set_Direct_Day
		Return Value: date
		Purpose: To set day in date
		Passed Values:
			$date , day to set

		Comments:
			May not  return a valid date
		*/

		function Set_Direct_Day($date,$payday)
		{
			$date = explode("-", $date);
			$date[2] = $payday;
			$date = "$date[0]-$date[1]-$date[2]";

			return  $date;
		}
			
		/*!
		Name: Date_Info
		Return Value: date
		Purpose: To get date info
		Passed Values:
			$date: the date in questions
			$info: year | day | month

		Comments:
		*/

		 function Date_Info ($date,$info)
		 {
		 	$date = explode("-", $date);

			switch ($info)
			{
				case "year" :
				$date = $date[0];
				break;
				
				case "day" :
				$date = $date[2];
				break;

				case "month":
				$date = $date[1];
				break;
			}

			return  $date;
		}

		/*!
		Name: Billing_Cycle
		Return Value: number of days
		Purpose: To find  difference between two dates in days
		Passed Values:
			$date1:
			$date2:

		Comments:
		*/
		
		function  Date_Diff($date1,$date2)
		{
			$date1 = explode("-", $date1);
			$date2 = explode("-", $date2);
			
			$date1 = mktime (0,0,0,$date1[1],$date1[2],$date1[0])  ;
			$date2 = mktime (0,0,0,$date2[1],$date2[2],$date2[0])  ;
  
			return ($date1 - $date2)/(60*60*24);
		}

		/*!
		Name: Adjust Date
		Return Value: date
		Purpose: To adjust date (example: feb 30 will be adjusted as feb28 if not leep year)
		Passed Values:
			$date

		Comments:
			will return a valid date
		*/
		

		function Adjust_Date($date)
		{
			while(!$this->Is_Date($date))
			{
				$date = $this->Add_Direct_Day($date, -1);
			}

			$date = $this->Adjust_Holiday($date);
			
			return $date;
		}

		/*!
		Name: Adjust_Holiday
		Return Value: date
		Purpose: Moves date fwd if the given date is holiday or week-end
		Passed Values:
			$date

		Comments:
			will return a valid date
		*/
			
		function Adjust_Holiday($date)
		{
			while ($this->Is_Holiday($date))
			{
				$date = $this->Add_Days($date, 1);
			}
			
			while ($this->Is_Weekend($date))
			{
				$date = $this->Add_Days($date, 1);
			}
			
			return $date;
		}

		/*!
		Name: Is_Weekend
		Return Value: Boolean
		Purpose: To check for week-end
		Passed Values:
			$date:

			Comments:
				if the day of the week is Saturday(6) or Sunday(0)
		*/

		function Is_Weekend($date)
		{
			$date = explode("-", $date);
			$day = date("w", mktime(0,0,0,$date[1],$date[2],$date[0]));

			if($day == 0 || $day == 6)
			{
				return TRUE;
			}
			
			return FALSE;

		}
			
		/*!
		Name: Is_Holiday
		Return Value: Boolean
		Purpose: To check for holiday or week-end
		Passed Values:
			$date:

			Comments:
		*/

		function Is_Holiday($date)
		{
			//Is the passed date a holiday
			foreach ($this->holiday_object as $obj=>$event_date)
			{
				if($date == $event_date)
				{
					return TRUE;
				}
			}

			return FALSE;
		}

		/*!
		Name: Get_Holidays
		Return Value: Boolean
		Purpose: To build an object of holidays (holiday_object->date)
		Passed Values:
			
		Comments:
			will set the holiday_object var as holiday_object->date
		*/

		function Get_Holidays()
		{
			$sql = "select * from holidays";
			$query = $this->sql->cluster1->Query($this->database_name,$sql, Debug_1::Trace_Code (__FILE__, __LINE__));
   			Error_2::Error_Test ($query);

			$n = 0;
			while (FALSE !== ($result = $this->sql->cluster1->Fetch_Object_Row ($query)))
			{
				$this->holiday_object->{$n} = $result->date;
				$n++;
			}
			return TRUE;
		}
	}
?>