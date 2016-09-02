<?php

/**
 * Mocked up OLP_IModel implementation because PHPUnit's mock framework mucks up
 * __get and __set methods.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_MockIModel extends stdClass implements OLP_IModel
{
	/**
	 * Implemented for the model interface.
	 * @return int Rows deleted.
	 */
	public function delete()
	{
		return 1;
	}
	
	/**
	 * Implemented for the model interface.
	 *
	 * @throws Exception
	 * @param array $where_args Where arguments.
	 * @return void
	 */
	public function loadAllBy(array $where_args = array())
	{
		throw new Exception("not implemented");
	}
	
	/**
	 * Implemented for the model interface.
	 *
	 * @throws Exception
	 * @param array $where_args
	 * @return void
	 */
	public function loadBy(array $where_args)
	{
		throw new Exception("not implemented");
	}
	
	/**
	 * Implemented for the model interface.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function save()
	{
		throw new Exception("not implemented");
	}
}

?>