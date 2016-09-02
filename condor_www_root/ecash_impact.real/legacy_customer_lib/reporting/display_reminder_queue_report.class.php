<?php

/**
 * @package Reporting
 * @category Display
 */
class Reminder_Queue_Report extends Report_Parent
{
	public function __construct(ECasj_Transport $transport, $module_name)
	{
		$this->report_title = "Reminder Queue";
		$this->column_names = array(
				'app_id' => 'Application Id' ,
				'last' => 'Last Name' ,
				'first' => 'First Name' ,
				'date' => 'Transaction Scheduled Date' ,
				'arranged' => 'Contact Arranged' ,
				'agent' => 'Owning Agent' ,
				);
		$this->sort_columns = array(
				'app_id',
				'last',
				'first',
				'date',
				'arranged',
				'agent',
				);

		$this->link_columns       = array( 'app_id'  => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%app_id%%%'  );

		$this->column_format = array(
				'date' => self::FORMAT_DATE,
				);
		$this->totals = array(
				'company' => array(),
				'grand' => array(),
				);

		$this->totals_conditions = null;
		$this->date_dropdown = null;
		$this->loan_type = FALSE;
		$this->agent_list = TRUE;
		$this->download_file_name = null;
		$this->ajax_reporting = TRUE;

		parent::__construct($transport, $module_name);
	}

	// GF #12527: Cannot format date by default if it may be 'None' [benb]
    protected function Format_Field( $name, $data, $totals = false, $html = true )
    {
		switch ($name)
		{
			case 'date':
				if ($data == 'None')
					return 'None';
	
				return parent::Format_Field( $name, $data, $totals, $html);
			default:
				return $data;
		}
	}
}

?>
