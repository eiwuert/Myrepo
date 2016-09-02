<?php
Class StopWatch
{
	var $on = NULL;
	var $start = NULL;
	var $stop = NULL;

	function __Construct ()
	{
		$this->Start ();
	}

	function __Destruct ()
	{
		$this->Stop ();
	}

	function Start ()
	{
		$this->on = TRUE;
		$this->start = explode (" ", microtime());
	}

	function Stop ()
	{
		if ($this->on)
		{
			$this->stop = explode (" ", microtime());
			$this->on = FALSE;

			$this->delta = ($this->stop[0] - $this->start[0]) + ($this->stop[1] - $this->start[1]);
			return $this->delta;
		}
		return FALSE;
	}
}

class Timer
{
	protected $watch;

	function __Construct ($key = '_')
	{
		$this->watch = array ();
		$this->Start ($key);
	}

	function __Destruct ()
	{}

	function Start ($key = '_')
	{
		$this->watch[$key] = new StopWatch();
	}

	function Stop ($key = '_')
	{
		$delta = $this->watch[$key]->Stop();
		return $delta;
	}

	function Show ($key = '_')
	{
		$this->Stop ($key);
		$this->Display ($key);
	}

	function Delta ($key = '_')
	{
		return $this->watch[$key]->delta;
	}

	function Display ($key = '_')
	{
		echo str_pad("* timer ($key)",30," ", STR_PAD_RIGHT)."= ".$this->watch[$key]->delta."\n";
	}

	function Remove ($key)
	{
		unset ($this->watch[$key]);
	}
}
?>
