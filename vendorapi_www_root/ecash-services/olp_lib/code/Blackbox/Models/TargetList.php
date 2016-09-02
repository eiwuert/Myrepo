<?php
/**
 * Returns a list of Target models.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_TargetList extends Blackbox_Models_IterativeModel 
{
	/**
	 * Returns the class name of the object.
	 *
	 * @return string
	 */
	public function getClassName()
	{
		return 'Blackbox_Models_TargetList';
	}
	
	/**
	 * Creates an instance of the Blackbox_Models_Target objects.
	 *
	 * @param array $db_row
	 * @return Blackbox_Models_Target
	 */
	public function createInstance(array $db_row)
	{
		$item = new Blackbox_Models_Target();
		$item->fromDbRow($db_row);
		return $item;
	}
	
	/**
	 * Returns a list of Target objects.
	 *
	 * @param array $where_args
	 * @return Blackbox_Models_TargetList
	 */
	public static function getBy(array $where_args)
	{
		$query = "SELECT * FROM target " . self::buildWhere($where_args);

		$list = new self();
		$list->statement = $list->getDatabaseInstance()->queryPrepared($query, $where_args);
		return $list;
	}
}
