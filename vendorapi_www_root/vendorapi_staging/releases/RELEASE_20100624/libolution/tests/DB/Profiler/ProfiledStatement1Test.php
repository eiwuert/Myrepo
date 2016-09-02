<?php

/**
 * Test case for DB_Profiler_ProfiledStatement_1
 *
 * @author Jordan Raub <jordan.raub@dataxltd.com>
 */
class DB_Profiler_ProfiledStatement1Test extends PHPUnit_Framework_TestCase
{
	public function testExecNull()
	{
		$query = 'query';

		$stmt = new DB_Profiler_ProfiledStatement_1(
			$query,
			$stmtMock = $this->getStatementMock(),
			$profiler = $this->getProfilerMock()
		);

		$profiler->expects($this->once())
			->method('startQuery')
			->with($this->equalTo($query));
		$profiler->expects($this->once())
			->method('endQuery')
			->with($this->equalTo($query));
		$stmtMock->expects($this->once())
			->method('execute')
			->with($this->equalTo(null));

		$stmt->execute();
	}

	public function testExecNotNull()
	{
		$query = 'query';
		$args = array(
			'this'	=> 'that',
			'the'	=> 'other'
		);

		$stmt = new DB_Profiler_ProfiledStatement_1(
			$query,
			$stmtMock = $this->getStatementMock(),
			$profiler = $this->getProfilerMock()
		);

		$profiler->expects($this->once())
			->method('startQuery')
			->with($this->equalTo($query));
		$profiler->expects($this->once())
			->method('endQuery')
			->with($this->equalTo($query));
		$stmtMock->expects($this->once())
			->method('execute')
			->with($this->equalTo($args));

		$stmt->execute($args);
	}

	/**
	 * Returns a mock of the DB_IStatement_1 object
	 *
	 * @return DB_IStatement_1
	 */
	protected function getStatementMock()
	{
		return $this->getMock(
				'StatementMock',
				array('execute', 'fetch', 'fetchAll', 'rowCount', 'getIterator')
		);
	}

	/**
	 * Returns a mock of the DB_Profiler_IProfiler_1
	 *
	 * @return DB_Profiler_IProfiler_1
	 */
	protected function getProfilerMock()
	{
		return $this->getMock(
				'DB_Profiler_IProfiler_1'
		);
	}
}

?>