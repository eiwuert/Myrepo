<?php
	
	class Percent_Source extends Transformation_Source
	{
		
		protected $precision;
		protected $as_percentage;
		
		public function __construct($source = NULL, $precision = NULL, $as_percentage = NULL)
		{
			
			if ($source instanceof iSource) $this->source = $source;
			if (is_numeric($precision) && ($precision > 0)) $this->precision = $precision;
			if (is_bool($as_percentage)) $this->as_percentage = $as_percentage;
			
			return;
			
		}
		
		public function Precision($precision = NULL)
		{
			
			if (is_numeric($precision)) $this->precision = $precision;
			return $this->precision;
			
		}
		
		public function As_Percentage($as = NULL)
		{
			
			if (is_bool($as)) $this->as_percentage = $as;
			return $this->as_percentage;
			
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->Check_Prepared())
			{
				
				// get our source data
				$this->source->Prepare($this->now);
				$this->source->Fill($data_x, $data_y);
				$this->source->Finalize();
				
				// total we'll compare to
				$total = array_sum($data_y);
				
				reset($data_x);
				
				while (list($key, $y) = each($data_y))
				{
					
					if (is_numeric($y))
					{
						
						// calculate our ratio of the total
						$y = (($total != 0) ? ($y / $total) : 0);
						
						// do we need to round at all?
						if ($this->as_percentage === TRUE) $y = ($y * 100);
						if (is_numeric($this->precision)) $y = round($y, $this->precision);
						
						$data_y[$key] = $y;
						
					}
					
				}
				
			}
			
			return;
			
		}
		
	}
	
?>