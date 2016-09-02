<?php
/**
 * Adapter class for using libolution models as a metadata
 * and connection provider for Zend DB tables
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class DB_Zend_Adapter_Table extends Zend_Db_Table_Abstract
{
	/**
	 * Constructor converts information from the supplied model to create
	 * a fully metadata aware Zend_Db_Table object
	 *
	 * @param DB_Models_WritableModel_1 $model
	 * @return void
	 */
	public function __construct(DB_Models_WritableModel_1 $model)
	{
		parent::__construct($this->getConfigFromModel($model));
	}

	/**
	 * Create a config array based on the model supplied
	 *
	 * @param DB_Models_WritableModel_1 $model
	 * @return array
	 */
	protected function getConfigFromModel(DB_Models_WritableModel_1 $model)
	{
		$config = array(
			// Create a Zend_Adapter using the libolution DB adapter
			self::ADAPTER => new DB_Zend_Adapter_Connection($model->getDatabaseInstance()),
			// Get the table name for the model
			self::NAME => $model->getTableName(),
			// Get the primary key columns array
			self::PRIMARY => $model->getPrimaryKey(),
			// Since we are dealing wit PDO MySQL return a boolean based on the existence
			// of an auto-increment column in the model 
			self::SEQUENCE => (bool)$model->getAutoIncrement(),
			// Create an associative array with the columns as the keys for column metadata 
			self::METADATA => array_fill_keys($model->getColumns(), NULL)
		);
		return $config;
	}
}

?>