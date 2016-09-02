<?php
/**
 * Parses/Stores configuration information for handling Messages
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLP_Message_Config
{
	/**
	 * the file that we'll be using to gather configuration
	 * information
	 * @var string
	 */
	protected $configfile;
	
	/**
	 * @var DOMDocument 
	 */
	protected $domdocument;
	
	/**
	 * @var DOMXPath 
	 */
	protected $xpath;
	
	/**
	 * 
	 * @var string
	 */
	protected $default_environment;
	
	/**
	 * Sets the environment to use by default
	 * @param string $environment
	 * @return void
	 */
	public function setEnvironment($environment)
	{
		$this->default_environment = $environment;
	}
	
	/**
	 * Return the current config file that the config
	 * is using.
	 * @return string
	 */
	public function getConfigFile()
	{
		if (!isset($this->configfile))
		{
			$this->configfile = $this->findConfigFile();
		}
		return $this->configfile;
	}
	
	
	/**
	 * Attempts to locate a config file to use. 
	 * @return string
	 */
	public function findConfigFile()
	{
		return $this->getDefaultConfigFile();
	} 
	
	/**
	 * Set the config file to use.
	 * @param string $file
	 * @return void
	 */
	public function setConfigFile($file)
	{
		if (file_exists($file))
		{
			// Reset the domdocument / xpath
			unset($this->domdocument);
			unset($this->xpath);
			$this->configfile = $file;
		}
		else
		{
			throw new InvalidArgumentException("File $file does not exist.");
		}
	}
	
	/**
	 * Return the destination string for an environment
	 * @param string $environment
	 * @return string|FALSE
	 */
	public function getDestination($environment = NULL)
	{
		if (is_null($environment))
		{
			$environment = $this->default_environment;
		}
		if (empty($environment))
		{
			$nodes = $this->getXpath()->query('//messageconfig/destination/environment[@default]');	
		}
		else
		{
			$nodes = $this->getXpath()->query('//messageconfig/destination/environment[@name="'.$environment.'"]');
		}
		if ($nodes->length)
		{
			return $nodes->item(0)->nodeValue;
		}
		return FALSE;
	}
	
	/**
	 * Return destination honoring any overrides that
	 * specific message may have
	 * @param string $message
	 * @param string $environment
	 * @return string
	 */
	public function getMessageDestination($message, $environment = NULL)
	{
		if (is_null($environment))
		{
			$environment = $this->default_environment;
		}
		$env_query = !empty($environment) ? 'environment[@name="'.$environment.'"]' : 'environment[@default]';
		
		$nodes = $this->getXpath()->query('//messageconfig/messages/message[@name="'.$message.'"]/destination/'.$env_query);
		if ($nodes->length)
		{
			return $nodes->item(0)->nodeValue;
		}
		return $this->getDestination($environment);
	}

	/**
	 * Returns the source of a message
	 * @param string $message
	 * @return string|FALSE
	 */
	public function getMessageSource($message)
	{
		$nodes = $this->getXpath()->query('//messageconfig/messages/message[@name="'.$message.'"]/source');
		if ($nodes->length)
		{
			return $nodes->item(0)->nodeValue;
		}
		return FALSE;
	}
	
	/**
	 * Returns a class associated with the message
	 * @param string $message
	 * @return string
	 */
	public function getMessageClass($message)
	{
		$nodes = $this->getXpath()->query('//messageconfig/messages/message[@name="'.$message.'"]/class');
		if ($nodes->length)
		{
			return $nodes->item(0)->nodeValue;
		}
		return FALSE;
	}
	
	/**
	 * Returns a message class based on the message
	 * source
	 * @param string $source
	 * @return string|False
	 */
	public function getMessageClassBySource($source)
	{
		$nodes = $this->getXpath()->query('//messageconfig/messages/message[source="'.$source.'"]/class');
		if ($nodes->length)
		{
			return $nodes->item(0)->nodeValue;
		}
		return FALSE;
	}
	
	/**
	 * Returns the default path to look for config file.
	 * @return string
	 */
	protected function getDefaultConfigPath()
	{
		return dirname(__FILE__).DIRECTORY_SEPARATOR.'config';
	}
	
	/**
	 * returns the default config file.
	 * @return string
	 */
	protected function getDefaultConfigFile()
	{
		return $this->getDefaultConfigPath().DIRECTORY_SEPARATOR.'message-config.xml';
	}
	
	/**
	 * Return a domdocument representing the config file. 
	 * Will create a new one if one has not already been created or 
	 * reuse the existing one.
	 * @return DOMDocument
	 */
	protected function getDomDocument()
	{
		if (!$this->domdocument instanceof DOMDocument)
		{
			$this->domdocument = new DOMDocument();
			$this->domdocument->load($this->getConfigFile());
		}
		return $this->domdocument;
	}
	
	/**
	 * Return a domxpath representing the config
	 * file. Creates a new one if it has not already been
	 * created or reuses the existing one
	 * @return DOMXpath
	 */
	protected function getXpath()
	{
		if (!$this->xpath instanceof DOMXPath)
		{
			$this->xpath = new DOMXPath($this->getDomDocument());
		}
		return $this->xpath;
	}
}