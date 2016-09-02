<?php
/**
 * Defines the PostBlackboxProcessor Class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * Performs post-processing cleanup type work after Blackbox has been run.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class BlackboxDataProcessor 
{
	/**
	 * Expire OLP applications.
	 *
	 * Blackbox may suggest, by adding application_ids to data->expire_olp,
	 * that certain apps should be expired. This method will expire those apps.
	 *
	 * @throws Exception
	 * 
	 * @param Blackbox_Data $data data passed to blackbox.
	 * @param MySQL_4 object
	 * @param string name of the database to use (olp, olp_rc, etc)
	 * 
	 * @return void
	 */
	static public function expireApplicationsOLP(Blackbox_Data $data, $olp_db = NULL, $olp_database_name = '')
	{
		if (!$data->expire_olp) return;

		$query = "UPDATE application
			SET application_type = 'EXPIRE'
			WHERE application_id IN (".implode(', ', $expire_olp).")";
		$result = $olp_db->Query($olp_database_name, $query);

	}

	/**
	 * Expire LDB applications.
	 * 
	 * Blackbox may suggest, by adding expire_ldb and expire_ldb_name_short properties to data,
	 * that LDB applications should be marked as expired. This method expires those apps.
	 * 
	 * @param Blackbox_Data $data object passed to the Blackbox process
	 * @param OLP_LDB $olp_ldb ...
	 *
	 * @throws Exception
	 * 
	 * @return void
	 */
	static public function expireApplicationsLDB(Blackbox_Data $data, OLP_LDB $olp_ldb)
	{
		if (!$data->expire_ldb) return;

		if (!$data->expire_ldb_name_short)
		{
			throw new Exception('expire_ldb_name_short missing, cannot expire applications.');
		}


		$ldb_writer = Setup_DB::Get_Instance('mysql', BFW_MODE, $data->expire_ldb_name_short);

		$expired_status_id = array_pop($olp_ldb->Status_Glob('/prospect/expired'));

		if (!$expired_status_id)
		{
			throw new Exception('cannot expire applications without expired_status_id');
		}

		$query = "
			UPDATE 
				application
			SET 
				modifying_agent_id = (
						SELECT agent_id
						FROM agent
						WHERE login = 'olp' AND active_status = 'active'
						),
			   application_status_id = {$expired_status_id}
			WHERE 
				application_id IN (".implode(', ', $data->expire_ldb).")";

		$ldb_writer->Query($query);
	}
}
?>
