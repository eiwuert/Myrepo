<?php
	
	/**
	 *
	 * Transformation-source base class, designed to be extended. It acts upon
	 * single source (unlike a Compound_Source). An example of a transformation
	 * source is the Cumulative_Source.
	 *
	 */
	class Transformation_Source implements iSource
	{
		
		protected $source;
		
		protected $prepared = FALSE;
		protected $now;
		
		public function __construct($source = NULL)
		{
			
			if ($source instanceof iSource) $this->source = $source;
			return;
			
		}
		
		public function Source(iSource $source)
		{
			
			$this->source = $source;
			return;
			
		}
		
		public function Prepare($now)
		{
			
			if ($this->source)
			{
				
				$this->now = $now;
				$this->prepared = TRUE;
				
			}
			else
			{
				throw new Exception('No source.');
			}
			
			return;
			
		}
		
		protected function Check_Prepared($throw = TRUE)
		{
			
			if ($throw && (!$this->prepared))
			{
				throw new Exception('Source not prepared.');
			}
			
			return $this->prepared;
			
		}
		
		public function Finalize()
		{
			
			$this->now = NULL;
			$this->prepared = FALSE;
			
			return;
			
		}
		
		// just to complete the interface
		public function Fill($data_x, &$data_y)
		{
		}
		
	}
	
?>