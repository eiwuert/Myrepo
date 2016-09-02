<?php

/**
 * Provides a customized iterator interface for eCash Models.
 *
 * @package DB
 * @author  Adam Englander <adam.englander@sellingsource.com>
 */
interface ECash_Models_IIterativeModel extends DB_Models_IIterativeModel_1
{
	/**
	 * Returns a string to specify the table name to search.
	 *
	 * @return string
	 */
	public function getTableName();

	/**
	 * selects from the model's table based on the where args
	 *
	 * @param array $where_args
	 * @return bool
	 */
	public function loadBy(array $where_args = array());

	/**
	 * this loadBy uses the ECash DB Util file to accept a different operator
	 *	in the where clause.
	 *
	 * @example of the call:
	 *  $my_model = ECash::getFactory()->getModel("Mine");
	 *  $my_model->loadByModified(array(
	 *		'date_created' => array('>' => '2009-09-21'
	 *  ));
	 * 
	 * @param array $where_args
	 * @return bool
	 */
	public function loadByModified(array $where_args = array());

	/**
	 * Sets the LIMIT of the SQL statement
	 * 
	 * @param integer $limit
	 */
	public function setLimit($limit = NULL);

	/**
	 * The ORDER BY of the SQL statement and the optional LIMIT
	 *
	 * @param array $order_by
	 * @param integer $limit
	 */
	public function orderBy(array $order_by);
}

?>
