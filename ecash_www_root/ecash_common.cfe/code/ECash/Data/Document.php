<?php

class ECash_Data_Document extends ECash_Data_DataRetriever
{
	public function get_package_list($company_id, $loan_type_id, $getLowerTier = true, $active = true)
	{
		if($getLowerTier)
		{
			$extra_sql = ' and (dp.company_id = '. $company_id . ' or dp.company_id = 0 )
				AND (dp.loan_type_id = ' . $loan_type_id . ' or dp.loan_type_id = 0) 
			and not exists(select * from document_package d where d.name_short = dp.name_short and d.document_package_id != dp.document_package_id  
			and ((dp.loan_type_id = 0 and d.loan_type_id = ' . $loan_type_id . ') or (dp.company_id = 0 and d.company_id = ' . $company_id. ')) and d.active_status = \'active\')';
		}
		else
		{
			$extra_sql = ' and dp.company_id = '. $company_id . ' 
				AND dp.loan_type_id = ' . $loan_type_id . ' ';		
		}
		if($active)
		{
			$extra_sql .= ' and dp.active_status = "active"';
		}
		$query="SELECT
				dp.document_package_id,
				dp.name as parent_desc,
				dlp.document_list_id as child_id,
				dl.name as child_name,
				dl.name_short as child_name_short,
				dl.esig_capable,
				dl.send_method,
				dp.name_short as name_short,
				(select name from document_list where document_list_id = dp.document_list_id) as package_body,
		  		dp.document_list_id as package_body_id,
				dp.active_status,
				dp.company_id,
				dp.loan_type_id
		  FROM
		  		document_package as dp,
				document_list_package as dlp,
				document_list as dl
		  WHERE
				dp.document_package_id = dlp.document_package_id
				AND
				dlp.document_list_id = dl.document_list_id
				$extra_sql
		  ORDER BY dp.name";

		$result = $this->db->query($query); 
		$packages = array();
		while($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if(!isset($packages[$row->parent_desc]))
				$packages[$row->parent_desc] = array();
				
			$packages[$row->parent_desc][] = $row;
		}
		return $packages;
	}


	static public function transformDocumentFromService(stdClass $document)
	{
		$document->event_type = $document->document_event_type;
		$document->document_method = empty($document->document_method) ? $document->document_method_legacy : $document->document_method;
		$document->xfer_date = date('m-d-Y H:i', strtotime($document->date_created));
		$document->alt_xfer_date = date('Y-m-d', strtotime($document->date_modified));
	}

	/**
	 * @todo remove the server parameter
	 * @param Server $server (DEPRECATED)
	 * @param  $document_id
 	 * @return object
	 */
	static public function Get_Document_Log($document_id)
	{
		$document = ECash::getFactory()->getDocumentClient()->findDocumentById($document_id);

		self::transformDocumentFromService($document);
		$query = "
					SELECT
						document_list.document_api,
						document_list.document_list_id,
						document_list.name_short as name,
						document_list.name as description,
						document_list.required,
						document_list.send_method,
						agent.agent_id,
						if (agent.login is NULL, 'unknown', agent.login) as login
					FROM
						document_list
						LEFT JOIN agent ON (agent.agent_id = ?)
					WHERE
						document_list.name_short = ?
						AND document_list.company_id = ?
						AND document_list.system_id = ?
					";

		$result = DB_Util_1::querySingleRow(ECash::getMasterDb(), $query, array(
			$document->agent_id,
			$document->document_list_name,
			ECash::getCompany()->getCompanyId(),
			ECash::getSystemId()
		));

		return (object)array_merge((array)$document, $result);
	}
}

?>
