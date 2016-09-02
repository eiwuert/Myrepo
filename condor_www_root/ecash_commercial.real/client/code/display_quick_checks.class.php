<?php

require_once(LIB_DIR. "form.class.php");
require_once("display_parent.abst.php");
require_once(COMMON_LIB_DIR . "dropdown_dates.1.php");


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
		return "<link rel=\"stylesheet\" href=\"css/transactions.css\">
		        <link rel=\"stylesheet\" href=\"js/calendar/calendar-dp.css\">
               ". include_js();
	}

	public function Get_Body_Tags()
	{
		return "";
	}

	public function Get_Module_HTML()
	{
		$fields = ECash::getTransport()->Get_Data();

		$fields->from_date = $this->Generate_Date_Dropdown( 'from_date_',
		                                                    $fields->from_date_year,
		                                                    $fields->from_date_month,
		                                                    $fields->from_date_day );
		$fields->to_date   = $this->Generate_Date_Dropdown( 'to_date_',
		                                                    $fields->to_date_year,
		                                                    $fields->to_date_month,
		                                                    $fields->to_date_day );

		if(in_array("receive", ECash::getTransport()->page_array))
		{
			if (isset($fields->display_upload_status))
			{
				switch ($fields->display_upload_status)
				{
					case "success":
						$fields->display_upload_status = "<font color=green>Upload Successful</font>";
						break;
					case "failed":
					default:
						$fields->display_upload_status = ucfirst($fields->display_upload_status);
						$fields->display_upload_status = "<font color=red>" . $fields->display_upload_status . "</font>";
						break;
				}
			}

			$form = new Form(CLIENT_MODULE_DIR.$this->module_name."/view/receive_quick_checks.html");
		}
		else
		{
			$fields->company_id = ECash::getTransport()->company_id;

			$fields->data_rows_concatenated = '';

			$row_number = 1;

			$newest = 0;

			foreach( $fields->data_stuff as $row )
			{
				$fields->data_rows_concatenated .= $this->Get_Row_Html($row, $row_number);
				$newest = (strtotime($row->date_created) > $newest ? strtotime($row->date_created) : $newest);
				$row_number++;
			}

			if( $newest < mktime(0, 0, 0) )
			{
				$first = <<<EOHTML
                        <tr>
                                <td class="align_left_alt">Current</td>
                                <td class="align_left_alt">
                                        <select name="collection_type" id ='external_collection_companies'>
                                                <option value='pdf'>PDF</option>
                                                <option value='electronic' selected>Electronic</option>
                                        </select>
                                </td>
                                <td class="align_left_alt">{$fields->pending_count}</td>
                                <td class="align_left_alt">{$fields->pending_total}</td>
                                <td class="align_left_alt">Ready</td>
                                <td class="align_left_alt">
                                        <input type="submit" name="submitButton" value="Process" style="font-weight:bold; color:blue;" >
                                </td>
                        </tr>
EOHTML;
				$fields->data_rows_concatenated = $first . $fields->data_rows_concatenated;
			}
			
			$fields->master_domain = eCash_Config::getInstance()->MASTER_DOMAIN;

			$form = new Form( CLIENT_MODULE_DIR . $this->module_name . "/view/quick_checks.html" );
		}

		return $form->As_String($fields);
	}

	public function Get_Row_Html( $row, $row_number )
	{
		$class = $row_number % 2 == 0 ? 'align_left_alt' : 'align_left';
		$quick_checks_batch_id = $row->quick_checks_batch_id;

		$result = "
		<tr>
			<td class='$class'>{$row->date_created}</td>
			<td class='$class'>" . ucfirst( strtr($row->type, '_', ' ') ) . "</td>
			<td class='$class'>{$row->record_count}</td>
			<td class='$class'>{$row->total}</td>
			<td class='$class'>{$row->status}</td>
			<td class='$class'>" . $this->Get_Pdf_Electronic_Link_Html($row->type, $row->status, $quick_checks_batch_id) . "</td>
		</tr>
		";

		return $result;
	}

	public function Get_Pdf_Electronic_Link_Html( $type, $status, $quick_checks_batch_id )
	{
		$type = strtolower($type);

		if( $type == 'electronic' && ($status == 'created' || $status == 'failed') )
		{
			$send = ($status == 'created' ? 'Send' : 'Resend');
			// When viewing is possible use these 4 lines instead of the 2 below
			//$url   = '/?mode=quick_checks&action=quick_check_view_download&quick_checks_batch_id=' . $quick_checks_batch_id;
			//$link  = '<a target="_blank" href="' . $url . '" onclick="return OpenDialogSizedHelper(this, 400, 400)">View</a> / ';
			//$url   = "/?mode=quick_checks&action=quick_check_resend&quick_checks_batch_id={$quick_checks_batch_id}";
			//$link .= "<a href=\"{$url}\">{$send}</a>";
			$url  = "/?mode=quick_checks&action=quick_check_resend&quick_checks_batch_id={$quick_checks_batch_id}";
			$link = "<a href=\"{$url}\">{$send}</a>";
			return $link;
		}
		elseif( $type == 'pdf' )
		{
			// This is a good approach to popup windows because it works even when javascript is turned off.
			$url   = '/?mode=quick_checks&action=quick_check_view_download&quick_checks_batch_id=' . $quick_checks_batch_id;
			$link  = '<a target="_blank" href="' . $url . '" onclick="return OpenDialogSizedHelper(this, 400, 400)">View</a>';
			return $link;
		}
		else
			return '';
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
