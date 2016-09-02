<?php

class Auto_Mode
{
	var $local_name;

	function Get_Local_Name()
	{
		return $this->local_name;
	}

	function Fetch_Mode($server_url)
	{
		if ( preg_match("/rc\.\w*\.\w*\.(ds\d{2}|dev\d{2})\.tss/i", $server_url, $matched) )
		{
			$mode = "RC";
		}
		elseif ( preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $server_url, $matched) )
		{
			$mode = "LOCAL";
			$this->local_name = $matched[1];
		}
		elseif( preg_match ("/^rc\d*\./i", $server_url) )
		{
			$mode = "RC";
		}
		else
		{
			$mode = "LIVE";
		}

		return $mode;
	}
}

?>