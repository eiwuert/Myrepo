<?php

/**
 * Tests the generic client class which handles posting to blackbox vendors.
 * 
 * @group requires_blackbox
 * @todo remove from this group when issue #35145
 * @package LenderAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class LenderAPI_Generic_ClientTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Returns the Lender post data to override the client with in {@see testLoopback()}.
	 *
	 * @return array
	 */
	public static function loopbackDataProvider()
	{
		return array(
			array(
				array(
					'incoming_xsl' => file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.loopback.incoming.xsl'),
					'outgoing_xsl' => file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.loopback.outgoing.xsl'),
					"vendor_api_url_LOCAL" => NULL,
					"vendor_api_method_LOCAL" => 'LOOPBACK',
				),
				file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.loopback.sent.xml'),
				'Application Accepted!'
			),
		);
	}
	
	/**
	 * Test a client running in loopback mode.
	 *
	 * @dataProvider loopbackDataProvider
	 * @return void
	 */
	public function testLoopback($target_data, $data_sent, $message)
	{
		$client = new LenderAPI_Generic_Client('LOCAL', TEST_DB_CONNECTOR(TEST_BLACKBOX), LenderAPI_Generic_Client::POST_TYPE_STANDARD);
		$response = $client->postLead(
			'OBB', $this->getDataSources(), $target_data
		);
		
		// check that we stored the outgoing XML
		$this->assertEquals(
			strtolower(preg_replace('/\s/i', '', $response->getDataSent())), 
			strtolower(preg_replace('/\s/i', '', $data_sent)), 
			'Data sent did not match!'
		);
		
		// check that the transform went properly and the xsl:choose was matchedz
		$this->assertEquals($response->message, $message);
	}
	
	/**
	 * Returns the Lender post data to override the client with in {@see testEmail()}.
	 *
	 * @return array
	 */
	public static function emailDataProvider()
	{
		return array(
			array(
				array(
					'incoming_xsl' => file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.email.incoming.xsl'),
					'outgoing_xsl' => file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.email.outgoing.xsl'),
					"vendor_api_url_LOCAL" => NULL,
					"vendor_api_method_LOCAL" => 'LOOPBACK',
				),
				file_get_contents(dirname(__FILE__) . '/_fixtures/ClientTest.email.sent.xml'),
				'Accepted',
				'Thanks Joe!',
			),
		);
	}
	
	/**
	 * Test a client running in loopback mode.
	 *
	 * @dataProvider emailDataProvider
	 * @return void
	 */
	public function testEmail($target_data, $data_sent, $message, $thank_you_content)
	{
		$mock_client = new Mock_LenderAPI_Generic_Client('LOCAL', TEST_DB_CONNECTOR(TEST_BLACKBOX), LenderAPI_Generic_Client::POST_TYPE_STANDARD);
		$response = $mock_client->postLead(
			'OBB', $this->getDataSources(), $target_data
		);
		
		// check that we stored the outgoing XML
		$this->assertEquals(
			strtolower(preg_replace('/\s/i', '', $response->getDataSent())), 
			strtolower(preg_replace('/\s/i', '', $data_sent)), 
			'Data sent did not match!'
		);
		
		// check that the transform went properly and the xsl:choose was matchedz
		$this->assertEquals($response->message, $message);
		
		// check that the data sources were piped to the incoming transformation and set the name correctly in the thank you content
		$this->assertEquals($response->getThankYouContent(), $thank_you_content);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Generates the data sources client tests
	 *
	 * @return ArrayObject
	 */
	protected function getDataSources()
	{
		$data_sources = new ArrayObject();
		$data = new OLPBlackbox_Data();
		$data->name_first = 'Joe';
		$data_sources['application'] = new LenderAPI_BlackboxDataSource($data, 'application');
		return $data_sources;
	}
	
	// -------------------------------------------------------------------------
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		$connection = $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
		return $connection;
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		$dataset = $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/ClientTest.dataset.xml');
		return $dataset;
	}
}

// -------------------------------------------------------------------------

/**
 * Need to create a mock client that loads up a mock Email transport object
 * with objects that get set up by the client 
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class Mock_LenderAPI_Generic_Client extends LenderAPI_Generic_Client
{
/**
	 * Returns the transport object, assumedly for mocking.
	 * @param string $method The method to use, such as GET or POST_SOAP, to use
	 * to post to the lender.
	 * @param string $url the URL to post to.
	 * @param int|NULL $timeout Number of seconds before request time out.
	 * @return LenderAPI_Transport
	 */
	protected function getTransportObject($method, $url = '', $timeout = NULL)
	{
		return new Mock_LenderAPI_Transport_Email($this->response, $this->request_xsl, $this->response_xsl);
	}
}

/**
 * Need to mock the email transport to not actually send off any emails
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class Mock_LenderAPI_Transport_Email extends LenderAPI_Transport_Email
{
	/**
	 * Takes care of the actual email sending for this class
	 *
	 * @param array $recipients
	 * @param string $template
	 * @param array $tokens
	 * @return void
	 */
	protected function sendEmails($recipients, $template, $tokens)
	{
	}
}

class tx_Mail_Client {

}
?>
