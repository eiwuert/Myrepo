<?php

	class proClient
	{
		var $open_try = 3;
		var $open_us = 50000;
		var $save_try = 5000;
		var $save_us = 10000;
		
		var $is_batch = FALSE;
		var $batch = array();

		function proClient ($basePath, $exeKey)
		{
			if (! extension_loaded('sqlite'))
			{
				if (! dl ('sqlite.so'))
				{
					die ('no sqlite support');
				}
			}
			if (! preg_match('/^spc_.*$/', $exeKey))
			{
				die("Invalid exeKey ({$exeKey})");
			}
			$this->exeKey = $exeKey;

			$this->setBasePath($basePath);
		}

		function setBasePath($basePath)
		{
			$this->basePath = $basePath.$this->exeKey.'/journal';
			if (! file_exists($this->basePath))
			{
				$this->makeDir($this->basePath);
			}
		}

		function makeDir ($path)
		{
			$dirs = explode ('/', $path);
			$tmp = '/';
			foreach ($dirs as $dir)
			{
				if (! $dir) continue;
				$tmp .= $dir.'/';
				if (! file_exists($tmp))
				{
					mkdir ($tmp, 0775);
				}
			}
		}

		function bin2Str ($bin, $nbits = 6)
		{
			$alpha = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-";

			$p = 0;
			$q = strlen($bin);

			$w = 0;
			$have = 0;
			$mask = (1 << $nbits) - 1;

			$out = '';

			while (1)
			{
				if ($have < $nbits)
				{
					if ($p < $q)
					{
						$w |= ord($bin{$p++}) << $have;
						$have += 8;
					}
					else
					{
						if ($have == 0)
							break;
						$have = $nbits;
					}
				}

				$out .= $alpha{($w & $mask)};

				$w >>= $nbits;
				$have -= $nbits;
			}

			return $out;
		}

		function hash ($msg, $key = NULL)
		{
			$hash = $this->bin2Str(pack("H*", sha1($msg.$key)));
			return $hash;
		}


		function openJournal ()
		{
			$err = '';
			$journalPath = $this->basePath.'/';

			$glob = glob ($journalPath.'journal_*.db');
			if (!(is_array($glob) && count($glob)))
			{
				$glob = array($journalPath."journal_".sprintf ("%06d", mt_rand (0, 999)).".db");
			}
			shuffle($glob);

			$this->pid = getmypid();

			for ($i = 0, $f = false ; $i < 100 && !$f ; $i++)
			{
				foreach ($glob as $journalName)
				{
					$this->journalLock = $journalName.'-lock';
					if (file_exists ($this->journalLock) || file_exists($journalName.'-journal') || (file_exists($journalName) && @filesize($journalName) > 200000000))
					{
						continue;
					}
					$f = $this->_openJournal($journalName);
					if ($f) break;
				}
				if (! $f)
				{
					array_unshift($glob, $journalPath."journal_".sprintf ("%06d", mt_rand (0, 999)).".db");
					$glob = array_unique($glob);
				}
			}

			if (! $f)
			{
				//$host = trim(`hostname -f`);
				die("openJournal failed after $i retries");
			}
		}

		function _openJournal($journalName)
		{
			$try = 0;
			do
			{
				$this->db = sqlite_open ($journalName, 0666, $err);
			}
			while (! $this->db && $try++ < $this->open_try && ! usleep($this->open_us));

			if (! $this->db)
			{
				//die("openJournal failed $journalName with $err\n");
				return FALSE;
			}

			$this->retryQuery ("BEGIN");
			$this->journalName = $journalName;

			$c = sqlite_single_query ($this->db, "select count(*) from sqlite_master where name = 'data'");
			if (! $c)
			{
				$rc = sqlite_exec ($this->db, "CREATE TABLE data (action_date, action, ap01, ap02, ap03, ap04, ap05, ap06, ap07, ap08, ap09, ap10, status_flag)");
				if (! $rc)
				{
					return FALSE;
				}
				@chmod($this->journalName, 0660);
			}
			return TRUE;
		}

		function closeJournal ()
		{
			$this->retryQuery ("COMMIT");
			sqlite_close ($this->db);
		}

		function insertJournal ($arg)
		{
			$args = is_array ($arg) ? $arg : func_get_args ();

			while (count ($args) < 12)
			{
				$args[] = '';
			}

			$qry = "insert into data VALUES ('".implode("','", array_map("sqlite_escape_string", $args))."', 'LOCAL')";
			$rc = $this->retryQuery ($qry);
			if (! $rc)
			{
				return FALSE;
			}
			return TRUE;
		}

		function retryQuery ($qry, $try = 1000, $nap = 2000)
		{
			do
			{
				$rc = sqlite_exec ($this->db, $qry);
			}
			while (! $rc && $try-- && ! usleep($nap));
			return $rc;
		}

		function batchBegin()
		{
			$this->is_batch = TRUE;
		}

		function batchCommit()
		{
			$this->openJournal();

			foreach ($this->batch as $x)
			{
				$this->insertJournal($x);
			}

			$this->closeJournal();

			$this->batch = array();
			$this->is_batch = FALSE;
		}

		function _doJournal ($arg)
		{
			$this->openJournal ();

			$this->insertJournal ($arg);

			$this->closeJournal ();
		}

		function doJournal ($arg)
		{
			$arg = is_array ($arg) ? $arg : func_get_args ();

			if ($this->is_batch)
			{
				$this->batch[] = $arg;
			}
			else
			{
				$this->_doJournal($arg);
			}
		}
	}
?>
