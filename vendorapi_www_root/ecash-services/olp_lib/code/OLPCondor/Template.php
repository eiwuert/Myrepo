<?php
/**
 * Condor template class for getting and validating template names
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPCondor_Template
{
	/*
	 * Type constant definitions
	 */
	const TYPE_LOAN = 'loan';
	const TYPE_CARD = 'card';
	const TYPE_TITLE = 'title';
	const TYPE_RENEWAL = 'renewal';
	const TYPE_CSA = 'csa';
	const TYPE_NOTE = 'note';
	
	/**
	 * Default template names
	 *
	 * @var array
	 */
	protected static $default_names = 
		array(
			self::TYPE_LOAN => 'Loan Document',
			self::TYPE_CARD => 'Card Loan Document',
			self::TYPE_CSA  => 'Credit Services Agreement',
			self::TYPE_NOTE => 'Online Note',
		);
	
	/**
	 * Get template name
	 *
	 * @param string $property_short Property short for Enterprise company
	 * @param string $type Type of document
	 * @param string $loan_type Loan type for the document
	 * @return string
	 */
	public static function getName($property_short, $type = self::TYPE_LOAN, $loan_type = 'standard')
	{
		$template = OLPCondor_Config::getTemplateName($property_short, $loan_type, $type);
		if (empty($template) && isset(self::$default_names[$type]))
		{
			$template = self::$default_names[$type];
		}
		return $template;
	}
	
	/**
	 * Determine if a template is valid for the property_short based on the parameters
	 *
	 * @param string $name Name of the template
	 * @param string $property_short
	 * @param string $type Type of template
	 * @param string $loan_type Loan type for the document
	 * @return bool
	 */
	public static function valid($name, $property_short, $type = NULL, $loan_type = NULL)
	{
		$valid_templates = array();
		// If both type and state are provided, use the getName funciton to get the template
		if (!empty($loan_type) && !empty($type))
		{
			$valid_templates[] = self::getName($property_short, $type, $loan_type);
		}
		elseif (!empty($loan_type))
		{
			// Get state templates
			$templates = OLPCondor_Config::getLoanTypeTemplates($property_short, $loan_type);
			// If we have at least one template, cycle through them and add to valid templates
			if (count($templates) > 0)
			{
				foreach ($templates as $template)
				{
					$valid_templates[] = $template;
				}
			}
			// Add all default templates for types not accounted for
			foreach (self::$default_names as $type_index => $type_value)
			{
				if (!isset($templates->$type_index))
				{
					$valid_templates[] = $type_value;
				}
			}
		}
		elseif (!empty($type))
		{
			// Get type templates
			$templates = OLPCondor_Config::getTypeTemplates($property_short, $type);
			// If we have at least one template, cycle through them and add to valid templates
			if (count($templates) > 0)
			{
				foreach ($templates as $template)
				{
					$valid_templates[] = $template;
				}
			}
			// Add all default templates for types not accounted for
			foreach (self::$default_names as $type_index => $type_value)
			{
				if (!isset($templates->$type_index) && strcasecmp($type_index, $type) === 0)
				{
					$valid_templates[] = $type_value;
				}
			}
		}
		else
		{
			// No parameters supplied, get all templates for the property_short
			$templates = OLPCondor_Config::getTemplates($property_short);
			
			if (isset($templates->loan_types) && count($templates->loan_types) > 0)
			{
				// Add all loan_type templates for property short
				foreach ($templates->loan_types as $loan_type_templates)
				{
					if (count($loan_type_templates) > 0)
					{
						foreach ($loan_type_templates as $loan_type_template)
						{
							$valid_templates[] = $loan_type_template;
						}
					}
				}
			}
			// Add all default templates for property short
			if (isset($templates->default) && count($templates->default) > 0)
			{
				foreach ($templates->default as $default_template)
				{
					$valid_templates[] = $default_template;
				}
			}
			// Add all global default templates as well
			foreach (self::$default_names as $type_index => $type_value)
			{
				$valid_templates[] = $type_value;
			}
		}
		// If the name is in the valid templates array, it is valid
		$valid = in_array($name, $valid_templates);
		return $valid;
	}
}

?>
