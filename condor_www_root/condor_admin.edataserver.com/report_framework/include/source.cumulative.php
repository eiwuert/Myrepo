<?php
	
	class Cumulative_Source extends Transformation_Source
	{
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->Check_Prepared())
			{
				
				// get our source data
				$this->source->Prepare($this->now);
				$this->source->Fill($data_x, $data_y);
				$this->source->Finalize();
				
				// start with nothing
				$cumulative = 0;
				reset($data_y);
				
				while (list($key, $y) = each($data_y))
				{
					// don't add non-numeric data
					$data_y[$key] = (is_numeric($y) ? ($cumulative += $y) : $cumulative);
				}
				
			}
			
			return;
			
		}
		
	}
	
?>