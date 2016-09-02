<?php

require_once('autoload_setup.php');

class Date_PayDateModelTest extends PHPUnit_Framework_TestCase
{

		/**
		 * The exception could also be tested with a doc tag that looks like:
		 * [at]expectedException Exception
		 */
		public function testFormat()
		{
			$this->setExpectedException('Exception', 'Holiday iterator must contain holidays in Unix Timestamp format');
			$iterator = new ArrayIterator(array('2008-01-01'));
			$model = Date_PayDateModel_1::getModel(Date_PayDateModel_1::WEEKLY_ON_DAY, 'friday');
			$calc = new Date_PayDateCalculator_1($model, new Date_PayDateNormalizer_1($iterator, FALSE), strtotime('2008-01-04'));
		}
}

?>