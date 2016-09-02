<?php

require_once "phing/types/DataType.php";

/**
 * This Type represents a svn module
 */
class Server extends DataType
{
	protected $host;
	protected $port = 22;
	protected $username;

	public function getHost(Project $p)
	{
		if ($this->isReference())
		{
			return $this->getRef($p)->getHost($p);
		}
		return $this->host;
	}

	public function setHost($host)
	{
		$this->host = $host;
	}

	public function getPort(Project $p)
	{
		if ($this->isReference())
		{
			return $this->getRef($p)->getPort($p);
		}
		return $this->port;
	}

	public function setPort($port)
	{
		$this->port = $port;
	}

	public function setUsername($user)
	{
		$this->username = $user;
	}

	public function getUsername(Project $p)
	{
		if ($this->isReference())
		{
			return $this->getRef($p)->getUsername($p);
		}
		return $this->username;
	}

	public function getRef(Project $p)
	{
		if (!$this->checked)
		{
			$stk = array();
			array_push($stk, $this);
			$this->dieOnCircularReference($stk, $p);
		}
		$o = $this->ref->getReferencedObject($p);
		if (!($o instanceof Server))
		{
			throw new BuildException($this->ref->getRefId()." doesn't denote a Server");
		}
		else
		{
			return $o;
		}
	}

	public function asString(Project $p)
	{
		$user = $this->getUsername($p);
		$port = $this->getPort($p);
		$host = $this->getHost($p);

		$str = '';
		if (!empty($user))
		{
			$str .= "$user@";
		}
		$str .= "$host";
		if (!empty($port))
		{
			$str.=":$port";
		}
		return $str;
	}

	public function __toString()
	{
		return $this->name;
	}

}