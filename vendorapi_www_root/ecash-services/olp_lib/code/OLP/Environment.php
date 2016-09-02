<?php

/**
 * Contains logic for determining what environment we're in.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Environment
{
	/**
	 * List of allowed environment overrides as the keys, and if supports
	 * read-only mode as the value.
	 *
	 * @var array
	 */
	protected static $allowed_environment_overrides = array(
		'QA_MANUAL' => array(
			'db_read_only' => FALSE,
			'domain_prefix' => 'qa.',
		),
		'QA2_MANUAL' => array(
			'db_read_only' => FALSE,
			'domain_prefix' => 'qa2.',
		),
		'QA_SEMIAUTOMATED' => array(
			'db_read_only' => FALSE,
			'domain_prefix' => 'saqa.',
		),
		'QA_AUTOMATED' => array(
			'db_read_only' => FALSE,
			'domain_prefix' => 'aqa.',
		),
		'STAGING' => array(
			'db_read_only' => TRUE,
			'domain_prefix' => 'staging.',
		),
	);
	
	/**
	 * Gets the override environment, if one is set. Else, returns the passed
	 * in mode.
	 *
	 * @return string
	 */
	public static function getOverrideEnvironment($mode)
	{
		$application_environment = self::getApplicationEnvironment();
		
		if ($application_environment)
		{
			if (isset(self::$allowed_environment_overrides[$application_environment]['db_read_only'])
				&& self::$allowed_environment_overrides[$application_environment]['db_read_only']
				&& preg_match('/_READONLY$/i', $mode))
			{
				$mode = "{$application_environment}_READONLY";
			}
			else
			{
				$mode = $application_environment;
			}
		}
		
		return $mode;
	}
	
	/**
	 * Returns the domain prefix that is used for this environment, if any.
	 *
	 * @return string
	 */
	public static function getDomainPrefix()
	{
		$application_environment = self::getApplicationEnvironment();
		$prefix = '';
		
		if ($application_environment && isset(self::$allowed_environment_overrides[$application_environment]['domain_prefix']))
		{
			$prefix = self::$allowed_environment_overrides[$application_environment]['domain_prefix'];
		}
		
		return $prefix;
	}
	
	/**
	 * Returns the environment variable APPLICATION_ENVIRONMENT.
	 *
	 * @return string
	 */
	protected static function getApplicationEnvironment()
	{
		$application_environment = FALSE;
		
		if (isset($_SERVER['APPLICATION_ENVIRONMENT'])
			&& array_key_exists(strtoupper($_SERVER['APPLICATION_ENVIRONMENT']), self::$allowed_environment_overrides))
		{
			$application_environment = strtoupper($_SERVER['APPLICATION_ENVIRONMENT']);
		}
		
		return $application_environment;
	}
	
	/**
	 * Returns if the mode you are in supports secure redirects.
	 *
	 * @param string $mode
	 * @return bool
	 */
	public static function allowSecureRedirect($mode)
	{
		$mode = self::getOverrideEnvironment($mode);
		
		switch (strtoupper($mode))
		{
			case 'LIVE':
				$allowed = TRUE;
				break;
			
			default:
				$allowed = FALSE;
				break;
		}
		
		return $allowed;
	}
}

