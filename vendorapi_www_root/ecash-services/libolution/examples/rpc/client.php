<?php

require_once('AutoLoad.1.php');

$cli = new Rpc_Client_1('http://rpc.ds02.tss/redir.php');


// Simple usage {{{

echo $cli->hello('world'), "\n\n";
print_r($cli->result);
echo "\n\n";

try
{
	$res = $cli->except();
}
catch (Exception $e)
{
	echo "Caught Exception!\n", $e, "\n\n";
}

try
{
	$res = $cli->err();
}
catch (Exception $e)
{
	echo "Caught Exception!\n", $e, "\n\n";
}
echo "\n";

// }}}


// Batch usage {{{

$cli->rpcBatchBegin();

$cli->hello('llama');
$cli->goodbye('llama');

$res = $cli->rpcBatchExec();
print_r($res);
echo "\n\n";

// }}}

// Batch with keyed calls and multiple service objects {{{

$cli->rpcBatchBegin();

$cli->call->addMethod('hi', 'hello', array('llama'));
$cli->call->addMethod('bye', 'goodbye', array('llama'));
$cli->call->addMethod('key', 'getKey', NULL, 'a');

$res = $cli->rpcBatchExec();
print_r($res);
echo "\n\n";

// }}}

?>
