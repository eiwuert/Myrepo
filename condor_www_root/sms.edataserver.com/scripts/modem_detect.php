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
	
	// map the modem serial numbers to the
	// symlinked devices we'll create
	$map = array(
		'010438000002279' => '/dev/modem_6515',
		'010438000002238' => '/dev/modem_0454',
		'010438000002246' => '/dev/modem_2952',
		'010438000002451' => '/dev/modem_8958',
		'010438000002287' => '/dev/modem_3924',
	);
	
	// clean up left-over symlinks
	foreach ($map as $link)
	{
		@unlink($link);
	}
	
	$script = array_shift($argv);
	$dev = $argv;
	
	if (!count($dev))
	{
		// search for devices
		echo("Searching for possible modem devices...\n");
		$dev = glob(DEVICE_PATH.DEVICE_FIND);
	}
	
	// make sure these are real paths
	$dev = array_filter(array_map('realpath', $dev));
	if (!count($dev))	 echo("No devices were found.\n");
	
	foreach ($dev as $device)
	{
		
		echo("Examining {$device}...\n");
		
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
					$command = "AT+CGSN";
					$write = @fwrite($fh, $command."\n");
					
					$read = '';
					$i = 0;
					
					// set a maximum limit on this, for protection
					while ((($buff = @fgets($fh, 1024)) !== FALSE) && (++$i < 100))
					{
						
						$buff = trim($buff);
						if (($buff == 'OK') || ($buff == 'ERROR')) break;
						if ($buff == 'AT+CGSN') $buff = '';
						
						// add to what we've read
						if ($buff != '') $read .= ' '.$buff;
						
					}
					
					if (preg_match('/^(\d+)$/', trim($read), $matches))
					{
						
						// extract the serial number
						$serial = $matches[1];
						
						echo("Found modem with serial number {$serial}.\n");
						
						if (isset($map[$serial]) && (!file_exists($map[$serial])))
						{
							
							$sym = $map[$serial];
							
							// create a symlink to the actual device
							symlink($device, $sym);
							echo("Created symlink {$sym} to {$device}.\n");
							
						}
						
					}
					else
					{
						echo("Modem {$device} returned an invalid response: {$read}\n");
					}
					
					// close the device
					@fclose($fh);
					
				}
				else
				{
					echo("Could not open {$device}!\n");
				}
				
			}
			else
			{
				echo("Could not change device parameters on {$device}!\n");
			}
			
			// reset the device options
			exec('stty -F '.$device.' '.DEVICE_OPTS, $n);
			
		}
		else
		{
			echo("{$device} does not appear to be a serial device!\n");
		}
		
		// put a blank line before the next device
		echo("\n");
		
	}
	
?>