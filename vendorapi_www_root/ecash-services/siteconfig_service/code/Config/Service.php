<?php

class Config_Service
{
	private $config;
	private $license_mode;

	public function __construct($config, $license_mode = 'LIVE')
	{
	//	$this->config = $config;
		$this->license_mode = $license_mode;
	}

	/**
	 * Loads a site config by license key, promo ID, and promo sub code.
	 *
	 * The site config is retrieved from Webadmin1 and then 'enhanced' with
	 * enterprise specific data from OLP's EnterpriseData. This includes, among
	 * other things, the company and enterprise name (eg., CLK and CA,
	 * respectively).
	 *
	 * @param $license String Site license key
	 * @param $promo String Promo ID
	 * @param $sub String Promo Sub Code
	 * @return Object
	 */
	public function getConfig($license, $promo, $sub)
	{
        // generic
        if ($license == 'some_license_key') $config = json_decode('{"promo_id":"37407","promo_sub_code":"","vendor_id":"52454","promo_status":{"valid":"invalid","promo_status":"ACTIVE","page_id":"82287"},"cost_action":"submit","exit_strategy":null,"validation_fields":[],"license":"1ec0ce43ed28aaff5e5f129b6d6bf284","created_date":"2011-11-09 11:00:34","mode":"LIVE","page_id":60482,"site_id":"60480","property_id":"60883","page_name":"\/","site_name":"someloancompany.com","property_name":"SomeLoanCompany","stat_server":"1","stat_base":"","site_server":"1","site_base":"olp_bb_visitor","site_category":"","bb_flag":"FALSE","bb_stamp":"0000-00-00 00:00:00","qualify":{},"legal_entity":"someloancompany.com","property_short":"GENERIC","disable_advertising":true,"online_confirmation":true,"force_promo_id":"0","support_fax":"8772250623","support_phone":"8005579038","footer_amount":"1,000","bypass_limits":true,"contact_us_pending_loan_email":"clientservices@someloancompany.com","disable_preferred_tier":true,"organic_lead_site":"generic","default_promo_id":"33854","display_captcha":true,"customer_service_email":"customerservice@someloancompany.com","statpro_customer":"generic","event_pixel":[],"name_view":"SomeLoanCompany","lrrt_kgm":true,"limits":{"accept_level":"1","hourly_limits":true,"stat_caps":[{"period":"WEEKLY","type":"vendor_id","cap":"540","stat":"sold_amg5","start_day":"SATURDAY","start_date":"2011-11-05","end_date":"","bypass_limits":1,"fallback_limits":0},{"period":"WEEKLY","type":"vendor_id","cap":"495","stat":"sold_amg3","start_day":"SATURDAY","start_date":"2011-11-05","end_date":"","bypass_limits":1,"fallback_limits":0},{"period":"WEEKLY","type":"vendor_id","cap":"1350","stat":"sold_amg2","start_day":"SATURDAY","start_date":"2011-11-05","end_date":"","bypass_limits":1,"fallback_limits":0},{"period":"WEEKLY","type":"vendor_id","cap":"2115","stat":"sold_amg_st1","start_day":"MONDAY","start_date":"2011-11-07","end_date":"","bypass_limits":1,"fallback_limits":0}]},"promo_limit":"0","min_lead_cost":"20.00","excluded_targets":"awl, bdp_t1, vip_t1, cld, grv_st1, cure2, scs_t1","enterprise":"scn","company":"generic","enterprise_license":"1ec0ce43ed28aaff5e5f129b6d6bf284","name":"someloancompany.com","cs_fax":"8772250623","db_type":"mysql","cs_phone":"8005579038","ecash_version":3,"street":"","city":"","state":"","zip":"","ent_short_url":"","cs_email":"customerservice@someloancompany.com","teleweb_phone":"","new_ent":false,"use_soap":false,"use_cfe":true,"ecash_api_path":"ecash_aalm\/code","ecash_api_class":"SCN_API","confirm_third_party_sale":false,"vendor_api_enterprise":"scn"}');
		$config->promo_id = $promo;
		$config->promo_sub_code = $sub;
		return json_encode($config);
        /*	$site = $this->config->Get_Site_Config($license, $promo, $sub);
		if (($site->promo_id == '10000')
			&& (preg_match('/^[1-9]\d{4,9}$/', $site->default_promo_id)))
		{
			$site = $this->config->Get_Site_Config($license, $site->default_promo_id, $sub);
		}

		$this->loadEnterpriseData($site, $site->site_name);

		if ($site->enterprise == 'generic')
		{
			$site->enterprise = 'scn';
		}

		// If we don't have a StatPro customer in the config, get it based on
		// the enterprise
		if (empty($site->statpro_customer))
		{
			$site->statpro_customer = strtolower($site->enterprise);
		}

		return json_encode($site);*/
	}

	/**
	 * Loads data related to the ECash enterprise/company.
	 *
	 * This currently relies on OLP's EnterpriseData class. In the future,
	 * this data should either be stored directly in the runtime configuration,
	 * or EnterpriseData should be moved into this project.
	 *
	 * @param $config stdClass Site config object
	 * @param $site String Site name (eg. someloancompany.com)
	 * @return void
	 */
	private function loadEnterpriseData($config, $site)
	{
		$property = EnterpriseData::getProperty($config->site_name);

		if ($property !== NULL)
		{
			$config->enterprise = strtolower(EnterpriseData::getCompany($property));
			$config->company = strtolower($property);

			foreach (EnterpriseData::getEnterpriseData($property) as $name=>$value)
			{
				if ($name === 'license') {
					$config->enterprise_license = $value[$this->license_mode];
				} elseif ($name != 'site_name') {
					$config->{$name} = $value;
				}
			}
		}
	}
}
