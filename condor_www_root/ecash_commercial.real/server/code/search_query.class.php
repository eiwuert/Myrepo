<?php
require_once(SQL_LIB_DIR.'fetch_status_map.func.php');

class Search_Query
{
	private $server;

	public function __construct(Server $server)
	{
		$this->server = $server;
	}

	protected function Return_Appropriate_Statuses($module, $mode) {
//		$status_map = Fetch_Status_Map();

		if ($module == 'funding' && $mode == 'verification') 
		{
			return array(
//				Search_Status_Map('dequeued::verification::applicant::*root', $status_map),
//				Search_Status_Map('follow_up::verification::applicant::*root', $status_map),
//				Search_Status_Map('queued::verification::applicant::*root', $status_map),
//				Search_Status_Map('withdrawn::applicant::*root', $status_map),
			);
		} 
		else if ($module == 'funding' && $mode == 'underwriting') 
		{
			return array(
//				Search_Status_Map('dequeued::underwriting::applicant::*root', $status_map),
//				Search_Status_Map('follow_up::underwriting::applicant::*root', $status_map),
//				Search_Status_Map('queued::underwriting::applicant::*root', $status_map),
			);
		} 
		else if ($module == 'watch') 
		{
			return array();
		} 
		else if ($module == 'collections') 
		{
			return array(
//				Search_Status_Map('pending::external_collections::*root', $status_map),
//				Search_Status_Map('recovered::external_collections::*root', $status_map),
//				Search_Status_Map('sent::external_collections::*root', $status_map),
//				Search_Status_Map('new::collections::customer::*root', $status_map),
//				Search_Status_Map('indef_dequeue::collections::customer::*root', $status_map),
//				Search_Status_Map('current::arrangements::collections::customer::*root', $status_map),
//				Search_Status_Map('hold::arrangements::collections::customer::*root', $status_map),
//				Search_Status_Map('unverified::bankruptcy::collections::customer::*root', $status_map),
//				Search_Status_Map('verified::bankruptcy::collections::customer::*root', $status_map),
//				Search_Status_Map('dequeued::contact::collections::customer::*root', $status_map),
//				Search_Status_Map('follow_up::contact::collections::customer::*root', $status_map),
//				Search_Status_Map('queued::contact::collections::customer::*root', $status_map),
//				Search_Status_Map('ready::quickcheck::collections::customer::*root', $status_map),
//				Search_Status_Map('sent::quickcheck::collections::customer::*root', $status_map),
//				Search_Status_Map('return::quickcheck::collections::customer::*root', $status_map),
//				Search_Status_Map('arrangements::quickcheck::collections::customer::*root', $status_map),
			);
		} 
		else if ($module == 'conversion') 
		{
			return array();
		} 
		else if ($module == 'loan_servicing') 
		{
			return array();
		} 
		else 
		{
			return array();
		}
	}

	public function Find_Applicants($module, $mode, $type_1, $deliminator_1, $criteria_1, $search_option,
									$type_2 = NULL, $deliminator_2 = NULL, $criteria_2 = NULL) //mantis:2016 - $search_option
	{
		$restrictions = NULL;

		if(file_exists(CUSTOMER_LIB . "application_restrict.func.php"))
		{
			require_once(CUSTOMER_LIB . "application_restrict.func.php");
			$restrictions = getSearchControlRestrictions();
		}

		$max_search_retrieval_rows = ECash_Config::getInstance()->MAX_SEARCH_DISPLAY_ROWS + 1;

		/**
		 * Get the columns to display and query based on the company's config
		 */
		if(!$columns = ECash_Config::getInstance()->SEARCH_DISPLAY_COLUMNS)
		{
			$columns = array(
							'application_id' => true,
							'name_first' => true,
							'name_last' => true,
							'ssn' => true,
							'street' => true,
							'city' => true,
							'county' => true,
							'state' 	=> true,
							'application_status' => true,
							'application_balance' => true
							);

		}
		/**
		 * Added the capability to search and view applications across multiple
		 * companies.  The Fraud module automatically supports this feature.
		 */
		$multi_company = ECash_Config::getInstance()->MULTI_COMPANY_ENABLED;

		//fraud module has access to all active companies
		if($module === "fraud") $multi_company = TRUE;
		if($_REQUEST['action'] == 'email_queue_quick_search') $multi_company = FALSE;

		if($multi_company === TRUE)
		{
			$company_where = "c.active_status = 'active'";

			$include_archive = ECash_Config::getInstance()->MULTI_COMPANY_INCLUDE_ARCHIVE;

			if($module == "fraud" || $include_archive !== TRUE)
			{
				$company_where .= " AND c.company_id < 100";
			}
		}
		else
		{
			$company_where = "app.company_id = {$this->server->company_id}";
		}

		// mantis:4313 - add DISTINCT
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
					SELECT DISTINCT
						c.company_id,
						(case
							when c.name_short = 'd1' then '5FC'
							when c.name_short = 'pcl' then 'OCC'
							when c.name_short = 'ca' then 'AML'
							else upper(c.name_short)
						end) as display_short,
						lt.abbreviation as loan_type_abbreviation";

		if($columns['application_id'])
		{
			$query.=',app.application_id ';
		}

		if($columns['name_first'])
		{
			$query.=',rtrim(app.name_first) as name_first ';
		}

		if($columns['name_last'])
		{
			$query.= ',rtrim(app.name_last) as name_last ';
		}

		if($columns['ssn'])
		{
			$query.= ',app.ssn ';
		}

		if($columns['street'])
		{
			$query.= ',app.street ';
		}

		if($columns['city'])
		{
			$query.= ',app.city ';
		}
		
		if($columns['county'])
		{
			$query.= ',app.county ';
		}
		
		if ($columns['state']) 
		{
			$query.= ',app.state
					';
		}

		if ($columns['application_status'])
		{
			$query.=',app.application_status_id
					,app_stat.name as application_status
					,app_stat.name_short as application_status_short
					';
		}

		if ($columns['application_balance'])
		{
			$query.= "
						,IFNULL((
							SELECT
								SUM(ea.amount)
							  FROM
								event_amount ea
								JOIN transaction_register tr USING (transaction_register_id)
						    	JOIN transaction_type tt USING (transaction_type_id)
							  WHERE
								ea.application_id = app.application_id
								AND (transaction_status = 'complete' OR
								(transaction_status = 'pending' AND
								tt.name_short = 'loan_disbursement'))
						  ), 0.00) as application_balance ";
		}

			$query.="
						FROM
						application app
						JOIN loan_type lt USING (loan_type_id)
						JOIN application_status app_stat USING (application_status_id)
						JOIN company c on (c.company_id = app.company_id)
					";


		if($type_1 == 'ach_id' || $type_2 == 'ach_id') //mantis:5500
		{
			$query .= "
					JOIN ach USING (application_id)
				";
		}

		if($type_1 == 'ecld_id' || $type_2 == 'ecld_id')
		{
			$query .= "
					JOIN ecld USING (application_id)
				";
		}

		if($type_1 == 'phone' || $type_2 == 'phone') //mantis:4313
		{
			// GF #16682: left join to fix search issue [benb]
			$query .= "
					LEFT JOIN personal_reference pr ON app.application_id = pr.application_id
				";
		}

		if($restrictions)
		{
			foreach($restrictions['join'] as $join_type => $join_text)
			{
				switch ($join_type)
				{
					case "loan_type":
						//am already included in query!
					break;

					default:
						$query .= "JOIN " . $join_text;
					break;
				}
			}
		}
			$query .= "
						WHERE
							{$company_where}
				";

	if($restrictions)
	{
		foreach($restrictions['where'] as $where_type => $where_text)
		{
			switch ($where_type)
			{
				default:
					$query .= "	AND	{$where_text}";
				break;
			}
		}
	}
		if($type_1 == 'phone') //mantis:4313
		{
			$query .= " AND (" . $this->Build_Search_Criteria('phone_home', $deliminator_1, $criteria_1);
			$query .= " OR " . $this->Build_Search_Criteria('phone_work', $deliminator_1, $criteria_1);
			$query .= " OR " . $this->Build_Search_Criteria('phone_fax', $deliminator_1, $criteria_1);
			$query .= " OR " . $this->Build_Search_Criteria('phone_cell', $deliminator_1, $criteria_1);
			$query .= " OR " . $this->Build_Search_Criteria('ref_phone_home', $deliminator_1, $criteria_1);
			$query .= ")";
		}
		//add search criteria 1
		else
		{
			$query .= " AND " . $this->Build_Search_Criteria($type_1,
			$deliminator_1,
			$criteria_1);
		}

		if($criteria_2 != NULL)
		{
			if($type_2 == 'phone') //mantis:4313
			{
				$query .= " AND (" . $this->Build_Search_Criteria('phone_home', $deliminator_2, $criteria_2);
				$query .= " OR " . $this->Build_Search_Criteria('phone_work', $deliminator_2, $criteria_2);
				$query .= " OR " . $this->Build_Search_Criteria('phone_fax', $deliminator_2, $criteria_2);
				$query .= " OR " . $this->Build_Search_Criteria('phone_cell', $deliminator_2, $criteria_2);
				$query .= " OR " . $this->Build_Search_Criteria('ref_phone_home', $deliminator_2, $criteria_2);
				$query .= ")";
			}
			//add search criteria 2
			else
			{
				$query .= " AND " . $this->Build_Search_Criteria($type_2,
				$deliminator_2,
				$criteria_2);
			}
		}

		$statuses = $this->Return_Appropriate_Statuses($module, $mode);
		
		if (count($statuses)) 
		{
			$query .= " AND app.application_status_id IN ('".implode("','", $statuses)."')";
		}
		
		if(!ECash_Config::getInstance()->ALLOW_UNSIGNED_APPS)
		{
			
			//Statuses to exclude from search results when companies don't have Unsigned Apps feature.
			$excluded_statuses = array();
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('pending::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('confirmed::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('confirm_declined::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('declined::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('disagree::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('preact_confirmed::prospect::*root');
			$excluded_statuses[] = ECash::getFactory()->getReferenceList('ApplicationStatusFlat')->toId('preact_pending::prospect::*root');
			
			
//			$excluded_statuses[] = Search_Status_Map('confirmed::prospect::*root', $status_map);

			
			$query .= " AND app.application_status_id NOT IN ('".implode("','", $excluded_statuses)."')";
		}
		
		//mantis:2016
		$base_query = $query;

		if($search_option == 'on')
		{
			$query .= "HAVING (application_balance != 0) ORDER BY app.ssn ASC, app.date_created DESC";
		}
		else
		{
			$query .= "
					ORDER BY app.date_created DESC
				";
		}
		//end mantis:2016

		$query .= "
					LIMIT {$max_search_retrieval_rows}
				";

		$db = ECash_Config::getMasterDbConnection();

		$search_results = $db->query($query)->fetchAll(PDO::FETCH_OBJ);

		return $search_results;
	}

	private function Build_Search_Criteria($criteria_type, $deliminator, $search_criteria)
	{
		$search_criteria = strtoupper($search_criteria);
		$search_string = "";
		$to_replace = array("(", ")", " ", "-"); //mantis:4313

		switch($criteria_type)
		{
			case "email":	//mantis:4253
			$search_string .= "app.email ";
			$search_criteria = strtolower($search_criteria);
			break;

			case "ach_id":	//mantis:5500
			$search_string .= "ach.ach_id ";
			$search_criteria = strtolower($search_criteria);
			break;

			case "ecld_id":
			$search_string .= "ecld.ecld_id ";
			$search_criteria = strtolower($search_criteria);
			break;

			case "name_last":
			$search_string .= "app.name_last ";
			break;

			case "name_first":
			$search_string .= "app.name_first ";
			break;

			case "social_security_number":
			$search_string .= "app.ssn ";
			$search_criteria = str_replace("-", "", $search_criteria);
			break;

			case "archive_id":
			$search_string .= "app.archive_cashline_id ";
			break;

			case "application_id":
			default:
			if(!IsIntegerValue($search_criteria) || $search_criteria < 0)
			{
				//throw an error here
				//throw new Exception("Incorrect format for Application ID.");
				throw new Number_Format_Exception("Incorrect format for Application ID."); //mantis:4022
			}
			else
			{
				$search_string .= "app.application_id ";
			}
			break;

			case "customer_id":
			default:
			if(!IsIntegerValue($search_criteria) || $search_criteria < 0)
			{
				throw new Number_Format_Exception("Incorrect format for Customer ID.");
			}
			else
			{
				$search_string .= "app.customer_id ";
			}
			break;

			//mantis:4313
			case "phone_home":
			$search_string .= "app.phone_home ";
			$search_criteria = str_replace($to_replace, "", $search_criteria);
			break;

			case "phone_work":
			$search_string .= "app.phone_work ";
			$search_criteria = str_replace($to_replace, "", $search_criteria);
			break;

			case "phone_cell":
			$search_string .= "app.phone_cell ";
			$search_criteria = str_replace($to_replace, "", $search_criteria);
			break;

			case "phone_fax":
			$search_string .= "app.phone_fax ";
			$search_criteria = str_replace($to_replace, "", $search_criteria);
			break;

			case "ref_phone_home":
			$search_string .= "pr.phone_home ";
			$search_criteria = str_replace($to_replace, "", $search_criteria);
			break;
			//end mantis:4313
		}

		switch($deliminator)
		{
			case "starts_with":
			$search_criteria .= "%";
			$search_string .= "like ";
			break;

			case "contains":
			$search_criteria = "%" . $search_criteria . "%";
			$search_string .= "like ";
			break;

			case "is":
			default:
			$search_string .= "= ";
			break;
		}

		$search_criteria = ECash_Config::getMasterDbConnection()->quote($search_criteria);
		//$search_criteria = "'" . $search_criteria . "'";

		$search_string .= $search_criteria;

		return $search_string;
	}
}

?>
