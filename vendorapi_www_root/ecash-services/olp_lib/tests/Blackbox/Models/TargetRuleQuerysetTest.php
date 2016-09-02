<?php

/**
 * Tests the Rule queryset for a Blackbox_Models_Target
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Models
 * @group requires_blackbox
 * @todo remove group when issue 35145 is resolved
 */
class Blackbox_Models_TargetRuleQuerysetTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection() 
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/TargetTest.xml'
		);
	}
	
	/**
	 * Test the basic querying capacity of this query set object.
	 *
	 * @dataProvider basicQuerysetProvider
	 * @param array $filters The filters to use to pull targets.
	 * @param array $property_shorts The property shorts we expect to find with
	 * the filters provided.
	 * @param string $target_type The type of target to retrieve with the queryset.
	 * @param bool $active Whether the targets must be inactive or active.
	 * @param string $glue_type AND/OR string to indicate how to query rules.
	 * @return void
	 */
	public function testBasicQueryset(
		array $filters, 
		array $property_shorts, 
		$target_type = NULL, 
		$active = NULL, 
		$glue_type = OLP_DB_WhereGlue::AND_GLUE)
	{
		$target_model = new Blackbox_Models_Target(
			TEST_DB_CONNECTOR(TEST_BLACKBOX)
		);
		
		$query_set = new Blackbox_Models_TargetRuleQueryset($target_model, $glue_type);
		if ($target_type) $query_set->targetTypeFilter($target_type);
		if ($active !== NULL) $query_set->targetActiveFilter($active);
		
		foreach ($filters as $f)
		{
			$query_set->filter($f[0], $f[1], $f[2]);
		}
		
		$found = array();
		foreach ($query_set as $target)
		{
			$this->assertTrue(
				in_array(strtolower($target->property_short), $property_shorts),
				"{$target->property_short} is not in " . print_r($property_shorts, TRUE)
			);
			$found[] = $target->property_short;
		}
		$this->assertEquals(
			count($found),
			count($property_shorts),
			sprintf('targets %s not the same as %s',
				print_r($found, TRUE),
				print_r($property_shorts, TRUE))
		);
	}
	
	/**
	 * Provide data to test basic target rule queryset functionality.
	 *
	 * @return array
	 */
	public static function basicQuerysetProvider()
	{
		$target_type = 'TARGET';
		$campaign_type = 'CAMPAIGN';
		
		$active = TRUE;
		
		$where_1 = array(
			array('minimum_income', '>', 1000),
		);
		$where_2 = array(
			array('minimum_age', OLP_DB_WhereCond::EQUALS, 23),
		);
		$where_3 = $where_2;
		$where_4 = array(
			array('excluded_states', 'like', '%NY%'),
			array('excluded_states', OLP_DB_WhereCond::LIKE, '%NJ%'),
		);
		$where_5 = array(
			array('excluded_states', 'like', '%NV%'),
			array('excluded_states', 'like', '%KS%'),
		);
		$where_6 = $where_5;
		
		$property_shorts_1 = array('bgc', 'csg');
		$property_shorts_2 = array('bgc');
		$property_shorts_3 = array('bgc', 'tdc');
		$property_shorts_4 = array('nsc');
		$property_shorts_5 = array('tgc', 'gtc', 'csg');
		$property_shorts_6 = array();
		
		return array(
			// pull either inactive or active with income > 1000
			array($where_1, $property_shorts_1, $target_type),
			
			// pull active targets with minimum_age = 23
			array($where_2, $property_shorts_2, $target_type, $active),
			
			// pull campaigns/targets which are inactive/active for minimum_age = 23
			array($where_3, $property_shorts_3),
			
			// pull active targets with income > 1000
			array($where_1, $property_shorts_2, $target_type, $active),
			
			// pull active targets with excluded states of NJ and NY
			array($where_4, $property_shorts_4, $target_type, $active),
			
			// get campaigns who mention NV or KS, there should be 3
			array($where_5, $property_shorts_5, $campaign_type, $active, OLP_DB_WhereGlue::OR_GLUE),
			
			// changing the OR to AND for $where_5 should result in NO targets.
			array($where_6, $property_shorts_6, $campaign_type, $active,),
		);
	}
}

?>
