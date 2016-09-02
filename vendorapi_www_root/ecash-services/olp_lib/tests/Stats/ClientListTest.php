<?php
/** Test case for Stats_ClientList.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_ClientListTest extends PHPUnit_Framework_TestCase
{
	/** A list of values to search and their resulting username found.
	 *
	 * @return array
	 */
	public static function pickTargetTwoTargetsDataProvider()
	{
		return array(
			array(
				'username', // $search_by
				'catch',    // $search_value
				TRUE,       // $use_default
				'catch',    // $username
			),
			
			array('property_short', 'ufc', TRUE, 'clk'),
			array('property_short', 'UFC', TRUE, 'clk'),
			array('property_short', 'pcl', TRUE, 'clk'),
			array('property_short', 'ic', TRUE, 'imp'),
			array('property_short', 'fakeproperty', TRUE, 'catch'),
			array('property_short', 'fakeproperty', FALSE, NULL),
			
			array('property_id', 31631, TRUE, 'clk'),
			array('property_id', 64656, TRUE, 'lcs'),
			array('property_id', 57458, TRUE, 'ocp'),
			
			array('username', 'bbrule', TRUE, 'bbrule'),
			array('username', 'pw', TRUE, 'pw'),

			array('non-existant property', 'not found value', TRUE, 'catch'),
			array('non-existant property', 'not found value', FALSE, NULL),
		);
	}

	/**
	 * Tests pick target with two targets.
	 *
	 * @param string $search_by
	 * @param string $search_value
	 * @param string $username
	 * @dataProvider pickTargetTwoTargetsDataProvider
	 * @return void
	 */
	public function testClientList($search_by, $search_value, $use_default, $username)
	{
		$client_data = Stats_ClientList::getStatClient($search_by, $search_value, $use_default);
		
		if (is_array($client_data))
		{
			$this->assertEquals($client_data['username'], $username);
		}
		else
		{
			$this->assertEquals($client_data, $username);
		}
	}
}

?>
