<?php

require_once 'prpc/client.php';

class Nirvana_Source_PRPC implements Nirvana_ISource
{
	private $url;

	public function setUrl($url) {
		$this->url = $url;
	}

	public function getTokens($track_keys, $user, $pass)
	{
		$url = $this->url.'?'.http_build_query(array('user' => $user, 'pass' => $pass));

		$prpc_client = new Prpc_Client($url, TRUE);
		return $prpc_client->Fetch_Multiple($track_keys);
	}

	public function __toString() {
		return "PRPC: {$this->url}";
	}
}