<?php
	
	class Interval_Range extends Transformation_Source
	{
		
		protected $now;
		protected $start_date;
		protected $end_date;
		protected $interval;
		protected $base_date;
		
		protected $source;
		protected $title;
		
		public function __construct($base = NULL, $start = NULL, $end = NULL, $interval = NULL, $source = NULL)
		{
			
			if ($source instanceof iSource) $this->source = $source;
			if ($base !== NULL) $this->base_date = $base;
			if ($start !== NULL) $this->start_date = $start;
			if ($end !== NULL) $this->end_date = $end;
			if ($interval !== NULL) $this->interval = $interval;
			
			return;
			
		}
		
		public function Start_Date($date = NULL)
		{
			
			if (is_numeric($date) || (($date !== NULL) && (@strtotime($date) !== FALSE)))
			{
				$this->start_date = $date;
			}
			
			return $this->start_date;
			
		}
		
		public function End_Date($date = NULL)
		{
			
			if (is_numeric($date) || (($date !== NULL) && (@strtotime($date) !== FALSE)))
			{
				$this->end_date = $date;
			}
			
			return $this->end_date;
			
		}
		
		public function Base_Date($date = NULL)
		{
			
			if (is_numeric($date) || (($date !== NULL) && (@strtotime($date) !== FALSE)))
			{
				$this->base_date = $date;
			}
			
			return $this->base_date;
			
		}
		
		public function Interval($interval = NULL)
		{
			
			if (is_numeric($interval) || (@strtotime('+'.$interval) !== FALSE))
			{
				$this->interval = $interval;
			}
			
			return $this->interval;
			
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->Check_Prepared())
			{
				
				// transform the offsets in $data_x to intervals within our date range
				$intervals = Build_Intervals($data_x, $this->now, $this->base_date, $this->start_date, $this->end_date, $this->interval);
				
				// and now, fetch the data for those intervals
				// from our underlying source
				if (count($intervals))
				{
					$this->source->Prepare($this->now);
					$this->source->Fill($intervals, $data_y);
					$this->source->Finalize();
				}
				
			}
			
			return;
			
		}
		
	}
	
?>