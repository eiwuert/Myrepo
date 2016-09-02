<?php

/**
 * @package Reporting
 * @category Display
 */
class Achbatch_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "ACH Batch Report";

		// GF 12733: Changed some of the column names to better represent what the column represents. [benb]
		$this->column_names       = array( 
				'report_date'                    => 'Date',
				'credit_num_attempted'         => '# Credits',
				'credit_total_attempted'       => '$ Credits',
				'debit_num_attempted'               => '# Debits',
				'debit_total_attempted'                 => '$ Debits',
				'net_attempted'   => '# Net',
				'net_total'	=> '$ Net',
				'num_returns_actual_day'           => '# Returned',
				'total_returns_actual_day'         => '$ Returned',
				'net_after_returns'              => '$ Net after returns',
				'num_unauthorized'            => '# Unauthorized Returns',
				'total_unauthorized'     => '$ Unauthorized Returns',
				
		);
                $this->column_format       = array( 'report_date' => self::FORMAT_DATE,
                                'credit_total_attempted' => self::FORMAT_CURRENCY,
                                'debit_total_attempted' => self::FORMAT_CURRENCY,
                                'net_total' => self::FORMAT_CURRENCY,
                                'total_returns_actual_day' => self::FORMAT_CURRENCY,
                                'net_after_returns' => self::FORMAT_CURRENCY,
                                'total_unauthorized' => self::FORMAT_CURRENCY,
                                                                        );


		$this->sort_columns       = array( 'report_date',          
				'credit_num_attempted',
				'credit_total_attempted',
				'debit_total_attempted',
				'debit_num_attempted',
				'net_attempted',
				'net_total',
				'num_returns_actual_day',
				'total_returns_actual_day',
				'net_after_returns',
				'num_unauthorized',
				'total_unauthorized',
									);
		$this->link_columns       = array();
		$this->totals             = array( 'company' => array( 'credit_num_attempted'        		=> Report_Parent::$TOTAL_AS_SUM,
					'credit_total_attempted'       		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_num_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_total_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'net_attempted'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'num_returns_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returns_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returns'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_unauthorized'       		=> Report_Parent::$TOTAL_AS_SUM,
					'total_unauthorized'          		=> Report_Parent::$TOTAL_AS_SUM
					),

					'grand'   => array( 'credit_num_attempted'        		=> Report_Parent::$TOTAL_AS_SUM,
					'credit_total_attempted'       		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_num_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'debit_total_attempted' 		=> Report_Parent::$TOTAL_AS_SUM,
					'net_attempted'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_total'                         => Report_Parent::$TOTAL_AS_SUM,
					'num_returns_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'total_returns_actual_day'          		=> Report_Parent::$TOTAL_AS_SUM,
					'net_after_returns'       		=> Report_Parent::$TOTAL_AS_SUM,
					'num_unauthorized'       		=> Report_Parent::$TOTAL_AS_SUM,
					'total_unauthorized'          		=> Report_Parent::$TOTAL_AS_SUM) );
		//$this->report_table_height = 276;
		$this->totals_conditions   = null;
		$this->date_dropdown       = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type           = true;
		$this->download_file_name  = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		if ($data == NULL)
			return 0;
		else
			return parent::Format_Field( $name, $data, $totals, $html);
	}

}

?>
