<?php
require_once "phing/types/DataType.php";

/**
 * This Type represents a svn module
 */
class ServerList extends DataType
{
	protected $name;

	protected $servers = array();

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName(Project $p)
	{
		if ($this->isReference())
		{
			return $this->getRef($p)->getName($p);
		}
		return $this->name;
	}

	public function getServers(Project $p)
	{
		if ($this->isReference())
		{
			return $this->getRef($p)->getServers($p);
		}
		return $this->servers;
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
		if (!($o instanceof ServerList))
		{
			throw new BuildException($this->ref->getRefId()." doesn't denote a ServerList. It's a ".get_class($o));
		}
		else
		{
			return $o;
		}
	}

	public function addServer(Server $server)
	{
		$this->servers[] = $server;
	}

	public function __toString()
	{
		return $this->name;
	}

}