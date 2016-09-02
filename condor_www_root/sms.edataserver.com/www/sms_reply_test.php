<?

	require_once('logsimple.php');

	// This script is purely for testing kannel code changes.
	// It's purpose is to test kannel configuration of reply url's.

	$http_vars =& getHttpVarArray();
	$http_var_count = count($http_vars);
	$display = "http_var_count=[$http_var_count], http_vars=[" . array_to_string($http_vars) . ']';
	logsimplewrite($display);

	
	function & getHttpVarArray()
	{
		return 'POST' == $_SERVER['REQUEST_METHOD'] ? $_POST : $_GET;
	}

	function array_to_string( &$vars )
	{
		$result = '';
		foreach($vars as $key => $val) $result .= ($result == '' ? '' : ',') . "$key=$val";
		return $result;
	}

?>
<?= $display ?>