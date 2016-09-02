	<?php
	if (is_file(PLUGINS_PATH."$plugin/admin/$action.php"))
		include_once(PLUGINS_PATH."$plugin/admin/$action.php"); 
	else 
		echo "Page not found: $plugin/$action";
	?>
