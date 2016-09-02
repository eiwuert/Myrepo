<?php

require_once(LIB_DIR. "form.class.php");
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");
require_once(COMMON_LIB_DIR . "dropdown_dates.1.php");
require_once(CUSTOMER_LIB."list_available_collection_companies.php");


class Display_View extends Display_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->module_name = $module_name;
		$this->transport = ECash::getTransport();
	}

	public function Get_Header()
	{
		include_once(WWW_DIR . "include_js.php");
		return include_js();
	}

	public function Get_Body_Tags()
	{
	}

	public function Get_Module_HTML()
	{
		// $fields = new stdClass();
		$fields = ECash::getTransport()->Get_Data();
		
		$fields->company_id = ECash::getTransport()->company_id;
		$fields->agent_id = ECash::getTransport()->agent_id;
		// $fields->ext_applications = 'There are ' . $temp['count'] . ' application(s) ready.';
		$fields->data_rows_concatenated = '';

		$fields->from_date = $this->Generate_Date_Dropdown( 'from_date_', $fields->from_date_year, $fields->from_date_month, $fields->from_date_day );
		$fields->to_date   = $this->Generate_Date_Dropdown( 'to_date_',
		                                                    $fields->to_date_year,
		                                                    $fields->to_date_month,
		                                                    $fields->to_date_day ); //mantis:5598

		$collection_companies = list_available_collection_companies();
		
		$fields->collection_companies = '';
		foreach ($collection_companies as $company_short => $company) {
			$fields->collection_companies .= '<option value="'.$company_short.'">'.$company."</option>\n";
		}
		
		$batch_direction = ECash::getTransport()->Get_Next_Level();
		
		switch ($batch_direction)
		{
			case "post_collections":
//				$fields->el_dumpo = "<pre>" . var_export($fields,true) . "</pre>";
				foreach( $fields->inc_coll_data as $row )
				{
					$fields->data_rows_concatenated .= $this->Get_ICBatch_Row_Html($row, ++$row_number);
				}
				
				$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/incoming_collections.html");
				break;
				
			default:			
				foreach( $fields->ext_coll_data as $row )
				{
					$fields->data_rows_concatenated .= $this->Get_Row_Html($row, ++$row_number);
				}
				
				$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/external_collections.html");
				
		}
		
		return $form->As_String($fields);
	
	}


	public function Get_Row_Html( $row, $row_number )
	{
		$class = $row_number % 2 == 0 ? 'align_left' : 'align_left_alt';
	
		/*
				<select name='collection_company' id ='external_collection_companies' style='width:200px'>
					<option value='crsi'>CRSI</option>
					<option value='pinion'>Pinion</option>
					<option value='pinion_north'>Pinion North</option>
					<option value='other'>Other</option>
				</select>
		*/

		//	DLH, 2005.12.22
		//	This is the direct approach that sends the download right back to the originating screen.  It
		//	seems to work fine and doesn't upset or reset the scrolling position of the screen.
		//	----------------------------------------------------------------------------------------------------------------
		// 	<td class='$class'><a href=\"?action=download_external_apps&ext_collections_batch_id=$row->ext_collections_batch_id\">download</a></td>

		//	This version opens a smaller screen which then submits the download.  I did this because I assumed that
		//	the current screen would be messed up or its scrolling position would be reset upon doing a download.  It turns
		//	out my assumption was not correct, at least not for firefox.  With Opera, a new tab is opened showing the
		//	results of the download.  For me, I'd rather go with the first approach but I think the little window approach
		//	might be less confusing for the users.  It's easy to change so I'll see which approach Bill prefers.
		//	----------------------------------------------------------------------------------------------------------------
		//	<td class='$class'><a href='#' onclick=\"OpenDialog('/ec_download.php?ext_collections_batch_id=$row->ext_collections_batch_id');return false;\">download</a></td>

		$result = "
		<tr>
			<td class='$class'>$row->date_created</td>
			<td class='$class'>" . ucwords( strtr($row->ext_collections_co, '_', ' ') ) . "</td>
			<td class='$class'>$row->record_count</td>
			<td class='$class'>processed</td>
			<td class='$class'><a href=\"?action=download_external_apps&ext_collections_batch_id=$row->ext_collections_batch_id\">download</a></td>
		</tr>
		";

		return $result;
	
	}
	
	public function Get_ICBatch_Row_Html( $row, $row_number )
	{
		$class = $row_number % 2 == 0 ? 'align_left' : 'align_left_alt';
	
		switch ($row->batch_status)
		{			
			case "received-partial":
			case "received-full":
				$piece = "<a href=\"?action=process_incoming_collections&incoming_collections_batch_id=$row->incoming_collections_batch_id\">Process</a>";
				break;
				
			case "failed":
			case "success":
			case "partial":
				$piece = "<a href=\"?action=incoming_collections_report&incoming_collections_batch_id=$row->incoming_collections_batch_id\">See Report</a>";
				break;
				
			case "in-progress":
			default:
				$piece = "";
		}
		
		$result = "
		<tr>
			<td class='$class'>$row->date_created</td>
			<td class='$class'>$row->file_name</td>
			<td class='$class'>$row->record_count</td>
			<td class='$class'>$row->batch_status</td>
			<td class='$class'>$piece</td>
		</tr>
		";

		return $result;
	
	}
	
	public function Generate_Date_Dropdown( $html_prefix, $year_selected=0, $month_selected=0, $day_selected=0 )
	{
		$date_drop = new Dropdown_Dates();

		$date_drop->Set_Prefix($html_prefix);
		
		$date_drop->Set_Day($day_selected > 0 ? $day_selected : date('d'));
		$date_drop->Set_Month($month_selected > 0 ? $month_selected : date('m'));
		$date_drop->Set_Year($year_selected > 0 ? $year_selected : date('Y'));

		return $date_drop->Fetch_Drop_All();
	}

	
}

?>
