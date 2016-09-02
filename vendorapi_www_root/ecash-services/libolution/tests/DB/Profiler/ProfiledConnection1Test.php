<?php

/**
 * Test case for DB_Profiler_ProfiledConnection_1
 *
 * @author Jordan Raub <jordan.raub@dataxltd.com>
 */
class DB_Profiler_ProfiledConnection1Test extends PHPUnit_Framework_TestCase
{
	public function testBeginTransaction()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$connection->expects($this->once())
			->method('beginTransaction');

		$profiler->expects($this->once())
			->method('beginTransaction');

		$profiledConnection->beginTransaction();
	}

	public function testCommit()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$connection->expects($this->once())
			->method('commit');

		$profiler->expects($this->once())
			->method('commit');

		$profiledConnection->commit();
	}

	public function testRollBack()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$connection->expects($this->once())
			->method('rollBack');

		$profiler->expects($this->once())
			->method('rollBack');

		$profiledConnection->rollBack();
	}

	public function testQuery()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$query = 'query';

		$connection->expects($this->once())
			->method('query')
			->with($this->equalTo($query));

		$profiler->expects($this->once())
			->method('startQuery')
			->with($this->equalTo($query));
		$profiler->expects($this->once())
			->method('endQuery')
			->with($this->equalTo($query));

		$profiledConnection->query($query);
	}

	public function testPrepare()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$statement = $this->getStatementMock();

		$query = 'query';

		$connection->expects($this->once())
			->method('prepare')
			->will($this->returnValue($statement));

		$profiledConnection->prepare($query);
	}

	public function testExec()
	{
		$profiledConnection = new DB_Profiler_ProfiledConnection_1(
			$connection = $this->getConnectionMock(),
			$profiler = $this->getProfilerMock()
		);

		$query = 'query';

		$connection->expects($this->once())
			->method('exec')
			->with($this->equalTo($query));

		$profiler->expects($this->once())
			->method('startQuery')
			->with($this->equalTo($query));
		$profiler->expects($this->once())
			->method('endQuery')
			->with($this->equalTo($query));

		$profiledConnection->exec($query);
	}

	/**
	 * Returns a mock of the DB_Profiler_IProfiler_1
	 *
	 * @return DB_Profiler_IProfiler_1
	 */
	protected function getConnectionMock()
	{
		return $this->getMock(
				'DB_IConnection_1'
		);
	}

	/**
	 * Returns a mock of the DB_Profiler_IProfiler_1
	 *
	 * @return DB_Profiler_IProfiler_1
	 */
	protected function getStatementMock()
	{
		return $this->getMock(
				'StatementMock'
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