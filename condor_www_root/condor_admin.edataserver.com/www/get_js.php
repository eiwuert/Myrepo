<?php
	require_once( "config.php" );

	if( ! empty($_GET['override']) )
	{
		if( file_exists(WWW_DIR . "js/" . basename($_GET['override']) . ".js") )
			$js = file_get_contents(WWW_DIR . "js/" . basename($_GET['override']) . ".js");
		else
			$js = "";
	}
	else
	{
		$js  = file_get_contents( "js/layer.js" );
		$js .= file_get_contents( "js/menu.js" );
		$js .= file_get_contents( "js/flux_capacitor.js" );
		$js .= file_get_contents( "js/overlib.js" );
		$js .= file_get_contents( "js/disable_link.js" );
	}

	header( "application/x-javascript" );

	echo $js;
?>