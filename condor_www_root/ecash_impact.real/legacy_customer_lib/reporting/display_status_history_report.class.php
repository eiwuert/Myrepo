<?php

/**
 * @package Reporting
 * @category Display
 */
class Status_History_Report extends Report_Parent
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

		$this->report_title       = "Status History Report";

		$this->column_names       = array( 'application_id' 			=> 'Application ID',
										   'date_created'				=> 'Time Modified',
		                                   'previous_status' 			=> 'Previous',
		                                   'new_status' 				=> 'New Status',
		                                   'agent_name'   				=> 'Agent Name');

		$this->column_format       = array(
										   'date_created'				=> self::FORMAT_TIME);

		$this->sort_columns       = array( 'application_id',	'date_modified',	'status_before',
		                                   'status_after',		'agent_id',  		'agent_name');

        $this->link_columns       = array( 'application_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%'  );

        $this->totals = null;
		$this->totals_conditions  = null;

		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = false;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}
}

?>
