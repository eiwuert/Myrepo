<?php

	require 'libolution/AutoLoad.1.php';

	$obj = new stdClass();
	$obj->test = 'woot';

	$store = new Util_ObjectStorage_1();
	$store[$obj] = new stdClass();
	$store[$obj]->test = 'foo';

	var_dump($obj->test, $store[$obj]->test);

	// Note that you can't modify an array from
	// ArrayAccess, so the following WILL NOT work:
	// (in fact, you should get a notice)

	$store[$obj] = array();
	$store[$obj]['test'] = 'foo';

	var_dump($store[$obj]['test']);

?>