<?php
/**
 * CS Config Class
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPECash_CS_Config
{
	/**
	 * RPC URLs by company
	 *
	 * @var array
	 */
	protected static $rpc_urls = array(
		EnterpriseData::COMPANY_DMP => array(
			'LIVE' => 'prpc://ecash_api:urf1r3d@live.ecash.mycashcenter.com/rpc/prpc.php',
			'RC' => 'prpc://ecash_api:urf1r3d@rc.ecash.mcc.ept.tss/rpc/prpc.php',
			'LOCAL' => 'prpc://ecash_api:urf1r3d@rc.ecash.mcc.ept.tss/rpc/prpc.php',
		),
		EnterpriseData::COMPANY_MMP => array(
			'LIVE' => 'prpc://ecash_api:urf1r3d@live.ecash.mymoneypartner.com/rpc/prpc.php',
			'RC' => 'prpc://ecash_api:urf1r3d@rc.ecash.mmp.ept.tss/rpc/prpc.php',
			'LOCAL' => 'prpc://ecash_api:urf1r3d@rc.ecash.mmp.ept.tss/rpc/prpc.php',
		)
	);

	/**
	 * Gets the RPC URL of an eCash service
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @param string $class Name of class to use in the RPC call
	 * @return array
	 */
	public static function getRpcUrl($property_short, $mode, $class)
	{
		$mode = strtoupper($mode);
		$company = EnterpriseData::getCompany($property_short);
		
		if (!empty($company) && isset(self::$rpc_urls[$company]) && isset(self::$rpc_urls[$company][$mode]))
		{
			$url = self::$rpc_urls[$company][$mode];
			$url .= '?rpc_class='.$class;
			$url .= '&company='.strtolower(EnterpriseData::resolveAlias($property_short));
		}
		else
		{
			$url = FALSE;
		}
		return $url;
	}
	
	/**
	 * Gets the RPC URL for the CSO eCash service
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return array
	 */
	public static function getCSORpcUrl($property_short, $mode)
	{
		$url = self::getRpcUrl($property_short, $mode, 'eCash_Custom_RPC_CSO');
		return $url;
	}
	
	/**
	 * Gets the RPC URL for the eCash Token service
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return array
	 */
	public static function getTokenRpcUrl($property_short, $mode)
	{
		$url = self::getRpcUrl($property_short, $mode, 'ECash_RPC_Tokens');
		return $url;
	}
	
	/**
	 * Can a property short request a rollover
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return bool
	 */
	public static function canRollover($property_short, $mode)
	{
		return (self::getCSORpcUrl($property_short, $mode) !== FALSE);
	}
		
	/**
	 * Can a property short get tokens from the Token RPC call
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return bool
	 */
	public static function tokenRpcReady($property_short, $mode)
	{
		return (self::getTokenRpcUrl($property_short, $mode) !== FALSE);
	}
}

?>
