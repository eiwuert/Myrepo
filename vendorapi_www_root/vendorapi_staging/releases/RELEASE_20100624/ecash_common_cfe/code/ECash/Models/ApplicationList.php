<?php

/**
 * A hybrid List model that loads the application models from the Application Service rather than
 * the Database.
 *
 * @author Brian Ronald <brian.ronald@sellingsource.com>
 * @package Ecash.Models
 */
class ECash_Models_ApplicationList extends ECash_Models_HybridIterativeModel
{

	public function getClassName()
	{
		return 'ECash_Models_Application';
	}

	public function getTableName()
	{
		return 'application';
	}

	/**
	 * Generates a list of applications for the Iterator
	 *
	 * application_id, customer_id, and ssn are all valid search methods
	 * to load the application list.
	 *
	 * @param <array> $where_args
	 */
	public function loadBy($where_args = array())
	{
		$this->model_list = array();

		$app_client = ECash::getFactory()->getWebServiceFactory()->getWebService('application');

		if(isset($where_args['customer_id']) && ! empty($where_args['customer_id']))
		{
			$criteria = array(
				array(	'field'          => 'customer_id',
						'strategy'       => 'is',
						'searchCriteria' => $where_args['customer_id']));
		}
		elseif(isset($where_args['application_id']))
		{
			$criteria = array(
				array(	'field'          => 'application_id',
						'strategy'       => 'is',
						'searchCriteria' => $where_args['application_id']));
		}
		elseif(isset($where_args['ssn']))
		{
			$criteria = array(
				array(	'field'          => 'social_security_number',
						'strategy'       => 'is',
						'searchCriteria' => $where_args['ssn']));
		}
		elseif(isset($where_args['track_id']))
		{
			$criteria = array(
				array(	'field'          => 'track_id',
						'strategy'       => 'is',
						'searchCriteria' => $where_args['track_id']));
		}

		$records = $app_client->applicationSearch($criteria, 100);

		/**
		 * One where_arg filter here... We'll need to reconstruct this if other filters are required.
		 */
		if(count($records) > 0)
		{
			foreach($records as $result)
			{
				if(isset($where_args['application_status']))
				{
					if($result->application_status_name == $where_args['application_status'])
					{
						$this->model_list[] = $result->application_id;
					}
				}
				else
				{
					$this->model_list[] = $result->application_id;
				}
			}
		}
	}

	/**
	 * Creates an instance of the class based off of the current application_id
	 * in the iterator.
	 *
	 * @param string $application_id
	 * @return ECash_DB_Models_WritableModel
	 */
	protected function createInstance($application_id)
	{
		$model_name = $this->getClassName();
		$model = new $model_name($this->getDatabaseInstance());
		$model->loadBy(array('application_id' => $application_id));

		return $model;
	}

}
?>
