<?php
require_once('security.8.php');
define('PASSWORD_ENCRYPTION', 'ENCRYPT');
/**
 * CHANGES FROM ANDREW'S SKELETON:
 * addLoanAction now just takes a name, since I assume he's looking for an insertion into the loan history rather than literally inserting a new loan action
 * changed 'name' to 'name_short' to reduce ambiguity
 *
 * QUESTIONS
 */
class ECash_API_Insertion
{
	protected $application;
	protected $ecash_application_id;
	protected $models = array();
	protected $dependency_models = array();

	/**
	 * The constructor is where any name_short to id conversions should take place
	 * and any other prep work along those lines
	 * 
	 * @param array $data General application data
	 */
	public function __construct(array $data) 
	{
		$db = ECash_Config::getMasterDbConnection();
		$db->beginTransaction();
		try{
			$this->application = ECash::getFactory()->getModel('Application');
			//$application->fromDbRow($this->data);
			foreach($data as $key=>$value) {
				try{
					$this->application->{$key} = $value;
				} catch(Exception $e) {
					continue;
				}
			}
			
			$this->convert_dependent_columns();

			$this->application->insert();
			$this->ecash_application_id = $this->application->application_id;
		}
		catch(Exception $e)
		{
			$db->rollBack();
			throw $e;
		}
	}
	/**
	 * Converts dependent columns from string representations to reference id's
	 *
	 */
	private function convert_dependent_columns()
	{
			//fix agent id
			if(!is_numeric($this->application->modifying_agent_id))
			{
				$agent = ECash::getFactory()->getModel('Agent');
				$agent->loadBy(array('login' => $this->application->modifying_agent_id));
				if(empty($agent->agent_id))
				{
					$this->application->modifying_agent_id = 0;
				}
				else
				{
					$this->application->modifying_agent_id = $agent->agent_id;
				}
				
			}
			
			if(!is_numeric($this->application->agent_id))
			{
				$agent = ECash::getFactory()->getModel('Agent');
				$agent->loadBy(array('login' => $this->application->agent_id));
				if(empty($agent->agent_id))
				{
					$this->application->agent_id = 0;
				}
				else
				{
					$this->application->agent_id = $agent->agent_id;
				}
				
			}
			//fix company id
			if(!is_numeric($this->application->company_id))
			{
				$company = ECash::getFactory()->getModel('Company');
				$company->loadBy(array('name_short' => $this->application->company_id));
				if(empty($company->company_id))
				{
					throw exception('Invalid Company');
				}
				else
				{
					$this->application->company_id = $company->company_id;
				}
			}
			
			//fix application status id
			//@todo: figure out what olp will be sending to represent status
			if(!is_numeric($this->application->application_status_id))
			{
				$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
				$this->application->application_status_id = $status_list->toId($this->application->application_status_id);
			}
		
		
	}
	/**
	 * Add a campaign info record
	 *
	 * @param int $site_id
	 * @param int $promo_id
	 * @param string $sub_code
	 * @param int $reservation
	 * @param string $date
	 */
	public function addCampaign($promo_id, $sub_code, $reservation, $date = NULL) 
	{
		/* @var $campaign ECash_Models_CampaignInfo */
		$campaign = ECash::getFactory()->getModel('CampaignInfo');
		$campaign->date_created = is_null($date) ? date('Y-m-d H:i:s') : $date;
		$campaign->company_id = $this->application->company_id;
		if(empty($this->application->enterprise_site_id))
		{
			$o = new DB_Models_ColumnObserver_1($this->dependency_models['site'], 'site_id');
			$o->addTarget($campaign);
		}
		else
		{
			$campaign->site_id = $this->application->enterprise_site_id;
		}
		
		$campaign->promo_id = $promo_id;
		$campaign->promo_sub_code = $sub_code;
		$campaign->reservation_id = $reservation;
		$this->models[] = $campaign;
	}

	/**
	 * Add a card record
	 *
	 * @param unknown_type $number
	 * @param unknown_type $ref_id
	 * @param unknown_type $bin
	 * @param unknown_type $stock
	 * @param unknown_type $account
	 */
	public function addCard($number, $ref_id, $bin, $stock, $account) 
	{
		return;
	}
	/**
	 * Add a site record
	 *
	 * @param string $name
	 * @param string $license_key
	 */
	public function addSite($name, $license_key)
	{
		
		$site = ECash::getFactory()->getModel('Site');
		$site->loadBy(array('license_key' => $license_key));
		if(empty($site->site_id))
		{
			$site->date_modified = time();
			$site->date_created = time();		
			$site->active_status = 'active';
			$site->name = $name;
			$site->license_key = $license_key;
					
			$o = new DB_Models_ColumnObserver_1($site, 'site_id');
			$o->addTarget($this->application, 'enterprise_site_id');
			
			$this->dependency_models['site'] = $site;
		}
		else
		{
			$this->application->enterprise_site_id = $site->site_id;	
		}
		
	}
    /**
	 * Add a Customer record
	 * 
	 * Check for existing customer id based on ssn and company, or company/dob/email
	 * If does not exist create new one
	 *
	 *@return array(login => '', password => '')
	 */
    public function addCustomer()
    {
		$customer = ECash::getFactory()->getModel('Customer');
		
		$row = $customer->loadBySSN($this->application->company_id, $this->application->ssn);
		if(empty($row->customer_id))
		{
			$check_application = ECash::getFactory()->getModel('Application');
			$check_application->loadBy(array('email' => $this->application->email, 'dob' => $this->application->dob, 'company_id' => $this->application->company_id));
			
			if(empty($check_application->customer_id))
			{
			 	$username = strtoupper($this->application->name_first{0} . $this->application->name_last . '_');
				$username = preg_replace('/[^a-zA-Z0-9\-_]+/', '', $username);	
				$username .= $customer->getUsernameCount($username) + 1;
				
				// create a random password
				$clear_pass = 'cash' . substr(microtime(), - 3);
				$password = Security_8::Encrypt_Password($clear_pass);
			
				$customer->date_created = time();	
			  	$customer->company_id = $this->application->company_id;
			  	$customer->ssn = $this->application->ssn;
			  	$customer->modifying_agent_id = $this->application->modifying_agent_id;
			  	$customer->login = $username;
			  	$customer->password = $password;
			
				$o = new DB_Models_ColumnObserver_1($customer, 'customer_id');
				$o->addTarget($this->application);
			
				$this->dependency_models[] = $customer;
				return array('username' => $username, 'password' => $clear_pass);				
			}
			else
			{
				$this->application->customer_id = $check_application->customer_id;
				$customer->loadBy(array('customer_id' => $check_application->customer_id));
				return array('username' => $customer->login, 'password' => Security_8::Decrypt_Password($customer->password));	
			}
		}
		else
		{
			$this->application->customer_id = $row->customer_id;
			return array('username' => $row->login, 'password' => Security_8::Decrypt_Password($row->password));	
		}
    }
	/**
	 * Add a status history record
	 *
	 * @param string $date
	 * @param string $status_name_short
	 * @param int $agent_id
	 */
	public function addStatusHistory($date, $status_string, $agent_id = NULL) 
	{
		/* @var $history ECash_Models_StatusHistory */
		$history = ECash::getFactory()->getModel('StatusHistory');
		$history->date_created = date("Y-m-d H:i:s", $date);
		$history->company_id = $this->application->company_id;
		$history->agent_id = is_null($agent_id) ? $this->application->modifying_agent_id : $agent_id;
		$status_list = ECash::getFactory()->getReferenceList('ApplicationStatusFlat');
		$history->application_status_id = $status_list->toId($status_string);
		$this->models[] = $history;
	}

	/**
	 * Add a personal reference
	 *
	 * @param string $name
	 * @param string $phone
	 * @param string $relationship
	 */
	public function addReference($name, $phone, $relationship) 
	{
		/* @var $reference ECash_Models_PersonalReference */
		$reference = ECash::getFactory()->getModel('PersonalReference');
		$reference->date_created = date('Y-m-d H:i:s');
		$reference->company_id = $this->application->company_id;
		$reference->name_full = $name;
		$reference->phone_home = $phone;
		$reference->relationship = $relationship;
		$this->models[] = $reference;
	}

	/**
	 * Add a bureau inquiry record
	 *
	 * @param string $type
	 * @param string $sent
	 * @param string $received
	 * @param string $trace
	 * @param string $outcome
	 */
	public function addInquiry($type, $sent, $received, $trace, $outcome) 
	{
		/* @var $inquiry ECash_Models_BureauInquiry */
		$inquiry = ECash::getFactory()->getModel('BureauInquiry');
		$inquiry->date_created = date('Y-m-d H:i:s');
		$inquiry->company_id = $this->application->company_id;
		$inquiry->inquiry_type = $type;
		$inquiry->sent_package = $sent;
		$inquiry->received_package = $received;
		$inquiry->outcome = $outcome;
		$inquiry->trace_info = $trace;
		$this->models[] = $inquiry;
	}

	/**
	 * Add a comment
	 *
	 * @param string $comment_text
	 * @param string $type
	 * @param string $source
	 * @param string $visibility
	 */
	public function addComment($comment_text, $type = 'standard', $source = 'system', $visibility = 'public') 
	{
		/* @var $comment ECash_Models_Comment */
		$comment = ECash::getFactory()->getModel('Comment'); //ECash_Models_Comment();
		$comment->comment = $comment_text;
		$comment->date_created = date('Y-m-d H:i:s');
		$comment->company_id = $this->application->company_id;
		$comment->source = $source;
		$comment->agent_id = $this->application->modifying_agent_id;
		$comment->type = $type;
		$comment->visibility = $visibility;
		$this->models[] = $comment;
	}

	/**
	 * Add a loan action
	 *
	 * @param string $name_short
	 */
	public function addLoanAction($name_short) 
	{
		//@todo : check for existance of loan action
		$loan_action = ECash::getFactory()->getModel('LoanActions');
		/* @var $loan_action ECash_Models_LoanActionHistory */
		$loan_action_history = ECash::getFactory()->getModel('LoanActionHistory');
		$loan_action->loadBy(array('name_short' => $name_short));
		if(empty($loan_action->loan_action_id))
		{		
			$loan_action->name_short = $name_short;
			$loan_action->description = $name_short;
			$loan_action->status = 'active'; 
			
			$o = new DB_Models_ColumnObserver_1($loan_action, 'loan_action_id');
			$o->addTarget($loan_action_history);
			
			$this->dependency_models[] = $loan_action;
		}
		else
		{
			$loan_action_history->loan_action_id = $loan_action->loan_action_id;
		}
		
		$loan_action_history->date_created = date('Y-m-d H:i:s');
		$loan_action_history->application_status_id = $this->application->application_status_id;
		$loan_action_history->agent_id = $this->application->modifying_agent_id;
		$this->models[] = $loan_action_history;
	}

	/**
	 * Set an application flag
	 *
	 * @param string $name_short
	 */
	public function setFlag($name_short) 
	{
		/* @var $app_flag ECash_Models_ApplicationFlag */
		$app_flag = ECash::getFactory()->getModel('ApplicationFlag');
		$app_flag->date_created = date('Y-m-d H:i:s');
		$app_flag->modifying_agent_id = $this->application->modifying_agent_id;
		$app_flag->active_status = 'active';
		$app_flag->setFlagType($name_short);
		$app_flag->company_id = $this->application->company_id;
		$this->models[] = $app_flag;
	}

	/**
	 * Save all records
	 *
	 * @return application_id
	 */
	public function save() 
	{
		$db = ECash_Config::getMasterDbConnection();
	
		try {
			foreach($this->dependency_models as $model)
			{
				$model->save();
			}
			
			foreach($this->models as $model)
			{
				$model->application_id = $this->application->application_id;
				$model->save();
			}
			$this->application->save();
			$db->commit();
		}
		catch(Exception $e)
		{
			$db->rollBack();
			throw $e;
		}
		$this->ecash_application_id = $this->application->application_id;
		return $this->ecash_application_id;
	}
}

?>
