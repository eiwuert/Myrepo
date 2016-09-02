<?php

require_once ('prpc/server.php');

class Chain_Server extends Prpc_Server
{
	function Chain_Server ()
	{
		parent::Prpc_Server ();
	}

	function Chain ($url, $chain)
	{
		foreach ($chain as $id => $link)
		{
			$rpc = $this->Prpc_Proxy ($url.'chain_server.'.$id.'.php');
			$rpc->Chain ($url, $link);
		}

		return ($_GET['cs']);
	}
}

new Chain_Server ();

?>
