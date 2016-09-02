<?php
/**
 * @package Rpc
 */

/**
 * Recieves an rpc call
 *
 */
class Rpc_Server_1
{

	/**
	 * @var bool
	 */
	protected $is_rpc_client;

	/**
	 * Service objects
	 *
	 * @var array
	 */
	protected $service = array();

	/**
	 * Constructor
	 *
	 * @param string $class Class name of the default service
	 * @param array $arg Constructor args for the default service
	 * @param bool $process Process the post immediately?
	 */
	public function __construct ($class, $arg = NULL, $process = TRUE)
	{
		ob_start();
		ignore_user_abort(1);
		set_time_limit(0);
		set_error_handler(array($this, 'handleError'), E_ALL ^ (E_NOTICE|E_USER_NOTICE|E_USER_WARNING));
		set_exception_handler(array($this, 'handleException'));
		$this->addService(NULL, $class, $arg);
		if($process) $this->processPost();
	}

	/**
	 * Add a service object
	 *
	 * @param string $name
	 * @param string $class
	 * @param array $arg
	 */
	public function addService($name, $class, $arg = NULL)
	{
		if (is_object($class))
		{
			$this->service[$name] = $class;
		}
		else
		{
			$r = new ReflectionClass($class);

			if ($arg === NULL)
				$this->service[$name] = $r->newInstance();
			else
				$this->service[$name] = $r->newInstanceArgs($arg);
		}
	}

	/**
	 * Process the call in the POST data
	 *
	 */
	public function processPost()
	{
		if (! $this->isRpcClient())
		{
			ob_end_clean();
			echo "<pre>\n", __CLASS__, "\n\tService\n\n";
			foreach($this->service as $k => $v)
			{
				$r = new ReflectionObject($v);
				echo "\t\t", $k ? $k : 'default', ' => ', get_class($v), "\n";
				$ms = $r->getMethods();
				foreach($ms as $m)
				{
					if ($m->isPublic())
						echo "\t\t\t", $m->getName(), "(", implode(", ", $m->getParameters()), ");\n";
				}
				echo "\n";
			}
			echo "</pre>\n";
			exit(1);
		}

		$call = Rpc_1::decode(file_get_contents('php://input'));

		$res = $this->processCall($call);
		$this->sendResult($res);
	}

	/**
	 * Process a call
	 *
	 * @param Rpc_Call_1 $call
	 */
	public function processCall(Rpc_Call_1 $call)
	{
		$res = $call->invoke($this->service);
		if(ob_get_level()) $res->output = ob_get_clean();
		return $res;
	}

	/**
	 * Send the result via http
	 *
	 * @param Rpc_Result_1 $res
	 */
	protected function sendResult(Rpc_Result_1 $res)
	{
		$data = Rpc_1::encode($res);
		//header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
		//header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		//header('Pragma: no-cache');
		header('Content-Type: octet/stream');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.strlen($data));

		echo $data;
		exit (0);
	}

	protected function isRpcClient()
	{
		if ($this->is_rpc_client === NULL)
		{
			$this->is_rpc_client = stripos($_SERVER['HTTP_USER_AGENT'], 'rpc_client') === FALSE ? FALSE : TRUE;
		}
		return $this->is_rpc_client;
	}

	public function handleError($no, $str, $file, $line, $ctx)
	{
		if (error_reporting() & $no)
		{
			throw new RuntimeException("Server Side error in $file ($line)\n$no - $str");
		}
	}

	public function handleException($e)
	{
		if($this->isRpcClient())
		{
			$ob = ob_get_level() ? ob_get_clean() : NULL;
			$res = new Rpc_Result_1(Rpc_1::E_INIT);
			$res[] = array(Rpc_1::T_THROW, $e, $ob);
			$this->sendResult($res);
		}
		else
		{
			$ob = ob_get_level() ? ob_get_clean() : NULL;
			echo "<pre>\nServer side exception: ".$e->getMessage()."\n\n".$e."\n\n\nOutput Buffer:\n".$ob."\n\n</pre>";
			exit(1);
		}
	}
}

?>
