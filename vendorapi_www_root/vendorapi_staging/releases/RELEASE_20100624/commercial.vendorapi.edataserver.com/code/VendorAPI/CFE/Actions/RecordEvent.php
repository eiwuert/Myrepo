<?php
/**
 * CFE Action that will record unique and non-unique events in StatPro
 * Expected parameters are 'event' and 'unique'
 * 		'event' is a string and is required
 * 		'unique' is an enumerated string of TRUE/FALSE and is optional.  If set
 * 		to TRUE will apply application level uniqueness to the event 
 * Expected attributes are 'driver', 'track_key', 'space_key', and
 * 'application_id'
 * 		'driver' is a VendorAPI_IDriver and is used to retrieve a StatPro client 
 * 		'track_key' is a string and is used for recording the event
 * 		'space_key' is a string and is used for determining the space information
 * 			for recording the event
 * 		'application_id' is an integer and is oprional.  It is required for any
 * 			unique event being recorded and identifies an event's uniqueness
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_CFE_Actions_RecordEvent extends ECash_CFE_Base_BaseAction
		implements ECash_CFE_IExpression
{
	public function execute(ECash_CFE_IContext $context)
	{
		$driver = $context->getAttribute('driver');
		if (!($driver instanceof VendorAPI_IDriver))
		{
			$class = ($class == NULL) ? "NULL" :  get_class($driver);
			throw new RuntimeException(
				sprintf(
					'Context returned "%s" instead of VendorAPI_IDriver from getAttribute(\'driver\') in RecordEvent CFE Action',
					$class));
		}
		
		$stat_client = $driver->getStatProClient();
		if (!($stat_client instanceof VendorAPI_StatProClient))
		{
			$class = ($class == NULL) ? "NULL" :  get_class($stat_client);
			throw new RuntimeException(
				sprintf("StatPro client %s in driver is not instance of VendorAPI_StatProClient as required by RecordEvent CFE Action",
				$class));
		}
		
		$params = $this->evalParameters($context);
		
		if (empty($params['event']))
		{
			throw new RuntimeException(
				"\"event\" parameter not provided but required by RecordEvent CFE Action");
		}
		
		$track_key = $context->getAttribute("track_key");
		if (empty($track_key))
		{
			throw new RuntimeException(
				"\"track_key\" attribute not provided but required by RecordEvent CFE Action");
		}
	
		$space_key = $context->getAttribute("space_key");
		if (empty($space_key))
		{
			throw new RuntimeException(
				"\"space_key\" attribute not provided but required by RecordEvent CFE Action");
		}
	
		$unique = !empty($params['unique']) && strcasecmp($params['unique'], "TRUE") == 0;
		
		if ($unique)
		{
			if (!($stat_client instanceof VendorAPI_StatPro_Unique_IClient))
			{
				$class = ($class == NULL) ? "NULL" :  get_class($stat_client);
				throw new RuntimeException(
					sprintf("StatPro client %s in driver does not imlplement VendorAPI_StatPro_Unique_IClient as required by RecordEvent CFE Action",
					$class));
			}
			
			$application_id = $context->getAttribute("application_id");
			if (empty($application_id))
			{
				throw new RuntimeException(
					"\"application_id\" attribute not provided but required by RecordEvent CFE Action as unique parameter was TRUE");
			}
			$stat_client->hitUniqueStat($params['event'], $application_id, $track_key, $space_key);
		}
		else
		{
			$stat_client->hitStat($params['event'], $track_key, $space_key);
		}
	}
	
	public function getType()
	{
		return get_class($this);
	}
	
	public function getParameters()
	{
		return $this->params;
	}
}
?>
