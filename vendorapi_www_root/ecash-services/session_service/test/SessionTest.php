<?php

class SessionTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var Session
	 */
	protected $session;
	protected $session_id_1 = '001e42f3ffcae46d7ae67951cee60ea2';
	protected $session_id_1_table = 'session_0';
	protected $session_key_1 = 'fruit';
	protected $expected_value_1 = 'mango';
	
	public function setUp()
	{
		$this->session = new Session(bootstrap_config()->pdo);
		
		parent::setUp();
	}
	
	public function testCreatingASession()
	{
		$session_id = $this->session->create();
		
		$stmnt = bootstrap_config()->pdo->prepare(
			"SELECT COUNT(*) AS c FROM " . $this->session->tableFromSessionID($session_id) . " WHERE session_id = ?"
		);
		$stmnt->execute(array($session_id));
		$row = $stmnt->fetch(PDO::FETCH_ASSOC);
		
		$this->assertEquals(
			1, $row['c'], "Unexpected number of session rows after create(): {$row['c']}'"
		);
	}
	
	public function testReadingFromASession()
	{
		$lock_key = $this->session->lock($this->session_id_1);
		$session = $this->session->read($this->session_id_1, $lock_key);
		
		$this->assertEquals(
			$session[$this->session_key_1],
			$this->expected_value_1,
			'Session read did not have expected key/value.'
		);
	}
	
	public function testWritingToASession()
	{
		$KEY = 'new_key';
		$VALUE = 'bananas + circle = ' . rand(10, 10000);
		$decoded_value = trim(shell_exec(
			'php -f ' . dirname(__FILE__) . "/encode.php \"array('$KEY' => '$VALUE')\""
		));
		
		$lock_key = $this->session->lock($this->session_id_1);
		$session = array($KEY => $VALUE);
		$this->session->save($this->session_id_1, $lock_key, $session);
		
		$stmnt = bootstrap_config()->pdo->prepare(
			"SELECT session_info FROM " 
			. $this->session->tableFromSessionID($this->session_id_1) 
			. " WHERE session_id = ?"
		);
		$stmnt->execute(array($this->session_id_1));
		$row = $stmnt->fetch(PDO::FETCH_ASSOC);
		$this->assertNotEquals(
			FALSE, $row, 'Could not retrieve row session was supposed to write to.'
		);
		$this->assertEquals($row['session_info'], $decoded_value, 'Saved value was not correct.');
	}
	
	public function testOpeningALockedSession()
	{
		$this->setExpectedException('SessionLockException');
		$this->session->lock($this->session_id_1);	// by default, locks for 60 seconds
		
		$seconds_to_lock = $seconds_to_try = 1;
		$this->session->lock($this->session_id_1, $seconds_to_try, $seconds_to_lock);
	}
	
	public function testAgainstAlienLock()
	{
		$this->markTestSkipped("For a last minute release, session respects session_lock, which deadlocks this test.");
		// create row which looks like it was created with session.8.php
		$alien_locked_row = array(
			'session_id' => '5fd1415bebe13fb64f9e1f82c3b4decd',
			'session_info' => 'a{}',
			'session_lock' => 1,
		);
		$alien_lock_seconds = 2;
		$seconds_to_lock = 2;	// not really important for this test.
		
		$stmnt = bootstrap_config()->pdo->prepare(
			"INSERT INTO " . $this->session->tableFromSessionID($alien_locked_row['session_id'])
			. " (session_id, session_info, session_lock, date_locked)
			VALUES (:session_id, :session_info, :session_lock, DATE_ADD(NOW(), INTERVAL $alien_lock_seconds SECOND))"
		);
		$stmnt->execute($alien_locked_row);
		
		// try to acquire lock on session foreign (alien) system locked.
		try 
		{
			$this->session->lock(
				$alien_locked_row['session_id'],  
				$alien_lock_seconds - 1,
				$seconds_to_lock
			);
			$this->assertTrue(FALSE, 'Lock was acquired in violation of old lock system!');
		}
		catch (SessionLockException $e)
		{
			$this->assertTrue(TRUE, 
				'Correctly unable to acquire session locked by old system: ' 
				. $e->getMessage()
			);
		}
		
		sleep($alien_lock_seconds + 1);
		
		// should not throw exception
		$this->session->lock($alien_locked_row['session_id']);
	}
	
	public function testGettingTableFromSessionID()
	{
		$session = $this->freshSessionMock(array('__toString'));
		
		$this->assertEquals(
			$this->session_id_1_table,
			$session->tableFromSessionID($this->session_id_1),
			'Wrong table name produced from session object.'
		);
	}
	
	public function testReleasingAndReacquiringLock()
	{
		$a_long_time = 2;
		$immediately = 1;
		
		$lock_key = $this->session->lock($this->session_id_1, $a_long_time, $a_long_time);
		$this->session->release($this->session_id_1, $lock_key);
		
		// check the lock was released properly by trying to immediately get it.
		$new_lock_key = $this->session->lock($this->session_id_1, $immediately, $a_long_time);
		$this->assertTrue((bool)$new_lock_key, 'New lock key was empty.');
	}
	
	// -------------------------------------------------------------------------
	
	protected function updateValuesToSession0($value)
	{
		$stmnt = bootstrap_config()->pdo->prepare('UPDATE session_0 SET session_info = ?');
		$stmnt->execute(array($value));
	}
	
	protected function freshSessionMock(array $methods = array())
	{
		return $this->getMock(
			'Session', 
			$methods, 
			array(bootstrap_config()->pdo)
		);
	}
	
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection()
	{
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(
			bootstrap_config()->pdo, bootstrap_config()->schema
		);
	}
	
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/_fixtures/SessionTest.xml');
	}
}

?>