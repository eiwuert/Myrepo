<?php

require_once 'prpc/server.php';

class Nirvana_Aggregate extends Prpc_Server
{
	protected $sources;
	protected $order;
	protected $username;
	protected $password;

	/**
	 * @var Nirvana_Log
	 */
	protected $log;

	public function __construct(array $sources, array $order, $username, $password, Nirvana_Log $log)
	{
		$this->sources = $sources;
		$this->order = $order;
		$this->username = $username;
		$this->password = $password;
		$this->log = $log;

		// Run parent's constructor
		parent::__construct();
	}

	public function Fetch($track)
	{
		return $this->Fetch_Multiple(array($track));
	}

	/**
	 * Takes in track keys, returns a token data about them.
	 *
	 * @param array $track_keys
	 * @return array
	 */
	public function Fetch_Multiple($track_keys)
	{
		if (!is_array($track_keys))
		{
			$track_keys = array($track_keys);
		}

		// To protect against bad data, only process strings
		$track_keys = array_filter($track_keys, 'is_string');

		$keys = array();
		foreach($track_keys as $key)
		{
			$key = trim($key);
			if (!empty($key)) $keys[] = $key;
		}

		$track_data = array();
		$track_keys = $keys;

		foreach($this->order as $source_name)
		{
			if (count($track_keys) == 0) break;

			try
			{
				$this->log->info("Fetching data for %s tracks from %s [%s]", count($track_keys), $source_name, $this->sources[$source_name]);

				$start_time = microtime(TRUE);
				$data = $this->sources[$source_name]->getTokens($track_keys, $this->username, $this->password);
				$total_time = microtime(TRUE) - $start_time;

				if (is_array($data))
				{
					foreach($data as $key => $tokens)
					{
						if (count($tokens))
						{
							$this->log->debug("Found data for track %s in source %s [%s]", $key, $source_name, $this->sources[$source_name]);
						}
						else
						{
							unset($data[$key]);
						}
					}

					if (count($data) > 0)
					{
						$track_data = array_merge($track_data, $data);
						$track_keys = array_diff($track_keys, array_keys($data));
					}
				}
				else
				{
					$this->log->warn(
						"%s [%s] returned non-array after %s seconds",
						$source_name,
						$this->sources[$source_name],
						$total_time
					);
				}
			}
			catch (Exception $e)
			{
				$this->log->error(
					"Fetching from %s [%s] failed: %s\n%s",
					$source_name,
					$this->sources[$source_name],
					$e
				);
			}
		}

		foreach ($track_keys as $key)
		{
			$this->log->debug("Found no data for track %s", $key);
			$track_data[$key] = array();
		}

		return $track_data;
	}
}
