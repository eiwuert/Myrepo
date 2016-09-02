<?php
/**
 * ECash_Documents_DocumentList
 * a Document iterator
 * 
 */
class ECash_Documents_DocumentList implements Iterator, Countable
{
	protected $documents;
	protected $pointer;
	public function __construct(array $documents)
	{
		$this->documents = $documents;
		$this->pointer = 0;
	}
	public function rewind()
	{
		$this->pointer = 0;
	}
	public function current()
	{
		return $this->documents[$this->pointer];
	}
	public function next()
	{
		$this->pointer++;
	}
	public function valid()
	{
		return (!empty($this->documents[$this->pointer]) && $this->documents[$this->pointer] instanceof ECash_Documents_Document);
	}
	public function key()
	{
		return $this->pointer;
	}
	public function count()
	{
		return count($this->documents);
	}
	
	
}



?>