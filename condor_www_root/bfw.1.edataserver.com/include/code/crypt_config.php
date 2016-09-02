<?php

	/**
	 * Class to retrieve encryption keys
	 *
	 * @author Vinh Trinh <vinh.trinh@sellingsource.com>
	 *
	 */

require_once('libolution/AutoLoad.1.php');
require_once('libolution/Security/Crypt.1.php');

class Crypt_Config
{
	static private $mode;
	
	//IV used to initialize everything
	const IV = '0123456789ABCDEF';
	
	//Key used to decrypt remote key
	const CONFIG_KEY = 'Se||ing$0rc3Se||ing$0rc3';
	
	public function __construct($mode)
	{
		self::$mode = $mode;
		return $this->Get_Config(self::$mode);
	}

		/**
		 * @param string $mode - set to "LOCAL", "RC', or "LIVE"
		 * 
		 * returns array("KEY" => $encryption_key, "IV" => $encryption_iv)
		 */	
	
	static function Get_Config($mode)
	{
		
		
		self::$mode = strtoupper($mode);
	
		switch(self::$mode) {
			case 'LOCAL':
			default:
				$key_file =	'/virtualhosts/crypt/key.dat';
				
				/* OLP Key for Monster just in case decryption of external file does not work			
				$crypt_config = array(
						"KEY" 	=>  'LOCAL12345678912',
						"IV"	=>  self::IV,
					);
				*/

				break;
			case 'RC':
				$key_file =	'/virtualhosts/crypt/key.dat';				
				
				/* OLP Key for RC just in case decryption of external file does not work
				$crypt_config = array(
					"KEY" 	=>  'RC12345678912345',
					"IV"	=>  self::IV,
					);
				*/
				break;
			case 'LIVE':
				$key_file =	'/virtualhosts/crypt/key.dat';

				break;
		}
		
		if(file_exists($key_file))
		{
			$crypt = new Security_Crypt_1(md5(self::CONFIG_KEY));
			$crypt->SetStaticIV(self::IV);
			$crypt->setUseStaticIV(true);
			$olp_key = $crypt->Decrypt(base64_decode(trim(file_get_contents($key_file))));
			
			$crypt_config = array(
				"KEY" 	=>  $olp_key,
				"IV"	=>  self::IV,
			);
		}
		else 
		{
			if(self::$mode == 'LIVE')
			{
				// Send Alert
			}
		}
		

		return $crypt_config;
	}
}
?>
