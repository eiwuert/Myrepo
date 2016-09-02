<?php
require_once('ole_smtp_lib.php');
include_once('cc_validate.php');
define('OLP_COLLECTIONS_EVENT','OLP_COLLECTIONS');
define('PROPERTY_ID',17176);

/**
 * Class to handle Collections website specific functions.
 *
 * @author Brian Feaver
 */
class Collections
{
	private $config;
	private $email_info = array(
		'500fastcash.com' => array(
			'email' => 'customerservice@500fastcash.com',
			'from' => '500FastCash'
		),
		'ameriloan.com' => array(
			'email' => 'customerservice@ameriloan.com',
			'from' => 'Ameriloan'
		),
		'unitedcashloans.com' => array(
			'email' => 'customerservice@unitedcashloans.com',
			'from' => 'United Cash Loans'
		),
		'oneclickcash.com' => array(
			'email' => 'customerservice@oneclickcash.com',
			'from' => 'OneClickCash'
		),
		'usfastcash.com' => array(
			'email' => 'customerservice@usfastcash.com',
			'from' => 'USFastCash'
		),
		'paypinion.com' => array(
			'email' => 'pinion@paypinion.com',
			'from' => 'Pinion'
		),
		'pinionnorth.com' => array(
			'email' => 'collection@pinionnorth.com',
			'from' => 'Pinion North'
		),
		'lendingcashsource.com' => array(
			'email' => 'customerservice@lendingcashsource.com',
			'from' => 'Lending Cash Source'
		),
	);

	/**
	 * Constructor
	 *
	 * @param object $config Site config
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}

	/**
	 * Process collection website form. This function will email the collected info to
	 * CLK and setup the appropriate page to display for the thank you page.
	 *
	 * @return string Which page to display for the thank you.
	 */
	public function Process_Collections()
	{
		// Send off the email
		if(!isset($_SESSION['collections_confirmed']))
		{
			$this->Send_Collections_Email();
			$_SESSION['collections_confirmed'] = 1;
		}

		switch($_SESSION['data']['transaction_type'])
		{
			case 'payment_proposal':
				$next_page = 'collections_thanks_proposal';
				break;
			case 'pay_on_account':
				$next_page = 'collections_thanks_pay';
				break;
			default:
				assert(FALSE);
		}

		// Setup codes for MoneyGram and Western Union and Site Collection Phone Numbers
		/*
			All of this could be put into an array like email info, but since this is already done,
			I didn't go back and redo it all.
		*/
		switch($this->config->site_name)
		{
			case '500fastcash.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4308;
				$_SESSION['data']['mg_company_name'] = '500FastCash';
				$_SESSION['data']['mg_city'] = 'Miami';
				$_SESSION['data']['mg_state'] = 'Oklahoma';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = '500FastCash';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = '500300';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = '500FastCash Phone #: 1-888-339-6669';
				break;
			case 'ameriloan.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4311;
				$_SESSION['data']['mg_company_name'] = 'Ameriloan';
				$_SESSION['data']['mg_city'] = 'Miami';
				$_SESSION['data']['mg_state'] = 'Oklahoma';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'Ameriloan';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'AL100';

				// Collections Phone Number
				$_SESSION['data']['collections_phone_number'] = 'Ameriloan Phone #: 1-800-536-8918';
				break;
			case 'unitedcashloans.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4309;
				$_SESSION['data']['mg_company_name'] = 'UnitedCashLoans';
				$_SESSION['data']['mg_city'] = 'Miami';
				$_SESSION['data']['mg_state'] = 'Oklahoma';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'UnitedCashLoans';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'U200';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = 'United Cash Loans Phone #: 1-800-354-0602';
				break;
			case 'oneclickcash.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4310;
				$_SESSION['data']['mg_company_name'] = 'OneClickCash';
				$_SESSION['data']['mg_city'] = 'Niobrara';
				$_SESSION['data']['mg_state'] = 'Nebraska';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'OneClickCash';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'PC500';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = 'One Click Cash Phone #: 1-800-349-9418';
				break;
			case 'usfastcash.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4307;
				$_SESSION['data']['mg_company_name'] = 'USFastCash';
				$_SESSION['data']['mg_city'] = 'Miami';
				$_SESSION['data']['mg_state'] = 'Oklahoma';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'USFastCash';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'USF400';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = 'US Fast Cash Phone #: 1-800-636-9460';
				break;
			case 'paypinion.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4277;
				$_SESSION['data']['mg_company_name'] = 'Pinion Management';
				$_SESSION['data']['mg_city'] = 'Carson City';
				$_SESSION['data']['mg_state'] = 'Nevada';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'Pinion Mgmt';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'pinion@paypinion.com';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = 'Pinion Phone #: 1-800-430-2740';
				break;
			case 'pinionnorth.com':
				// MoneyGram
				$_SESSION['data']['mg_receive_code'] = 4284;
				$_SESSION['data']['mg_company_name'] = 'Pinion Management North';
				$_SESSION['data']['mg_city'] = 'Albuquerque';
				$_SESSION['data']['mg_state'] = 'New Mexico';

				// Western Union
				$_SESSION['data']['wu_pay_to'] = 'Pinion Mgmt';
				$_SESSION['data']['wu_code_city'] = 'Pinion';
				$_SESSION['data']['wu_state'] = 'Kansas';
				$_SESSION['data']['wu_senders_number'] = 'collection@pinionnorth.com';

				// Collections Phone Number
				$_SESSION['data']['collections_phone'] = 'Pinion North Phone #: 1-800-430-2695';
				break;
			default:
				assert(FALSE);
		}

		return $next_page;
	}

	/**
	 * Sends the Contact Us email to customerservice.
	 */
	public function Send_Contact_Us_Email()
	{
		// Determine who we need to send this to and who it's from

		// Debug - if local or RC, send it to a dev
		if('LIVE' != $this->config->mode)
		{
			//$email = 'brian.feaver@sellingsource.com';
			$email = 'adam.englander@sellingsource.com';
		}
		else
		{
				$email = $this->email_info[$this->config->site_name]['email'];
		}
		if(!empty($email))
		{
			$from = $this->email_info[$this->config->site_name]['from'];
			$from_address = 'no-reply'.substr($email, strpos($email, '@'));
			$data = Array(
					'first_name'    =>    $_SESSION['data']['name_first'],
					'last_name'     =>    $_SESSION['data']['name_last'],
					'address'       =>    $_SESSION['data']['home_street'],
					'city'          =>    $_SESSION['data']['home_city'],
					'state'         =>    $_SESSION['data']['home_state'],
					'zip'           =>    $_SESSION['data']['home_zip'],
					'home_phone'    =>    $_SESSION['data']['phone_home'],
					'best_contact_phone' => $_SESSION['data']['phone_best_contact'],
					'email_address' =>    $_SESSION['data']['email_primary'],
					'account_number'=>    $_SESSION['data']['account_number'],
					'ssn'           =>    $_SESSION['data']['social_security_number'],
					'comments'      =>    $_SESSION['data']['comments'],
					'from_address'  =>   'no-reply'.substr($email,strpos($email,'@')),
					'site_name'     =>    $this->config->site_name,
					'email_primary' =>    $email,
					'email_primary_name' => $email,
					'from_address' => "$from <$from_address>"

				);
			//Add app ID to data for logging if it exists #9836[AE]
			if (isset($_SESSION['application_id']))
			{
				$data['application_id'] = $_SESSION['application_id'];
			}
				
			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);

			try 
			{
				$template = OLP_COLLECTIONS_EVENT;
				$result = $tx->sendMessage('live',$template,
					$data['email_primary'],'',$data);
				if('LIVE' == $this->config->mode)
				{
					$template = 'OLP_COLLECTIONS_CONTACT_US';
					$result = $tx->sendMessage('live',
						$template,
						'crystal@FC500.com','',$data);
				}
			}
			catch (Exception $e)
			{
				throw new Exception(
					"Trendex mail $template failed. ".$e->getMessage());
			}
		}
	}

	/**
	 * Sends the collections email to the appropriate company.
	 */
	private function Send_Collections_Email()
	{
		// Determine our subject
		switch($_SESSION['data']['transaction_type'])
		{
			case 'payment_proposal':
				$subject = 'Your Online Proposal';
				break;
			case 'pay_on_account':
				$subject = 'Your Online Payment';
				break;
		}

		// Determine who we need to send this to and who it's from
		$email = $this->email_info[$this->config->site_name]['email'];
		$from = $this->email_info[$this->config->site_name]['from'];

		if('LIVE' != $this->config->mode)
		{
			$email = 'brian.feaver@sellingsource.com';
		}

		// Format the dates in month/day/year
		$first_payment_date = date('m/d/Y', strtotime($_SESSION['data']['pay_date1']));
		$second_payment_date = (!empty($_SESSION['data']['pay_date2'])) ?
			date('m/d/Y', strtotime($_SESSION['data']['pay_date2'])) : '';
		switch($_SESSION['data']['payment_type'])
		{
			case 'CREDIT_CARD':
				$payment_type = "Credit Card\n";
				switch($_SESSION['data']['card_type'])
				{
					case 'mc':
						$card_type = 'MasterCard';
						break;
					case 'visa':
						$card_type = 'Visa';
						break;
					case 'ax':
						$card_type = 'American Express';
						break;
				}
				$payment_type .= "Card Type: $card_type";
				break;
			case 'MONEY_GRAM':
				$payment_type = "MoneyGram";
				break;
			case 'WESTERN_UNION':
				$payment_type= "Western Union";
				break;
		}
		if(!empty($subject) && !empty($email))
		{
			$data = Array(
				'name_on_file'         => $_SESSION['data']['name_on_file'],
				'account_number'       => $_SESSION['data']['account_number'],
				'ssn'                  => $_SESSION['data']['social_security_number'],
				'current_balance'      => $_SESSION['data']['current_balance'],
				'first_payment_date'   => $first_payment_date,
				'amount_first_payment' => $_SESSION['data']['amount_first_payment'],
				'second_payment_date'  => $second_payment_date,
				'amount_second_payment'=> $_SESSION['data']['amount_second_payment'],
				'first_name'           => $_SESSION['data']['name_first'],
				'last_name'            => $_SESSION['data']['name_last'],
				'address'              => $_SESSION['data']['home_street'],
				'city'                 => $_SESSION['data']['home_city'],
				'state'                => $_SESSION['data']['home_state'],
				'zip'                  => $_SESSION['data']['home_zip'],
				'home_phone'           => $_SESSION['data']['phone_home'],
				'best_contact_phone'   => $_SESSION['data']['phone_best_contact'],
				'email_address'        => $_SESSION['data']['email_primary'],
				'payment_type'         => $payment_type,
				'email_primary'        => $email,
				'email_primary_name'   => $email,
				'site_name'            => $this->config->site_name,
				'company'              => "$from <$from_address>",
				'subject'              => $subject,
 			);
			//Add app ID to data for logging if it exists #9836[AE]
			if (isset($_SESSION['application_id']))
			{
				$data['application_id'] = $_SESSION['application_id'];
			}

 			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);
			try 
			{
				$template = 'OLP_COLLECTIONS_EMAIL';
				$result = $tx->sendMessage('live',$template,
					$data['email_primary'],'',$data);
				if('LIVE' == $this->config->mode)
				{
					$tempalte = 'OLP_COLLECTIONS_EMAIL';
					$data['email_primary'] = 'Crystal@FC500.com';
					$data['email_primary_name'] = 'Crystal Cram';
					$tx->sendMessage('live',$template,$data['email_primary'],'',$data);
				}
					
			}
			catch (Exception $e)
			{
				$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, $this->config->site_name, APPLOG_ROTATE);
				$applog->Write(
					"Trendex mail $template failed. ".
						$e->getMessage());
			}	
		}
	}

	/**
	 * Will validate extra collection data that isn't caught by the usual validation code.
	 *
	 * @param array $data
	 * @return array
	 */
	public function Validate_Collection_Data($data)
	{
		$valid = TRUE;
		$errors = array();

		// Card number validation has been removed as per Mantis 10520

		return array($valid, $errors);
	}
}
?>
