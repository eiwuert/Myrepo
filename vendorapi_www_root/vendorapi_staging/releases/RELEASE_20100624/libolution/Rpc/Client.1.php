<?php
/**
 * @package Rpc
 */

/**
 * Sends rpc calls
 *
 */
class Rpc_Client_1 extends Object_1
{
	/**
	 * Curl handle
	 *
	 * @var resource
	 */
	protected $ch;

	/**
	 * The ob 
	 * 
	 * @var string
	 */
	protected $ob;
	public function getOutput()
	{
		return $this->ob;
	}

	/**
	 * Server URL
	 *
	 * @var string
	 */
	protected $url;
	public function setUrl($url)
	{
		$this->url = $url;
	}
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * Connect timeout
	 *
	 * @var int
	 */
	protected $cto;
	public function setConnectTimeout($sec)
	{
		$this->cto = $sec;
	}
	public function getConnectTimeout()
	{
		return $this->cto;
	}

	/**
	 * Global timeout
	 *
	 * @var int
	 */
	protected $gto;
	public function setGlobalTimeout($sec)
	{
		$this->gto = $sec;
	}
	public function getGlobalTimeout()
	{
		return $this->gto;
	}

	/**
	 * Batch flag
	 *
	 * @var bool
	 */
	protected $is_batch = FALSE;
	public function isBatch()
	{
		return $this->is_batch;
	}

	/**
	 * Current call object
	 *
	 * @var Rpc_Call_1
	 */
	protected $call;

	/**
	 * Constructor
	 *
	 * @param string $url
	 * @param int $connect_timeout
	 * @param int $global_timeout
	 */
	public function __construct($url = NULL, $connect_timeout = 15, $global_timeout = 6000)
	{
		$this->setUrl($url);
		$this->setConnectTimeout($connect_timeout);
		$this->setGlobalTimeout($global_timeout);
	}

	/**
	 * Destructor
	 *
	 */
	public function __destruct()
	{
		$this->curlFree();
	}

	/**
	 * Magic method to implement method calls
	 *
	 * @param string $m Method name
	 * @param array $a Method arguments
	 * @return mixed
	 */
	public function __call($m, $a)
	{
		if (! $this->is_batch)
		{
			$this->call = new Rpc_Call_1();
		}
		$this->call->addMethod(NULL, $m, $a);

		if ($this->is_batch)
		{
			return NULL;
		}

		return $this->doSingleCall();
	}

	/**
	 * Initialize the curl resource
	 *
	 */
	protected function curlInit()
	{
		$this->ch = curl_init();

		$h = array("User-Agent: ".__CLASS__, "Content-Type: octet/stream", "Content-Transfer-Encoding: binary");
		$opt = array(
			CURLOPT_NOSIGNAL => 1,
			CURLOPT_URL => $this->url,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_CONNECTTIMEOUT => $this->cto,
			CURLOPT_TIMEOUT => $this->gto,
			CURLOPT_TCP_NODELAY => 1,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $h,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_MAXREDIRS => 10,
		);

		curl_setopt_array($this->ch, $opt);
	}

	/**
	 * Free the curl resource
	 *
	 */
	protected function curlFree()
	{
		if (is_resource($this->ch))
		{
			curl_close($this->ch);
			$this->ch = NULL;
		}
	}

	/**
	 * Init, use, free the curl resource
	 *
	 * @return mixed
	 */
	protected function curlCall()
	{
		$this->curlInit();
		try
		{
			$re = $this->sendCall();
			$this->curlFree();
		}
		catch (Exception $e)
		{
			$this->curlFree();
			throw $e;
		}
		return $re;
	}

	/**
	 * Send the call and read the response
	 *
	 * @return Rpc_Result_1
	 */
	protected function sendCall()
	{
		$data = Rpc_1::encode($this->call);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);

		$c = curl_exec($this->ch);
		if ($c === FALSE)
			throw new RuntimeException('curl_exec on '.$this->url.' failed with '.curl_error($this->ch));

		$re = Rpc_1::decode($c);

		if (! $re instanceOf Rpc_Result_1)
			throw new RuntimeException("Rpc decode failed. Server response of ".strlen($c)." bytes follows:\n\n".$c);

		return $re;
	}

	/**
	 * Send a single call and process the response
	 *
	 * @return mixed
	 */
	protected function doSingleCall()
	{
		$re = $this->curlCall();
		$this->ob = $re[0][2];

		return $this->procResponse($re[0]);
	}

	/**
	 * Act upon a single response record
	 *
	 * @param array $r
	 * @return mixed
	 */
	protected function procResponse(array $r)
	{
		switch ($r[0])
		{
			case Rpc_1::T_RETURN:
				return $r[1];

			case Rpc_1::T_THROW;
				throw $r[1];

			default:
				throw new RuntimeException('Unknown Rpc response type '.$r[0]);
		}
	}

	/**
	 * Begin batching calls
	 *
	 */
	public function rpcBatchBegin()
	{
		$this->is_batch = TRUE;
		$this->call = new Rpc_Call_1();
	}

	/**
	 * Execute the batch
	 *
	 * @return Rpc_Result_1
	 */
	public function rpcBatchExec()
	{
		$r = $this->curlCall();
		$this->is_batch = FALSE;
		if ($r->flag & Rpc_1::E_INIT)
			return $this->procResponse($r[0]);
		return $r;
	}

	/**
	 * Get the call object
	 *
	 * @return Rpc_Call_1
	 */
	public function getCall()
	{
		if (! $this->call)
		{
			$this->call = new Rpc_Call_1();
		}
		return $this->call;
	}
}

?>
