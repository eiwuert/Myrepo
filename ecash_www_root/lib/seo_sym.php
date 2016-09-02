#!/usr/bin/php
<?php
// This file is a command-line tool to generate the symlinks used in
// SEO for the SST sites (and maybe others).  Run it from the directory
// you want to create the links in.  The index.php MUST be in this
// directory already.  If putting this on a new server, you must
// edit the .bashrc script when you first log in and add a reference
// to this file, then exit and re-enter.

if (count ($_SERVER["argv"]) != 2)
{
	echo "\n\nUSAGE: seo_sym seo_directory\n\n(NOTE: file must be run from the directory you want the links to appear in.)\n\n";
}
else
{
	if (is_dir ($_SERVER["argv"][1]))
	{
		$seo_dir = dir ($_SERVER["argv"][1]);
		$current_path = realpath (".");

		while (($file_name = $seo_dir->read()) !== false)
		{
			switch ($file_name)
			{
				case ".":
				case "..":
				case "seo.inc.array.refers.php":
				case "seo.inc.links.keywords.php":
				case "seo.vars.index.php":
					break;
				default:
					if (strpos ($file_name, "seo.vars.") !== false)
					{
						if (is_file ($seo_dir->path."/".$file_name))
						{
							$link_name = str_replace ("seo.vars.", "", $file_name);
							$command = "ln -s ".$current_path."/index.php ".$link_name;
							system ($command, $success);
							if ($success !== FALSE)
							{
								echo "Sym-Link ".$link_name." created.\n";
							}
							else
							{
								echo "ERROR: Sym-Link ".$link_name." failed miserably.\n";
							}
						}
					}
					break;
			}
		}
		echo "\nAll links created. Done.\n\n";
	}
}


?>