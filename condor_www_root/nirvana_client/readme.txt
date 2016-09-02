Nirvana Client Libraries
------------------------

Follow these guidelines:

	1) Include the client.php in the base directory. Do not include the
	   client.php in the php4/ and php5/ directories directly.
	
	2) Extend the Nirvana_Client class that is included in client.php.
	
	3) Implement Nirvana_Client::Fetch_Multiple() in your super class.
	
	4) Make sure the only object instantiated in your included class is an
	   instance of your super class.
	
	5) Place the script in a public location on your web server so Nirvana
	   may make PRPC requests for user data.
	   
	6) Give RC and LIVE addresses to me. (Hargrove)