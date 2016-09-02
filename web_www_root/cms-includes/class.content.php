<?php

class Content
{
	protected $_string_table = array();
	protected $_next = false;
	public function __construct($_db)
	{
		$this->_db = $_db;
		$this->_string_table = array();
	}
	
	public function getStringTable()
	{
		return $this->_string_table;
	}
	
	protected function setString($code, $text)
	{
		$this->_string_table[$code] = $text;
	}
	
	protected function getContent($params)
	{
		return true;
	}
	
	public function next()
	{
		return $this->_next;
	}
}

?>