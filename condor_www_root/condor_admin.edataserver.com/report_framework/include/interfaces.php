<?php
	
	/**
	 *
	 * Various and sundry interfaces used by the framework.
	 * @author Andrew Minerd
	 *
	 */
	
	/**
	 *
	 * Represents a source of reporting data.
	 *
	 */
	interface iSource
	{
		
		/**
		 *
		 * Prepares the source for fetching data. This must ALWAYS be
		 * called prior to calling Fill(), or exceptions WILL occur.
		 *
		 * Any relative dates within the source will be resolved according
		 * to $now, which allows you to base a report off a non-system time.
		 *
		 */
		public function Prepare($now);
		
		/**
		 *
		 * Fills the array $data_y with the values for the points
		 * described in the array $data_x.
		 *
		 */
		public function Fill($data_x, &$data_y);
		
		/**
		 *
		 * Called post-Fill, allows the source to clean-up.
		 *
		 */
		public function Finalize();
		
	}
	
	/**
	 *
	 * A data source that can be cached.
	 *
	 */
	interface iCacheable extends iSource
	{
		
		/**
		*
		* Fetches data for a single point, $x.
		*
		*/
		public function Fetch($x);
		
		/**
		*
		* Returns a unique hash for the point $x. The actual
		* caching mechanism will probably use this hash.
		*
		*/
		public function Hash($x);
		
	}
	
	/**
	*
	* A source that fetches data between intervals. The intervals
	* are described with a start and end point:
	*
	* ($x >= $start) && ($x < $end)
	*
	*/
	interface iInterval_Source extends iSource
	{
	}
	
	/**
	*
	* Although it shares the same interface, a class implementing the iRange
	* interface generally acts as a translator between an iReport and an
	* iSource, converting the report's scale into something meaningful
	* to the source.
	*
	*/
	interface iRange extends iSource
	{
		
		public function Source(iSource $source);
		
	}
	
	/**
	*
	* A report.
	*
	*/
	interface iReport
	{
		
		/**
		*
		* Adds a data source to the report.
		*
		*/
		public function Add_Source(iSource $source, $title);
		
		/**
		*
		* Does the magic of building the X-scale and fetching data from
		* all sources. Relative times in the report or any of its sources
		* will be resolved relative to $now.
		*
		*/
		public function Prepare($now);
		
		/**
		*
		* Retrieves the generated X-scale.
		*
		*/
		public function Scale();
		
		/**
		*
		* Retrieves the report data as an array.
		*
		*/
		public function Data();
		
		/**
		*
		* Retrieves an array of labels for each point on
		* the X-scale.
		*
		*/
		public function Labels();
		
	}
	
	/**
	*
	* An display translates an iReport object into a format meaningful
	* to a human, such as a graph or table.
	*
	*/
	interface iDisplay
	{
		
		/**
		*
		* Actually does the the rendering. If $file is provided,
		* the results of the render should be saved to it.
		*
		*/
		public function Render(iReport $report, $file = NULL);
		
	}
	
?>