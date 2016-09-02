<?php
	// Make sure we can handle errors
	require_once ("error.2.php");

	class Library_1
	{
		function Library_1 ()
		{
			return TRUE;
		}

		function Get_Library ($name, $api, $feature)
		{
			// Default to absolute failure
			$lib_path = new Error_2 ();
			$lib_path->name = $name;
			$lib_path->api = $api;
			$lib_path->feature = $feature;
			$lib_path->fatal = TRUE;

			// The test location
			$test_path = "/virtualhosts/lib/".strtolower ($name).".".$api.".php";
			
			// Use the exact API
			if (is_file ($test_path))
			{
				// Open the file
				$file_handle = fopen ($test_path, "r");

				// Get the version
				fgets ($file_handle); // Read the first line (should be <?php)
				$version_line = fgets ($file_handle); // Get the second line (the one we want)
				fclose ($file_handle); // All done

				preg_match ("/(\d*)\.(\d*)\.(\d*)/", $version_line, $matches);
				$lib_api = $matches [1];
				$lib_feature = $matches [2];
				$lib_bug = $matches [3];

				if ((int)$lib_api >= (int)$api)
				{
					// API is good, check for feature level
					if ((int)$lib_feature >= (int)$feature)
					{
						// Remove the error class from the lib_path variable
						unset ($lib_path);

						// Set the path to the library file
						$lib_path = strtolower ($name).".".$api.".php";
					}
					else
					{
						// Wrong feature level
						$lib_path->message = "The required feature level is not available.  Please update ".$name." on: ".$_SERVER ["SERVER_NAME"];
					}
				}
				else
				{
					// Wrong api level
					$lib_path->message = "The required api level is not available.  Please update ".$name." on: ".$_SERVER ["SERVER_NAME"];
				}
			}
			else
			{
				// The file does not exist
				$lib_path->message = "The required library is not available.  Please add ".strtolower ($name).".".$api.".php on: ".$_SERVER ["SERVER_NAME"];
			}

			return $lib_path;
		}

		function Get_Version ()
		{
			$version = new stdClass ();

			$version->api = 1;
			$version->feature = 0;
			$version->bug = 0;
			$version->version = $version->api.".".$version->feature.".".$version->bug;

			return $version;
		}
	}
?>
