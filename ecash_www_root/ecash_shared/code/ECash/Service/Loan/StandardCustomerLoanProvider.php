<?php

class ECash_Service_Loan_StandardCustomerLoanProvider implements ECash_Service_Loan_ICustomerLoanProvider
{
	/**
	 * @var DB_IConnection_1
	 */
	private $db;

	/**
	 * @var ECash_Factory
	 */
	private $factory;

	/**
	 * @var ECash_Loan_ECashAPIFactory
	 */
	private $api_factory;

	private $company_id;

	private $datecreated_is_timestamp;

	public function __construct(DB_IConnection_1 $db, ECash_Factory $factory, ECash_Service_Loan_IECashAPIFactory $api_factory, $company_id, $datecreated_is_timestamp = false)
	{
		$this->db = $db;
		$this->factory = $factory;
		$this->api_factory = $api_factory;
		$this->company_id = $company_id;
		$this->datecreated_is_timestamp = $datecreated_is_timestamp;
	}

	/**
	 * Finds loans for the given login.
	 * @param $username
	 * @param $password
	 * @param $ecash_api
	 * @return array of loans
	 */
	public function findLoansForCustomer($username, $password)
	{
		$service = $this->factory->getAppClient();

		$applications = $service->applicationSearch(
			array(
				array(
					'searchCriteria' => $username,
					'field' => 'aa.login',
					'strategy' => 'is',
				),
				array(
					'searchCriteria' => $password,
					'field' => 'aa.password',
					'strategy' => 'is',
				),
			),
			100
		);

		$loans = array();

		foreach ($applications as $app)
		{
			$ecash_api = $this->api_factory->createECashAPI($app->application_id);

			$date = strtotime($app->date_created);

			$loans[] = array(
				'application_id' => $app->application_id,
				'status' => $app->application_status_name,
				'date_created' => date('c', $date),
				'balance' => (float)$ecash_api->Get_Payoff_Amount(),
			);
		}

		return $loans;
	}
}
