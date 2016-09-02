<?php
 
/**
 * Event Log - Singleton Wrapper
 * event log wrapper using the singleton pattern
 */
require_once('libolution/AutoLoad.1.php');
require_once('libolution/Security/Crypt.1.php');

class Crypt_Singleton extends Security_Crypt_1
{
	static private $instance;
	
	public function __construct($key = ENC_KEY,$iv = ENC_IV)
	{
		parent::__construct(md5($key));
		$this->SetStaticIV($iv);
		$this->setUseStaticIV(true);
		
	}
	
	static public function Get_Instance($key = ENC_KEY, $iv = ENC_IV)
	{
		if ( !isset(self::$instance) )
		{
			self::$instance = new Crypt_Singleton($key,$iv);
		}	
		return self::$instance;
	}
	public function encrypt($source)
	{
		if(isset($source) && $source != '')
		{
			$enc_data = parent::encrypt($source);
			$enc_b64 = base64_encode($enc_data);	
			return $enc_b64;
		}
		else 
		{
			return '';
		}
		
	}
	
	public function decrypt($source)
	{
		if(isset($source) && $source != '')
		{
			$enc_b64 = base64_decode($source);
			$dec_string = parent::decrypt($enc_b64);
			return $dec_string;
		}
		else 
		{
			return '';
		}
	}
}
?>
