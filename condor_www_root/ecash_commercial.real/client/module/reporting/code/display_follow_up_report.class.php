<?php

/**
 * @package Reporting
 * @category Display
 */
class Follow_Up_Report extends Report_Parent
{

	public function __construct(ECash_Transport $transport, $module_name)
	{

		$this->report_title       = "Follow Up Report";
		$this->column_names       = array( 'company_name' 	=> 'Company',
		                                   'application_id' => 'Application ID',
		                                   'agentName'      => 'Agent',
		                                   'comment'        => 'Comment',
		                                   'queue'	    => 'Type',
		                                   'date_created'   => 'Created On',
		                                   'follow_up'	    => 'Follow Up' );
		$this->sort_columns       = array( 'application_id', 'agentName',
                                                   'comment',        'queue',
                                                   'date_created',   'follow_up' );
		$this->link_columns       = array( 'application_id' => '?module=%%%module%%%&mode=%%%mode%%%&show_back_button=1&action=show_applicant&application_id=%%%application_id%%%' );
		$this->totals             = array( 'company' => array( 'rows' ),
		                                   'grand'   => array() );
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->download_file_name = null;
		$this->ajax_reporting     = true;

		parent::__construct($transport, $module_name);
	}

	/**
	* Gets the html for the data section of the report
	* also updates running totals
	* used only by Get_Module_HTML()
	*
	* @param  string name of the company
	* @param  &array running totals
	* @return string
	* @access protected$DATE_DROPDOWN_SPECIFIC
	*/
	protected function Get_Data_HTML($company_data, &$company_totals)
	{
		$row_toggle = true;  // Used to alternate row colors
		$line       = "";

		for( $x = 0 ; $x < count($company_data) ; ++$x )
		{
			$td_class = ($row_toggle = ! $row_toggle) ? "align_left" : "align_left_alt";

			// 1 row of data
			$line .= "    <tr>\n";
			foreach( $this->column_names as $data_name => $column_name )
			{
				// the the data link to somewhere?
				if( count($this->link_columns) > 0 && isset($this->link_columns[$data_name])  && isset($company_data[$x]['mode']))
				{
					// do any replacements necessary in the link
					$this->parse_data_row = $company_data[$x];
					$href  = preg_replace_callback("/%%%(.*?)%%%/", array($this, 'Link_Parse'), $this->link_columns[$data_name]);
					$line .= "     <td class=\"$td_class\"><a href=\"#\" onClick=\"parent.window.location='$href'\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</a></td>\n";
				}
				else
				{
					if($data_name == "comment")
					{
						$line .= "     <td class=\"$td_class\" style=\"width: 260px;\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
					}
					else
					{
						$line .= "     <td class=\"$td_class\">" . $this->Format_Field($data_name, $company_data[$x][$data_name]) . "</td>\n";
					}
				}
				// If the col's data matches the criteria, total it up
				if( $this->check_eval($company_data[$x], $data_name) && isset($this->totals['company'][$data_name]) )
				{
					switch($this->totals['company'][$data_name])
					{
						case self::$TOTAL_AS_COUNT:
							$company_totals[$data_name]++;
							break;
						case self::$TOTAL_AS_SUM:
							$company_totals[$data_name] += $company_data[$x][$data_name];
							break;
						default:
							// Dont do anything, somebody screwed up
					}
				}
			}
			$company_totals['rows']++;
			$line .= "    </tr>\n";
		}

		return $line;
	}

	/**
	* Checks the total_conditions for specified column does necessary replacements, evals it and returns result
	*
	* @param  array   column's data to check
	* @param  string  column condition to check
	* @return boolean
	* @access private
	*/
	protected function check_eval($line, $column)
	{
		$conditional = str_replace( "%%%var%%%", addslashes($line[$column]), $this->totals_conditions[$column] );
		return eval($conditional);
	}

	/**
	* Used to format field data for printing
	*
	* @param string  $name column name to format
	* @param string  $data field data
	* @param boolean $totals formatting totals or data?
	* @param boolean $html format for html?
	* @return string
	* @access protected
	*/
	protected function Format_Field( $name, $data, $totals = false, $html = true )
	{
		if($name == "queue")
		{
			$data = ucfirst($data);
		}
		
		if (($name == 'date_created') || ($name == 'follow_up')) 
		{
			$data = date('m/d/y g:i a', strtotime($data));
		}

		return $data;
	}
}

?>
