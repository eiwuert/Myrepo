<?php
	
	class Interval_Report implements iReport
	{
		
		protected $sources = array();
		protected $data_set = array();
		protected $titles = array();
		
		protected $now;
		protected $start_date;
		protected $end_date;
		protected $interval;
		protected $label_format;
		protected $title;
		
		// only exist after Prepare() is called
		protected $labels;
		protected $scale;
		
		public function __construct($title = NULL, $start = NULL, $end = NULL, $interval = NULL, $label = NULL)
		{
			
			if ($title !== NULL) $this->title = $title;
			if ($start !== NULL) $this->start_date = $start;
			if ($end !== NULL) $this->end_date = $end;
			if ($interval !== NULL) $this->interval = $interval;
			if ($label !== NULL) $this->label_format = $label;
			
			return;
			
		}
		
		public function Title($title = NULL)
		{
			
			if ($title !== NULL) $this->title = $title;
			return $this->title;
			
		}
		
		public function Add_Source(iSource $source, $title)
		{
			
			$this->sources[] = $source;
			$this->titles[] = $title;
			return;
			
		}
		
		public function Start_Date($date = NULL)
		{
			
			if (Translate_Date($date) !== FALSE)
			{
				$this->start_date = $date;
			}
			
			return $this->start_date;
			
		}
		
		public function End_Date($date = NULL)
		{
			
			if (Translate_Date($date) !== FALSE)
			{
				$this->end_date = $date;
			}
			
			return $this->end_date;
			
		}
		
		public function Default_Range()
		{
			
			// create a default range for the report
			$range = new Interval_Range($this->start_date, $this->start_date, $this->end_date, $this->interval);
			return $range;
			
		}
		
		public function Interval($interval = NULL)
		{
			
			if (is_numeric($interval) || (@strtotime($interval) !== NULL))
			{
				$this->interval = $interval;
			}
			
			return $this->interval;
			
		}
		
		public function Label_Format($label = NULL)
		{
			if ($label !== NULL )$this->label_format = $label;
			return $this->label_format;
		}
		
		public function Prepare($now)
		{
			
			$end_date = Transform_Date($this->end_date, $now);
			$start_date = Transform_Date($this->start_date, $end_date);
			
			$data_x = array();
			$data_y = array();
			$labels = array();
			
			$date = $start_date;
			$base = $start_date;
			
			while ($date < $end_date)
			{
				
				$labels[] = date($this->label_format, $date);
				$data_x[] = ($date - $base);
				$data_y[] = NULL;
				
				// move forward one interval
				$date = Move_Interval($date, $this->interval);
				
			}
			
			// save these for displays
			$this->scale = $data_x;
			$this->labels = $labels;
			
			foreach ($this->sources as $key=>&$source)
			{
				
				// create a copy for this source
				$temp = $data_y;
				
				$source->Prepare($now);
				$source->Fill($data_x, $temp);
				$source->Finalize();
				
				$this->data_set[$key] = $temp;
				
			}
			
			return;
			
		}
		
		public function Data()
		{
			
			$sets = array();
			
			foreach ($this->data_set as $key=>$data)
			{
				
				$set = array(
					'data' => $data,
					'title' => $this->titles[$key],
				);
				
				$sets[] = $set;
				unset($set);
				
			}
			
			return $sets;
			
		}
		
		public function Scale()
		{
			return $this->scale;
		}
		
		public function Labels()
		{
			return $this->labels;
		}
		
	}
	
?>