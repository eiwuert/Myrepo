<?php

class ECash_Data_Search extends ECash_Data_DataRetriever
{
	/**
	 *  @TODO This whole search thing needs to be re-evaluated
	 */
	public function getApplications($multi_company = FALSE)
	{
		/**
		 * Get the columns to display and query based on the company's config
		 */
		if(!$columns = ECash_Config::getInstance()->SEARCH_DISPLAY_COLUMNS)
		{
			$columns = $this->getDisplayColumns();
		}
		
		$max_search_retrieval_rows = ECash_Config::getInstance()->MAX_SEARCH_DISPLAY_ROWS + 1;

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
			$company_where = "app.company_id = ".ECash::getCompany()->getModel()->company_id;
		}

		// mantis:4313 - add DISTINCT
		$query = "SELECT DISTINCT
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

		$query.= "
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
			$query .= "
					JOIN personal_reference pr ON app.application_id = pr.application_id
				";
		}
		
		$query .= "
						WHERE
							{$company_where}
				";
		
	}

	protected function getDisplayColumns()
	{
		return array(
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
}
?>