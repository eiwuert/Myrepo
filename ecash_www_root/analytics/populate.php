<?php

	/**
	 * Command line script that sets up the environment and loads
	 * the appropriate classes, then executes the analytics process.
	 * 
	 * @author Brian Ronald <brian.ronald@sellingsource.com>
	 */

	require 'libolution/AutoLoad.1.php';

	if (count($argv) < 4)
	{
		echo "Usage:   {$argv[0]} [mode] [customer] [company]\n";
		echo "Example: {$argv[0]} RC CLK ufc\n\n";
		die();
	}

	$mode     = $argv[1];
	$customer = strtoupper($argv[2]);
	$company  = $argv[3];

	$current_path = dirname(__FILE__);
	AutoLoad_1::addSearchPath($current_path . '/code/');

	$class_name = $customer . '_Batch';

	$rc = new ReflectionClass($class_name);
	if (! $rc->implementsInterface('Analysis_IBatch'))
	{
		throw new Exception('Customer does not implement analysis batch interface.');
	}

	$analysis_db = call_user_func(array($class_name, 'getAnalysisDb'), $mode);
	$ecash_db = call_user_func(array($class_name, 'getECashDb'), $company, $mode);

	if ($rc->implementsInterface('Analysis_IBatchLegacy'))
	{
		$legacy_db = call_user_func(array($class_name, 'getLegacyDb'), $company, $mode);
	}
	else
	{
		$legacy_db = NULL;
	}

	$analysis = new Analysis(
		$analysis_db->getConnection()
	);

	$batch = new $class_name(
		$company,
		$ecash_db->getConnection(),
		$analysis,
		($legacy_db) ? $legacy_db->getConnection() : NULL
	);

	$batch->execute();

?>
