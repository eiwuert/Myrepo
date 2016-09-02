<?php
/**
	@publicsection
	@public
	@brief
		A pure virtual class.

	This class exists only to derive other message types.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
*/
class Prpc_Message
{
}

/**
	@publicsection
	@public
	@brief
		A pure virtual class.

	This class exists only to derive other message types.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
*/
class Prpc_Request extends Prpc_Message
{
}

/**
	@publicsection
	@public
	@brief
		Message used to pass a function call request.

	Message used to pass a function call request.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
*/
class Prpc_Call extends Prpc_Request
{
	function Prpc_Call ($method, $arg)
	{
		$this->method = $method;
		$this->arg = $arg;
	}
}

/**
	@publicsection
	@public
	@brief
		Message used to pass a one-way notification.

	Message used to pass a one-way notification.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision

	@todo
		- Implement persistant retries and then start using this
*/
class Prpc_Notice extends Prpc_Request
{
	function Prpc_Notice ($url, $method, $arg)
	{
		$this->url = $url;
		$this->method = $method;
		$this->arg = $arg;
	}
}

/**
	@publicsection
	@public
	@brief
		A pure virtual class.

	This class exists only to derive other message types.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
*/
class Prpc_Response extends Prpc_Message
{
	var $debug;
	var $trace;

	function Prpc_Response ($debug, $trace)
	{
		$this->debug = $debug;
		$this->trace = $trace;
	}
}

/**
	@publicsection
	@public
	@brief
		Message used to pass a function call result.

	Message used to pass a function call result.

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision
*/
class Prpc_Result extends Prpc_Response
{
	function Prpc_Result ($result, $debug, $trace = NULL)
	{
		parent::Prpc_Response ($debug, $trace);
		$this->result = $result;
	}
}

/**
	@publicsection
	@public
	@brief
		Message used to pass a fault

	Message used to pass a fault

	@version
		1.0.0 2003-07-25 - Rodric Glaser
			- Initial revision

		1.0.1 2003-09-26 - Rodric Glaser
			- Add host member
*/
class Prpc_Fault extends Prpc_Response
{
	function Prpc_Fault ($code, $text, $host, $file, $line, $debug, $trace = NULL)
	{
		parent::Prpc_Response ($debug, $trace);
		$this->code = $code;
		$this->text = $text;
		$this->host = $host;
		$this->file = $file;
		$this->line = $line;
	}
}
?>
