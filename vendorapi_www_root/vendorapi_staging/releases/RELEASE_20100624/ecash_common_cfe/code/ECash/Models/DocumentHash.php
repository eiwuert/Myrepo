<?php

/**
 * The document hash model
 *
 * Revision History:
 *	05.19.2009 - rayl - Added the gdocument hash model
 *  *
 * @package Models
 * @author  Russell Lee <raymond.lopez@sellingsource.com>
 */
class ECash_Models_DocumentHash extends ECash_Models_WritableModel
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
			'document_hash_id',
			'application_id',
			'company_id',
			'document_list_id',
			'hash',
			'active_status'
		);
	}

	/**
	 * The name of the model table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'document_hash';
	}

	/**
	 * The primary key columns
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		return array(
			'document_hash_id',
		);
	}

	/**
	 * The auto increment column
	 *
	 * @return string
	 */
	public function getAutoIncrement()
	{
		return 'document_hash_id';
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
		$this->loadFromService($data);
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
	 * Loads data from the applicaiton service
	 *
	 *@param array $data - array of data that can be set in column data
	 *@return array
	 */
	protected function loadFromService($data)
	{
		if (!empty($data['application_id']))
		{
			return $data;
		}

		$doc_list_model = ECash::getFactory()->getReferenceModel("DocumentListRef");
		$doc_name = $doc_list_model->loadByKey($data['document_list_id'])->name;

		$args = array(
			'application_id' => $data['application_id'],
			'document_list_name' => $doc_name
		);

		$hash_service = ECash::getFactory()->getDocumentHashClient();
		$service_data = $hash_service->find($args);

		if (empty($service_data))
		{
			return $data;
		}

		$data['date_created']		= $service_data['data_created'];
		$data['date_modified']		= $service_data['date_modified'];
		$data['document_hash_id']	= $service_data['document_'];
		$data['application_id']		= $service_data['application_id'];
		$data['company_id']			= $service_data['company_id'];
		$data['document_list_id']	= $service_data['document_list_id'];
		$data['hash']				= $service_data['hash'];
		$data['active_status']		= $service_data['active_status'];

		return $data;
	}

	/**
	 * Deletes all entries in the document hash that are older than the given $timestamp.
	 *
	 * If the company id is not provided then rows for all companies will be deleted.
	 *
	 * @param int $timestamp
	 * @param int $company_id
	 * @return int
	 */
	public function removeEntriesBefore($timestamp, $company_id = NULL)
	{
		$date = date('Y-m-d H:i:s', $timestamp);
		$query = 'CALL sp_remove_document_hash_entries_before_date ("'.$date.'");';
		$mssql_db = ECash::getAppSvcDB();
		return $mssql_db->query($query);
	}
}

?>
