<?php
	
	/**
	 *
	 * A "smoothing" source -- essentially, takes an average over ($strength / 2)
	 * points on either side of the point in question (unless you're, say, the first
	 * or last point). This is generally useful for non-cumulative graphs that
	 * have very small intervals.
	 *
	 */
	class Smooth_Source extends Transformation_Source
	{
		
		protected $precision = NULL;
		protected $strength = 5;
		
		public function __construct($source = NULL, $strength = NULL, $precision = NULL)
		{
			
			if ($source instanceof iSource) $this->source = $source;
			if (is_numeric($strength) && ($strength > 0)) $this->strength = $strength;
			if (is_numeric($precision) && ($precision > 0)) $this->precision = $precision;
			
		}
		
		public function Precision($precision = NULL)
		{
			if (is_numeric($precision)) $this->precision = $precision;
			return $this->precision;
		}
		
		public function Strength($strength = NULL)
		{
			if (is_numeric($strength)) $this->strength = $strength;
			return $this->strength;
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			// make sure we're prepared for this
			if ($this->Check_Prepared())
			{
				
				// get a working copy
				$work = $data_y;
				
				// fill from our real source
				$this->source->Prepare($this->now);
				$this->source->Fill($data_x, $work);
				$this->source->Finalize();
				
				$keys = array_keys($data_x);
				$count = count($keys);
				
				while (list($offset, $key) = each($keys))
				{
					
					// starting point
					$start = ($offset - $this->strength);
					if ($start < 0) $start = 0;
					
					// ending point
					$end = ($start + $this->strength);
					if ($end > $count) $end = $count;
					
					$avg = array();
					
					// collect our averaging points
					for ($i = $start; $i <= $end; $i++)
					{
						$avg[] = $work[$keys[$i]];
					}
					
					// compute the average
					$value = $work[$key];
					
					if (count($avg))
					{
						$value = (array_sum($avg) / count($avg));
						if ($this->precision) $value = round($value, $this->precision);
					}
					
					$data_y[$key] = $value;
					
				}
				
			}
			
			return;
			
		}
		
	}
	
?>