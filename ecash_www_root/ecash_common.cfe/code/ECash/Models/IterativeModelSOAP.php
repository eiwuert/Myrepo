<?php

class ECash_Models_IterativeModelSOAP extends ECash_Models_IterativeModel
{
	/**
	 * @var DB_Models_WritableModelSOAP
	 */
	protected $base_soap_model;
	
	protected $iterator = NULL;

	/**
	 * Constructor defines the base SOAP model
	 *
	 * @param DB_Models_WritableModelSOAP $db
	 * @return void
	 */
	public function __construct(DB_Models_WritableModelSOAP $base_soap_model)
	{
		$this->base_soap_model = $base_soap_model;
	}

	/**
	 * @param array $where_args
	 * @return bool
	 * @see ECash_DB_Models_IterativeModel#loadBy
	 */
	public function loadBy(array $where_args = array())
	{
		$this->iterator = $this->base_soap_model->loadAllBy($where_args);
	}

	/**
	 * UNSUPPORTED CURRENTLY
	 * @param array $where_args
	 * @return bool
	 * @see ECash_DB_Models_IterativeModel#loadByModified
	 */
	public function loadByModified(array $where_args = array())
	{
		throw new Exception("loadByModified not implemented");
	}

	/**
	 * Sets the LIMIT of the SQL statement
	 * 
	 * @param integer|string|NULL $limit
	 * @see ECash_DB_Models_IterativeModel#setLimit
	 */
	public function setLimit($limit = NULL)
	{
		$this->base_soap_model->setLimit($limit);
	}

	/**
	 * @param array $order_by
	 * @see ECash_DB_Models_IterativeModel#orderBy
	 */
	public function orderBy(array $order_by)
	{
		$this->order_by = $order_by;
	}

	/**
	 * Not supported
	 *
	 * @param string $db_inst
	 * @return DB_IConnection_1
	 */
	public function getDatabaseInstance($db_inst = NULL)
	{
		throw new Exception("getDatabaseInstance is not supported in SOAP models");
	}

	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::currentRawData()
	 */
	public function currentRawData()
	{
		return $this->iterator->currentRawData();
	}
	
	/**
	 * @return string 
	 * @see DB_Models_IIterativeModel_1::getClassName()
	 */
	public function getClassName()
	{
		return "DB_Models_WritableModelSOAP";
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::count()
	 */
	public function count()
	{
		return  $this->iterator->count();
	}
	
	/**
	 * @return DB_Models_IIterativeModel_1
	 * @see DB_Models_IIterativeModel_1::current()
	 */
	public function current()
	{
		return $this->iterator->current();
	}
	
	/**
	 * @return int 
	 * @see DB_Models_IIterativeModel_1::key()
	 */
	public function key()
	{
		return $this->iterator->key();
	}
	
	/**
	 * @return DB_Models_IWriteableModel 
	 * @see DB_Models_Iterator_1::next()
	 */
	public function next()
	{
		return $this->iterator->next();
	}
	/**
	 * @return void 
	 * @see DB_Models_IIterativeModel_1::rewind()
	 */
	public function rewind()
	{
		return $this->iterator->rewind();
	}
	
	/**
	 * @return array 
	 * @see DB_Models_IIterativeModel_1::toArray()
	 */
	public function toArray()
	{
		return $this->iterator->toArray();
	}
	
	/**
	 * @return DB_Models_ModelList_1 
	 * @see DB_Models_IIterativeModel_1::toList()
	 */
	public function toList()
	{
		return $this->iterator->toList();
	}
	
	
	/**
	 * @return bool 
	 * @see DB_Models_IIterativeModel_1::valid()
	 */
	public function valid()
	{	
		return $this->iterator->valid();
	}

        public function getTableName()
        {
                return ''; 
        }
	
}

?>
