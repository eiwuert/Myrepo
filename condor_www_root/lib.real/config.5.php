<?php

require_once ("prpc/server.php");

class Config_5
{
	var $mode;
	var $server;
	var $path;
	var $verbose_debug;
	
	function __Construct($server_name, $mode = "AUTO")
	{
		switch( strtoupper($mode) )
		{
			// Figure out mode based on server name
			case "AUTO":
				if ( preg_match("/\.(ds\d{2}|dev\d{2}|gambit)\.tss$/i", $server_name, $matched) )
				{
					//$this->server = "tss.config.soapdataserver.com.{$matched[1]}.tss";
					$this->server = "tss.config.soapdataserver.com.gambit.tss:8080";

				}
				elseif( preg_match ("/^rc\./i", $_SERVER["SERVER_NAME"]) )
				{
					$this->server = "rc.config.soapdataserver.com";					
				}
				else
				{
					$this->server = "config.soapdataserver.com";					
				}
			break;
			
			// Manual mode selection
			case "LOCAL":
				if( preg_match("/\.(ds\d{2}|dev\d{2}|gambit)\.tss$/i", $server_name, $matched) )
				{
					//$this->server = "tss.config.soapdataserver.com.php5.{$matched[1]}.tss";	
					$this->server = "tss.config.soapdataserver.com.gambit.tss:8080";
				}
				else
				{
					throw new Exception("Cannot use local mode on a non dsX named system.");
				}
			break;
			
			case "RC":
				$this->server = "rc.config.1.soapdataserver.com";
			break;
			
			default:
				$this->server = "config.1.soapdataserver.com";				
			break;
		}
		
		$this->mode = $mode;
		// init_4 is a hack and needs to get fixed
		$this->path = "/init_4";
	}
			
	public function Get_Site_Config ($license, $promo_id = NULL, $promo_sub_code = NULL, $page)
	{
	
		// Simple validation on license key.
		if( !is_string($license) )
		{
			throw new Exception("License key should be a string 32 characters or longer.");
		}
		
		// If we do not have a proper promo_id change to the default of 10000
		if( is_null($promo_id) || !is_numeric($promo_id) )
		{
			$promo_id = 10000;
		}
		
		// If sub_code is null change to default of a blank string
		if( is_null($promo_sub_code) )
		{
			$promo_sub_code = "";
		}
		
		$server = "prpc://". $this->server . $this->path;
	
		// Attempt PRPC call
		$init_obj = new Prpc_Client ($server);

		try 
		{
			$response = $init_obj->Get_Init($license, $promo_id, $promo_sub_code, $page, strtoupper($this->mode));
		}
		catch(Exception $e)
		{
			throw $e;
		}
		
		return $response;
	}
}

?>
