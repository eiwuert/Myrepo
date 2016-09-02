<?php
	
	class Formula_Source
	{
		
		protected $sources = array();
		protected $names = array();
		
		protected $formula = NULL;
		protected $precision = 4;
		protected $now;
		
		public function Add_Source($name, $source)
		{
			
			// add to our data sources
			$this->sources[$name] = $source;
			$this->names[] = $name;
			return;
			
		}
		
		public function Formula($formula = NULL)
		{
			
			if ($formula !== NULL) $this->formula = $formula;
			return $this->formula;
			
		}
		
		public function Prepare($now)
		{
			$this->now = $now;
		}
		
		public function Finalize()
		{
		}
		
		public function Fill($data_x, &$data_y)
		{
			
			$source_data = array();
			
			file_put_contents('/tmp/report.log', "BEGINNING FORMULA...\n\n", FILE_APPEND);
			
			foreach ($this->names as $name)
			{
				
				if (isset($this->sources[$name]))
				{
					
					$this->sources[$name]->Prepare($this->now);
					
					$temp = $data_y;
					$this->sources[$name]->Fill($data_x, $temp);
					
					$source_data[$name] = $temp;
					unset($temp);
					
					$this->sources[$name]->Finalize();
					
				}
				
			}
			
			while (list($key, $x) = each($data_x))
			{
				
				$formula = $this->formula;
				
				foreach ($this->names as $name)
				{
					$value = (isset($source_data[$name][$key])) ? $source_data[$name][$key] : 0;
					$formula = preg_replace('/\b'.$name.'\b/', $value, $formula);
				}
				
				if (($result = @eval('return ('.$formula.');')) !== FALSE)
				{
					$data_y[$key] = round($result, $this->precision);
				}
				
			}
			
			file_put_contents('/tmp/report.log', "END FORMULA...\n\n", FILE_APPEND);
			
			return;
			
		}
		
	}
	
?>