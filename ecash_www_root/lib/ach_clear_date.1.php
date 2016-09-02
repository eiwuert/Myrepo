<?

## Calculate the clear date of an ach transaction.  The returned date will be a 
##  unix timestamp.  The transaction is not clear until the end of the business
##  day on the specified clear date.
##
## Arguements:
## 		$transaction_date => Unix timestamp of the date the transaction was placed
##
## 		$cut_off_time => This is the cut off time specified by the ach processor.
##                     Transactions placed before this time are generally processed
##                     on that day, transactions placed after this time are processed
##                     on the following banking business day.  If nothing is passed
##                     2:00pm will be used as the default.
##
## 		$days_to_hold => This is the number of business days you want to wait before a
##                     transaction is cleared. This is uaually 5 business days from
##                     the date the transaction went into the ACH network.
##                     Transactions placed after the $cut_off_time begin their hold
##                     on the following banking business day. 
##
## Example Use: 
## 			$txn_date = mktime();
## 			$clear_date = Ach_Clear_Date::Get_Clear_Date($txn_date)
##
## Example Dates:
## 			Transaction Date: September 12th, 2005 10:45am
## 			Clear Date: September 19th, 2005
##
## 			Transaction Date: September 12th, 2005 9:45pm
## 			Clear Date: September 20th, 2005
##
## 			Transaction Date: September 30th, 2005 10:45am
## 			Clear Date: October 7th, 2005
##
## 			Transaction Date: September 30th, 2005 9:45pm
## 			Clear Date: October 11th, 2005
## 			Note:  The transaction is not processed on the 30th, because it was after
##             the cut_off_date.  It is not processed on Oct 1st or 2nd because
##             they are the weekend.  It is processed on October 3rd. This makes
##             the 4th, 5th, 6th, and 7th the first 4 business days of the hold.
##             Monday the 10th rolls around and it is a holiday, so we skip it, 
##             making the 5th business day the 11th.  This transaction will clear
##             at the end of the business day on the 11th.
##
##
## Matt Piper
## matt.piper@sellingsource.com
## 09/21/05
##
## I am no ACH expert, to my knowledge this is correct.  If you find a problem, 
## please fix it and let me know.
##
## Modified 10/12/05 (Matt Piper) ::
##   Like I said, Im no ach expert.  There was a problem in the logic where
##   occasionally the date would be off by a date.  The old code checked if the 
##   date ended up on a holiday it added a day, not taking into account holidays
##   that happen in the $days_to_hold period.  But all is well and it is fixed now..
##   or atleast I think.
##
## Modified 10/20/05 (Matt Piper) ::
##   Im not sure why the original version made you pass in actual days for the
##   $days to hold, instead of banking days.  Everything is done by banking days
##   so it only makes sense to switch it to accept business days.  So thats what
##   I did, $days_to_hold is now business days.
##
## Modified 02/08/06 (Matt Piper) ::
##   Added a new function Get_Business_Days_Count that you can pass in two
##   dates and it will return the number of business days between those days.
##   This takes into account weekends and holidays.
##
## Modified 05/31/06 (Matt Piper) ::
##   Added a new function, Get_Previous_Banking_Date that you can pass in a
##   date and optional number of banking days, and the function will return
##   the previous banking day.  By default, it will return the last banking
##   day, but if you want to know what day 5 business days ago, you can pass
##   the optional num_days arguement.
##
## Modified 06/16/06 (Matt Piper) ::
##   Added a new function, Get_Next_Banking_Date that you can pass in a
##   date and optional number of banking days, and the function will return
##   the next banking day.  By default, it will return the next banking
##   day, but if you want to know what day 5 business days in the future will
##   be, you can pass the optional num_days arguement.
##
## Modified 11/13/06 (Matt Piper) ::
##   Ran into a case where $days_to_hold was passed in as '0', but it returns
##   the following day, mainly because of the cut in time not being passed.
##   If $days_to_hold == 0, there is no need to do anything, just return the
##   date.

class Ach_Clear_Date {

	function Get_Clear_Date ($transaction_date, $cut_off_time='2:00pm', $days_to_hold='5') {

		$holiday_array = Ach_Clear_Date::Get_Holiday_Array();
		//foreach($holiday_array as $holiday) {
		//	echo date("Y-m-d", $holiday) . "<BR>";
		//}

		## Set the initial value for the clear date.  This is our base line number, so 
		##   the transaction date becomes the start for figuring out when the
		##   transaction will clear.
		$clear_date = strtotime( "00:00:00", $transaction_date );

		## If $days_to_hold == '0' we dont need to do anything else.
		if( $days_to_hold == '0' ) {
			return $clear_date;
		}

		## Figure out the cutoff time for the transaction.
		## Cutoff time must be in the fomrat h:mma, maybe not the best way to require
		##   it passed in, but for now it works for my purpose.
		$cut_off_date = strtotime($cut_off_time, $transaction_date);

		## If the transaction was placed after the cutoff, it will not be processed
		##   until the next day, so add an extra day to our clear date.
		if($transaction_date > $cut_off_date) {
			$clear_date = strtotime("+1 days", $clear_date);
		}
		
		## Figure out the date the transaction will be processed.  At this point, the
		##  clear date will either be the day of the transaction, or the following day
		##  if it was placed after the cut off.  If the current clear date is on a
		##  weekend or or on a holiday we need to add a day until we reach the first
		##  business day after the transaction was initiated.  This is the day the
		##  transaction will go into the ach network.  This is where we will start 
		##  adding days until we reach the $days_to_hold.
		while (Ach_Clear_Date::Is_Weekend ($clear_date) || Ach_Clear_Date::Is_Holiday ($clear_date, $holiday_array)) {
			$clear_date = strtotime("+1 day", $clear_date);
		}

		## Once we know what day the transaction will be sent into the ach network, 
		##   we can calculate from there the days it should be held.
		##   This is generally 5 business days.
		for($i=0; $i<$days_to_hold; $i++) {
			
			## Figure out the next business day. By default we make it one day, but in
			##   the event that $clear_date + $days_to_hold is a weekend or holidy,
			##   we need to continue adding a day until we reach the next business day.
			$days_to_add = 1;
			while (Ach_Clear_Date::Is_Weekend ( strtotime("+" . $days_to_add . " days", $clear_date) ) || Ach_Clear_Date::Is_Holiday ( strtotime("+" . $days_to_add . " days", $clear_date), $holiday_array )) {
				$days_to_add++;
			}
			$clear_date = strtotime("+" . $days_to_add . " days", $clear_date);
			
		}
		
		## At this point, we have calculated the clear date based on when it will make
		##   it into the ach network, plus the $days_to_hold.  If this clear date
		##   falls on a weekend or holiday, adjust it one day at a time until a valid
		##   date is reached.
		while (Ach_Clear_Date::Is_Weekend ($clear_date) || Ach_Clear_Date::Is_Holiday ($clear_date, $holiday_array)) {
			$clear_date = strtotime("+1 day", $clear_date);
		}
		
		## We found the clear date, return it.
		return $clear_date;
	}
	
	
	function Get_Business_Days_Count ($transaction_date_start, $transaction_date_end, $holidays) {

		$holiday_array = $holidays!="" ? $holidays : Ach_Clear_Date::Get_Holiday_Array();
		
		$transaction_date_current = $transaction_date_start;
		$day_count = 0;
		while( $transaction_date_current < $transaction_date_end ) {
			$transaction_date_current = strtotime("+1 day", $transaction_date_current);
			while (Ach_Clear_Date::Is_Weekend ($transaction_date_current) || Ach_Clear_Date::Is_Holiday ($transaction_date_current, $holiday_array)) {
				$transaction_date_current = strtotime("+1 day", $transaction_date_current);
			}
			$day_count++;
		}

		## We found the clear date, return it.
		return $day_count;
	}
	
	
	function Get_Previous_Banking_Date($start_date, $num_days=1) {
		$holiday_array = Ach_Clear_Date::Get_Holiday_Array();
		$previous_date = strtotime( $start_date );
		for($i=0; $i<$num_days; $i++) {
			$days_to_subtract = 1;
			while (Ach_Clear_Date::Is_Weekend ( strtotime("-" . $days_to_subtract . " days", $previous_date) ) || Ach_Clear_Date::Is_Holiday ( strtotime("-" . $days_to_subtract . " days", $previous_date), $holiday_array )) {
				$days_to_subtract++;
			}
			$previous_date = strtotime("-" . $days_to_subtract . " days", $previous_date);
		}
		return $previous_date;
	}
	
	
	function Get_Next_Banking_Date($start_date, $num_days=1) {
		$holiday_array = Ach_Clear_Date::Get_Holiday_Array();
		$next_date = strtotime( $start_date );
		for($i=0; $i<$num_days; $i++) {
			$days_to_add = 1;
			while (Ach_Clear_Date::Is_Weekend ( strtotime("+" . $days_to_add . " days", $next_date) ) || Ach_Clear_Date::Is_Holiday ( strtotime("+" . $days_to_add . " days", $next_date), $holiday_array )) {
				$days_to_add++;
			}
			$next_date = strtotime("+" . $days_to_add . " days", $next_date);
		}
		return $next_date;
	}
	
	
	function Is_Holiday($date, $holiday_array){
		if (in_array ($date, $holiday_array)){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	function Is_Weekend($date) {
		if (date ("w", $date) == 0 || date ("w", $date) == 6) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	function Get_Holiday_Array() {
		include_once("/virtualhosts/lib/bank_holidays.2.php");
		$bank_holidays = new Bank_Holidays('2005', (date("Y")+1));
		$holiday_array = $bank_holidays->Get_Holidays();
		return $holiday_array;
	}
	
}

?>