<?php
/**
 * Test posting to the vendor api remotely. This test should be excluded from
 * running all tests in the phpunit config files.
 *
 * Note: the group is important here, as it keeps this test from running as part
 * of running all the tests, since it's an integration test, not a unit test.
 *
 * Note: This is the WRONG place for these tests. They should be in the individual
 * ecash modules. However
 *
 * 1) This was for a refactor of vendor_api proper, not those modules.
 * 2) Those other modules are a pain to get approved/pushed to QA.
 * 3) I left a TODO here and by default Adam and Andrew inherit my TODOs.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @group rpc-functional
 */
class Functional_Post_RemoteIntegrationTest extends PHPUnit_Framework_TestCase
{
	private $fixture_dir;
	private $url;


	public function setUp()
	{
		$this->fixture_dir = dirname(__FILE__) . '/_fixtures/application_array/';
		$this->url = "http://vendor_api_API.ds98.tss/index.php";	// INSERT YOUR BOX NUMBA!
		// $this->url = "http://vendor-api-API.qa.tss/index.php";
	}

	public function testPostingToAgean()
	{
		$data = $this->freshApplicationData();
		$response = $this->freshVendorAPIClient()->Post($data);
		$this->assertTrue((bool)$response['result']['qualified'], "Got " . print_r($response, true));
	}

	public function testThatAgeanRejectsOldCars() 
	{
		$data = $this->freshTitleLoanApplicationData();
		$data['year'] = 1981;
		$response = $this->freshVendorAPIClient()->Post($data);
		$this->assertFalse((bool)$response['result']['qualified'], "Got " . print_r($response, true));
	}

	public function testThatAgeanRejectsHighMileageCars()
	{
		$data = $this->freshTitleLoanApplicationData();
		$data['mileage'] = 200001;
		$response = $this->freshVendorAPIClient()->Post($data);
		$this->assertFalse((bool)$response['result']['qualified'], "Got " . print_r($response, true));
	}

	public function testAgeanRejectsBadState()
	{
		$data = $this->freshTitleLoanApplicationData();
		$data['state'] = 'ID';
		$response = $this->freshVendorAPIClient()->Post($data);
		$this->assertResponseNotQualified($response);
	}

	public function testAgeanAcceptsBadStateIfNonOrganic()
	{
		$data = $this->freshTitleLoanApplicationData('cbnk', 'cbnk2');
		$data['state'] = 'ID';
		$response = $this->freshVendorAPIClient('cbnk')->Post($data);
		$this->assertResponseIsQualified($response);
	}

	public function testPostingToAmeriloan()
	{
		$data = $this->freshApplicationData("ca", "ca");
		$response = $this->freshVendorAPIClient("ca")->Post($data);
		$this->assertResponseIsQualified($response);
	}

	public function testPostingToAmeriloanFromKansas()
	{
		$data = $this->freshApplicationData("ca", "ca");
		$data['state'] = 'KS';
		$response = $this->freshVendorAPIClient("ca")->Post($data);
		$this->assertResponseNotQualified($response);
	}

	public function testPostingToAmeriloanWithSameHomeAndWork()
	{
		$data = $this->freshApplicationData("ca", "ca");
		$data['phone_home'] = '9993334444';
		$data['phone_work'] = $data['phone_home'];
		$data['income_source'] = 'EMPLOYMENT';
		$response = $this->freshVendorAPIClient("ca")->Post($data);
		$this->assertResponseNotQualified($response);
	}

	public function testPostingToOPM()
	{
		$data = $this->freshApplicationData("opm_bsc", "opm_bsc");
		$response = $this->freshVendorAPIClient("opm_bsc")->Post($data);
		$this->assertResponseIsQualified($response);
	}

	public function testPostingToSCN()
	{
		$data = $this->freshApplicationData("generic", "generic");
		$response = $this->freshVendorAPIClient("generic")->Post($data);
		$this->assertResponseIsQualified($response);
	}

	/**
	 * When posting to a non-organic AALM there are
	 */
	public function testSCNAcceptsGAFromPW()
	{
		$data = $this->freshApplicationData("generic", "generic");
		$data['state'] = 'GA';
		$response = $this->freshVendorAPIClient("generic")->Post($data);
		$this->assertResponseIsQualified($response);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	protected function wtfWasResponse($response)
	{
		if (array_key_exists('state_object', $response))
		{
			$content = @unserialize($response['state_object']);
			$content = print_r($content, true);
		}
		else $content = print_r($response, true);

		file_put_contents('/tmp/wtfWasResponse', $content);
	}

	protected function assertResponseIsQualified($response, $qualified_amount = NULL)
	{
		return $this->assertResponse($response, true, $qualified_amount);
	}

	protected function assertResponseNotQualified($response)
	{
		return $this->assertResponse($response, false);
	}

	protected function assertResponse($response, $qualified, $qualified_amount = NULL)
	{
		$this->assertArrayHasKey('result', $response,
			"response " . print_r($response, true) . " had no result?"
		);

		$this->assertArrayHasKey('qualified', $response['result'],
			"Result " . print_r($response['result'], true) . "had no qualified column?"
		);

		$this->assertEquals((bool) $qualified, (bool) $response['result']['qualified'],
			"Expected different qualify result."
		);
	}

	/**
	 *
	 * @param array $response_object
	 * @return string representation of the state object, if present, otherwise
	 * representation of the response object.
	 */
	protected function repr($response_object)
	{
		if (array_key_exists("state_object", $response_object))
		{
			$unserialized = @unserialize($response_object["state_object"]);
			if ($unserialized) return print_r($unserialized, true);
		}

		return print_r($response_object, true);
	}

	/**
	 * @staticvar array $companies Allowed companies.
	 * @param string $company The company we'd like to post to.
	 * @return Rpc_Client_1 New client to talk to a VendorAPI deployment
	 */
	protected function freshVendorAPIClient($company = 'cbnk')
	{
		static $companies = array(
			'generic' => array('enterprise' => 'scb'),
		);
		
		if (!array_key_exists($company, $companies)) 
			throw new InvalidArgumentException("unknown company $company");

		$username = array_key_exists('username', $companies[$company])
			? $companies[$company]['username']
			: 'username';
		$password = array_key_exists('password', $companies[$company])
			? $companies[$company]['password']
			: 'password';

		if (!array_key_exists('enterprise', $companies[$company]))
		{
			throw new RuntimeException("companies array missing enterprise for $company");
		}
		else
		{
			$enterprise = $companies[$company]['enterprise'];
		}

		$api = ($enterprise == 'clk' ? 'amg' : 'commercial');

		return new Rpc_Client_1(
			sprintf("%s?company=%s&enterprise=%s&username=%s&password=%s",
				str_replace('API', $api, $this->url),
				$company,
				$companies[$company]['enterprise'],
				$username,
				$password),
			10
		);
	}

	protected function freshTitleLoanApplicationData($target='cbnk', $campaign='cbnk')
	{
		$data = $this->freshApplicationData($target, $campaign);
		$data['vin'] = "89283823la";
		$data['make'] = 'Ford';
		$data['is_title_loan'] = 1;
		return $data;
	}

	protected function freshApplicationData($target='cbnk', $campaign='cbnk')
	{
		$data = unserialize(
			file_get_contents($this->fixture_dir . 'remote_integration_basic.inc')
		);
		$data['application_id'] = rand(11111234, 77777234);
		$data['ssn'] = '51439' . sprintf('%04d', rand(0, 9999));
		$data['campaign'] = $campaign;
		$data['campaign_name'] = $campaign;
		$data['target'] = $target;
		
		return $data;
	}
}
?>
