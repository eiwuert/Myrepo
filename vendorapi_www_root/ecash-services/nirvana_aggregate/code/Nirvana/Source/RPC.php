<?php
class Nirvana_Source_RPC implements Nirvana_ISource
{
	private $url;

	/**
	 * Timeout for the RPC/whatever connection
	 *
	 * @var int
	 */
	private $connection_timeout = 60; // Seconds for the RPC timeout

	public function setUrl($url) {
		$this->url = $url;
	}

	public function setConnectionTimeout($timeout) {
		$this->connection_timeout = $timeout;
	}

	public function getTokens($track_keys, $user, $pass)
	{
		$url = $this->url.'?'.http_build_query(array('user' => $user, 'pass' => $pass));

		$rpc_client = new Rpc_Client_1($url, $this->connection_timeout, $this->connection_timeout);
		return $rpc_client->Fetch_Multiple($track_keys);
	}

	public function __toString() {
		return "RPC: {$this->url}";
	}
}