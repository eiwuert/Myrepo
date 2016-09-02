<?php

	require_once('olp_ldb.php');

	class Agean_LDB extends OLP_LDB
	{
		protected $hash_key = 'L08N54M3';
		
		public function __construct(&$mysql, $property_short = null, $data = array())
		{
			$this->ent_prop_list = Enterprise_Data::getCompanyData(Enterprise_Data::COMPANY_AGEAN);
			parent::__construct($mysql, $property_short, $data);
		}
		
		public function Create_Transaction($data, $send_email = TRUE)
		{
			if(!empty($data['vehicle_make']))
			{
				$this->required_tables[] = 'Vehicle';				
			}
			
			return parent::Create_Transaction($data, $send_email);
		}
		
		public function Insert_Application()
		{
			$insert_info = parent::Insert_Application();

			// gForge #5209 - double checking date format and formatting it correctly for mysql.	[RV]
			if (isset($this->data['banking_start_date']))
			{
				// gForge #8292 - Fixing the date formatting issues.	[RV]
				$b_date_array = sscanf($this->data['banking_start_date'], '%u-%u-%u', $b_year, $b_month, $b_date);
				$insert_info['data']['banking_start_date'] = date('Y-m-d', mktime(0,0,0, $b_month, $b_date, $b_year));
			}
			
			// gForge #5209 - double checking date format and formatting it correctly for mysql.	[RV]
			if (isset($this->data['residence_start_date']))
			{
				// gForge #8292 - Fixing the date formatting issues.	[RV]
				$r_date_array = sscanf($this->data['residence_start_date'], '%u-%u-%u', $r_year, $r_month, $r_date);
				$insert_info['data']['residence_start_date'] = date('Y-m-d', mktime(0,0,0, $r_month, $r_date, $r_year));
			}
			
			$insert_info['data']['date_hire'] = $this->data['date_of_hire'];
			$insert_info['data']['job_title'] = $this->data['work_title'];
			
			return $insert_info;
		}
		
		protected function Get_Mail_Template()
		{
			return 'AGEAN_PAPERLESS_FUNDER_REVIEW';
		}
		
		public function Get_Condor_Template()
		{
			$template = 'Loan Documents DE Payday';
			if(strcasecmp($this->property_short, 'jiffy') === 0)
			{
				$template = 'Loan Documents CA';
			}
			elseif(!empty($this->data['vehicle_make']))
			{
				$template = 'Loan Documents DE Title';
			}

			return $template;
		}
		
		protected function Get_Login_Link($site_name = '')
		{
			$site_name = $this->ent_prop_list[$this->property_short]['site_name'];
			$site_id = $this->ent_prop_list[$this->property_short]['site_id'];
			
			$login_hash = md5($this->data['application_id'] . $site_id . $this->hash_key);
			$encoded_app_id = urlencode(base64_encode($this->data['application_id']));
			
			return "http://{$site_name}/LoanPage.aspx?applicationid={$encoded_app_id}&login={$login_hash}";
		}
		
		protected function Get_Loan_Type()
		{
			if(strcasecmp(strtolower($this->property_short), 'jiffy') === 0)
			{
				$this->loan_type = 'california_payday';
			}
			elseif(!empty($this->data['vehicle_make']))
			{
				$this->loan_type = 'delaware_title';
			}
			else
			{
				$this->loan_type = 'delaware_payday';
			}
		}
		
		public function Insert_Vehicle()
		{
			$insert_info = array(
				'table' => 'vehicle',
				'data' => array(
					'date_created'		=> 'NOW()',
					'date_modified'		=> 'NOW()',
					'application_id'	=> $this->data['application_id'],
					'vin'				=> $this->data['vehicle_vin'],
					'license_plate'		=> $this->data['vehicle_license_plate'],
					'make'				=> $this->data['vehicle_make'],
					'model'				=> $this->data['vehicle_model'],
					'series'			=> $this->data['vehicle_series'],
					'style'				=> $this->data['vehicle_style'],
					'color'				=> $this->data['vehicle_color'],
					'year'				=> $this->data['vehicle_year'],
					'mileage'			=> $this->data['vehicle_mileage'],
					'value'				=> $this->data['vehicle_value'],
					'title_state'		=> $this->data['vehicle_title_state'],
				)
			);
			
			return $insert_info;
		}
		
	}

?>
