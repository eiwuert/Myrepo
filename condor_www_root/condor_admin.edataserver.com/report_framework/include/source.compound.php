<?php
	
	/**
	 *
	 * Compound-source base class, designed to be extended.
	 *
	 */
	class Compound_Source implements iSource
	{
		
		protected $sources = array();
		protected $now;
		
		public function __construct()
		{
			
			$sources = func_get_args();
			if (is_array($sources)) $this->sources = $sources;
			
		}
		
		public function Add_Source(Source &$source)
		{
			
			// add to our data sources
			$this->sources[] = &$source;
			return;
			
		}
		
		public function Prepare($now)
		{
			
			if (count($this->sources))
			{
				
				$this->now = $now;
				
			}
			else
			{
				throw new Exception('No sources.');
			}
			
			return;
			
		}
		
		public function Finalize()
		{
			
			$this->now = NULL;
			return;
			
		}
		
		protected function Check_Prepared()
		{
			
			$prepared = TRUE;
			
			if (!$this->now)
			{
				throw new Exception('Source not prepared.');
			}
			
			return $prepared;
			
		}
		
	}
	
?>