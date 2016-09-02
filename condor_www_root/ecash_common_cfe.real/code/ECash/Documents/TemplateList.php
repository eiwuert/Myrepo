<?php
/**
 * ECash_Documents_TemplateList
 * a Template iterator
 * 
 */
class ECash_Documents_TemplateList implements Iterator, Countable
{
	protected $templates;
	protected $pointer;
	public function __construct(array $templates)
	{
		$this->templates = $templates;
		$this->pointer = 0;
	}
	public function rewind()
	{
		$this->pointer = 0;
	}
	public function current()
	{
		return $this->templates[$this->pointer];
	}
	public function next()
	{
		$this->pointer++;
	//	return $this->templates[$this->pointer];
	}
	public function valid()
	{
		return $this->templates[$this->pointer] instanceof ECash_Documents_Template;
	}
	public function key()
	{
		return $this->pointer;
	}
	public function count()
	{
		return count($this->templates);
	}
	
}



?>