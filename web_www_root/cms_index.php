<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This is the start. Everything goes through index.php, gets parsed
 * and displays the appropriate page.
 * 
**/

#die("made it here");

	define ('ROOT_PATH', realpath(dirname(__FILE__)).'/');
	include 'cms-includes/defines.inc.php';
        include 'cms-includes/functions.inc.php';
	include 'cms-includes/init.inc.php';
	
	// any custom defines for this site
	if (is_file(ROOT_PATH.'cms-includes/site.defines.inc.php'))
	    include 'cms-includes/site.defines.inc.php';
	// any custom functions for this site
	if (is_file(ROOT_PATH.'cms-includes/site.functions.inc.php'))
	    include 'cms-includes/site.functions.inc.php';
	
	lum_start();

?>
