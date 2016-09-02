<?php
/**
 * General Log Client class
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class GeneralLog_Client
{

	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	/**
	 * Create a general log type and return the ID
	 *
	 * @param GeneralLog_Models_GeneralLogType $model
	 * @param string $name
	 * @return integer
	 */
	protected static function createType(GeneralLog_Models_GeneralLogType $model, $name)
	{
		$model->name = $name;
		$model->insert();

		$pk = $model->getAutoIncrement();
		$id = $model->$pk;
		return $id;
	}
	
	/**
	 * Get General Log Typ prinary key value by name
	 *
	 * @param DB_Database_1 $sql
	 * @param string $name
	 * @return integer
	 */
	protected static function getTypeKeyByName(DB_Database_1 $sql, $name)
	{
		$model = new GeneralLog_Models_GeneralLogType($sql);
		$args = array('name' => $name);
		$cursor = $model->loadAllBy($args);
		if ($cursor->count() > 0)
		{
			$id = $cursor->next()->general_log_type_id;
		}
		else
		{
			$id = self::createType($model, $name);
		}	
		return $id;
	}
	
	/**
	 * Create a general log entry and return the id
	 *
	 * @param DB_Database_1 $sql
	 * @param string $type_name
	 * @param string $detail
	 * @param srting $session_id
	 * @param string $application_id
	 * @return integer
	 */
	public static function createEntry(DB_Database_1 $sql, $type_name, $detail, $session_id = NULL, $application_id = NULL)
	{
		$type_id = self::getTypeKeyByName($sql,$type_name);
		
		$model = new GeneralLog_Models_GeneralLog($sql);
		$model->general_log_type_id = $type_id;
		$model->detail = $detail;
		$model->session_id = $session_id;
		$model->application_id = $application_id;
		
		$model->insert();
		$pk = $model->getAutoIncrement();
		$id = $model->$pk;
		return $id;
	}
	
	/**
	 * Get a count of rows by date adn, optionally by type
	 *
	 * @param DB_Database_1 $sql
	 * @param integer $start_date
	 * @param integer $end_date
	 * @param string $type_name
	 * @return integer
	 */
	public static function getCount(DB_Database_1 $sql, $start_date, $end_date, $type_name = NULL)
	{
		$start_date = date(self::DATE_FORMAT, $start_date);
		$end_date = date(self::DATE_FORMAT, $end_date);
		$where_args = array('start_date' => $start_date, 'end_date' => $end_date);
		$where_statements = array('date_created between :start_date and :end_date');
		if (!is_null($type_name))
		{
			$type_id = self::getTypeKeyByName($sql, $type_name);
			$where_args['type_id'] = $type_id;
			$where_statements[] = 'general_log_type_id = :type_id' ;
		}
		$model = new GeneralLog_Models_GeneralLog($sql);
		$count = $model->countBy($where_args, $where_statements);
		return $count;
	}
	
}
?>