<?php
class VendorAPI_SuppressionListTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_SuppressionList
	 */
	protected $suppression;

	protected function setUp()
	{
		$this->db = $this->getMock('DB_IConnection_1');
		$this->company_id = 2;
		$this->suppression = $this->getMock('VendorAPI_SuppressionList_DBLoader', array('getModelCollection','getListIDs'), array($this->db));
        $this->suppression->expects($this->any())
                ->method('getModelCollection')
                ->will($this->returnValue($this->getModelCollection()));
	}
	
	protected function getModelCollection()
	{
		$models = array(
			"SuppressionLists",
			"SuppressionListRevisions",
			"SuppressionListRevisionValues",
			"SuppressionListValues"
		);
		
		$model_item = array();
		
		foreach($models as $model)
		{
			$class = 'ECash_Models_'.$model;
			$model_item[$model] = $this->getMock($class, array('loadBy', 'loadAllBy'));
			
			$model_item[$model]->expects($this->any())
                ->method('loadBy')
                ->will($this->returnValue(true));

		}
		
		$model_item["SuppressionLists"]->name = "TestName";
		$model_item["SuppressionLists"]->field_name = "TestField";
		$model_item["SuppressionLists"]->description = "TestDesc";
		$model_item["SuppressionLists"]->loan_action = "TestAction";
		
		$model_item["SuppressionListRevisions"]->revision_id = 1;
		

		$rev_vals[0]->value_id = 5;
		$rev_vals[1]->value_id = 7;
		$model_item["SuppressionListRevisionValues"]->expects($this->any())
                ->method('loadAllBy')
                ->will($this->returnValue($rev_vals));

		$model_item["SuppressionListValue"]->value_id = 5;
		$model_item["SuppressionListValue"]->value = "test";              
			
		return $model_item;
	}	
	
	public function testgetList()
	{
		$this->markTestIncomplete("Fixed in another branch.");
		$name = "TestName";
		$values = $this->suppression->getListByName($name);
 		$this->assertThat($values[$key]["SuppressionList"], $this->isinstanceOf('TSS_SuppressionList_1'));		
		$this->assertEquals("TestField", $values[$key]["Field"]);		
 		
	}
	
}
?>
