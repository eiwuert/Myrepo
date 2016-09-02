<?php
/**
	An acquired or artificial quality.
*/

class SourcePro_Entity_Attribute_Property extends SourcePro_Entity_Attribute_Base
{
	protected $m_get;
	protected $m_set;

	function __construct ($owner, $name, $get = NULL, $set = NULL)
	{
		parent::__construct($owner, $name);
		$this->m_get = $get;
		$this->m_set = $set;
	}

	function __destruct ()
	{
		parent::__destruct();
	}

	function _get ()
	{
		if (! $this->m_get)
		{
			throw new SourcePro_Exception("Entity property ({$this->m_name}) does not support read", 1000);
		}

		if (! method_exists($this->m_owner, $this->m_get))
		{
			throw new SourcePro_Exception("Entity property ({$this->m_name}) getter ({$this->m_get}) does not exist", 1000);
		}

		try
		{
			return $this->m_owner->{$this->m_get}($this->m_name);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	function _set ($value)
	{
		if (! $this->m_set)
		{
			throw new SourcePro_Exception("Entity property ({$this->m_name}) does not support write", 1000);
		}

		if (! method_exists($this->m_owner, $this->m_set))
		{
			throw new SourcePro_Exception("Entity property ({$this->m_name}) setter ({$this->m_set}) does not exist", 1000);
		}

		try
		{
			$this->m_owner->{$this->m_set}($this->m_name, $value);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	function _call ($args)
	{
		throw new SourcePro_Exception("Entity property ({$this->m_name}) used as method", 1000);
	}
}

?>
