<?php

$dir_base = '/usr/log/';

foreach (glob ($dir_base.'*', GLOB_ONLYDIR|GLOB_MARK) as $year)
{
	foreach (glob ($year.'*', GLOB_ONLYDIR|GLOB_MARK) as $month)
	{
		foreach (glob ($month.'*', GLOB_ONLYDIR|GLOB_MARK) as $day)
		{

			if (! preg_match ('/\/(\d+)\/(\d+)\/(\d+)\/$/', $day, $m))
				continue;

			if (! (strtotime($m[1].'-'.$m[2].'-'.$m[3]) < time()-(86400*2)))
				continue;

			foreach (glob ($day.'*') as $file)
			{
				if (is_file ($file) && ! preg_match ('/\.bz2$/', $file))
				{
					echo "Compressing ".$file."... ";
					exec ('bzip2 '.$file);
					echo "Done.\n";
				}
			}
		}	
	}
}

?>
