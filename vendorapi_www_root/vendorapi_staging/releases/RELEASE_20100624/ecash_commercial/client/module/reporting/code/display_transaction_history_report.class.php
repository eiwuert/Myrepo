<?php

/**
 * @package Reporting
 * @category Display
 */
class Transaction_History_Report extends Report_Parent
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

		$this->report_title       = "Transaction History Report";

		$this->column_names       = array( 'company_name'				=> 'Company',
										   'application_id' 			=> 'Application ID',
										   'date_modified'				=> 'Time Modified',
		                                   'transaction_register_id'	=> 'Transaction ID',
		                                   'transaction_type_name' 		=> 'Transaction Type',
		                                   'status_before' 				=> 'Previous',
		                                   'status_after' 				=> 'New Status',
		                                   'amount'  					=> 'Amount',
		                                   'agent_name'   				=> 'Agent Name');

		$this->column_format       = array(
										   'application_id'				=> self::FORMAT_ID ,
										   'date_modified'				=> self::FORMAT_TIME ,
										   'transaction_register_id'	=> self::FORMAT_ID ,
		                                   'amount'  					=> self::FORMAT_CURRENCY );

		$this->sort_columns       = array( 'application_id',	'date_modified',	'transaction_register_id',
		                                   'agent_id',  		'agent_name');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_SPECIFIC;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
