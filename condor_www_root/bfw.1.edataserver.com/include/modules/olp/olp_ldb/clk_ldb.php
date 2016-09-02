<?php

	require_once('olp_ldb.php');

	class CLK_LDB extends OLP_LDB
	{
		public function __construct(&$mysql, $property_short = null, $data = array())
		{
			$this->ent_prop_list = Enterprise_Data::getCompanyData(Enterprise_Data::COMPANY_CLK);
			
			parent::__construct($mysql, $property_short, $data);

			$this->required_tables[] = 'Application_Contact';
		}
		
		/**
		* @desc inserts doc records
		*
		**/
		public function Document_Event($application_id, $property_short, $type = 'web')
		{
			$company_id = $this->Company_ID($property_short);

			$archive_id = (isset($_SESSION['condor_data']['archive_id']))
							? $_SESSION['condor_data']['archive_id']
							: $this->data['condor_doc_id'];
			
			if($type == 'fax')
			{
				$docs = array(
					array (
						'date_created'		=> 'NOW()',
						'company_id'		=> $company_id,
						'application_id'	=> $application_id,
						'document_list_id'	=> 'Loan Document',
						'document_method'	=> 'fax',
						'transport_method'	=> 'condor',
						'agent_id'			=> 'olp',
						'document_event_type'=> 'sent',
						'archive_id'		=> $archive_id,
						'system_id'			=> 3
					)
				);
			}
			else
			{
				/*
					If we've already signed the docs, we don't want to attempt to insert
					the other docs again (it would in fact fail). But for Impact we want
					to insert the Loan Documents after they've been signed.
				*/
				$docs = array(
					array (
						'date_created'		=> 'NOW()',
						'company_id'		=> $company_id,
						'application_id'	=> $application_id,
						'document_list_id'	=> 'Loan Document',
						'document_method'	=> 'olp',
						'transport_method'	=> 'web',
						'agent_id'			=> 'olp',
						'document_event_type'=> 'sent',
						'archive_id'		=> $archive_id,
						'system_id'			=> 3
					)
				);
			}
			
			foreach ($docs as $doc)
			{
				$field_array['insert'] = $doc;
				$this->Insert_Record('document', $doc);

				$doc['document_event_type'] = 'received';
				$field_array['insert'] = $doc;
				$this->Insert_Record('document', $doc);
			}
		}
		
		// Mantis #12161 - Added in to override the one in olp_ldb. This just checks the loan_type data to see if this is a card or standard loan_type [RV]
		protected function Get_Loan_Type()
		{
			if($this->data['loan_type'] == 'card')
			{
				$this->loan_type = 'card';
			}
			else
			{
				$this->loan_type = 'standard';
			}
		}
		
		protected function Get_Mail_Data()
		{
			if(!isset($this->data['ecashapp']))
			{
				$data = parent::Get_Mail_Data();
			}
			else
			{
				$video = 3;
				switch(strtoupper($this->property_short))
				{
					case 'UFC':
						$event_id = 244;
						$project_id = 10261;
						$video = 4; // if USFastCash we need to use video 4, not video 3.
						break;
					case 'CA':
						$event_id = 240;
						$project_id = 10511;
						break;
					case 'UCL':
						$event_id = 242;
						$project_id = 10512;
						break;
					case 'PCL':
						$event_id = 243;
						$project_id = 10513;
						break;
					case 'D1':
						$event_id = 241;
						$project_id = 10514;
						break;
					default:
						$project_id = 10261; // Defaulting to UFC
						$video = 4;
						break;
				}
				
				//Set Legal Ent
				$prefix = (BFW_MODE == 'RC') ? 'rc.' : '';
				$site_name = $prefix . $this->ent_prop_list[$this->property_short]['site_name'];
				$confirm_url = $this->Get_Login_Link($site_name);
				
				$ip_address = (isset($this->data['client_ip_address'])) ? $this->data['client_ip_address'] : $this->data['campaign_info']['client_ip_address'];
				
				$date_app = time();
	
				$date_app_created = date('m/d/Y', $date_app);
				$time_app_created = date('h:iA', $date_app);
	
				$marketing_site_array = parse_url($site_name);
			 	$marketing_site = (array_key_exists('host', $marketing_site_array)) ? $marketing_site_array['host'] : $marketing_site_array['path'];
	
				$mark_site = explode('.', $marketing_site);
				$fund_date = date('m/d/Y', strtotime($this->data['qualify_info']['fund_date']));
	
				$data = array(
					'site_name'					=> $site_name,
					'name_view'					=> $this->ent_prop_list[$this->property_short]['legal_entity'],
					'email_primary' 			=> $this->data['email_primary'],
					'email_primary_name'		=> $this->data['name_first'] . ' ' . $this->data['name_last'],
					'name'						=> strtoupper($this->data['name_first']) . ' ' .
												   strtoupper($this->data['name_last']),
					'amount'					=> '$' . number_format($this->data['qualify_info']['fund_amount'], 2),
					'application_id'			=> $this->data['application_id'],
					'confirm_link'				=> $confirm_url,
					'video_link'				=> "http://netxstudios.sitestream.com/{$project_id}/{$video}.html",
					'estimated_fund_date_1'		=> $fund_date,
					'estimated_fund_date_2' 	=> date('m/d/Y', strtotime($fund_date) + 86400),
					'client_ip_address'			=> $ip_address,
					'username'				    => $this->data['username'],
					'password'				    => $this->data['password'],
					'date_app_created'			=> $date_app_created,
					'time_app_created'			=> $time_app_created,
					'marketing_site'			=> $mark_site[0]
				);
			}

			return $data;
		}
		
		protected function Get_Mail_Template()
		{
			$template = 'OLP_PAPERLESS_FUNDER_REVIEW';
			
			if(isset($this->data['ecashapp']))
			{
				//Use the old-style confirmation emails.
				switch(strtoupper($this->property_short))
				{
					case 'UFC':	$template = 1654; break;
					case 'UCL':	$template = 1655; break;
					case 'PCL':	$template = 1656; break;
					case 'CA':	$template = 1657; break;
					case 'D1':	$template = 1658; break;
					default:	$template = 1659; break;
				}
			}
			
			return $template;
		}
		
		
			
		/**
		 * @desc Inserts the phone and fax numbers into application_contact
		 */
		public function Insert_Application_Contact()
		{
			// Setting the application_id up here since no matter where it falls the field contents are the same
			$company_id = $this->Company_ID($this->property_short);
			$field_array['application_id'] = $this->data['application_id'];
			
			if(!empty($this->data['phone_home']))
			{
				// Insert phone_home
				$field_array['type'] = 'phone';
				$field_array['company_id'] =  $company_id;
				$field_array['application_contact_category_id'] = "Home Phone";
				$field_array['value'] = $this->data['phone_home'];
		
				$insert_id[] = $this->Insert_Record('application_contact', $field_array);
			}
	
			if(!empty($this->data['phone_work']))
			{
				// Insert phone_work
				$field_array['type'] = 'phone';
				$field_array['company_id'] =  $company_id;
				$field_array['application_contact_category_id'] = "Work Phone";
				$field_array['value'] = $this->data['phone_work'];
		
				$insert_id[] = $this->Insert_Record('application_contact', $field_array);
			}
	
			if(!empty($this->data['phone_cell']))
			{
				// Insert phone_cell
				$field_array['type'] = 'phone';
				$field_array['company_id'] =  $company_id;
				$field_array['application_contact_category_id'] = "Cell Phone";
				$field_array['value'] = $this->data['phone_cell'];
		
				$insert_id[] = $this->Insert_Record('application_contact', $field_array);
			}
	
			if(!empty($this->data['phone_fax']))
			{
				// Insert phone_fax
				$field_array['type'] = 'fax';
				$field_array['company_id'] =  $company_id;
				$field_array['application_contact_category_id'] = "Primary Fax";
				$field_array['value'] = $this->data['phone_fax'];
		
				$insert_id[] = $this->Insert_Record('application_contact', $field_array);
			}
	
			return $insert_id;
		}
	}

?>
