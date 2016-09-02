<?php
	/**
	 * A cheesy little utility to encrypt passwords so they can be stored in the pop_accounts table.
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */
	
	require_once(dirname(__FILE__) . "/../lib/security.php");
	
	if($argc < 2 || empty($argv[1]))
	{
		echo "Condor Pop Mail Password Encrypt Utility\n";
		echo "This utility will encrypt the supplied password so that it can be pasted into the pop_accounts table\n";
		echo "Usage: " . $argv[0] . " [password] \n\n";
		exit;
	}
	
	$new_password = Security::Encrypt($argv[1]);
	echo "The encrypted password is : $new_password\n";

?>
