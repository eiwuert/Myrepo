<?php
/*

dequest.1.php

written by David Bryant

this function is designed to wipe all unspecified contents of the GET, POST and REQUEST
super-arrays in order to prevent accidental re-submissions on back buttons and page
reloads.  To use it, call dequest() immediately after using your data.

*/

function dequest ($preserve_arr=false)
{
	if ($preserve_arr)
	{
		$x_request = array_diff (array_keys ($_REQUEST), $preserve_arr);
		$x_post = array_diff (array_keys ($_POST), $preserve_arr);
		$x_get = array_diff (array_keys ($_GET), $preserve_arr);

		if (count ($x_request) > 0)
		{
			foreach ($x_request as $x)
			{
				unset ($_REQUEST[$x]);
			}
		}

		if (count ($x_post) > 0)
		{
			foreach ($x_post as $x)
			{
				unset ($_POST[$x]);
			}
		}

		if (count ($x_get) > 0)
		{
			foreach ($x_get as $x)
			{
				unset ($_GET[$x]);
			}
		}
	}
	else
	{
		$_REQUEST = array ();
		$_GET = array ();
		$_POST = array ();
	}
}

?>