#!/usr/local/php5/bin/php
<?php
	
	/*
		
		Creates device sym-links from the USB devices
		for MultiTech GSM modems based on the modem's
		serial number.
		
		This allows Kannel to always reference the
		same device (our sym-link), and be sure that
		it's using the correct phone number.
		
		Author: Andrew Minerd
		Version: 1.0
		
	*/
	
	define('DEVICE_PATH', '/dev/tts/');
	define('DEVICE_FIND', 'USB*');
	define('DEVICE_OPTS', '115200 crtscts cs8  -parenb cstopb');
	
	$script = array_shift($argv);
	$dev = $argv;
	
	if (!count($dev))
	{
		// find USB tty devices
		$dev = glob(DEVICE_PATH.DEVICE_FIND);
	}
	
	// make sure these are real paths
	$dev = array_filter(array_map('realpath', $dev));
	
	foreach ($dev as $device)
	{
		
		// save our previous options
		exec('stty -F '.$device.' -g', $saved, $return);
		$saved = reset($saved);
		
		if ($return === 0)
		{
			
			// set our device options
			exec('stty -F '.$device.' '.DEVICE_OPTS, $n, $return);
			
			if ($return === 0)
			{
				
				$fh = @fopen($device, 'w+');
				
				if ($fh !== FALSE)
				{
					
					// ask for the serial number of the modem
					$command = "AT+CFUN=1";
					$write = @fwrite($fh, $command."\n");
					
					// close the device
					@fclose($fh);
					
				}
				
			}
			
			// reset the device options
			exec('stty -F '.$device.' '.DEVICE_OPTS, $n);
			
		}
		
	}
	
?>