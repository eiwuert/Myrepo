<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_DocumentListList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_DocumentList';
		}

		public function getTableName()
		{
			return 'document_list';
		}

		public function getdocs($company_id, $loan_type_id, $where_args = array(), $addtl_sort = "send", $getLowerTier = true, $active = true)
		{
			if($active)
				$where_args['active_status'] = 'active';
			$orig_where = $where_args;
			switch ($addtl_sort) 
			{
				case "send":
					$addtl_sort = " l.doc_send_order, ";
					break;
	
				case "receive":
					$addtl_sort = " l.doc_receive_order, ";
					break;
	
				default:
					$addtl_sort .= ($addtl_sort) ? ", " : "" ;
	
			}
			
			$where_args['loan_type_id'] = $loan_type_id;
			$where_args['company_id'] = $company_id;
			$extra_sql = '';
			if($getLowerTier)
			{
				$extra_sql = ' or ((company_id = 0 or company_id = :company_id) and loan_type_id = 0 '. self::buildWhereClause($orig_where, true, null, false) .')
				and not exists(select * from document_list d where d.name_short = l.name_short and d.document_list_id != l.document_list_id  
				and ((l.loan_type_id = 0 and d.loan_type_id = :loan_type_id) or (l.company_id = 0 and d.company_id = :company_id)) '. self::buildWhereClause($orig_where, true, null, false) . ')';
			}

			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */				
 			SELECT
				* from document_list l " . self::buildWhereClause($where_args, true, null, true) . $extra_sql . "  order by" . $addtl_sort . "l.name_short ";
			$this->statement = $this->getDatabaseInstance()->queryPrepared($query, $where_args);
		
		}
		
		public function getReferenceData($company_id, $loan_type_id, $getLowerTier = true, $active = true)
		{
			 $this->getdocs($company_id, $loan_type_id, array('only_receivable' => 'no'), $getLowerTier, $active);
		}
		public function getRecievable($company_id, $loan_type_id, $getLowerTier = true, $active = true)
		{
			 $this->getdocs($company_id, $loan_type_id, array('required' => 'yes'), 'receive', $getLowerTier, $active);
		}
		public function getSendable($company_id, $loan_type_id, $getLowerTier = true, $active = true)
		{
			$this->getdocs($company_id, $loan_type_id, array(), 'send', $getLowerTier, $active);
		//	$where_args = array('loan_type_id' => $loan_type_id);
		//	if($getLowerTier)
		//	{
		//		$extra_sql = ' or ((company_id = 0 or company_id =' . $company_id . ') and loan_type_id = 0)';
		//	}
		//	else
		//	{
		//		$extra_sql = ' and company_id = ' . $company_id;
		//	}
		//	$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */			
 		//	SELECT	* from document_list l" . self::buildWhere($where_args) . $extra_sql;
		//	
		//	$this->statement = $this->getDatabaseInstance()->queryPrepared($query, $where_args);
		}
	}
?>
