<?php
require_once("phing/Task.php");
require_once('phing/input/YesNoInputRequest.php');


class SSHTask extends SvnBaseTask
{
	protected $servers = array();
	protected $server_lists = array();
	protected $command;
	protected $verbose = FALSE;
	protected $failed_on_error = TRUE;

	public function addServer(Server $server)
	{
		$this->servers[] = $server;
	}

	public function addServerList(ServerList $list)
	{
		$this->server_lists[] = $list;
	}

	public function setCommand($command)
	{
		$this->command = trim($command);
	}

	public function addText($text)
	{
		$this->command = trim($text);
	}

	public function setVerbose($verbose)
	{
		$this->verbose = $verbose;
	}
	
	public function setFailedOnError($fail)
	{
		$this->fail_on_error = $fail;
	}

	public function performCommand(Server $server)
	{
		$ssh = trim(`which ssh`);
		if (!is_executable($ssh))
		{
			throw new runtimeException("Failed to find ssh executable.");
		}
		$ssh .= ' ';
		$port = $server->getPort($this->getProject());
		$username = $server->getUsername($this->getProject());
		if (!empty($port))
		{
			$ssh .= '-p '.escapeshellarg($port).' ';
		}
		if (!empty($username))
		{
			$ssh .= escapeshellarg($port).'@';
		}
		$ssh .= escapeshellarg($server->getHost($this->getProject()));
		$ssh .= ' '.escapeshellarg($this->command);
		if ($this->verbose === FALSE)
		{
			$ssh .= ' 2> /dev/null';
		}

		$this->log("Connecting to {$server->asString($this->getProject())}");
		ob_start();
		$this->log("Server: {$server->asString($this->getProject())}\nCommand: $ssh", Project::MSG_DEBUG);
		system($ssh, $return);
		if ($this->verbose !== FALSE)
		{
			$output = ob_get_clean();
			$output = $server->asString($this->getProject()).str_replace("\n","\n {$server->asString($this->getProject())} ", $output);
			$this->log($output);
		}
		else
		{
			ob_end_clean();
		}
		return $return == 0;
	}

    function main()
    {
		$pids = array();
		if (empty($this->command))
		{
			throw new RuntimeException("Invalid command!");
		}
		foreach ($this->server_lists as $list)
		{
			$servers = $list->getServers($this->getProject($p));
			foreach ($servers as $server)
			{
				$this->addServer($server);
			}
		}
		$this->server_list = array();
		$servers = array();
		foreach ($this->servers as $server)
		{
			$pid = pcntl_fork();
			if ($pid == -1)
			{
				throw new RuntimeException("Failed to fork.");
			}
			elseif ($pid)
			{
				$servers[$pid] = $server->asString($this->getProject());
			}
			else
			{
				exit($this->performCommand($server) ? 0 : 1);
			}
		}
		$pass = TRUE;
		$pids = array_keys($servers);
		$failed_pids = array();
		foreach ($pids as $pid)
		{
			pcntl_waitpid($pid, $status);
			if (pcntl_wifexited($status))
			{
				if (pcntl_wexitstatus($status) != 0)
				{
					$pass = FALSE;
					$failed_pids[] = $pid;
				}					
				
			}
			else
			{
				$failed_pids[] = $pid;
			}
		}
		if (!$pass)
		{
			foreach ($failed_pids as $pid)
			{
				$this->log("{$servers[$pid]}: command exited with failed status.", Project::MSG_ERR);
			}
			if ($this->failed_on_error)
			{
				throw new BuildException("Comamnds Failed.");
			}
		}
    }
}