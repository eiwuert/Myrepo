<?php
	// private.config.php
	// The global configuration file.

	// Handle stuff outside the web space.
	define ('PHP_DIR', './code/');
	define ('URL_DIRS', preg_replace ('/\/[^\/]*\/$/', '/', preg_replace ('/\?'.$_SERVER ['QUERY_STRING'].'/', '', $_SERVER ['REQUEST_URI'].'/')));
	define ('URL_ROOT', 'http://'.$_SERVER ['SERVER_NAME'].URL_DIRS);
	define ('PARENT_URL', 'http://'.$_SERVER ['SERVER_NAME'].preg_replace ('/\/[^\/]*\/$/', '/', URL_DIRS));
	define ('IMAGE_URL','./image/');
	define ('CSS_URL','./css/');
	define ('EXEC_SECRET', 'venal');
	define ('SECURE_SITE', 'epointmarketing.com');
	define ('SESS_NAME', 'ssid');

	// kludge until i find something better
	define ('BIN_DIR', '/virtualhosts/lib/ge/');

	switch (TRUE)
	{
		// localhost
		case (preg_match ('/.*\.(ds\d{2}|dev\d{2}|alpha)\.tss$/', $_SERVER ['SERVER_NAME'], $matched)):
			define ('PRPC_SERVER', 'prpc://ge.2.soapdataserver.com.'.$matched[1].'.tss/main.php');
			break;
		 // rc
		case (preg_match ('/^rc\..*/', $_SERVER ['SERVER_NAME'])):
			define ('PRPC_SERVER', 'prpc://rc.ge.2.soapdataserver.com/main.php');
			break;
		 // live
		default:
			define ('PRPC_SERVER', 'prpc://ge.2.soapdataserver.com/main.php');
			break;
	}

?>
