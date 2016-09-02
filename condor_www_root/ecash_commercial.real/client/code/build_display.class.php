<?php
class Build_Display
{
	
	private $data;
	private $acl;
	private $mode;
	private $module_name;
	private $data_format;
	
	public function __construct(stdClass &$data, &$acl, $mode, $module_name, &$data_format)
	{
		$this->acl = &$acl;
		$this->data = &$data;
		$this->mode = $mode;
		$this->module_name = $module_name;
		$this->data_format = &$data_format;
	}
	protected function OLP_Phone_Fmt ($phone_number, $format='formatted')
	{
		$result = str_replace(array('(', ')', '-', ' '), array(''), $phone_number);

		if ( $format == 'formatted' && !empty($result) )
		{
			$result = substr($result, 0, 3) . '-' . substr($result, 3, 3) . '-' . substr($result, 6, 4);
		}

		return $result;
	}
	public function Build_Paydate_URL(&$data)
	{
		# build querystring
		$qs = array();
	
		$app_row = $data->model;

		if (isset($app_row->model['paydate']))
		{
			reset($app_row->model['paydate']);
			while (list($k,$v) = each($app_row->model['paydate']))
			{
				$qs[] = urlencode("paydate[" . $k . "]") . "=" . urlencode( strtoupper($v) );
			}
			return '&' . join("&", $qs);
		}
		return '';

	}
	
	protected function make_clean($text) {
		//Replacing new lines with colons!
		$text = str_replace(array("\r","\n"), "; ", $text);
		//That's right, I'm changing the text to htmlentities.
		$text = htmlentities($text);
		
		//That's right, I'm replacing double quotes with single quotes
		$text = str_replace('"', "'"  , $text );
		
		//That's right, I'm adding slashes twice, you wanna fight about it?!
		$text = addslashes($text);
		$text = addslashes($text);
		return $text;
	}
	
	public function Build_Comments($comments)
	{
		$html = "<table class=\"{$this->mode}\" width=\"100%\" border=\"0\">\n";

		$alt = TRUE;
		$rowcount = 0;
		$altrowcount = 0;
		foreach($comments as $row)
		{
			trim($this->data_format->Display("sentence", $row->comment));
			$td_class = $alt ? "align_left_alt" : "align_left";
			$div_id = "generic_comments" . ++$rowcount;
			$div_id2 = "agent_login" . ++$rowcount;
			$link_id = "AppInfoCommentDetails" . ++$altcowcount;
			
			$comment_clean = $this->make_clean($row->comment);
			$datetime = $row->date_created;

			$js_comment = "com=window.open('','comment','height=150,width=350,scrollbars=1,status=0,screenX=200,screenY=200;');";
			$js_comment .= "com.document.write('<html><head><title>Comment</title><style>body{font-family:arial,sans-serif;}</style></head><body>";
			$js_comment .= "<b>Agent:</b> <font size=-1>{$row->agent_name_formatted}</font>";
			$js_comment .= "<br><b>Date/Time:</b> <font size=-1>{$row->date_created}</font>";
		// We're converting the clean comment to HTML entities AGAIN to prevent HTML injection, no pretty text for you!
			$js_comment .= "<br><b>Comment:</b><br><font size=-1>".htmlentities($comment_clean)."</font>";
			$js_comment .= "</body></html>');com.document.close();com.focus();";

			$html .= "<div id=\"{$div_id}\" class=\"comment\">";
			$html .= "<a id=\"{$link_id}\" href=\"javascript:void(0);\" onclick=\"{$js_comment}\" class=\"{$td_class}\">";
			//$html .= "<a href=\"javascript:void(0);\" onmouseover=\"return overlib('{$comment_clean}', RIGHT, ABOVE);\" onmouseout=\"return nd();\" style=\"color:black; text-decoration: none; cursor: default;\">{$row->comment}</a>";
			$html .= "<span class=\"comment\"><nobr>".htmlentities($row->comment)."</nobr></span>";
			$html .= "<span class=\"agent_name\"><nobr>{$row->agent_name_short}</nobr></span>";
			$html .= "<span class=\"time\"><nobr>{$datetime}</nobr></span>";
			$html .= "<div class=\"clearer\"></div>";
			$html .= "</a></div>";
			$alt = !$alt;
		}

		$html .= "</table>\n";
		return $html;
	}

	public function Build_Contact_Information()
	{
		$pbx_enabled = (isset($this->data->pbx_enabled) && $this->data->pbx_enabled == 'true');


		$contacts = $this->data->contacts;
		require_once(CLIENT_CODE_DIR . '/ApplicationContactInterface.class.php');
		$contact_interface = new ApplicationContactInterface($pbx_enabled);

		$this->data->contact_quick_dial_button = ($pbx_enabled)
			? '<input type="button" value="Quick Dial" class="button" onClick="javascript:QuickDial();" />'
			: '';
		$this->data->contact_flag_selection = $contact_interface->getFlagDropdown($this->data->contact_flags);
		$this->data->contact_category_selection = $contact_interface->getCategoryDropdown($this->data->contact_categories);

		return $contact_interface->getHtml($contacts, $this->mode, $this->data_format, $this->module_name);
	}

	public function Build_Application_Flags()
	{	
		//get the application flags data
		$app_flags = $this->data->app_flags;
		$flags = $this->data->available_flags;
		
		//Get application Flag dropdown
		$html = '<select name="flag_type" id="flag_type" onChange="CustomFlag();" style="width: 100%;">';
		foreach ($flags as $flag)
		{	
			if (!array_key_exists($flag['name_short'], ($app_flags))) 
			{
				$html .= '<option value="' . $flag['name_short'] . '">' . $flag['name'] . '</option>';
			}
		}
		$html .= '<option value="**custom**">--------------</option>';
		$html .= '<option value="**custom**">**Custom Flag**</option>';
		$html .= '</select>';
		
		$this->data->application_flag_list=$html;
		
		//get list of application data
		$html = "

				";

		$alt = TRUE;
		$rowcount = 0;
		foreach ($app_flags as $flag => $details)
		{
			$alt = !$alt;
			$td_class = $alt ? 'align_left_alt' : 'align_left';
			$div_id = 'generic_contact_information' . $rowcount;
			$rowcount++;

			$html .= "
				<tr class='height'>
					<td class='{$td_class}' width='20%' style='padding-left: 4px;'>
					<nobr>
						<a href=\"#\" onClick=\"GetFlagDetails('".$details['name_short']."');\" > {$details['name_short']}</a>
					</nobr>
					<div id='{$flag}' style='visibility:hidden;display:none;'>
						<div id='{$flag}_name_short' style='visibility:hidden;'>{$flag}</div>
						<div id='{$flag}_name' style='visibility:hidden;'>{$details['name']}</div>
						<div id='{$flag}_agent' style='visibility:hidden;'>{$details['agent_name_first']} {$details['agent_name_last']}</div>
						<div id='{$flag}_date' style='visibility:hidden;'>{$details['date_created']}</div>
						<div id='{$flag}_loan_action' style='visibility:hidden;'>N/A</div>
						<div id='{$flag}_id' style='visibility:hidden;'>{$details['application_flag_id']}</div>
					</div>
					</td>
					<td class='{$td_class}' width='5%'><nobr></nobr></td>
					<td class='{$td_class}' width='20%'><nobr>{$details['agent_name_first']} {$details['agent_name_last']}</nobr></td>
					<td class='{$td_class}' width='55%'>
						<div id='{$div_id}'>
							<nobr>{$details['date_created']}</nobr>
						</div>
					</td>
				</tr>
				";
		}

	

		$html .= '';

		
		$this->data->current_app_flags=$html;
		
	}
		
	public function Build_Loan_Actions($loan_actions)
	{
		$alt = TRUE;
		$rowcount = 0;
		$html = '';
		if(count($loan_actions))
		{
			foreach($loan_actions as $row)
			{
				$this->data_format->Display("sentence", $row->description);
				$name = $row->description;
				$td_class = $alt ? "align_left_alt" : "align_left";
				$div_id = "generic_loan_actions";
				$comment_clean = str_replace(array("\r","\n"), "; ", $row->description);
				$comment_clean = str_replace('"', "&amp;quot;"  , $comment_clean );
				$comment_clean = str_replace("'", "&amp;apos;"  , $comment_clean );
//				$comment_clean = addslashes($comment_clean);

				$js_comment = "com=window.open('','loan_action','height=150,width=350,scrollbars=1,status=0,screenX=200,screenY=200;');";
				$js_comment .= "com.document.write('<html><head><title>Loan Action</title><style>body{font-family:arial,sans-serif;}</style></head><body>";
				$js_comment .= "<b>Agent:</b> <font size=-1>{$row->agent_name}</font>";
				$js_comment .= "<br><b>Date/Time:</b> <font size=-1>{$row->date_created}</font>";
				$js_comment .= "<br><b>Loan Action:</b><br><font size=-1>{$comment_clean}</font>";
				$js_comment .= "</body></html>');com.document.close();com.focus();";

				// Action / Description
				if(strlen($row->description) <= 24)
				{
					$short_desc = $row->description;
				}
				else
				{
					$short_desc = substr($row->description,0,21) . "...";
				}

				// Agent Name
				if(strlen($row->agent_name) <= 12)
				{
					$short_name = $row->agent_name;
				}
				else
				{
					$short_name = substr($row->agent_name,0,8) . "...";
				}

				// Status
				if(strlen($row->status) <= 9)
				{
					$short_status = $row->status;
				}
				else
				{
					$short_status = substr($row->status,0,6) . "...";
				}
				
				$html .= "<div class=\"{$div_id}\" onclick=\"{$js_comment}\">";
				$html .= "<span class=\"{$td_class}\" style=\"text-align:left; width:145px; overflow: hidden;\" onMouseOver=\"tooltip(event, '" . ($comment_clean) . "', null, -30);\" onMouseOut=\"tooltip(null);\"><nobr>{$short_desc}</nobr></span>";
				$html .= "<span class=\"{$td_class}\" style=\"text-align:left; width: 70px; overflow: hidden;\" onMouseOver=\"tooltip(event, '" . addslashes($row->agent_name) . "', null, -30);\" onMouseOut=\"tooltip(null);\"><nobr>{$short_name}</nobr></span>";
				$html .= "<span class=\"{$td_class}\" style=\"text-align:left; width: 66px; overflow: hidden;\" onMouseOver=\"tooltip(event, '" . addslashes($row->status) . "', null, -30);\" onMouseOut=\"tooltip(null);\"><nobr>{$short_status}</nobr></span>";
				$html .= "<span class=\"{$td_class}\" style=\"text-align:left; width: 72px; overflow: hidden;\" onMouseOver=\"tooltip(event, '" . addslashes($row->date_created) . "', null, -30);\" onMouseOut=\"tooltip(null);\"><nobr>{$row->date_created}</nobr></span>";

				$html .= "</div>\n";
				$alt = !$alt;
			}
		}
		return $html;
	}

	public function Build_Documents($documents)
	{
		$html = "<table class=\"{$this->mode}\" width=\"100%\" cellpadding=\"1px\">\n";

		$alt = TRUE;
		$rowcount = 0;

		$js_arcid = null;
		foreach($documents as $row)
		{

			$this->data_format->Display("smart_case", $row->description);

			$name = NULL;
		  	$name = $row->description;

			// Document name aliasing
			if (!empty($row->name_other))
			{
			if (preg_match("/^other_email/",$row->name)) // email related docs get full aliasing
			{
				$name = $row->name_other;
			}
			else if(preg_match("/^other/",$row->name)) // all "others" get partial aliasing
			{
				$name .= ": " . $row->name_other;
				}
			}

			switch(strtolower($row->document_api))
			{
				case 'condor':
					if($row->archive_id != "")
					{
						$name = "<a target='_blank' href='/show_pdf.php?archive_id={$row->archive_id}'>{$name}</a>";
					}
				break;

				case 'copia':
					if ($row->document_id_ext != "") 
					{
						@list($dnis, $tiff) = @explode(",", $row->document_id_ext);

						$name = "<a target=\"_blank\" href=\"/show_tiff.php?dnis={$dnis}&tiff={$tiff}\">{$name}</a>";
					}
				break;
			}

			$td_class = $alt ? "align_left_alt" : "align_left";
			$div_id = "generic_documents" . ++$rowcount;

			if ('failed' === $row->event_type) // [mantis:4898]
			{
				$row->event_type = '<span style="color: #DD0000;"><b>Fail</b></span>';
			}
			else
			{
				$row->event_type = ($row->event_type == "sent") ? "Sent" : "Rcvd";
				$modify_button = (strtolower($row->document_api) == 'condor' && isset($row->archive_id)) ? "<input type=button class='button2autosize' value='Details' onClick=\"window.open('?action=modify_received_document&document_id={$row->document_id}&previous_module={$this->module_name}','DocumentDetails','height=450,width=515,status=no,toolbar=no,scrollbars=yes');\">" : '';
				if($row->event_type == "Rcvd") $js_arcid .= "archive_id[{$row->archive_id}] = 1;\n";
			}

			$html .= "<tr class=\"height\">";
			// Removed extra tooltip
			$html .= "<td class=\"{$td_class}\" colspan=4><div id=\"{$div_id}\" style=\"overflow:hidden;font-weight:bold;\" onMouseOver=\"tooltip(event, '" . ($this->make_clean($name)) . "', null, -30);\" onMouseOut=\"tooltip(null);\"><nobr>{$name}</nobr></div></td>";
			$html .= "<td class=\"{$td_class}\" align=\"right\" style=\"text-align:right;\">{$modify_button}</td>";
			$html .= "</tr><tr class=\"height\">";
			$html .= "<td class=\"{$td_class} underlined\">". strtoupper($this->make_clean($row->document_method)) . "</td>";
			$html .= "<td colspan=2 class=\"{$td_class} underlined\"><nobr>" . $row->event_type . ": ". $this->make_clean($row->xfer_date)."</nobr></td>";
			$html .= "<td colspan=2 class=\"{$td_class} underlined\">Agent: ". $this->make_clean($row->login) ."</td>";
			$html .= "</tr>";
			$html .= "</tr>\n";
			$alt = !$alt;



		}


		$js = "<script language=javascript>


		function IDCheck(strString)
		   //  check for valid numeric strings
		   {
		   var strValidChars = '0123456789';
		   var strChar;
		   var blnResult = true;

		   if (strString.length == 0) return false;

		   //  test strString consists of valid characters listed above
		   for (i = 0; i < strString.length && blnResult == true; i++)
		      {
		      strChar = strString.charAt(i);
		      if (strValidChars.indexOf(strChar) == -1)
		         {
		         blnResult = false;
		         }
		      }
		   return blnResult;
		   }
		</script>
		";

		$html .= "</table>\n";
		$html = $js ."\n". $html;
		return $html;
	}

	public function Build_Packaged_Document_List($document_list, $send_type = null)
	{
		$is_last_4_ssn = in_array("disable_document_links", $this->data->read_only_fields); //mantis:4922
		$html = "<table class=\"{$this->mode}\">\n";

		$alt = TRUE;
		$rowcount = 0;
		foreach($document_list as $id => $row)
		{
			$td_class = $alt ? "align_left_alt" : "align_left";
			//GF 21767 - Gave each report div a more descriptive unique ID
			$id_name = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ", $row->name))));
			$id_type = "Packaged";
			$div_id = "App{$id_type}Doc{$id_name}";

			$html .= "<tr class=\"height\">
								<td width=\"100%\" title=\"{$row->document_package_name}\" class=\"{$td_class}\">
									<div id=\"{$div_id}\" style=\"width:280px; overflow:hidden\">
										<nobr><b>{$row->document_package_name}</b>";
			if ($send_type == 'both') $html .= "&nbsp;&nbsp;({$row->send_method})";
			$html .= "</nobr>
									</div>
								</td>
								<td class=\"{$td_class}\">";
			switch(strtolower($row->document_api)) 
			{
				case "condor":
					$html .= "<input type=\"checkbox\" name=\"document_list[{$id}]\" value=\"" . $row->document_package_name ."\" onChange=\"var cstat = this.checked ; for (var i = 0; i < this.form.elements.length; i++) { if (this.form.elements[i].checked == true) { this.form.elements[i].checked = false } } this.checked = cstat;\" onClick=\"javascript:var is_checked = false; for (var i = 0; i < this.form.elements.length; i++) { if (this.form.elements[i].checked) { is_checked = true } } VerifySelection(is_checked);\">"; //[mantis:3304]
//					$html .= "<input type=\"checkbox\" name=\"document_list[{$id}]\" value=\"" . $row->document_package_name ."\" onChange=\"var cstat = this.checked ; this.checked = cstat;\" onClick=\"javascript:var is_checked = false; for (var i = 0; i < this.form.elements.length; i++) { if (this.form.elements[i].checked) { is_checked = true } } VerifySelection(is_checked);\">"; //[mantis:3304]
					break;

				default:
					$html .= "<input type=\"checkbox\" name=\"document_list[{$id}]\" value=\"" . $row->document_package_name . "\" onClick=\"javascript:var is_checked = false; for (var i = 0; i < this.form.elements.length; i++) { if (this.form.elements[i].checked) { is_checked = true } } VerifySelection(is_checked);\">"; //[mantis:3304]

			}



			$html .= "</td></tr>\n";

			if (count($row->bodyparts) > 0)
			{
				foreach ($row->bodyparts as $key => $value)
				{
					$html .= "<tr class=\"height\">
									<td width=\"100%\" title=\"{$value}\" class=\"{$td_class}\">
										<div id=\"{$div_id}\" style=\"width:280px; overflow:hidden\">";

					switch(strtolower($row->document_api)) 
					{
						case "condor":
							if ($is_last_4_ssn) //mantis:4922
								$html .= $row->description . " ({$row->send_method})";
							else
								$html .= "<nobr><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><a target=\"_blank\" href=\"/document_preview.php?application_id={$this->data->application_id}&document_id={$key}\">{$value}</a></nobr>";
							break;

						default:
							$html .= "<nobr><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b>{$value}</nobr>";

					}

					$html .= "			</div>
									</td>
									<td class=\"{$td_class}\">&nbsp;</td>
								</tr>\n";
				}
			}

			$alt = !$alt;
		}

		$html .= "</table>\n";
		return $html;

	}

	public function Build_Document_Restrictions($document_list)
	{
		if(empty($this->data->js_all_docs))
		{
			$this->data->js_email_only_docs = (string) "0";
			$this->data->js_fax_only_docs = (string) "0";
			$this->data->js_all_docs = (string) "0";
			$this->data->js_copia_docs = (string) "0";
			$this->data->js_condor_docs = (string) "0";
		}

		$doc_list = array_values(array_keys($document_list));
		sort($doc_list);

		foreach($doc_list as $id)
		{

			$this->data->js_all_docs .= ",".$id;

			switch($document_list[$id]->send_method) 
			{
				case "email":
					if (!strstr(",{$id}",$this->data->js_email_only_docs)) $this->data->js_email_only_docs .= ",".$id;
					break;

				case "fax":
					if (!strstr(",{$id}",$this->data->js_fax_only_docs)) $this->data->js_fax_only_docs .= ",".$id;
					break;

			}

			switch(strtolower($document_list[$id]->document_api)) 
			{
				case "condor":
					if (!strstr(",{$id}",$this->data->js_condor_docs)) $this->data->js_condor_docs .= ",".$id;
					break;

				case "copia":
				default:
					if (!strstr(",{$id}",$this->data->js_copia_docs)) $this->data->js_copia_docs .= ",".$id;

			}

		}

	}

	public function Build_Document_List($document_list, $list_type = 'send')
	{
		$is_last_4_ssn = in_array("disable_document_links", $this->data->read_only_fields); //mantis:4922

		if (!count($document_list))
			return false;

		$html = "<table class=\"{$this->mode}\">\n";

		$alt = TRUE;
		$rowcount = 0;


		foreach($document_list as $id => $row)
		{
			if ($row->active != "active") continue;

			if ($row->only_receivable == 'yes' || preg_match("/^other_email/", $row->name) ) continue;

			$td_class = $alt ? "align_left_alt" : "align_left";
			//GF 21767 - Gave each report div a more descriptive unique ID
			$id_name = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ", $row->name))));
			$id_type = ucfirst($list_type);
			$div_id = "App{$id_type}Doc{$id_name}";

			if ($list_type == "esig") $row->send_method = 'email';

			$send_split = explode(",",$row->send_method);

			$html .= "<tr class=\"height\"><td width=\"100%\" title=\"{$row->description}\" class=\"{$td_class}\"><div id=\"{$div_id}\" style=\"width:280px; overflow:hidden\"><nobr>";
			switch(strtolower($row->document_api)) 
			{
				case "condor":

					if ($is_last_4_ssn) //mantis:4922
						$html .= $row->description . " ({$row->send_method})";
					else
						$html .= "<a target=\"_blank\" href=\"/document_preview.php?application_id={$this->data->application_id}&document_id={$id}\">{$row->description}</a> ($row->send_method)";
					break;

				case "copia":
				default:

					$html .= $row->description . " ({$row->send_method})";
			}
//			if ($row->name == "other") $html .= "&nbsp;<input type=\"text\" name=\"docname_" . strtolower($row->name) . "\" size=\"24\" />";
			$html .= "</nobr></div></td><td class=\"{$td_class}\">";

			$onClick = "doc_send_{$list_type}_ot.toggle(this);";

			$html .= "<input type=\"checkbox\" name=\"document_list[{$id}]\" value=\"{$row->description}\" onClick=\"{$onClick}\">";

			$html .= "</td></tr>\n";

			$alt = !$alt;

		}

		$html .= "</table>\n";
		return $html;
	}

	public function Build_Receive_Document_List($document_list, $sent_documents)
	{
		$is_last_4_ssn = in_array("disable_document_links", $this->data->read_only_fields); //mantis:4922

		if (!count($document_list))
			return false;

		$html = "<table class=\"{$this->mode}\">\n";

		$alt = TRUE;
		$rowcount = 0;

		$live_docs = array();
/*
		$body_list  = array();
		foreach($this->data->send_doc_list as $doc) 
		{
			if ( ( isset($doc->email_body_id) && ($doc->document_list_id != $doc->email_body_id && !in_array($doc->email_body_id,$body_list) ) ) ||
 				 ( isset($doc->fax_body_id) && ($doc->document_list_id != $doc->fax_body_id  && !in_array($doc->fax_body_id,$body_list) ) ) ||
 				 ( isset($doc->esig_body_id) && ($doc->document_list_id != $doc->esig_body_id  && !in_array($doc->fax_body_id,$body_list) ) )
 				 ) {
 				 	foreach (Array('email','fax','esig') as $t) 
					{
 				 		$a = $t . "_body_id";
 				 		if (isset($doc->{$a})) 
						{
 				 			$body_list[] = $doc->{$a};
 				 		}
 				 	}
 				 }
		}

//var_dump($document_list);

		foreach($sent_documents as $row)
		{
			if (in_array($row->document_list_id, $live_docs) || in_Array($row->document_list_id, $body_list)) continue;
			$live_docs[] = $row->document_list_id;
			if (!strstr(",{$row->document_list_id}",$this->data->js_all_docs)) $this->data->js_all_docs .= ",".$row->document_list_id;

			$td_class = $alt ? "align_left_alt" : "align_left";
			$div_id = "generic_doclist" . ++$rowcount;

			$html .= "<tr class=\"height\"><td width=\"100%\" title=\"{$row->description}\" class=\"{$td_class}\"><div id=\"{$div_id}\" style=\"width:280px; overflow:hidden\"><nobr>";
			switch(strtolower($row->document_api))
			{
				case 'condor':
					if (!strstr(",{$row->document_list_id}",$this->data->js_condor_docs)) $this->data->js_condor_docs .= ",".$row->document_list_id;
					if($row->archive_id != "")
					{
						$html .= "<a target=\"_blank\" href=\"/show_pdf.php?archive_id={$row->archive_id}\">{$row->description}</a>";
					} 
					else 
					{
						$html .= $row->description;
					}
				break;

				case 'copia':
					if (!strstr(",{$row->document_list_id}",$this->data->js_copia_docs)) $this->data->js_copia_docs .= ",".$row->document_list_id;
					if ($row->document_id_ext != "") 
					{
						@list($dnis, $tiff) = @explode(",", $row->document_id_ext);

						$html .= "<a target=\"_blank\" href=\"/show_tiff.php?dnis={$dnis}&tiff={$tiff}\">{$row->description}</a>";
					} 
					else 
					{
						$html .= $row->description;
					}
				break;
			}

			$onClick = "doc_send_recv_ot.toggle(this);";

//			if ($row->name == "other") $html .= "&nbsp;<input type=\"text\" name=\"docname_" . strtolower($row->name) . "\" size=\"24\" />";
			if (preg_match("/^other/",strtolower($row->name))) $html .= "&nbsp;<input type=\"text\" name=\"docname_" . strtolower($row->name) . "\" size=\"24\" />";
			$html .= "</nobr></div></td><td class=\"{$td_class}\">";
			$html .= "<input type=\"checkbox\" name=\"document_list[{$row->document_list_id}]\" value=\"{$row->description}\" onClick=\"doc_send_recv_ot.toggle(this); \">";
			$html .= "</td></tr>\n";

			$alt = !$alt;
		}
*/
		foreach($document_list as $id => $row)
		{
			if (in_array($row->document_list_id, $live_docs) || preg_match("/^other_email/", $row->name) ) continue;
			$td_class = $alt ? "align_left_alt" : "align_left";
			//GF 21767 - Gave each report div a more descriptive unique ID
			$id_name = str_replace(" ","",ucwords(str_replace("-"," ",str_replace("_"," ", $row->name))));
			$id_type = 'Receive';
			$div_id = "App{$id_type}Doc{$id_name}";

			$html .= "<tr class=\"height\"><td width=\"100%\" title=\"{$row->description}\" class=\"{$td_class}\"><div id=\"{$div_id}\" style=\"width:280px; overflow:hidden\"><nobr>";
			switch(strtolower($row->document_api)) 
			{
				case "condor":
//					if ($row->name != "other" && $row->only_receivable != 'yes')
					if (!preg_match("/^other/",strtolower($row->name)) && $row->only_receivable != 'yes')
					{
						if ($is_last_4_ssn) //mantis:4922
							$html .= $row->description . " ({$row->send_method})";
						else
							$html .= "<a target=\"_blank\" href=\"/document_preview.php?application_id={$this->data->application_id}&document_id={$id}\">{$row->description}</a>";
					}
					else $html .= $row->description;
					break;

				case "copia":
				default:
					$html .= $row->description;
			}
//			if ($row->name == "other") {
			if (preg_match("/^other/",strtolower($row->name))) 
			{

				if (strtolower($row->document_api) == "condor" && !strstr(",{$row->document_list_id}",$this->data->js_condor_docs)) $this->data->js_condor_docs .= ",".$row->document_list_id;
				elseif (strtolower($row->document_api) == "copia" && !strstr(",{$row->document_list_id}",$this->data->js_copia_docs)) $this->data->js_copia_docs .= ",".$row->document_list_id;

				$html .= "&nbsp;<input type=\"text\" name=\"docname_" . strtolower($row->name) . "\" size=\"24\" />";

			}
			$html .= "</nobr></div></td><td class=\"{$td_class}\">";
			$html .= "<input type=\"checkbox\" name=\"document_list[{$id}]\" value=\"{$row->description}\" onClick=\"doc_send_recv_ot.toggle(this); \">";
			$html .= "</td></tr>\n";
			$alt = !$alt;
		}

		$html .= "</table>\n";
		return $html;
	}
	
	public function Build_Application_List($application_list)
	{
		$list_html = "";
		foreach($application_list as $application)
		{
			$display_pay_date_1 = $application->paydates['paydates'][0] . " &nbsp; " . $application->paydates['paydays'][0];
			$display_pay_date_2 = $application->paydates['paydates'][1] . " &nbsp; " . $application->paydates['paydays'][1];
			$display_pay_date_3 = $application->paydates['paydates'][2] . " &nbsp; " . $application->paydates['paydays'][2];
			$display_employer_phone = $this->data_format->Display("phone", $application->employer_phone);

			// GF #11926: When there's no first payment date (denied reacts, etc) don't show anything [benb]
			$display_date_first_payment = ($application->date_first_payment == "12-31-1969") ? " &nbsp; " : $application->date_first_payment;

			$list_html .= "
			<tr>
			   <td><a href=\"?action=show_applicant&application_id={$application->application_id}\">{$application->application_id}</a></td>
			   <td>{$application->date_fund_actual}</td>
			   <td>{$application->fund_actual}</td>
			   <td>{$application->last_payment_date}</td>
			   <td>{$display_date_first_payment}</td>
			   <td>{$application->status_long}</td>
			</tr>
			<tr>
			   <td style=\"border-bottom: 1px solid gray;\">{$application->date_created}</td>
			   <td style=\"border-bottom: 1px solid gray;\">{$application->employer_name}</td>
			   <td style=\"border-bottom: 1px solid gray;\">{$application->employer_phone}</td>
			   <td style=\"border-bottom: 1px solid gray;\">$display_pay_date_1</td>
			   <td style=\"border-bottom: 1px solid gray;\">$display_pay_date_2</td>
			   <td style=\"border-bottom: 1px solid gray;\">$display_pay_date_3</td>
			</tr>
			";		
		}
		return $list_html;
	}
	
	public function Build_Personal_References_Table(&$get_links)
	{
		require_once(SERVER_CODE_DIR . "validate.class.php");

		$html = "";
		$edit_html = '';
		$num_refs = count($this->data->references) + 1;
		$max_refs = Validate::$NUM_PERSONAL_REFERENCES;
	//	$this->Set_Contact_Data();

		for ($x=0; $x < $num_refs; $x++)
		{
			$ref_num = $x+1;

			$format_list = array(	'ref_name'         => 'smart_case',
									'ref_phone'        => 'phone',
									'ref_relationship' => 'smart_case');

			$data = new stdClass();
			
			$data->ref_id           = isset($this->data->references[$x]) ? $this->data->references[$x]->personal_reference_id : null;
			$data->ref_name         = isset($this->data->references[$x]) ? $this->data->references[$x]->name_full : null;
			$data->ref_phone        = isset($this->data->references[$x]) ? $this->data->references[$x]->phone_home : null;
			$data->ref_relationship = isset($this->data->references[$x]) ? $this->data->references[$x]->relationship : null;
			$link_id = "ref_phone_{$x}_link";
			$data->phone_link = $get_links->Generate_Phone_Link($this->data->application_id, $data->ref_phone, $link_id, false, 'Personal Reference');

			$this->data_format->Display_Many($format_list, $data);

			if($x > 0) // Add some spacing
			{
				$html      .="				<tr><td colspan=\"0\">&nbsp;</td></tr>\n";
				$edit_html .="				<tr><td colspan=\"0\">&nbsp;</td></tr>\n";
			}
			$contact_icon = 'contact_ref_phone_' . $ref_num;
			if(! empty($data->ref_id))
			{
				
				$html .= <<<ENDHTML

				<tr class="height">
					<td class="align_left_alt_bold">&nbsp;Reference #$ref_num</td>
					<td class="align_left_alt">&nbsp;</td>
					<td class="align_left_alt">&nbsp;</td>
				</tr>
				<tr class="height">
					<td class="align_left_bold">&nbsp;Name:</td>
					<td class="align_left">&nbsp;</td>
					<td class="align_left">{$data->ref_name}</td>
				</tr>
				<tr class="height">
					<td class="align_left_alt_bold">&nbsp;Phone Number:</td>
					<td class="align_left_alt">{$this->data->$contact_icon}&nbsp;</td>
					<td class="align_left_alt">{$data->ref_phone} {$data->phone_link}</td>
				</tr>
				<tr class="height">
					<td class="align_left_bold">&nbsp;Relationship:</td>
					<td class="align_left">&nbsp;</td>
					<td class="align_left">{$data->ref_relationship}</td>
				</tr>

ENDHTML;
			}

			if($x < $max_refs)
			{
				$icon = isset($this->data->$contact_icon) ? $this->data->$contact_icon : null;
				$edit_html .= <<<ENDHTML
				<tr class="height">
					<td class="align_left_alt_bold">&nbsp;Reference #$ref_num</td>
					<td class="align_left_alt">&nbsp;<input type="hidden" id="personal_ref_id_{$ref_num}" name="personal_ref_id_{$ref_num}" value="{$data->ref_id}"></td>
					<td class="align_left_alt">&nbsp;</td>
				</tr>
				<tr class="height">
					<td class="align_left_bold"><span class="std_text" id="name_span_{$ref_num}">&nbsp;Name:</span>&nbsp;</td>
					<td class="align_left">&nbsp;</td>
					<td class="align_left"><input type="text" id="ref_name_{$ref_num}" name="ref_name_{$ref_num}" value="{$data->ref_name}" maxlength="25" onkeypress="return editKeyBoard(this,keybAlpha,((window.event)?window.event:event));"></td>
				</tr>
				<tr class="height">
					<td class="align_left_alt_bold"><span class="std_text" id="phone_span_{$ref_num}">&nbsp;Phone Number</span>:&nbsp;</td>
					<td class="align_left_alt">{$icon}&nbsp;</td>
					<td class="align_left_alt"><input type="text" id="ref_phone_{$ref_num}" name="ref_phone_{$ref_num}" value="{$data->ref_phone}" maxlength="14" onkeypress="return editKeyBoard(this,keybNumeric,((window.event)?window.event:event));" onkeyup="mask(this.value,this,'0,4,5,9',Array('(',')',' ','-'),((window.event)?window.event:event));"></td>
				</tr>
				<tr class="height">
					<td class="align_left_bold"><span class="std_text" id="relationship_span_{$ref_num}">&nbsp;Relationship</span>:&nbsp;</td>
					<td class="align_left">&nbsp;</td>
					<td class="align_left"><input type="text" id="ref_relationship_{$ref_num}" name="ref_relationship_{$ref_num}" value="{$data->ref_relationship}" maxlength="25"  onkeypress="return editKeyBoard(this,keybAlpha,((window.event)?window.event:event));"></td>
				</tr>

ENDHTML;
			}

			unset($data);
			unset($format_list);
		}

		$this->data->personal_references_display_html = $html;
		$this->data->personal_references_edit_html = $edit_html;

	}
	public function Build_Debt_Consolidation_Table()
	{

		///debt_consolidation
		$data = new stdClass();
		$payment_type = "debt_consolidation";
		$data->layer_id = "layout1group1layer5edit";
		$data->layer_style = "%%%{$payment_type}_edit_layer%%%";
		$data->layer_form_name = "Debt Consolidation";
		$data->layer_form_id = "{$payment_type}_payment_form";
		$data->payment_type = $payment_type;
		$j = $this->data->num_arranged_payments;


		$payment_type_drop = "
		<td class=\"align_left\">Debt Company: <a href=\"#\" onClick=\"OpenDebtCompanyPopup('debt_company','Add Debt Company','$this->mode','');\">add</a></td><td class=\"align_left\">
		<select id=\"debt_consolidation_company\" name=\"debt_consolidation_company\" onChange=\"selected_debt_company=this.options[this.selectedIndex].value;\">";
		if(isset($this->data->debt_companies) && is_array($this->data->debt_companies))
		{
			foreach ($this->data->debt_companies as $key => $value)
			{
				$payment_type_drop .= "<option value=\"{$key}\">{$value->company_name}</option>\n";
			}
		}
		$payment_type_drop .= "</select>";
		$payment_type_drop .= "<a href=\"#\" onClick=\"OpenDebtCompanyPopup('debt_company_edit','Edit Debt Company','$this->mode',document.getElementById('debt_consolidation_company').options[document.getElementById('debt_consolidation_company').selectedIndex].value);\">edit</a></td>";

		$incl_pnd_checked = ' checked="checked"';
		$which_amount = 'posted_total';
		$payment_table = <<<EOS
			<table cellspacing="0" cellpadding="0" width="100%" class="%%%mode_class%%%">
			<tr class="height">
				{$payment_type_drop}
				<td class="align_left" colspan="2"><input onChange="CreateDebitPayments(false);" type="checkbox" value="y" id="{$payment_type}_arr_incl_pend" name="{$payment_type}_arr_incl_pend"> Include Pending Items</td>
			</tr>
			<tr>
				<td class="align_left">
					Payment Amount:
				</td><td class="align_left">
					<input id="{$payment_type}_amount" name="{$payment_type}_amount" type="text" size="10" value="0" OnKeyUp="CreateDebitPayments(false);">
				</td>
				<td class="align_left">
					Payment Date:
					</td><td class="align_left">
					<input id="{$payment_type}_date" name="{$payment_type}_date" type="text" size="10" readonly onChange="CreateDebitPayments(false);">
					<a href="#" onClick="PopCalendar1('{$payment_type}_date', event, '{$this->data->date_fund_actual}',false);">select</a>
					<input type=hidden name=debt_payments value=0>
					<input type=hidden name=debt_total_amount value='%%%posted_total%%%'>
					<input type=hidden name="debt_total_posted" id="debt_total_posted" value='%%%posted_total%%%'>
					<input type=hidden name="debt_total_pending" id="debt_total_pending" value='%%%posted_pending_total%%%'>
					<input type=hidden name="debt_interest_accrued_posted" id="debt_interest_accrued_posted" value='%%%interest_accrued_now_posted%%%'>
					<input type=hidden name="debt_interest_accrued_pending" id="debt_interest_accrued_pending" value='%%%interest_accrued_now_pending%%%'>

				</td>
			</tr>
			<tr class="height">
			<td class="align_left">Maximum Amount: </td>
			<td class="align_left">$<span id="debt_amount_arrange">%%%posted_total%%%</span></td>
			<td class="align_left">Interest Accrued: </td>
			<td class="align_left">$<span id="interest_accrued">%%%interest_accrued_now_posted%%%</span></td>
			</tr>
			</table>
EOS;

		$payment_table .= "<table border=0 id=\"{$payment_type}_payment_table\" cellspacing=\"0\" cellpadding=\"0\" height=0 width=\"100%\" border=\"0\" class=\"%%%mode_class%%% payment_template\">\n";

		$payment_table .= " <thead class=\"fixedHeader\">\n";
		$payment_table .= "  <tr class=\"height\">\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\">Payment Date</th>\n<th class=\"%%%mode_class%%%\"></th>";
		$payment_table .= "   <th class=\"%%%mode_class%%%\">Payment Amount</th><th class=\"%%%mode_class%%%\"></th>\n";
		$payment_table .= "	  <th class=\"%%%mode_class%%%\">Total Amount</th>\n<th class=\"%%%mode_class%%%\"></th>";
		$payment_table .= "  </tr>\n";
		$payment_table .= " </thead>\n";



		$payment_table .= "<tbody class=\"scrollContent\">\n";

		$payment_table .= "<tr class=\"height\" id=\"{$payment_type}_payment_row\"  style=\"display: table-row;\" >\n";

		$payment_table .= "<td id=\"paydate_display_list\" class=\"align_center\"></td><td class=\"align_center\"></td>";

		$payment_table .= "<td id=\"payamt_display_list\"></td><td></td><td id=\"total_display_list\" class=\"align_right\"></td><td class=\"align_center\"></td>";

		$payment_table .= "	</tr>";

		$payment_table .= " </tbody>\n";

		$payment_table .= " </table>\n";
		$data->payment_table = $payment_table;



		$template_html = file_get_contents(CLIENT_VIEW_DIR . "debt_consolidation_template.html");
		$str = Display_Utility::Token_Replace($template_html, (array)$data);
		return $str;
	}

	public function Build_Payment_Table($payment_type)
	{
		if(isset($this->data->business_rules['service_charge']['svc_charge_type']))
		{
			$daily_interest_flag = $this->data->business_rules['service_charge']['svc_charge_type'];
		}
		else
		{
			$daily_interest_flag = 'Fixed';
		}
		$data = new stdClass();
		if(isset($this->data->business_rules['one_time_arrangement_min']))
		{
			$data->arrangement_min_payment = $this->data->business_rules['one_time_arrangement_min'];
		}
		else
		{
			$data->arrangement_min_payment = '25';
		}

		if(isset($this->data->business_rules['display_action_effective']))
		{
			$data->display_action_effective = $this->data->business_rules['display_action_effective'];
		}
		else
		{
			$data->arrangement_min_payment = 'action';
		}
		
		if(isset($this->data->business_rules['check_payment_type']))
		{
			$data->check_type = $this->data->business_rules['check_payment_type'];
		}
		else
		{
			$data->check_type = 'ACH';
		}
		
		$this->data->svc_charge_percentage = $this->data->business_rules['service_charge']['svc_charge_percentage']; /* this is in the template */
		$this->data->interest_accrual_limit = $this->data->business_rules['service_charge']['interest_accrual_limit']; /* this is in the template */

		$payment_table = "";

		$payment_type_drop = $this->get_payment_type_drop($payment_type);

		$data->payment_type = $payment_type;
		$data->layer_form_id = "{$payment_type}_payment_form";

		$which_total = 'posted_total';
		$this->data->which_total_value = isset($this->data->posted_total) ? $this->data->posted_total : 0;

		// Enable settlement if they're in collections contact
		if (($this->data->level0 == 'dequeued' || $this->data->level0 == 'queued') &&  $this->data->level1 == 'contact' && $this->data->level2 == 'collections' && $this->data->level3 == 'customer' && $this->data->level4 == '*root' && ((isset($this->data->business_rules['settlement_offer']['has_arranged_settlements']) && $this->data->business_rules['settlement_offer']['has_arranged_settlements'] == 'yes')))
		{
			$has_settlement = true;
		}
		else
		{
			$has_settlement = false;
		}


		switch($payment_type)
		{
		case "ad_hoc":
			$has_discount = false;
			//retreive unscheduled payments from the schedule
			$scheduled_debits = Retrieve_Scheduled_Debits($this->data->schedule_status->debits);
			$j = count($scheduled_debits);
			$data->layer_id = "layout1group1layer4edit";
			$data->layer_style = "%%%ad_hoc_edit_layer%%%";
			$data->layer_form_name = "Ad Hoc Scheduling";
			break;
		case "manual_payment":
			$has_discount = false;
			$j = 3;
			$data->layer_id = "layout1group1layer3edit";
			$data->layer_style = "%%%manual_payment_edit_layer%%%";
			$data->layer_form_name = "Manual Payment";
			break;
		case "partial_payment":
			$has_discount = false;
			$j = 1;
			$data->layer_id = "layout1group1layer9edit";
			$data->layer_style = "%%%partial_payment_edit_layer%%%";
			$data->layer_form_name = "Partial Payment";
			break;
		case "next_payment_adjustment":
			$has_discount = false;
			$j = 1;
			$data->layer_id = "layout1group1layer7edit";
			$data->layer_style = "%%%next_payment_adjustment_edit_layer%%%";
			$data->layer_form_name = "Next Payment Adjustment";
			$which_total = 'posted_pending_total';
			$this->data->which_total_value = isset($this->data->posted_pending_total) ? $this->data->posted_pending_total : 0;
			
			break;
		case "payment_arrangement":
			$has_discount = true;
			$trimmed_value = substr($this->data->fund_actual, 0, 3);

			$j = $this->data->num_arranged_payments;
			if ($this->data->has_failed_payment_arrangements) 
			{
				$j = $this->data->num_arranged_payments_failed;
			}
			
			$data->layer_id = "layout1group1layer1edit";
			$data->layer_style = "%%%payment_arrangement_edit_layer%%%";
			$data->layer_form_name = "Payment Arrangements";

			$application = ECash::getApplicationById($this->data->application_id);
			if ($application->getFlags()->get('arr_incl_pend'))
			{
				$which_total = 'posted_pending_total';
				$this->data->which_total_value = isset($this->data->posted_pending_total) ? $this->data->posted_pending_total : 0;
			}
			break;
		}

		$payment_table .= "
			<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" class=\"%%%mode_class%%%\">
				  <tr class=\"height\">
						" . $this->get_num_payments_cell($payment_type, $j) . "
						" . $this->get_include_pending_cell($payment_type) . "
						" . $this->get_discount_percentage_cell($payment_type, $has_discount, $has_settlement) . "
				  </tr>
				</table>
			<span style='display:none;' id=\"{$payment_type}_arranged_remaining\">%%%{$which_total}%%%</span>
			<span style='display:none;' id=\"{$payment_type}_arranged_amount\">0.00</span>
			<span style='display:none;' id=\"{$payment_type}_principal_total\">0.00</span>
			<span style='display:none;' id=\"{$payment_type}_service_total\">0.00</span>
			";

		$payment_table .= "<table id=\"{$payment_type}_payment_table\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\" class=\"%%%mode_class%%% payment_template\">\n";
		$payment_table .= " <thead class=\"fixedHeader\" width=728>\n";
		$payment_table .= "  <tr class=\"height\">\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:115px;\">Payment Type</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:136px;\">Payment Date</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:70px;\" id='{$payment_type}_interest_balance_title'>Interest<br>Balance</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:70px;\">Fee<br>Balance</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:70px;\" id='{$payment_type}_interest_accrued_title'>Interest<br>Accrued</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:70px;\">Amount</th>\n";
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\" width:70px;\">Principal<br>Balance</th>\n";
		if (($payment_type == 'payment_arrangement') && ($this->mode == 'conversion'))
		{
			$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\"width:115px;\">Collections Agent</th>\n";
		}
		else
		{
			$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\"width:115px;\">Description</th>\n";
		}
		$payment_table .= "   <th class=\"%%%mode_class%%%\" style=\"width:26px;\"></th>\n";
		$payment_table .= "  </tr>\n";
		$payment_table .= " </thead>\n";
		$payment_table .= " <tbody class=\"scrollContent\" style='height:200px; padding-top:2px;'>\n";

		$holidays = Fetch_Holiday_List();
		$pd_calc = new Pay_Date_Calc_3($holidays);

		for ($i = 0; $i < $j; $i++)
		{
			$style = "style=\"display: table-row; white-space:nowrap;\"";
			$valstr = "value=\"0\"";

			if ($payment_type == 'ad_hoc')
			{
				$style = "style=\"display: table-row;\"";
				$de = $scheduled_debits[$i];
				$value = -($de->principal_amount + $de->fee_amount);
				$value = number_format($value, 2, '.','');
				$amount_col = "<input type=\"hidden\" name=\"{$payment_type}_amount_{$i}\" id=\"{$payment_type}_amount_{$i}\" value=\"{$value}\" maxlength=\"7\">";
				$amount_col .= "<span>{$value}</span>";
				$amount_col .= "<input type=\"hidden\" id=\"{$payment_type}_actual_amount_{$i}\" name=\"{$payment_type}_actual_amount_{$i}\" value=\"{$value}\" maxLength=\"7\">\n";
				$amount_col .= "<input type=\"hidden\" id=\"{$payment_type}_principal_amount_{$i}\" name=\"{$payment_type}_principal_amount_{$i}\" value=\"".-$de->principal_amount."\">\n";
				$amount_col .= "<input type=\"hidden\" id=\"{$payment_type}_fee_amount_{$i}\" name=\"{$payment_type}_fee_amount_{$i}\" value=\"".-$de->fee_amount."\">\n";
				$date_col = " <a href=\"#\" onClick=\"PopCalendar1('{$payment_type}_date_{$i}', event, '{$this->data->date_fund_actual}');\">select</a>";
				$date_col .= "&nbsp;<input id=\"{$payment_type}_date_{$i}\" value=\"".date("m/d/Y", strtotime($de->date_effective))."\" name=\"{$payment_type}_date_{$i}\" type=\"text\" size=\"10\">";
			}
			else
			{
				$amount_col = "<input id=\"{$payment_type}_amount_{$i}\" name=\"{$payment_type}_amount_{$i}\" type=\"text\" size=\"7\" maxlength=\"7\" {$valstr} >\n";
				$amount_col .= "<input type=\"hidden\" id=\"{$payment_type}_actual_amount_{$i}\" name=\"{$payment_type}_actual_amount_{$i}\">";
				
				$yy_mm_dd_format = substr($this->data->date_fund_actual, 6) . '-' . substr($this->data->date_fund_actual, 0, 5);
				$next_day = $pd_calc->Get_Business_Days_Forward( $yy_mm_dd_format, 1);

				if ($payment_type == 'manual_payment') 
				{
					$date_col = " <a href=\"#\" onClick=\"PopCalendar4('{$payment_type}_date_{$i}', event, '{$next_day}', true,'{$payment_type}_payment_type_{$i}');\">select</a>";
					$date_col .= "&nbsp;<input id=\"{$payment_type}_date_{$i}\" name=\"{$payment_type}_date_{$i}\" type=\"text\" size=\"10\" READONLY>";
				} 
				elseif(in_array($payment_type, array('next_payment_adjustment', 'partial_payment'))) 
				{
					//todo: can select date X days forward from next paydate based on rule
					if(isset($this->data->business_rules['one_time_arrangement_grace']))
					{
						$days_forward_allowed = $this->data->business_rules['one_time_arrangement_grace'];
					}
					else
					{
						$days_forward_allowed = 7;
					}
					try 
					{
						if($days_forward_allowed == 0)
						{
							$date_allowed_forward = $pd_calc->Get_Calendar_Days_Backward(substr($this->data->paydate_1, 6) . '-' . substr($this->data->paydate_1, 0, 5), 1);
						}
						else 
						{
							$date_allowed_forward = $pd_calc->Get_Calendar_Days_Forward(substr($this->data->paydate_0, 6) . '-' . substr($this->data->paydate_0, 0, 5), $days_forward_allowed - 1);
						}
					}
					catch (Exception $e)
					{
						$date_allowed_forward = '';
					}
					$date_col = " <a href=\"#\" onClick=\"PopCalendar6('{$payment_type}_date_{$i}', event, '{$this->data->date_fund_actual}','". $date_allowed_forward . "', '{$payment_type}_payment_type_{$i}');\">select</a>";
					$date_col .= "&nbsp;<input id=\"{$payment_type}_date_{$i}\" name=\"{$payment_type}_date_{$i}\" type=\"text\" size=\"10\" READONLY>";
					
				}
				else 
				{
					$date_col = " <a href=\"#\" onClick=\"PopCalendar1('{$payment_type}_date_{$i}', event, '{$this->data->date_fund_actual}', false, '{$payment_type}_payment_type_{$i}');\">select</a>";
					$date_col .= "&nbsp;<input id=\"{$payment_type}_date_{$i}\" name=\"{$payment_type}_date_{$i}\" type=\"text\" size=\"10\" READONLY>";
				}
			}

			if (($payment_type == 'payment_arrangement') && ($this->mode == 'conversion'))
			{
				if ($i == 0)
				{
					$desc_field = "<td style='width:115px;'><select name=\"collections_agent\">\n";
					foreach ($this->data->collections_agents as $id => $ca)
					{
						$desc_field .= "<option value=\"{$id}\">{$ca}</option>\n";
					}
					$desc_field .= "</select>\n</td>\n";
				}
				else
				{
					$desc_field = "";
				}
			}
			else
			{
				$desc_field = "<td style='width:115px;'><input id=\"{$payment_type}_desc_{$i}\" name=\"{$payment_type}_desc_{$i}\"type=\"text\" size=\"15\" maxlength=\"40\"></td>\n";
			}

			$payment_table .= "<tr id=\"{$payment_type}_payment_row_{$i}\" {$style}>\n";
			$payment_table .= "<td style='width:115px;'>". str_replace("payment_type", "{$payment_type}_payment_type_{$i}", $payment_type_drop) . "</td>\n";
			$payment_table .= "<td style=\"width:136px; align: right; white-space:nowrap;\">".$date_col."</td>\n";
			$payment_table .= " <td id=\"{$payment_type}_payment_row_{$i}_interest_balance\" style='width:70px;'></td>\n";
			$payment_table .= " <td id=\"{$payment_type}_payment_row_{$i}_fee_balance\" style='width:70px;'></td>\n";
			$payment_table .= " <td id=\"{$payment_type}_payment_row_{$i}_interest\" style='width:70px; padding-left: 10px;'></td>\n";
			$payment_table .= "<input type='hidden' id='{$payment_type}_interest_range_begin_{$i}' name='{$payment_type}_interest_range_begin_{$i}'>\n";
			$payment_table .= "<input type='hidden' id='{$payment_type}_interest_range_end_{$i}' name='{$payment_type}_interest_range_end_{$i}'>\n";
			$payment_table .= "<input type='hidden' id='{$payment_type}_interest_amount_{$i}' name='{$payment_type}_interest_amount_{$i}'>\n";
			$payment_table .= " <td style='width:70px;'>" . $amount_col . "</td>\n";
			$payment_table .= " <td id=\"{$payment_type}_payment_row_{$i}_balance\" style='width:70px;'></td>\n";
			$payment_table .= $desc_field;
			$payment_table .= "</tr>\n";
		}

		$payment_table .= "<input id=\"{$payment_type}_discount_date\" name=\"{$payment_type}_discount_date\" size=\"10\" readonly value=\"\" type=\"hidden\">\n";
		$payment_table .= "<tr id=\"{$payment_type}_discount_row\" style=\"visibility: hidden; white-space:nowrap;\">\n";
		$payment_table .= "<td style='width:115px;'>Discount</td>\n";
		$payment_table .= "<td style=\"width:136px;\" id=\"{$payment_type}_discount_date_displayed\"></td>\n";
		$payment_table .= " <td id=\"{$payment_type}_discount_row_interest_balance\" style='width:70px;'></td>\n";
		$payment_table .= " <td id=\"{$payment_type}_discount_row_fee_balance\" style='width:70px;'></td>\n";
		$payment_table .= " <td id=\"{$payment_type}_discount_row_interest\" style='width:70px;'></td>\n";

		$payment_table .= " <td id=\"{$payment_type}_discount_displayed\" style='width:70px;'></td>\n";
		$payment_table .= "<input id=\"{$payment_type}_discount_amount\" name=\"{$payment_type}_discount_amount\" size=\"10\" readonly value=\"0.00\" type=\"hidden\">\n";

		$payment_table .= " <td id=\"{$payment_type}_discount_row_balance\" style='width:70px;'></td>\n";
		$payment_table .= " <td id=\"{$payment_type}_discount_row_desc\" style='width:115px;'>\n";
		$payment_table .= "<input id=\"{$payment_type}_discount_desc\" name=\"{$payment_type}_discount_desc\" type=\"text\" size=\"15\"></td>\n";
		$payment_table .= "</tr>\n";


        $payment_table .= " <tr id=\"{$payment_type}_summary_row\"></tr>\n";
        $payment_table .= " </tbody>\n";
		$payment_table .= " <tbody class=\"scrollContent\" style='height:46px; overflow:hidden;'>\n";
        $payment_table .= " <tr>\n";
        $payment_table .= "  <td style='width:115px;'></td>\n";
        $payment_table .= "  <td style='width:136px;'></td>\n";
        $payment_table .= "  <th class=\"%%%mode_class%%%\" style=\"width:65px; text-align:center; border-top:2px solid #AAAAAA; border-left:1px solid #AAAAAA; \" id='{$payment_type}_interest_paid_title'>Interest<br>Paid:<br/><span id=\"{$payment_type}_summary_interest\"></span></th>\n"; // interest in this payment
        $payment_table .= "  <th class=\"%%%mode_class%%%\" style=\"width:65px; text-align:center; border-top:2px solid #AAAAAA; \" id='{$payment_type}_fees_paid_title'>Fees<br>Paid:<br/><span id=\"{$payment_type}_summary_fee\"></span></th>\n"; // fee portion
        $payment_table .= "  <th class=\"%%%mode_class%%%\" style=\"width:65px; text-align:center; border-top:2px solid #AAAAAA; \">Principal<br>Paid:<br/><span id=\"{$payment_type}_summary_principal\"></span></th>\n"; // principal portion
        $payment_table .= "  <th class=\"%%%mode_class%%%\" style=\"width:65px; text-align:center; border-top:2px solid #AAAAAA; \">Total<br>Paid:<br/><span id=\"{$payment_type}_summary_payments_value\"></span></th>\n"; // payment amount
        $payment_table .= "  <th class=\"%%%mode_class%%%\" style=\"width:70px; text-align:center; border-top:2px solid #AAAAAA; border-right:1px solid #AAAAAA; \">Remaining<br>Balance:<br/><span id=\"{$payment_type}_summary_balance\"></span></th>\n"; // line balance
        $payment_table .= " </tr>\n";
//        $payment_table .= " </tfoot>\n";
        $payment_table .= " </tbody>\n";

		$payment_table .= "</table>\n";
        $data->payment_table = $payment_table;

		$template_html = file_get_contents(CLIENT_VIEW_DIR . "payment_template.html");
		$str = Display_Utility::Token_Replace($template_html, (array)$data);

		return $str;
	}
	
	private function get_payment_type_drop ($payment_type)
	{
		$active_options = Array();
		$all_drop_options = Array( 
			'ext_recovery' => 'Tier 2 Recovery',
			'western_union' => 'Western Union',
			'personal_check' => 'Personal Check',
			'payment_arranged' => 'ACH',
			'adjustment_internal' => 'Adjustment',
			'credit_card' => 'Credit Card',
			'moneygram' => 'Moneygram',
			'money_order' => 'Money Order'
		);
		
		switch($payment_type) 
		{
		case 'manual_payment':
			// The default options always availab.e
			array_push($active_options, 'credit_card');
			array_push($active_options, 'moneygram');
			array_push($active_options, 'money_order');
			
			array_push($active_options, 'western_union');

			array_push($active_options, 'personal_check');
			if($this->data->status == 'sent')
			{
				array_push($active_options, 'ext_recovery');
			}
			break;
		
		case 'payment_arrangement':	
		case 'ad_hoc':
			if ($this->ach_allowed()) array_push($active_options, 'payment_arranged');
			// The default options always availab.e
			array_push($active_options, 'credit_card');
			array_push($active_options, 'moneygram');
			array_push($active_options, 'money_order');
			array_push($active_options, 'western_union');
			break;
					
		case 'next_payment_adjustment':
		case 'partial_payment':
			//adjustment_internal should not be a valid payment type option for arrangements [W! AALM RC 4474]
			if ($this->ach_allowed()) array_push($active_options, 'payment_arranged');
			// The default options always availab.e duplicated so correct order of types [GF8851]
			array_push($active_options, 'credit_card');
			array_push($active_options, 'moneygram');
			array_push($active_options, 'money_order');
			break;

		// We want some options on all except some screens, so this catches those cases not handled
		default:
			// The default options always availab.e
			array_push($active_options, 'credit_card');
			array_push($active_options, 'moneygram');
			array_push($active_options, 'money_order');
			array_push($active_options, 'western_union');
		}
		$current_drop_options = Array();
		foreach ($active_options as $option) 
		{
			$current_drop_options[$option] = $all_drop_options[$option];
		}
		$select_text = '<select id="payment_type" name="payment_type">';
		foreach ($current_drop_options as $name => $value) 
		{
			$select_text .= "<option value='{$name}'>{$value}</option>\n";
		}
		$select_text .= '</select>';

		return $select_text;
	}
	
	private function ach_allowed() 
	{
		foreach ($this->data->app_flags as $key => $value) 
		{
			switch ($key) 
			{
				case 'has_fatal_ach_failure':
				case 'cust_no_ach':
					return false;
				default:
			}
		}
		return true;
	}
	private function get_num_payments_cell ($payment_type, $j) 
	{
		$cell = "";
		switch($payment_type)
		{
		case "ad_hoc":
			$num_payments_drop = "<input type=hidden id=\"{$payment_type}_max_num\" name=\"{$payment_type} _max_num\" value=\"$j\">";
			$num_payments_drop .= "<select id=\"{$payment_type}_num\" name=\"{$payment_type}_num\">";
			$num_payments_drop .= "<option value=\"{$j}\">{$j}</option>\n";
			$num_payments_drop .= "</select>";
			break;
		case "next_payment_adjustment":
		case "partial_payment":
			$num_payments_hidden = "<input type=hidden id=\"{$payment_type}_num\" name=\"{$payment_type}_num\" value=\"1\">";
			break;
		default:
			$num_payments_drop = "<input type=hidden id=\"{$payment_type}_max_num\" name=\"{$payment_type}_max_num\" value=\"$j\">";
			$num_payments_drop .= "<select id=\"{$payment_type}_num\" name=\"{$payment_type}_num\">";
			for ($i = 0; $i < $j; $i++)
			{
				$v = $i+1;
				$num_payments_drop .= "<option value=\"{$v}\">{$v}</option>\n";
			}
			$num_payments_drop .= "</select>";
		}
		if (!empty($num_payments_hidden)) 
		{
			$cell .= $num_payments_hidden;
		}
		if (!empty($num_payments_drop)) 
		{
			$cell .= "
						<td class=\"align_right\">Number of Payments: </td>
						<td class=\"align_left\">{$num_payments_drop}</td>
			";
		}
		return $cell;
	}
	
	private function get_include_pending_cell ($payment_type) 
	{
		switch($payment_type)
		{
		case "next_payment_adjustment":
		case "partial_payment":
			$include_pending_cell = '<input onChange="UpdateAmountLeftToArrange(\''.$payment_type.'\')" type="checkbox" value="y" id="'.$payment_type.'_arr_incl_pend" name="'.$payment_type.'_arr_incl_pend" style="display:none;" checked="checked">';
			break;
		default:
			$include_pending_cell = '<td class="align_right">Include Pending Items: </td>
					<td align="left"><input onChange="UpdateAmountLeftToArrange(\''.$payment_type.'\')" type="checkbox" value="y" id="'.$payment_type.'_arr_incl_pend" name="'.$payment_type.'_arr_incl_pend" checked ="checked"></td>';
		}
		return $include_pending_cell;
	}

	private function get_discount_percentage_cell ($payment_type, $has_discount, $has_settlement = FALSE) {
		$discount_cell = '';
		if (($has_discount && intval($this->data->business_rules['arrangements_met_discount']) > 0))
		{
			$discount_cell = "<td class=\"align_right\">Percent Discount Offer:</td>\n";
			$discount_cell .= "<td class=\"align_left\">\n";
			$discount_cell .= "<select id=\"{$payment_type}_percent_discount\" name=\"\" onChange=\"AdjustArrangedAmounts('{$payment_type}')\">\n";
			$discount_max = intval($this->data->business_rules['arrangements_met_discount']) + 1;
			for ($i = 0; $i < $discount_max; $i += 5)
			{
				$discount_cell .= "<option value=\"{$i}\">{$i}</option>\n";
			}
			$discount_cell .= "</select>\n %</td>";
		} 
		else if ($has_settlement)
		{
			$discount_cell = "<td class=\"align_right\">Percent Settlement Offer:</td>\n";
			$discount_cell .= "<td class=\"align_left\">\n";
			$discount_cell .= "<select id=\"{$payment_type}_percent_discount\" name=\"\" onChange=\"AdjustArrangedAmounts('{$payment_type}')\">\n";
			for ($i = 0; $i <= 100; $i += 5)
			{
				$discount_cell .= "<option value=\"{$i}\">{$i}</option>\n";
			}
			$discount_cell .= "</select>\n %</td>";

		}

		return $discount_cell;
	}
	
	public function Build_Button_Sets(&$layouts)
	{
		// Re-Act button
		if
		(
			(
				in_array($this->data->status, array('paid')) ||
				in_array($this->data->status, array('recovered')) ||
		     	(in_array($this->data->status, array('withdrawn')) &&  ($this->data->is_react == 'yes'))
		     )
		     && (preg_match('/customer_service/', $this->mode) == 1)
		     && ($this->data->can_react)
		)
		{
			switch ($this->mode) 
			{
			case 'underwriting': 
				$button_html_source = "react_buttons.html";
				break;
			
			case 'customer_service': 
				$button_html_source = "{$this->mode}_react_buttons.html";
				break;
			}
			$this->Build_React_URL();
			$this->Build_React_URL2();
		}
		elseif (in_array($this->data->status, array('active'))
				&& ($this->data->scheduled_payments == 0)
				&& ($this->data->schedule_status->posted_and_pending_total <= 0)
		    	&& (preg_match('/underwriting|customer_service/', $this->mode) == 1)
				&& eCash_Config::getInstance()->USE_SOAP_PREACT !== FALSE)
		{
			switch ($this->mode) 
			{
			case 'underwriting': 
				$button_html_source = "preact_buttons.html";
				break;
			case 'customer_service': 
				$button_html_source = "{$this->mode}_preact_buttons.html";
				break;
			}
			$this->Build_React_URL();
			$this->Build_React_URL2();
		}
		/*elseif(in_array('reprocess', $this->display_layers))
		{
			$button_html_source = 'reprocessing_buttons.html';
			}*/
		else
		{
			// If we are a EcashApp react we need to be able to
			// Send a Esig Link
			//react_mail or react_send_mail or something
			if(	$this->mode == 'customer_service' &&
				$this->data->is_react == 'yes' &&
				$this->data->status == 'pending')
			{
				$button_html_source = "customer_service_react_send_buttons.html";
			}
			else
			{
				$button_html_source = "{$this->mode}_buttons.html";
			}
		}

		//Back button
		if (isset($_REQUEST['show_back_button']))
		{
			if (isset($_REQUEST['show_email_archive_id'])) //mantis:7025
			{
				$this->data->back_button_link = "<a href=\"?mode={$this->mode}&action=show_email&archive_id="
				                              . "{$_REQUEST['show_email_archive_id']}\">&lt;&lt; Back</a>";
			}
			else
			{
				//$this->data->back_button_link = "<a href=\"javascript:back();\">&lt;&lt; Back</a>";
				$this->data->back_button_link = "<a href=\"?module=reporting&mode=\">&lt;&lt; Back</a>"; //mantis:5781
			}
		}
		else
		{
			$this->data->back_button_link = "";
		}


		// Set the name of the controlling agent if there is one
		if (!empty($this->data->assoc_agent))
		{
			$agent = $this->data->assoc_agent;
			$this->data->controlling_agent_name = ucfirst($agent->name_last).', '.ucfirst($agent->name_first);
		}
		else 
		{
			$this->data->controlling_agent_name = 'NONE';
		}
		
		// If there is a file that exists in the customer lib, use it first.
		if (file_exists(CUSTOMER_LIB . "{$this->module_name}/view/{$button_html_source}")) 
		{
			$buttons = file_get_contents(CUSTOMER_LIB . "{$this->module_name}/view/{$button_html_source}");
		}
		else if (file_exists(CLIENT_MODULE_DIR . "{$this->module_name}/view/{$button_html_source}")) 
		{
			$buttons = file_get_contents(CLIENT_MODULE_DIR . "{$this->module_name}/view/{$button_html_source}");
		} 
		else 
		{
			$buttons = '';
		}

		foreach ($layouts as $layout)
		{
			$buttons .= $layout->Get_Button_Content();
		}

		return Display_Utility::Token_Replace($buttons, (array)$this->data);
	}
	//EcashApp
	protected function Build_React_URL()
	{

		$app_row = $this->data->model;
		
		$this->data->new_app_url = eCash_Config::getInstance()->ECASH_APP;

		$ssn_wk = trim(str_replace('-', '', $this->data->ssn));
		$ssn1 = substr($ssn_wk, 0, 3);
		$ssn2 = substr($ssn_wk, 3, 2);
		$ssn3 = substr($ssn_wk, 5, 4);

		$getstr  = "?ecashapp="						. urlencode($this->data->company) . "&force_new_session&no_checks";
		$getstr .= "&react_app_id="					. urlencode($this->data->application_id);
		$getstr .= "&agent_id="					. urlencode($this->data->agent_id);

		$getstr .= "&promo_id="						. urlencode(eCash_Config::getInstance()->ECASH_APP_REACT_PROMOID);
		$getstr .= "&ecashdn="						. $_SERVER["SERVER_NAME"];
		$getstr .= "&name_first="					. urlencode($this->data->name_first);
		$getstr .= "&name_last="					. urlencode($this->data->name_last);
		$getstr .= "&name_middle="					. urlencode($this->data->name_middle);
		$getstr .= "&ssn_part_1="					. urlencode($ssn1);
		$getstr .= "&ssn_part_2="					. urlencode($ssn2);
		$getstr .= "&ssn_part_3="					. urlencode($ssn3);

		// If not in LIVE mode, send react e-mail to gmail test account
		if (EXECUTION_MODE == 'LIVE') 
		{
			$getstr .= "&email_primary="				. urlencode($this->data->customer_email);
		}
		else 
		{
			$getstr .= "&email_primary="				. urlencode('ecash3drive@gmail.com');
		}
		$getstr .= "&date_dob_m="					. urlencode($this->data->dob_month);
		$getstr .= "&date_dob_d="					. urlencode($this->data->dob_day);
		$getstr .= "&date_dob_y="					. urlencode($this->data->dob_year);
		$getstr .= "&home_street="					. urlencode(ucwords($this->data->street));
		$getstr .= "&home_unit="					. urlencode(strtoupper($this->data->unit));
		$getstr .= "&home_city="					. urlencode(ucwords($this->data->city));
		$getstr .= "&home_state="					. urlencode(strtoupper($this->data->state));
		$getstr .= "&home_county="					. urlencode(strtoupper($this->data->county));
		$getstr .= "&home_zip="						. urlencode($this->data->zip);
		$getstr .= "&phone_home="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_home));
		$getstr .= "&phone_cell="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_cell));
		$getstr .= "&phone_work="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_work));
		$getstr .= "&best_call_time="				. (($this->data->call_time_pref == 'no preference') ? '' : urlencode(strtoupper($this->data->call_time_pref)));
		$getstr .= "&employer_name="				. urlencode($this->data->employer_name);
		$getstr .= "&income_monthly_net="			. urlencode($this->data->income_monthly);
		$getstr .= "&income_type="					. urlencode(strtoupper($this->data->income_source));
		$getstr .= "&income_direct_deposit="		. (($app_row->model['direct_deposit'] == 'yes') ? 'TRUE' : 'FALSE');
		$getstr .= "&state_id_number="				. urlencode(strtoupper($this->data->legal_id_number));
		$getstr .= "&state_id_state="				. urlencode(strtoupper($this->data->legal_id_state));
		$getstr .= "&bank_name="					. urlencode(ucwords($this->data->bank_name));
		$getstr .= "&bank_aba="						. urlencode($this->data->bank_aba);
		$getstr .= "&bank_account="					. urlencode($this->data->bank_account);
		$getstr .= "&bank_account_type="			. urlencode(strtoupper($this->data->bank_account_type));
		$getstr .= "&paydate[frequency]="			. urlencode(strtoupper($app_row->model['frequency_name']));
		$getstr .= "&ref_01_name_full="				. urlencode(ucwords($this->data->ref_name_1));
		$getstr .= "&ref_01_phone_home="			. urlencode($this->OLP_Phone_Fmt($this->data->ref_phone_1));
		$getstr .= "&ref_01_relationship="			. urlencode(ucwords($this->data->ref_relationship_1));
		$getstr .= "&ref_02_name_full="				. urlencode(ucwords($this->data->ref_name_2));
		$getstr .= "&ref_02_phone_home="			. urlencode($this->OLP_Phone_Fmt($this->data->ref_phone_2));
		$getstr .= "&ref_02_relationship="			. urlencode(ucwords($this->data->ref_relationship_2));
		$getstr .= "&legal_notice_1="				. 'TRUE';

		if (strtoupper($this->data->state) == 'CA')
		{
			$getstr .= "&cali_agree=agree";
		}

		/*----------------------------------------------
			"dw"	= Weekly on day
			"dwpd"	= Every other week on day
			"dmdm"	= Twice per month on dates
			"wwdw"	= Twice per month on week and day
			"dm"	= Monthly on date
			"wdw"	= Monthly on week and day
			"dwdm"	= Monthly on day of week after day
		-----------------------------------------------*/
		switch ($app_row->model['model_name'])
		{
			case 'dw':
				$getstr .= "&paydate[weekly_day]="			. urlencode(strtoupper($app_row->model['day_string_one']));
				break;
			case 'dwpd':
				$getstr .= "&paydate[twicemonthly_type]=biweekly";
				$getstr .= "&paydate[biweekly_day]="		. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[biweekly_date]="		. date('m/d/Y', strtotime('-2 weeks', strtotime($app_row->model['paydate']['biweekly_date'])));
				break;
			case 'dmdm':
				$getstr .= "&paydate[twicemonthly_type]=date";
				$getstr .= "&paydate[twicemonthly_date1]="	. urlencode($app_row->model['day_int_one']);
				$getstr .= "&paydate[twicemonthly_date2]="	. urlencode($app_row->model['day_int_two']);
				break;
			case 'wwdw':
				$getstr .= "&paydate[twicemonthly_type]=week";
				$getstr .= "&paydate[twicemonthly_day]="	. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[twicemonthly_week]="	. urlencode($app_row->model['week_one'] . '-' . $app_row->model['week_two']);
				break;
			case 'dm':
				$getstr .= "&paydate[monthly_type]=date";
				$getstr .= "&paydate[monthly_date]="		. urlencode($app_row->model['day_int_one']);
				break;
			case 'wdw':
				$getstr .= "&paydate[monthly_type]=day";
				$getstr .= "&paydate[monthly_day]="			. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[monthly_week]="		. urlencode($app_row->model['week_one']);
				break;
			case 'dwdm':
				$getstr .= "&paydate[monthly_type]=after";
				$getstr .= "&paydate[monthly_after_day]="	. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[monthly_after_date]="	. urlencode($app_row->model['day_int_one']);
				break;
		}

		$this->data->new_app_get_str = $getstr;

		return true;
	}
	//Soap
	protected function Build_React_URL2()
	{
		$app_row = $this->data->model;
		
		$this->data->new_app_url_soap = "/soap_react.php?process=start";
		$ssn_wk = trim(str_replace('-', '', $this->data->ssn));
		$ssn1 = substr($ssn_wk, 0, 3);
		$ssn2 = substr($ssn_wk, 3, 2);
		$ssn3 = substr($ssn_wk, 5, 4);
		
		$getstr  = "&ecashapp="						. urlencode($this->data->company) . "&force_new_session&no_checks";
		$getstr .= "&loan_type="					. urlencode($this->data->loan_type);
		$getstr .= "&react_app_id="					. urlencode($this->data->application_id);
		$getstr .= "&company_id="					. urlencode($this->data->company_id);
		$getstr .= "&track_agent_action="			. "true"; // Track Agent Action
		$getstr .= "&agent_id="					. urlencode($this->data->agent_id);

		$getstr .= "&promo_id="						. urlencode(eCash_Config::getInstance()->ECASH_APP_REACT_PROMOID);
		$getstr .= "&ecashdn="						. $_SERVER["SERVER_NAME"];
		$getstr .= "&name_first="					. urlencode($this->data->name_first);
		$getstr .= "&name_last="					. urlencode($this->data->name_last);
		$getstr .= "&name_middle="					. urlencode($this->data->name_middle);
		$getstr .= "&ssn_part_1="					. urlencode($ssn1);
		$getstr .= "&ssn_part_2="					. urlencode($ssn2);
		$getstr .= "&ssn_part_3="					. urlencode($ssn3);

		// If not in LIVE mode, send react e-mail to gmail test account
		if (EXECUTION_MODE == 'LIVE') 
		{
			$getstr .= "&email_primary="				. urlencode($this->data->customer_email);
		}
		else 
		{
			$getstr .= "&email_primary="				. urlencode('ecash3drive@gmail.com');
		}		
		$getstr .= "&vehicle_color="				. urlencode($this->data->vehicle_color);
		$getstr .= "&vehicle_value="				. urlencode($this->data->vehicle_value);
		$getstr .= "&vehicle_license_plate="		. urlencode($this->data->vehicle_license_plate);
		$getstr .= "&vehicle_title_state=" 			. urlencode($this->data->vehicle_title_state);
		$getstr .= "&vehicle_vin="					. urlencode($this->data->vehicle_vin);
		$getstr .= "&vehicle_year="					. urlencode($this->data->vehicle_year);
		$getstr .= "&vehicle_make="					. urlencode(trim($this->data->vehicle_make));
		$getstr .= "&vehicle_series="				. urlencode(trim($this->data->vehicle_series));
		$getstr .= "&vehicle_model="				. urlencode(trim($this->data->vehicle_model));
		$getstr .= "&vehicle_body="					. urlencode(trim($this->data->vehicle_style));
		$getstr .= "&vehicle_mileage="				. urlencode($this->data->vehicle_mileage);
		$getstr .= "&date_of_hire="					. urlencode($this->data->date_hire);
		$getstr .= "&job_title="					. urlencode($this->data->job_title_trim);
		$getstr .= "&residence_start_date="			. urlencode($this->data->residence_start_date);
		$getstr .= "&banking_start_date="			. urlencode($this->data->banking_start_date);
		$getstr .= "&date_dob_m="					. urlencode($this->data->dob_month);
		$getstr .= "&date_dob_d="					. urlencode($this->data->dob_day);
		$getstr .= "&date_dob_y="					. urlencode($this->data->dob_year);
		$getstr .= "&home_street="					. urlencode(ucwords($this->data->street));
		$getstr .= "&home_unit="					. urlencode(strtoupper($this->data->unit));
		$getstr .= "&home_city="					. urlencode(ucwords($this->data->city));
		$getstr .= "&home_state="					. urlencode(strtoupper($this->data->state));
		$getstr .= "&home_county="					. urlencode(strtoupper($this->data->county));
		$getstr .= "&home_zip="						. urlencode($this->data->zip);
		$getstr .= "&phone_home="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_home));
		$getstr .= "&phone_cell="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_cell));
		$getstr .= "&phone_work="					. urlencode($this->OLP_Phone_Fmt($this->data->phone_work));
		$getstr .= "&phone_work_ext="				. urlencode($this->OLP_Phone_Fmt($this->data->phone_work_ext));
		$getstr .= "&best_call_time="				. (($this->data->call_time_pref == 'no preference') ? '' : urlencode(strtoupper($this->data->call_time_pref)));
		$getstr .= "&employer_name="				. urlencode($this->data->employer_name);
		$getstr .= "&income_monthly_net="			. urlencode($this->data->income_monthly);
		$getstr .= "&income_type="					. urlencode(strtoupper($this->data->income_source));
		$getstr .= "&income_direct_deposit="		. (($app_row->model['direct_deposit'] == 'yes') ? 'TRUE' : 'FALSE');
		$getstr .= "&state_id_number="				. urlencode(strtoupper($this->data->legal_id_number));
		$getstr .= "&state_issued_id="				. urlencode(strtoupper($this->data->legal_id_state));
		$getstr .= "&bank_name="					. urlencode(ucwords($this->data->bank_name));
		$getstr .= "&bank_aba="						. urlencode($this->data->bank_aba);
		$getstr .= "&bank_account="					. urlencode($this->data->bank_account);
		$getstr .= "&bank_account_type="			. urlencode(strtoupper($this->data->bank_account_type));
		foreach($app_row->model as $key => $value)
		{
			$value = is_string($value) ? urlencode($value) : "";
			$getstr .= "&paydate_model[$key]="		. $value;
		}
		$getstr .= "&paydate[frequency]="			. urlencode(strtoupper($app_row->model['frequency_name']));
		$getstr .= "&ref_01_name_full="				. urlencode(ucwords($this->data->ref_name_1));
		$getstr .= "&ref_01_phone_home="			. urlencode($this->OLP_Phone_Fmt($this->data->ref_phone_1));
		$getstr .= "&ref_01_relationship="			. urlencode(ucwords($this->data->ref_relationship_1));
		$getstr .= "&ref_02_name_full="				. urlencode(ucwords($this->data->ref_name_2));
		$getstr .= "&ref_02_phone_home="			. urlencode($this->OLP_Phone_Fmt($this->data->ref_phone_2));
		$getstr .= "&ref_02_relationship="			. urlencode(ucwords($this->data->ref_relationship_2));
		$getstr .= "&legal_notice_1="				. 'TRUE';
		$getstr .= "&promo_sub_code="				. '';

		if (strtoupper($this->data->state) == 'CA')
		{
			$getstr .= "&cali_agree=agree";
		}

		/*----------------------------------------------
			"dw"	= Weekly on day
			"dwpd"	= Every other week on day
			"dmdm"	= Twice per month on dates
			"wwdw"	= Twice per month on week and day
			"dm"	= Monthly on date
			"wdw"	= Monthly on week and day
			"dwdm"	= Monthly on day of week after day
		-----------------------------------------------*/
		switch ($app_row->model['model_name'])
		{
			case 'dw':
				$getstr .= "&paydate[weekly_day]="			. urlencode(strtoupper($app_row->model['day_string_one']));
				break;
			case 'dwpd':
				$getstr .= "&paydate[twicemonthly_type]=biweekly";
				$getstr .= "&paydate[biweekly_day]="		. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[biweekly_date]="		. date('m/d/Y', strtotime('-2 weeks', strtotime($app_row->model['paydate']['biweekly_date'])));
				break;
			case 'dmdm':
				$getstr .= "&paydate[twicemonthly_type]=date";
				$getstr .= "&paydate[twicemonthly_date1]="	. urlencode($app_row->model['day_int_one']);
				$getstr .= "&paydate[twicemonthly_date2]="	. urlencode($app_row->model['day_int_two']);
				break;
			case 'wwdw':
				$getstr .= "&paydate[twicemonthly_type]=week";
				$getstr .= "&paydate[twicemonthly_day]="	. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[twicemonthly_week]="	. urlencode($app_row->model['week_one'] . '-' . $app_row->model['week_two']);
				break;
			case 'dm':
				$getstr .= "&paydate[monthly_type]=date";
				$getstr .= "&paydate[monthly_date]="		. urlencode($app_row->model['day_int_one']);
				break;
			case 'wdw':
				$getstr .= "&paydate[monthly_type]=day";
				$getstr .= "&paydate[monthly_day]="			. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[monthly_week]="		. urlencode($app_row->model['week_one']);
				break;
			case 'dwdm':
				$getstr .= "&paydate[monthly_type]=after";
				$getstr .= "&paydate[monthly_after_day]="	. urlencode(strtoupper($app_row->model['day_string_one']));
				$getstr .= "&paydate[monthly_after_date]="	. urlencode($app_row->model['day_int_one']);
				break;
		}

		$this->data->new_app_get_str_soap = $getstr;

		return true;
	}

}



?>
