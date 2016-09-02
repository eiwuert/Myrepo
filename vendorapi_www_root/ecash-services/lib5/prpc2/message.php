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
class Prpc_Message2
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
class Prpc_Request2 extends Prpc_Message2
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
class SourcePro_Prpc_Message_Call extends Prpc_Request2
{
	function SourcePro_Prpc_Message_Call ($method, $arg)
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
class Prpc_Notice2 extends Prpc_Request2
{
	function Prpc_Notice2 ($url, $method, $arg)
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
class Prpc_Response2 extends Prpc_Message2
{
	var $output;
	var $trace;

	function Prpc_Response2 ($output, $trace)
	{
		$this->output = $output;
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
class SourcePro_Prpc_Message_Return extends Prpc_Response2
{
	function SourcePro_Prpc_Message_Return ($args, $output, $trace = NULL)
	{
		parent::Prpc_Response2 ($output, $trace);
		$this->args = $args;
	}
}

class SourcePro_Prpc_Message_Except extends Prpc_Response2
{
    public $except;

    function __construct ($except, $output = NULL)
    {
        $this->except = $except;
        $this->output = $output;
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
class Prpc_Fault2 extends Prpc_Response2
{
	function Prpc_Fault2 ($code, $text, $host, $file, $line, $debug, $trace = NULL)
	{
		parent::Prpc_Response2 ($debug, $trace);
		$this->code = $code;
		$this->text = $text;
		$this->host = $host;
		$this->file = $file;
		$this->line = $line;
	}
}
?>
