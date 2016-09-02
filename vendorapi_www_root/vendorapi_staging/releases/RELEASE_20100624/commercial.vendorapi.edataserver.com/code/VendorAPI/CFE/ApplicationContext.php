<?php

/**
 * CFE context wrapping our application object
 * @author stephan.soileau <stephan.soileau@sellingsource.com>
 *
 */
class VendorAPI_CFE_ApplicationContext implements ECash_CFE_IContext
{
	/**
	 *
	 * @var VendorAPI_IApplication
	 */
	protected $application;

	/**
	 * @var VendorAPI_CallContext
	 */
	protected $context;

	/**
	 *
	 * @param VendorAPI_IApplication $application
	 * @return void
	 */
	public function __construct(VendorAPI_IApplication $application, VendorAPI_CallContext $context)
	{
		$this->application = $application;
		$this->context = $context;
	}

	/**
	 * Set an attribute on the Application context
	 *
	 * @param String $name
	 * @param String $value
	 * @return void
	 */
	public function setAttribute($name, $value)
	{
		$method = sprintf('set%s', str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));

		if (method_exists($this, $method))
		{
			$this->$method($value);
		}
		elseif (method_exists($this->application, $method))
		{
			$this->application->$method($value);
		}
		else
		{
			$this->application->$name = $value;
		}
	}

	/**
	 * return an attribute in the application
	 * @param string $name
	 * @return string
	 */
	public function getAttribute($name)
	{
		$method = sprintf('get%s', str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));

		if (method_exists($this, $method))
		{
			return $this->$method();
		}
		elseif (method_exists($this->application, $method))
		{
			return $this->application->$method();
		}
		elseif (method_exists($this->context, $method))
		{
			return $this->context->$method();
		}
		else
		{
			return $this->application->$name;
		}
	}

	/**
	 * @return bool
	 */
	public function getHasLoanActions()
	{
		return $this->application->hasLoanActions($this->context->getApiAgentId());
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function setApplicationStatus($value)
	{
		$this->application->updateStatus(
			$value,
			$this->context->getApiAgentId()
		);
	}

	/**
	 *
	 * @return VendorAPI_IApplication
	 */
	public function getApplication()
	{
		return $this->application;
	}
}
