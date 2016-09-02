<?php
/**
 * Condor configuration class
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_Config
{
	/**
	 * Template map
	 *
	 * @var array
	 */
	protected static $template_map = array(
		'JIFFY' => array(
			'Default' => array(
				OLPCondor_Template::TYPE_LOAN => 'Loan Documents CA',
			),
		),
		'CBNK' => array(
			'Default' => array(
				OLPCondor_Template::TYPE_LOAN => 'Loan Documents DE Payday',
				OLPCondor_Template::TYPE_TITLE => 'Loan Documents DE Title',
			),
		),

		'MMP' => array(
			'Default' => array(
				OLPCondor_Template::TYPE_LOAN => 'Utah eCash DDL Agreement',
				OLPCondor_Template::TYPE_RENEWAL => 'Utah eCash Renewal Agreement',
			),
		),

		'UNIT_TEST' => array(
			'Loan_Types' => array(
				'ST' => array(
					OLPCondor_Template::TYPE_LOAN => 'Unit Test ST Loan',
					OLPCondor_Template::TYPE_RENEWAL => 'Unit Test ST Renewal',
				),
			),
			'Default' => array(
				OLPCondor_Template::TYPE_LOAN => 'Unit Test Default Loan',
				OLPCondor_Template::TYPE_TITLE => 'Unit Test Default Title',
			),
		),
	);
	
	/**
	 * Get the base configuration object for a property short 
	 *
	 * @param string $property_short
	 * @return stdClass
	 */
	public static function getConfig($property_short)
	{
		$return = new stdClass();
		$resolved_property = strtoupper(EnterpriseData::resolveAlias($property_short));
		if (isset(self::$template_map[$resolved_property]))
		{
			$return->templates = new stdClass();
			
			$return->templates->defaults = new stdClass();
			if (isset(self::$template_map[$resolved_property]['Default']) && count(self::$template_map[$resolved_property]['Default'] > 0))
			{
				foreach (self::$template_map[$resolved_property]['Default'] as $key => $value)
				{
					$return->templates->defaults->{$key} = $value;
				}
			}
			
			
			$return->templates->loan_types = new stdClass();
			if (isset(self::$template_map[$resolved_property]['Loan_Types']) && count(self::$template_map[$resolved_property]['Loan_Types'] > 0))
			{
				foreach (self::$template_map[$resolved_property]['Loan_Types'] as $loan_type => $templates)
				{
					$return->templates->loan_types->{$loan_type} = new stdClass();
					foreach ($templates as $key => $value)
					{
						$return->templates->loan_types->{$loan_type}->{$key} = $value;
					}
				}
			}
		}
		return $return;
	}

	/**
	 * Get templates for property_short
	 *
	 * @param string $property_short
	 * @return stdClass
	 */
	public static function getTemplates($property_short)
	{
		$config = self::getConfig($property_short);
		if (!empty($config) && isset($config->templates))
		{
			$templates = $config->templates;
		}
		else
		{
			$templates = NULL;
		}
		return $templates;
	}
	
	/**
	 * Get templates for a particular loan type for a property short
	 *
	 * @param string $property_short
	 * @param string $loan_type Loan type name short
	 * @return stdClass
	 */
	public static function getLoanTypeTemplates($property_short, $loan_type)
	{
		$templates = self::getTemplates($property_short);
		if (!empty($templates->loan_types)
			&& isset($templates->loan_types->$loan_type))
		{
			$return = $templates->loan_types->$loan_type;
		}
		else
		{
			$return = NULL;
		}
		
		return $return;
	}
	
	/**
	 * Get the default templates for a property short
	 *
	 * @param string $property_short
	 * @return stdClass
	 */
	public static function getDefaultTemplates($property_short)
	{
		$templates = self::getTemplates($property_short);
		if (!empty($templates) && isset($templates->defaults))
		{
			$default_templates = $templates->defaults;
		}
		else
		{
			$default_templates = NULL;
		}
		return $default_templates;
	}
	
	/**
	 * Get a template name
	 *
	 * @param string $property_short
	 * @param state $state Geographical state
	 * @param type $type Document type
	 * @return string
	 */
	public static function getTemplateName($property_short, $loan_type, $type)
	{
		$template = NULL;
		$loan_type_templates = self::getLoanTypeTemplates($property_short, $loan_type);
		if (!empty($loan_type_templates) && isset($loan_type_templates->$type))
		{
			$template = $loan_type_templates->{$type};
		}
		else
		{
			$type_templates = self::getDefaultTemplates($property_short);
			if (isset($type_templates->$type))
			{
				$template = $type_templates->$type;
			}
		}
		return $template;
	}
	
	/**
	 * Get all templates of a type from a property short by state
	 *
	 * @param string $property_short
	 * @param string $type
	 * @return stdClass
	 */
	public static function getTypeTemplates($property_short, $type)
	{
		$return = new stdClass();
		$templates = self::getTemplates($property_short);
		if (count($templates) > 0)
		{
			if (isset($templates->loan_types) && count($templates->loan_types) > 0)
			{
				foreach ($templates->loan_types as $loan_type => $loan_type_templates)
				{
					if (isset($loan_type_templates->$type))
					{
						$return->$loan_type = $loan_type_templates->$type;
					}
				}
			}
			if (isset($templates->defaults) && count($templates->defaults) > 0)
			{
				if (isset($templates->defaults->$type))
				{
					$return->default = $templates->defaults->$type;
				}
			}
		}
		return $return;
	}
}

?>
