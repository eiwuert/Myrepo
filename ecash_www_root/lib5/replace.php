<?php
//define(SITE, "notspam.ds14.tss");
//define('SITE', "fastcashemail.com");
//define(SITE, "charity.no-ip.com/~fastcas");
//define('PAGE', "index.php");
//define(PAGE, "decode.php");
//customersupply.info custsupply.info secureaid.info goldenaid.info cashact.info cashboost.info serviceaid.info cashsupport.info talkcash.info capitalcash.info customcorp.info bigbridges.info linkmaker.info locstocbarl.info theromyzon.info cashucarry.info inmypocket.info fasthelper.info companyconnection.info linktoyou.info
//code modified because of parse_url bug in 5.0.5

//include_once("code_url.php");
class Replace_Url
{		
	function comReplace($url, $line, $proxy = 'linktoyou.info')
	{
		if (isset($url))
		{
			$replace_str = str_replace(".com", ".{$proxy}", $url);
			return str_replace($url, $replace_str, $line);
		}		
	}
	
	//PHP5 will need a static added to the front of this function. 
	function replace($body, $email_address="test", $proxy = 'linktoyou.info')
	{		
		$body_array = explode(" ", $body);
		foreach ($body_array as $index=>$line)
		{			
			if (preg_match("/href=\"http:\/\/(.*?.com)/", $line, $match) )
			{
				$body_array[$index] = Replace_Url::comReplace($match[1], $line, $proxy);
			}
			if (preg_match("/src=\"http:\/\/(.*?.com)/", $line, $match) )
			{
				$body_array[$index] = Replace_Url::comReplace($match[1], $line, $proxy);
			}
			if (preg_match("/background=\"http:\/\/(.*?.com)/", $line, $match) )
			{
				$body_array[$index] = Replace_Url::comReplace($match[1], $line, $proxy);
			}

		}
		$body = implode(" ", $body_array);
		return $body;
	}
}

//Debug Test
//echo Replace_Url::replace( implode(" ", file("/home/jason/email.txt"))."\r\n");
?>
