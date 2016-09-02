<?php
	
	class Average_Interval_Range extends Transformation_Source
	{
		
		protected $start_date;
		protected $end_date;
		protected $major_interval;
		protected $minor_interval;
		
		protected $precision;
		
		public function __construct($start_date, $end_date, $major_interval, $minor_interval, $source)
		{
			
			$this->start_date = $start_date;
			$this->end_date = $end_date;
			$this->major_interval = $major_interval;
			$this->minor_interval = $minor_interval;
			$this->source = $source;
			
			return;
			
		}
		
		public function Precision($precision = NULL)
		{
			
			if (is_numeric($precision) && ($precision >= 0)) $this->precision = $precision;
			return $this->precision;
			
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			// make sure Prepare() has been called
			if ($this->Check_Prepared())
			{
				
				$start = $this->Translate_Date($this->start_date);
				$end = $this->Translate_Date($this->end_date);
				
				$interval_start = $start;
				
				$totals = array();
				$count = array();
				
				while ($interval_start < $end)
				{
					
					// move forward one interval
					$interval_end = Move_Interval($interval_start, $this->major_interval, 1);
					
					reset($data_x);
					$intervals = array();
					
					while (list($key, $offset) = each($data_x))
					{
						
						$offset += $interval_start;
						
						// make sure this falls within our interval (otherwise, we may end
						// up counting, say March 1st as February 29th, or something similar)
						if (($offset >= $interval_start) && ($offset < $interval_end))
						{
							$next = Move_Interval($offset, $this->minor_interval, 1);
							$intervals[] = array($offset, $next);
						}
						
					}
					
					$temp = $data_y;
					
					// get our source data
					$this->source->Prepare($this->now);
					$this->source->Fill($intervals, $temp);
					$this->source->Finalize();
					
					reset($temp);
					
					// now, process the result of the source and update our totals and counts
					while (list($key, $value) = each($temp))
					{
						
						if (is_numeric($value))
						{
							if (isset($totals[$key])) $totals[$key] += $value; else $totals[$key] = $value;
							if (isset($count[$key])) $count[$key]++; else $count[$key] = 1;
						}
						
					}
					
					// move along
					$interval_start = $interval_end;
					
				}
				
				reset($totals);
				
				while (list($key, $value) = each($totals))
				{
					
					$value = ($value / $count[$key]);
					
					if ($this->precision !== NULL)
					{
						$value = round($value, $this->precision);
					}
					
					$data_y[$key] = $value;
					
				}
				
			}
			
			return;
			
		}
		
		protected function Translate_Date($date)
		{
			
			if (!is_numeric($date)) $date = strtotime($date, $this->now);
			return $date;
			
		}
		
	}
	
?>