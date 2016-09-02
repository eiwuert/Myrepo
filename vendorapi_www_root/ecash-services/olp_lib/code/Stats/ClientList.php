<?php

/** List of different stat clients.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_ClientList
{
	const DEFAULT_CLIENT = 'catch';
	
	/** An array of stat clients and their properties. Automated seeach engine
	 * supports searching one array deep.
	 *
	 * @var array
	 */
	static protected $stat_clients = array(
		'generic' => array(
			'username' => 'username',
			'password' => 'password',
			'company' => EnterpriseData::COMPANY_GENERIC,
			'property_id' => 00000,
			'property_short' => 'generic',
		),
	);
	
	/** Quick-search for a client. Will return default client if none found.
	 *
	 * @param string $search_property Property to search via.
	 * @param string $search_value Value to compare against.
	 * @param string $use_default If true (default), always return a client.
	 * @return array
	 */
	static public function getStatClient($search_property, $search_value, $use_default = TRUE)
	{
		$client = NULL;
		
		foreach (self::$stat_clients AS $client_short => $stat_client)
		{
			if (isset($stat_client[$search_property]))
			{
				if (is_array($stat_client[$search_property]))
				{
					foreach ($stat_client[$search_property] AS $value)
					{
						if (!strcasecmp($search_value, $value))
						{
							$client = $stat_client;
							break;
						}
					}
				}
				elseif (!strcasecmp($stat_client[$search_property], $search_value))
				{
					$client = $stat_client;
					break;
				}
			}
			else if ($search_property == 'property_short' && EnterpriseData::isCompanyProperty($client_short,$search_value))
			{
				$stat_client['property_short'] = array_map('strtolower', EnterpriseData::getCompanyProperties($client_short));
				$client = $stat_client;
			}
		}
		
		// If we want a client, but could not find one, return our default.
		if (!$client && $use_default)
		{
			$client = self::$stat_clients[self::DEFAULT_CLIENT];
		}
		
		return $client;
	}
	
	/** Returns full client list.
	 *
	 * @return array
	 */
	static public function getClientList()
	{
		return self::$stat_clients;
	}
}

?>
