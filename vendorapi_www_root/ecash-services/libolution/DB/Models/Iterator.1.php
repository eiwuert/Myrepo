<?php
/**
 * Iterator for DB_Models_IWritableModel_1 objects
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Models_Iterator_1 implements DB_Models_IIterativeModel_1
{
	/**
	 * Collection of DB_Models_IIterativeModel_1 objects
	 *
	 * @var array
	 */
	protected $models = array();
	
	/**
	 * Current position of the iterator
	 *
	 * @var int
	 */
	protected $position = 0;
	
	/**
	 * @param array $models Models to iterate over
	 * @return void
	 */
	function __construct(array $models)
	{
		$this->position = 0;
		$this->setModels($models);
	}
	
	/**
	 * Validate and set models to iterate over
	 *
	 * @param array $models
	 * @return void
	 */
	protected function setModels(array $models)
	{
		$class = $this->getClassName();
		foreach ($models as $model)
		{
			if (!($model instanceof $class))
			{
				throw new InvalidArgumentException(
					"All items must " . $this->getClassName());
			}
		}
		$this->models = $models;
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::count()
	 */
	public function count()
	{
		return count($this->models);
	}
	
	/**
	 * @return DB_Models_ModelBase 
	 * @see DB_Models_IIterativeModel_1::current()
	 */
	public function current()
	{
		return $this->models[$this->position];
	}
	
	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::currentRawData()
	 */
	public function currentRawData()
	{
		return $this->current()->getColumnData();
	}
	
	/**
	 * @return string 
	 * @see DB_Models_IIterativeModel_1::getClassName()
	 */
	public function getClassName()
	{
		return "DB_Models_IWritableModel_1";
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::key()
	 */
	public function key()
	{
		return $this->position;
	}
	
	/**
	 * @return DB_Models_IWriteableModel 
	 * @see DB_Models_IIterativeModel_1::next()
	 */
	public function next()
	{
		++$this->position;
	}
	
	/**
	 * @return void 
	 * @see DB_Models_IIterativeModel_1::rewind()
	 */
	public function rewind()
	{
		$this->position = 0;
	}
	
	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::toArray()
	 */
	public function toArray()
	{
		return $this->models;
	}
	
	/**
	 * NOT IMPLEMENTED
	 * @return DB_Models_ModelList_1 
	 * @see DB_Models_IIterativeModel_1::toList()
	 */
	public function toList()
	{
		throw new Exception("Method toList() not implmented for containers");
	}
	
	/**
	 * @return bool 
	 * @see DB_Models_IIterativeModel_1::valid()
	 */
	public function valid()
	{	
		return isset($this->models[$this->position]);
	}
}
?>