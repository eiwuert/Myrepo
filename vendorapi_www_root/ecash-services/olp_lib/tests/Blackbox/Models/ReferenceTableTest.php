<?php
/**
 * Test case for the Blackbox_Models_ReferenceTable object.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Models_ReferenceTableTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests the toArray function.
	 *
	 * @return void
	 */
	public function testToArray()
	{
		$db = $this->getMock('DB_IConnection_1');
		
		$empty_ref_model = $this->getMock(
			'Blackbox_Models_Reference_BlackboxType',
			array('save', 'isStored', 'isAltered'),
			array($db)
		);
		
		$target_ref_model = $this->getMock(
			'Blackbox_Models_Reference_BlackboxType',
			array('save', 'isStored', 'isAltered'),
			array($db)
		);
		$target_ref_model->expects($this->any())->method('isStored')->will($this->returnValue(TRUE));
		$target_ref_model->expects($this->any())->method('isAltered')->will($this->returnValue(FALSE));
		$target_ref_model->blackbox_type_id = 1;
		$target_ref_model->name = 'target';
		
		$campaign_ref_model = $this->getMock(
			'Blackbox_Models_Reference_BlackboxType',
			array('save', 'isStored', 'isAltered'),
			array($db)
		);
		$campaign_ref_model->expects($this->any())->method('isStored')->will($this->returnValue(TRUE));
		$campaign_ref_model->expects($this->any())->method('isAltered')->will($this->returnValue(FALSE));
		$campaign_ref_model->blackbox_type_id = 2;
		$campaign_ref_model->name = 'campaign';
		
		$expected_data_id_key = array(
			1 => 'target',
			2 => 'campaign'
		);
		
		$expected_data_name_key = array(
			'target' => 1,
			'campaign' => 2
		);
		
		$ref_table = new Blackbox_Models_ReferenceTable($empty_ref_model);
		$ref_table->addModel($target_ref_model);
		$ref_table->addModel($campaign_ref_model);
		
		$this->assertEquals($expected_data_id_key, $ref_table->toArray());
		$this->assertEquals($expected_data_name_key, $ref_table->toArray(FALSE));
	}
}
