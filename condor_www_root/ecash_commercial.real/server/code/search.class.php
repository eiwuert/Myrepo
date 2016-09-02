<?php

require_once(SERVER_CODE_DIR . 'cashline_view.class.php');
require_once(CUSTOMER_LIB.'list_available_criteria_types.php');

class Search
{
	private $request;
	private $cashline_view;
	private $max_search_display_rows;
	private $module;
	private $timer;

	public function __construct()
	{
		$this->request = ECash::getRequest();
		$this->module = ECash::getModule()->Get_Active_Module();
		$this->timer = ECash::getMonitoring()->getTimer();
	}

	public function Search_Now($no_actions = false)
	{
		
		$available_criteria_types = list_available_criteria_types();
		if(isset($available_criteria_types['email']))
		{
			//mantis:4284
			if (   ($this->request->criteria_type_1 == 'email' && $this->request->search_deliminator_1 == 'contains')
			|| ($this->request->criteria_type_2 == 'email' && $this->request->search_deliminator_2 == 'contains'))
			{
				$data->search_message = 'Cannot use "contains" with Email search.';
				ECash::getTransport()->Set_Data($data);
				ECash::getTransport()->Add_Levels('search');
				$this->timer->stopTimer('searching_for_application');
				return;
			}
		}

		/* search expects the following from the request form:
		//
		// criteria_type_1 (application_id/name_last/name_first/social_security_number)
		// search_deliminator_1 (is/contains/starts_with) -- in honor of brilliance
		// search_criteria_1
		//
		// optionally:
		// criteria_type_2
		// search_deliminator_2
		// search_criteria_2
		//
		*/
		
		//add search criteria 1
		$this->request->search_criteria_1 = trim($this->request->search_criteria_1);
		if(strlen($this->request->search_criteria_1) == 0)
		{
			//return with no data
			$data->search_message = "Primary criterion not specified";
			$this->Save_Search_Data($data, $this->request->module, $this->request->mode);
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
			$this->timer->stopTimer('searching_for_application');
			return;
		}

		$max_search_display_rows = eCash_Config::getInstance()->MAX_SEARCH_DISPLAY_ROWS;
		if(empty($max_search_display_rows))
		{
			$max_search_display_rows = 500; // Set a limit of some sort if it's not configured.
		}
		$this->max_search_display_rows = $max_search_display_rows;

		$this->timer->startTimer('searching_for_application');

		$count = 0; // # of records found
		$data = new stdClass();

		$search_query = new Search_Query(ECash::getServer());

		//add search criteria 2
		$module = isset($this->request->module) ? $this->request->module : null;
		$mode = isset($this->request->mode) ? $this->request->mode : null;
		$option = isset($this->request->search_option) ? $this->request->search_option : null;
		try
		{
			$this->request->search_criteria_2 = trim($this->request->search_criteria_2);
			if(strlen($this->request->search_criteria_2) > 0 && strlen($this->request->criteria_type_2) > 0)
			{
				$data->search_results =
					$search_query->Find_Applicants($module, $mode,
										$this->request->criteria_type_1,
									$this->request->search_deliminator_1,
									$this->request->search_criteria_1,
									$option,
									$this->request->criteria_type_2,
									$this->request->search_deliminator_2,
									$this->request->search_criteria_2);
			}
			else
			{
				$data->search_results =
					$search_query->Find_Applicants($module, $mode,
										$this->request->criteria_type_1,
									$this->request->search_deliminator_1,
									$this->request->search_criteria_1,
									$option); //mantis:2016
			}
		}
		catch(Number_Format_Exception $e)
		{
			//set the results to an empty array and display a message
			$data->search_results = array();
			$data->search_message = $e->getMessage();
		}
		$this->Save_Search_Data($data, $module, $mode);
		$count = count($data->search_results);
		// we need to prevent client from displaying too large of a result set, otherwise
		// the PHP memory limit could be exceeded;
		if(!empty($data->search_results) && count($data->search_results) > $this->max_search_display_rows)
		{
			$data->search_message = "Your search would return more than " . $this->max_search_display_rows . " results. Please narrow your search.";
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
			$this->timer->stopTimer('searching_for_application');
			return;
		}
		// if there's exactly one result, show the data for that application
		elseif(!empty($data->search_results) && count($data->search_results) == 1)
		{
			$this->request->application_id = $data->search_results[0]->application_id;
			$this->request->company_id = $data->search_results[0]->company_id;
			$result = $this->Show_Applicant($data->search_results[0]->application_id, $no_actions);
			if($result === FALSE) $count = 0;
		}
		else
		{
			ECash::getTransport()->Set_Data($data);
			ECash::getTransport()->Add_Levels('search');
		}
		$this->timer->stopTimer('searching_for_application');

		$agent = ECash::getAgent();
		$agent->getTracking()->add('search_' . ECash::getRequest()->module . '_' . ECash::getRequest()->mode, $application_id);
		return $count;
	}

	private function Save_Search_Data($data, $module, $mode)
	{
		
		//Retrieve criteria types available - use for elimination of values - GF #14379
		$available_criteria_types = list_available_criteria_types();
		
		//save the criteria for display
		$data->search_criteria = array(
		'criteria_type_1'       => isset($this->request->criteria_type_1) ? $this->request->criteria_type_1 : null,
		'search_deliminator_1'	=> isset($this->request->search_deliminator_1) ? $this->request->search_deliminator_1 : null,
		'search_criteria_1'     => isset($this->request->search_criteria_1) ? $this->request->search_criteria_1 : null,
		'criteria_type_2'       => isset($this->request->criteria_type_2) ? $this->request->criteria_type_2 : null,
		'search_deliminator_2'	=> isset($this->request->search_deliminator_2) ? $this->request->search_deliminator_2 : null,
		'search_criteria_2'		=> isset($this->request->search_criteria_2) ? $this->request->search_criteria_2 : null,
		'search_option' 		=> isset($this->request->search_option) ? $this->request->search_option : null,
		'search_option_checked'	=> (isset($this->request->search_option) && $this->request->search_option == "on") ? "CHECKED" : "");

		//Purge non available search data - GF #14379 cleanup of below Mantis 4313
		if(!isset($available_criteria_types[$data->search_criteria['criteria_type_1']]))
		{
			$data->search_criteria['criteria_type_1'] 		= '';
			$data->search_criteria['search_deliminator_1'] 	= '';
			$data->search_criteria['search_criteria_1'] 	= '';
		}
		if(!isset($available_criteria_types[$data->search_criteria['criteria_type_2']]))
		{
			$data->search_criteria['criteria_type_2'] 		= '';
			$data->search_criteria['search_deliminator_2'] 	= '';
			$data->search_criteria['search_criteria_2'] 	= '';
		}

		//Changed to reference appropriate control option - NT
		$is_start_with_ssn_disabled = in_array("disable_starts_with_ssn", ECash::getTransport()->Get_Data()->read_only_fields);

		//mantis:4313 - add phone
		if($is_start_with_ssn_disabled && $this->request->criteria_type_1 == 'social_security_number')
		{
			$data->search_criteria['criteria_type_1'] = 'application_id';
			$data->search_criteria['search_criteria_1'] = '';
		}
		
		if($is_start_with_ssn_disabled && $this->request->criteria_type_2 == 'social_security_number')
		{
			$data->search_criteria['criteria_type_2'] = '';
			$data->search_criteria['search_criteria_2'] = '';
		}

		//Cache the search data
		$_SESSION['search_data'] = $data;
		
	}

	public function Show_Applicant($application_id=null, $no_actions = false)
	{
		if (is_null($application_id)) $application_id = $this->request->application_id;

		$loan_data = new Loan_Data(ECash::getServer());
		if($data = $loan_data->Fetch_Loan_All($application_id))
		{
			/**
			 * Multi-Company Search capability.  Enable company switching ONLY
			 * if Multi-Company is enabled.
			 */
			$multi_company = eCash_Config::getInstance()->MULTI_COMPANY_ENABLED;
			if($multi_company === FALSE && $this->module != 'fraud')
			{
				if($data->company_id != ECash::getCompany()->company_id)
				{
					throw new Exception("Can't pull application from a different company!  {$data->company_id} != ".ECash::getCompany()->company_id);
				}
			}
			else if($data->company_id != ECash::getCompany()->company_id)
			{
				$company = ECash::getFactory()->getCompanyById($data->company_id);
				ECash::setCompany($company);

				//re-instantiating the loan_data class here so the $server dependent methods called
				//within have the right company_id in the server class.
				$loan_data = new Loan_Data(ECash::getServer());
				$data = $loan_data->Fetch_Loan_All($application_id);
			}

			// We need to track how this application was found
			if (!$no_actions && isset($_SESSION['Server_state']['active_module']))
			{
			
				$search_mode = $_SESSION['Server_state']['active_module'];
				$search_area = $_SESSION[$search_mode."_mode"];
				$search_action = "search_{$search_mode}_{$search_area}";

				$agent = ECash::getAgent();
				$agent->getTracking()->add($search_action, $this->request->application_id);
			}
			ECash::getTransport()->Set_Data($data);
			return TRUE;
		}
		else
		{
			$this->Get_Last_Search();
			return FALSE;
		}
	}

	public function Get_Last_Search($module = NULL, $mode = NULL)
	{
		if(!empty($_SESSION['search_data']))
		{
			if( isset($this->request->sort) )
			{
				include_once("advanced_sort.1.php");

				if( isset($this->request->sort) )
				{
					include_once("advanced_sort.1.php");

					switch($this->request->sort)
					{
						case "social":
						$sort_data_col = "ssn";
						break;

						case "status":
						$sort_data_col = "application_status";
						break;

						case "amount":
						$sort_data_col = "application_balance";
						break;

						default:
						if(empty($this->request->sort))
						$this->request->sort = "name_last";
						$sort_data_col = $this->request->sort;
					}

					$direction = SORT_ASC;
					$sort_string = "asc";

					if( isset($_SESSION['search']['last_sort']) )
					{
						if( $_SESSION['search']['last_sort']['col'] == $sort_data_col )
						{
							if( isset($_SESSION['search']['last_sort']['direction']) && $_SESSION['search']['last_sort']['direction'] == "asc" )
							{
								$direction = SORT_DESC;
								$sort_string = "desc";
							}
						}
					}

					$_SESSION['search']['last_sort']['col'] = $sort_data_col;
					$_SESSION['search']['last_sort']['direction'] = $sort_string;

					//echo "<pre>"; print_r($_SESSION['search_data']); echo "</prE>";

					$_SESSION['search_data']->search_results = Advanced_Sort::Sort_Data($_SESSION['search_data']->search_results, $sort_data_col, $direction);
				}

			}

			ECash::getTransport()->Set_Data($_SESSION['search_data']);
		}
		ECash::getTransport()->Add_Levels('search');
	}

	public function Get_Cashline_View()
	{
		if(! is_a($this->cashline_view, 'Cashline_View')) {
			$this->cashline_view = new Cashline_View($this->server, $this->request);
		}

		$this->cashline_view->Get_Customer();
		ECash::getTransport()->Set_Levels('popup','cashline_view');
		return TRUE;
	}

	public function Get_Cashline_Note()
	{
		if(! is_a($this->cashline_view, 'Cashline_View')) {
			$this->cashline_view = new Cashline_View($this->server, $this->request);
		}
		$this->cashline_view->Get_Note();
		ECash::getTransport()->Set_Levels('popup','cashline_view_note');
		return TRUE;
	}
}

?>
