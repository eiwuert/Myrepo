<?php
	
	class Clip_Source extends Transformation_Source
	{
		
		protected $low_limit;
		protected $high_limit;
		
		public function Low_Limit($limit = NULL)
		{
			if (is_numeric($limit)) $this->low_limit = $limit;
			return $this->low_limit;
		}
		
		public function High_Limit($limit = NULL)
		{
			if (is_numeric($limit)) $this->high_limit = $limit;
			return $this->high_limit;
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->Check_Prepared())
			{
				
				$this->source->Prepare($now);
				$this->source->Fill($data_x, $data_y);
				$this->source->Finalize();
				
				while (list($key, $y) = each($data_y))
				{
					
					if (is_numeric($y))
					{
						if (is_numeric($this->low_limit) && ($y < $this->low_limit)) $y = $this->low_limit;
						elseif (is_numeric($this->high_limit) && ($y > $this->high_limit)) $y = $this->high_limit;
					}
					
				}
				
			}
			
			return;
			
		}
		
	}
	
?>