<?php

/**
 * Test the BlackboxDataSource test to make sure that only the values needed for
 * the lender post are iterated over.
 *
 * @todo This was apparently marked "skipped," needs to be fixed.
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package LenderAPI
 */
class LenderAPI_BlackboxDataSourceTest extends PHPUnit_Framework_TestCase
{
	public static function testSourceDataProvider()
	{
		$doc = self::getDOMDocument();
		
		$data_xpath = 'application';
		$bad_data_keys = array(
			'do_datax_rework' => 'nothing', 
			'allow_datax_rework' => 'useless'
		);
		$blackbox_data = new OLPBlackbox_Data();
		
		$state_xpath = 'campaign';
		$bad_state_data_keys = array(
			'current_leads' => '3',
			'frequency_score' => 2,
		);
		$state_data = self::setupData(
			$doc, 
			$state_xpath, 
			$bad_state_data_keys
		);
		
		return array(
			array($blackbox_data, $bad_data_keys, $data_xpath),
			array($state_data, $bad_state_data_keys, $state_xpath),
		);
	}
	
	/**
	 * Returns a DOMDocument for the example XML, which is basically the master
	 * record on the sending protocol.
	 * @return DOMDocument
	 */
	protected static function getDOMDocument()
	{
		$doc = new DOMDocument();
		$doc->load(dirname(__FILE__) . '/../../code/LenderAPI/xml/transport_example.xml');
		return $doc;
	}
	
	/**
	 * Retrieves the campaign name from the XML.
	 * @return string
	 */
	protected static function getCampaignName()
	{
		$doc = self::getDOMDocument();
		$xpath = new DOMXPath($doc);
		$nodes = $xpath->query('//data/campaign/campaign_name');
		/* @var $nodes DOMNodeList */
		return $nodes->item(0)->nodeValue;
	}
	
	/**
	 * Test that the values that are expected for the LenderAPI.
	 * @dataProvider testSourceDataProvider
	 * @return void
	 */
	public function testSource($data, $bad_keys, $xpath_key)
	{
		$this->markTestSkipped("can't test with blackbox currently");
		$doc = self::getDOMDocument();
		
		$memcache = $this->getMock(
			'Cache_Memcache', 
			array('get', 'set'), 
			array(), 
			'', 
			FALSE
		);
		// get/set should be called, but get should not return anything to avoid
		// trying to build fields from memcache
		$memcache->expects($this->atLeastOnce())->method('get');
		$memcache->expects($this->any())->method('get')->will($this->returnValue(NULL));
		$memcache->expects($this->atLeastOnce())->method('set');
		

		// $data_source = new LenderAPI_BlackboxDataSource($blackbox_data);
		$data_source = $this->getMock(
			'LenderAPI_BlackboxDataSource', 
			array('xmlFileNotModified'),
			array($data, $xpath_key, $memcache),
			''
		);
		$data_source->expects($this->any())
			->method('xmlFileNotModified')
			->will($this->returnValue(TRUE));
		// $data_source->setStorageObject($memcache);
		
		$xpath = new DOMXPath($doc);
		
		foreach ($data_source as $key => $value)
		{
			$this->assertFalse(
				array_key_exists($key, array_keys($bad_keys)),
				"key $key (value: $value) should not be iterated over"
			);
			$this->assertEquals(
				1, 
				$xpath->query("//data/$xpath_key/$key")->length,
				sprintf("could not find //data/$xpath_key/$key")
			);
		}
	}
	
	/**
	 * Set up a blackbox data object with some keys that are valuable for the
	 * LenderAPI transport layer, and some that aren't.
	 * 
	 * @param DOMDocument $doc The example XML document for the LenderAPI post.
	 * @param mixed $data Something that accepts key/values (Blackbox_Data, etc.)
	 * @param string $xpath_key The key 
	 * @param array $bad_keys Keys to add to the blackbox data which should not
	 * be available for the LenderAPI post.
	 * @return OLPBlackbox_Data
	 */
	protected static function setupData(DOMDocument $doc, $data, $xpath_key, array $bad_keys = array())
	{
		$xpath = new DOMXPath($doc);
		
		// Broken, damn static functions
//		foreach ($xpath->query("//data/{$xpath_key}")->item(0)->childNodes as $node)
//		{
//			/* @var $node DOMElement */
//			if (!$node instanceof DOMElement) continue;
//			
//			$key = strtolower($node->nodeName);
//			try
//			{
//				$data->$key = strtoupper($node->nodeValue);
//			}
//			catch (Exception $e)
//			{
//				// probably a composite key, no worries.
//			}
//		}

		foreach ($bad_keys as $key => $value)
		{
			$data->$key = $value;
		}
		
		return $data;
	}
}
?>
