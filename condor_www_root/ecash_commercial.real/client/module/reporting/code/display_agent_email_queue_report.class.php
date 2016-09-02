<?php

/**
 * @package Reporting
 * @category Display
 */
class Agent_Email_Queue_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title = "Agent Email Queue Report";

		$this->column_names = array('company_name' => 'Company', 
									'agent'     => 'Agent',
		                            'received'   => 'Received',
		                            'associated' => 'Associated',
 									'responded'  => 'Responded',
 									'followups'  => 'Follow Ups',
 									'filed'      => 'Filed',
 									'queued'     => 'Queue Change',
 									'canned'     => 'Canned Responses',
 									'removed'    => 'Removed'							
									);

		$this->sort_columns = array(
									'agent',          
		                            'received',
		                            'associated',
 									'responded',
 									'followups',
 									'filed',
 									'queued',
 									'canned',
 									'removed'
									);

		$this->link_columns = array();

		$this->totals       = array( 'company' => array(
                                                        'received'   => Report_Parent::$TOTAL_AS_SUM,
		                                                'associated' => Report_Parent::$TOTAL_AS_SUM,
		                                                'responded'  => Report_Parent::$TOTAL_AS_SUM,
		                                                'followups'  => Report_Parent::$TOTAL_AS_SUM,
		                                                'filed'      => Report_Parent::$TOTAL_AS_SUM,
		                                                'queued'     => Report_Parent::$TOTAL_AS_SUM,
		                                                'canned'     => Report_Parent::$TOTAL_AS_SUM,
				                                        'removed'    => Report_Parent::$TOTAL_AS_SUM	                                                 
		                                                ),
		                             'grand'   => array(
                                                        'received'   => Report_Parent::$TOTAL_AS_SUM,
		                                                'associated' => Report_Parent::$TOTAL_AS_SUM,
		                                                'responded'  => Report_Parent::$TOTAL_AS_SUM,
		                                                'followups'  => Report_Parent::$TOTAL_AS_SUM,
		                                                'filed'      => Report_Parent::$TOTAL_AS_SUM,
		                                                'queued'     => Report_Parent::$TOTAL_AS_SUM,                                             
				                                        'canned'     => Report_Parent::$TOTAL_AS_SUM,
		                                                )
		                             );

		$this->column_format = array(
		                             'received'   => self::FORMAT_NUMBER,
		                             'associated' => self::FORMAT_NUMBER,
		                             'responded'  => self::FORMAT_NUMBER,
		                             'followups'  => self::FORMAT_NUMBER,
		                             'filed'      => self::FORMAT_NUMBER,
		                             'queued'     => self::FORMAT_NUMBER,
		                             'canned'     => self::FORMAT_NUMBER,
		                             'removed'    => self::FORMAT_NUMBER		
		                             );

		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->download_file_name = null;
		$this->loan_type          = TRUE;
		$this->agent_list = TRUE;
		$this->wrap_header = FALSE;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}
}

?>
