<?php
/**
 * Holds statuses for the monitor.
 * 
 * Originally thought this might end up doing more, but turned it out it was a lot simpler.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Monitor
{
	/**
	 * Array of running scrubbers.
	 *
	 * @var unknown_type
	 */
	protected $running_scrubbers = array();
	
	/**
	 * Stores the statuses retreived from the $reader XMLReader object with the host.
	 *
	 * @param XMLReader $reader
	 * @param string $host
	 * @return void
	 */
	public function storeStatuses(XMLReader $reader, $host)
	{
		$this->running_scrubbers[$host] = array();
		
		while (@$reader->read())
		{
			if ($reader->nodeType == XMLReader::ELEMENT)
			{
				$name = $reader->name;
			}
			
			if ($reader->nodeType == XMLReader::TEXT)
			{
				switch ($name)
				{
					case 'module':
						$module = $reader->value;
						break;
					case 'last_update':
						$last_update = $reader->value;
						break;
				}
			}
			
			if (isset($module) && isset($last_update))
			{
				$this->running_scrubbers[$host][$module] = $last_update;
				unset($module);
				unset($last_update);
			}
		}
	}
	
	/**
	 * Returns an array of running scrubbers.
	 *
	 * @return array
	 */
	public function getRunningScrubbers()
	{
		return $this->running_scrubbers;
	}
}
