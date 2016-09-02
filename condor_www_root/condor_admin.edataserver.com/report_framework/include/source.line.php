<?php
	
	class Source_Line implements iSource
	{
		
		protected $x;
		protected $y;
		protected $slope;
		
		public function X($x = NULL)
		{
			if (is_numeric($x)) $this->x = $x;
			return $this->x;
		}
		
		public function Y($y = NULL)
		{
			if (is_numeric($y)) $this->y = $y;
			return $this->y;
		}
		
		public function Slope($slope = NULL)
		{
			if (is_numeric($slope)) $this->slope = $slope;
			return $this->slope;
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			while (list($key, $x) = each($data_x))
			{
				$value = ((($x - $this->x) * $this->slope) + $this->y);
				$data_y[$key] = $value;
			}
			
		}
		
	}
	
?>