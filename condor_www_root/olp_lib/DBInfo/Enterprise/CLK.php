<?php
/**
 * Database connection information for the CLK eCash companies.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class DBInfo_Enterprise_CLK
{
	/**
	 * Returns an array of the database information for the given company and mode.
	 *
	 * @param string $name_short the name short of the company to retrieve db info for
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	public static function getDBInfo($name_short, $mode)
	{
		$server = array();

		switch (strtolower($name_short))
		{
			case 'ca':
				$server = self::getCA($mode);
				break;
			case 'd1':
				$server = self::getD1($mode);
				break;
			case 'pcl':
				$server = self::getPCL($mode);
				break;
			case 'ucl':
				$server = self::getUCL($mode);
				break;
			case 'ufc':
			default:
				$server = self::getUFC($mode);
				break;
		}
		
		$server['db_type'] = 'mysqli';
		
		return $server;
	}
	
	/**
	 * Returns an array containing the database connection information for CA (Ameriloan).
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	protected static function getCA($mode)
	{
		$server = array();
		
		switch ($mode)
		{
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					"host"		=> "writer.ecashca.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3306
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> "reader.ecashcaolp.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					'port'		=> 3307,
					"password"	=> "password"
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = array(
					"host"		=> "db121.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "dicr9dJA",
					"port"		=> 3306
				);
				break;
		}
		
		return $server;
	}
	
	/**
	 * Returns an array containing the database connection information for D1 (500FastCash).
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	protected static function getD1($mode)
	{
		$server = array();
		
		switch ($mode)
		{
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					"host"		=> "writer.ecash3d1.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3306
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> "reader.ecashd1olp.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password"
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = self::getDevDB();
				break;
		}
		
		return $server;
	}
	
	/**
	 * Returns an array containing the database connection information for PCL (OneClickCash).
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	protected static function getPCL($mode)
	{
		$server = array();
		
		switch ($mode)
		{
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					"host"		=> "writer.ecashpcl.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3306);
				break;
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> "reader.ecashpclolp.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3308
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = array(
					"host"		=> "dev.ecash3clk.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb_20080404",
					"password"	=> "password",
					"port"		=> 3306
				);
				break;
		}
		
		return $server;
	}
	
	/**
	 * Returns an array containing the database connection information for UCL (UnitedCashLoans).
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	protected static function getUCL($mode)
	{
		$server = array();
		
		switch ($mode)
		{
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					"host"		=> "writer.ecashucl.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3306
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> "reader.ecashuclolp.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3308
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = array(
					"host"		=> "dev.ecash3clk.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb_ucl_20070812",
					"password"	=> "password",
					"port"		=> 3306
				);
				break;
		}
		
		return $server;
	}
	
	/**
	 * Returns an array containing the database connection information for UFC (USFastCash).
	 *
	 * @param string $mode the mode we're currently running in
	 * @return array
	 */
	protected static function getUFC($mode)
	{
		$server = array();
		
		switch ($mode)
		{
			case 'LIVE':
			case 'SLAVE':
				$server = array(
					"host"		=> "writer.ecashufc.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3306
				);
				break;
			case 'LIVE_READONLY':
				$server = array(
					"host"		=> "reader.ecashufcolp.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb",
					"password"	=> "password",
					'port'		=> 3307
				);
				break;
			case 'RC':
			case 'RC_READONLY':
			default:
				$server = array(
					"host"		=> "dev.ecash3clk.ept.tss",
					"user"		=> "olp",
					"db"		=> "ldb_20080411",
					"password"	=> "password",
					"port"		=> 3306
				);
				break;
		}
		
		return $server;
	}
}
?>
