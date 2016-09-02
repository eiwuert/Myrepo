<?php
	// *******************************************************
	// Name:		MASTER JAVASCRIPT FILE
	// Module: 		tss.shared.code/smt
	// Created:		01.13.2004 by David Bryant
	// Updated:		Managed in CVS/Subversion Log Tree
	// *******************************************************

	// ********************************************************
	// Name:	ENABLE JAVASCRIPT TOOLS
	// ********************************************************

	// FUNCTION: Write Popup String used below
	// ********************************************************
	function pop_write_js ($pop)
	{
		return sprintf(
			"window.open(\"%s\",\"%s\",\"%s\");\n" .
			"%s\n",
			$pop->pop_url,
			$pop->pop_winname,
			($pop->pop_dims ? $pop->pop_dims : "width=400,height=300"),
			($pop->pop_stack == "under" ? "window.focus();" : "")
		);
	}

?>

<script type="text/javascript">
<!--// HIDE FROM INCOMPATIBLE BROWSERS

		// Test for cookie capability; send to uh-oh page if not
		// ********************************************************
		if (!navigator.cookieEnabled)
		{
			if (!document.cookie || document.cookie == null || document.cookie == "")
			{
				<?php
				if ($_REQUEST['page'] != "no_unique_id")
				{
					echo "\t\t\t\tdocument.location.href=\"index.php?page=no_unique_id\";\n";
				}
				?>
			}
		}

		// Set exit variable based on page type
		// ********************************************************
		var exit = <?php echo ($target_class == "pops" || substr ($_SERVER["HTTP_REFERER"], -1, count ($_SERVER["PHP_SELF"])) == $_SERVER["PHP_SELF"]) ? "false" : "true" ?>;

		// Set onLoad Pop
		// ********************************************************
		function pop_onLoad()
		{
			<?php echo $pop_onLoad_js; ?>
		}

		// Set onUnload popup
		// ********************************************************
		function pop_onUnload()
		{
			if (exit)
			{
				<?php echo $pop_onUnload_js; ?>
				window.focus();
			}
			return true;
		}

		// Set Pop Title
		// ********************************************************
		function set_title (ref, title)
		{
			ref.document.title = title;
		}

		// ********************************************************
		function pop_bookmark(page_req, bookmark)
		{
			page_url = "?page="+page_req+"#"+bookmark;
			win = window.open(page_url,"popwin","width=500,height=420,resizable=no,scrollbars=yes,toolbar=no,menubar=no");
		}

		// Spawn local pops from drop-down menus
		// ********************************************************
		function pop_dropdown(dropdown_obj)
		{
			page_req = dropdown_obj.options[dropdown_obj.selectedIndex].value;
			switch (page_req)
			{
				// these do nothing:
				case "":
				case "Quick Navigation":
					break;
				//these change location:
				case "?page=home":
				case "?page=cs_login":
					document.location.href = "index.php"+page_req+"&unique_id=<?php echo session_id (); ?>";
					break;
				// this spawns the popup:
				case "?page=cs_removeme":
				default:
					page_url = "index.php"+page_req+"&unique_id=<?php echo session_id (); ?>";
					window.open(page_url,"popwin","width=490,height=420,resizable=no,scrollbars=yes,toolbar=no,menubar=no");
					break;
			}
		}

		// Spawn external sites in standard sized pop (800x600)
		// ********************************************************
		function pop_newsite(page_url, bookmark)
		{
			if (bookmark)
			{
				page_url = page_url+"#"+bookmark;
			}
			window.open(page_url,"tss_win","width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes");
		}

		// Spawn local pops from menus
		// ********************************************************
		function pop_menu(page_req)
		{
			switch (page_req)
			{
				// these change location:
				case "":
				case "?page=prequal":
					//document.location.href = "index.php"+page_req;
					document.location.href = "index.php?unique_id=<?php echo session_id (); ?>";
					break;
				// this spawns the popup:
				default:
					page_url = page_req+"&unique_id=<?php echo session_id (); ?>";
					window.open(page_url,"popwin","width=500,height=420,resizable=no,scrollbars=yes,toolbar=no,menubar=no");
					break;
			}
		}

		// Redirect links INSIDE of a popup back to parent window
		// ********************************************************
		function back2opener(loc_str)
		{
			opener.location.href = loc_str;
		}



		function _check_state(val)
		{
			hide_div("ca_form");
			switch(val)
			{
				case "CA":
					show_div("ca_form");
					break;
			}
		}
		
		if(!hide_div) {
			var hide_div = function(d) {
					document.getElementById(d).style.display = "none";
				}
		}
		
		if(!show_div) {
			var show_div = function(d) {
					document.getElementById(d).style.display = "block";
				}
		}


// -->
</script>
