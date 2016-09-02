<?php

require_once ('prpc/client.php');

$host = 'prpc://'.$_SERVER['SERVER_NAME'].'/';
$url = $host.'chain_server.0.php';

$url = isset ($_REQUEST['url']) ? $_REQUEST['url'] : $url;
$method = isset ($_REQUEST['method']) ? $_REQUEST['method'] : 'Chain';

$trace['file'] = isset ($_REQUEST['trace']['file']) ? $_REQUEST['trace']['file'] : PRPC_TRACE_FILE;
$trace['call'] = isset ($_REQUEST['trace']['call']) ? $_REQUEST['trace']['call'] : PRPC_TRACE_CALL;
$trace['result'] = isset ($_REQUEST['trace']['result']) ? $_REQUEST['trace']['result'] : 0;

if (isset ($_REQUEST['args']))
{
	eval ('$args = '.$_REQUEST['args'].';');
}
else
{
	$args = array
	(
		preg_replace ('/(prpc:\/\/[^\/]+\/).*/', '$1', $url),
		array
		(
			'1' => array (),
			'2' => array (),
			'1' => array
			(
				'2' => array (),
				'1' => array ()
			)
		)
	);
}

?>

<form action="<?php echo $_SERVER ['PHP_SELF']; ?>">

<table>
	<tr>
		<td>URL</td>
		<td><input type="text" name="url" value="<?php echo $url; ?>" size="75"></td>
	</tr>
	<tr>
		<td>Method</td>
		<td><input type="text" name="method" value="<?php echo $method; ?>" size="75"></td>
	</tr>
	<tr>
		<td>Args</td>
		<td><textarea name="args" rows="20" cols="75"><?php var_export ($args); ?></textarea></td>
	</tr>
	<tr>
		<td>Trace</td>
		<td>
			<input type="checkbox" name="trace[file]" value="<?php echo PRPC_TRACE_FILE; ?>"<?php echo $trace['file'] == PRPC_TRACE_FILE ? " checked" : ""; ?>> File&nbsp;&nbsp;
			<input type="radio" name="trace[call]" value="<?php echo PRPC_TRACE_CALL; ?>"<?php echo $trace['call'] == PRPC_TRACE_CALL ? " checked" : ""; ?>> Call&nbsp;&nbsp;
			<input type="radio" name="trace[call]" value="<?php echo PRPC_TRACE_ARGS; ?>"<?php echo $trace['call'] == PRPC_TRACE_ARGS ? " checked" : ""; ?>> Args&nbsp;&nbsp;
			<input type="checkbox" name="trace[result]" value="<?php echo PRPC_TRACE_RESULT; ?>"<?php echo $trace['result'] == PRPC_TRACE_RESULT ? " checked" : ""; ?>> Result&nbsp;&nbsp;
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
			<input type="hidden" name="action" value="invoke">
			<input type="submit" name="Invoke" value="Invoke">
		</td>
	</tr>
</table>

</form>

<?php

if ($_REQUEST['action'] == 'invoke')
{

	echo '<hr><pre>';

	foreach ($trace as $opt)
	{
		$sum += $opt;
	}

	$rpc = new Prpc_Client ($url, TRUE, $sum);

	//$result = call_user_func_array (array (&$rpc, $method), $args);
	$result = $rpc->$method ($args[0], $args[1]);

	print_r ($rpc->_prpc_debug);
	echo "\n\n";

	//echo "\n\n", '---Result---', "\n\n"; print_r ($result); echo "\n\n";
}

?>