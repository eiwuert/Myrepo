<?php

/**
 * TranDotCom (Name of file is wrong)
 *
 * A concrete implementation class for posting to trandotcom loan processing
 * system
 */

class Vendor_Post_Impl_transdotcom extends Abstract_Vendor_Post_Implementation
{
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 8;

	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
				'post_url' => 'http://blackbox.post.server.jubilee.tss:8080/p.php/TRANSDOTCOM',
				'default_benefit_start' => '',
				'state_req' => false,
				'weekends' => true, //Check for weekends
				'send_requested_effective_date' => true,
				'id_verified' => 'N',
				'send_dismode_field' => FALSE
				),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
				),
			// The next entries are params specific to property shorts.
			// They have higher priority than all of the previous entries
			'tbf'    => Array(
				'ALL'      => Array(
					'store_id' => '14300490001',
					'user_id' => '53002373',
					'password' => 'password',
					'marketing_code' => '359',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '300', // GForge #4648 [DY]
					'finance_charge' => '150',
					'pdloanrcvdfrom_is_promo_id' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.3bpaydayloans.com/leads.aspx'
					),
				),
			'gecc'    => Array(
				'ALL'      => Array(
					'store_id' => '26209100001',
					'user_id' => 'pweekly',
					'password' => 'password',
					'marketing_code' => '11',
					'pdloanrcvdfrom' => 'PWeekly',
					'loan_amount' => '400',
					'finance_charge' => '120',
					'pdloanrcvdfrom_is_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.cashdirectexpress.com/leads.aspx'
					),
				),
			'gecc_t1'    => Array(
				'ALL'      => Array(
					'store_id' => '26209100001',
					'user_id' => 'pweekly',
					'password' => 'password',
					'marketing_code' => '11',
					'pdloanrcvdfrom' => 'PWeekly',
					'loan_amount' => '400',
					'finance_charge' => '120',
					'pdloanrcvdfrom_is_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.cashdirectexpress.com/leads.aspx'
					),
				),
			'frca_t1'    => Array(
				'ALL'      => Array(
					'store_id' => '27800490001',
					'user_id' => '53002172',
					'password' => 'password',
					'marketing_code' => '512',
					'pdloanrcvdfrom' => 'PartnerWeekly',
					'loan_amount' => '400',
					'finance_charge' => '120',
					'send_requested_effective_date_today' => TRUE,
					// 'pdloanrcvdfrom_is_promo_id' => true,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastandreliablecash.com/leads.aspx',
					),
				),				
			'py'    => Array(
				'ALL'      => Array(
					//'post_url' => 'https://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom' => 'PART01'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayyes.com/leads.aspx',
					'store_id' => '57204100001',
					),
				),
			'py2'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'pdloanrcvdfrom' => 'PART05',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayservices.com/leads.aspx',
					'store_id' => '57203100001',
					),
				),
			'py_t1'    => Array(
				'ALL'      => Array(
					//'post_url' => 'https://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom' => 'PART01'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayyes.com/leads.aspx',
					'store_id' => '57204100001',
					),
				),
			'sdw'    => Array(
				'ALL'      => Array(
					'store_id' => '16700490001',
					'user_id' => '53002242',
					'password' => 'password',
					'marketing_code' => '253',
					'loan_amount' => '300', // was 500 before Hope told me to  change it [AuMa]
					'finance_charge' => '150',
					'pdloanrcvdfrom' => 'partnerweekly',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.secondwallet.com/swxmlpostingacceptor.php',
					'store_id' => '16700490001',
					),
				),
			'wp'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.webpayday.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.webpayday.com/leads.aspx',
					'store_id' => '57202490001',
					),
				),
			'wp_t1'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.webpayday.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.webpayday.com/leads.aspx',
					'store_id' => '57202490001',
					),
				),
			'cs'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.cashsupply.com/leads.aspx',
					'store_id' => '57201350001',
					),
				),
			'cs_t1'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.cashsupply.com/leads.aspx',
					'store_id' => '57201350001',
					),
				),
			'cs2'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => 'partpc',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.papercheckpaydayloan.com/leads.aspx',
					'store_id' => '57206100001',
					),
				),
			'ps'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayservices.com/leads.aspx',
					'store_id' => '57203100001',
					),
				),
			'ps2'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'pdloanrcvdfrom' => 'PART05',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayservices.com/leads.aspx',
					'store_id' => '57203100001',
					),
				),
			'ps_t1'    => Array(
				'ALL'      => Array(
					//'post_url' => 'http://qa.cashsupply.com/leads.aspx',
					'store_id' => '57299350001',
					'user_id' => '1111144',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'default_benefit_start' => '1/1/2006',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.paydayservices.com/leads.aspx',
					'store_id' => '57203100001',
					),
				),
			'zip_t2'    => Array(//GForge:ePoint:PartnerWeekly:#4701 [AuMa]
				'ALL'      => Array(
					'store_id' => '26202290001',
					'user_id' => '53002167',
					'password' => 'password',
					'marketing_code' => '19',
					'pdloanrcvdfrom' => 'PWKY02',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'default_benefit_start' => '1/1/2004'
					),
				'LOCAL'    => Array(
					'post_url' => 'http://209.59.172.228/leads.aspx',
					'store_id' => '5555',
					'user_id' => '1',
					'password' => 'password',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.NetCashUsa.com/leads.aspx',
					'store_id' => '26302290001'
					),
				),
			'zip_t1'    => Array(//GForge:ePoint:PartnerWeekly:#3360 [MJ]
				'ALL'      => Array(
					'store_id' => '26202290001',
					'user_id' => '53002167',
					'password' => 'password',
					'marketing_code' => '19',
					'pdloanrcvdfrom' => 'PWKY02',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'default_benefit_start' => '1/1/2004'
					),
				'LOCAL'    => Array(
					'post_url' => 'http://209.59.172.228/leads.aspx',
					'store_id' => '5555',
					'user_id' => '1',
					'password' => 'password',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.zipcash.com/leads.aspx',
					'store_id' => '26202290001'
					),
				),
			'zip1'    => Array(
				'ALL'      => Array(
					'store_id' => '26202290001',
					'user_id' => '53002167',
					'password' => 'password',
					'marketing_code' => '19',
					'pdloanrcvdfrom' => 'PWKY01',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'default_benefit_start' => '1/1/2004'
					),
				'LOCAL'    => Array(
					'post_url' => 'http://209.59.172.228/leads.aspx',
					'store_id' => '5555',
					'user_id' => '1',
					'password' => 'password',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.zipcash.com/leads.aspx',
					'store_id' => '26202290001'
					),
				),
			'zip2'    => Array(
				'ALL'      => Array(
					'store_id' => '26302290001',
					'user_id' => '53002167',
					'password' => 'password',
					'marketing_code' => '19',
					'pdloanrcvdfrom' => 'PWKY02',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'default_benefit_start' => '1/1/2004'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.netcashusa.com/leads.aspx'
					),
				),
			'fwc_al'    => Array(
				'ALL'      => Array(
					'store_id' => '17001010001',
					'user_id' => '53002174',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '52.50',
					'state_req' => '10',	// 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17001010001'
					),
				),
			'fwc_mo'    => Array(
				'ALL'      => Array(
					'store_id' => '17002290001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '75',
					'state_req' => '14',	// 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17002290001'
					),
				),
			'fwc_mo2'    => Array(
				'ALL'      => Array(
					'store_id' => '17002290001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '75',
					'state_req' => '14',	// 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17002290001'
					),
				),
			'fwc_sd'    => Array(
				'ALL'      => Array(
					'store_id' => '17003460001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',		// 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17003460001'
					),
				),
			'fwc_sd2'    => Array(
				'ALL'      => Array(
					'store_id' => '17003460001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',		// 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17003460001'
					),
				),
			'fwc_ut'    => Array(
				'ALL'      => Array(
					'store_id' => '17000490001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17000490001'
					),
				),
			'fwc_ut2'    => Array(
				'ALL'      => Array(
					'store_id' => '17000490001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17000490001'
					),
				),
			'fwc_mt'    => Array(
				'ALL'      => Array(
					'store_id' => '17004300001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '75',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17004300001'
					),
				),
			'fwc_co'    => Array(
				'ALL'      => Array(
					'store_id' => '17005080001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '60',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17005080001'
					),
				),
			'fwc_wi'    => Array(
				'ALL'      => Array(
					'store_id' => '17006550001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17006550001'
					),
				),
			'fwc_wi2'    => Array(
				'ALL'      => Array(
					'store_id' => '17006550001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17006550001'
					),
				),
			'fwc_wy'    => Array(
				'ALL'      => Array(
					'store_id' => '17007560001',
					'user_id' => '53002168',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '30',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17007560001'
					),
				),
			'fwc_az'    => Array(
				'ALL'      => Array(
					'store_id' => '17008040001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '52.94',
					'state_req' => '5',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17008040001'
					),
				),
			'fwc_az2'    => Array(
				'ALL'      => Array(
					'store_id' => '17008040001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '52.94',
					'state_req' => '5',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17008040001'
					),
				),
			'fwc_id'    => Array(
				'ALL'      => Array(
					'store_id' => '17001000001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17001000001'
					),
				),
			'fwc_id2'    => Array(
				'ALL'      => Array(
					'store_id' => '17001000001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17001000001'
					),
				),
			'fwc_nd'    => Array(
				'ALL'      => Array(
					'store_id' => '17011380001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '60',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17011380001'
					),
				),
			'fwc_de'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17009100001'
					),
				),
			'fwc_de2'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17009100001'
					),
				),
			'fwc_pa'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17009100001'
					),
				),
			'fwc_pa2'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					'store_id' => '17009100001'
					),
				),
			'fwc_la'    => Array(
				'ALL'      => Array(
					'store_id' => '17012420001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '45',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_la2'    => Array(
				'ALL'      => Array(
					'store_id' => '17012420001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '300',
					'finance_charge' => '45',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_mn'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_mn2'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_ri'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_ri2'    => Array(
				'ALL'      => Array(
					'store_id' => '17009100001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_hi'    => Array(//GForge #3982 [MJ]
				'ALL'      => Array(
					'store_id' => '17014150001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_ks'    => Array(//GForge #3982 [MJ]
				'ALL'      => Array(
					'store_id' => '17015200001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_wa'    => Array(//GForge #3982 [MJ]
				'ALL'      => Array(
					'store_id' => '17016530001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'fwc_ak'    => Array(//GForge #3982 [MJ]
				'ALL'      => Array(
					'store_id' => '17013020001',
					'user_id' => '53002171',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'state_req' => '4',    // 1 day is added later to $req_days
					),
				'LOCAL'    => Array(
				'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.fastwirecash.com/leads.aspx',
					),
				),
			'pts'    => Array(
				'ALL'      => Array(
					'store_id' => '56100350001',
					'user_id' => 'sellings',
					'password' => 'password',
					'marketing_code' => '119',
					'pdloanrcvdfrom_is_sellingsource_promo_id'=>true,
					'loan_amount' => '500',
					'finance_charge' => '60',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.nationwidecash.com/Leads.aspx',
					'store_id' => '56100350001'
					),
				),
			'pts_t2'    => Array(
				'ALL'      => Array(
					'store_id' => '56100350001',
					'user_id' => 'sellings',
					'password' => 'password',
					'marketing_code' => '119',
					'pdloanrcvdfrom_is_sellingsource_promo_id'=>true,
					'loan_amount' => '500',
					'finance_charge' => '60',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.nationwidecash.com/Leads.aspx',
					),
				),
			'ilt'    => Array(
				'ALL'      => Array(
					'store_id' => '16102120001',
					'user_id' => '53002227',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'dis_mode'	=> 'A',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.instantloantoday.com/leads.aspx',
					),
				),
			'iln'    => Array(
				'ALL'      => Array(
					'store_id' => '16101120001',
					'user_id' => '53002234',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'dis_mode'	=> 'A',
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.instantloansnow.com/leads.aspx',
					),
				),
			'apd'    => Array(
				'ALL'      => Array(
					'store_id' => '16103120001',
					'user_id' => 'Selling',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '450',
					'finance_charge' => '135',
					'dis_mode'	=> 'A',
					'send_promo_id' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://lrs01.artissystems.com/leads.aspx',
					),
				),
			'apd2'    => Array(
				'ALL'      => Array(
					'store_id' => '16103120001',
					'user_id' => 'Selling',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '450',
					'finance_charge' => '135',
					'dis_mode'	=> 'A',
					'send_promo_id' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://lrs01.artissystems.com/leads.aspx',
					),
				),
			'apd_t1'    => Array(
				'ALL'      => Array(
					'store_id' => '16103120001',
					'user_id' => 'Selling',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '450',
					'finance_charge' => '135',
					'dis_mode'	=> 'A',
					'send_promo_id' => TRUE,
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://lrs01.artissystems.com/leads.aspx',
					),
				),
			'abs'    => Array(
				'ALL'      => Array(
					'store_id' => '16600490001',
					'user_id' => '10000028',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '300',
					'finance_charge' => '0', // No Such info
					'dis_mode' => 'A', // No Such info
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://www.esigcentral.com/leads.aspx',
					),
				),
			'lls'    => Array(
				'ALL'      => Array(
					//'store_id' => '95000000001', @TF GF#3624
					'store_id' => '95000000004',
					'user_id' => '53002313',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '200',
					'finance_charge' => '90',
					'send_requested_effective_date_today' => TRUE,
					'send_requested_effective_date' => TRUE,
					'state_req' => '5'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					//'post_url' => 'https://www.littleloanshoppe.com/USLLS/leads.aspx',
					'post_url' => 'https://www.littleloanshoppe.com/lls/leads.aspx',
					),
				),
			'lls_1'    => Array(
				'ALL'      => Array(
					'store_id' => '95000000001', // @TF GF#3624 GForge #3835 [DY]
					// 'store_id' => '95000000004',
					'user_id' => '53002313',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Selling Source',
					'loan_amount' => '200',
					'finance_charge' => '90',
					'send_requested_effective_date_today' => TRUE,
					'send_requested_effective_date' => TRUE,
					'state_req' => '5'
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					//'post_url' => 'https://www.littleloanshoppe.com/USLLS/leads.aspx',
					'post_url' => 'https://www.littleloanshoppe.com/lls/leads.aspx',
					),
				),
			'ufpd'    => Array(
				'ALL'      => Array(
					'store_id' => '16400490001',
					'user_id' => 'partner',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '400', //
					'finance_charge' => '120', // 30 per 100
			//		'send_requested_effective_date_today' => TRUE,
			//		'send_requested_effective_date' => TRUE,
					'id_verified' => 'Y',
					'send_dismode_field' => TRUE
					),
				'LOCAL'    => Array(
				//	'post_url' => 'https://lrs02.artissystems.com/leads.aspx',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://lrs02.artissystems.com/leads.aspx',
					),
				),
			'ufpd_t1'    => Array( // ufpd_t1 added GForge #8889 [AuMa]
				'ALL'      => Array(
					'store_id' => '16400490001',
					'user_id' => 'partner',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '400', //
					'finance_charge' => '120', // 30 per 100
			//		'send_requested_effective_date_today' => TRUE,
			//		'send_requested_effective_date' => TRUE,
					'id_verified' => 'Y',
					'send_dismode_field' => TRUE
					),
				'LOCAL'    => Array(
				//	'post_url' => 'https://lrs02.artissystems.com/leads.aspx',
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'https://lrs02.artissystems.com/leads.aspx',
					),
				),
			'gcm'    => Array(
				'ALL'      => Array(
					'store_id' => '27701320001',
					'user_id' => '53002354',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'PartnerWeekly',
					'loan_amount' => '350',
					'finance_charge' => '105',
					'send_requested_effective_date_today' => TRUE,
					'send_requested_effective_date' => TRUE
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					'post_url' => 'http://www.getcashman.com/leads.aspx',
					),
				),
			'pd2g'    => Array(
				'ALL'      => Array(
					'store_id' => '60000000001',
					'user_id' => 'xmluser',
					'password' => 'password',
					'marketing_code' => '2',
					'pdloanrcvdfrom' => 'PartnerWeekly',
					'loan_amount' => '1000',
					'finance_charge' => '250',
					'post_url' => 'https://www.payday2go.com/pd2g/leads.aspx',
					'idverified_is_military' => true
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'user_id' => 'pweekly',
					'password' => 'password*',
					'marketing_code' => '343',
					'store_id' => '26216110001',
					'post_url' => 'https://www.payday2go.com/pd2g/leads.aspx',
				),
			),
			'egf'    => Array(
				'ALL'      => Array(
//					'store_id' => '57299350001',
					'store_id' => '57302490001',
					'user_id' => 'Part',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'post_url' => 'https://207.36.0.135/leads.aspx',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true,
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '57302490001',
					'post_url' => 'https://www.eaglefinance.com/leads.aspx',
				),
			),
			'egf_1'    => Array(
				'ALL'      => Array(
//					'store_id' => '57299350001',
					'store_id' => '57302490001',
					'user_id' => 'Part',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '500',
					'finance_charge' => '150',
					'post_url' => 'https://www.eaglefinance.com/leads.aspx',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '57302490001',
					'post_url' => 'https://www.eaglefinance.com/leads.aspx',
				),
			),
			'egf_we'    => Array(
				'ALL'      => Array(
//					'store_id' => '57299350001',
					'store_id' => '57302490001',
					'user_id' => 'Part',
					'password' => 'password',
					'marketing_code' => '72',
					'loan_amount' => '300',
					'finance_charge' => '150',
					'post_url' => 'https://207.36.0.135/leads.aspx',
					'send_requested_effective_date' => false,
					'pdloanrcvdfrom_is_part01_promo_id' => true
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '57302490001',
					'post_url' => 'https://www.eaglefinance.com/leads.aspx',
				),
			),

			'icd_fl'    => Array(	//Added for mantis 10049 iCashDirect INC. [MJ]
				'ALL'      => Array(
					'store_id' => '26103120001',
					'user_id' => '53002159',
					'password' => 'password',
					'marketing_code' => '359',
					'pdloanrcvdfrom' => 'PART01',
					'loan_amount' => '300',
					'finance_charge' => '78.80',
					'post_url' => 'https://florida.quickbucksdirect.com/leads.aspx',
					'send_requested_effective_date' => false,
					'state_req' => 7
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '26103120001',
					'post_url' => 'https://florida.quickbucksdirect.com/leads.aspx',
				),
			),
			'icd_tx'    => Array(	//Added for mantis 10049 iCashDirect INC. [MJ]
				'ALL'      => Array(
					'store_id' => '26104480001',
					'user_id' => '53002157',
					'password' => 'password',
					'marketing_code' => '359',
					'pdloanrcvdfrom' => 'PART01',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'post_url' => 'https://texas.quickbucksdirect.com/leads.aspx',
					'send_requested_effective_date' => false
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '26104480001',
					'post_url' => 'https://texas.quickbucksdirect.com/leads.aspx',
				),
			),
			'icd_ut'    => Array(	//Added for mantis 10049 iCashDirect INC. [MJ]
				'ALL'      => Array(
					'store_id' => '26105490001',
					'user_id' => '53002157',
					'password' => 'password',
					'marketing_code' => '359',
					'pdloanrcvdfrom' => 'PART01',
					'loan_amount' => '300',
					'finance_charge' => '90',
					'post_url' => 'https://utah.quickbucksdirect.com/leads.aspx',
					'send_requested_effective_date' => false
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '26105490001',
					'post_url' => 'https://utah.quickbucksdirect.com/leads.aspx',
				),
			),
			'udc'    => Array(
				'ALL'      => Array(
					'store_id' => '26208490002',
					'user_id' => 'partner',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '350',
					'finance_charge' => '90',
					'send_requested_effective_date' => false,
					'state_req' => '7'
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.ezpaydaycash.com/leads.aspx',
				),
			),
			'aal'    => Array(	//Added for mantis 11440 All American Lending. [TP]
				'ALL'      => Array(
					'store_id' => '26303000001',
					'user_id' => '53002243',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom_is_promo_id' => true,
					'loan_amount' => '400',//From 300 to 400 GForge#3939 [MJ] //From 400 to 300 GForge#4649 [DY] //From 300 to 350 GForge#5176 [BA]//From 350 to 400 GF_5643 [AuMa]
					'finance_charge' => '120', //from 90 to 120 GF 5680 [TF]
					'post_url' => 'http://www.myquikloan.com/leads.aspx',
					'send_requested_effective_date' => false
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'store_id' => '26303000001',
					'post_url' => 'http://www.myquikloan.com/leads.aspx',
				),
			),
			'mpt'    => Array(	// Mantis #11965 [DY]
				'ALL'      => Array(
					'store_id' => '28517000001',
					'user_id' => '53002163',
					'password' => 'ssource',
					// 'marketing_code' => '359',
					'pdloanrcvdfrom' => 'Selling Source (PW)',
					'loan_amount' => '100', // GForge #4653 [DY]
					'finance_charge' => '90',
					'state_req_array' => array(
						'default' => 4,
						'AL' => 10,
						'KS' => 7,
						'MO' => 14,
						'IL' => 13
					),
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					// 'post_url' => 'https://www.myloansusa.com/new/leads.aspx',
					'post_url' => 'https://www.myloansusa.com/leads.aspx',
					),
				),
			'mpt2'    => Array(
				'ALL'      => Array(
					'store_id' => '28517000001',
					'user_id' => '53002163',
					'password' => 'ssource',
					// 'marketing_code' => '359',
					'pdloanrcvdfrom' => 'Selling Source (PW)',
					'loan_amount' => '100',
					'finance_charge' => '90',
					'state_req_array' => array(
						'default' => 4,
						'AL' => 10,
						'KS' => 7,
						'MO' => 14,
						'IL' => 13
					),
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					// 'post_url' => 'https://www.myloansusa.com/new/leads.aspx',
					'post_url' => 'https://www.myloansusa.com/leads.aspx',
					),
				),
			'mpt3'    => Array(
				'ALL'      => Array(
					'store_id' => '28517000001',
					'user_id' => '53002163',
					'password' => 'ssource',
					// 'marketing_code' => '359',
					'pdloanrcvdfrom' => 'Selling Source (PW)',
					'loan_amount' => '100',
					'finance_charge' => '90',
					'state_req_array' => array(
						'default' => 4,
						'AL' => 10,
						'KS' => 7,
						'MO' => 14,
						'IL' => 13
					),
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					),
				'LOCAL'    => Array(
					),
				'RC'       => Array(
					),
				'LIVE'     => Array(
					// 'post_url' => 'https://www.myloansusa.com/new/leads.aspx',
					'post_url' => 'https://www.myloansusa.com/leads.aspx',
					),
				),
			'exc'    => Array( // Mantis #11965 [DY]
				'ALL'      => Array(
					'store_id' => '15200490001',
					// 'post_url' => 'https://www.instantcash911.com/leads.aspx',
					'user_id' => '53002388',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '400',
					'finance_charge' => '120',
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					'state_req' => '4',
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.instantcash911.com/lrs/leads.aspx',
				),
			),
            'exc1'    => Array( // GForge #5516 [DY]
                'ALL'      => Array(
                    'store_id' => '15201490001',
                    // 'post_url' => 'https://www.fastmoney911.com/leads.aspx',
                    'user_id' => 'partner2',
                    'password' => 'password',
                    'marketing_code' => '508',
                    'pdloanrcvdfrom' => 'Partner Weekly',
                    'loan_amount' => '400',
                    'finance_charge' => '120',
                    // 'send_requested_effective_date_today' => TRUE,
                    // 'send_requested_effective_date' => TRUE
                    'state_req' => '4',
                ),
                'LOCAL'    => Array(
                ),
                'RC'       => Array(
                ),
                'LIVE'     => Array(
                    'post_url' => 'https://www.instantcash911.com/lrs/leads.aspx', // GForge 6413 [BA]
                ),
            ),
            'exc2'    => Array( // GForge #5516 [DY]
                'ALL'      => Array(
                    'store_id' => '15201490001',
                    // 'post_url' => 'https://www.fastmoney911.com/leads.aspx',
                    'user_id' => 'partner2',
                    'password' => 'password',
                    'marketing_code' => '508',
                    'pdloanrcvdfrom' => 'Partner Weekly',
                    'loan_amount' => '400',
                    'finance_charge' => '120',
                    // 'send_requested_effective_date_today' => TRUE,
                    // 'send_requested_effective_date' => TRUE
                    'state_req' => '4',
                ),
                'LOCAL'    => Array(
                ),
                'RC'       => Array(
                ),
                'LIVE'     => Array(
                    'post_url' => 'https://www.instantcash911.com/lrs/leads.aspx', // GForge 6413 [BA]
                ),
            ),    
			'exc3'    => Array( // GForge #5686 [DY]
				'ALL'      => Array(
					'store_id' => '15201490001',
					// 'post_url' => 'https://www.fastmoney911.com/leads.aspx',
					'user_id' => '53002402',
					'password' => 'password',
					'marketing_code' => '513',
					'pdloanrcvdfrom' => 'Partner 3',
					'loan_amount' => '400',
					'finance_charge' => '120',
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					'state_req' => '4',
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.instantcash911.com/lrs/leads.aspx', // GForge 6413 [BA]
				),
			),
			'exc4'    => Array( // GForge #5686 [DY]
				'ALL'      => Array(
					'store_id' => '15201490001',
					// 'post_url' => 'https://www.fastmoney911.com/leads.aspx',
					'user_id' => '53002402',
					'password' => 'password',
					'marketing_code' => '513',
					'pdloanrcvdfrom' => 'Partner 3',
					'loan_amount' => '400',
					'finance_charge' => '120',
					// 'send_requested_effective_date_today' => TRUE,
					// 'send_requested_effective_date' => TRUE
					'state_req' => '4',
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.instantcash911.com/lrs/leads.aspx', // GForge 6413 [BA]
				),
			),
			'ace_t1'    => Array(
				'ALL'      => Array(
					'store_id' => '27900480002',
					'user_id' => 'partner',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '400',
					'finance_charge' => '90',
					// 'send_requested_effective_date_today' => TRUE,
					'state_req' => '8',
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.aceasap.com/payday/leads.aspx',
				),
			),
			'ace_t1b'    => Array(
				'ALL'      => Array(
					'store_id' => '27900480001',
					'user_id' => 'partner',
					'password' => 'password',
					'marketing_code' => '253',
					'pdloanrcvdfrom' => 'Partner Weekly',
					'loan_amount' => '300',
					'finance_charge' => '90',
					// 'send_requested_effective_date_today' => TRUE,
					'state_req' => '8',
				),
				'LOCAL'    => Array(
				),
				'RC'       => Array(
				),
				'LIVE'     => Array(
					'post_url' => 'https://www.aceasap.com/payday/leads.aspx',
				),
			),
		);

	/**
	 * Add for Mantis #11965 [DY].
	 *
	 * @param array $lead_data
	 * @param string $mode
	 * @param string $property_short
	 */
	public function __construct(&$lead_data, $mode, $property_short)
	{
		parent::__construct($lead_data, $mode, $property_short);

		if (strcasecmp($this->property_short, 'mpt') === 0) {

			switch (strtoupper($this->lead_data['data']['home_state'])) {
				case 'AL':
					$store_id = '28501000001';
					$marketing_code = '253';
					break;
				case 'LA':
					$store_id = '28504000001';
					$marketing_code = '253';
					break;
				case 'MN':
					$store_id = '28506000001';
					$marketing_code = '359';
					break;
				case 'MO':
					$store_id = '28507000001';
					$marketing_code = '253';
					break;
				case 'MT':
					$store_id = '28508000001';
					$marketing_code = '253';
					break;
				case 'PA':
					$store_id = '28514000001';
					$marketing_code = '359';
					break;
				case 'RI':
					$store_id = '28515000001';
					$marketing_code = '359';
					break;
				case 'SD':
					$store_id = '28509000001';
					$marketing_code = '253';
					break;
				case 'UT':
					$store_id = '28511000001';
					$marketing_code = '359';
					break;
				case 'WI':
					$store_id = '28512000001';
					$marketing_code = '253';
					break;
				case 'WY':
					$store_id = '28513000001';
					$marketing_code = '253';
					break;
				default:
					$store_id = '';
					break;
			}

			$this->rpc_params['mpt']['ALL']['store_id'] = $store_id;
			$this->rpc_params['mpt']['ALL']['marketing_code'] = $marketing_code;

		}
	}

	// This function returns an integer (2 - 11) of the category value

	public function Get_Gross_Income_Category($yearlyPay)
	{

		$gross_income_cat = 0;

		if($yearlyPay <= 15000)
		{
			$gross_income_cat = 2;
		}
		else if ($yearlyPay > 15000 && $yearlyPay <= 20000)
		{
			$gross_income_cat = 3;
		}
		else if ($yearlyPay > 20000 && $yearlyPay <= 25000)
		{
			$gross_income_cat = 4;
		}
		else if ($yearlyPay > 25000 && $yearlyPay <= 30000)
		{
			$gross_income_cat = 5;
		}
		else if ($yearlyPay > 30000 && $yearlyPay <= 35000)
		{
			$gross_income_cat = 6;
		}
		else if ($yearlyPay > 35000 && $yearlyPay <= 40000)
		{
			$gross_income_cat = 7;
		}
		else if ($yearlyPay > 40000 && $yearlyPay <= 45000)
		{
			$gross_income_cat = 8;
		}
		else if ($yearlyPay > 45000 && $yearlyPay <= 50000)
		{
			$gross_income_cat = 9;
		}
		else if ($yearlyPay > 50000 && $yearlyPay <= 55000)
		{
			$gross_income_cat = 10;
		}
		else
		{
			$gross_income_cat = 11;
		}

		return $gross_income_cat;
	}


	protected $static_thankyou = FALSE;



	public function Generate_Fields(&$lead_data, &$params)
	{

		$payperiod = array(
			'WEEKLY' => 'W',
			'BI_WEEKLY' => 'B',
			'TWICE_MONTHLY' => 'S',
			'MONTHLY' => 'M',
		);

		$frequency = '';
		if (isset($lead_data['data']['paydate']['frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate']['frequency']];
			$pay_period_frequency = $lead_data['data']['paydate']['frequency'];
		}
		if (!$frequency && isset($lead_data['data']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['income_frequency']];
			$pay_period_frequency = $lead_data['data']['income_frequency'];
		}
		if (!$frequency && isset($lead_data['data']['paydate_model']['income_frequency']))
		{
			$frequency = $payperiod[$lead_data['data']['paydate_model']['income_frequency']];
			$pay_period_frequency = $lead_data['data']['paydate_model']['income_frequency'];
		}

		$qualify = new Qualify_2(NULL);
		$paycheck_net = round($qualify->Calculate_Monthly_Net($pay_period_frequency, $lead_data['data']['income_monthly_net']),0);

		$income_source = $lead_data['data']['income_type'] == 'EMPLOYMENT' ? "P" :"O" ;
		$income_type = $lead_data['data']['income_direct_deposit']=='TRUE'?'D':'P';

		list($y3, $m3, $d3) = explode("-", $lead_data['data']['paydate_model']['last_pay_date']);
		$lastpaydate = date("m/d/Y", mktime("0","0","0",$m3,$d3,$y3));

		list($y1, $m1, $d1) = explode("-", $lead_data['data']['paydates'][0]);
		$paydate1 = date("m/d/Y", mktime("0","0","0",$m1,$d1,$y1));

		list($y2, $m2, $d2) = explode("-", $lead_data['data']['paydates'][1]);
		$paydate2 = date("m/d/Y", mktime("0","0","0",$m2,$d2,$y2));

		if ($frequency == 'S')
		{
			$semi_monthly1 = $paydate1;
			$semi_monthly2 = $paydate2;
		}

		$custaccttype = $lead_data['data']['bank_account_type'] == 'CHECKING' ? "C" : "S";

		$issued_state = ($lead_data['data']['state_issued_id']) ? $lead_data['data']['state_issued_id'] : $lead_data['data']['home_state'];


		// Calculate Gross Income Category
		$calc_gross_income = $this->Get_Gross_Income_Category( $lead_data['data']['income_monthly_net'] * 12);


		$coreg_xml = false;

		$dom = new DOMDocument('1.0','utf-8');
		$root_element = $dom->createElement('EXTPOSTTRANSACTION');
		$dom->appendChild($root_element);

		$stl_element = $dom->createElement('STLTRANSACTIONINFO');
		$root_element->appendChild($stl_element);

		$ext_element = $dom->createElement('EXTTRANSACTIONDATA');
		$root_element->appendChild($ext_element);

		// Populate stl_element
		$s_element[] = $dom->createElement('TRANSACTIONTYPE', '100');
		$s_element[] = $dom->createElement('USERID', $params['user_id']);
		$s_element[] = $dom->createElement('PASSWORD', $params['password']);
		$s_element[] = $dom->createElement('STOREID', $params['store_id']);
		$s_element[] = $dom->createElement('STLTRANSACTIONID');

		if($params['send_promo_id'] == TRUE)
		{
			$s_element[] = $dom->createElement('EXTRANSACTIONID', $lead_data['data']['promo_id']);
		}
		else
		{
			$s_element[] = $dom->createElement('EXTTRANSACTIONID');
		}

		$s_element[] = $dom->createElement('MESSAGENUMBER');
		$s_element[] = $dom->createElement('MESSAGEDESC');
		$s_element[] = $dom->createElement('STLTRANSACTIONDATE');




		foreach ($s_element as $s)
		{
			$stl_element->appendChild($s);
		}

		// Populate ext_element
		//Customer Element
		$customer_element = $dom->createElement('CUSTOMER');
		$ext_element->appendChild($customer_element);

		$c_element[] = $dom->createElement('CUSTSSN',$lead_data['data']['social_security_number']);
		$c_element[] = $dom->createElement('CUSTFNAME',$lead_data['data']['name_first']);
		$c_element[] = $dom->createElement('CUSTMNAME');
		$c_element[] = $dom->createElement('CUSTLNAME',$lead_data['data']['name_last']);
		$c_element[] = $dom->createElement('CUSTADD1',$lead_data['data']['home_street'] .' '. $lead_data['data']['home_unit']);
		$c_element[] = $dom->createElement('CUSTADD2');
		$c_element[] = $dom->createElement('CUSTCITY',$lead_data['data']['home_city']);
		$c_element[] = $dom->createElement('CUSTSTATE',$lead_data['data']['home_state']);
		$c_element[] = $dom->createElement('CUSTZIP',$lead_data['data']['home_zip']);
		$c_element[] = $dom->createElement('CUSTZIP4');
		$c_element[] = $dom->createElement('CUSTHOMEPHONE',$lead_data['data']['phone_home']);
		$c_element[] = $dom->createElement('CUSTMOBILEPHONE',$lead_data['data']['phone_cell']);
		$c_element[] = $dom->createElement('CUSTMSGPHONE');
		$c_element[] = $dom->createElement('CUSTFAX');
		$c_element[] = $dom->createElement('CUSTWORKPHONE',$lead_data['data']['phone_work']);
		$c_element[] = $dom->createElement('CUSTWORKPHONEEXT',$lead_data['data']['ext_work']);
		$c_element[] = $dom->createElement('CUSTEMAIL',$lead_data['data']['email_primary']);
		$c_element[] = $dom->createElement('CUSTMOMMAIDNAME');
		$c_element[] = $dom->createElement('CUSTDOB',$lead_data['data']['dob']);
		$c_element[] = $dom->createElement('CUST18YRSOLD','Y');
		$c_element[] = $dom->createElement('CUSTDLSTATE',$issued_state);
		$c_element[] = $dom->createElement('CUSTDLNO',$lead_data['data']['state_id_number']);
		$c_element[] = $dom->createElement('UTILBILLVERIFIED');
		$c_element[] = $dom->createElement('YRSATCURRADD');
		$c_element[] = $dom->createElement('MNTHSATCURRADD');
		$c_element[] = $dom->createElement('YRSATPREVADD');
		$c_element[] = $dom->createElement('MNTHSATPREVADD');
		$c_element[] = $dom->createElement('LANDLORDNAME');
		$c_element[] = $dom->createElement('LANDLORDPHONE');
		$c_element[] = $dom->createElement('HOMESTATUS');
		$c_element[] = $dom->createElement('DISTFRMSTORE');
		$c_element[] = $dom->createElement('CUSTEDUCATION');
		$c_element[] = $dom->createElement('GROSSINCOME',$calc_gross_income); // This is the Gross Income Category (2-11)
		$c_element[] = $dom->createElement('MKTCODES',$params['marketing_code']);

		//This flag is for vendors who want pdloanrcvdfrom to be the promo id
		// as requested in mantis 9532,10089 & GForge 3899.
		if($params['pdloanrcvdfrom_is_promo_id'] == true)
		{
			$c_element[] = $dom->createElement('PDLOANRCVDFROM',$lead_data['config']->promo_id);
		}
		else if ($params['pdloanrcvdfrom_is_part01_promo_id'] == true)
		{//Mantis 10089 - EGF wants PART01 and promoID returned concatenated together. [MJ]
			$c_element[] = $dom->createElement('PDLOANRCVDFROM',"PART01".$lead_data['config']->promo_id);
		}
		else if ($params['pdloanrcvdfrom_is_sellingsource_promo_id'] == true)
		{   //GForge 3059 [MJ], GForge 3832 [DY]
			$c_element[] = $dom->createElement('PDLOANRCVDFROM',"SS" . $lead_data['config']->promo_id);
		}
		else
		{
			$c_element[] = $dom->createElement('PDLOANRCVDFROM',$params['pdloanrcvdfrom']);
		}

		if($params['idverified_is_military'] == true)//Added for Mantis 12093 [MJ]
		{
			$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			$c_element[] = $dom->createElement('IDVERIFIED',$is_military);
		}
		else 
		{
			$c_element[] = $dom->createElement('IDVERIFIED',$params['id_verified']); // Should be "Y" - was set to N	
		}
		
		$c_element[] = $dom->createElement('PREVIOUSCUST','N'); // ?? Previous Customer - how do you get this?
		$c_element[] = $dom->createElement('SPOUSESSN');
		$c_element[] = $dom->createElement('SPOUSEFNAME');
		$c_element[] = $dom->createElement('SPOUSEMNAME');
		$c_element[] = $dom->createElement('SPOUSELNAME');
		$c_element[] = $dom->createElement('SPOUSEDOB');
		$c_element[] = $dom->createElement('SPOUSEPHONE');
		$c_element[] = $dom->createElement('SPOUSEEMPLOYER');
		$c_element[] = $dom->createElement('SPOUSEWORKPHONE');
		$c_element[] = $dom->createElement('SPOUSEWORKPHONEEXT');
		$c_element[] = $dom->createElement('AUTOYEAR');
		$c_element[] = $dom->createElement('AUTOMAKE');
		$c_element[] = $dom->createElement('AUTOMODEL');
		$c_element[] = $dom->createElement('AUTOCOLOR');
		$c_element[] = $dom->createElement('AUTOTAG');
		$c_element[] = $dom->createElement('AUTOVIN');
		$c_element[] = $dom->createElement('AUTOVALUE');
		$c_element[] = $dom->createElement('AUTONOTE');
			
		if (in_array(strtolower($this->property_short), array('zip1', 'zip_t1'))) { // Mantis #11993 [DY], GForge #4166 [DY]
			$is_minitary = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			$c_element[] = $dom->createElement('ACTIVEMILITARY', $is_minitary);
		}
		else if (in_array(strtolower($this->property_short), array('lls','lls_1','icd_ut'))) { // Mantis #12013 [MJ] #12014 [DY]
			$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			$c_element[] = $dom->createElement('ISMILITARY', $is_military);
		}
		//Little Loan Shoppe additional military question ISCLAIMAINTMILITARY, will be the same as ISMILITARY since question answeres both.
		if (in_array(strtolower($this->property_short), array('lls','lls_1'))) { // GForge 3484 [MJ]
			$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			$c_element[] = $dom->createElement('ISCLAIMAINTMILITARY', $is_military);
		}
		
		// [#6811] BBx - Second Wallet - Military Field [TF] (updated)
		if (in_array(strtolower($this->property_short), array('sdw'))) 
		{ 
			// now: ISCLAIMANTMILITARY old: ISCLAIMAINTMILITARY 
			$is_military = (strcasecmp($lead_data['data']['military'], 'TRUE') === 0) ? 'Y' : 'N';
			$c_element[] = $dom->createElement('ISMILITARY', $is_military);
			$c_element[] = $dom->createElement('ISCLAIMANTMILITARY', $is_military);
		}
		
		foreach ($c_element as $c)
		{
			$customer_element->appendChild($c);
		}

		//Reference1
		$reference1_element = $dom->createElement('REFERENCE');
		$ext_element->appendChild($reference1_element);
		list($first,$last) = split(" ", $lead_data['data']['ref_01_name_full']);

		$r1_element[] = $dom->createElement('REFFNAME',$first);
		$r1_element[] = $dom->createElement('REFMNAME');
		$r1_element[] = $dom->createElement('REFLNAME',$last);
		$r1_element[] = $dom->createElement('REFADD1');
		$r1_element[] = $dom->createElement('REFADD2');
		$r1_element[] = $dom->createElement('REFCITY');
		$r1_element[] = $dom->createElement('REFSTATE');
		$r1_element[] = $dom->createElement('REFZIP');
		$r1_element[] = $dom->createElement('REFZIP4');
		$r1_element[] = $dom->createElement('REFHOMEPHONE',$lead_data['data']['ref_01_phone_home']);
		$r1_element[] = $dom->createElement('REFMOBILEPHONE');
		$r1_element[] = $dom->createElement('REFMSGPHONE');
		$r1_element[] = $dom->createElement('REFFAX');
		$r1_element[] = $dom->createElement('REFWORKPHONE');
		$r1_element[] = $dom->createElement('REFWORKPHONEEXT');
		$r1_element[] = $dom->createElement('REFEMAIL');
		$r1_element[] = $dom->createElement('REFRELATION',$lead_data['data']['ref_01_relationship']);
		$r1_element[] = $dom->createElement('REFACTIVEFLAG','P');

		foreach ($r1_element as $r1)
		{
			$reference1_element->appendChild($r1);
		}

		//Reference2
		$reference2_element = $dom->createElement('REFERENCE');
		$ext_element->appendChild($reference2_element);
		list($first,$last) = split(" ", $lead_data['data']['ref_02_name_full']);

		$r2_element[] = $dom->createElement('REFFNAME',$first);
		$r2_element[] = $dom->createElement('REFMNAME');
		$r2_element[] = $dom->createElement('REFLNAME',$last);
		$r2_element[] = $dom->createElement('REFADD1');
		$r2_element[] = $dom->createElement('REFADD2');
		$r2_element[] = $dom->createElement('REFCITY');
		$r2_element[] = $dom->createElement('REFSTATE');
		$r2_element[] = $dom->createElement('REFZIP');
		$r2_element[] = $dom->createElement('REFZIP4');
		$r2_element[] = $dom->createElement('REFHOMEPHONE',$lead_data['data']['ref_02_phone_home']);
		$r2_element[] = $dom->createElement('REFMOBILEPHONE');
		$r2_element[] = $dom->createElement('REFMSGPHONE');
		$r2_element[] = $dom->createElement('REFFAX');
		$r2_element[] = $dom->createElement('REFWORKPHONE');
		$r2_element[] = $dom->createElement('REFWORKPHONEEXT');
		$r2_element[] = $dom->createElement('REFEMAIL');
		$r2_element[] = $dom->createElement('REFRELATION',$lead_data['data']['ref_02_relationship']);
		$r2_element[] = $dom->createElement('REFACTIVEFLAG','1');

		foreach ($r2_element as $r2)
		{
			$reference2_element->appendChild($r2);
		}

		//Bank
		$bank_element = $dom->createElement('BANK');
		$ext_element->appendChild($bank_element);

		$b_element[] = $dom->createElement('CUSTABANO',$lead_data['data']['bank_aba']);
		$b_element[] = $dom->createElement('CUSTBANKNAME',$lead_data['data']['bank_name']);
		$b_element[] = $dom->createElement('CUSTACCTNO',$lead_data['data']['bank_account']);
		$b_element[] = $dom->createElement('CUSTACCTTYPE',$custaccttype);
		$b_element[] = $dom->createElement('CUSTBANKADD1');
		$b_element[] = $dom->createElement('CUSTBANKADD2');
		$b_element[] = $dom->createElement('CUSTBANKCITY');
		$b_element[] = $dom->createElement('CUSTBANKSTATE');
		$b_element[] = $dom->createElement('CUSTBANKZIP');
		$b_element[] = $dom->createElement('CUSTBANKZIP4');
		$b_element[] = $dom->createElement('CUSTBANKPHONE');
		$b_element[] = $dom->createElement('CUSTBANKFAX');
		$b_element[] = $dom->createElement('VOIDEDCHECKNO');
		$b_element[] = $dom->createElement('ACCTOPENDATE');
		$b_element[] = $dom->createElement('ACCTEXPDATE');
		$b_element[] = $dom->createElement('ACCT30DAYSOLD');
		$b_element[] = $dom->createElement('RECBANKSTMT','N');
		$b_element[] = $dom->createElement('NOOFNSF','0');
		$b_element[] = $dom->createElement('NOOFTRAN','0');
		$b_element[] = $dom->createElement('ENDINGSTMTBAL','0');
		$b_element[] = $dom->createElement('BANKACTIVEFLAG','P');

		foreach ($b_element as $b)
		{
			$bank_element->appendChild($b);
		}

		//Employer
		$employer_element = $dom->createElement('EMPLOYER');
		$ext_element->appendChild($employer_element);

		$e_element[] = $dom->createElement('TYPEOFINCOME',$income_source);
		$e_element[] = $dom->createElement('EMPNAME',$lead_data['data']['employer_name']);
		$e_element[] = $dom->createElement('EMPADD1');
		$e_element[] = $dom->createElement('EMPADD2');
		$e_element[] = $dom->createElement('EMPCITY');
		$e_element[] = $dom->createElement('EMPSTATE');
		$e_element[] = $dom->createElement('EMPZIP');
		$e_element[] = $dom->createElement('EMPZIP4');
		$e_element[] = $dom->createElement('EMPPHONE',$lead_data['data']['phone_work']);
		$e_element[] = $dom->createElement('EMPPHONEEXT',$lead_data['data']['ext_work']);
		$e_element[] = $dom->createElement('EMPFAX');
		$e_element[] = $dom->createElement('CONTACTNAME');
		$e_element[] = $dom->createElement('CONTACTPHONE');
		$e_element[] = $dom->createElement('CONTACTPHONEEXT');
		$e_element[] = $dom->createElement('CONTACTFAX');
		$e_element[] = $dom->createElement('BENEFITSTARTDATE',$params['default_benefit_start']);
		$e_element[] = $dom->createElement('BENEFITENDDATE');
		$e_element[] = $dom->createElement('EMPLTYPE','F');
		$e_element[] = $dom->createElement('JOBTITLE');
		$e_element[] = $dom->createElement('WORKSHIFT', 'F');  // Work Shift - I don't know if we gather that information
		$e_element[] = $dom->createElement('AVGSALARY',$paycheck_net);
		$e_element[] = $dom->createElement('TYPEOFPAYROLL',$income_type);
		$e_element[] = $dom->createElement('PERIODICITY',$frequency);
		$e_element[] = $dom->createElement('INCOMEVERIFIED','Y');
		$e_element[] = $dom->createElement('PAYGARNISHMENT',0); //?? I don't think we gather that information
		$e_element[] = $dom->createElement('PAYBANKRUPTCY',0);//?? I don't think we gather that information
		$e_element[] = $dom->createElement('LASTPAYDATE',$lastpaydate);
		$e_element[] = $dom->createElement('NEXTPAYDATE',$paydate1);
		$e_element[] = $dom->createElement('SECONDPAYDATE',$paydate2);
		$e_element[] = $dom->createElement('PAYROLLACTIVEFLAG','P');
		$e_element[] = $dom->createElement('NEXT_PAY_FREE_FORM');
		$e_element[] = $dom->createElement('SEMIMONTHLY_1ST_PAYDAY');
		$e_element[] = $dom->createElement('SEMIMONTHLY_2ND_PAYDAY');

		foreach ($e_element as $e)
		{
			$employer_element->appendChild($e);
		}

		//Applicationpayperiod
		$application_element = $dom->createElement('APPLICATION');
		$ext_element->appendChild($application_element);

		/**
		 * For vendors that supply leads to multiple states, you can specify
		 * individual state_reqs for them with this.
		 */
		if (!empty($params['state_req_array']))
		{
			$state = strtoupper($lead_data['data']['home_state']);
			$params['state_req'] = $params['state_req_array']['default'];
			
			$params['state_req'] = (isset($params['state_req_array'][$state]))
									? $params['state_req_array'][$state]
									: $params['state_req_array']['default'];
		}

		//Change paydate to later paydate if under state requirements
		if ($params['state_req'])
		{
			$req_days = $params['state_req'] + 1; // Adding 1 day to calculate from 'tommorrow'
			$req = mktime(0, 0, 0, date("m"), date("d") + $req_days, date("Y"));

			if (strtotime($paydate1) < $req)
			{
				if (strtotime($paydate2) < $req)
				{
					$paydate1 = date("m/d/Y", strtotime($lead_data['data']['paydates'][2]));
				}
				else
				{
					$paydate1 = $paydate2;
				}
			}
		}

		//Calc requested effective date
		if($params['send_requested_effective_date_today'])
		{
			$red = date("m/d/Y");
		}
		else
		{
			$red = $this->Move_Date(date("m/d/Y",time() + 86400), $params['weekends']);
		}
		// This was added for UFPD
		if($params['send_dismode_field'])
		{
			$a_element[] = $dom->createElement('DISMODE','A');  // DISBURSEMENT MODE should be ACH (Checking Account)
		}

		$a_element[] = $dom->createElement('REQUESTEDAMOUNT',$params['loan_amount']);
		$a_element[] = $dom->createElement('REQUESTEDDUEDATE',$paydate1);
		if($params['send_requested_effective_date']){
			$a_element[] = $dom->createElement('REQUESTEDEFFECTIVEDATE',$red);
		} else {
			$a_element[] = $dom->createElement('REQUESTEDEFFECTIVEDATE',"");
		}
		$a_element[] = $dom->createElement('FINANCECHARGE',$params['finance_charge']);
		$a_element[] = $dom->createElement('APPLICATIONDATE',date("m/d/Y h:i:s A"));
		$a_element[] = $dom->createElement('LOANTYPE','S');
		$a_element[] = $dom->createElement('CLNVLOANREP');

		foreach ($a_element as $a)
		{
			$application_element->appendChild($a);
		}

		$fields_xml = $dom->saveXML();
	
		return $fields_xml;
	}


	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		if (!strlen($data_received))
		{
			$result->Empty_Response();
			$result->Set_Vendor_Decision('TIMEOUT');
		}
		elseif (preg_match ('/<SUCCESS>1<\/SUCCESS>/i', $data_received, $d))
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$m = array();
			if(preg_match('/<REASONDESC>([^<]+)<\/REASONDESC>/i', $data_received, $m))
			{
				$result->Set_Vendor_Reason(substr($m[1],0,255));
			}
			elseif(preg_match('/<MESSAGEDESC>([^<]+)<\/MESSAGEDESC>/i', $data_received, $m))
			{
				$result->Set_Vendor_Reason(substr($m[1],0,255));
			}

			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}

	/**
	 * @desc A PHP magic function. See http://www.php.net/manual/en/language.oop5.magic.php
	 */
	public function __toString()
	{
		return "Vendor Post Implementation [Trandotcom loan processing system]";
	}

	public function Thank_You_Content(&$data_received)
	{
		preg_match('/<APPLICATIONURL>(.*)<\/APPLICATIONURL>/i', $data_received, $m);
		$url = trim($m[1]);
		$content = parent::Generic_Thank_You_Page($url, self::REDIRECT);
		return($content);
	}

}
?>
