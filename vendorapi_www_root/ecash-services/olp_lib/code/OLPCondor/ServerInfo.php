<?php
/**
 * Class OLPCondor_ServerInfo provides Condor server connection info for a mode
 * and property short
 *
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_ServerInfo
{
	/**
	 * Key map holder
	 *
	 * @var array
	 */
	private $key_map = array(
		'GENERIC' => array(
			'LIVE' => array(
				'KEY' => 'username',
				'PASS' => 'password',
			),
			'RC' => array(
				'KEY' => 'username',
				'PASS' => 'password',
			),
		),
	);
	
	/**
	 * Array of server connections for condor modes
	 *
	 * @var array
	 */
	private $servers = array(
		'RC' => 'rc.condor.4.edataserver.com',
		'LIVE' => 'condor.4.internal.edataserver.com',
	);
	
	/**
	 * An instance of this class
	 *
	 * @var OLPCondor_ServerInfo
	 */
	private static $instance = NULL;
	
	/**
	 * This is a singleton.
	 */
	protected function __construct()
	{
	}
	
	/**
	 * Gets an instance of this class.
	 * For internal use only!
	 *
	 * @return OLPCondor_ServerInfo An instance of this class.
	 */
	private static function getInstance()
	{
		if (!(self::$instance instanceof OLPCondor_ServerInfo))
		{
			self::$instance = new OLPCondor_ServerInfo();
		}
		
		return self::$instance;
	}
	
	/**
	 * Maps OLP mode to Condor mode for user/pass
	 *
	 * @param string $olp_mode OLP mode
	 * @return string
	 */
	protected function mapMode($olp_mode)
	{
		switch (strtoupper($olp_mode))
		{
			case 'LIVE':
				$condor_mode = 'LIVE';
				break;
			default:
				$condor_mode = 'RC';
				break;
		}
		
		return $condor_mode;
	}
	
	/**
	 * Returns the Condor key based on property short and mode
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return string
	 */
	protected function getCondorKey($property_short, $mode)
	{
		return $this->key_map[strtoupper($property_short)][$mode]['KEY'];
	}
	
	/**
	 * Returns the Condor password based on property short and mode
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return string
	 */
	protected function getCondorPass($property_short, $mode)
	{
		return $this->key_map[strtoupper($property_short)][$mode]['PASS'];
	}
	
	/**
	 * Returns the Condor server based on property short and mode
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return string
	 */
	protected function getCondorServer($property_short, $mode)
	{
		$property_short = strtoupper($property_short);
		
		if (isset($this->key_map[$property_short][$mode]['SERVER']))
		{
			$server = $this->key_map[$property_short][$mode]['SERVER'];
		}
		else
		{
			$server = $this->servers[$mode];
		}
		
		return $server;
	}
	
	/**
	 * Retrun the Condor server string
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return string
	 */
	public static function getServerInfo($property_short, $mode)
	{
		$condor = self::getInstance();
		$mode = $condor->mapMode($mode);
		$user = $condor->getCondorKey($property_short, $mode);
		$pass = $condor->getCondorPass($property_short, $mode);
		$server = $condor->getCondorServer($property_short, $mode);
		
		$url = sprintf('%s:%s@%s', $user, $pass, $server);
		
		return $url;
	}
}
?>
