<?php
class LenderAPI_Transport_FakeResponse extends LenderAPI_Transport
{
	protected $fake_response;
	public function __construct($fake_response, $url = NULL, $method = NULL, $timeout = NULL, $response = NULL, $param = NULL, $agent = NULL)
	{
		$this->fake_response = $fake_response;
		parent::__construct($url, $method, $timeout, $response, $param, $agent);
	}
	
	/**
	 * send the data
	 *
	 * @param array|string $data
	 * @return LenderAPI_Response
	 */
	public function send($data)
	{
		$this->agent->Reset_State();
		if (!empty($this->timeout))
		{
			$this->agent->Set_Timeout($this->timeout);
		}
		
		$start = microtime(TRUE);
		
		$h = array();
		if (! empty($this->headers))
		{
			foreach ($this->headers as $k => $v)
			{
				$h[] = "{$k}: {$v}";
			}
			$this->agent->Set_Headers($h);
		}
		
		$this->response->setDataSent(implode("\n", $h)."\n\n".$data);
		$body = $this->fake_response;
		

		$this->response->setPostTime(microtime(TRUE) - $start);
		$this->response->cookieJar = array();
		$this->response->setDataReceived($body);


		return $this->response;
	}
	
}