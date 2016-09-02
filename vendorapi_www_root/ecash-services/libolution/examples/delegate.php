<?php

	require 'libolution/AutoLoad.1.php';

	// instaniating allows us to call the delegate more than once
	// and type-hint for a delegate
	$d = Delegate_1::fromFunction('var_dump');
	$d->invoke($GLOBALS);

	// if we just want the optimizations, and don't want to
	// instantiate an instance of Delegate_1
	Delegate_1::call('var_dump', $GLOBALS);

?>