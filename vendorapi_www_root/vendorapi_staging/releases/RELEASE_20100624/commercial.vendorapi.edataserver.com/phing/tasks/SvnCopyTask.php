<?php
require_once("phing/Task.php");
require_once('phing/input/YesNoInputRequest.php');
/**
 * Basic phing task to copy svn urls
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class SvnCopyTask extends Task
{
	/**
	 *
	 * @var string
	 */
	protected $svnpath;

	/**
	 *
	 * @var string
	 */
	protected $username;

	/**
	 *
	 * @var string
	 */
	protected $password;

	/**
	 *
	 * @var string
	 */
	protected $nocache;

	/**
	 *
	 * @var source
	 */
	protected $source;

	/**
	 *
	 * @var target
	 */
	protected $target;

	/**
	 *
	 * @var dev message
	 */
	protected $comment;

	/**
	 * Initialize this task. We do nothing
	 * @return string
	 */
	public function init()
	{
		$this->nocache = FALSE;
	}

	/**
	 * Set the path to use for svn executable
	 * @param string $path
	 * @return void
	 */
	public function setSvnPath($path)
	{
		if (is_executable($path))
		{
			$this->svnpath = $path;
		}
		else
		{
			throw new RuntimeException("Invalid svn path {$path}");
		}
	}

	/**
	 * Get the svn path.. Will try to find it
	 * if it hasn't been set.
	 * @return string
	 */
	public function getSvnPath()
	{
		if (empty($this->svnpath))
		{
			$path = trim(`which svn`);
			$this->setSvnPath($path);
		}
		return $this->svnpath;
	}

	/**
	 * Set the username to use for this
	 * svn command
	 * @param string $user
	 * @return void
	 */
	public function setUsername($user)
	{
		$this->username = $user;
	}

	/**
	 * Set the password to use for this
	 * svn command
	 * @param string $password
	 * @return void
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 *
	 * @param boolean $nocache
	 * @return unknown_type
	 */
	public function setNocache($nocache)
	{
		$this->nocache = $nocache;
	}

	/**
	 * Run the task
	 * @return void
	 */
	public function main()
	{
		if (empty($this->target) || empty($this->source))
		{
			throw new RuntimeException("Requires a target and a source repository.");
		}
		if ($this->svnExists($this->source))
		{
			$this->svnCopy($this->source, $this->target);
		}
	}

	/**
	 * set the source repository url
	 * @param string $p
	 * @return void
	 */
	public function setSource($p)
	{
		$this->source = $p;
	}

	/**
	 * Set the target repository url
	 * @param string $p
	 * @return void
	 */
	public function setTarget($p)
	{
		$this->target = $p;
	}

	/**
	 * Get the svn path
	 * @return unknown_type
	 */
	protected function buildBaseSVNCommand()
	{
		if ($this->nocache && (empty($this->username) || empty($this->password)))
		{
			throw new RuntimeException("If using nocache, must have username/password.");
		}
		$svn = $this->getSvnPath();
		if (!empty($this->username))
		{
			$svn .= ' --username="'.$this->username.'"';
		}
		if (!empty($this->password))
		{
			$svn .= ' --password="'.$this->password.'"';
		}
		if ($this->nocache)
		{
			$svn .= ' --no-auth-cache';
		}

		return $svn;
	}

	/**
	 * Setting the message
	 * @param string $msg
	 * @return void
	 */
	public function setComment($msg)
	{
		$this->comment = $msg;
	}

	protected function svnCopy($source, $target)
	{
		$this->log("Copying $source to $target.", Project::MSG_INFO);
		if ($this->svnExists($target))
		{
			$input = new YesNoInputRequest("Target path exists. Delete in?", array('yes', 'no'));
			$this->getProject()->getInputHandler()->handleInput($input);
			$value = $input->getInput();
			if ($value)
			{
				$this->log("Deleting $target", Project::MSG_INFO);
				$cmd = $this->buildBaseSVNCommand()." delete {$target} -m \"Deleting to copy back over\"";
				exec($cmd, $output, $return);
				if ($return != 0)
				{
					throw new RuntimeException("Failed to remove $target");
				}
			}
			else
			{
				return;
			}
		}
		$cmd = $this->buildBaseSVNCommand().' copy '.$source.' '.$target;
		if (!empty($this->comment))
		{
			$cmd .= ' -m "'.$this->comment.'"';
		}
		exec($cmd, $output, $return);
		if ($return != 0)
		{
			throw new RuntimeException("Failed to copy $source to $target");
		}
	}

	/**
	 * Does the svn uri exist?
	 * @param $url
	 * @return boolean
	 */
	protected function svnExists($url)
	{
		$this->log("Checking existance of $url", Project::MSG_DEBUG);
		$cmd = $this->buildBaseSVNCommand()." ls $url 2> /dev/null";
		$this->log("Executing svn: $cmd", Project::MSG_DEBUG);
		exec($cmd, $output, $return);
		return ($return == 0);
	}

}