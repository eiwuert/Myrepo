<?php

	require_once(dirname(__FILE__) . "/../lib/condor_crypt.php");	
	define('EXECUTION_MODE', 'Live');

	$in_file  = $argv[1];
	$out_file = $argv[2];
	
	if(is_file($in_file))
	{
		
		$data = file_get_contents($in_file);
		
//		if($this->encrypted)
//		{
			$data = Condor_Crypt::Decrypt($data, 'MODE_LIVE');
//		}
//		if ($this->compression === 'GZ')
//		{
			// suppress the output because php sucks.
			$data = @gzuncompress($data);
//		}
	}
	
	file_put_contents($out_file, $data);
?>

