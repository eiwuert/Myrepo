<?php
	
	/**
	 *
	 * Generates the ratio of the source $value to the source $total.
	 *
	 */
	class Ratio_Source
	{
		
		protected $value;
		protected $total;
		protected $precision;
		protected $as_percentage;
		
		protected $now;
		
		public function __construct($value = NULL, $total = NULL, $precision = NULL, $as_percentage = NULL)
		{
			
			if (is_object($value) || is_numeric($value)) $this->value = $value;
			if (is_object($total) || is_numeric($total)) $this->total = $total;
			
			if (is_numeric($precision) && ($precision > 0)) $this->precision = $precision;
			if (is_bool($as_percentage)) $this->as_percentage = $as_percentage;
			
			return;
			
		}
		
		public function Value($source = NULL)
		{
			
			if (is_object($source) || is_numeric($source))
			{
				$this->value = &$source;
			}
			
			return $this->value;
			
		}
		
		public function Total($source = NULL)
		{
			
			if (is_object($source) || is_numeric($source))
			{
				$this->total = $source;
			}
			
			return $this->total;
			
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
		
		public function Prepare($now)
		{
			
			if ($this->value && $this->total)
			{
				
				$this->now = $now;
				
			}
			else
			{
				throw new Exception('Must have a value and total source.');
			}
			
			return;
			
		}
		
		public function Finalize()
		{
			
			$this->now = NULL;
			return;
			
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->now)
			{
				
				if (count($data_x) === count($data_y))
				{
					
					// get our values
					if (is_object($this->value))
					{
						
						$value = $data_y;
						
						$this->value->Prepare($this->now);
						$this->value->Fill($data_x, $value);
						$this->value->Finalize();
						
					}
					else
					{
						$value = $this->value;
					}
					
					// get our totals
					if (is_object($this->total))
					{
						
						$total = $data_y;
						
						$this->total->Prepare($this->now);
						$this->total->Fill($data_x, $total);
						$this->total->Finalize();
						
					}
					else
					{
						$total = $this->total;
					}
					
					reset($data_x);
					
					while (list($key, $x) = each($data_x))
					{
						
						// calculate the ratio
						if (is_array($value))
						{
							$y = (isset($value[$key]) ? $value[$key] : 0);
						}
						else
						{
							$y = $value;
						}
						
						if (is_array($total))
						{
							$y = ((isset($total[$key]) && ($total[$key] != 0)) ? ($y / $total[$key]) : NULL);
						}
						else
						{
							$y = ($total != 0) ? ($y / $total) : NULL;
						}
						
						// do we need to round at all?
						if ($this->as_percentage === TRUE) $y = ($y * 100);
						if (is_numeric($this->precision)) $y = round($y, $this->precision);
						
						// assign this value to our data
						if ($y !== NULL) $data_y[$key] = $y;
						
					}
					
					// clean up
					unset($value);
					unset($total);
					
				}
				
			}
			else
			{
				throw new Source_Not_Prepared();
			}
			
			return;
			
		}
		
	}
	
?>