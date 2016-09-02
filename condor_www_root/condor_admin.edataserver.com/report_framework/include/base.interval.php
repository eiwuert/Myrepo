<?php
	
	/**
	 * Designed only to be extended, this class provides basic functionality
	 * for classes that work with date ranges (i.e., Interval_Range and Interval_Report)
	 */
	class Base_Interval_Range
	{
		
		protected function Build_Range($base_date, $start_date, $end_date, $interval)
		{
			
			$end_date = $this->Transform_Date($this->end_date, $this->now);
			$start_date = $this->Transform_Date($this->start_date, $end_date);
			$base_date = $this->Transform_Date($this->base_date, $end_date);
			
		}
		
		protected function Build_Intervals($offsets, $now, $base_date, $start_date, $end_date, $interval)
		{
			
			reset($offsets);
			$intervals = array();
			
			// get the real dates (resolve strtotimes, etc.)
			$end_date = $this->Transform_Date($end_date, $now);
			$start_date = $this->Transform_Date($start_date, $end_date);
			$base_date = $this->Transform_Date($base_date, $end_date);
			
			while (list($key, $offset) = each($offsets))
			{
				
				$offset += $base_date;
				
				// make sure this falls within our interval (otherwise, we may end
				// up counting, say March 1st as February 29th, or something similar)
				if (($offset >= $start_date) && ($offset < $end_date))
				{
					$next = Move_Interval($offset, $interval, 1);
					$intervals[$key] = array($offset, $next);
				}
				
			}
			
			return $intervals;
			
		}
		
		protected function Transform_Date($date, $relative_to)
		{
			
			if (!is_numeric($date))
			{
				$date = Replace_Vars(Date_Vars($relative_to), $date);
				$date = strtotime($date, $relative_to);
			}
			
			return $date;
			
		}
		
	}
	
?>