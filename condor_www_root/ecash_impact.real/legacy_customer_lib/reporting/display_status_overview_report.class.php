<?php

/**
 * @package Reporting
 * @category Display
 */
class Status_Overview_Report extends Report_Parent
{
	/**
	 * constructor, initializes data used by report_parent
	 *
	 * @param Transport $transport the transport object
	 * @param string $module_name name of the module we're in, not used, but keeps
	 *                            universal constructor call for all modules
	 * @access public
	 */
	public function __construct(ECash_Transport $transport, $module_name)
	{
		//$this->company_totals = array();

		$this->report_title       = "Status Overview Report";

		$this->column_names       = array( 'company_name'   => 'Company Name',
										   'application_id' => 'Application ID',
										   'name_first'		=> 'First Name',
										   'name_last'		=> 'Last Name',
										   'phone_home' 	=> 'Home Phone',
										   'phone_work' 	=> 'Work Phone',
										   'phone_cell' 	=> 'Cell Phone',
		                                   'ssn' 			=> 'SSN',
		                                   'street' 		=> 'Street',
		                                   'city'  			=> 'City',
		                                   'state'  		=> 'State',
		                                   'balance'   		=> 'Principal Balance');

		$this->sort_columns       = array( 'application_id',	'name_first',	'name_last',    	
											'ssn',				'street',  		'city',        
											'state',			'balance');

        //$this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 	= array('company' => array( 'balance','rows'),
        						'grand' =>  array( 'balance','rows'));
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}

	public function Download_XML_Data( )
	{
		if (function_exists('gzencode')) 
		{
			$zlib_compression = strtolower(ini_get('zlib.output_compression'));
			
			if ($zlib_compression != '' && $zlib_compression != 'off' && $zlib_compression != '0') 
			{
    			ini_set('zlib.output_compression', 'Off');
			}
		}

		parent::Download_XML_Data();	
	}

	public function Download_Data()
	{
		if (function_exists('gzencode')) 
		{
			$zlib_compression = strtolower(ini_get('zlib.output_compression'));
			
			if ($zlib_compression != '' && $zlib_compression != 'off' && $zlib_compression != '0') 
			{
    			ini_set('zlib.output_compression', 'Off');
			}
		}
	
		header( "Accept-Ranges: bytes\n");
		header( "Content-Disposition: attachment; filename={$this->download_file_name}\n");
		header( "Content-Type: application/vnd.ms-excel\n\n");

		echo $this->report_title . " - Run Date: " . date('m/d/Y') . "\n";

		if( !empty($this->prompt_reference_agents))
		{
			$agents = $this->Get_Agent_List();
			
			if(isset($this->search_criteria['agent_id']))
			{
				foreach($this->search_criteria['agent_id'] as $agent_id)
				{
					if(isset($agents[$agent_id]))
					{
						echo "For agent: ".$agents[$agent_id]."\n";
					}
				}
			}
		}

		// Is the report run for a specific date, date range, or do dates not matter?
		switch($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_RANGE:
				if (isset($this->search_criteria['start_date_MM']))
				{
					echo "Date Range: " . $this->search_criteria['start_date_MM']   . '/'
											   . $this->search_criteria['start_date_DD']   . '/'
											   . $this->search_criteria['start_date_YYYY'] . " to "
											   . $this->search_criteria['end_date_MM']     . '/'
											   . $this->search_criteria['end_date_DD']     . '/'
											   . $this->search_criteria['end_date_YYYY']   . "\n";
				}
				break;
			case self::$DATE_DROPDOWN_SPECIFIC:
				if (isset($this->search_criteria['specific_date_MM']))
				{
					echo "Date: " . $this->search_criteria['specific_date_MM'] . '/'
									 	. $this->search_criteria['specific_date_DD'] . '/'
									 	. $this->search_criteria['specific_date_YYYY'] . "\n";
				}
				break;
			case self::$DATE_DROPDOWN_NONE:
			default:
				// Nothing to do
				break;
		}

		$total_rows = 0;

		// An empty array for the grand totals
		$grand_totals = array();
		foreach( $this->totals['grand'] as $which => $unused )
		{
			$grand_totals[$which] = 0;
		}

		echo "\n";

		echo $this->Get_Column_Headers( false );

		// Sort through each company's data
		foreach ($this->search_results as $company_name => $company_data)
		{
			// Short-circuit the loop if this is the "summary" data.
			if ($company_name == 'summary')
			{
				continue;
			}

			// An array of company totals which gets added to grand_totals
			$company_totals = array();
			foreach ($this->column_names as $data_name => $column_name)
			{
				$company_totals[$data_name] = 0;
			}

			// If isset($x), this is the 2nd+ company, insert a blank line to seperate the data
			if (isset($x))
			{
				echo "\n";
			}

			foreach (array_keys($company_data) as $x)
			{
				$line = "";

				foreach (array_keys($this->column_names) as $data_col_name)
				{
                    $this->totals['company'][$data_col_name] = isset($this->totals['company'][$data_col_name]) ? $this->totals['company'][$data_col_name] : null;
                    $company_data[$x][$data_col_name] = isset($company_data[$x][$data_col_name]) ? $company_data[$x][$data_col_name]: null;
					$line .= $this->Format_Field($data_col_name, $company_data[$x][$data_col_name], false, false) . "\t";
                    switch($this->totals['company'][$data_col_name])
                    {
                        case self::$TOTAL_AS_COUNT:
                            $company_totals[$data_col_name]++;
                            break;
                        case self::$TOTAL_AS_SUM:
                            $company_totals[$data_col_name] += $company_data[$x][$data_col_name];
                            break;
                        case self::$TOTAL_AS_AVERAGE;
                            $company_totals[$data_col_name] += ($company_data[$x][$data_col_name]/count($company_data));
                        default:
                            // Dont do anything, somebody screwed up
                    }

				}

				// removes the last tab if we're at the end of the loop and replaces it with a newline
				echo substr($line, 0, -1) . "\n";
				flush();
			}

			$total_rows += count($company_data);
			$company_totals['rows'] = count($company_data);

			// If there's more than one company, show a company totals line
			if (count($this->totals['company']) > 0)
			{
				// Was commented by JRS: [Mantis:1651]... Uncommented by [tonyc][mantis:5861]
				echo $this->Get_Company_Total_Line($company_name, $company_totals) . "\n\n";
			}

			// Add the company totals to the grand totals
			foreach ($grand_totals as $key => $value)
			{
				// Flash report (and maybe others) does something special with the totals
				if (isset($company_totals[$key]))
				{
					$grand_totals[$key] += $company_totals[$key];
				}
			}
		}

		// grand totals
		// dont show grand totals if only 1 company... exact same #s are in company totals above it
		if (count($this->totals['grand']) > 0 && $this->num_companies > 1)
		{
			echo $this->Get_Grand_Total_Line($grand_totals);
		}

		/* Mantis:1508#2 */
		if(isset($this->search_results['summary']))
		{
			echo "\n\n"; // This ends the "Count = ..." row and one empty row

			$company_names = array_keys($this->search_results);
			// Next line commented out: Additional change from Mantis:1508
			// $company_names[] = "Grand";
			$this->search_results['summary']['Grand'] = array();
			$grand_totals =& $this->search_results['summary']['Grand'];

			foreach ($company_names as $company_name)
			{
				if ($company_name == 'summary')
				{
					continue;
				}

				echo "${company_name} Totals:\tCount\tDebit\tCredit\n"; // Add header line

				foreach($this->search_results['summary'][$company_name] as $item => $data)
				{
					if('notes' == $item || 'code' == $item)
					{
						echo ucwords($item)."\n"; // Name of subsection

						foreach( $data as $special => $data2 )
						{
							if( 'Grand' != $company_name )
							{
								if( ! isset( $grand_totals[$item] ) || ! isset( $grand_totals[$item][$special] ) )
								{
									$grand_totals[$item][$special] = array(
											'count'  => 0,
											'debit'  => 0,
											'credit' => 0,
											);
								}

								$grand_totals[$item][$special]['count' ] += $data2['count' ];
								$grand_totals[$item][$special]['debit' ] += $data2['debit' ];
								$grand_totals[$item][$special]['credit'] += $data2['credit'];
							}

							echo $special
									.	"\t"
									.	$data2['count']
									.	"\t"
									.	number_format($data2['debit'],2,".",",")
									.	"\t"
									.	number_format($data2['credit'],2,".",",")
									.	"\n"
									;
						}
					}
					else
					{
						if( 'Grand' != $company_name )
						{
							if( ! isset( $grand_totals[$item] ) )
							{
								$grand_totals[$item] = array(
										'count'  => 0,
										'debit'  => 0,
										'credit' => 0,
										);
							}

							$grand_totals[$item]['count' ] += $data['count' ];
							$grand_totals[$item]['debit' ] += $data['debit' ];
							$grand_totals[$item]['credit'] += $data['credit'];
						}

						echo $item
								.	"\t"
								.	$data['count']
								.	"\t"
								.	number_format($data['debit'],2,".",",")
								.	"\t"
								.	number_format($data['credit'],2,".",",")
								.	"\n"
								;
					}
				}

				echo "\n"; // Add one empty row beneath this company
			}
		}

		//mantis:4324
		$generic_data = $this->transport->Get_Data();
	}

	/**
	 * Definition of abstract method in Report_Parent
	 * Used to format field data for printing
	 *
	 * @param  string  $name   column name to format
	 * @param  string  $data   field data
	 * @param  boolean $totals formatting totals or data?
	 * @param  boolean $html   format for html?
	 * @return string          formatted field
	 * @access protected
	 */
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		switch( $name )
		{
			case 'name_first':
			case 'name_last':
			case 'status':
			case 'street':
			case 'city':
				return ucwords($data);
			case 'state':
				return strtoupper($data);
			case 'balance':
				if( $html === true )
				{
					$markup = ($data < 0 ? 'color: red;' : '');
					$open = ($data < 0 ? '(' : '');
					$close = ($data < 0 ? ')' : '');
					$data = abs($data);
					return '<div style="text-align:right;'. $markup . '">' .$open.'\\$' . number_format($data, 2, '.', ',') . $close . '</div>';
				}
				else
				{
					return '$' . number_format($data, 2, '.', ',');
				}
			case 'time_in_queue':
			case 'order':
				if( $html === true )
					return "<div style='text-align:right;'>$data</div>";
				else
					return $data;
			case 'application_id':
			default:
				return $data;
		}
	}
}

?>
