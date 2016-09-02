<?php
require_once 'CompanyData.php';

/**
 * Temporary wrapper class to the Enterprise Data class in BFW.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 * @author Matt Piper <matt.piper@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class EnterpriseData extends CompanyData
{
	/**
	 * A 1:1 mapping of property short aliases in the format
	 * 'ALIAS' => 'PROP'
	 *
	 * @var array
	 */
	private $aliases = array(
	);

	/**
	 * When an alias campaign successfully sells, we need to switch
	 * the promo on redirect so that the reports will show how
	 * the new campaign is working.
	 *
	 * @var array
	 */
	private $alias_promos = array(
		'CBNK1' => 31141,
		'IC_T1' => 30790,
		'IC_PST'=> 31173,
		'IC_EST'=> 31172,
		'IC_CC'	=> 31191,
		'IC_ND' => 32407,
		'IC_ND2'=> 32548,
		'IC2'	=> 32549,
		'LCS_T1'=> 32809,
	);

	/**
	 * List of enterprise companies
	 *
	 * @var array
	 */
	protected $enterprise_companies = array(
		self::COMPANY_AGEAN,
		self::COMPANY_CLK,
		self::COMPANY_FBOD,
		self::COMPANY_GENERIC,
		self::COMPANY_IMPACT,
		self::COMPANY_LCS,
		self::COMPANY_QEASY,
		self::COMPANY_HMS,
		self::COMPANY_OPM,
		self::COMPANY_DMP,
		self::COMPANY_MMP,
	);

	/**
	 * A 1:1 mapping of sites to property shorts in the format
	 * 'sitename.com' => 'PROP'
	 *
	 * @var unknown_type
	 */
	private $ent_prop_short_list = array (
		'someloancompany.com'		=> 'GENERIC',
	);
	
	/**
	 * An array of eCash URLs for each enterprise company.
	 *
	 * @var array
	 */
	private $ent_ecash_hostname_list = array(
		self::COMPANY_GENERIC => array(
			'LIVE' => 'live.ecash.loanservicingcompany.com',
			'RC' => 'rc.ecash.someloancompany.com',
			'LOCAL' => 'rc.ecash.someloancompany.com',
			'QA_MANUAL' => 'aalm.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'aalm.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'aalm.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_AGEAN => array(
			'LIVE' => 'live-ecash.ageanonline.com',
			'RC' => 'rc.ecash.agean.ept.tss',
			'LOCAL' => 'rc.ecash.agean.ept.tss',
			'QA_MANUAL' => 'agean.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'agean.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'agean.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_IMPACT => array(
			'LIVE' => 'funding.impactcashusa.com',
			'RC' => 'rc.ecash.impactcashusa.com',
			'LOCAL' => 'rc.ecash.impactcashusa.com',
			'QA_MANUAL' => 'impact.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'impact.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'impact.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_CLK => array(
			'LIVE' => 'live.ecash.eplatflat.com',
			'RC' => 'rc.ecash.eplatflat.com',
			'LOCAL' => 'rc.ecash.eplatflat.com',
			'QA_MANUAL' => 'qa.ecash.eplatflat.com',
			'QA2_MANUAL' => 'qa2.ecash.eplatflat.com',
			'CACHE_BY_COMPANY' => FALSE,
		),
		
		self::COMPANY_HMS => array(
			'LIVE' => 'live.ecash.hugoservicesonline.com',
			'RC' => 'rc.ecash.hms.ept.tss',
			'LOCAL' => 'rc.ecash.hms.ept.tss',
			'QA_MANUAL' => 'hms.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'hms.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'hms.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_OPM => array(
			'LIVE' => 'live.ecash.bigskycash.com',
			'RC' => 'rc.ecash.opm.ept.tss',
			'LOCAL' => 'rc.ecash.opm.ept.tss',
			'QA_MANUAL' => 'opm.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'opm.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'opm.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_DMP => array(
			'LIVE' => 'live.ecash.mycashcenter.com',
			'RC' => 'rc.ecash.mcc.ept.tss',
			'LOCAL' => 'rc.ecash.mcc.ept.tss',
			'QA_MANUAL' => 'mcc.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'mcc.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'mcc.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
		
		self::COMPANY_MMP => array(
			'LIVE' => 'live.ecash.mymoneypartner.com',
			'RC' => 'rc.ecash.mmp.ept.tss',
			'LOCAL' => 'rc.ecash.mmp.ept.tss',
			'QA_MANUAL' => 'mmp.ecash.manual.qa.tss',
			'QA_SEMIAUTOMATED' => 'mmp.ecash.semi-automated.qa.tss',
			'QA_AUTOMATED' => 'mmp.ecash.automated.qa.tss',
			'CACHE_BY_COMPANY' => TRUE,
		),
	);

	/**
	 * A list of enterprise data for all property shorts.
	 *
	 * @var array
	 */
	private $ent_prop_list = array(
		'PCL' => array(
			'site_name' => 'oneclickcash.com',
			'license' => array (
						'LIVE' => '1f1baa5b8edac74eb4eaa329f14a03619f025e2000e0a7b26429af2395f847ce',
						'RC' => '1f1baa5b8edac74eb4eaa329f14a03610a2177d7a01cd1a59258c95fdb31f87b',
						'LOCAL' => '1f1baa5b8edac74eb4eaa329f14a0361604521a4b54937ed3385eb0b5e274b2a'
						),
			'name' => 'OneClickCash',
			'legal_entity' => 'OneClickCash',
			'cs_phone' => '8002303266',
			'cs_fax' => '8885536477',
			'teleweb_phone' => '8002987460',
			'cs_email' => 'customerservice@oneclickcash.com',
			'compliance_email' => 'compliancedepartment@oneclickcash.com',
			'coll_phone' => '8003499418',
			'ecash_version' => 3.0,
			'street' => '52946 Highway 12 Suite 3',
			'city' => 'Niobrara',
			'state' => 'NE',
			'zip' => '68760',
			'ent_short_url' => 'www.wrlez.com',
			'property_short' => 'PCL',
			'ctc_promo_id' => '29704',
			'egc_promo_id' => '29703',
			'sms_promo_id' => '26182',
			'wap_promo_id' => '30234',
			'db_type' => 'mysql',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'sms_promo_id' => '26182',
			'use_cfe' => FALSE,
			'page_id' => 1807,
			'confirm_third_party_sale' => TRUE,
			'react_use_normal_confirm_agree_process' => TRUE,
			'react_use_entauth' => TRUE,
			'react_use_login_bypass_auth' => TRUE,
		),
		'UCL' =>array(
			'site_name' => 'unitedcashloans.com',
			'license' => array (
						'LIVE' => 'd386ac4380073ed7d193e350851fe34f',
						'RC' => 'd63c6aaf39e22727c6438daf81f3a603',
						'LOCAL' => '060431565db8215c0e44bd345a339cbe',
						),
			'name' => 'UnitedCashLoans',
			'legal_entity' => 'UnitedCashLoans',
			'cs_fax' => '8008038794',
			'cs_phone' => '8002798511',
			'cs_email' => 'customerservice@unitedcashloans.com',
			'compliance_email' => 'compliancedepartment@unitedcashloans.com',
			'coll_phone' => '8003540602',
			'teleweb_phone' => '8003034963',
			'ecash_version' => 3.0,
			'street' => 'PO Box 111',
			'city' => 'Miami',
			'state' => 'OK',
			'zip' => '74355',
			'ent_short_url' => 'www.prduw.com',
			'db_type' => 'mysql',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'property_short' => 'UCL',
			'ctc_promo_id' => '30054',
			'egc_promo_id' => '30053',
			'sms_promo_id' => '26183',
			'wap_promo_id' => '30235',
			'use_cfe' => FALSE,
			'page_id' => 39417,
			'confirm_third_party_sale' => TRUE,
			'react_use_normal_confirm_agree_process' => TRUE,
			'react_use_entauth' => TRUE,
			'react_use_login_bypass_auth' => TRUE,
		),
		'CA' => array(
			'site_name' => 'ameriloan.com',
			'license' => array (
						'LIVE' => 'b8f225e1a2865c224d55c98cf85d399a',
						'RC' => '2b76c04f9a36630314691f5b7d40825a',
						'LOCAL' => 'b11647308d21180eb2e424ef6d4cae5a',
						),
			'name' => 'AmeriLoan',
			'legal_entity' => 'AmeriLoan',
			'cs_fax' => '8002569166',
			'db_type' => 'mysql',
			'cs_phone' => '8003629090',
			'cs_email' => 'customerservice@ameriloan.com',
			'compliance_email' => 'compliancedepartment@ameriloan.com',
			'coll_phone' => '8005368918',
			'teleweb_phone' => '8003039123',
			'ecash_version' => 3.0,
			'street' => 'PO Box 111',
			'city' => 'Miami',
			'state' => 'OK',
			'zip' => '74355',
			'ent_short_url' => 'www.niegi.com',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'property_short' => 'CA',
			'sms_promo_id' => '26184',
			'wap_promo_id' => '30232',
			'use_cfe' => FALSE,
			'page_id' => 39413,
			'confirm_third_party_sale' => TRUE,
			'react_use_normal_confirm_agree_process' => TRUE,
			'react_use_entauth' => TRUE,
			'react_use_login_bypass_auth' => TRUE,
		),
		'UFC' => array(
			'site_name' => 'usfastcash.com',
			'license' =>  array (
							'LIVE' => '11041e0365baa557ec768915a501faab',
							'RC' => 'f5b522467891c35bdf29db4365e8b253',
							'LOCAL' => '2704c44311fc6383ed880c1c057a3bdf',
							),
			'name' => 'USFastCash',
			'legal_entity' => 'USFastCash',
			'cs_fax' => '8008038796',
			'cs_phone' => '8006401295',
			'cs_email' => 'customerservice@usfastcash.com',
			'compliance_email' => 'compliancedepartment@usfastcash.com',
			'coll_phone' => '8006369460',
			'teleweb_phone' => '8002980487',
			'ecash_version' => 3.0,
			'street' => 'PO Box 111',
			'city' => 'Miami',
			'state' => 'OK',
			'zip' => '74355',
			'ent_short_url' => 'www.koutr.com',
			'db_type' => 'mysql',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'property_short' => 'UFC',
			'ctc_promo_id' => '29707',
			'egc_promo_id' => '29550',
			'sms_promo_id' => '26185',
			'wap_promo_id' => '30236',
			'use_cfe' => FALSE,
			'page_id' => 17212,
			'confirm_third_party_sale' => TRUE,
			'react_use_normal_confirm_agree_process' => TRUE,
			'react_use_entauth' => TRUE,
			'react_use_login_bypass_auth' => TRUE,
		),
		'D1'=>array(
			'site_name' => '500fastcash.com',
			'license' => array (
							'LIVE' => '38652e89cffb810a98577dd04c8daf43',
							'RC' => 'adfc593c968599f7f406aa84c0fa8a55',
							'LOCAL' => 'bc599acd75dd875d5a33a597d68af14a',
						),
			'name' => '500FastCash',
			'legal_entity' => '500FastCash',
			// [#13072] Added in a card customer service phone # into the company data array	[RV]
			'card_cs_phone'=>'8004097338',
			'cs_phone'=>'8889196669',
			'cs_fax' => '8004161619',
			'teleweb_phone' => '8002976309',
			'cs_email' => 'customerservice@500fastcash.com',
			'compliance_email' => 'compliancedepartment@500fastcash.com',
			'coll_phone' => '8883396669',
			'ecash_version' => 3.0,
			'street' => '515 G SE',
			'city' => 'Miami',
			'state' => 'OK',
			'zip' => '74354',
			'ent_short_url' => 'www.slewr.com',
			'db_type' => 'mysql',
			'use_verify_queue',
			'new_ent' => TRUE,
			'use_soap' => FALSE,
			'property_short' => 'D1',
			'sms_promo_id' => '26181',
			'wap_promo_id' => '30233',
			'use_cfe' => FALSE,
			'page_id' => 39121,
			'confirm_third_party_sale' => TRUE,
			'react_use_normal_confirm_agree_process' => TRUE,
			'react_use_entauth' => TRUE,
			'react_use_login_bypass_auth' => TRUE,
		),
		'IC' => array(
			'site_name' => 'impactcashusa.com',
			'license' => array (
							'LIVE' => '6acd9423b6a2c32813e85d3705fd5300',
							'RC' => '7d83d14e88f63a492e7375a6de460eb2',
							'LOCAL' => '74cb58689fb09537cb37effafb06ba3b',
						),
			'name' => 'PDL Ventures, LLC',
			'legal_entity' => 'PDL Ventures, LLC',
			'cs_fax' => '8884305140',
			'db_type' => 'mysql',
			'cs_phone'=>'8007070102',
			'ecash_version' => 3.0,
			'street' => 'PO Box 140',
			'city' => 'Box Elder',
			'state' => 'MT',
			'zip' => '59521',
			'ent_short_url' => '',
			'cs_email' => 'support@impactcashusa.com',
			'teleweb_phone' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'IC',
			'sms_promo_id' => '28155',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_impact/code',
			'ecash_api_class' => 'IMPACT_API',
			'page_id' => 48230,
			'confirm_third_party_sale' => FALSE
		),
		'IFS' => array(
			"site_name" => "impactsolutiononline.com",
			'name' => 'PDL Ventures, LLC',
			"license" => array (
							'LIVE' => 'a55c2ae41cb0e9a7b5207e3c415c4d5d',
							'RC' => 'd934c50af0f0ef2a0201557aa8aebe4f',
							'LOCAL' => 'f096ee9c7357a23dabc9d5e312ef6b21',
						),
			"legal_entity" => "PDL Ventures, LLC",
			'cs_fax' => "8003213887",
			"db_type" => "mysql",
			'cs_phone'=>"8003213886",
			'cs_email' => 'support@impactsolutiononline.com',
			'ecash_version' => 3.0,
			'street' => 'PO Box 140',
			'city' => 'Box Elder',
			'state' => 'MT',
			'zip' => '59521',
			'ent_short_url' => '',
			'teleweb_phone' => '',
			'cs_email' => 'support@impactsolutiononline.com',
			'teleweb_phone' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'IFS',
			'sms_promo_id' => '28155',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_impact/code',
			'ecash_api_class' => 'IMPACT_API',
			'page_id' => 61319,
			'confirm_third_party_sale' => FALSE,
		),
		'ICF' => array(
			"site_name" => "cashfirstonline.com",
			"name" => 'Cash First',
			"license" => array (
							'LIVE' => '4af52fea05cc512349c51a1ed64787da',
							'RC' => '342437b42c0c96a724aabb19c6ed1f28',
							'LOCAL' => '8a0ed89ba98f0f8a5f7363bad8cdf139',
						),
			"name" => "PDL Ventures, LLC",
			"legal_entity" => "PDL Ventures, LLC",
			'cs_fax' => "8003218719",
			"db_type" => "mysql",
			'cs_phone'=>"8003218718",
			'ecash_version' => 3.0,
			'street' => 'PO Box 140',
			'city' => 'Box Elder',
			'state' => 'MT',
			'zip' => '59521',
			'ent_short_url' => '',
			'cs_email' => 'support@cashfirstonline.com',
			'teleweb_phone' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'ICF',
			'sms_promo_id' => '28155',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_impact/code',
			'ecash_api_class' => 'IMPACT_API',
			'page_id' => 61552,
			'confirm_third_party_sale' => FALSE,
		),
		'IIC' => array(
			'site_name' => 'impactintacash.com',
			'license' => array (
							'LIVE' => '2f9a80046a284503f1e4730ea16ebbe1',
							'RC' => '9cda28bede70df97173c4e06539cd2e1',
							'LOCAL' => '654e79491e6afe28a81e90fdbe1bf13d',
						),
			'name' => 'PDL Ventures, LLC',
			'legal_entity' => 'PDL Ventures, LLC',
			'cs_fax' => '8669746822',
			'db_type' => 'mysql',
			'cs_phone'=>'8669646822',
			'cs_email' => 'customerservice@impactintacash.com',
			'teleweb_phone' => '',
			'ecash_version' => 3.0,
			'street' => 'PO Box 140',
			'city' => 'Box Elder',
			'state' => 'MT',
			'zip' => '59521',
			'ent_short_url' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'IIC',
			'sms_promo_id' => '32462',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_impact/code',
			'ecash_api_class' => 'IMPACT_API',
			'page_id' => 65392,
			'confirm_third_party_sale' => FALSE,
			'ecash_hostname_list' => array(
				'LIVE' => 'live.ecash.impactintacash.com',
				'RC' => 'rc.ecash.intacash.ept.tss',
				'LOCAL' => 'rc.ecash.intacash.ept.tss',
				'CACHE_BY_COMPANY' => FALSE,
			),
		),
		'IPDL' => array(
			"site_name" => "impactcashcap.com",
			'name' => 'PDL Ventures, LLC',
			"license" => array (
							'LIVE' => '7ac786263857f87ce0c073956406f155',
							'RC' => '018c82278487dcb4f7c22af62510ae0b',
							'LOCAL' => 'b2b3dd245712c91c280e9275a59617ca',
						),
			"legal_entity" => "PDL Ventures, LLC",
			'cs_fax' => "8003216018",
			"db_type" => "mysql",
			'cs_phone'=>"8003216017",
			'ecash_version' => 3.0,
			'street' => 'PO Box 140',
			'city' => 'Box Elder',
			'state' => 'MT',
			'zip' => '59521',
			'ent_short_url' => '',
			'cs_email' => 'support@impactcashcap.com',
			'teleweb_phone' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'IPDL',
			'sms_promo_id' => '28155',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_impact/code',
			'ecash_api_class' => 'IMPACT_API',
			'page_id' => 61556,
			'confirm_third_party_sale' => FALSE,
		),

		/** eCash Generic **/
		'GENERIC' => array(
			"site_name" => "someloancompany.com",
			"license" => array (
							'LIVE' => 'some_license_key',
							'RC' => 'some_license_key',
							'LOCAL' => 'some_license_key',
						),
			"name" => "someloancompany.com",
			"legal_entity" => "someloancompany.com",
			'cs_fax' => "8770000000",
			"db_type" => "mysql",
			'cs_phone'=>"8000000000",
			'ecash_version' => 3.0,
			'street' => '',
			'city' => '',
			'state' => '',
			'zip' => '',
			'ent_short_url' => '',
			'cs_email' => 'customerservice@someloancompany.com',
			'teleweb_phone' => '',
			'use_verify_queue',
			'new_ent' => FALSE,
			'use_soap' => FALSE,
			'property_short' => 'GENERIC',
			'use_cfe' => TRUE,
			'ecash_api_path' => 'ecash_scn/code',
			'ecash_api_class' => 'SCN_API',
			'page_id' => 60482,
			'confirm_third_party_sale' => FALSE,
			'vendor_api_enterprise' => 'scn',
		),
	);


	/**
	 * An instance of this class
	 *
	 * @var EnterpriseData
	 */
	protected static $instance = NULL;

	/**
	 * Gets an instance of this class.
	 * For internal use only!
	 *
	 * @return EnterpriseData An instance of this class.
	 */
	protected static function getInstance()
	{
		if (!(self::$instance instanceof EnterpriseData))
		{
			self::$instance = new EnterpriseData();
		}

		return self::$instance;
	}

	/**
	 * Get the full Enterprise property list
	 *
	 * @return array
	 */
	public static function getEntPropList()
	{
		return self::getInstance()->ent_prop_list;
	}

	/**
	 * Get the full Enterprise property short list
	 *
	 * @return array
	 */
	public static function getEntPropShortList()
	{
		return self::getInstance()->ent_prop_short_list;
	}


	/**
	 * Check if a site exists in the Enterprise property short list
	 *
	 * @param string $site A full sitename (ex: sitename.com)
	 * @return bool TRUE if site is an enterprise site
	 */
	public static function siteIsEnterprise($site = NULL)
	{
		if (empty($site))
		{
			$site = SiteConfig::getInstance()->site_name;
		}

		return (empty($site)) ? FALSE : isset(self::getInstance()->ent_prop_short_list[strtolower($site)]);
	}

	/**
	 * Determines if the site belongs to the specified company.
	 *
	 * @param string $company The name of the company
	 * @param string $site The site's URL
	 * @return bool TRUE if the site belongs to the company.
	 */
	public static function siteIsCompany($company, $site = NULL)
	{
		$result = FALSE;

		if (self::isCompany($company))
		{
			if (is_null($site))
			{
				$site = SiteConfig::getInstance()->site_name;
			}

			if (!empty($site) && isset(self::getInstance()->ent_prop_short_list[strtolower($site)]))
			{
				$result = self::isCompanyProperty($company, self::getInstance()->ent_prop_short_list[strtolower($site)]);
			}
		}

		return $result;
	}

	/**
	 * Gets a property short based on the provided site.
	 *
	 * @param string $site A full sitename (ex: sitename.com)
	 * @return string|null The property short found for the given site.
	 */
	public static function getProperty($site)
	{
		$property = NULL;

		if (self::siteIsEnterprise($site))
		{
			$property = self::getInstance()->ent_prop_short_list[strtolower($site)];
		}

		return $property;
	}

	/**
	 * Finds the company for a given property short.
	 *
	 * @param string $property the property short
	 * @return string|null The name of the company found.
	 */
	public static function getCompany($property)
	{
		$company = NULL;

		if (self::isEnterpriseCompany($property))
		{
			$company = $property;
		}
		else if (self::isEnterprise($property))
		{
			$company = parent::getCompany(self::resolveAlias($property));
		}

		return strtoupper($company);
	}

	/**
	 * Gets properties and aliases of properties for a company
	 *
	 * @param string $company
	 * @return array
	 */
	public static function getCompanyProperties($company)
	{
		$properties = parent::getCompanyProperties($company);
		foreach ($properties as $property)
		{
			$properties = array_merge($properties,self::getAliases($property));
		}

		return $properties;
	}

	/**
	 * Will return whether a property or alias belongs to a company
	 *
	 * @param string $company
	 * @param string $property
	 */
	public static function isCompanyProperty($company, $property = NULL)
	{
		return parent::isCompanyProperty($company,self::resolveAlias($property));
	}

	/**
	 * Checks if the provided property belongs to the provided company but is not
	 * an alias of a property. This is provided as an alternative to checks like
	 * Is_Impact($property) && $property != 'ic_t1'. Instead you would
	 * call this like:
	 *
	 * EnterpriseData::isCompanyPropertyNotAlias(EnterpriseData::COMPANY_IMPACT, $property)
	 *
	 * @param string $company The name of the company.
	 * @param string $property The property short to use.
	 * 		If not provided, it will default to the config's property_short value
	 * @return bool TRUE if the property belongs to the company but is not an alias
	 * 		of one of the company's properties.
	 */
	public static function isCompanyPropertyNotAlias($company, $property = NULL)
	{
		return (parent::isCompanyProperty($company, $property) && !self::isAlias($property));
	}

	/**
	 * Checks if the provided property is an alias.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if the property is an alias.
	 */
	public static function isAlias($property)
	{
		return isset(self::getInstance()->aliases[strtoupper($property)]);
	}

	/**
	 * Determines whether the given property has aliases or not.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if the property has aliases.
	 */
	public static function hasAliases($property)
	{
		return (count(self::getAliases($property)) > 0);
	}

	/**
	 * Gets the 'real' property short for the provided alias.
	 * Will default to the passed-in property short if it can't find an alias.
	 *
	 * @param string $property the property short
	 * @return string The property short found for the alias.  This value is always all uppercase.
	 */
	public static function resolveAlias($property)
	{
		if (self::isAlias($property))
		{
			$property = self::getInstance()->aliases[strtoupper($property)];
		}

		return strtoupper($property);
	}

	/**
	 * Gets all aliases for a given property short.
	 *
	 * @param string $property the property short
	 * @return array A list of property shorts that are aliases for the provided property short.
	 */
	public static function getAliases($property)
	{
		return array_keys(self::getInstance()->aliases, strtoupper($property));
	}

	/**
	 * Gets the main property and all of the aliases for a given property short.
	 * If you provide an alias, it will resolve that alias before fetching all of the other
	 * aliases for that property.
	 *
	 * @param string $property the property short
	 * @return array An array containing the main property along with all aliases
	 */
	public static function getPropertyAndAliases($property)
	{
		$real_property = self::resolveAlias($property);
		$property_aliases = self::getAliases($real_property);
		return array_merge(array($real_property), $property_aliases);
	}

	/**
	 * Gets a promo_id to use on redirect to the enterprise site for an Alias.
	 *
	 * @param string $property The property short of the alias.
	 * @return int The promo_id to be used.  NULL if it doesn't exist.
	 */
	public static function getAliasPromo($property)
	{
		$promo = NULL;

		if (self::isAlias($property) && !empty(self::getInstance()->alias_promos[strtoupper($property)]))
		{
			$promo = self::getInstance()->alias_promos[strtoupper($property)];
		}

		return $promo;
	}

	/**
	 * Checks if a property short has an entry in the Enterprise property list.
	 *
	 * @param string $property the property short
	 * @return bool TRUE if an entry is found.
	 */
	public static function isEnterprise($property)
	{
		return isset(self::getInstance()->ent_prop_list[self::resolveAlias($property)]);
	}

	/**
	 * Gets all the Enterprise data for a property short.
	 *
	 * @param string $property the property short
	 * @return array An array with all the data (or an empty array if none is found)
	 */
	public static function getEnterpriseData($property)
	{
		$data = array();

		if (self::isEnterprise($property))
		{
			$data = self::getInstance()->ent_prop_list[self::resolveAlias($property)];
		}

		return $data;
	}

	/**
	 * Gets all Enterprise data for all properties associated with the given company.
	 *
	 * @param string $company a string of the company name
	 * @return array
	 */
	public static function getCompanyData($company)
	{
		$properties = self::getCompanyProperties($company);

		$data = array();
		foreach ($properties as $property)
		{
			$data[$property] = self::getEnterpriseData($property);
		}

		return $data;
	}

	/**
	 * Returns whether or not a company is an Enterprise company.
	 *
	 * @param string $company The Company name
	 * @return bool TRUE if the company is an Enterprise company.
	 */
	public static function isEnterpriseCompany($company)
	{
		return (in_array(strtoupper(self::resolveAlias($company)), self::getInstance()->enterprise_companies));
	}

	/**
	 * Gets a specific option from the Enterprise data for the given property.
	 *
	 * @param string $property the property short
	 * @param string $option an optional parameter to retrieve
	 * @return mixed The data found for the option (or null if none is found).
	 */
	public static function getEnterpriseOption($property, $option)
	{
		$value = NULL;

		if (self::isEnterprise($property) && isset(self::getInstance()->ent_prop_list[self::resolveAlias($property)][$option]))
		{
			$value = self::getInstance()->ent_prop_list[self::resolveAlias($property)][$option];
		}

		return $value;
	}

	/**
	 * Gets the license key for the current mode for the given property short.
	 *
	 * @param string $property a string of the property short
	 * @param string $mode The current process mode (LOCAL/RC/LIVE)
	 * @return string The license key found or null otherwise.
	 */
	public static function getLicenseKey($property, $mode = NULL)
	{
		if (is_null($mode) && defined('BFW_MODE'))
		{
			$mode = BFW_MODE;
		}

		$keys = self::getEnterpriseOption($property, 'license');

		return (is_array($keys) && !is_null($mode)) ? $keys[$mode] : NULL;
	}

	/**
	 * Returns all Enterprise property shorts.
	 *
	 * @param bool $include_aliases If TRUE, this will also return aliases.
	 * @return array
	 */
	public static function getAllProperties($include_aliases = TRUE)
	{
		$properties = ($include_aliases) ? array_keys(self::getInstance()->aliases) : array();

		foreach (self::getInstance()->companies as $company => $props)
		{
			// We only want Enterprise companies
			if (self::isEnterpriseCompany($company))
			{
				$properties = array_merge($properties, $props);
			}
		}

		return array_unique($properties);
	}

	/**
	 * Returns if the property short is set to use CFE or not.
	 *
	 * @param string $property Property short to check.
	 * @return boolean
	 */
	public static function isCFE($property)
	{
		$return = FALSE;
		if (self::isEnterprise($property))
		{
			$use_cfe = self::getEnterpriseOption($property, 'use_cfe');
			$return = (is_bool($use_cfe)) ? $use_cfe : FALSE;
		}
		return $return;
	}

	/**
	 * Does this site confirm the sale of a lead
	 * to a third party?
	 *
	 * @param string|null $site
	 * @return boolean
	 */
	public static function confirmThirdPartySale($site = NULL)
	{
		$ret = FALSE;
		if (empty($site))
		{
			$site = SiteConfig::getInstance()->site_name;
			if (empty($site))
			{
				throw new RuntimeException('No site.');
			}
		}
		if (self::siteIsEnterprise($site))
		{
			$ret = self::getEnterpriseOption(self::getProperty($site), 'confirm_third_party_sale');
			if (!is_bool($ret))
			{
				$ret = FALSE;
			}
		}
		return $ret;
	}
	
	
	/**
	 * Gets the property_short's eCash hostname. Returns NULL if not found.
	 *
	 * @param string $property_short
	 * @param string $mode
	 * @return string
	 */
	public static function getEnterpriseHostname($property_short, $mode)
	{
		$property_short = self::resolveAlias($property_short);
		$company = self::getCompany($property_short);
		$mode = strtoupper($mode);
		$ecash_hostname_list = self::getInstance()->ent_ecash_hostname_list;
		$prop_list = self::getEntPropList();
		
		$hosts = array();
		
		if (isset($ecash_hostname_list[$company])
			&& is_array($ecash_hostname_list[$company])
		)
		{
			$hosts = array_merge($hosts, $ecash_hostname_list[$company]);
		}
		
		if (isset($prop_list[$property_short]['ecash_hostname_list'])
			&& is_array($prop_list[$property_short]['ecash_hostname_list'])
		)
		{
			$hosts = array_merge($hosts, $prop_list[$property_short]['ecash_hostname_list']);
		}
		
		if (isset($hosts[$mode]))
		{
			$hostname = $hosts[$mode];
		}
		elseif ($mode == 'STAGING')
		{
			$hostname = $hosts['LIVE'];
		}
		else
		{
			$hostname = NULL;
		}
		
		return $hostname;
	}
}

?>
