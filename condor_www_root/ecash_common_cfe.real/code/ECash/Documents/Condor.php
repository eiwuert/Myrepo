<?php
require_once("prpc/client.php");
/**
 * ECash_Documents_Condor
 * static prpc object for Condor
 * 
 */
class ECash_Documents_Condor
{
	static private $prpc;
	
	static public function Prpc()
	{
		try 
		{
			if (!(self::$prpc instanceof Prpc_Client)) 
			{
				$condor_server = eCash_Config::getInstance()->CONDOR_SERVER;
				self::$prpc = new Prpc_Client($condor_server);
			}

			return self::$prpc;
			
		} 
		catch (Exception $e) 
		{
			if (preg_match("//",$e->getMessage())) 
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: " . $condor_server . " is not a valid PRPC resource.");
			}
			
			throw $e;
			
		}

	}
	
}

?>