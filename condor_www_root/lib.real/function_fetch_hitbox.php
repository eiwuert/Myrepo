<?php
// function_fetch_hitbox.php
// this is a hitbox code function for the one-off sites.
include_once ("/virtualhosts/lib/lib_mode.1.php");

function fetch_hitbox($hitbox_id=false, $mode=MODE_RC)
{
	if ($hitbox_id)
	{
		$acct_number = $hitbox_id;
		$hitbox = '';

		$hitbox .= "\n<!-- BLOCK: HITBOX [begin] -->";
		$hitbox .= "\n<!-- BEGIN WEBSIDESTORY CODE v3.0.0 (pro-no10)-->";
		$hitbox .= "\n<!-- COPYRIGHT 1997-2003 WEBSIDESTORY, INC. ALL RIGHTS RESERVED. U.S.PATENT No. 6,393,479 B1. Privacy notice at: http://websidestory.com/privacy -->";
		$hitbox .= ($mode != MODE_LIVE) ? "\n<!-- TEST BLOCK : DS/RC Environment [OPEN] -->" : "";
		$hitbox .= "\n<script language=\"javascript\">";
		$hitbox .= "\nvar _pn=\"PUT+PAGE+NAME+HERE\"; //page name(s)";
		$hitbox .= "\nvar _mlc=\"CONTENT+CATEGORY\"; //multi-level content category";
		$hitbox .= "\nvar _cp=\"null\"; //campaign";
		$hitbox .= "\nvar _acct=\"".$acct_number."\"; //account number(s)";
		$hitbox .= "\nvar _pndef=\"title\"; //default page name";
		$hitbox .= "\nvar _ctdef=\"full\"; //default content category";
		$hitbox .= "\nvar _prc=\"\"; //commerce price";
		$hitbox .= "\nvar _oid=\"\"; //commerce order";
		$hitbox .= "\nvar _dlf=\"\"; //download filter";
		$hitbox .= "\nvar _elf=\"\"; //exit link filter";
		$hitbox .= "\nvar _epg=\"\"; //event page identifier";
		$hitbox .= "\nvar _mn=\"wp189\"; //machine name";
		$hitbox .= "\nvar _gn=\"phg.hitbox.com\"; //gateway name";
		$hitbox .= "\n</script>";
		$hitbox .= "\n<script language=\"javascript1.1\" defer src=\"http://stats.hitbox.com/js/hbp-11up.js\"></script>";
		$hitbox .= "\n<script language=\"javascript\">if(navigator.appName!=\"Netscape\"&&navigator.appVersion.toString().charAt(0)!=\"3\")document.write(\"<!\"+\"--\");</script>";
		$hitbox .= "\n<noscript>";
		$hitbox .= "\n<img src=\"http://phg.hitbox.com/HG?hc=wp189&cd=1&hv=6&ce=u&hb=".$acct_number."&n=PUT+PAGE+NAME+HERE&vcon=CONTENT+CATEGORY\" border=\"0\" width=\"1\" height=\"1\">";
		$hitbox .= "\n</noscript>";
		$hitbox .= ($mode != MODE_LIVE) ? "\n<!-- TEST BLOCK : DS/RC Environment [CLOSE] -->" : "";
		$hitbox .= "\n<!-- END WEBSIDESTORY CODE  -->";
		$hitbox .= "\n<!-- BLOCK: HITBOX [end] -->";
	}
	else
	{
		$hitbox = "";
	}
	return $hitbox;
}
?>
