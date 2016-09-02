<?php

/**
 * @package Reporting
 * @category Display
 */
class Verification_Performance_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title = "Verification Performance Report";
		$this->column_names = array( 'company_name'		   => 'Company Name',
									 'agent_name'          => 'Agent',
		                             'num_approved'        => 'Approved',
		                             'num_in_underwriting' => 'Received UW',
		                             'num_funded'          => 'Funded',
		                             'num_withdrawn'       => 'Withdrawn',
		                             'num_denied'          => 'Denied',
		                             'num_reverified'      => 'Reverified' );

		$this->sort_columns = array( 'company_name',
									 'agent_name',          'num_approved',
		                             'num_in_underwriting', 'num_funded',
		                             'num_withdrawn',       'num_denied',
		                             'num_reverified' );
		$this->link_columns = array();
		$this->totals       = array( 'company' => array( 'num_approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_in_underwriting' => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_reverified'      => Report_Parent::$TOTAL_AS_SUM),
		                             'grand'   => array( 'num_approved'        => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_in_underwriting' => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_funded'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_withdrawn'       => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_denied'          => Report_Parent::$TOTAL_AS_SUM,
		                                                 'num_reverified'      => Report_Parent::$TOTAL_AS_SUM) );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

}

?>
