<?php
/**
 * Class to determine the qualify amount for Agean companies.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount extends OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount
{
	/**
	 * Determines whether the rule has enough information to run.
	 *
	 * @param Blackbox_Data $data Information about the app being processed.
	 * @param Blackbox_IStateData $state_data Info about the ITarget running us.
	 * 
	 * @return bool Whether or not the rule can run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data) 
	{
		$valid = TRUE;
		
		if (!empty($this->getConfig()->title_loan))
		{
			// Title loans need either a VIN or a make/model/series/style/year
			$valid = (isset($data->vehicle_vin)
				|| (isset($data->vehicle_make)
					&& isset($data->vehicle_model)
					&& isset($data->vehicle_series)
					&& isset($data->vehicle_style)
					&& isset($data->vehicle_year))
				);
		}
		
		return ($valid && parent::canRun($data, $state_data));
	}
	
	/**
	 * Put in title loan specific data, if needed, to rule set.
	 * 
	 * The rule set gets passed to the LoanAmountCalculator class. In addition
	 * to the things needed by the parent, Agean handles title loans so
	 * we may need to pass that as well.
	 *
	 * @param array $rules rule info array to be used in calculating the amount qualified
	 * @param Blackbox_Data $data information about the state of the application
	 * @param Blackbox_IStateData $state_data information about the ITarget running this rule
	 * 
	 * @return void
	 */
	protected function getUserRules($rules, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->getConfig()->title_loan)
		{
			$rules[] = array('name' => 'vehicle_vin', 'value' => $data->vehicle_vin);
			$rules[] = array('name' => 'vehicle_make', 'value' => $data->vehicle_make);
			$rules[] = array('name' => 'vehicle_year', 'value' => $data->vehicle_year);
			$rules[] = array('name' => 'vehicle_model', 'value' => $data->vehicle_model);
			$rules[] = array('name' => 'vehicle_style', 'value' => $data->vehicle_style);
			$rules[] = array('name' => 'vehicle_series', 'value' => $data->vehicle_series);
		}

		$rules = parent::getUserRules($rules, $data, $state_data);
		return $rules;
	}
}

?>
