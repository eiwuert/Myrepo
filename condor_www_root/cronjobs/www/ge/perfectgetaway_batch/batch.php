<?php

require_once '/virtualhosts/cronjobs/www/ge/ge_batch.php';
require_once '/virtualhosts/cronjobs/includes/pass_args.1.php';

// files must be included in this order

// define site specific constants
chdir(preg_replace('/(.*\/)*[^\/]/','$1',$_SERVER['PATH_TRANSLATED']));
require 'site.conf.php';
// define common constants, create ftp object & define email addresses
require '/virtualhosts/cronjobs/www/ge/global.conf.php';
// create sql object & db object
require '/virtualhosts/cronjobs/www/ge/global.setup.php';
// set variables based on command line parms
require '/virtualhosts/cronjobs/www/ge/get_args.php';

// Setup GE Promo Code Oject
$promo = new stdClass ();
$promo->site_code = SITE_CODE;

// code provided from GE, monthly
switch (date("Ym"))
{
	case 200306:
		$promo->promo_code = "00000000";
		break;
	case 200308:
	case 200309:
	case 200310:
	case 200311:
	case 200401:
	case 200402:
		$promo->promo_code = "01001764";
		break;
	default:
		exit ('ERROR: No promo code for '.date('Ym')."\n");
}

require '/virtualhosts/cronjobs/www/ge/common_batch.php';

?>
