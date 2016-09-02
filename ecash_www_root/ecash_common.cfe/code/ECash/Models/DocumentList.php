<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_DocumentList extends ECash_Models_ObservableWritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;
		public $System;
		public function getColumns()
		{
			static $columns = array(
			'date_modified', 'date_created', 'active_status',
			'company_id', 'loan_type_id', 'document_list_id', 'name', 'name_short',
			'required', 'esig_capable', 'system_id', 'send_method',
			'document_api', 'doc_send_order', 'doc_receive_order',
			'only_receivable'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('document_list_id');
		}
		public function getAutoIncrement()
		{
			return 'document_list_id';
		}
		public function getTableName()
		{
			return 'document_list';
		}
		public function getColumnID()
		{
			return 'document_list_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
		public function getByNameShort($company_id, $loan_type_id, $name_short)
		{
			$where_args['active_status'] = 'active';
			$where_args['loan_type_id'] = $loan_type_id;
			$where_args['company_id'] = $company_id;
			$where_args['name_short'] = $name_short;
			$extra_sql = ' or ((company_id = 0 or company_id = :company_id) and loan_type_id = 0 and name_short = :name_short)
				and not exists(select * from document_list d where d.name_short = :name_short and d.document_list_id != l.document_list_id  
				and ((l.loan_type_id = 0 and d.loan_type_id = :loan_type_id) or (l.company_id = 0 and d.company_id = :company_id)))';
			
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */				
 			SELECT
				* from document_list l " . self::buildWhere($where_args) . $extra_sql . "  order by l.name_short ";
	//		echo '<pre>' . $query . print_r($where_args,true) . '</pre>';
			$row = DB_Util_1::querySingleRow($this->getDatabaseInstance(), $query, $where_args);

			if ($row !== FALSE)
			{
				$this->fromDbRow($row);
			}
			return FALSE;
		}
	}
?>
