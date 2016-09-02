<?php

/**
 * The application model
 *
 * Revision History:
 *	04.13.2009 - bszerdy - Added the getApplicationFull function.
 *  *
 * @package Models
 * @author  Russell Lee <russell.lee@sellingsource.com>
 */
class ECash_Models_ApplicationVersion extends ECash_Models_WritableModel
{
	/**
	 * The columns in the model
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return array(
			'date_created',
			'application_id',
			'version',
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'application_version';
	}

	/**
	 * The primary key columns
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array(
			'application_id',
		);
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return NULL;
	}
	

	/**
	 * Translates table data after loading data from the db and before setting it into the model.
	 *
	 * @param array $data - The data that will be set in the model
	 * @return array
	 */
	public function setColumnData(array $data)
	{
		$data['date_created'] = strtotime($data['date_created']);
		parent::setColumnData($data);
	}

	/**
	 * Translates model data prior to saving it into the database.
	 *
	 * @return array
	 */
	public function getColumnData()
	{
		$data = parent::getColumnData();
		$data['date_created'] = date('Y-m-d H:i:s', $data['date_created']);
		return $data;
	}

	/**
	 * Loads model update while obtaining a READ, WRITE lock on that row (index.)
	 *
	 * You should be in a transaction when calling this method.
	 *
	 * @param int $application_id
	 * @return bool
	 */
	public function loadForUpdate($application_id)
	{
			$where_args = array('application_id' => $application_id);
			$db = $this->getDatabaseInstance();

			$query = "
				SELECT *
				FROM " . $db->quoteObject($this->getTableName()) . "
				" . self::buildWhere($where_args, TRUE, $db) . "
				LIMIT 1 FOR UPDATE
			";
			return $this->loadPrepared($db, $query, $where_args);
	}
}

?>
