<?php

class TestAPI
{
	protected static $api;
	protected static $enterprise;
	protected static $company;
	protected static $mode;

	public static function getInstance($enterprise, $company, $mode, $username, $password)
	{
		if (self::$api)
		{
			if ($enterprise != self::$enterprise
				&& $company != self::$company
				&& $mode != self::$mode)
			{
				throw new Exception('Cannot initialize API for a different enterprise/company/mode');
			}
			return self::$api;
		}

		self::$enterprise = $_REQUEST['enterprise'] = $_GET['enterprise'] = $enterprise;
		self::$company = $_REQUEST['company'] = $_GET['company'] = $company;
		self::$mode = $_ENV['ENVIRONMENT_MODE'] = $mode;

		define('VENDORAPI_BASE_DIR', realpath('../'));

		$product = ($enterprise === 'clk') ? 'CLK' : 'Commercial';
		$loader_class = $product.'TestLoader';

		require_once VENDORAPI_BASE_DIR.'/../ecash_'.strtolower($product).'/code/ECash/VendorAPI/Loader.php';
		require_once $loader_class.'.php';

		$loader = new $loader_class(
			$enterprise,
			$company,
			$mode
		);
		$loader->bootstrap();

		$driver = $loader->getDriver();
		$auth = $driver->getAuthenticator();

		@$auth->authenticate(
			$username,
			$password
		);

		$call_context = new VendorAPI_CallContext();
		$call_context->setApiAgentId($auth->getAgentId());
		$call_context->setCompanyId($driver->getCompanyId());

		return self::$api = new self(
			new VendorAPI_Service($driver, $call_context),
			$driver
		);
	}

	protected $service;
	protected $driver;

	protected function __construct(VendorAPI_Service $s, VendorAPI_IDriver $d)
	{
		$this->service = $s;
		$this->driver = $d;
	}

	public function executeAction($action, array $args, $scrub = TRUE, &$state = NULL)
	{
		// clean out the journals so we can detect a change
		$db_path = '/var/state/vendor_api/'.strtolower($this->driver->getCompany()).'/*.db';
		foreach (glob($db_path) as $db) unlink($db);

		$result = call_user_func_array(array($this->service, $action), $args);

		if (isset($result['state_object']))
		{
			$state = unserialize($result['state_object']);

			if ($scrub
				&& count(glob($db_path)))
			{
				$app = new ECash_VendorAPI_DAO_Application($this->driver);
				$app->save($state);
			}
		}

		// clean out the journals again
		foreach (glob($db_path) as $db) unlink($db);

		return $result;
	}
}

?>
