<?php
/**
 * For normalizing data.
 *
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
class OLP_Data_Normalizer
{
	/**
	 * Feilds that are excluded from normalization.
	 * 
	 * @var array
	 */
	protected static $excluded_from_normalization = array(
		'redirect_time',
		'vehicle_style',
		'vehicle_make',
		'vehicle_series',
		'vehicle_model',
	);

	/**
	 * Normalize input data to prevent form post hijacking.
	 * 
	 * @param array|OLPBlackbox_Data &$data
	 * @return void
	 */
	public static function normalize(array &$data)
	{
		foreach (self::getNormalizableFields($data) as $field)
		{
			if (is_string($data[$field]))
			{
				$data[$field] = str_replace(array("\r", "\n\n", '<', '>'), '', $data[$field]);
				$data[$field] = stripslashes($data[$field]);
				$data[$field] = str_replace(array("'", '"'), '', $data[$field]);

				$data[$field] = str_replace(array('(', ')'), array('&#40;', '&#41;'), $data[$field]);
			}
		}
	}

	/**
	 * Denormalize input data.
	 * 
	 * @param array|OLPBlackbox_Data &$data
	 * @return void
	 */
	public static function deNormalize(&$data)
	{
		foreach (self::getNormalizableFields($data) as $field)
		{
			if (is_string($data[$field]))
			{
				$data[$field] = str_replace(array('&#40;', '&#41;'), array('(', ')'), $data[$field]);
			}
		}
	}

	/**
	 * Get fields that will be normalized.
	 * 
	 * @param array|OLPBlackbox_Data &$data
	 * @return array
	 */
	protected static function getNormalizableFields(&$data)
	{
		$keys = self::getKeys($data);
		return array_diff($keys, self::$excluded_from_normalization);
	}

	/**
	 * Get keys.
	 * 
	 * @param array|OLPBlackbox_Data &$data
	 * @return array
	 */
	protected static function getKeys(&$data)
	{
		if ($data instanceof OLPBlackbox_Data)
		{
			return $data->getKeys();
		}
		elseif (is_array($data))
		{
			return array_keys($data);
		}

		return array();
	}
}
