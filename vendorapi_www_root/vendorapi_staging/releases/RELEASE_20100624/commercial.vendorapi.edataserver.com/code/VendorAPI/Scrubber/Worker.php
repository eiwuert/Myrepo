<?php
class VendorAPI_Scrubber_Worker
{
	const MAX_ATTEMPTS = 255;

	public function execute(VendorAPI_Scrubber_Job $job)
	{
		output("Scrubbing {$job->mode} / {$job->enterprise} / {$job->company}: {$job->journal}");

		define('VENDORAPI_ENTERPRISE', $job->enterprise);
		$loader = new VendorAPI_Loader($job->enterprise, strtolower($job->company), $job->mode);

		//  We have to do this, because the bootstrap will throw notices
		// warnings everywhere, but we don't want to just @ so that actual
		// fatal errors show up
		$old_error = error_reporting(E_ERROR);
		$loader->bootstrap();
		error_reporting($old_error);

		/**
		 * Set the current mode on the RequestTimer so we know
		 * the session is CLI based, and not an HTTP request
		 */
		$loader->getDriver()->getTimer()->setMode('CLI');

		$this->scrubJournal(
			$loader->getDriver(),
			$job->journal
		);
	}

	protected function scrubJournal(VendorAPI_IDriver $driver, $file)
	{
		$db = $this->openJournal($file);
		if ($db === FALSE)
		{
			return;
		}

		output("Scrubbing journal {$file}");

		// order by least attempts first so that state objects with
		// issues don't prevent new state objects from being scrubbed
		$results = $db->query('SELECT * FROM state_object ORDER BY attempts');

		while (($row = $results->fetch(PDO::FETCH_OBJ)))
		{
			if ($row->attempts > self::MAX_ATTEMPTS)
			{
				output("Ignoring state object {$file}:{$row->state_object_id}; attempted too many times");
				continue;
			}

			try
			{
				// increment the attempt counter BEFORE processing the state object, so that
				// if we get a fatal error or something, it gets moved to the rear of the queue
				output("Attempting to scrub state object {$file}:{$row->state_object_id}");
				$this->incrementAttempt($db, $row->state_object_id);

				$completed = $this->processState(
					$driver,
					$db,
					unserialize(gzuncompress($row->state_object))
				);

				if ($completed)
				{
					output("Deleting state object {$file}:{$row->state_object_id}");
					$this->deleteStateObject($db, $row->state_object_id);
				}
			}
			catch (Exception $e)
			{
				$this->exception($e, "Could not process state object {$file}:{$row->state_object_id}:");
			}
		}

		$count = DB_Util_1::querySingleValue($db, 'SELECT count(*) FROM state_object');
		if ($count == 0
			&& file_exists($file))
		{
			output("Journal {$file} is empty. Removing.");
			unlink($file);
		}
		else
		{
			output("Somehow {$file} is not empty. Leaving it around.");
		}
	}

	protected function openJournal($file)
	{
		$lock_file = str_replace('.db', '.lock', $file);
		$pid = new VendorAPI_Scrubber_PidFile($lock_file);

		try
		{
			$pid->check();
		}
		catch (Exception $e)
		{
			return FALSE;
		}

		$db = new DB_Database_1('sqlite:'.$file);

		try
		{
			// upgrade the database if needed
			$db->exec("ALTER TABLE state_object ADD attempts INTEGER NOT NULL DEFAULT 0");
		}
		catch (Exception $e)
		{
		}

		return $db;
	}

	protected function deleteStateObject(DB_IConnection_1 $db, $state_object_id)
	{
		$query = 'DELETE FROM state_object WHERE state_object_id=?';
		DB_Util_1::queryPrepared($db, $query, array($state_object_id));
	}

	protected function incrementAttempt(DB_IConnection_1 $db, $state_object_id)
	{
		$q = 'UPDATE state_object SET attempts = attempts + 1 WHERE state_object_id = ?';
		DB_Util_1::queryPrepared($db, $q, array($state_object_id));
	}

	protected function processState(VendorAPI_IDriver $driver, DB_Database_1 $db, $object)
	{
		if (!$object instanceof VendorAPI_StateObject)
		{
			throw new RuntimeException('Invalid state object');
		}
		$app = new ECash_VendorAPI_DAO_Application($driver);
		return $app->save($object);
	}

	public function exception(Exception $e, $msg = '')
	{
		if ($msg) $msg .= "\n";
		// concat so that it gets written in one go, otherwise
		// it can get split up because we're forked
		echo $msg.$e->getMessage()."\n"
			. $e->getTraceAsString()."\n";
	}
}
