<?php
/**
 * Tests the SubArray validator.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Validation_SubArrayTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that our isValid passes correctly.
	 *
	 * @return void
	 */
	public function testIsValidPass()
	{
		$array = array(
			array('stuff' => 1)
		);
		
		$validator = new Validation_SubArray_1();
		$validator->addValidator('stuff', new Validation_Number_1(1, 2));
		$this->assertTrue($validator->isValid($array, new ArrayObject()));
	}
	
	/**
	 * Tests that if we pass an empty array to a SubArray validator with optional parameters, that it
	 * returns TRUE.
	 *
	 * @return void
	 */
	public function testIsValidEmptyArray()
	{
		$validator = new Validation_SubArray_1();
		$validator->addValidator('stuff', new Validation_Optional_1(new Validation_Number_1(1, 2)));
		$this->assertTrue($validator->isValid(array(), new ArrayObject()));
	}
	
	/**
	 * Tests that we get an error if the value is not an array.
	 *
	 * @return void
	 */
	public function testGetMessageNotArray()
	{
		$value = new stdClass();
		$value->test = 'not an array';
		
		$validator = new Validation_ObjectValidator_1();
		$validator->addValidator('test', new Validation_SubArray_1());
		
		$this->assertFalse($validator->validate($value));
		
		$errors = $validator->getErrors();
		$this->assertEquals('test', $errors[0]->field);
	}
	
	/**
	 * Tests that we get back a flat error array and not nested arrays.
	 *
	 * @return void
	 */
	public function testReturnsFlatErrorArray()
	{
		$value = array(
			'test' => array(
				array('stuff' => 1)
			)
		);
		
		$validator = new Validation_ArrayValidator_1();
		$subarray_validator = new Validation_SubArray_1();
		$subarray_validator->addValidator('stuff', new Validation_Number_1(2, 2));
		$validator->addValidator('test', $subarray_validator);
		
		$validator->validate($value);
		$errors = $validator->getErrors();
		
		$this->assertEquals('stuff', $errors[0]->field);
		$this->assertEquals('test', $errors[1]->field);
	}
	
	/**
	 * Tests that if you have multiple validators in the SubArray validator and multiple elements in your array,
	 * that errors generated in the first element aren't removed when the second element is validated.
	 *
	 * @return void
	 */
	public function testMultipleSubValidators()
	{
		$value = array(
			'campaign_info' => array(
				array('promo_id' => 99999, 'name' => 'nationalfastcash'),
				array('promo_id' => 5000000, 'name' => 'stuff.com')
			)
		);
		
		$validator = new Validation_ArrayValidator_1();
		$subarray_validator = new Validation_SubArray_1();
		$subarray_validator->addValidator('promo_id', new Validation_Number_1(10000, 99999));
		$subarray_validator->addValidator('name', new Validation_Regex_1('/^\w+\.(com|net|org)$/', 'does not work'));
		$validator->addValidator('campaign_info', $subarray_validator);
		
		$validator->validate($value);
		$errors = $validator->getErrors();
		
		$this->assertEquals('name', $errors[0]->field);
		$this->assertEquals('promo_id', $errors[1]->field);
	}
}
