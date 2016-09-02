<?php

/**
 * A constraint which will compare the value of a method return to a value.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package PHPUnit
 */
class Framework_Constraint_Method extends PHPUnit_Framework_Constraint
{
	/**
	 * The constraint to evaluate with.
	 *
	 * @var PHPUnit_Framework_Constraint
	 */
	protected $constraint;
	
	/**
	 * The method name to execute on the thing we'll evalaute()
	 *
	 * @var string
	 */
	protected $method_name;
	
	/**
	 * The arguments to pass to the method.
	 *
	 * @var array
	 */
	protected $args;
	
	/**
	 * The value from the method call at run time.
	 *
	 * @var value
	 */
	protected $value = 'Method Not Run!';

	/**
	 * A constraint which will compare the output of a method using a constraint.
	 *
	 * @param PHPUnit_Framework_Constraint $constraint The constraint to use to
	 * compare the output of the method with.
	 * @param string $method_name The name of the method to call on the object being
	 * compared at runtime.
	 * @param array $args List of arguments to pass to the method identified by
	 * $methodName
	 * @return void
	 */
	function __construct(PHPUnit_Framework_Constraint $constraint, $method_name, $args = array())
	{
		$this->constraint = $constraint;
		$this->method_name = $method_name;
		$this->args = $args;
	}
	
	/**
	 * 
	 * @param mixed $other Value or object to evaluate. 
	 * @return bool 
	 * @see PHPUnit_Framework_Constraint::evaluate()
	 */
	public function evaluate($other)
	{
		$this->setValueFromOther($other);
		return $this->constraint->evaluate($this->value);
	}
	
	/**
	 * Run a method (once) and store the value.
	 *
 	 * @param mixed $other The item we're being asked to evaluate.
 	 * @return void
	 */
	public function setValueFromOther($other)
	{
		PHPUnit_Framework_Assert::assertTrue(
			is_object($other),
			'Item to evaluate passed to ' . __CLASS__ . ' was not an object!'
		);
		PHPUnit_Framework_Assert::assertTrue(
			method_exists($other, $this->method_name) || method_exists($other, '__call'),
			'Object passed to ' . __CLASS__ . ' does not have the expected method ' . $this->method_name
		);
		try
		{
			$this->value = call_user_func_array(array($other, $this->method_name), $this->args);
		} 
		catch (Exception $e)
		{
			$this->value = 'Method call threw exception in ' 
				. __METHOD__ . ': ' . $e->getMessage();
		}
	}
	
	/**
	 * If this test has been run indicate the value which failed, otherwise the
	 * object we were supposed to evaluate.
	 *
	 * @param mixed $other The value passed to evaluate() which failed 
	 * the constraint check.
	 * @param string $description A string with extra description of what was
	 * going on while the evaluation failed.
	 * @param boolean $not Flag to indicate negation.
	 * @throws PHPUnit_Framework_ExpectationFailedException
	 * @return void
	 */
	public function fail($other, $description, $not = FALSE)
	{
		parent::fail(
			$this->value ? $this->value : $other,
			$description,
			$not
		);
	}
	
	/**
	 * @return string 
	 * @see PHPUnit_Framework_SelfDescribing::toString()
	 */
	public function toString()
	{
		return $this->constraint->toString();
	}
}

?>
