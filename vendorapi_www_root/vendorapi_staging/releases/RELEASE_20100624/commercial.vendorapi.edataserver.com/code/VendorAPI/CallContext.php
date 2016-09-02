<?php

/**
 * The call context for vendor api actions.
 * 
 * This class will hold information that is determined based on the call made.
 * This does not actually include data passed to the call as parameters. 
 * Moreso data such as agent id, company id, company name, enterprise name, etc.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_CallContext
{
	/**
	 * @var int
	 */
	protected $agent_id;

    /**
     * @var string
     */
    protected $agent_name;

	/**
	 * @var int
	 */
	protected $company_id;

	/**
	 * @var string
	 */
	protected $company;

	/**
	 * @var int
	 */
	protected $application_id;

    public function getApiAgentName() {
        return $this->agent_name;
    }

    public function setApiAgentName($agent_name) {
        $this->agent_name = $agent_name;
    }

	/**
	 * Sets the api agent id.
	 * 
	 * This is the agent that the call was made by.
	 *
	 * @param int $agent_id
	 */
	public function setApiAgentId($agent_id)
	{
		$this->agent_id = $agent_id;
	}
	
	/**
	 * Returns the agent id
	 *
	 * @return int
	 */
	public function getApiAgentId()
	{
		return $this->agent_id;
	}

	/**
	 * Set the company id.
	 * @param int $company_id
	 * @return void
	 */
	public function setCompanyId($company_id)
	{
		$this->company_id = $company_id;
	}
	
	/**
	 * Returns the company id
	 *
	 * @return int
	 */
	public function getCompanyId()
	{
		return $this->company_id;
	}

	/**
	 * Set the company.
	 * @param string $company
	 * @return void
	 */
	public function setCompany($company)
	{
		$this->company = $company;
	}
	
	/**
	 * Returns the company
	 *
	 * @return string
	 */
	public function getCompany()
	{
		return $this->company;
	}

	public function setApplicationId($application_id)
	{
		$this->application_id = $application_id;
}

	public function getApplicationId()
	{
		return $this->application_id;
	}
}

?>
