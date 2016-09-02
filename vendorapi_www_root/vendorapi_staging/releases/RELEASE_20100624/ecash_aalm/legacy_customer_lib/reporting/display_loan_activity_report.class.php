<?php

/**
 * @package Reporting
 * @category Display
 */
class Loan_Activity_Report extends Report_Parent
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
		$this->company_totals = array();

		$this->report_title       = "Loan Activity Report";

		$this->column_names       = array( 'company'					=> 'Company',
										   'payment_date'				=> 'Payment Date',
										   'fund_date'                  => 'Fund Date',
										   'application_id' 			=> 'Application ID',
										   'last_name'                  => 'Last Name',
										   'first_name'                 => 'First Name',
										   'ach_id'                     => 'ACH ID',
										   'trans_id'					=> 'Transaction ID',
										   'original_loan_amount'       => 'Original Loan Amount',
										   'payoff_amount'              => 'Payoff Amount',
										   'tran_amount'				=> 'Transaction Amount',
										   't_type'                     => 'Transaction Type',
										   'c_or_d'						=> 'Credit/Debit',
										   'status'						=> 'Current Status',
										   'agent_name'					=> 'Agent Name',
										   'new_vs_react'				=> 'New/React',
										   'application_status'			=> 'Application Status' );
										
		$this->column_format       = array( 'payment_date'				=> self::FORMAT_DATE,
											'fund_date'					=> self::FORMAT_DATE,
											'application_id'			=> self::FORMAT_ID,
											'ach_id'                    => self::FORMAT_ID,
											'trans_id'					=> self::FORMAT_ID,
											'original_loan_amount'		=> self::FORMAT_CURRENCY,
											'tran_amount'				=> self::FORMAT_CURRENCY,
											'payoff_amount'				=> self::FORMAT_CURRENCY );

		$this->sort_columns       = array(	'payment_date',	
											'fund_date', 
											'application_id',
											'last_name', 
											'first_name',
											'time_modified', 
											'ach_id',
											'trans_id', 
											'original_loan_amount', 
											'tran_amount', 
											'payoff_amount', 
											't_type', 
											'c_or_d',
											'status', 
											'agent_name' );

        $this->link_columns        = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals 			   = null;
		$this->totals_conditions   = null;

		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;
		$this->download_file_name  = null;
		$this->ajax_reporting 	   = true;
		$this->company_list_no_all = true;
		parent::__construct($transport, $module_name);
	}

	// Custom Dropdown for this report 
    protected function Get_Form_Options_HTML(stdClass &$substitutions)
    {
        $funding      = ($this->search_criteria['date_type'] == "funding_date")     ? "selected" : "";
        $transaction  = ($this->search_criteria['date_type'] == "transaction_date") ? "selected" : "";

        $substitutions->date_type_list  = '<span>Date Search : </span><span><select name="date_type" size="1" style="width:auto;;"></span>';
		$substitutions->date_type_list .= '<option value="transaction_date"' . $transaction . '>Transaction Date</option>';
        $substitutions->date_type_list .= '<option value="funding_date"'     . $funding . '>Fund Date</option>';
        $substitutions->date_type_list .= '</select>';

        return parent::Get_Form_Options_HTML($substitutions);
    }

	/* Sort the excel report by funding date or payment date */
    public function Download_Data()
    {
        if ($this->search_criteria['date_type'] == 'funding_date')
            $ordercol = "fund_date";
        else
            $ordercol = "payment_date";

        foreach( $this->search_results as $company_name => $company_data )
        {
            $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $ordercol, SORT_ASC);
        }

        parent::Download_Data();
    }

	/* Sort by fund date or payment date for AJAX report */
    public function Download_XML_Data()
    {
		if ($this->search_criteria['date_type'] == 'funding_date')
			$ordercol = "fund_date";
		else
			$ordercol = "payment_date";

        // This is a hack the reporting framework doesn't seem to have a way to specify
        // a default arbitrary sort column in the initial display of the report. Apparently
        // CLK has a new framework so I'm not going to worry about it much now. [benb]
		if ($_REQUEST['sort'] == "undefined")
		{
			foreach( $this->search_results as $company_name => $company_data )
			{
                $this->search_results[$company_name] = Advanced_Sort::Sort_Data($company_data, $ordercol, SORT_ASC);
            }
        }

        parent::Download_XML_Data();
    }

	// Get rid of the empty totals line in the excel report
	protected function Get_Company_Total_Line($company_name, &$company_totals)
	{
		return;
	}

}

?>
