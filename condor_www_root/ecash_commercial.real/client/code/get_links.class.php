<?php

class Get_Links
{
	protected $data;
	protected $acl;
	protected $mode;
	protected $module_name;

	public function __construct(stdClass &$data, ECash_ACL &$acl, $mode, $module_name)
	{
		$this->acl = &$acl;
		$this->data = &$data;
		$this->mode = $mode;
		$this->module_name = $module_name;
	}

	public function Set_Personal_References_Phone_Links()
	{
		$this->Generate_Phone_Link($this->data->application_id, $this->data->ref_phone_1, 'ref_phone_1_link', false, 'Personal Reference');
		$this->Generate_Phone_Link($this->data->application_id, $this->data->ref_phone_2, 'ref_phone_2_link', false, 'Personal Reference');
	}

	public function Set_General_Info_Phone_Links()
	{
		$this->Generate_Phone_Link($this->data->application_id, $this->data->phone_home, 'phone_home_link', false, 'Home Phone');
		$this->Generate_Phone_Link($this->data->application_id, $this->data->phone_work, 'phone_work_link', false, 'Work Phone');
		$this->Generate_Phone_Link($this->data->application_id, $this->data->phone_cell, 'phone_cell_link', false, 'Cell Phone');
	}

	public function Generate_Phone_Link($application_id, $phone_number, $data_item_name, $is_contact_id = FALSE, $category = NULL)
	{
		// Note: This functionality, by a different method, is duplicated in ApplicationContactInterface::getDialLink()
		$this->data->{$data_item_name} = '';
		if(isset($this->data->pbx_enabled) && $this->data->pbx_enabled == 'true' && !empty($phone_number) && !in_array("disable_phone_link",$this->data->read_only_fields))
		{
			if ($is_contact_id === true) 
			{
				$ph_url = "&contact_id={$phone_number}";
				$cat_url = "";

			} 
			else 
			{
				$ph_url = "&dial_number={$phone_number}";
				$cat_url = ($category) ? "&add_contact=true&type=phone&category={$category}" : "" ;
			}

			$this->data->{$data_item_name} = ' [<a href="#" onclick="javascript:window.open(\'/?action=pbx_dial&amp;application_id=' . $application_id . $ph_url . $cat_url . '\', \'PBX Dial\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=300,height=100,left=150,top=150,screenX=150,screenY=150\')">Dial</a>]';
			return $this->data->{$data_item_name};
		}
		return '';
	}
	public function Get_Edit_Vehicle_Data_Link($current_section_id)
	{
		if (! $this->Is_This_Read_Only($current_section_id, 'vehicle_data'))
		{
			return "<input type=button class=\"button\" value=\"Edit Vehicle Data\" onClick=\"javascript:SetDisplay(0,0,9,'edit', '{$this->mode}_buttons');\">";
		}

		return '&nbsp;';
	}

	public function Get_Loan_Actions_Link($section_id)
	{
		$result = '';

		// does personal link appear
		foreach($this->data->all_sections as $key => $value)
		{
			if ($section_id == $value->section_parent_id
					&& $value->name == 'loan_actions')
			{
				$result = "<div id=\"AppSubMenuLoanActions\" class=\"level3_nav\" onClick=\"SetDisplay(0,0,7,'view', '{$this->mode}_buttons');\">Loan Actions</div>";
				break;
			}
		}

		return $result;
	}

	public function Get_Documents_Link($doc_id)
	{
		if ($doc_id <= -1)
		{
			return '';
		}

		$div_attributes = 'class="submenu_layer ' . $this->mode . '" id="documents_float_menu"';

		// Warning - if you modify this static links array, you'll also need to modify the array in Documents_Item_Add_If_Permitted accordingly.
		$links = array(
				'Main' =>
				'<div id="AppSubMenuDocs" class="level3_nav" onClick="Toggle_Menu(\'documents_float_menu\', this.id); return false;">Documents</div>',
				'Send Documents' =>
				'<a id="AppSubMenuSendDocs" href="#" onClick="SetDisplay(0,1,1,\'edit\', \'' . $this->mode . '_buttons\'); return false;"><div>Send Documents</div></a>',
				'Receive Documents' =>
				'<a id="AppSubMenuReceiveDocs" href="#" onClick="SetDisplay(0,1,2,\'edit\', \'' . $this->mode . '_buttons\'); return false;"><div>Receive Documents</div></a>',
				'ESig Documents' =>
				'<a id="AppSubMenuESigDocs" href="#" onClick="SetDisplay(0,1,3,\'edit\', \'' . $this->mode . '_buttons\'); return false;"><div>ESig Documents</div></a>',
				'Packaged Docs' =>
				'<a id="AppSubMenuPackagedDocs" href="#" onClick="SetDisplay(0,1,4,\'edit\', \'' . $this->mode . '_buttons\'); return false;"><div>Packaged Docs</div></a>');

		$link_items = array();

		$this->Documents_Item_Add_If_Permitted($link_items, 'Send Documents');
		$this->Documents_Item_Add_If_Permitted($link_items, 'Receive Documents');
		$this->Documents_Item_Add_If_Permitted($link_items, 'ESig Documents');
		$this->Documents_Item_Add_If_Permitted($link_items, 'Packaged Docs');

		if (!empty($link_items)) return $this->getMenuLinks($links, $link_items, $div_attributes);
		return null;
	}

	/**
	 * Helper function for Get_Documents_Link - Adds to a list if permitted by ACL
	 */
	protected function Documents_Item_Add_If_Permitted(&$array, $item) 
	{
		// We cache this the first time we see it in this module, to avoid excessive calls.
		// -- if mode and  module wasn't checked, the permissions from the different modes and/or modules could be there!! -- //
		if (empty($this->items_permitted) || (isset($this->items_permitted) && !is_array($this->items_permitted)) || 
				(isset($this->items_permitted_cached_module) && ($this->data->current_module . $this->data->current_mode) != $this->items_permitted_cached_module )) 
		{
			// Warning - The entries in ths items_permitted array should directly correspond to the links array in Get_Documents_Link
			$this->items_permitted = Array(
					'Main' => $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'documents', 'documents')),
					'Send Documents' => $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'documents', 'send_documents')),
					'Receive Documents' => $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'documents', 'receive_documents')),
					'ESig Documents' => $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'documents', 'esig_documents')),
					'Packaged Docs' => $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'documents', 'packaged_docs')),
					);
			$this->items_permitted_cached_module = $this->data->current_module . $this->data->current_mode;
		}
		if (!empty($this->items_permitted[$item])) $array[] = $item;
	}

	public function Get_Edit_Employment_N_Origin_Link($current_section_id)
	{
		$result = '<a href="#" onClick="javascript:SetDisplay(0,0,3,\'edit\', \'' . $this->mode . '_buttons\');">Edit Employment &nbsp;</a>';

		if ($this->Is_This_Read_Only($current_section_id, 'employment_n_origin'))
		{
			$result = '&nbsp;';
		}

		return $result;
	}

	public function Get_Employment_N_Origin_Link($section_id)
	{
		$result = '';

		// does personal link appear
		foreach($this->data->all_sections as $key => $value)
		{
			if ($section_id == $value->section_parent_id
					&& $value->name == 'employment_n_origin')
			{
				$result = '<div id="AppSubMenuEmpOrig" class="level3_nav" onClick="SetDisplay(0,0,3,\'view\', \''
					. $this->mode
					. '_buttons\');">Employment & Origin</div>';
				break;
			}
		}

		return $result;
	}

	public function Get_Edit_Application_Info_Link($current_section_id)
	{
		// Active apps are automatically marked read only
		if(($this->module_name == 'funding' && $this->data->status == 'active') || $this->data->status == 'approved') return '&nbsp;';

		if (! $this->Is_This_Read_Only($current_section_id, 'application_info'))
		{
			return '<a href="#" onClick="javascript:SetDisplay(0,0,2,\'edit\', \'' . $this->mode . '_buttons\');">Edit App Info</a>&nbsp;';
		}

		return '&nbsp;';
	}

	protected function contains_only_application_history($app_id)
	{
		$application_info_is_found = FALSE;
		$application_history_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $app_id) continue;
			if ($value->name === 'application_info') $application_info_is_found = TRUE;
			if ($value->name === 'application_history') $application_history_is_found = TRUE;
		}

		if (!$application_info_is_found && $application_history_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

	protected function contains_only_application_info($app_id)
	{
		$application_info_is_found = FALSE;
		$application_history_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $app_id) continue;
			if ($value->name === 'application_info') $application_info_is_found = TRUE;
			if ($value->name === 'application_history') $application_history_is_found = TRUE;
		}

		if ($application_info_is_found && !$application_history_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

	protected function contains_full_application($app_id)
	{
		$application_info_is_found = FALSE;
		$application_history_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $app_id) continue;
			if ($value->name === 'application_info') $application_info_is_found = TRUE;
			if ($value->name === 'application_history') $application_history_is_found = TRUE;
		}

		if ($application_info_is_found && $application_history_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

	public function Get_Application_Link($app_id)
	{
		if ($app_id <= -1)
		{
			return '&nbsp;';
		}

		$div_attributes = 'class="submenu_layer ' . $this->mode . '" id="application_float_menu"';

		$links = array(
				'Main' =>
				'<div id="AppSubMenuAppMain" class="level3_nav" onClick="Toggle_Menu(\'application_float_menu\', this.id); return false;">Application</div>',
				'Application History' =>
				'<a id="AppSubMenuAppHistory" href="#" onClick="SetDisplay(1,1,2,\'view\', \'application_summary_buttons\'); return false;"><div>Application History</div></a>',
				'Application Info' =>
				'<a id="AppSubMenuAppInfo" href="#" onClick="SetDisplay(0,0,2, \'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Application Info</div></a>',
				'Vehicle Data' =>
				'<a id="AppSubMenuVehicleData" href="#" onClick="SetDisplay(0,0,9, \'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Vehicle Data</div></a>',
				'Application Audit' =>
				'<a id="AppSubMenuAppAudit" href="#" onClick="javascript:window.open(\'/?action=get_application_audit_log&amp;application_id='.$this->data->application_id.'\', \'application_audit_log\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=515,height=420,left=150,top=150,screenX=150,screenY=150\'); return false;"><div>Application Audit</div></a>',
				'Payment Arrangement History' =>
				'<a id="AppSubMenuPayArrangHistory" href="#" onClick="javascript:window.open(\'/?action=get_payment_arrangement_history&amp;application_id='.$this->data->application_id.'\', \'payment_arrangement_history\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=815,height=420,left=150,top=150,screenX=150,screenY=150\'); return false;"><div>Payment Arrangement History</div></a>',
				'Do Not Loan Audit' =>
				'<a id="AppSubMenuDNLAudit" href="#" onClick="javascript:window.open(\'/?action=get_dnl_audit_log&amp;application_id='.$this->data->application_id.'\', \'dnl_audit_log\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=615,height=420,left=150,top=150,screenX=150,screenY=150\'); return false;"><div>Do Not Loan Audit</div></a>',
				'Application Flags' =>
				'<a id="AppSubMenuAppFlags" href="#" onClick="SetDisplay(0,0,10,\'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Application Flags</div></a>',
				'Application Flags History' =>
				'<a id="AppSubMenuFlagsHistory" href="#" onClick="javascript:window.open(\'/?action=get_application_flag_history&amp;application_id='.$this->data->application_id.'\', \'application_flag_history\', \'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=515,height=420,left=150,top=150,screenX=150,screenY=150\'); return false;"><div>Application Flags History</div></a>',
				);



		$link_items = array();

		if ($this->contains_full_application($app_id))
		{
			if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_info')))
			{
				$link_items[] = 'Application Info';
			}
			if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_history')))
			{
				$link_items[] = 'Application History';
			}
		}
		else if ($this->contains_only_application_info($app_id))
		{
			if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_info')))
			{	
				$link_items[] = 'Application Info';
			}
		}
		else if ($this->contains_only_application_history($app_id))
		{
			if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_history')))
			{
				$link_items[] = 'Application History';
			}
		}

		// Only display Vehicle Data if we're a Title Loan and have permission
		if(isset($this->data->loan_type_model) 
				&& $this->data->loan_type_model === 'Title' 
				&& $this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'vehicle_data')))
		{
			$link_items[] = 'Vehicle Data';
		}

		if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_flag')))
		{
			$link_items[] = 'Application Flags';
		}

		// Needs section and ACL			
		$link_items[] = 'Application Audit';

		if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'application_flag_history')))
		{
			$link_items[] = 'Application Flags History';
		}
		if($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'application', 'payment_arrangement_history')))
		{
			$link_items[] = 'Payment Arrangement History';
		}

		// No Sections for these
		$link_items[] = 'Do Not Loan Audit';

		return $this->getMenuLinks($links, $link_items, $div_attributes);
	}

	public function Get_Edit_Personal_Details_Link($personal_id)
	{
		if (! $this->Is_This_Read_Only($personal_id, 'personal_details'))
		{
			return '<a href="#" onClick="javascript:SetDisplay(0,0,0,\'edit\', \'' . $this->mode . '_buttons\');">Edit Details</a> &nbsp;';
		}

		return '&nbsp;';
	}

	public function Get_Edit_Personal_References_Link($personal_id)
	{
		$result = '<a href="#" onClick="javascript:SetDisplay(0,0,6,\'edit\', \'' . $this->mode . '_buttons\');">Add/Edit References</a> &nbsp;';

		if ($this->Is_This_Read_Only($personal_id, 'personal_references'))
		{
			$result = '&nbsp;';
		}

		return $result;
	}

	public function Get_ID_And_Credit_Link($section_id)
	{
		$result = '';

		// does personal link appear
		foreach($this->data->all_sections as $key => $value)
		{
			if ($section_id == $value->section_parent_id
					&& $value->name == 'id_and_credit')
			{
				$result = '<div id="AppSubMenuIdCredit" class="level3_nav" onClick="SetDisplay(0,0,1,\'view\', \''
					. $this->mode
					. '_buttons\');">ID & Credit</div>';
				break;
			}
		}

		return $result;
	}

	public function Get_Edit_ID_And_Credit_Link($id_and_credit_id)
	{
		$result = '<a href="/get_idv_record.php?cid=' . $this->data->company_id . '&app_id=' . $this->data->application_id
			. '" target="idvinfo">' . $this->data->idv_full_record . '</a>&nbsp;';

		if ($this->Is_This_Read_Only($id_and_credit_id, 'id_and_credit'))
		{
			$result = '&nbsp;';
		}

		return $result;
	}

	/**
	 * Get the Personal Link and the drop down menu.
	 */
	public function Get_Personal_Link($personal_id)
	{
		if ($personal_id <= -1)
		{
			return '&nbsp;';
		}

		$div_attributes = 'class="submenu_layer ' . $this->mode . '" id="personal_float_menu"';

		$links = array(
				'Main' =>
				'<div id="AppSubMenuPersonal" class="level3_nav" onClick="Toggle_Menu(\'personal_float_menu\', this.id); return false;">Personal</div>',
				'Personal References' =>
				'<a id="AppSubMenuPersonalReferences" href="#" onClick="SetDisplay(0,0,6,\'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Personal References</div></a>',
				'Personal Details' =>
				'<a id="AppSubMenuPersonalDetails" href="#" onClick="SetDisplay(0,0,0,\'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Personal Details</div></a>',
				'Contact Information' =>
				'<a id="AppSubMenuPersonalContact" href="#" onClick="SetDisplay(0,0,8,\'view\', \'' . $this->mode . '_buttons\'); return false;"><div>Contact Information</div></a>');

		$link_items = array();

		if ($this->contains_full_personal($personal_id))
		{
			$link_items[] = 'Personal Details';

			$link_items[] = 'Personal References';

		}
		else if ($this->contains_only_personal_detail($personal_id))
		{
			$link_items[] = 'Personal Details';
		}
		else if ($this->contains_only_personal_refrenece($personal_id))
		{
			$link_items[] = 'Personal References';
		}
		if ($this->acl->Acl_Check_For_Access(Array($this->data->current_module, $this->data->current_mode, 'personal', 'personal_contacts')))
		{
			$link_items[] = 'Contact Information';
		}
		return $this->getMenuLinks($links, $link_items, $div_attributes);
	}

	protected function Is_This_Read_Only($parent_id, $child_name)
	{
		if($this->data->isReadOnly) return TRUE;
		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id == $parent_id
					&& $value->name == $child_name
					&& $value->read_only > 0)
			{
				return TRUE;
			}
		}

		return FALSE;
	}


	protected function getMenuLinks($links, $link_items, $div_attributes)
	{
		$result = $links['Main'];
		if (empty($link_items))
		{
			return $result;
		}

		$height = 14 * count($link_items) + 4;

		$result .= '
			<div ' . sprintf($div_attributes, $height) . '>';

		$first = true;
		foreach ($link_items as $item)
		{
			if (!$first)
			{
				$result .= '';
			}
			else
			{
				$first = false;
			}

			$result .= '
				' . $links[$item];
		}

		$result .= '
			</div>';
		return $result;
	}

	protected function contains_only_personal_refrenece($personal_id)
	{
		$personal_details_is_found = FALSE;
		$personal_refs_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $personal_id) continue;
			if ($value->name === 'personal_details') $personal_details_is_found = TRUE;
			if ($value->name === 'personal_references') $personal_refs_is_found = TRUE;
		}

		if ($personal_refs_is_found && !$personal_details_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

	protected function contains_full_personal($personal_id)
	{
		$result = FALSE;
		$personal_details_is_found = FALSE;
		$personal_refs_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $personal_id) continue;
			if ($value->name === 'personal_details') $personal_details_is_found = TRUE;
			if ($value->name === 'personal_references') $personal_refs_is_found = TRUE;
		}

		if ($personal_refs_is_found && $personal_details_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

	public function Get_Transaction_Link($transaction_id)
	{
		$result = '';
		$active_customer = FALSE;
		$show_transactions = FALSE;
		$cancel_scheduled = false;

		foreach($this->data->schedule_status->posted_schedule as $item) {
			if($item->event_name_short == 'cancel' && $item->status == 'scheduled') $cancel_scheduled = true;
		}


		// If there's no balance on the account, we probably shouldn't
		// be trying to add the various Editing / Payment functions.
		if((($this->data->level1 === 'customer') || ($this->data->level2 === 'customer')
					|| ($this->data->level3 === 'customer')) && ($this->data->status !== 'paid') && ($this->data->status !== 'approved')
				&& $cancel_scheduled == false)
		{
			$active_customer = TRUE;
		}

		// Make sure they're not a conversion account because they will have a posted total, but not be considered 'active' yet.
		if( ($active_customer === TRUE) || (($this->data->schedule_status->posted_total > 0) && ($this->data->level1 !== 'cashline')))
		{
			$show_transactions = TRUE;
		}

		// Enable the display of the transactions menu for Title Loan apps in the underwriting and verification areas
		if(($this->mode === 'underwriting' || $this->mode === 'verification'))
		{
			$show_transactions = TRUE;
		}



		if ($transaction_id > -1)
		{
			$has_transaction_overview = FALSE;
			$has_payment_arrangement = FALSE;
			$has_manual_payment = FALSE;
			$has_internal_adjustment = FALSE;
			$has_add_fees = FALSE;
			$has_add_recovery = FALSE;
			$has_add_recovery_reversal = FALSE;
			$has_ad_hoc_schedule = FALSE;
			$has_add_write_off = FALSE;
			$has_place_in_qc_ready = FALSE;
			$has_refund = FALSE;
			$has_scheduled_payout = FALSE;
			$has_cancel_loan = FALSE;
			$has_add_paydown = FALSE;
			$has_debt_consolidation = FALSE;
			$has_chargeback = FALSE;
			$has_chargeback_reversal = FALSE;
			$has_2nd_tier_recovery = FALSE;
			$has_next_payment_arrangement = FALSE;
			$has_partial_payment = FALSE;
			$has_refinance = FALSE;
			$has_request_rollover = FALSE;
			$has_rollover = FALSE;
			$has_grace_period_arrangements = FALSE;
			// does personal link appear

			foreach($this->data->all_sections as $key => $value)
			{
				if($transaction_id != $value->section_parent_id) continue;

				if($value->name == 'transactions_overview' ) 
				{ 
					$has_transaction_overview = TRUE; 

				}

				if($cancel_scheduled == true) continue;

				if ($value->name == 'payment_arrangement' && $show_transactions == TRUE && (($this->data->loan_type_model != 'CSO') || ($this->data->loan_type_model == 'CSO' && $this->data->status != 'active' && $this->data->status != 'past_due'  )) )
				{
					$has_payment_arrangement = TRUE; 
				}
				
				if ($value->name == 'next_payment_arrangement' && $this->data->schedule_status->posted_and_pending_total > 0 && $this->data->loan_type_model != 'CSO' ) 
				{
					$has_next_payment_arrangement = TRUE; 
				}

				if ($value->name == 'partial_payment') 
				{
					$has_partial_payment = TRUE; 

				}

				if ($value->name == 'manual_payment' &&
						($show_transactions === TRUE && $this->data->status != 'paid') )
				{
					$has_manual_payment = TRUE;
				}

				if ($value->name === 'internal_adjustment' && $show_transactions === TRUE) 
				{
					$has_internal_adjustment = TRUE; 
				}

				if ($value->name === 'add_fees' && $show_transactions === TRUE ) 
				{
					$has_add_fees = TRUE; 
				}

				if($show_transactions == TRUE && $this->data->loan_type_model == 'CSO' && $this->data->schedule_status->posted_and_pending_total > 0 && $this->ach_allowed())
				{
					$has_refinance = TRUE;

				}
				if($show_transactions == TRUE && $this->data->schedule_status->posted_and_pending_total > 0 && $this->ach_allowed())
				{	
					if($this->data->business_rules['transaction_methods']['loan_renewal_request'] == 'yes')
					{
						$has_request_rollover = TRUE;
					}
					if($this->data->business_rules['transaction_methods']['loan_renewal'] == 'yes')
					{
						$has_rollover = TRUE;
					}
				}

				if ($this->data->loan_type_model == 'CSO' && $this->data->status == 'past_due' )
				{
					$has_grace_period_arrangements = TRUE; 
				}
				
				
				/**
				 * @todo This is majorly broke.  This currently points to the 
				 * Payment Arrangements Screen.  The Section for it is only in 
				 * Conversion.  I can only assume it's 2nd Tier Payment, which 
				 * is currently handled by the Manual Payments screen. [BrianR]
				 */
				if ($value->name === 'add_recovery' && $show_transactions === TRUE) 
				{
					$has_add_recovery = TRUE; 
				}


				foreach ($this->data->schedule_status->posted_schedule as $event) 
				{
					if($event->event_name === 'Second Tier Recovery (Principal)' || $event->event_name === 'Second Tier Recovery (Fees)')
					{
						$has_2nd_tier_recovery = TRUE;
						break;
					}
				}
				// Mantis Issue: 2758 (Even if the customer is Inactive Recovered we want to show Recovery Reversal)
				// This really only applies to customers in 2nd Tier / External Collections.
				if ($value->name === 'add_recovery_reversal' && ($this->data->status === 'recovered' 
							|| $this->data->status === 'sent' && $this->data->level1 === 'external_collections') && $has_2nd_tier_recovery)
				{
					$has_add_recovery_reversal = TRUE; 
				}


				if ($value->name === 'ad_hoc_schedule' && $show_transactions === TRUE) 
				{
					if(eCash_Config::getInstance()->USE_ADHOC_PAYMENTS !== FALSE)
					{
						$has_ad_hoc_schedule = TRUE;
					}
				}

				if ($value->name === 'add_writeoff'  && $show_transactions === TRUE) 
				{
					$has_add_write_off = TRUE; 
				}

				if ($value->name === 'place_in_qc_ready' && $show_transactions === TRUE && (eCash_Config::getInstance()->USE_QUICKCHECKS === TRUE))
				{
					$has_place_in_qc_ready = (($this->data->status === 'unverified' && $this->data->level1 === 'bankruptcy')
							|| ($this->data->status === 'verified' && $this->data->level1 === 'bankruptcy')) ? FALSE : TRUE;

				}

				if ($value->name === 'refund' && ($this->data->schedule_status->posted_and_pending_total > 0 || $this->data->status == 'paid')) 
				{
					$has_refund = TRUE; 
				}

				if ($value->name === 'refund_3rd_party') 
				{
					$has_refund = TRUE; 
				}

				if ($value->name === 'cancel_loan')
				{
					// If it's been less than three days since the last payment and they're in a normal active
					// status or they're in a Pre-Fund status, allow them to Cancel their loan.
					if (($this->data->level1 === 'servicing' && $this->data->status != 'past_due') || $this->data->status === 'approved')
					{
						if ($this->data->schedule_status->cancellable) 
						{ 
							$has_cancel_loan = TRUE;
						}
					}
				}

				// Add Paydown -- Have to have a Principal Balance
				if ($value->name === 'add_paydown' && $show_transactions === TRUE && $this->data->schedule_status->posted_and_pending_principal > 0 && $this->ach_allowed())
				{
					// A paydown should only apply for Servicing customers.
					// Customers in Collections need to make Arrangements
					if($this->data->level1 === 'servicing')
					{
						$has_add_paydown = TRUE;
					}
				}

				// Payout -- Have to have some sort of balance
				if ($value->name === 'schedule_payout' && $show_transactions === TRUE && $this->data->schedule_status->posted_and_pending_total > 0 && $this->ach_allowed() ) 
				{
					$has_scheduled_payout = TRUE; 
				}

				if ($value->name === 'debt_consolidation'
						&& $this->data->status != 'withdrawn' //mantis:2859
						&& $this->data->status != 'denied'
						&& $this->data->status != 'confirm_declined'
						&& $this->data->status != 'declined'
						&& $this->data->status != 'disagree'
						&& $this->data->status != 'paid'
						&& $this->data->status != 'recovered'
						&& ($this->data->status != 'active' && $this->data->loan_type_model == 'CSO')
						&& $this->data->schedule_status->posted_and_pending_total > 0
				   )
				{
					if(eCash_Config::getInstance()->USE_DEBT_CONSOLIDATION_PAYMENTS !== FALSE)
					{
						$has_debt_consolidation = TRUE;
					}

				}

				if ($value->name === 'chargeback' && $this->data->schedule_status->can_chargeback === TRUE && $this->data->loan_type_model != 'CSO')
				{
					$has_chargeback = TRUE; 
				}

				if ($value->name === 'chargeback_reversal' && $this->data->schedule_status->can_reverse_chargeback === TRUE && $this->data->loan_type_model != 'CSO')
				{
					$has_chargeback_reversal = TRUE; 
				}
			}





			// For second tier pending, we want to restrict to manual payments, and payment arrangements
			// Gforge #16487 4.1.4.2
			if ($this->data->level0 === 'pending' && $this->data->level1 === 'external_collections')
			{
				$has_internal_adjustment = FALSE;
				$has_add_fees = FALSE;
				$has_add_recovery = FALSE;
				$has_add_recovery_reversal = FALSE;
				$has_ad_hoc_schedule = FALSE;
				$has_add_write_off = FALSE;
				$has_place_in_qc_ready = FALSE;
				$has_refund = FALSE;
				$has_scheduled_payout = FALSE;
				$has_cancel_loan = FALSE;
				$has_add_paydown = FALSE;
				$has_debt_consolidation = FALSE;
				$has_chargeback = FALSE;
				$has_chargeback_reversal = FALSE;
				$has_2nd_tier_recovery = FALSE;
				$has_next_payment_arrangement = FALSE;
				$has_partial_payment = FALSE;
			}


			// For second tier sent, we want to restrict to 2nd tier recovery payments only
			// Gforge #16487 4.1.4.2
			if ($this->data->level0 === 'sent' && $this->data->level1 === 'external_collections')
			{
				$has_payment_arrangement = FALSE;
				$has_manual_payment = FALSE;

				$has_internal_adjustment = FALSE;
				$has_add_fees = FALSE;
				$has_add_recovery = FALSE;
				$has_add_recovery_reversal = FALSE;
				$has_ad_hoc_schedule = FALSE;
				$has_add_write_off = FALSE;
				$has_place_in_qc_ready = FALSE;
				$has_refund = FALSE;
				$has_scheduled_payout = FALSE;
				$has_cancel_loan = FALSE;
				$has_add_paydown = FALSE;
				$has_debt_consolidation = FALSE;
				$has_chargeback = FALSE;
				$has_chargeback_reversal = FALSE;
				$has_2nd_tier_recovery = TRUE;
				$has_next_payment_arrangement = FALSE;
				$has_partial_payment = FALSE;
			}

			// Read-only access currently works great because pre-fund statuses don't meet the conditions
			// of having the payments available. However with Geneva Roth, Collections Contact status is
			// a read-only status. We need to disable all payments for read-only statuses.
			$readonly_statuses = Get_Readonly_Statuses();

			// It's a read-only status
			if(in_array($this->data->application_status_id, $readonly_statuses))
			{
				$has_payment_arrangement = FALSE;
				$has_manual_payment = FALSE;

				$has_internal_adjustment = FALSE;
				$has_add_fees = FALSE;
				$has_add_recovery = FALSE;
				$has_add_recovery_reversal = FALSE;
				$has_ad_hoc_schedule = FALSE;
				$has_add_write_off = FALSE;
				$has_place_in_qc_ready = FALSE;
				$has_refund = FALSE;
				$has_scheduled_payout = FALSE;
				$has_cancel_loan = FALSE;
				$has_add_paydown = FALSE;
				$has_debt_consolidation = FALSE;
				$has_chargeback = FALSE;
				$has_chargeback_reversal = FALSE;
				$has_2nd_tier_recovery = FALSE;
				$has_next_payment_arrangement = FALSE;
				$has_refinance = FALSE;
				$has_request_rollover = FALSE;
				$has_rollover = FALSE;
				$has_grace_period_arrangements = FALSE;
				$has_partial_payment = FALSE;
			}


			if ($has_transaction_overview ||
					$has_payment_arrangement ||
					$has_manual_payment ||
					$has_internal_adjustment ||
					$has_ad_hoc_schedule ||
					$has_add_write_off ||
					$has_place_in_qc_ready ||
					$has_refund ||
					$has_add_fees ||
					$has_add_recovery ||
					$has_add_recovery_reversal ||
					$has_scheduled_payout ||
					$has_cancel_loan ||
					$has_add_paydown ||
					$has_debt_consolidation ||
					$has_next_payment_arrangement ||
					$has_refinance	||
					$has_request_rollover 	||
					$has_rollover)
			{

				$result = "<div id=\"AppSubMenuTrans\" class=\"level3_nav\" onClick=\"Toggle_Menu('transaction_float_menu', this.id);\">Transactions</div>\n";
				$result .= "<div class=\"submenu_layer {$this->mode}\" id=\"transaction_float_menu\">\n";

				// Transactions Overview
				if ($has_transaction_overview) 
				{
					$result .= "<a id=\"AppSubMenuTransOverview\" href=\"#\" onClick=\"SetDisplay(1,1,0,'view', 'schedule_buttons');\"><div>Transactions Overview</div></a>\n"; 
				}

				// Payment Arrangements
				if ($has_payment_arrangement) 
				{
					$result .= "<a id=\"AppSubMenuPaymentArrange\" href=\"#\" onClick=\"SetDisplay(1,1,1,'edit', 'payment_arrangement_buttons');\"><div>Payment Arrangement</div></a>\n"; 
				} 

				// Next Payment Adjustment
				if ($this->data->schedule_status->num_scheduled_events > 0 && $has_next_payment_arrangement && !$this->data->schedule_status->has_arrangements && ($this->module_name === 'loan_servicing' || $this->module_name === 'collections') && ($this->data->level2 == 'customer')) 
				{
					$result .= "<a id=\"AppSubMenuArrangeNextPayment\" href=\"#\" onClick=\"SetDisplay(1,1,7,'edit', 'next_payment_adjustment_buttons');\"><div>Arrange Next Payment</div></a>\n"; 
				}

				// GF #13254: Partial payments should only be available when an application is in collections, and there's no payments scheduled. [benb]
				// assuming still restricted to the loan servicing and collections modules.
				// GF #16266: Made it also look to see whether the level2 was collections (collections new (even though it should have scheduled stuff)). [benb]
				if ($has_partial_payment && ($this->module_name === 'loan_servicing' || $this->module_name === 'collections') && ($this->data->level1 == "collections" || $this->data->level2 == "collections") && $this->data->schedule_status->num_scheduled_events == 0)
				{
					$result .= "<a id=\"AppSubMenuPartialPayment\" href=\"#\" onClick=\"SetDisplay(1,1,9,'edit', 'partial_payment_buttons');\"><div>Partial Payment</div></a>\n"; 
				}
				// Manual Payment
				if ($has_manual_payment) 
				{
					$result .= "<a id=\"AppSubMenuManualPayment\" href=\"#\" onClick=\"SetDisplay(1,1,3,'edit', 'manual_payment_buttons');\"><div>Manual Payment</div></a>\n"; 
				}

				// AdHoc Schedule
				if ($has_ad_hoc_schedule) 
				{
					$result .= "<a id=\"AppSubMenuAdHoc\" href=\"#\" onClick=\"SetDisplay(1,1,4,'edit', 'ad_hoc_buttons');\"><div>Ad Hoc Schedule</div></a>\n"; 
				}

				// Debt Consolidation
				if ($has_debt_consolidation) 
				{
					$result .= "<a id=\"AppSubMenuDebtConsol\" href=\"#\" onClick=\"SetDisplay(1,1,5,'edit', 'debt_consolidation_buttons');\"><div>Debt Consolidation</div></a>\n"; 

					if($this->data->schedule_status->has_debt_consolidation_payments === TRUE)
					{
						$result .= "<a id=\"AppSubMenuPostDebtConsol\" href=\"#\" onClick=\"OpenTransactionPopup('post_debt_consolidation&amp;application_id={$this->data->application_id}', 'Post Debt Consolidation', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Post Debt Consolidation</div></a>\n";
					}
				}

				// Add Paydown
				if ($has_add_paydown) 
				{
					$result .= "<a id=\"AppSubMenuAddPaydown\" href=\"#\" onClick=\"OpenTransactionPopup('paydown', 'Add Paydown', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Add Paydown</div></a>\n"; 
				}

				// Payout
				if ($has_scheduled_payout) 
				{
					$result .= "<a id=\"AppSubMenuPayout\" href=\"#\" onClick=\"OpenTransactionPopup('payout', 'Add Payout', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Schedule Payout</div></a>\n"; 
				}

				// Add Write-Off
				if ($has_add_write_off) 
				{
					$result .= "<a id=\"AppSubMenuAddWriteoff\" href=\"#\" onClick=\"OpenTransactionPopup('writeoff','Add Debt Writeoff', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Add Writeoff</div></a>\n"; 
				}

				// Internal Adjustment
				if ($has_internal_adjustment) 
				{
					$result .= "<a id=\"AppSubMenuInternalAdj\" href=\"#\" onClick=\"OpenTransactionPopup('adjustment', 'Internal Adjustment', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Internal Adjustment</div></a>\n"; 
				}

				// Refund
				if ($has_refund) 
				{
					$result .= "<a id=\"AppSubMenuRefund\" href=\"#\" onClick=\"OpenTransactionPopup('refund', 'Refund Amount', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Refund</div></a>\n"; 
				}

				// Cancel Loan
				if ($has_cancel_loan) 
				{
					$result .= "<a id=\"AppSubMenuCancelLoan\" href=\"#\" onClick=\"VerifyCancel();\"><div>Cancel Loan</div></a>\n"; 
				}

				// Chargeback
				if ($has_chargeback) 
				{
					$result .= "<a id=\"AppSubMenuChargeback\" href=\"#\" onClick=\"OpenTransactionPopup('chargeback', 'Chargeback', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Chargeback</div></a>\n"; 
				}

				// Chargeback Reversal
				if ($has_chargeback_reversal)
				{
					$result .= "<a id=\"AppSubMenuChargebackReverse\" href=\"#\" onClick=\"OpenTransactionPopup('chargeback_reversal', 'Chargeback Reversal', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Chargeback Reversal</div></a>\n"; 
				}

				if ($has_2nd_tier_recovery)
				{
					$result .= "<a id=\"AppSubMenu2TierRecovery\" href=\"#\" onClick=\"SetDisplay(1,1,3,'edit', 'manual_payment_buttons');\"><div>2nd Tier Recovery Payment</div></a>\n";
				}

				// Recovery
				if ($has_add_recovery) 
				{
					$result .= "<a id=\"AppSubMenuAddRecovery\" href=\"#\" onClick=\"SetDisplay(1,1,1,'edit', 'add_recovery_buttons');\"><div>Add Recovery</div></a>\n";
				}

				// Recovery Reversal
				if ($has_add_recovery_reversal)
				{
					$result .= "<a id=\"AppSubMenuRecoveryReverse\" href=\"#\" onClick=\"OpenTransactionPopup('ext_recovery_reversal', 'Recovery Reversal', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Recovery Reversal</div></a>\n";
				}

				// Place in QC Ready
				if ($has_place_in_qc_ready) 
				{
					$result .=  "<a id=\"AppSubMenuPlaceInQCReady\" href=\"#\" onClick=\"VerifyPlaceInQC();\"><div>Place in QC Ready</div></a>\n"; 
				}

				// Fees
				if ($has_add_fees && $this->data->status != 'withdrawn' && $this->data->status != 'paid') 
				{
					// Lien Fees and Transfer Fess should only be applied once
					if($this->data->schedule_status->has_lien_fee === FALSE && $this->data->loan_type_model === 'Title')
					{
						$result .= "<a id=\"AppSubMenuAddLienFee\" href=\"#\" onClick=\"AddFee('assess_fee_lien',0,'Lien Fee');\"><div>Add Lien Fee</div></a>\n"; 
					}

					if($this->data->schedule_status->has_transfer_fee === FALSE)
					{
						$transfer_fee_amount = $this->data->business_rules['moneygram_fee'];
						$result .= "<a id=\"AppSubMenuAddWireTransFee\" href=\"#\" onClick=\"AddFee('assess_fee_transfer',{$transfer_fee_amount},'Wire Transfer Fee');\"><div>Add Wire Transfer Fee</div></a>\n"; 
					}

					// There are no limits to Delivery fees.
					if ($this->data->loan_type_model === 'Title')
					{
						$delivery_fee_amount = $this->data->business_rules['ups_label_fee'];
						$result .= "<a id=\"AppSubMenuAddDeliveryFee\" href=\"#\" onClick=\"AddFee('assess_fee_delivery',{$delivery_fee_amount}, 'Delivery Fee');\"><div>Add Delivery Fee</div></a>\n"; 
					}
				}

				//Grace Period Arrangements I <3 CSO!
				if ($has_grace_period_arrangements)
				{
					$result .= "<a id=\"AppSubMenuGracePeriod\" href=\"#\" onClick=\"OpenTransactionPopup('grace_period_arrangement', 'Add Grace Period Arrangement', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Grace Period Arrangements</div></a>\n"; 
				}
				
				//Rollovers
				if ($has_refinance)
				{
					$result .= "<a id=\"AppSubMenuRefi\" href=\"#\" onClick=\"OpenTransactionPopup('refinance', 'Refinance', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Refinance</div></a>\n"; 
				}
				//Rollovers
				if ($has_rollover)
				{
					$result .= "<a id=\"AppSubMenuRenew\" href=\"#\" onClick=\"OpenTransactionPopup('rollover', 'Rollover', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Renewal</div></a>\n"; 
				}
				//Rollovers
				if ($has_request_rollover)
				{
					$result .= "<a id=\"AppSubMenuRequestRenew\" href=\"#\" onClick=\"OpenTransactionPopup('request_rollover', 'Request Rollover', '{$this->mode}');Toggle_Menu('transaction_float_menu');\"><div>Request Renewal</div></a>\n"; 
				}
				$result .= "</div>\n";
			}
			else
			{
				$result = "<div></div>\n";
			}
		}
		return $result;
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

	protected function contains_only_personal_detail($personal_id)
	{
		$personal_details_is_found = FALSE;
		$personal_refs_is_found = FALSE;

		foreach($this->data->all_sections as $key => $value)
		{
			if ($value->section_parent_id !== $personal_id) continue;
			if ($value->name === 'personal_details') $personal_details_is_found = TRUE;
			if ($value->name === 'personal_references') $personal_refs_is_found = TRUE;
		}

		if (!$personal_refs_is_found && $personal_details_is_found)
		{
			return TRUE;
		}

		return FALSE;
	}

}
?>
