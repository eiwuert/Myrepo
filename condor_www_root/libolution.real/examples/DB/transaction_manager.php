<?php

	/*
		Essentially what we're going to do is write a function
		that requires a transaction, and then call it from the
		context of an existing transaction. The function will
		silently "borrow" the existing transaction, but be careful
		not to commit it. This allows the function to be used with
		or without an existing transaction, but ensures that the
		operating requiring a transaction will always have one.
	*/

	function A(DB_IConnection_1 $db, $value)
	{
		$st = $db->prepare("INSERT INTO blah VALUES (?)");
		$st->execute(array($value));

		return $db->lastInsertId();
	}

	function B(DB_IConnection_1 $db, $value)
	{
		$tm = new DB_TransactionManager_1($db);
		$tm->beginTransaction();

		try
		{
			$st = $db->prepare("INSERT INTO woot VALUES (?)");
			$st->execute(array($value));

			$tm->commit();
		}
		catch (Exception $e)
		{
			// clean up first!
			$tm->rollBack();
		}
	}

	require 'libolution/AutoLoad.1.php';

	@unlink('./transact');
	$db = new DB_Database_1('sqlite:./transact');
	$db->exec("
		CREATE TABLE blah (value string);
		CREATE TABLE woot (value string);
	");

	$tm = new DB_TransactionManager_1($db);
	$tm->beginTransaction();

	// this function doesn't require a transaction
	$id = A($db, 'test');

	// this one does, but will operate within the
	// context of the transaction we've already started
	B($db, $id);

	$tm->commit();

	$st = $db->query("
		SELECT blah.value as blah,
			woot.value as woot
		FROM blah
			JOIN woot ON (woot.value = blah.rowid)
	");
	foreach ($st as $r)
	{
		var_dump($r);
	}

?>