<?php
	
	class Average_Source extends Compound_Source
	{
		
		protected $precision;
		
		public function Fill($data_x, &$data_y, &$labels)
		{
			
			if ($this->Check_Prepared())
			{
				
				// to hold combined values
				$totals = array();
				$total_count = array();
				
				// loop through our sources
				foreach ($this->sources as &$source)
				{
					
					// fill up a temporary array
					$temp = $data_y;
					
					$source->Prepare($this->now);
					$source->Fill($data_x, $temp, $labels);
					$source->Finalize();
					
					// add to our totals
					while (list($key, $y) = each($temp))
					{
						
						// a non-numeric value indicates that there was
						// no data for this point (NOT the same as zero!)
						if (is_numeric($y))
						{
							
							if (isset($totals[$key]))
							{
								$totals[$key] += $y;
								$total_count[$key]++;
							}
							else
							{
								$totals[$key] = $y;
								$total_count[$key] = 1;
							}
							
						}
						
					}
					
					// done with this
					unset($temp);
					
				}
				
				// do this once
				$count = count($this->sources);
				
				while (list($key, $y) = each($totals))
				{
					
					// get our average value
					$count = $total_count[$key];
					$y = (($count > 0) ? ($y / $count) : 0);
					
					// round, if required
					if (is_numeric($this->precision)) $y = round($y, $this->precision);
					
					// save it into the array
					$data_y[$key] = $y;
					
				}
				
				// clean up
				unset($totals);
				
			}
			
			return;
			
		}
		
	}
	
?>