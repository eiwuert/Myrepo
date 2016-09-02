<?php
	
	class Deficit_Source extends Transformation_Source
	{
		
		protected $limit;
		
		public function __construct($source = NULL, $limit = NULL)
		{
			if ($source instanceof iSource) $this->source = $source;
			if (is_numeric($limit)) $this->limit = $limit;
		}
		
		public function Limit($limit = NULL)
		{
			if (is_numeric($limit)) $this->limit = $limit;
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			if ($this->Check_Prepared())
			{
				
				$work = $data_y;
				
				// get our source data
				$this->source->Prepare($this->now);
				$this->source->Fill($data_x, $work);
				$this->source->Finalize();
				
				while (list($key, $y) = each($work))
				{
					// don't draw anything if we're above the limit
					if (is_numeric($y) && ($y < $this->limit)) $data_y[$key] = ($this->limit - $y);
				}
				
			}
			
			return;
			
		}
		
	}
	
?>