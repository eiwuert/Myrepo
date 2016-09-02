<?php
// function_fetch_pixels.php
// this is a tracking pixel function for the one-off sites
include_once ("/virtualhosts/lib/lib_mode.1.php");

function fetch_pixels ($stat_page, $site_mode)
{
	$pixel_str = false;
	if (isset ($_SESSION["config"]->event_pixel[$stat_page]))
	{
		$pixel_str = "<!-- tracking pixels start ";
		$pixel_str .= $site_mode == MODE_LIVE ? "-->\n" : "\n";
		// check to see if it exists first
		foreach ($_SESSION["config"]->event_pixel[$stat_page] as $pix_arr)
		{
			if( isset($pix_arr["subcode"]) && $_SESSION["promo_sub_code"] == $pix_arr["subcode"] )
			{
				$pixel_str .= "<img src=\"".trim ($pix_arr["tracking_pixel"])."\" width=\"1\" height=\"1\" border=\"0\" />\n";
			}
			elseif( !isset($pix_arr["subcode"]) )
			{
				$pixel_str .= "<img src=\"".trim ($pix_arr["tracking_pixel"])."\" width=\"1\" height=\"1\" border=\"0\" />\n";
			}
		}
		$pixel_str .= $site_mode == MODE_LIVE ? "<!-- tracking pixels end -->\n" : " tracking pixels end -->\n";
	}
	return $pixel_str;
}

?>